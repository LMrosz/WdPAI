<?php
/**
 * Ilustracja butelki (czysto dekoracyjna). Kolory szkła i kapsla zależą od klasy kategorii
 * ustawionej na rodzicu (wine--red, wine--white, wine--rose, wine--sparkling).
 */
?>
<svg class="bottle" viewBox="0 0 120 360" focusable="false" aria-hidden="true">
    <rect class="bottle-foil" x="47" y="2" width="26" height="54" rx="3"/>
    <path class="bottle-glass" d="M50 52H70V92C70 122 100 132 100 170V330Q100 348 82 348H38Q20 348 20 330V170C20 132 50 122 50 92Z"/>
    <path class="bottle-shine" d="M30 176V326" />
    <rect class="bottle-label" x="30" y="204" width="60" height="98" rx="3"/>
    <rect class="bottle-label-frame" x="34" y="208" width="52" height="90" rx="2"/>
    <circle class="bottle-label-mark" cx="60" cy="234" r="9"/>
    <path class="bottle-label-mark" d="M42 262H78M46 270H74M50 278H70"/>
</svg>
