<?php
/**
 * Podsumowanie błędów nad formularzem; każdy wpis prowadzi do pola.
 *
 * @var array<string, string> $errors nazwa pola => komunikat
 * @var array<string, string> $labels nazwa pola => etykieta
 */
?>
<?php if ($errors !== []): ?>
    <div class="form-errors" role="alert">
        <p class="form-errors-title"><?= $t('forms.error_summary') ?></p>
        <ul>
            <?php foreach ($errors as $name => $message): ?>
                <li><a href="#<?= $e($name) ?>"><?= $e($labels[$name] ?? $name) ?></a>: <?= $e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
