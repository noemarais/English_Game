<?php
session_start();
require 'db.php';

/* --------------------------------------
    INSCRIPTION
--------------------------------------- */
if (isset($_POST['register'])) {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp_clair = $_POST['mot_de_passe'] ?? '';

    if ($nom === '' || $email === '' || $mdp_clair === '') {
        $register_msg = "Tous les champs sont obligatoires.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT user_id FROM joueurs WHERE email = ?");
        $stmt->execute([$email]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            $register_msg = "Cet email est déjà utilisé.";
        } else {
            $mot_de_passe = password_hash($mdp_clair, PASSWORD_DEFAULT);

            // Gestion avatar à l'inscription
            $avatarPath = 'avatars/avatar1.png'; // valeur par défaut

            // Si avatar prédéfini choisi
            if (!empty($_POST['preset_avatar'])) {
                $avatarPath = $_POST['preset_avatar'];
            }

            // Si fichier uploadé
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

            $stmt = $db->prepare("
                INSERT INTO joueurs (nom, email, mot_de_passe, avatar_path)
                VALUES (?, ?, ?, ?)
            ");
            if ($stmt->execute([$nom, $email, $mot_de_passe, $avatarPath])) {
                $register_msg = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $register_msg = "Erreur lors de l'inscription.";
            }
        }
    }
}

/* --------------------------------------
    CONNEXION
--------------------------------------- */
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $mot_de_passe === '') {
        $login_msg = "Email et mot de passe requis.";
    } else {
        $stmt = $db->prepare("SELECT user_id, nom, mot_de_passe FROM joueurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['nom'] = $user['nom'];
            header("Location: home.php");
            exit;
        } else {
            $login_msg = "Identifiants incorrects.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Surf'It - Login</title>
    <link rel="stylesheet" href="styles.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400..800&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>

<body class="login_page">

<div class="header_login">
    <h1 class="titrelogin">SURF'IT</h1>
    <img src="medias/LOGO.png" alt="LOGO Surf'It">
</div>

<div class="button_login">
    <button class="insc" id="btnShowRegister">S'inscrire</button>
    <button class="connect" id="btnShowLogin">Se connecter</button>
</div>

<?php if (isset($login_msg)) : ?>
    <p class="msg"><?php echo htmlspecialchars($login_msg); ?></p>
<?php endif; ?>

<?php if (isset($register_msg)) : ?>
    <p class="msg"><?php echo htmlspecialchars($register_msg); ?></p>
<?php endif; ?>

<!-- FORMULAIRE CONNEXION -->
<div class="container_connect" id="containerLogin">
    <div>
        <img src="medias/croix.png" alt="">
        <h2>Se connecter</h2>
    </div>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
        <button type="submit" name="login">Connexion</button>
    </form>
</div>

<!-- FORMULAIRE INSCRIPTION -->
<div class="container_insc" id="containerRegister">
    <div>
        <img src="medias/croix.png" alt="">
        <h2>Créer un compte</h2>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="nom" placeholder="Nom / Pseudo" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>

        <p class="label_avatar">Choisir un avatar</p>
        <div class="avatar_grid_inscription">
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <label class="avatar_option_inscription">
                    <input type="radio" name="preset_avatar" value="avatars/avatar<?php echo $i; ?>.png">
                    <img src="avatars/avatar<?php echo $i; ?>.png" alt="Avatar <?php echo $i; ?>">
                </label>
            <?php endfor; ?>
        </div>

        <p class="label_avatar">Ou uploader une photo</p>
        <input type="file" name="avatar_file" accept="image/*">

        <button type="submit" name="register">Inscription</button>
    </form>
</div>

<script src="script.js"></script>
</body>
</html>
