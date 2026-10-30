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
                    <li><a href="/" aria-current="page">Strona główna</a></li>
                    <li><a href="/login">Zaloguj się</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <div class="page hero-inner">
                <div class="hero-text">
                    <p class="eyebrow"><?= $e($estate['label']) ?></p>
                    <h1 id="hero-title">Witamy w <?= $e($estate['name']) ?> — <?= $e($estate['headline']) ?></h1>
                    <p><?= $e($estate['intro']) ?></p>
                    <p class="actions">
                        <a class="button" href="/login">Zaloguj się, aby zarezerwować degustację</a>
                    </p>
                </div>
                <dl class="stats">
                    <?php foreach ($estate['stats'] as $stat): ?>
                        <div class="stat-card">
                            <dt><?= $e($stat['label']) ?></dt>
                            <dd><?= $e($stat['value']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="page">
            <p><small>© 2024 VinoKeep Terroirs &amp; Cellar Systems. Wszelkie prawa zastrzeżone.</small></p>
        </div>
    </footer>
</body>
</html>
