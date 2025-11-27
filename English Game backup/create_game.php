<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

// Ami éventuellement pré-sélectionné depuis la home
$friendId = isset($_GET['friend_id']) ? (int) $_GET['friend_id'] : 0;

// Génération d'un code de partie à 6 chiffres
function generateGameCode(int $length = 6): string {
    $min = (int) str_pad('1', $length, '0', STR_PAD_RIGHT); // ex: 100000
    $max = (int) str_pad('', $length, '9', STR_PAD_RIGHT);  // ex: 999999
    return (string) random_int($min, $max);
}

$gameCode = generateGameCode();
$gameName = 'Partie Surf It';

try {
    $db->beginTransaction();

    // 1. Création de la partie
    $stmt = $db->prepare("
        INSERT INTO game_session (host_id, code, nom, status, created_at)
        VALUES (?, ?, ?, 'open', NOW())
    ");
    $stmt->execute([$currentUserId, $gameCode, $gameName]);

    $gameId = (int) $db->lastInsertId();

    // 2. Ajouter uniquement le host dans game_players
    $playerInsert = $db->prepare("
        INSERT INTO game_players (game_id, user_id, total_points, manches_gagnees)
        VALUES (?, ?, 0, 0)
    ");
    $playerInsert->execute([$gameId, $currentUserId]);

    // 3. Si un ami est passé en paramètre, on crée une invitation (pas de join direct)
    $notifyFriendId = 0;

    if ($friendId > 0 && $friendId !== $currentUserId) {

        // Vérifier que friendId est bien un ami "accepted"
        $friendsStmt = $db->prepare("
            SELECT 1
            FROM friends
            WHERE status = 'accepted'
              AND (
                    (requester_id = ? AND requested_id = ?)
                 OR (requested_id = ? AND requester_id = ?)
              )
            LIMIT 1
        ");
        $friendsStmt->execute([$currentUserId, $friendId, $currentUserId, $friendId]);
        $friendOk = $friendsStmt->fetchColumn();

        if ($friendOk) {
            // Vérifier s'il n'y a pas déjà une invitation en attente
            $invCheck = $db->prepare("
                SELECT id
                FROM game_invitations
                WHERE game_id = ?
                  AND inviter_id = ?
                  AND invited_id = ?
                  AND status = 'pending'
            ");
            $invCheck->execute([$gameId, $currentUserId, $friendId]);
            $already = $invCheck->fetch(PDO::FETCH_ASSOC);

            if (!$already) {
                $invInsert = $db->prepare("
                    INSERT INTO game_invitations (game_id, inviter_id, invited_id, status, created_at)
                    VALUES (?, ?, ?, 'pending', NOW())
                ");
                $invInsert->execute([$gameId, $currentUserId, $friendId]);
            }

            // On notifie ce friend-là côté WebSocket dans game_lobby.php
            $notifyFriendId = $friendId;
        }
    }

    $db->commit();

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    // Tu peux faire un meilleur handling plus tard (page d'erreur, log, etc.)
    die("Erreur lors de la création de la partie.");
}

// 4. Redirection vers le lobby
// Si un ami a été ciblé, on passe son id en notify_friend_id pour que le lobby déclenche l'event WebSocket
if (!empty($notifyFriendId)) {
    header('Location: game_lobby.php?id=' . $gameId . '&notify_friend_id=' . $notifyFriendId);
} else {
    header('Location: game_lobby.php?id=' . $gameId);
}
exit;
