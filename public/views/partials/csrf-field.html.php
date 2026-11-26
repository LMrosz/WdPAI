<?php
/**
 * Ukryte pole z tokenem CSRF; każdy formularz POST musi je zawierać.
 *
 * @var Closure $csrf zwraca token bieżącej sesji
 */
?>
<input type="hidden" name="csrf_token" value="<?= $e($csrf()) ?>">
