<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $code = preg_replace('/\D/', '', $code); // garder uniquement les chiffres

    if (strlen($code) !== 6) {
        $error = "Le code doit contenir 6 chiffres.";
    } else {
        // Chercher la partie
        $stmt = $db->prepare("SELECT id, status FROM game_session WHERE code = ?");
        $stmt->execute([$code]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$game) {
            $error = "Aucune partie trouvée avec ce code.";
        } else {
            $gameId = (int)$game['id'];

            if ($game['status'] !== 'open') {
                $error = "Cette partie n est plus disponible.";
            } else {
                // Nombre de joueurs déjà dans la partie
                $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM game_players WHERE game_id = ?");
                $countStmt->execute([$gameId]);
                $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
                $nbPlayers = (int)$countRow['c'];

                if ($nbPlayers >= 6) {
                    $error = "La partie est pleine (6 joueurs).";
                } else {
                    // Vérifier si le joueur est déjà dans la partie
                    $check = $db->prepare("
                        SELECT id FROM game_players
                        WHERE game_id = ? AND user_id = ?
                    ");
                    $check->execute([$gameId, $currentUserId]);
                    $already = $check->fetch(PDO::FETCH_ASSOC);

                    if (!$already) {
                        $insert = $db->prepare("
                            INSERT INTO game_players (game_id, user_id, total_points, manches_gagnees)
                            VALUES (?, ?, 0, 0)
                        ");
                        $insert->execute([$gameId, $currentUserId]);
                    }

                    header("Location: game_lobby.php?id=" . $gameId);
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rejoindre une partie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="game.css">
</head>
<body class="game-page">

<div class="game-container">
    <header class="game-header">
        <button class="game-back-btn" onclick="history.length > 1 ? history.back() : window.location.href='home.php'">
            ← Retour
        </button>
        <h1 class="title-font">Rejoindre une partie</h1>
    </header>

    <?php if ($error): ?>
        <p class="game-error text-font"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" class="game-form">
        <label class="text-font">Code de la partie</label>
        <input type="text" name="code" maxlength="6" placeholder="123456" required>

        <button type="submit" class="btn-primary text-font">Rejoindre</button>
    </form>
</div>

</body>
</html>
