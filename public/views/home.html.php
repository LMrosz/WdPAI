<?php $e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($pageTitle) ?></title>
</head>
<body>
    <header>
        <p><a href="/">VinoKeep</a> <small>Winnica i enoturystyka</small></p>
        <nav aria-label="Nawigacja główna">
            <ul>
                <li><a href="/" aria-current="page">Strona główna</a></li>
                <li><a href="/login">Zaloguj się</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section aria-labelledby="hero-title">
            <p><?= $e($estate['label']) ?></p>
            <h1 id="hero-title">Witamy w <?= $e($estate['name']) ?> — <?= $e($estate['headline']) ?></h1>
            <p><?= $e($estate['intro']) ?></p>
            <p>
                <a href="/login">Zaloguj się, aby zarezerwować degustację</a>
            </p>
            <dl>
                <?php foreach ($estate['stats'] as $stat): ?>
                    <div>
                        <dt><?= $e($stat['label']) ?></dt>
                        <dd><?= $e($stat['value']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
    </main>

    <footer>
        <p><small>© 2024 VinoKeep Terroirs &amp; Cellar Systems. Wszelkie prawa zastrzeżone.</small></p>
    </footer>
</body>
</html>
