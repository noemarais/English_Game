<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

$gameInvitesStmt = $db->prepare("
    SELECT 
        gi.id,
        gi.game_id,
        g.code,
        g.nom,
        j.nom AS host_name
    FROM game_invitations gi
    JOIN game_session g ON g.id = gi.game_id
    JOIN joueurs j ON j.user_id = g.host_id
    WHERE gi.invited_id = ?
      AND gi.status = 'pending'
      AND g.status = 'open'
    ORDER BY gi.created_at DESC
");
$gameInvitesStmt->execute([$currentUserId]);
$gameInvites = $gameInvitesStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($gameInvites);
exit;
