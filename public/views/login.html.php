<?php $e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&amp;family=Plus+Jakarta+Sans:wght@400;500;600&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="page actions">
            <p class="brand"><a href="/">VinoKeep</a> <small>Winnica i enoturystyka</small></p>
            <nav aria-label="Nawigacja główna">
                <ul>
                    <li><a href="/">Strona główna</a></li>
                    <li><a href="/login" aria-current="page">Zaloguj się</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="page">
        <section aria-labelledby="login-title">
            <h1 id="login-title">Logowanie do VinoKeep</h1>
            <p>Zaloguj się jako gość, piwniczy lub właściciel winnicy.</p>

            <form action="<?= $e($formAction) ?>" method="post">
                <fieldset>
                    <legend>Dane logowania</legend>
                    <p>
                        <label for="email">Adres e-mail</label>
                        <input type="email" id="email" name="email" autocomplete="username" required>
                    </p>
                    <p>
                        <label for="password">Hasło</label>
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                    </p>
                    <p>
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Zapamiętaj mnie na tym urządzeniu</label>
                    </p>
                </fieldset>
                <p><button type="submit">Zaloguj się</button></p>
            </form>

            <p><a href="/">Wróć na stronę główną</a></p>
        </section>
    </main>

    <footer class="site-footer">
        <div class="page">
            <p><small>© 2024 VinoKeep Terroirs &amp; Cellar Systems. Wszelkie prawa zastrzeżone.</small></p>
        </div>
    </footer>
</body>
</html>
