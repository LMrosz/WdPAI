<?php

declare(strict_types=1);

/*
 * Wszystkie teksty interfejsu i dane marki winnicy w jednym miejscu.
 * Dziś plik zwraca dane domyślne (winnica Corvo Bianco); docelowo te same klucze
 * będą pobierane z bazy i edytowane przez administratora, bez zmian w widokach.
 *
 * Wartości mogą zawierać znaczniki {klucz} — np. {site.name} — podstawiane
 * z innych wpisów, dzięki czemu zmiana nazwy winnicy zmienia wszystkie napisy.
 * Dane przykładowe (statystyki, opisy) są fikcyjne.
 */
return [
    'site' => [
        'locale' => 'pl',
        'name' => 'Corvo Bianco',
        'tagline' => 'Winnica w Księstwie Toussaint',
        'platform' => 'VinoKeep',
    ],

    'nav' => [
        'label' => 'Nawigacja główna',
        'home' => 'Strona główna',
        'login' => 'Zaloguj się',
    ],

    'home' => [
        'page_title' => '{site.name} — strona główna',
        'eyebrow' => 'Toussaint • posiadłość winiarska',
        'title' => 'Witamy w {site.name} — dobre wino bez pośpiechu',
        'intro' => 'Posiadłość wśród winnic Toussaint z własną piwnicą, degustacjami przy kominku '
            . 'i widokiem na dolinę, w której nawet wiedźmin odkłada miecz. '
            . 'Zarezerwuj wizytę i spróbuj win z naszych roczników.',
        'cta' => 'Zaloguj się, aby zarezerwować degustację',
        'stats' => [
            ['value' => '12 ha', 'label' => 'Winnice posiadłości'],
            ['value' => '3 odmiany', 'label' => 'Szczepy w uprawie'],
            ['value' => '6 roczników', 'label' => 'Aktualnie w piwnicy'],
            ['value' => '4,8 / 5', 'label' => 'Ocena gości'],
        ],
    ],

    'login' => [
        'page_title' => 'Logowanie — {site.name}',
        'title' => 'Logowanie do {site.name}',
        'intro' => 'Zaloguj się jako gość, piwniczy lub właściciel winnicy.',
        'legend' => 'Dane logowania',
        'email' => 'Adres e-mail',
        'password' => 'Hasło',
        'remember' => 'Zapamiętaj mnie na tym urządzeniu',
        'submit' => 'Zaloguj się',
        'back' => 'Wróć na stronę główną',
    ],

    'footer' => [
        'rights' => '© {year} {site.name}. Wszelkie prawa zastrzeżone.',
        'powered' => 'Platforma {site.platform}.',
    ],

    'errors' => [
        'not_found' => [
            'page_title' => 'Nie znaleziono strony — {site.name}',
            'title' => '404',
            'text' => 'Taka strona nie istnieje.',
            'back' => 'Wróć na stronę główną',
        ],
    ],
];
