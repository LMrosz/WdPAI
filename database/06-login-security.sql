-- VinoKeep: bezpieczeństwo logowania (Lab 12).
--
-- Skrypt dodaje tabelę failed_logins: dziennik nieudanych prób logowania. Służy do dwóch rzeczy:
--   * ograniczenia liczby prób (po kilku błędach logowanie jest chwilowo blokowane),
--   * audytu (kto, kiedy i z jakiego adresu IP próbował się zalogować).
-- Tabela nie zawiera haseł, ani jawnych, ani skrótów. Adres e-mail jest taki, jak wpisano w formularzu
-- (małymi literami), także gdy konto o takim adresie nie istnieje.
--
-- Przy pierwszym starcie z pustym wolumenem pg-data uruchamia się sam, po 01-05.
-- Na działającej bazie wykonaj go raz:
--   docker compose exec db psql -U app -d app -v ON_ERROR_STOP=1 -f /docker-entrypoint-initdb.d/06-login-security.sql
-- Skrypt można uruchomić ponownie bez szkody (IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS failed_logins (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(254) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    reason VARCHAR(30) NOT NULL CHECK (reason IN ('invalid_credentials', 'account_blocked')),
    user_agent VARCHAR(255),
    attempted_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Limit prób liczy ostatnie błędy dla pary (adres e-mail, adres IP) oraz dla samego adresu IP.
CREATE INDEX IF NOT EXISTS failed_logins_email_ip_idx ON failed_logins (email, ip_address, attempted_at DESC);
CREATE INDEX IF NOT EXISTS failed_logins_ip_idx ON failed_logins (ip_address, attempted_at DESC);
