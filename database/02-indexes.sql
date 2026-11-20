-- VinoKeep: indeksy (Lab 7).
--
-- PostgreSQL sam tworzy indeksy dla kluczy głównych i ograniczeń UNIQUE, ale nie dla kluczy obcych.
-- Dodajemy więc tylko te, po których aplikacja naprawdę filtruje, łączy lub sortuje.
-- Pokrycie indeksami z ograniczeń: wines (name, vintage), wine_pairings (wine_id, sort_order),
-- tasting_wines (tasting_id, wine_id), tasting_sessions (tasting_id, starts_at),
-- reservations (user_id, session_id), users (email).

-- Filtr katalogu win: /wines?type=red
CREATE INDEX wines_category_idx ON wines (category);

-- JOIN użytkownika z jego rolą i wyszukiwanie użytkowników danej roli
CREATE INDEX users_role_id_idx ON users (role_id);

-- Które degustacje podają dane wino (klucz złożony pokrywa tylko kierunek od degustacji)
CREATE INDEX tasting_wines_wine_id_idx ON tasting_wines (wine_id);

-- Najbliższe terminy degustacji
CREATE INDEX tasting_sessions_starts_at_idx ON tasting_sessions (starts_at);

-- Lista rezerwacji użytkownika od najnowszej (filtr po user_id i sortowanie po created_at w jednym indeksie)
CREATE INDEX reservations_user_created_idx ON reservations (user_id, created_at DESC);

-- Zajęte miejsca w terminie: liczą się tylko potwierdzone rezerwacje, więc indeks częściowy
CREATE INDEX reservations_session_confirmed_idx ON reservations (session_id) WHERE status = 'confirmed';
