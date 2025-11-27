<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

$action = $_POST['action'] ?? '';
$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;

// Accepter une demande (AJAX)
if ($action === 'accept' && $id > 0) {
    // Optionnel: vérifier que cette demande concerne bien currentUserId en requested_id
    $stmt = $db->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND requested_id = ?");
    $stmt->execute([$id, $currentUserId]);
    exit;
}

// Refuser une demande (AJAX)
if ($action === 'decline' && $id > 0) {
    $stmt = $db->prepare("DELETE FROM friends WHERE id = ? AND requested_id = ?");
    $stmt->execute([$id, $currentUserId]);
    exit;
}

// Suppression d'un ami (depuis la page friend_profile, via fetch ou form)
if ($action === 'remove') {
    $friendId = isset($_POST['friend_id']) ? (int) $_POST['friend_id'] : 0;
    if ($friendId > 0) {
        $stmt = $db->prepare("
            DELETE FROM friends
            WHERE status = 'accepted'
              AND (
                    (requester_id = ? AND requested_id = ?)
                 OR (requester_id = ? AND requested_id = ?)
              )
        ");
        $stmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);
    }
    exit;
}

exit;
