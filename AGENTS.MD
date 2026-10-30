# AGENTS.md

Ten plik jest przewodnikiem dla agentów AI pracujących w repozytorium **VinoKeep** (`my-app`). Przed wprowadzeniem zmian przeczytaj go razem z `README.md` i sprawdź aktualny stan kodu. Nie zakładaj, że opisana funkcja istnieje, dopóki nie potwierdzisz tego w plikach projektu.

## Cel projektu

VinoKeep to aplikacja dla winnicy i enoturystyki, tworzona w ramach kursu *Wstęp do Projektowania Aplikacji Internetowych* (WdPAI, sem. V). Jest napisana w czystym PHP 8.3 i korzysta z lekkiej, samodzielnie zaimplementowanej architektury MVC, bez frameworka PHP. Działa w kontenerach Docker Compose.

Docelowe role użytkowników (na razie tylko opisane w komentarzach, bez implementacji):

- `GUEST` — gość rezerwujący degustacje,
- `CELLAR_MASTER` — piwniczy zarządzający piwnicą,
- `WINERY_OWNER` — właściciel winnicy.

Aktualnie aplikacja obsługuje:

- stronę główną pod `/` (`DefaultController::index`) — hero z opisem posiadłości i kartami statystyk, dane na razie zapisane na sztywno w kontrolerze,
- formularz logowania pod `/login` (`SecurityController::login`) — tylko widok, bez trasy POST i bez uwierzytelniania,
- listę projektów pod `/projects` i szczegóły pod `/projects/{id}` (`ProjectController`) — przykładowe trasy z ćwiczeń, z minimalnymi widokami,
- odpowiedź 404 (`DefaultController::notFound`) dla nieznanych tras.

Nie ma jeszcze logowania, sesji, rezerwacji, oferty win ani warstwy Model — nie opisuj ich jako istniejących.

## Stack technologiczny

- PHP 8.3 z `declare(strict_types=1)`
- Composer i autoloading PSR-4
- Nginx jako serwer HTTP
- PHP-FPM do wykonywania kodu PHP
- Docker oraz Docker Compose
- HTML i CSS bez frameworka frontendowego

## Struktura katalogów

```text
.
├── docker/
│   ├── db/                    # obraz PostgreSQL (+ skrypty inicjalizujące)
│   ├── nginx/                 # obraz i konfiguracja Nginx
│   └── php/                   # obraz PHP-FPM, rozszerzenia PHP i Composer
├── public/                    # jedyny publicznie dostępny katalog
│   ├── css/style.css          # globalne style i zmienne projektu
│   ├── index.php              # front controller i definicje tras
│   └── views/                 # szablony HTML (*.html.php)
│       ├── home.html.php      # strona główna
│       ├── login.html.php     # logowanie
│       ├── not-found.html.php # 404
│       ├── projects.html.php  # lista projektów (ćwiczenie)
│       └── project.html.php   # szczegóły projektu (ćwiczenie)
├── src/
│   ├── controllers/           # App\Controller\* (Default, Security, Project, AppController)
│   └── Routing.php            # App\Routing — rejestracja i obsługa tras
├── AGENTS.MD                  # ten plik
├── README.md                  # opis projektu dla ludzi
├── composer.json              # konfiguracja autoloadingu
└── docker-compose.yaml        # usługi: server, php, db, mailpit, pgadmin-wdpai
```

Usługi Docker Compose:

| Usługa | Rola | Port na hoście |
|---|---|---|
| `server` | Nginx, document root `public/` | `8080` |
| `php` | PHP-FPM (przy starcie wykonuje `composer dump-autoload`) | — |
| `db` | PostgreSQL | `POSTGRES_PORT` (domyślnie `5433`) |
| `mailpit` | podgląd wysyłanych e-maili (dev) | `MAILPIT_PORT` (domyślnie `8025`) |
| `pgadmin-wdpai` | panel administracyjny bazy | `PGADMIN_PORT` (domyślnie `5050`) |

Nie umieszczaj logiki aplikacji ani danych wrażliwych w `public/`. Ten katalog jest document rootem Nginx i służy tylko jako punkt wejścia oraz miejsce na widoki i zasoby publiczne.

## Architektura MVC

Projekt stosuje uproszczony przepływ MVC:

```text
przeglądarka
    -> Nginx
    -> public/index.php
    -> Routing
    -> Controller
    -> Model / serwis (gdy zostanie dodany)
    -> View
    -> odpowiedź HTML
```

### Front Controller

`public/index.php` jest jedynym punktem wejścia aplikacji. Ładuje wyłącznie autoloader Composera (`vendor/autoload.php`) — nie dodawaj ręcznych `require_once` klas. Rejestruje trasy, przekazuje metodę HTTP i URI do routera oraz uruchamia widok 404 ze statusem 404, gdy żadna trasa nie pasuje.

Nowe trasy dodawaj w tym pliku w postaci:

