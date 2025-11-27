<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$gameId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$notifyFriendId = isset($_GET['notify_friend_id']) ? (int) $_GET['notify_friend_id'] : 0;

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

// Vérifier que l'utilisateur est dans la partie (au moins le host)
$check = $db->prepare("
    SELECT id FROM game_players
    WHERE game_id = ? AND user_id = ?
");
$check->execute([$gameId, $currentUserId]);
$isPlayer = $check->fetch(PDO::FETCH_ASSOC);

if (!$isPlayer) {
    header('Location: home.php');
    exit;
}

$isHost = ((int) $game['host_id'] === $currentUserId);
$error = '';
$success = '';

$notifyFriendName = '';

if ($notifyFriendId > 0) {
    $nfStmt = $db->prepare("SELECT nom FROM joueurs WHERE user_id = ?");
    $nfStmt->execute([$notifyFriendId]);
    $nfRow = $nfStmt->fetch(PDO::FETCH_ASSOC);
    if ($nfRow) {
        $notifyFriendName = $nfRow['nom'];
    } else {
        $notifyFriendId = 0;
    }
}

// Actions host
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isHost) {
    $action = $_POST['action'] ?? '';

    // Inviter un ami depuis le lobby
    if ($action === 'add_friend') {
        $friendId = isset($_POST['friend_id']) ? (int) $_POST['friend_id'] : 0;

        if ($friendId > 0 && $friendId !== $currentUserId) {

            // Nombre de joueurs dans la partie
            $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM game_players WHERE game_id = ?");
            $countStmt->execute([$gameId]);
            $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
            $nbPlayers = (int) $countRow['c'];

            if ($nbPlayers >= 6) {
                $error = "La partie est déjà pleine (6 joueurs).";
            } else {
                // Vérifier que c'est un ami
                $fStmt = $db->prepare("
                    SELECT j.user_id
                    FROM friends f
                    JOIN joueurs j ON (
                            (j.user_id = f.requested_id AND f.requester_id = ?)
                         OR (j.user_id = f.requester_id AND f.requested_id = ?)
                    )
                    WHERE f.status = 'accepted'
                      AND j.user_id = ?
                    LIMIT 1
                ");
                $fStmt->execute([$currentUserId, $currentUserId, $friendId]);
                $friendOk = $fStmt->fetch(PDO::FETCH_ASSOC);

                if (!$friendOk) {
                    $error = "Ce joueur n'est pas ton ami.";
                } else {
                    // Vérifier s'il n'est pas déjà dans la partie
                    $checkP = $db->prepare("
                        SELECT id FROM game_players
                        WHERE game_id = ? AND user_id = ?
                    ");
                    $checkP->execute([$gameId, $friendId]);
                    $already = $checkP->fetch(PDO::FETCH_ASSOC);

                    if ($already) {
                        $error = "Ce joueur est déjà dans la partie.";
                    } else {
                        // Vérifier s'il n'a pas déjà une invitation en attente
                        $invCheck = $db->prepare("
                            SELECT id
                            FROM game_invitations
                            WHERE game_id = ?
                              AND inviter_id = ?
                              AND invited_id = ?
                              AND status = 'pending'
                        ");
                        $invCheck->execute([$gameId, $currentUserId, $friendId]);
                        $invExists = $invCheck->fetch(PDO::FETCH_ASSOC);

                        if ($invExists) {
                            $error = "Invitation déjà envoyée à ce joueur.";
                        } else {
                            // Création de l'invitation
                            $invInsert = $db->prepare("
                                INSERT INTO game_invitations (game_id, inviter_id, invited_id, status, created_at)
                                VALUES (?, ?, ?, 'pending', NOW())
                            ");
                            $invInsert->execute([$gameId, $currentUserId, $friendId]);
                            $success = "Invitation envoyée.";
                        }
                    }
                }
            }
        }
    }

    // Démarrer la partie
    if ($action === 'start_game') {
        $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM game_players WHERE game_id = ?");
        $countStmt->execute([$gameId]);
        $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
        $nbPlayers = (int) $countRow['c'];

        if ($nbPlayers < 2) {
            $error = "Il faut au moins 2 joueurs pour démarrer la partie.";
        } else {
            $upd = $db->prepare("UPDATE game_session SET status = 'in_progress' WHERE id = ?");
            $upd->execute([$gameId]);

            header("Location: game_play.php?id=" . $gameId);
            exit;
        }
    }
}

// Liste des joueurs
$playersStmt = $db->prepare("
    SELECT gp.user_id, j.nom
    FROM game_players gp
    JOIN joueurs j ON j.user_id = gp.user_id
    WHERE gp.game_id = ?
    ORDER BY gp.id ASC
");
$playersStmt->execute([$gameId]);
$players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);
$playerIds = array_map(fn($p) => (int) $p['user_id'], $players);
$nbPlayers = count($players);

// Amis invitables
$invitableFriends = [];
if ($isHost && $nbPlayers < 6) {
    $friendsStmt = $db->prepare("
        (
            SELECT j.user_id, j.nom
            FROM friends f
            JOIN joueurs j ON j.user_id = f.requested_id
            WHERE f.requester_id = ? AND f.status = 'accepted'
        )
        UNION
        (
            SELECT j.user_id, j.nom
            FROM friends f
            JOIN joueurs j ON j.user_id = f.requester_id
            WHERE f.requested_id = ? AND f.status = 'accepted'
        )
    ");
    $friendsStmt->execute([$currentUserId, $currentUserId]);
    $allFriends = $friendsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allFriends as $f) {
        if (!in_array((int) $f['user_id'], $playerIds, true)) {
            $invitableFriends[] = $f;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lobby de la partie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="game.css">
</head>
<body class="game-page">

<div class="game-container">
    <header class="game-header">
        <button class="game-back-btn" onclick="window.location.href='home.php'">
            ← Home
        </button>
        <h1 class="title-font">Lobby</h1>
    </header>

    <?php if ($error): ?>
        <p class="game-error text-font"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="game-success text-font"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <section class="game-card">
        <div class="game-info-header">
            <div>
                <h2 class="game-name title-font">
                    <?php echo htmlspecialchars($game['nom'] ?: 'Partie Surf It'); ?>
                </h2>
                <div class="text-font game-sub-info">
                    Joueurs : <?php echo $nbPlayers; ?>/6
                </div>
            </div>
            <span class="game-status text-font game-status-<?php echo htmlspecialchars($game['status']); ?>">
                <?php echo strtoupper($game['status']); ?>
            </span>
        </div>

        <div class="game-code-block">
            <span class="text-font">Code de la partie :</span>
            <div class="game-code-row">
                <span class="game-code"><?php echo htmlspecialchars($game['code']); ?></span>
                <button type="button" class="copy-code-btn text-font"
                        onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($game['code']); ?>')">
                    Copier
                </button>
            </div>
        </div>

        <div class="game-players">
            <h3 class="title-font">Joueurs</h3>
            <ul>
                <?php foreach ($players as $p): ?>
                    <li class="text-font">
                        <?php echo htmlspecialchars($p['nom']); ?>
                        <?php if ((int) $p['user_id'] === (int) $game['host_id']): ?>
                            <span class="host-tag">Hôte</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ($isHost): ?>
            <div class="game-actions-lobby">
                <form method="POST">
                    <input type="hidden" name="action" value="start_game">
                    <button type="submit" class="btn-primary text-font"
                        <?php echo ($nbPlayers < 2) ? 'disabled style="opacity:0.6;cursor:not-allowed;"' : ''; ?>>
                        Démarrer la partie
                    </button>
                </form>
            </div>

            <?php if (!empty($invitableFriends) && $nbPlayers < 6): ?>
                <div class="lobby-invite-box">
                    <h3 class="title-font">Inviter des amis</h3>
                    <?php foreach ($invitableFriends as $f): ?>
                        <div class="lobby-invite-row text-font">
                            <span><?php echo htmlspecialchars($f['nom']); ?></span>
                            <form method="POST"
                                  class="inline-form invite-form"
                                  data-friend-id="<?php echo (int) $f['user_id']; ?>"
                                  data-friend-name="<?php echo htmlspecialchars($f['nom']); ?>"
                                  data-game-code="<?php echo htmlspecialchars($game['code']); ?>"
                                  data-game-name="<?php echo htmlspecialchars($game['nom'] ?: 'Partie Surf It'); ?>"
                                  data-host-name="<?php echo htmlspecialchars($_SESSION['nom']); ?>">
                                <input type="hidden" name="action" value="add_friend">
                                <input type="hidden" name="friend_id" value="<?php echo (int) $f['user_id']; ?>">
                                <button type="submit" class="lobby-add-btn">Inviter</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($nbPlayers >= 6): ?>
                <p class="text-font lobby-full-text">La partie est pleine (6 joueurs).</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const gameId        = <?php echo (int) $gameId; ?>;
    const userId        = <?php echo (int) $currentUserId; ?>;
    const playerName    = <?php echo json_encode($_SESSION['nom']); ?>;
    const notifyFriendId   = <?php echo (int) $notifyFriendId; ?>;
    const notifyFriendName = <?php echo json_encode($notifyFriendName); ?>;
    const gameCode      = <?php echo json_encode($game['code']); ?>;
    const gameName      = <?php echo json_encode($game['nom'] ?: 'Partie Surf It'); ?>;
    const hostName      = <?php echo json_encode($_SESSION['nom']); ?>;

    let ws;

    // URL du WebSocket sur PlanetHoster
    const wsBase = (location.protocol === 'https:' ? 'wss://' : 'ws://')
        + 'english-game.noe-marais.mds-angers.yt/ws';

    const wsUrl = wsBase
        + '?game_id=' + encodeURIComponent(gameId)
        + '&user_id=' + encodeURIComponent(userId)
        + '&name='    + encodeURIComponent(playerName);

    console.log('[WS] tentative de connexion au lobby...', wsUrl);

    try {
        ws = new WebSocket(wsUrl);
    } catch (e) {
        console.error('[WS] exception à la création du WebSocket', e);
        return;
    }

    if (ws) {
        ws.addEventListener('open', () => {
            console.log('[WS] connecté au lobby');

            // On annonce notre présence
            ws.send(JSON.stringify({
                type: 'join_lobby',
                gameId: gameId,
                userId: userId,
                name: playerName
            }));

            // Si on vient de create_game.php avec ?notify_friend_id=...
            if (notifyFriendId > 0) {
                console.log('[WS] envoi d une invitation temps réel à', notifyFriendId);
                ws.send(JSON.stringify({
                    type: 'game_invite_created',
                    gameId: gameId,
                    host_id: userId,
                    host_name: hostName,
                    invited_id: notifyFriendId,
                    invited_name: notifyFriendName,
                    game_name: gameName,
                    game_code: gameCode
                }));
            }
        });

        ws.addEventListener('error', (ev) => {
            console.error('[WS] erreur WebSocket', ev);
        });

        ws.addEventListener('close', () => {
            console.log('[WS] connexion WebSocket fermée');
        });

        ws.addEventListener('message', (event) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (e) {
                console.warn('[WS] message non JSON', event.data);
                return;
            }

            // Juste pour debug
            console.log('[WS] message reçu', data);

            if (Number(data.gameId) !== Number(gameId)) {
                return;
            }

            // Un nouveau joueur est arrivé ou s est connecté: on raffraîchit la liste
            if ((data.type === 'player_joined' || data.type === 'player_connected')
                && Number(data.userId) !== Number(userId)) {
                console.log('[WS] player_joined -> reload lobby');
                location.reload();
            }

            // Début de partie: tous les joueurs vont vers game_play
            if (data.type === 'start_game') {
                console.log('[WS] start_game -> redirection game_play');
                window.location.href = 'game_play.php?id=' + gameId;
            }

            // Fin de game poussée par le host
            if (data.type === 'game_ended') {
                console.log('[WS] game_ended -> redirection end_game');
                window.location.href = 'end_game.php?id=' + gameId;
            }
        });
    }

    // Quand le host clique sur "Démarrer la partie",
    // on envoie aussi un signal WS start_game
    const startForm = document.querySelector('form input[name="action"][value="start_game"]')?.closest('form');
    if (startForm) {
        startForm.addEventListener('submit', () => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'start_game',
                    gameId: gameId,
                    userId: userId
                }));
                console.log('[WS] start_game envoyé');
            }
        });
    }

    // Envoi des invitations depuis le lobby (boutons Inviter)
    const inviteForms = document.querySelectorAll('.invite-form');
    inviteForms.forEach(form => {
        form.addEventListener('submit', () => {
            if (!ws || ws.readyState !== WebSocket.OPEN) return;

            const invitedId   = form.dataset.friendId;
            const invitedName = form.dataset.friendName;
            const gCode       = form.dataset.gameCode;
            const gName       = form.dataset.gameName;
            const hName       = form.dataset.hostName;

            ws.send(JSON.stringify({
                type: 'game_invite_created',
                gameId: gameId,
                host_id: userId,
                host_name: hName,
                invited_id: invitedId,
                invited_name: invitedName,
                game_name: gName,
                game_code: gCode
            }));
            console.log('[WS] game_invite_created envoyé pour', invitedId);
        });
    });
});
</script>


</body>
</html>
