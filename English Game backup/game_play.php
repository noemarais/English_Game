<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$gameId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$maxRounds = 5;

if ($gameId <= 0) {
    header('Location: home.php');
    exit;
}

// Récupération de la partie
$stmt = $db->prepare("
    SELECT g.id, g.host_id, g.code, g.nom, g.status, g.created_at
    FROM game_session g
    WHERE g.id = ?
");
$stmt->execute([$gameId]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header('Location: home.php');
    exit;
}

$isHost = ((int) $game['host_id'] === $currentUserId);

// Vérifier que l'utilisateur appartient à cette partie
$check = $db->prepare("
    SELECT id
    FROM game_players
    WHERE game_id = ? AND user_id = ?
");
$check->execute([$gameId, $currentUserId]);
$isPlayer = $check->fetch(PDO::FETCH_ASSOC);

if (!$isPlayer) {
    header('Location: home.php');
    exit;
}

// gp_id du joueur courant (utile pour d'autres évolutions si besoin)
$myGpId = (int) $isPlayer['id'];

// Liste des joueurs de la partie
$playersStmt = $db->prepare("
    SELECT gp.id AS gp_id, j.nom, gp.total_points, gp.manches_gagnees
    FROM game_players gp
    JOIN joueurs j ON j.user_id = gp.user_id
    WHERE gp.game_id = ?
    ORDER BY gp.id ASC
");
$playersStmt->execute([$gameId]);
$players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($players)) {
    header('Location: game_lobby.php?id=' . $gameId);
    exit;
}

// Dernière manche enregistrée (toutes manches confondues)
$roundStmt = $db->prepare("
    SELECT COALESCE(MAX(gr.round_number), 0) AS last_round
    FROM game_rounds gr
    JOIN game_players gp ON gp.id = gr.game_player_id
    WHERE gp.game_id = ?
");
$roundStmt->execute([$gameId]);
$roundRow = $roundStmt->fetch(PDO::FETCH_ASSOC);
$lastRound = (int) $roundRow['last_round'];
$nextRound = $lastRound + 1;

// Si on dépasse le nombre max de manches, fin de partie
if ($nextRound > $maxRounds) {
    header('Location: end_game.php?id=' . $gameId);
    exit;
}

$error = '';

// ----------------------------------------------------
// HOST: validation de manche / fin de partie
// → Les points sont déjà dans game_rounds via submit_points.php
// → Ici on recalcule manches_gagnees et éventuellement on clôture la game.
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isHost) {
    $action = $_POST['action'] ?? 'next_round';

    try {
        $db->beginTransaction();

        // Recalcule COMPLET des manches gagnées à partir de game_rounds
        // 1) remettre à zéro les manches_gagnees
        $resetWins = $db->prepare("
            UPDATE game_players
            SET manches_gagnees = 0
            WHERE game_id = ?
        ");
        $resetWins->execute([$gameId]);

        // 2) récupérer toutes les manches existantes pour cette partie
        $roundNumbersStmt = $db->prepare("
            SELECT DISTINCT gr.round_number
            FROM game_rounds gr
            JOIN game_players gp ON gp.id = gr.game_player_id
            WHERE gp.game_id = ?
            ORDER BY gr.round_number ASC
        ");
        $roundNumbersStmt->execute([$gameId]);
        $roundNumbers = $roundNumbersStmt->fetchAll(PDO::FETCH_COLUMN);

        $lastRecordedRound = 0;

        if (!empty($roundNumbers)) {
            // Pour chaque manche, déterminer le(s) gagnant(s)
            $roundDetailsStmt = $db->prepare("
                SELECT gr.game_player_id, gr.points
                FROM game_rounds gr
                JOIN game_players gp ON gp.id = gr.game_player_id
                WHERE gp.game_id = ? AND gr.round_number = ?
            ");

            $incWin = $db->prepare("
                UPDATE game_players
                SET manches_gagnees = manches_gagnees + 1
                WHERE id = ?
            ");

            foreach ($roundNumbers as $rnum) {
                $rnum = (int) $rnum;
                $lastRecordedRound = max($lastRecordedRound, $rnum);

                $roundDetailsStmt->execute([$gameId, $rnum]);
                $rows = $roundDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($rows)) {
                    continue;
                }

                // Max de points pour cette manche
                $maxPoints = 0;
                foreach ($rows as $row) {
                    $pts = (int) $row['points'];
                    if ($pts > $maxPoints) {
                        $maxPoints = $pts;
                    }
                }

                if ($maxPoints > 0) {
                    foreach ($rows as $row) {
                        if ((int) $row['points'] === $maxPoints) {
                            $incWin->execute([(int) $row['game_player_id']]);
                        }
                    }
                }
            }
        }

        // Si on doit terminer la partie ou que l'on a déjà joué le max de manches
        $shouldFinish = ($action === 'end_game') || ($lastRecordedRound >= $maxRounds);

        if ($shouldFinish) {
            // Classement final basé sur total_points (déjà mis à jour par submit_points.php)
            $rankStmt = $db->prepare("
                SELECT id, total_points
                FROM game_players
                WHERE game_id = ?
                ORDER BY total_points DESC
            ");
            $rankStmt->execute([$gameId]);
            $rows = $rankStmt->fetchAll(PDO::FETCH_ASSOC);

            $pos = 1;
            $updatePos = $db->prepare("
                UPDATE game_players
                SET position_finale = ?
                WHERE id = ?
            ");
            foreach ($rows as $r) {
                $updatePos->execute([$pos, (int) $r['id']]);
                $pos++;
            }

            // Mettre la game en finished
            $updGame = $db->prepare("
                UPDATE game_session
                SET status = 'finished',
                    finished_at = NOW(),
                    duration_seconds = TIMESTAMPDIFF(SECOND, created_at, NOW())
                WHERE id = ?
            ");
            $updGame->execute([$gameId]);

            $db->commit();

            header('Location: end_game.php?id=' . $gameId);
            exit;
        }

        // Sinon on continue vers la manche suivante
        $db->commit();
        header('Location: game_play.php?id=' . $gameId);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $error = "Erreur lors de la validation de la manche.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Partie en cours</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="game.css">
</head>
<body class="game-page">

<div class="game-container">
    <header class="game-header">
        <button class="game-back-btn" onclick="if(confirm('Quitter la partie et revenir au lobby ?')){ window.location.href='game_lobby.php?id=<?php echo $gameId; ?>'; }">
            ← Lobby
        </button>
        <h1 class="title-font">Manche <?php echo $nextRound; ?>/<?php echo $maxRounds; ?></h1>
    </header>

    <?php if ($error): ?>
        <p class="game-error text-font"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <section class="game-card" 
             data-game-id="<?php echo (int)$gameId; ?>" 
             data-user-id="<?php echo (int)$currentUserId; ?>"
             data-my-gp-id="<?php echo $myGpId; ?>"
             data-is-host="<?php echo $isHost ? '1' : '0'; ?>"
             data-round="<?php echo (int)$nextRound; ?>"
             data-max-round="<?php echo (int)$maxRounds; ?>">

        <div class="timer-block">
            <div class="timer-label text-font">Timer</div>
            <div class="timer-display title-font" id="timerDisplay">05:00</div>
            <?php if ($isHost): ?>
                <div class="timer-actions">
                    <button type="button" class="btn-secondary text-font" id="timerToggle">Lancer</button>
                    <button type="button" class="btn-secondary text-font" id="timerReset">Réinitialiser</button>
                </div>
                <audio id="timerSound" src="medias/alarm.mp3" preload="auto"></audio>
            <?php endif; ?>
        </div>

        <!-- Bloc commun: chacun saisit SES points -->
        <h3 class="title-font">Tes points pour cette manche</h3>
        <div class="round-players-list">
            <div class="round-player-row">
                <div class="round-player-name text-font">
                    <?php echo htmlspecialchars($_SESSION['nom']); ?>
                </div>
                <input
                    type="number"
                    id="myPointsInput"
                    class="round-input"
                    min="0"
                    step="1"
                    placeholder="Points"
                >
            </div>
        </div>
        <button type="button"
                class="btn-primary text-font"
                id="btnSendMyPoints"
                style="margin-top:10px;">
            Valider mes points
        </button>

        <!-- Scoreboard actuel -->
        <h3 class="title-font" style="margin-top:20px;">Scores actuels</h3>
        <div class="round-players-list">
            <?php foreach ($players as $p): ?>
                <div class="round-player-row">
                    <div class="round-player-name text-font">
                        <?php echo htmlspecialchars($p['nom']); ?>
                    </div>
                    <div class="text-font"><?php echo (int) $p['total_points']; ?> pts</div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($isHost): ?>
            <!-- Contrôles host: manche suivante / fin de game -->
            <div class="round-actions" style="margin-top:20px;">
                <form method="POST" id="roundForm">
                    <input type="hidden" name="action" id="roundAction" value="next_round">
                    <button type="submit"
                            class="btn-primary text-font"
                            id="btnNextRound">
                        Manche suivante
                    </button>
                    <button type="submit"
                            class="btn-secondary text-font"
                            id="btnEndGame">
                        Terminer le jeu
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const card = document.querySelector('.game-card');
    if (!card) return;

    const gameId = parseInt(card.dataset.gameId, 10);
    const userId = parseInt(card.dataset.userId, 10);
    const isHost = card.dataset.isHost === '1';
    const currentRound = parseInt(card.dataset.round, 10);
    const maxRound = parseInt(card.dataset.maxRound, 10);

    const myPointsInput = document.getElementById('myPointsInput');
    const btnSendMyPoints = document.getElementById('btnSendMyPoints');

    // Au début: personne ne saisit tant que le timer n'est pas fini
    if (myPointsInput) myPointsInput.disabled = true;
    if (btnSendMyPoints) btnSendMyPoints.disabled = true;

    // Connexion WebSocket
    let ws;
    try {
        const WS_BASE = 'wss://noe-marais.mds-angers.yt/ws';

        ws = new WebSocket(
            WS_BASE
            + '?game_id=' + gameId
            + '&user_id=' + userId
            + '&name=' + encodeURIComponent(playerName)
        );
    } catch (e) {
        console.warn('WebSocket non disponible', e);
    }

    // TIMER partagé
    const display = document.getElementById('timerDisplay');
    const toggleBtn = document.getElementById('timerToggle');
    const resetBtn = document.getElementById('timerReset');
    const sound = document.getElementById('timerSound');

    let remaining = 300;
    let intervalId = null;
    let timerUnlocked = false;

    function formatTime(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    function updateDisplay() {
        if (display) display.textContent = formatTime(remaining);
    }

    function setHostButtonsEnabled(enabled) {
        const btnNext = document.getElementById('btnNextRound');
        const btnEnd = document.getElementById('btnEndGame');

        if (btnNext) btnNext.disabled = !enabled;
        if (btnEnd) btnEnd.disabled = !enabled;
    }

    // Etat initial: le host ne peut pas valider la manche avant la fin du timer
    if (isHost) {
        setHostButtonsEnabled(false);
    }

    // Host: gestion du timer + broadcast
    function tickHost() {
        if (remaining > 0) {
            remaining--;
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'timer_tick',
                    gameId,
                    remaining
                }));
            }
            updateDisplay();
            if (remaining === 0) {
                clearInterval(intervalId);
                intervalId = null;
                if (toggleBtn) toggleBtn.textContent = 'Lancer';
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'timer_done',
                        gameId
                    }));
                }
                if (sound) {
                    sound.currentTime = 0;
                    sound.play().catch(() => {});
                }
                timerUnlocked = true;

                // Tout le monde peut saisir ses points, host inclus
                if (myPointsInput) myPointsInput.disabled = false;
                if (btnSendMyPoints) btnSendMyPoints.disabled = false;

                if (isHost) {
                    setHostButtonsEnabled(true);
                }

                alert('Temps écoulé');
            }
        }
    }

    if (isHost && display && toggleBtn && resetBtn) {
        remaining = 300;
        updateDisplay();

        toggleBtn.addEventListener('click', () => {
            if (intervalId === null) {
                intervalId = setInterval(tickHost, 1000);
                toggleBtn.textContent = 'Pause';
            } else {
                clearInterval(intervalId);
                intervalId = null;
                toggleBtn.textContent = 'Lancer';
            }
        });

        resetBtn.addEventListener('click', () => {
            clearInterval(intervalId);
            intervalId = null;
            remaining = 300;
            updateDisplay();
            toggleBtn.textContent = 'Lancer';
            timerUnlocked = false;
            setHostButtonsEnabled(false);

            if (myPointsInput) myPointsInput.disabled = true;
            if (btnSendMyPoints) btnSendMyPoints.disabled = true;

            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'timer_tick',
                    gameId,
                    remaining
                }));
            }
        });
    }

    // Envoi des points de chaque joueur vers submit_points.php
    if (myPointsInput && btnSendMyPoints) {
        btnSendMyPoints.addEventListener('click', () => {
            const val = parseInt(myPointsInput.value, 10);
            if (isNaN(val) || val < 0) {
                alert('Entre un nombre de points valide.');
                return;
            }

            if (!timerUnlocked) {
                alert('Tu dois attendre la fin du timer pour valider tes points.');
                return;
            }

            const body = new URLSearchParams();
            body.append('game_id', gameId);
            body.append('round', currentRound);
            body.append('points', val);

            fetch('submit_points.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        alert('Tes points ont été enregistrés.');
                        myPointsInput.disabled = true;
                        btnSendMyPoints.disabled = true;
                    } else {
                        alert(data.message || 'Erreur lors de lenregistrement des points.');
                    }
                })
                .catch(() => {
                    alert('Erreur réseau lors de lenvoi des points.');
                });
        });
    }

    // Réception des messages WebSocket
    if (ws) {
        ws.addEventListener('message', (event) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (e) {
                return;
            }

            if (data.gameId != gameId) return;

            // Timer sync
            if (data.type === 'timer_tick') {
                remaining = data.remaining;
                updateDisplay();
            }

            if (data.type === 'timer_done') {
                timerUnlocked = true;

                if (myPointsInput) myPointsInput.disabled = false;
                if (btnSendMyPoints) btnSendMyPoints.disabled = false;

                if (isHost) {
                    setHostButtonsEnabled(true);
                }

                alert('Temps écoulé');
            }

            // Changement de manche pour tout le monde
            if (data.type === 'round_changed') {
                window.location.href = 'game_play.php?id=' + gameId;
            }

            // Fin de partie pour tout le monde
            if (data.type === 'game_ended') {
                window.location.href = 'end_game.php?id=' + gameId;
            }
        });
    }

    // Host: validation manche / fin de game + broadcast
    if (isHost) {
        const form = document.getElementById('roundForm');
        const actionInput = document.getElementById('roundAction');
        const btnNext = document.getElementById('btnNextRound');
        const btnEnd = document.getElementById('btnEndGame');

        // Si on est déjà à la dernière manche, on bloque "manche suivante"
        if (currentRound >= maxRound && btnNext) {
            btnNext.disabled = true;
        }

        if (form && actionInput) {
            form.addEventListener('submit', (e) => {
                if (!timerUnlocked) {
                    e.preventDefault();
                    alert('Tu dois attendre la fin du timer avant de valider la manche.');
                    return;
                }

                const isEnd = (e.submitter && e.submitter.id === 'btnEndGame');

                if (isEnd) {
                    actionInput.value = 'end_game';

                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            type: 'game_ended',
                            gameId
                        }));
                    }
                } else {
                    actionInput.value = 'next_round';

                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            type: 'round_changed',
                            gameId,
                            nextRound: currentRound + 1
                        }));
                    }
                }
            });
        }
    }
});
</script>

</body>
</html>