```php
$routing->get('/example', ExampleController::class, 'index');
$routing->post('/example', ExampleController::class, 'store');
```

### Router

`src/Routing.php` (`App\Routing`) łączy metodę HTTP i ścieżkę URL z publiczną metodą kontrolera. Parametry zapisane jako `{name}` są obecnie dopasowywane tylko do cyfr, konwertowane na `int` i przekazywane do akcji kontrolera przez nazwane argumenty.

Przykład: `/projects/{id}` wywołuje `ProjectController::show(int $id)`.

Router obsługuje obecnie metody GET i POST. Nie dodawaj logiki biznesowej do routera.

### Controller

Kontrolery znajdują się w `src/controllers/`, używają przestrzeni nazw `App\Controller` i dziedziczą po `AppController`. Ich zadaniem jest:

- odebranie i walidacja danych wejściowych,
- wywołanie modelu lub serwisu,
- przygotowanie danych dla widoku,
- wybranie szablonu przez `$this->render()`.

Kontroler powinien być cienki. Nie umieszczaj w nim rozbudowanych zapytań do bazy, HTML ani stylów. Dane „na sztywno” w `DefaultController` są tymczasowe i mają trafić do modelu/repozytorium, gdy pojawi się baza.

### Model

Warstwa Model nie jest jeszcze zaimplementowana. Usługa PostgreSQL istnieje w Docker Compose, ale aplikacja jeszcze się z nią nie łączy. Nie udawaj, że dane widoczne w widokach pochodzą z bazy danych. Gdy funkcja wymaga trwałego przechowywania danych, wydziel klasy modelu/repozytorium zamiast dodawać zapytania SQL do kontrolera.

Dla nowych klas zgodnych z mapowaniem `App\` w Composerze preferuj katalogi i nazwy klas o zgodnej wielkości liter, np.:

```text
src/Model/Wine.php                   -> App\Model\Wine
src/Repository/WineRepository.php    -> App\Repository\WineRepository
```

Po dodaniu lub przeniesieniu klas odśwież autoloader poleceniem `composer dump-autoload`.

### View

Widoki znajdują się w `public/views/` i mają nazwy `*.html.php`. `AppController::render()` przekazuje do nich dane jako zmienne przy użyciu `extract(..., EXTR_SKIP)`. Każdy widok ma własny `<head>` i wspólny schemat: `header` z nawigacją, `main`, `footer`.

Widoki odpowiadają za prezentację. Dozwolone są w nich proste instrukcje warunkowe i pętle, ale nie logika biznesowa, dostęp do bazy ani obsługa routingu.

Używaj semantycznego HTML (`header`, `nav`, `main`, `section`, `article`, `footer`, `dl`, `fieldset`), etykiet `label` powiązanych z polami oraz `aria-current="page"` w aktywnym linku nawigacji.

Każda wartość pochodząca od użytkownika lub z bazy musi być escapowana w kontekście HTML — widoki definiują w tym celu pomocnik `$e`:

```php
<?php $e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<?= $e($value) ?>
```

## Stylowanie (CSS)

Cały wygląd mieści się w jednym arkuszu `public/css/style.css`, zgodnym z systemem projektowym „Terroir & Heritage Luxe” (projekt widoków powstał w Figma/Stitch; materiały źródłowe nie są częścią repozytorium).

- **Zmienne** w `:root`: kolory (`--color-primary`, `--color-primary-deep`, `--color-primary-hover`, `--color-gold`, `--color-gold-light`, `--color-canvas`, `--color-surface`, `--color-text`, `--color-muted`, `--color-border`, `--color-gold-trim`, `--color-on-primary`), fonty (`--font-serif`, `--font-sans`), odstępy (`--space-xs` … `--space-2xl`, `--page-margin`, `--page-max`), promienie (`--radius`, `--radius-lg`) i cień (`--shadow-ambient`). Nie wpisuj wartości kolorów ani odstępów bezpośrednio w regułach — dodaj lub użyj zmiennej.
- **Typografia:** Playfair Display (nagłówki) i Plus Jakarta Sans (tekst), ładowane z Google Fonts w `<head>` widoków.
- **Układ:** Flexbox (nagłówek, nawigacja, rzędy akcji) i Grid (hero, karty statystyk). Kontener strony to klasa `.page`.
- **Mobile-first:** style bazowe dotyczą widoku mobilnego, a jedyny breakpoint to `@media (min-width: 64rem)` (desktop). **Nie dodawaj breakpointu tabletowego** — to wyraźna decyzja właściciela projektu.
- **Stany interaktywne:** każdy element interaktywny ma `:hover` oraz widoczny `:focus-visible` (kontur 3 px, na ciemnym tle hero w kolorze `--color-gold-light`). Nigdy nie usuwaj konturu fokusu bez zamiennika.
- Zachowuj kontrast tekstu co najmniej 4,5:1 (WCAG AA), a styl komponentów trzymaj w klasach, nie w atrybutach `style`.
- Nazwy klas zapisuj po angielsku, w `kebab-case`.

## Konwencje implementacji

- Zachowuj `declare(strict_types=1)` we wszystkich nowych plikach PHP.
- Używaj deklaracji typów parametrów, zwracanych wartości i właściwości.
- Nazwy klas zapisuj w `PascalCase`, a metod i zmiennych w `camelCase`.
- Nazwy techniczne w kodzie zapisuj po angielsku; tekst interfejsu jest po polsku (`<html lang="pl">`).
- Jeden kontroler powinien obsługiwać jeden spójny obszar aplikacji.
- Dane przekazuj do widoku jawnie w tablicy drugiego argumentu `render()` (każdy widok dostaje m.in. `pageTitle`).
- Nie twórz globalnego stanu i nie używaj `$_GET`, `$_POST` ani `$_SERVER` poza warstwą wejścia, jeżeli dane można przekazać jako argument.
- Nie dodawaj frameworka ani dużej biblioteki bez wyraźnej potrzeby i zgody użytkownika.
- Sekrety umieszczaj w `.env`; nigdy nie commituj haseł, tokenów ani danych dostępowych.
- Nie edytuj `vendor/` ręcznie.
- Zachowuj zakres zadania i nie zmieniaj niezwiązanych plików.
- Pliki w repozytorium mają końcówki linii zgodne z ustawieniami użytkownika (Windows, CRLF w katalogu roboczym) — nie zmieniaj ich masowo.

## Dodawanie nowej funkcji

Typowy porządek prac:

1. Dodaj trasę w `public/index.php`.
2. Dodaj albo rozszerz kontroler w `src/controllers/`.
3. Dodaj model, repozytorium lub serwis, jeśli funkcja zawiera logikę biznesową albo dostęp do danych.
4. Dodaj widok w `public/views/` i przekaż do niego tylko potrzebne dane.
5. Dodaj lub zmień style w `public/css/style.css`, korzystając ze zmiennych.
6. Sprawdź poprawną trasę, dane wejściowe, HTML, widok mobilny i desktopowy oraz zachowanie dla błędów i 404.

## Uruchamianie projektu

```bash
docker compose up --build -d
```

Aplikacja jest dostępna pod `http://localhost:8080` (port ustawiony w `docker-compose.yaml`).

