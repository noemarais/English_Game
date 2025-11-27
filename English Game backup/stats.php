<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

// Récup joueur + avatar
$userStmt = $db->prepare("SELECT nom, avatar_path FROM joueurs WHERE user_id = ?");
$userStmt->execute([$currentUserId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$avatarPath = !empty($user['avatar_path']) ? $user['avatar_path'] : 'avatars/avatar1.png';

// Stats globales du joueur
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
$statsStmt->execute([$currentUserId]);
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
    <title>Profil joueur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stats.css">
</head>
<body>

<div class="stats-app">

    <!-- Bandeau bleu -->
    <div class="stats-hero"></div>

    <!-- Carte blanche arrondie -->
    <section class="stats-card">
        <header class="stats-card-header">
            <div>
                <h1 class="stats-name"><?php echo htmlspecialchars($user['nom']); ?></h1>
            </div>
            <button class="avatar-btn" id="openAvatarModal">
                <div class="avatar-circle">
                    <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar">
                </div>
                <span class="avatar-label">Avatar</span>
            </button>
        </header>

        <!-- Grille 4 stats -->
        <div class="stats-grid">

            <!-- Bloc 1 : victoires de manches -->
            <article class="stat-box stat-box-primary">
                <h2 class="stat-title">NUMBER OF WIN</h2>
                <div class="stat-value"><?php echo $totalWins; ?></div>
                <div class="stat-sub">in <?php echo $gamesLabel; ?></div>
            </article>

            <!-- Bloc 2 : points totaux -->
            <article class="stat-box stat-box-secondary">
                <h2 class="stat-title">NUMBER OF POINTS</h2>
                <div class="stat-value"><?php echo $totalPoints; ?></div>
                <div class="stat-sub">in <?php echo $gamesLabel; ?></div>
            </article>

            <!-- Bloc 3 : position moyenne -->
            <article class="stat-box stat-box-primary">
                <h2 class="stat-title">AVERAGE POSITION</h2>
                <div class="stat-value"><?php echo $avgPosition; ?></div>
                <div class="stat-sub">in <?php echo $gamesLabel; ?></div>
            </article>

            <!-- Bloc 4 : points moyens par partie -->
            <article class="stat-box stat-box-secondary">
                <h2 class="stat-title">AVG POINTS / GAME</h2>
                <div class="stat-value"><?php echo $avgPointsPerGame; ?></div>
                <div class="stat-sub">in <?php echo $gamesLabel; ?></div>
            </article>

        </div>
    </section>

</div>

<!-- Nav wheel -->
<div class="stats-bottom-nav">
    <div class="stats-wheel" id="statsWheel">
        <div class="stats-arrow"></div>
        <div class="stats-user-icon"></div>
    </div>
</div>

<!-- Modal choix avatar -->
<div class="avatar-modal-bg" id="avatarModal">
    <div class="avatar-modal-box">
        <h3 class="title-font">Choisir un avatar</h3>

        <form action="avatar_update.php" method="POST" enctype="multipart/form-data" class="avatar-form">
            <p class="text-font">Avatars prédéfinis</p>

            <div class="avatar-grid">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <label class="avatar-option">
                        <input type="radio" name="preset_avatar" value="avatars/avatar<?php echo $i; ?>.png">
                        <img src="avatars/avatar<?php echo $i; ?>.png" alt="Avatar <?php echo $i; ?>">
                    </label>
                <?php endfor; ?>
            </div>

            <p class="text-font" style="margin-top:10px;">Ou uploader une photo</p>
            <input type="file" name="avatar_file" accept="image/*" class="avatar-file">

            <div class="avatar-actions">
                <button type="submit" class="avatar-save text-font">Enregistrer</button>
                <button type="button" class="avatar-cancel text-font" id="closeAvatarModal">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script src="stats.js" defer></script>
</body>
</html>
