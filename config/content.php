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
        'wines' => 'Nasze wina',
        'tastings' => 'Degustacje',
        'about' => 'O winnicy',
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

    'wines' => [
        'page_title' => 'Nasze wina — {site.name}',
        'eyebrow' => 'Limitowane alokacje piwnicy',
        'title' => 'Nasze wina',
        'intro' => 'Butelki powstają w niewielkich partiach, numerowane ręcznie i przydzielane gościom '
            . 'posiadłości. Poniżej aktualne roczniki z piwnicy {site.name}.',
        'price_label' => 'Cena za butelkę',
        'empty' => 'Aktualnie nie ma win w ofercie.',
        'items' => [
            // Wina z Corvo Bianco; rocznik i alkohol są opcjonalne (brak wpisu = brak na karcie)
            [
                'name' => 'Sepremento',
                'type' => 'Czerwone wytrawne',
                'grape' => 'Carvanere',
                'award' => 'Flagowe wino posiadłości',
                'notes' => 'Tradycyjne wino przypisane bezpośrednio do posiadłości {site.name}. '
                    . 'Ze szczepu carvanere, o wyrazistych nutach jeżyn, wiśni, śliwek i cynamonu.',
                'price' => '165 zł',
            ],
            [
                'name' => 'Biały Wilk',
                'type' => 'Białe wytrawne',
                'award' => null,
                'notes' => 'White Wolf. Świeże, rześkie wino o czystym, wytrawnym finiszu.',
                'price' => '110 zł',
            ],
            [
                'name' => 'Rzeźnik z Blaviken',
                'type' => 'Czerwone wytrawne',
                'award' => null,
                'notes' => 'Butcher of Blaviken. Mocne, ciemne wino o zdecydowanym, wytrawnym charakterze.',
                'price' => '105 zł',
            ],
            [
                'name' => 'Geralt z Rivii',
                'type' => 'Czerwone wytrawne',
                'award' => null,
                'notes' => 'Geralt of Rivia. Dojrzałe czerwone wino o głębokim smaku i długim posmaku.',
                'price' => '120 zł',
            ],
            [
                'name' => 'Reserve Chardonnay',
                'type' => 'Białe wytrawne',
                'vintage' => '2021',
                'abv' => '12,8%',
                'award' => 'Złoty medal',
                'notes' => 'Ciepła brioszka, krzemień i skórka cytryny, z solną czystością wapiennej gleby.',
                'price' => '98 zł',
            ],
            [
                'name' => 'Grand Cru Pinot Noir',
                'type' => 'Czerwone wytrawne',
                'vintage' => '2020',
                'abv' => '13,5%',
                'award' => '97 pkt',
                'notes' => 'Dzika czarna wiśnia, trufla i aksamitny finisz po dojrzewaniu w dębowej beczce.',
                'price' => '145 zł',
            ],
            [
                'name' => 'Late Harvest Riesling',
                'type' => 'Białe słodkie',
                'vintage' => '2021',
                'abv' => '8,5%',
                'award' => '94 pkt',
                'notes' => 'Kandyzowana bergamotka, miód akacjowy i szafran, równoważone krystaliczną kwasowością.',
                'price' => '112 zł',
            ],
            [
                'name' => 'Rosé de Toussaint',
                'type' => 'Różowe wytrawne',
                'vintage' => '2023',
                'abv' => '12,0%',
                'award' => null,
                'notes' => 'Truskawka, grejpfrut i świeże zioła; lekkie wino na ciepłe popołudnie w ogrodzie.',
                'price' => '72 zł',
            ],
            [
                'name' => 'Cuvée Brut',
                'type' => 'Musujące brut',
                'vintage' => '2019',
                'abv' => '12,5%',
                'award' => 'Srebrny medal',
                'notes' => 'Drobna perlistość, zielone jabłko i chrupiąca skórka chleba z długiego leżakowania.',
                'price' => '128 zł',
            ],
            [
                'name' => 'Cellar Master Merlot',
                'type' => 'Czerwone wytrawne',
                'vintage' => '2020',
                'abv' => '13,8%',
                'award' => null,
                'notes' => 'Śliwka, czarna porzeczka i nuty kakao; miękkie taniny, dobre do dłuższego leżakowania.',
                'price' => '89 zł',
            ],
        ],
    ],

    'tastings' => [
        'page_title' => 'Degustacje — {site.name}',
        'eyebrow' => 'Wizyta w posiadłości',
        'title' => 'Degustacje',
        'intro' => 'Spróbuj win prosto z piwnicy {site.name}. Każda wizyta odbywa się w niewielkiej grupie, '
            . 'z kieliszkiem w ręku i opowieścią o winnicy.',
        'offers' => [
            [
                'name' => 'Degustacja klasyczna',
                'badge' => 'Na początek',
                'description' => 'Trzy wina z bieżących roczników, podane z pieczywem i serami z okolicy.',
                'duration' => '1,5 godziny',
                'group' => 'do 8 osób',
                'price' => '90 zł / osoba',
            ],
            [
                'name' => 'Degustacja Sepremento',
                'badge' => 'Flagowa',
                'description' => 'Pionowa degustacja flagowego wina posiadłości z krótkim spacerem między krzewami.',
                'duration' => '2 godziny',
                'group' => 'do 6 osób',
                'price' => '150 zł / osoba',
            ],
            [
                'name' => 'Kolacja w winnicy',
                'badge' => null,
                'description' => 'Kolacja przy długim stole, do której każde danie ma dobrane wino z naszej piwnicy.',
                'duration' => '3 godziny',
                'group' => 'do 12 osób',
                'price' => '260 zł / osoba',
            ],
        ],
        'steps_title' => 'Jak to działa',
        'steps' => [
            'Wybierz rodzaj degustacji i dogodny termin.',
            'Zaloguj się i zarezerwuj miejsca dla swojej grupy.',
            'Przyjdź do posiadłości — resztą zajmie się piwniczy.',
        ],
        'duration_label' => 'Czas',
        'cta_title' => 'Zarezerwuj wizytę',
        'cta_text' => 'Rezerwacja wymaga konta gościa. Zaloguj się, aby wybrać termin i liczbę osób.',
        'cta' => 'Zaloguj się, aby zarezerwować degustację',
    ],

    'about' => [
        'page_title' => 'O winnicy — {site.name}',
        'eyebrow' => 'O posiadłości',
        'title' => 'O winnicy {site.name}',
        'intro' => '{site.name} to posiadłość winiarska w zachodniej części Toussaint — '
            . 'miejsce, w którym spokój winnicy spotyka się z historią i dobrym stołem.',
        'history_title' => 'Historia',
        'history' => [
            'Posiadłość przez lata niszczała, aż trafiła w ręce Geralta z Rivii, który otrzymał ją '
            . 'w darze od księżnej Anny Henrietty razem z kluczem i aktem własności.',
            'Dwór, ogród i piwnice zostały krok po kroku odnowione. Dziś {site.name} łączy '
            . 'tradycyjne winiarstwo Toussaint z gościnnością: degustacjami, kolacjami przy długim stole '
            . 'i opowieściami o ludziach, którzy tu mieszkali.',
        ],
        'facts_title' => 'W skrócie',
        'facts' => [
            ['label' => 'Położenie', 'value' => 'Zachodnie Toussaint'],
            ['label' => 'Właściciel', 'value' => 'Geralt z Rivii'],
            ['label' => 'Zarządca', 'value' => 'Barnabas-Basil Foulty, majordomus'],
            ['label' => 'Flagowe wino', 'value' => 'Sepremento (szczep Carvanere)'],
        ],
        'estate_title' => 'Posiadłość dzisiaj',
        'estate_intro' => 'Odnowiona posiadłość ma wszystko, czego potrzeba na wizytę i na codzienną pracę:',
        'features' => [
            'Odnowiony dwór z pokojem gościnnym na wieczorne kolacje',
            'Warsztat z kowadłem i szlifierką oraz stojaki na broń i zbroje',
            'Ogród, w którym rosną rzadkie zioła',
            'Stajnia dla koni gości i gospodarzy',
            'Piwnice i ukryta pracownia pod dworem',
        ],
        'cta_title' => 'Odwiedź nas',
        'cta_wines' => 'Zobacz nasze wina',
        'cta_tastings' => 'Zobacz degustacje',
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
