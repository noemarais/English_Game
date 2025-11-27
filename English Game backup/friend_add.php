<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';

if ($name === '') {
    header('Location: home.php?friend_msg=' . urlencode('Nom obligatoire.'));
    exit;
}

// 1. Chercher le joueur par nom EXACT
$userStmt = $db->prepare("SELECT user_id, nom FROM joueurs WHERE nom = ?");
$userStmt->execute([$name]);
$target = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    header('Location: home.php?friend_msg=' . urlencode('Aucun joueur avec ce nom.'));
    exit;
}

$targetId = (int) $target['user_id'];

if ($targetId === $currentUserId) {
    header('Location: home.php?friend_msg=' . urlencode('Tu ne peux pas t ajouter toi-même.'));
    exit;
}

// 2. Vérifier s il existe déjà une relation (n'importe quelle direction)
$checkStmt = $db->prepare("
    SELECT id, status
    FROM friends
    WHERE (requester_id = ? AND requested_id = ?)
       OR (requester_id = ? AND requested_id = ?)
    LIMIT 1
");
$checkStmt->execute([$currentUserId, $targetId, $targetId, $currentUserId]);
$existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $status = $existing['status'];

    if ($status === 'pending') {
        $msg = 'Une demande est déjà en attente.';
    } elseif ($status === 'accepted') {
        $msg = 'Vous êtes déjà amis.';
    } elseif ($status === 'rejected') {
        $msg = 'Cette demande a déjà été refusée.';
    } else {
        $msg = 'Relation déjà existante.';
    }

    header('Location: home.php?friend_msg=' . urlencode($msg));
    exit;
}

// 3. Créer la demande d ami
$insertStmt = $db->prepare("
    INSERT INTO friends (requester_id, requested_id, status, created_at)
    VALUES (?, ?, 'pending', NOW())
");
$insertStmt->execute([$currentUserId, $targetId]);

header('Location: home.php?friend_msg=' . urlencode('Demande envoyée à ' . $target['nom'] . '.'));
exit;
