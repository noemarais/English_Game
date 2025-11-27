<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

$action   = $_POST['action'] ?? '';
$inviteId = isset($_POST['invite_id']) ? (int) $_POST['invite_id'] : 0;

if ($inviteId <= 0 || ($action !== 'accept' && $action !== 'decline')) {
    header('Location: home.php');
    exit;
}

// Récup l'invitation + vérif que c'est bien pour ce user
$stmt = $db->prepare("
    SELECT gi.id, gi.game_id, gi.status, g.status AS game_status
    FROM game_invitations gi
    JOIN game_session g ON g.id = gi.game_id
    WHERE gi.id = ? AND gi.invited_id = ?
");
$stmt->execute([$inviteId, $currentUserId]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    header('Location: home.php');
    exit;
}

$gameId = (int)$invite['game_id'];

if ($action === 'decline') {
    $upd = $db->prepare("UPDATE game_invitations SET status = 'declined' WHERE id = ?");
    $upd->execute([$inviteId]);
    header('Location: home.php');
    exit;
}

if ($action === 'accept') {
    // Invitation déjà traitée ?
    if ($invite['status'] !== 'pending') {
        header('Location: home.php');
        exit;
    }

    // Partie encore ouverte ?
    if ($invite['game_status'] !== 'open') {
        // On marque l'invitation comme déclinée car plus valable
        $upd = $db->prepare("UPDATE game_invitations SET status = 'declined' WHERE id = ?");
        $upd->execute([$inviteId]);
        header('Location: home.php');
        exit;
    }

    // Vérifier que la partie n'est pas pleine
    $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM game_players WHERE game_id = ?");
    $countStmt->execute([$gameId]);
    $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
    $nbPlayers = (int)$countRow['c'];

    if ($nbPlayers >= 6) {
        $upd = $db->prepare("UPDATE game_invitations SET status = 'declined' WHERE id = ?");
        $upd->execute([$inviteId]);
        header('Location: home.php');
        exit;
    }

    // Vérifier si déjà dans la partie
    $checkP = $db->prepare("
        SELECT id FROM game_players
        WHERE game_id = ? AND user_id = ?
    ");
    $checkP->execute([$gameId, $currentUserId]);
    $already = $checkP->fetch(PDO::FETCH_ASSOC);

    if (!$already) {
        $insert = $db->prepare("
            INSERT INTO game_players (game_id, user_id, total_points, manches_gagnees)
            VALUES (?, ?, 0, 0)
        ");
        $insert->execute([$gameId, $currentUserId]);
    }

    // Mettre l'invitation en accepted
    $upd = $db->prepare("UPDATE game_invitations SET status = 'accepted' WHERE id = ?");
    $upd->execute([$inviteId]);

    header('Location: game_lobby.php?id=' . $gameId);
    exit;
}

header('Location: home.php');
exit;
