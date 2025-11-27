<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$gameId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($gameId <= 0) {
    header('Location: home.php');
    exit;
}

// Récup game
$stmt = $db->prepare("
    SELECT g.id, g.host_id, g.code, g.nom, g.status, g.created_at, g.finished_at, g.duration_seconds
    FROM game_session g
    WHERE g.id = ?
");
$stmt->execute([$gameId]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    header('Location: home.php');
    exit;
}

// Vérifier que l'utilisateur appartenait à cette partie
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

// Récup classement final
$playersStmt = $db->prepare("
    SELECT gp.id, gp.user_id, gp.total_points, gp.manches_gagnees, gp.position_finale, j.nom
    FROM game_players gp
    JOIN joueurs j ON j.user_id = gp.user_id
    WHERE gp.game_id = ?
    ORDER BY gp.total_points DESC
");
$playersStmt->execute([$gameId]);
$players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fin de partie</title>
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
        <h1 class="title-font">Fin de partie</h1>
    </header>

    <section class="game-card">
        <h2 class="game-name title-font">
            <?php echo htmlspecialchars($game['nom'] ?: 'Partie Surf It'); ?>
        </h2>
        <p class="text-font game-sub-info">
            Code: <?php echo htmlspecialchars($game['code']); ?>
            <?php if (!empty($game['duration_seconds'])): ?>
                - Durée: <?php echo (int)floor($game['duration_seconds'] / 60); ?> min
            <?php endif; ?>
        </p>

        <h3 class="title-font" style="margin-top:14px;">Classement</h3>

        <div class="endgame-list">
            <?php
            $rank = 1;
            foreach ($players as $p):
                $pos = $p['position_finale'] !== null ? (int)$p['position_finale'] : $rank;
            ?>
                <div class="endgame-row">
                    <div class="endgame-left">
                        <div class="endgame-rank">#<?php echo $pos; ?></div>
                        <div class="endgame-name text-font">
                            <?php echo htmlspecialchars($p['nom']); ?>
                        </div>
                    </div>
                    <div class="endgame-right text-font">
                        <div><?php echo (int)$p['total_points']; ?> pts</div>
                        <div class="endgame-roundwins">
                            <?php echo (int)$p['manches_gagnees']; ?> manches gagnées
                        </div>
                    </div>
                </div>
            <?php
                $rank++;
            endforeach;
            ?>
        </div>

        <div style="margin-top:18px;">
            <a href="home.php" class="btn-primary text-font" style="display:block;text-align:center;text-decoration:none;">
                Revenir à l accueil
            </a>
        </div>
    </section>
</div>

</body>
</html>
