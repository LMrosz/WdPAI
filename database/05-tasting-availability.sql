-- VinoKeep: dostępność terminów degustacji (Lab 8, rozszerzone w Lab 11).
--
-- Skrypt dopisuje do istniejącej bazy:
--   * widok tasting_session_availability: termin razem z liczbą zajętych i wolnych miejsc
--     oraz limitem osób w jednej rezerwacji,
--   * dane demonstracyjne: terminy na najbliższe soboty, żeby rezerwacja miała co pokazać
--     także po upływie terminów z 03-seed.sql.
--
-- Przy pierwszym starcie z pustym wolumenem pg-data uruchamia się sam, po 01-04.
-- Na działającej bazie wykonaj go raz:
--   docker compose exec db psql -U app -d app -v ON_ERROR_STOP=1 -f /docker-entrypoint-initdb.d/05-tasting-availability.sql
-- Skrypt można uruchomić ponownie bez szkody (CREATE OR REPLACE, ON CONFLICT).

-- Widok liczy zajęte miejsca raz, w jednym miejscu: aplikacja czyta z niego listę terminów
-- (PostgresTastingRepository), a rezerwacja sprawdza limit w transakcji tym samym warunkiem
-- (tylko potwierdzone rezerwacje zajmują miejsca). Limit 12 osób to ograniczenie
-- reservations_seats_check, a mniejszy limit grupy może wynikać z samej degustacji.
CREATE OR REPLACE VIEW tasting_session_availability AS
SELECT
    s.id                                            AS session_id,
    s.tasting_id,
    t.name                                          AS tasting_name,
    s.starts_at,
    s.seats_total,
    COALESCE(SUM(r.seats) FILTER (WHERE r.status = 'confirmed'), 0)::integer AS seats_booked,
    GREATEST(
        s.seats_total - COALESCE(SUM(r.seats) FILTER (WHERE r.status = 'confirmed'), 0),
        0
    )::integer                                      AS seats_free,
    LEAST(t.max_group_size, 12)::integer            AS max_seats
FROM tasting_sessions s
JOIN tastings t ON t.id = s.tasting_id
LEFT JOIN reservations r ON r.session_id = s.id
GROUP BY s.id, t.id;

-- Terminy demonstracyjne: sobota bieżącego tygodnia i trzech kolejnych, o godzinach z oferty.
-- Wiersze już istniejące (ta sama degustacja i godzina) są pomijane dzięki UNIQUE (tasting_id, starts_at).
INSERT INTO tasting_sessions (tasting_id, starts_at, seats_total)
SELECT
    t.id,
    ((date_trunc('week', CURRENT_DATE)::date + 5 + 7 * weeks.n) + plan.starts_at) AT TIME ZONE 'Europe/Warsaw',
    plan.seats_total
FROM (VALUES
    ('Degustacja klasyczna', TIME '14:00', 8),
    ('Degustacja Sepremento', TIME '11:00', 6),
    ('Kolacja w winnicy', TIME '18:30', 12)
) AS plan (tasting_name, starts_at, seats_total)
JOIN tastings t ON t.name = plan.tasting_name
CROSS JOIN generate_series(0, 3) AS weeks (n)
ON CONFLICT (tasting_id, starts_at) DO NOTHING;
