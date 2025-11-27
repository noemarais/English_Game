<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// 1. Sécurité: utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Non connecté'
    ]);
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

// 2. On accepte uniquement du POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Méthode invalide'
    ]);
    exit;
}

// 3. Récup des paramètres
$gameId = isset($_POST['game_id']) ? (int) $_POST['game_id'] : 0;
$round  = isset($_POST['round'])   ? (int) $_POST['round']   : 0;
$points = isset($_POST['points'])  ? (int) $_POST['points']  : 0;

if ($gameId <= 0 || $round <= 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Paramètres manquants'
    ]);
    exit;
}

// 4. Vérifier que la partie existe et n'est pas finie
$gameStmt = $db->prepare("
    SELECT id, status
    FROM game_session
    WHERE id = ?
");
$gameStmt->execute([$gameId]);
$game = $gameStmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Partie introuvable'
    ]);
    exit;
}

if ($game['status'] === 'finished') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Partie déjà terminée'
    ]);
    exit;
}

// 5. Vérifier que le joueur appartient bien à cette partie
$gpStmt = $db->prepare("
    SELECT id, total_points
    FROM game_players
    WHERE game_id = ? AND user_id = ?
");
$gpStmt->execute([$gameId, $currentUserId]);
$gp = $gpStmt->fetch(PDO::FETCH_ASSOC);

if (!$gp) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tu ne fais pas partie de cette partie'
    ]);
    exit;
}

$gpId = (int) $gp['id'];

try {
    $db->beginTransaction();

    // 6. Est-ce que ce joueur a déjà enregistré des points pour cette manche ?
    $roundStmt = $db->prepare("
        SELECT id, points
        FROM game_rounds
        WHERE game_player_id = ? AND round_number = ?
        LIMIT 1
    ");
    $roundStmt->execute([$gpId, $round]);
    $existing = $roundStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Il avait déjà des points pour cette manche → on ajuste
        $oldPoints = (int) $existing['points'];
        $diff = $points - $oldPoints;

        $updRound = $db->prepare("
            UPDATE game_rounds
            SET points = ?
            WHERE id = ?
        ");
        $updRound->execute([$points, (int) $existing['id']]);

        $updTotal = $db->prepare("
            UPDATE game_players
            SET total_points = total_points + ?
            WHERE id = ?
        ");
        $updTotal->execute([$diff, $gpId]);

    } else {
        // Première fois qu'il enregistre des points pour cette manche
        $insRound = $db->prepare("
            INSERT INTO game_rounds (game_player_id, round_number, points)
            VALUES (?, ?, ?)
        ");
        $insRound->execute([$gpId, $round, $points]);

        $updTotal = $db->prepare("
            UPDATE game_players
            SET total_points = total_points + ?
            WHERE id = ?
        ");
        $updTotal->execute([$points, $gpId]);
    }

    $db->commit();

    echo json_encode([
        'status'  => 'ok',
        'message' => 'Points enregistrés',
        'game_id' => $gameId,
        'round'   => $round,
        'points'  => $points
    ]);
    exit;

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erreur BDD lors de lenregistrement des points'
    ]);
    exit;
}
