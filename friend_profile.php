<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$friendId      = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($friendId <= 0 || $friendId === $currentUserId) {
    header('Location: home.php');
    exit;
}

// Vérifier que c est bien un ami (relation accepted dans les deux sens)
$checkStmt = $db->prepare("
    SELECT id FROM friends
    WHERE status = 'accepted'
      AND (
            (requester_id = ? AND requested_id = ?)
         OR (requester_id = ? AND requested_id = ?)
      )
    LIMIT 1
");
$checkStmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);
$relation = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$relation) {
    header('Location: home.php');
    exit;
}

// Récup info ami
$userStmt = $db->prepare("SELECT nom, avatar_path FROM joueurs WHERE user_id = ?");
$userStmt->execute([$friendId]);
$friend = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$friend) {
    header('Location: home.php');
    exit;
}

$avatarPath = !empty($friend['avatar_path']) ? $friend['avatar_path'] : 'avatars/avatar1.png';

// Stats globales de l ami
$statsStmt = $db->prepare("
    SELECT 
        COUNT(*) AS total_games,
        COALESCE(SUM(total_points), 0) AS total_points,
        COALESCE(SUM(manches_gagnees), 0) AS total_wins,
        COALESCE(AVG(position_finale), 0) AS avg_position,
        COALESCE(AVG(total_points), 0) AS avg_points_per_game
    FROM game_players
    WHERE user_id = ?
");
$statsStmt->execute([$friendId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalGames        = (int) ($stats['total_games'] ?? 0);
$totalPoints       = (int) ($stats['total_points'] ?? 0);
$totalWins         = (int) ($stats['total_wins'] ?? 0);
$avgPosition       = $totalGames > 0 ? round((float) $stats['avg_position'], 1) : 0;
$avgPointsPerGame  = $totalGames > 0 ? round((float) $stats['avg_points_per_game'], 1) : 0;

$gamesLabel = $totalGames . ' Parties';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil ami</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stats.css">
    <style>
        .friend-remove-btn {
            margin-top: 18px;
            background: #E74C3C;
            color: #ffffff;
            border: none;
            padding: 10px 14px;
            border-radius: 999px;
            cursor: pointer;
            font-family: "Montserrat", sans-serif;
            font-size: 13px;
        }
        .stats-subtitle {
            font-size: 13px;
            color: #777;
            margin-top: 4px;
        }
        .back-header {
            max-width: 430px;
            margin: 0 auto;
            padding: 12px 18px 0;
            position: relative;
            z-index: 5;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-family: "Montserrat", sans-serif;
            font-size: 13px;
            color: #ffffff;
        }
        .back-arrow-icon {
            width: 18px;
            height: 18px;
            border-left: 2px solid #ffffff;
            border-bottom: 2px solid #ffffff;
            transform: rotate(45deg);
        }
    </style>
</head>
<body>

<!-- Petit header avec flèche retour -->
<div class="back-header">
    <button class="back-btn" id="friendBackBtn">
        <span class="back-arrow-icon"></span>
        <span>Retour</span>
    </button>
</div>

<div class="stats-app">

    <div class="stats-hero"></div>

    <section class="stats-card">
        <header class="stats-card-header">
            <div>
                <h1 class="stats-name"><?php echo htmlspecialchars($friend['nom']); ?></h1>
                <div class="stats-subtitle text-font">Résumé de ses stats</div>
            </div>
            <div class="avatar-circle">
                <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
            </div>
        </header>

        <div class="stats-grid">
            <article class="stat-box stat-box-primary">
                <h2 class="stat-title">NUMBER OF WIN</h2>
                <div class="stat-value"><?php echo $totalWins; ?></div>
                <div class="stat-sub">in <?php echo $gamesLabel; ?></div>
            </article>

            <article class="stat-box stat-box-secondary">
                <h2 class="stat-title">NUMBER OF POINTS</h2>
                <div class="stat-value"><?php echo $totalPoints; ?></div>
                <div class="stat-sub">in <?php echo $gamesLabel; ?></div>
            </article>

            <article class="stat-box stat-box-primary">
                <h2 class="stat-title">AVERAGE POSITION</h2>
                <div class="stat-value"><?php echo $avgPosition; ?></div>
                <div class="stat-sub">in <?php echo $gamesLabel; ?></div>
            </article>

            <article class="stat-box stat-box-secondary">
                <h2 class="stat-title">AVG POINTS / GAME</h2>
                <div class="stat-value"><?php echo $avgPointsPerGame; ?></div>
                <div class="stat-sub">in <?php echo $gamesLabel; ?></div>
            </article>
        </div>

        <button class="friend-remove-btn" id="removeFriendBtn" data-friend-id="<?php echo $friendId; ?>">
            Supprimer des amis
        </button>
    </section>
</div>

<div class="stats-bottom-nav">
    <div class="stats-wheel" id="friendBackWheel">
        <div class="stats-arrow"></div>
        <div class="stats-user-icon"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Roue bas pour retour home
    const wheel = document.getElementById('friendBackWheel');
    if (wheel) {
        wheel.addEventListener('click', () => {
            wheel.classList.add('rotating');
            setTimeout(() => {
                window.location.href = 'home.php';
            }, 520);
        });
    }

    // Bouton retour dans le header (revenir en arrière dans l historique si possible)
    const backBtn = document.getElementById('friendBackBtn');
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = 'home.php';
            }
        });
    }

    // Supprimer ami
    const removeBtn = document.getElementById('removeFriendBtn');
    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            if (!confirm('Supprimer cet ami ?')) return;
            const friendId = removeBtn.getAttribute('data-friend-id');

            fetch('friend_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=remove&friend_id=' + encodeURIComponent(friendId)
            }).then(() => {
                window.location.href = 'home.php';
            });
        });
    }
});
</script>

</body>
</html>
