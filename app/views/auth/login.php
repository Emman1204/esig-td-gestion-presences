<h1>Connexion</h1>

<?php if (!empty($error)): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post" action="public/authenticate">
    <div>
        <label>Email</label><br>
        <input type="email" name="email" required>
    </div>

    <div>
        <label>Mot de passe</label><br>
        <input type="password" name="password" required>
    </div>

    <br>
    <button type="submit">Se connecter</button>
</form>
