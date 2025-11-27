<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

$avatarPath = null;

// 1. Avatar prédéfini
if (!empty($_POST['preset_avatar'])) {
    $avatarPath = $_POST['preset_avatar'];
}

// 2. Upload fichier si présent
if (!empty($_FILES['avatar_file']['name'])) {
    $uploadDir = 'uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmp  = $_FILES['avatar_file']['tmp_name'];
    $fileName = basename($_FILES['avatar_file']['name']);
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','gif','webp'];

    if (in_array($ext, $allowed)) {
        $newName = 'avatar_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (move_uploaded_file($fileTmp, $dest)) {
            $avatarPath = $dest;
        }
    }
}

// Si rien choisi ni uploadé, on ne change rien
if ($avatarPath !== null) {
    $stmt = $db->prepare("UPDATE joueurs SET avatar_path = ? WHERE user_id = ?");
    $stmt->execute([$avatarPath, $currentUserId]);
}

header('Location: stats.php');
exit;
