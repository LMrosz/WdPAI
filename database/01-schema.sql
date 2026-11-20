-- VinoKeep: schemat bazy PostgreSQL (Lab 7).
--
-- Wykonanie na działającym kontenerze (raz, na pustej bazie):
--   docker compose exec -T db psql -U app -d app < database/01-schema.sql
-- Przy pierwszym starcie z pustym wolumenem pg-data skrypty z tego katalogu
-- uruchamiają się same, w kolejności nazw plików (01, 02, 03).
--
-- Konwencje: klucze główne to GENERATED ALWAYS AS IDENTITY, nazwy ograniczeń są jawne
-- (tabela_kolumna_rodzaj), a każdy klucz obcy ma zapisaną akcję ON DELETE i ON UPDATE.

-- Role użytkowników. Kod roli sprawdza backend (GUEST, CELLAR_MASTER, WINERY_OWNER).
CREATE TABLE roles (
    id   SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(60) NOT NULL,
    CONSTRAINT roles_code_key   UNIQUE (code),
    CONSTRAINT roles_code_check CHECK (code ~ '^[A-Z][A-Z_]*$')
);

-- Konta użytkowników. E-mail zapisujemy małymi literami, więc zwykły UNIQUE
-- wystarcza do wykrycia duplikatu. Hasło to wyłącznie skrót z password_hash().
CREATE TABLE users (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email         VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name  VARCHAR(100) NULL,
    role_id       SMALLINT     NOT NULL,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_email_key       UNIQUE (email),
    CONSTRAINT users_email_check     CHECK (email = lower(email) AND position('@' IN email) > 1),
    CONSTRAINT users_role_id_fkey    FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Katalog win. Cena jest kwotą w PLN, a napis „165 zł” składa dopiero aplikacja.
CREATE TABLE wines (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name       VARCHAR(150)  NOT NULL,
    category   VARCHAR(20)   NOT NULL,
    type       VARCHAR(60)   NOT NULL,
    grape      VARCHAR(80)   NULL,
    vintage    SMALLINT      NULL,
    abv        NUMERIC(4, 1) NULL,
    award      VARCHAR(100)  NULL,
    notes      TEXT          NOT NULL,
    story      TEXT          NULL,
    aroma      TEXT          NULL,
    palate     TEXT          NULL,
    finish     TEXT          NULL,
    serving    VARCHAR(150)  NULL,
    cellaring  VARCHAR(200)  NULL,
    price      NUMERIC(8, 2) NOT NULL,
    created_at TIMESTAMPTZ   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT wines_category_check CHECK (category IN ('red', 'white', 'rose', 'sparkling')),
    CONSTRAINT wines_vintage_check  CHECK (vintage BETWEEN 1900 AND 2100),
    CONSTRAINT wines_abv_check      CHECK (abv BETWEEN 0 AND 25),
    CONSTRAINT wines_price_check    CHECK (price >= 0),
    -- to samo wino z tym samym rocznikiem nie może wystąpić dwa razy (także gdy rocznika brak)
    CONSTRAINT wines_name_vintage_key UNIQUE NULLS NOT DISTINCT (name, vintage)
);

-- Potrawy pasujące do wina: relacja 1:N. Usunięcie wina usuwa jego potrawy.
CREATE TABLE wine_pairings (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    wine_id    BIGINT       NOT NULL,
    sort_order SMALLINT     NOT NULL,
    dish       VARCHAR(150) NOT NULL,
    CONSTRAINT wine_pairings_wine_id_fkey    FOREIGN KEY (wine_id) REFERENCES wines (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT wine_pairings_sort_order_check CHECK (sort_order > 0),
    CONSTRAINT wine_pairings_wine_order_key   UNIQUE (wine_id, sort_order)
);

-- Rodzaje degustacji z oferty winnicy.
CREATE TABLE tastings (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name             VARCHAR(100)  NOT NULL,
    badge            VARCHAR(40)   NULL,
    description      TEXT          NOT NULL,
    duration_minutes SMALLINT      NOT NULL,
    max_group_size   SMALLINT      NOT NULL,
    price_per_person NUMERIC(8, 2) NOT NULL,
    CONSTRAINT tastings_name_key             UNIQUE (name),
    CONSTRAINT tastings_duration_check       CHECK (duration_minutes > 0),
    CONSTRAINT tastings_max_group_size_check CHECK (max_group_size > 0),
    CONSTRAINT tastings_price_check          CHECK (price_per_person >= 0)
);

-- Wina podawane na danej degustacji: relacja M:N z kluczem złożonym.
CREATE TABLE tasting_wines (
    tasting_id BIGINT NOT NULL,
    wine_id    BIGINT NOT NULL,
    PRIMARY KEY (tasting_id, wine_id),
    CONSTRAINT tasting_wines_tasting_id_fkey FOREIGN KEY (tasting_id) REFERENCES tastings (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT tasting_wines_wine_id_fkey    FOREIGN KEY (wine_id) REFERENCES wines (id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- Terminy degustacji, na które można rezerwować miejsca.
CREATE TABLE tasting_sessions (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tasting_id  BIGINT      NOT NULL,
    starts_at   TIMESTAMPTZ NOT NULL,
    seats_total SMALLINT    NOT NULL,
    CONSTRAINT tasting_sessions_tasting_id_fkey FOREIGN KEY (tasting_id) REFERENCES tastings (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT tasting_sessions_seats_check     CHECK (seats_total > 0),
    CONSTRAINT tasting_sessions_term_key        UNIQUE (tasting_id, starts_at)
);

-- Rezerwacje gości na terminy. Limit miejsc w terminie pilnuje transakcja w aplikacji
-- (suma miejsc z potwierdzonych rezerwacji nie przekracza seats_total), bo CHECK
-- nie może zajrzeć do innych wierszy.
CREATE TABLE reservations (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id    BIGINT      NOT NULL,
    session_id BIGINT      NOT NULL,
    seats      SMALLINT    NOT NULL,
    status     VARCHAR(20) NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT reservations_user_id_fkey    FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    -- termin z rezerwacjami nie może zniknąć po cichu
    CONSTRAINT reservations_session_id_fkey FOREIGN KEY (session_id) REFERENCES tasting_sessions (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT reservations_seats_check     CHECK (seats BETWEEN 1 AND 12),
    CONSTRAINT reservations_status_check    CHECK (status IN ('confirmed', 'cancelled')),
    CONSTRAINT reservations_user_session_key UNIQUE (user_id, session_id)
);
