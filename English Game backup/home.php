<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];

// Infos joueur
$userStmt = $db->prepare("SELECT nom, avatar_path FROM joueurs WHERE user_id = ?");
$userStmt->execute([$currentUserId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$avatarPath = !empty($user['avatar_path']) ? $user['avatar_path'] : 'avatars/avatar1.png';

// Amis acceptés
$friendsStmt = $db->prepare("
    (
        SELECT j.user_id, j.nom, j.avatar_path
        FROM friends f
        JOIN joueurs j ON j.user_id = f.requested_id
        WHERE f.requester_id = ? AND f.status = 'accepted'
    )
    UNION
    (
        SELECT j.user_id, j.nom, j.avatar_path
        FROM friends f
        JOIN joueurs j ON j.user_id = f.requester_id
        WHERE f.requested_id = ? AND f.status = 'accepted'
    )
");
$friendsStmt->execute([$currentUserId, $currentUserId]);
$friends = $friendsStmt->fetchAll(PDO::FETCH_ASSOC);

// Demandes d amis reçues
$pendingStmt = $db->prepare("
    SELECT f.id, f.requester_id, j.nom
    FROM friends f
    JOIN joueurs j ON j.user_id = f.requester_id
    WHERE f.requested_id = ? AND f.status = 'pending'
");
$pendingStmt->execute([$currentUserId]);
$pendingRequests = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Parties récentes : UNIQUEMENT les parties terminées
$gamesStmt = $db->prepare("
    SELECT 
        g.id,
        g.nom,
        g.code,
        g.status,
        g.created_at,
        g.finished_at
    FROM game_players gp
    JOIN game_session g ON g.id = gp.game_id
    WHERE gp.user_id = ?
      AND g.status = 'finished'
    ORDER BY COALESCE(g.finished_at, g.created_at) DESC
    LIMIT 5
");
$gamesStmt->execute([$currentUserId]);
$recentGames = $gamesStmt->fetchAll(PDO::FETCH_ASSOC);

// Invitations de partie existantes (affichage initial)
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

$friendMsg = isset($_GET['friend_msg']) ? trim($_GET['friend_msg']) : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Surf It - Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="home.css">
</head>
<body data-user-id="<?php echo (int)$currentUserId; ?>">

<div class="app">

    <?php if ($friendMsg !== ''): ?>
        <p class="no-games-text text-font" style="margin-bottom:10px;">
            <?php echo htmlspecialchars($friendMsg); ?>
        </p>
    <?php endif; ?>

    <!-- Header -->
    <header class="header">
        <div>
            <div class="header-text-small text-font">Content de te revoir</div>
            <div class="header-name title-font"><?php echo htmlspecialchars($user['nom']); ?></div>
        </div>
        <div class="avatar-circle" id="goToStats">
            <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="avatar-img">
        </div>
    </header>

    <!-- Amis -->
    <section class="friends-wrapper">
        <h2 class="section-title title-font">Tes amis</h2>

        <div class="friends-slider">
            <?php if (!empty($friends)): ?>
                <?php foreach ($friends as $f): ?>
                    <?php
                        $fAvatar = !empty($f['avatar_path']) ? $f['avatar_path'] : 'avatars/avatar1.png';
                    ?>
                    <article class="friend-card">
                        <div class="friend-banner"></div>
                        <div class="friend-content">
                            <div class="friend-name text-font">
                                <?php echo htmlspecialchars($f['nom']); ?>
                            </div>
                            <div class="friend-actions-row">
                                <form method="GET" action="friend_profile.php">
                                    <input type="hidden" name="id" value="<?php echo (int) $f['user_id']; ?>">
                                    <button type="submit" class="friend-profile-btn text-font">Profil</button>
                                </form>
                                <button 
                                    type="button" 
                                    class="friend-invite-btn text-font" 
                                    data-friend-id="<?php echo (int) $f['user_id']; ?>">
                                    Inviter
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-font no-games-text">Tu n as pas encore d amis, utilise le bouton ci dessous pour en ajouter.</p>
            <?php endif; ?>
        </div>

        <button class="add-friend-btn" id="btnAddFriend">Ajouter un ami</button>
    </section>

    <!-- Demandes d amis -->
    <?php if (!empty($pendingRequests)): ?>
        <section class="pending-box">
            <h3 class="pending-title title-font">Demandes d amis</h3>
            <?php foreach ($pendingRequests as $req): ?>
                <div class="pending-item text-font">
                    <span class="pending-name"><?php echo htmlspecialchars($req['nom']); ?></span>
                    <div class="pending-actions">
                        <form method="POST" action="friend_action.php" style="display:inline;">
                            <input type="hidden" name="action" value="accept">
                            <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                            <button type="submit" class="accept-btn">Accepter</button>
                        </form>
                        <form method="POST" action="friend_action.php" style="display:inline;">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                            <button type="submit" class="decline-btn">Refuser</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <!-- Invitations de partie (wrapper pour live update) -->
    <div class="game-invites-wrapper">
        <?php if (!empty($gameInvites)): ?>
            <section class="game-invites">
                <h3 class="game-invites-title title-font">Invitations de partie</h3>

                <?php foreach ($gameInvites as $inv): ?>
                    <article class="game-invite-card">
                        <div class="game-invite-main text-font">
                            <div class="game-invite-host">
                                <?php echo htmlspecialchars($inv['host_name']); ?> t a invité à jouer
                            </div>
                            <div class="game-invite-name">
                                Partie : <?php echo htmlspecialchars($inv['nom'] ?: 'Partie Surf It'); ?>
                            </div>
                            <div class="game-invite-code">
                                Code : <strong><?php echo htmlspecialchars($inv['code']); ?></strong>
                            </div>
                        </div>
                        <div class="game-invite-actions">
                            <form method="POST" action="game_invite_action.php">
                                <input type="hidden" name="invite_id" value="<?php echo (int) $inv['id']; ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="game-invite-accept text-font">Rejoindre</button>
                            </form>
                            <form method="POST" action="game_invite_action.php">
                                <input type="hidden" name="invite_id" value="<?php echo (int) $inv['id']; ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="game-invite-decline text-font">Refuser</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>

    <!-- Bloc central créer / rejoindre -->
    <section class="game-actions">
        <div class="game-actions-card">
            <div class="game-actions-title title-font">Lance une partie</div>
            <p class="text-font">Crée une session avec tes amis ou rejoins une partie existante.</p>
            <div class="game-actions-buttons">
                <button class="btn-pill btn-create text-font" id="btnCreateGame">Créer</button>
                <button class="btn-pill btn-join text-font" id="btnJoinGame">Rejoindre</button>
            </div>
        </div>
    </section>

    <!-- Parties récentes -->
    <section class="recent-section">
        <h3 class="section-title title-font">Tes dernières parties</h3>

        <?php if (!empty($recentGames)): ?>
            <?php foreach ($recentGames as $g): ?>
                <article class="recent-card">
                    <div class="recent-visual">
                        <div class="recent-bubbles">
                            <div class="bubble"></div>
                            <div class="bubble"></div>
                            <div class="bubble"></div>
                            <div class="bubble"></div>
                        </div>
                    </div>
                    <div class="recent-main text-font">
                        <div>
                            <div class="recent-date">
                                <?php echo htmlspecialchars($g['nom'] ?: 'Partie Surf It'); ?>
                            </div>
                            <div class="no-games-text">
                                Code: <?php echo htmlspecialchars($g['code']); ?> - <?php echo htmlspecialchars($g['status']); ?>
                            </div>
                        </div>
                        <div class="recent-rank">
                            <span>?</span>
                            <small>rang</small>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-games-text text-font">Aucune partie terminée pour le moment.</p>
        <?php endif; ?>
    </section>

</div>

<!-- Nav ronde -->
<div class="bottom-nav-wrapper">
    <div class="nav-wheel" id="homeWheel">
        <div class="nav-arrow"></div>
        <div class="nav-home-icon"></div>
    </div>
</div>

<!-- Modal ajout ami -->
<div class="modal-bg" id="modalAddFriend">
    <div class="modal-box">
        <h3 class="title-font">Ajouter un ami</h3>
        <form method="POST" action="friend_add.php" id="formAddFriend">
            <input type="text" name="name" class="modal-input" placeholder="Nom / pseudo de ton ami" required>
            <button type="submit" class="modal-submit text-font">Envoyer une demande</button>
            <button type="button" class="modal-cancel text-font" id="btnCloseModal">Annuler</button>
        </form>

    </div>
</div>

</body>
<script src="home.js"></script>
</html>
