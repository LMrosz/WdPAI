# VinoKeep

VinoKeep to aplikacja webowa dla winnic: prezentuje ofertę (wina, degustacje, historię posiadłości) i będzie służyć do zarządzania winnicą. Projekt na kurs „Wstęp do Projektowania Aplikacji Internetowych” (WdPAI), budowany w PHP w architekturze MVC. Domyślna treść jest przygotowana dla winnicy Corvo Bianco, ale wszystkie napisy są wydzielone do konfiguracji, żeby platforma działała dla dowolnej winnicy.

## Funkcje

| Obszar | Co robi | Status |
|---|---|---|
| Strona startowa (`/`) | Hero, statystyki winnicy, przyciski do głównych sekcji | gotowe |
| Nasze wina (`/wines`) | Katalog win w siatce kart (Sepremento, Biały Wilk, Rzeźnik z Blaviken, Geralt z Rivii i wina przykładowe) | gotowe |
| Degustacje (`/tastings`) | Oferty degustacji i kroki rezerwacji | gotowe (bez rezerwacji) |
| O winnicy (`/about`) | Historia, fakty i cechy posiadłości | gotowe |
| Logowanie (`/login`) | Formularz, walidacja pustych pól (422), przekierowanie na `/` po wysłaniu | tymczasowo, bez uwierzytelniania |
| Teksty serwisu | Wszystkie napisy i nazwa marki w `config/content.php` | gotowe |
| Motyw jasny/ciemny | Przełącznik w nagłówku: tryb systemowy, jasny lub ciemny, wybór zapamiętany w przeglądarce | gotowe |
| Dostępność (WCAG) | Semantyczne znaczniki, widoczny fokus, kontrast sprawdzony w obu motywach narzędziem axe | częściowo, pełny audyt planowany w przyszłości |
| Formularze, baza PostgreSQL, role użytkowników, upload, panel admina | Prawdziwe logowanie, rezerwacje, edycja treści | planowane w przyszłości |

## Tech stack

| Warstwa | Technologie |
|---|---|
| Backend | PHP 8.3 (własny MVC, autoloading PSR-4 przez Composer) |
| Serwer HTTP | Nginx + PHP-FPM |
| Baza danych | PostgreSQL (kontener gotowy, jeszcze nieużywany przez aplikację) |
| Frontend | HTML (widoki PHP), jeden plik CSS (zmienne CSS, Flexbox, Grid, mobile-first), JavaScript bez bibliotek (przełącznik motywu) |
| Konteneryzacja | Docker Compose |
| Narzędzia | Mailpit (podgląd maili), pgAdmin (zarządzanie bazą) |

## Szybki start

```bash
git clone https://github.com/LMrosz/WdPAI.git
cd my-app
docker compose up --build -d
```

| Usługa | Adres |
|---|---|
| Aplikacja | http://localhost:8080 |
| Mailpit | http://localhost:8025 |
| pgAdmin | http://localhost:5050 |

Porty i dane dostępowe bazy można zmienić zmiennymi środowiskowymi (np. w pliku `.env`): `POSTGRES_PORT`, `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`, `MAILPIT_PORT`, `PGADMIN_PORT`. Zatrzymanie: `docker compose down`.

## Struktura projektu

```text
my-app/
├── config/
│   └── content.php          # wszystkie teksty i dane marki (domyślnie Corvo Bianco)
├── docker/                  # Dockerfile'e i konfiguracja nginx, php, db
├── public/
│   ├── index.php            # front controller, definicje tras
│   ├── css/style.css        # design system „Terroir & Heritage Luxe”, tokeny obu motywów
│   ├── js/theme.js          # przełącznik motywu jasny/ciemny/systemowy
│   └── views/               # widoki *.html.php (sam element <main>)
│       ├── layout.html.php  # wspólny szkielet strony, składa head, nagłówek, treść i stopkę
│       ├── error.html.php   # wspólny widok błędów 400, 404 i 500
│       └── partials/        # wspólne head, header, footer
├── src/
│   ├── Routing.php          # router
│   ├── Exception/           # HttpException i wyjątki 400 oraz 404
│   ├── Service/
│   │   ├── SiteContent.php  # odczyt tekstów z config/content.php
│   │   └── Validator.php    # walidacja danych z formularzy po stronie serwera
│   └── controllers/         # kontrolery (AppController jako baza, ErrorController dla stron błędów)
├── docker-compose.yaml
├── composer.json
└── AGENTS.MD                # zasady pracy z asystentem AI
```

