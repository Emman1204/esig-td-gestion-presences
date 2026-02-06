<?php
// Sécurité : session obligatoire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Données utilisateur (si connecté)
$userNom = $_SESSION['user']['nom'] ?? null;
$userPrenom = $_SESSION['user']['prenom'] ?? null;
$userRole = $_SESSION['user']['role'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des présences</title>

    <!-- CSS du header -->
    <link rel="stylesheet" href="../../../public/css/header.css">
</head>

<body>
    <header>
        <nav class="navbar">
            <div class="navbar-left">
                <span class="logo">Gestion des présences</span>
            </div>

            <div class="navbar-right">
                <?php if ($userNom): ?>
                    <span class="user-name">
                        <?= htmlspecialchars($userNom) ?>
                        <small>(<?= htmlspecialchars($userRole) ?>)</small>
                    </span>

                    <form method="post" action="/public/logout" class="logout-form">
                        <button type="submit">Déconnexion</button>
                    </form>
                <?php endif; ?>
            </div>
        </nav>
    </header>