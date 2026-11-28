-- VinoKeep: przypomnienia o degustacjach (Lab 13, zadanie CRON).
--
-- Skrypt dodaje do tabeli reservations kolumnę reminder_sent_at: moment, w którym zadanie cykliczne
-- (bin/send-tasting-reminders.php) przejęło rezerwację do wysłania przypomnienia. NULL znaczy "jeszcze nie przypominano".
-- Dzięki tej kolumnie zadanie jest bezpieczne przy powtórzeniu: drugie uruchomienie w tej samej godzinie
-- albo dwa równoległe uruchomienia nie wyślą drugiej wiadomości o tej samej rezerwacji.
-- Częściowy indeks obejmuje tylko rezerwacje czekające na przypomnienie, więc pozostaje mały.
--
-- Przy pierwszym starcie z pustym wolumenem pg-data uruchamia się sam, po 01-07.
-- Na działającej bazie wykonaj go raz:
--   docker compose exec db psql -U app -d app -v ON_ERROR_STOP=1 -f /docker-entrypoint-initdb.d/08-reservation-reminders.sql
-- Skrypt można uruchomić ponownie bez szkody (IF NOT EXISTS).

ALTER TABLE reservations ADD COLUMN IF NOT EXISTS reminder_sent_at TIMESTAMPTZ NULL;

CREATE INDEX IF NOT EXISTS reservations_pending_reminder_idx
    ON reservations (session_id)
    WHERE status = 'confirmed' AND reminder_sent_at IS NULL;