Przepływ żądania: nginx → `public/index.php` → `Routing` → kontroler → widok. Kontroler przekazuje do widoku dane, a teksty pobiera przez `$t('klucz')` z `config/content.php`. Widok zwraca tylko `<main>`, a resztę strony dokłada `layout.html.php`.

## Motyw jasny i ciemny

Wygląd obu motywów opisują zmienne CSS w `public/css/style.css`: motyw jasny to `:root`, a ciemny `html[data-theme="dark"]` z tymi samymi nazwami zmiennych. Komponenty korzystają wyłącznie ze zmiennych, więc nie ma osobnego arkusza dla ciemnego motywu.

- Mały skrypt w `partials/head.html.php` ustawia motyw przed załadowaniem CSS, żeby strona nie migała.
- Przycisk w nagłówku przełącza kolejno: systemowy (ikona monitora), jasny (słońce), ciemny (księżyc).
- Wybór jasny/ciemny jest zapisany w `localStorage` pod kluczem `theme`. W trybie systemowym nie ma zapisu i strona podąża za ustawieniem systemu.
- Zmiana motywu w jednej karcie przeglądarki od razu przenosi się do pozostałych.

## Trasy

| Metoda | Ścieżka | Opis |
|---|---|---|
| GET | `/` | Strona główna |
| GET | `/wines` | Katalog win |
| GET | `/tastings` | Degustacje |
| GET | `/about` | O winnicy |
| GET | `/login` | Formularz logowania |
| POST | `/login` | Obsługa formularza (na razie bez uwierzytelniania) |

## Obsługa błędów

Router rzuca `NotFoundException`, gdy żadna trasa nie pasuje, a kontroler może rzucić `BadRequestException`, gdy żądanie jest błędne (np. zły format identyfikatora). Front controller łapie `HttpException` i pokazuje stronę błędu z odpowiednim kodem (400 albo 404) przez `ErrorController`. Każdy inny nieoczekiwany błąd kończy się ogólną stroną 500, a szczegóły trafiają tylko do logu serwera, bez stack trace dla użytkownika. Teksty błędów są w `config/content.php` (`errors.*`).

## Walidacja formularzy

`App\Service\Validator` sprawdza dane po stronie serwera łańcuchem reguł (`required`, `email`, `minLength`, `maxLength`, `matches`) i dla każdego pola zapamiętuje pierwszy błąd. Komunikaty są w `config/content.php` (`validation.*`), więc walidator nie zawiera napisów.

## Zmiana tekstów i marki

Nazwa winnicy, hasło, nazwa platformy, nawigacja, opisy i lista win są w `config/content.php`. W tekstach można używać znaczników w stylu `{site.name}`, które są podstawiane z konfiguracji. Aby przygotować serwis dla innej winnicy, wystarczy podmienić ten plik; docelowo edycję przejmie panel administratora.

## Zasady developmentu

- Branch dla każdej funkcji.
- Małe, logiczne commity (format `feat:`, `fix:`, `docs:`, `chore:`).
- Mobile-first, jeden breakpoint desktopowy (`min-width: 64rem`).
- Sekrety wyłącznie w `.env`.
- Szczegółowe zasady dla asystenta AI: `AGENTS.MD`.

## Autorzy

- Łukasz Mroszczak: całość projektu