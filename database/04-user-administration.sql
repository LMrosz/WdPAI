-- VinoKeep: zarządzanie użytkownikami (Lab 11).
--
-- Skrypt dopisuje do istniejącej bazy:
--   * rolę ADMIN (administratorów może być wielu, właściciel nadaje i odbiera tę rolę),
--   * kolumnę users.is_blocked (zablokowane konto nie może się zalogować),
--   * indeks, który pozwala mieć w bazie tylko jednego właściciela winnicy (WINERY_OWNER),
--   * konto demonstracyjne administratora.
--
-- Przy pierwszym starcie z pustym wolumenem pg-data uruchamia się sam, po 01, 02 i 03.
-- Na działającej bazie wykonaj go raz:
--   docker compose exec db psql -U app -d app -v ON_ERROR_STOP=1 -f /docker-entrypoint-initdb.d/04-user-administration.sql
-- Skrypt można uruchomić ponownie bez szkody (IF NOT EXISTS, ON CONFLICT).

-- Administrator ma uprawnienia właściciela do zarządzania kontami; w aplikacji różni go od
-- właściciela to, że nie może zarządzać innymi administratorami (Role::permissions()).
INSERT INTO roles (code, name) VALUES ('ADMIN', 'Administrator')
ON CONFLICT (code) DO NOTHING;

-- Blokada nie usuwa konta ani jego rezerwacji, tylko zamyka logowanie i unieważnia aktywną sesję.
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN NOT NULL DEFAULT FALSE;

-- Właściciel jest jeden: częściowy indeks UNIQUE obejmuje tylko wiersze z rolą WINERY_OWNER,
-- więc druga taka rola kończy się błędem 23505. Id roli trzeba wstawić do polecenia dynamicznie,
-- bo predykat indeksu nie może zawierać podzapytania.
DO $$
BEGIN
    EXECUTE format(
        'CREATE UNIQUE INDEX IF NOT EXISTS users_single_owner_idx ON users (role_id) WHERE role_id = %s',
        (SELECT id FROM roles WHERE code = 'WINERY_OWNER')
    );
END
$$;

-- Konto demonstracyjne administratora, to samo hasło co pozostałe konta demo: Winiarz-2026!
-- (tylko do lokalnego rozwoju, w prawdziwym wdrożeniu trzeba je usunąć).
INSERT INTO users (email, password_hash, display_name, role_id)
VALUES (
    'admin@corvo-bianco.example',
    '$2y$10$EuHfB8/zDyYoEh5tTBmlVev8w8rcz9Cg3Cf.nf0ZqdUpYlASrS7Nq',
    'Demo Administrator',
    (SELECT id FROM roles WHERE code = 'ADMIN')
)
ON CONFLICT (email) DO NOTHING;
