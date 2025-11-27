<?php
session_start();

// game.php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$game_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($game_id <= 0) {
    die("Partie invalide.");
}

// vérifier que la game existe
$stmt = $conn->prepare("SELECT id, host_id, code, nom, status FROM game_session WHERE id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$res = $stmt->get_result();
$game = $res->fetch_assoc();
$stmt->close();

if (!$game) {
    die("Partie introuvable.");
}

// récupérer les joueurs
$stmt = $conn->prepare("
    SELECT gp.id as gp_id, gp.user_id, gp.total_points, gp.manches_gagnees, j.nom, j.avatar
    FROM game_players gp
    JOIN joueurs j ON j.id = gp.user_id
    WHERE gp.game_id = ?
    ORDER BY gp.total_points DESC, gp.manches_gagnees DESC
");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$res = $stmt->get_result();
$players = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Partie #<?php echo htmlspecialchars($game['code']); ?></title>
<style>
body{font-family:Montserrat, Arial; padding:12px; max-width:480px; margin:0 auto;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.card{background:#fff;padding:12px;border-radius:8px;margin-bottom:8px;box-shadow:0 1px 6px rgba(0,0,0,.06);}
.player{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #eee;}
.player:last-child{border-bottom:0;}
.small{font-size:0.9rem;color:#666;}
</style>
</head>
<body>
<div class="header">
    <div>
        <h3>Partie : <?php echo htmlspecialchars($game['nom'] ?? $game['code']); ?></h3>
        <div class="small">Code : <?php echo htmlspecialchars($game['code']); ?> — Statut : <?php echo htmlspecialchars($game['status']); ?></div>
    </div>
    <div><a href="home.php">Retour</a></div>
</div>

<div class="card">
    <h4>Joueurs</h4>
    <?php if(empty($players)) echo '<p>Aucun joueur pour le moment.</p>'; ?>
    <?php foreach($players as $p): ?>
        <div class="player">
            <div>
                <strong><?php echo htmlspecialchars($p['nom']); ?></strong>
                <div class="small">Manches gagnées: <?php echo (int)$p['manches_gagnees']; ?> — Points: <?php echo (int)$p['total_points']; ?></div>
            </div>
            <div>
                <!-- futur : bouton pour entrer points (si c'est le joueur connecté) -->
                <?php if($p['user_id'] == $_SESSION['user_id']): ?>
                    <span class="small">C'est toi</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
