<section>
    <link rel="stylesheet" href="/public/css/login.css">
    <link rel="stylesheet" href="/public/css/globale.css">

    <div class="login-container">

        <h1>ESIG</h1>

        <?php if (!empty($error)): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="public/authenticate">

            <div>
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div>
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit">Se connecter</button>

        </form>

    </div>
</section>