Przydatne polecenia:

```bash
docker compose ps
docker compose logs -f
docker compose exec php php -l src/Routing.php
docker compose exec php composer dump-autoload
docker compose down
```

Kontener `php` przy starcie sam odświeża autoloader. Jeśli katalog `vendor/` nie istnieje, zainstaluj zależności/autoloader poleceniem:

```bash
docker compose run --rm php composer install
```

## Weryfikacja zmian

W projekcie nie ma obecnie skonfigurowanego zestawu testów automatycznych ani narzędzia do formatowania. Po zmianie:

- uruchom `php -l` dla każdego zmienionego pliku PHP,
- sprawdź `composer dump-autoload`, jeżeli zmieniałeś klasy lub namespace,
- uruchom kontenery i sprawdź zmienione endpointy (`/`, `/login`, `/projects`, `/projects/1`, nieistniejąca trasa → 404),
- przy zmianach CSS sprawdź widok mobilny (np. 375 px) i desktopowy (≥ 1024 px) pod kątem braku poziomego przewijania oraz stany `:hover` i `:focus-visible` (nawigacja klawiszem Tab),
- upewnij się, że logi `docker compose logs` nie zawierają nowych błędów,
- nie twierdź, że testy przeszły, jeżeli ich faktycznie nie uruchomiono.

## Git i współpraca

- Praca jest zapisywana w małych, logicznych commitach w stylu Conventional Commits, np. `feat: add hover and focus-visible states`, `chore: ...`, `docs: ...`.
- **Commity i push wykonuje użytkownik.** Agent nie wykonuje `git commit` ani `git push`; po zakończeniu kroku podaje, które pliki dodać (`git add`) i proponuje tytuł commitu.
- Nie wysyłaj zmian na GitHub bez wyraźnej prośby.

## Zasady dla agentów AI

- Najpierw przeczytaj pliki bezpośrednio związane z zadaniem i potwierdź aktualną implementację.
- Przed edycją sprawdź `git status`; zachowaj zmiany użytkownika i nie cofaj ich.
- Wprowadzaj najmniejszą spójną zmianę realizującą wymaganie.
- Utrzymuj rozdział odpowiedzialności MVC.
- Nie rozszerzaj zakresu zadania o refaktoryzacje, migracje lub nowe zależności bez uzasadnienia.
- Przy zadaniach kursowych wykonuj dokładnie to, co opisuje polecenie — nic ponad nie.
- Jeśli wymaganie jest niejednoznaczne, wybierz rozwiązanie zgodne z istniejącymi wzorcami; pytaj tylko wtedy, gdy wybór istotnie zmieni zachowanie aplikacji.
- Po zakończeniu opisz zmienione pliki oraz wykonaną weryfikację.