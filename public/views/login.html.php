<main class="page auth">
    <section class="auth-card" aria-labelledby="login-title">
        <h1 id="login-title"><?= $t('login.title') ?></h1>
        <p class="auth-intro"><?= $t('login.intro') ?></p>

        <form action="<?= $e($formAction) ?>" method="post">
            <fieldset>
                <legend><?= $t('login.legend') ?></legend>
                <p class="form-field">
                    <label for="email"><?= $t('login.email') ?></label>
                    <input type="email" id="email" name="email" autocomplete="username" required>
                </p>
                <p class="form-field">
                    <label for="password"><?= $t('login.password') ?></label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </p>
                <p class="form-check">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember"><?= $t('login.remember') ?></label>
                </p>
            </fieldset>
            <button type="submit" class="button button-block"><?= $t('login.submit') ?></button>
        </form>

        <p class="auth-back"><a href="/"><?= $t('login.back') ?></a></p>
    </section>
</main>
