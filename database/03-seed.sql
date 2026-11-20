-- VinoKeep: dane demonstracyjne (Lab 7).
--
-- Wina i degustacje pochodzą z domyślnej treści Corvo Bianco (config/content.php).
-- Kolejność wstawiania wines zachowuje numerację z listy w pliku, więc adresy /wines/1 ... /wines/10
-- wskazują te same wina co wcześniej. Konta demonstracyjne mają to samo hasło: Winiarz-2026!
-- (tylko do lokalnego rozwoju, w prawdziwym wdrożeniu trzeba je usunąć).

INSERT INTO roles (code, name) VALUES
    ('GUEST', 'Gość'),
    ('CELLAR_MASTER', 'Piwniczy'),
    ('WINERY_OWNER', 'Właściciel winnicy');

INSERT INTO users (email, password_hash, display_name, role_id) VALUES
    ('gosc@corvo-bianco.example', '$2y$10$EuHfB8/zDyYoEh5tTBmlVev8w8rcz9Cg3Cf.nf0ZqdUpYlASrS7Nq', 'Demo Gość', (SELECT id FROM roles WHERE code = 'GUEST')),
    ('piwniczy@corvo-bianco.example', '$2y$10$EuHfB8/zDyYoEh5tTBmlVev8w8rcz9Cg3Cf.nf0ZqdUpYlASrS7Nq', 'Demo Piwniczy', (SELECT id FROM roles WHERE code = 'CELLAR_MASTER')),
    ('wlasciciel@corvo-bianco.example', '$2y$10$EuHfB8/zDyYoEh5tTBmlVev8w8rcz9Cg3Cf.nf0ZqdUpYlASrS7Nq', 'Demo Właściciel', (SELECT id FROM roles WHERE code = 'WINERY_OWNER'));

INSERT INTO wines (name, category, type, grape, vintage, abv, award, notes, story, aroma, palate, finish, serving, cellaring, price) VALUES
    ('Sepremento', 'red', 'Czerwone wytrawne', 'Carvanere', NULL, NULL, 'Flagowe wino posiadłości', 'Tradycyjne wino przypisane bezpośrednio do posiadłości Corvo Bianco. Ze szczepu carvanere, o wyrazistych nutach jeżyn, wiśni, śliwek i cynamonu.', 'Sepremento to wino, które nosi nazwę posiadłości i trafia do piwnicy w niewielkich, ręcznie numerowanych partiach. Carvanere, rzadki szczep Toussaint, daje mu głębię i charakter, a długie dojrzewanie w dębowych beczkach nadaje okrągłą, aksamitną strukturę.', 'Dojrzałe jeżyny, wiśnia i śliwka z dodatkiem cynamonu.', 'Soczysty, pełny, z miękkimi taninami i nutą przypraw korzennych.', 'Długi, ciepły, z posmakiem czarnych owoców.', '16–18 °C, najlepiej po pół godzinie w karafce', 'Dobrze się starzeje przez 8–10 lat od rocznika.', 165.00),
    ('Biały Wilk', 'white', 'Białe wytrawne', NULL, NULL, NULL, NULL, 'White Wolf. Świeże, rześkie wino o czystym, wytrawnym finiszu.', 'Biały Wilk to wino na każdą porę dnia: świeże, rześkie i czyste. Powstaje z owoców zbieranych wcześnie rano, a fermentacja w niskiej temperaturze zachowuje jego lekki, owocowy charakter.', 'Zielone jabłko, cytrusy i białe kwiaty.', 'Rześki, wytrawny, z żywą kwasowością i mineralnym tłem.', 'Czysty, orzeźwiający, z cytrusową nutą.', '8–10 °C', 'Najlepsze młode, w ciągu 2–3 lat od rocznika.', 110.00),
    ('Rzeźnik z Blaviken', 'red', 'Czerwone wytrawne', NULL, NULL, NULL, NULL, 'Butcher of Blaviken. Mocne, ciemne wino o zdecydowanym, wytrawnym charakterze.', 'Rzeźnik z Blaviken to ciemne, zdecydowane wino dla tych, którzy lubią wyrazisty charakter. Długa maceracja nadaje mu intensywną barwę i mocną strukturę.', 'Czarna porzeczka, skóra i dym, z nutą pieprzu.', 'Pełny, mocny, z wyraźnymi taninami i wytrawnym charakterem.', 'Długi, lekko pieprzny.', '17–19 °C', 'Dojrzewa 5–7 lat od rocznika.', 105.00),
    ('Geralt z Rivii', 'red', 'Czerwone wytrawne', NULL, NULL, NULL, NULL, 'Geralt of Rivia. Dojrzałe czerwone wino o głębokim smaku i długim posmaku.', 'Geralt z Rivii to dojrzałe czerwone wino o głębokim smaku. Leżakuje długo, dzięki czemu jest zrównoważone, a jego posmak zostaje na długo po ostatnim łyku.', 'Wiśnia, suszona śliwka, wanilia i nuty dębu.', 'Głęboki, gładki, z dobrze ułożonymi taninami.', 'Bardzo długi, z nutą wanilii i przypraw.', '16–18 °C', 'Dojrzewa 6–8 lat od rocznika.', 120.00),
    ('Reserve Chardonnay', 'white', 'Białe wytrawne', NULL, 2021, 12.8, 'Złoty medal', 'Ciepła brioszka, krzemień i skórka cytryny, z solną czystością wapiennej gleby.', 'Winogrona na Reserve Chardonnay rosną na wapiennych zboczach winnicy. Część wina dojrzewa w beczkach, dzięki czemu zyskuje kremową fakturę, a kwasowość i mineralność zostają zachowane.', 'Ciepła brioszka, krzemień i skórka cytryny.', 'Kremowy, a zarazem świeży, z solną mineralnością.', 'Długi, czysty, cytrusowy.', '10–12 °C', 'Dobrze się starzeje przez 4–6 lat.', 98.00),
    ('Grand Cru Pinot Noir', 'red', 'Czerwone wytrawne', NULL, 2020, 13.5, '97 pkt', 'Dzika czarna wiśnia, trufla i aksamitny finisz po dojrzewaniu w dębowej beczce.', 'Grand Cru Pinot Noir powstaje z najlepszych winogron z najstarszych krzewów. Dojrzewa w dębowej beczce, co nadaje mu aksamitny finisz i elegancką złożoność.', 'Dzika czarna wiśnia, trufla i leśne runo.', 'Jedwabisty, elegancki, z delikatnymi taninami.', 'Długi, aksamitny, z nutą dębu.', '15–17 °C', 'Dojrzewa 8–12 lat od rocznika.', 145.00),
    ('Late Harvest Riesling', 'white', 'Białe słodkie', NULL, 2021, 8.5, '94 pkt', 'Kandyzowana bergamotka, miód akacjowy i szafran, równoważone krystaliczną kwasowością.', 'Late Harvest Riesling powstaje z owoców zbieranych późno, gdy są już bardzo dojrzałe i słodkie. Wysoka kwasowość równoważy słodycz, więc wino pozostaje lekkie.', 'Kandyzowana bergamotka, miód akacjowy i szafran.', 'Słodki, ale rześki, z krystaliczną kwasowością.', 'Długi, miodowy.', '6–8 °C', 'Może leżakować 10 lat i dłużej.', 112.00),
    ('Rosé de Toussaint', 'rose', 'Różowe wytrawne', NULL, 2023, 12.0, NULL, 'Truskawka, grejpfrut i świeże zioła; lekkie wino na ciepłe popołudnie w ogrodzie.', 'Rosé de Toussaint to lekkie wino na ciepłe popołudnie w ogrodzie. Krótki kontakt moszczu ze skórkami nadaje mu delikatną różową barwę i świeży charakter.', 'Truskawka, grejpfrut i świeże zioła.', 'Lekki, rześki, wytrawny.', 'Krótki, owocowy i czysty.', '8–10 °C', 'Najlepsze w ciągu 2 lat od rocznika.', 72.00),
    ('Cuvée Brut', 'sparkling', 'Musujące brut', NULL, 2019, 12.5, 'Srebrny medal', 'Drobna perlistość, zielone jabłko i chrupiąca skórka chleba z długiego leżakowania.', 'Cuvée Brut długo leży na osadzie, co daje mu drobną, trwałą perlistość i smak świeżo upieczonego chleba. Dobrze sprawdza się na uroczyste okazje.', 'Zielone jabłko, skórka chleba i nuty migdałów.', 'Wytrawne, z drobną perlistością i żywą kwasowością.', 'Czysty, chrupiący, długi.', '6–8 °C, w wąskim kieliszku', 'Dobrze się starzeje przez 3–5 lat.', 128.00),
    ('Cellar Master Merlot', 'red', 'Czerwone wytrawne', NULL, 2020, 13.8, NULL, 'Śliwka, czarna porzeczka i nuty kakao; miękkie taniny, dobre do dłuższego leżakowania.', 'Cellar Master Merlot to wino wybrane przez piwniczego jako najlepsze do dłuższego leżakowania. Jest miękkie i okrągłe, a ma dość struktury, by dojrzewać latami.', 'Śliwka, czarna porzeczka i kakao.', 'Okrągły i soczysty, z miękkimi taninami.', 'Średnio długi, czekoladowy.', '16–18 °C', 'Dojrzewa 6–8 lat od rocznika.', 89.00);

INSERT INTO wine_pairings (wine_id, sort_order, dish) VALUES
    ((SELECT id FROM wines WHERE name = 'Sepremento'), 1, 'Dziczyzna i pieczona jagnięcina'),
    ((SELECT id FROM wines WHERE name = 'Sepremento'), 2, 'Dojrzewające sery twarde'),
    ((SELECT id FROM wines WHERE name = 'Sepremento'), 3, 'Duszone mięsa w sosie winnym'),
    ((SELECT id FROM wines WHERE name = 'Biały Wilk'), 1, 'Ryby i owoce morza'),
    ((SELECT id FROM wines WHERE name = 'Biały Wilk'), 2, 'Sałatki z kozim serem'),
    ((SELECT id FROM wines WHERE name = 'Biały Wilk'), 3, 'Lekkie dania z kurczaka'),
    ((SELECT id FROM wines WHERE name = 'Rzeźnik z Blaviken'), 1, 'Grillowana wołowina'),
    ((SELECT id FROM wines WHERE name = 'Rzeźnik z Blaviken'), 2, 'Dziczyzna'),
    ((SELECT id FROM wines WHERE name = 'Rzeźnik z Blaviken'), 3, 'Dojrzałe sery'),
    ((SELECT id FROM wines WHERE name = 'Geralt z Rivii'), 1, 'Pieczona wołowina'),
    ((SELECT id FROM wines WHERE name = 'Geralt z Rivii'), 2, 'Potrawy z grzybami'),
    ((SELECT id FROM wines WHERE name = 'Geralt z Rivii'), 3, 'Długo dojrzewające sery'),
    ((SELECT id FROM wines WHERE name = 'Reserve Chardonnay'), 1, 'Homar i przegrzebki'),
    ((SELECT id FROM wines WHERE name = 'Reserve Chardonnay'), 2, 'Kurczak w kremowym sosie'),
    ((SELECT id FROM wines WHERE name = 'Reserve Chardonnay'), 3, 'Miękkie sery typu brie'),
    ((SELECT id FROM wines WHERE name = 'Grand Cru Pinot Noir'), 1, 'Kaczka z sosem wiśniowym'),
    ((SELECT id FROM wines WHERE name = 'Grand Cru Pinot Noir'), 2, 'Risotto z truflami'),
    ((SELECT id FROM wines WHERE name = 'Grand Cru Pinot Noir'), 3, 'Pieczone grzyby'),
    ((SELECT id FROM wines WHERE name = 'Late Harvest Riesling'), 1, 'Foie gras'),
    ((SELECT id FROM wines WHERE name = 'Late Harvest Riesling'), 2, 'Ciasta owocowe'),
    ((SELECT id FROM wines WHERE name = 'Late Harvest Riesling'), 3, 'Sery z niebieską pleśnią'),
    ((SELECT id FROM wines WHERE name = 'Rosé de Toussaint'), 1, 'Sałatki z owocami morza'),
    ((SELECT id FROM wines WHERE name = 'Rosé de Toussaint'), 2, 'Grillowane warzywa'),
    ((SELECT id FROM wines WHERE name = 'Rosé de Toussaint'), 3, 'Lekkie przystawki'),
    ((SELECT id FROM wines WHERE name = 'Cuvée Brut'), 1, 'Ostrygi i owoce morza'),
    ((SELECT id FROM wines WHERE name = 'Cuvée Brut'), 2, 'Przystawki na uroczystości'),
    ((SELECT id FROM wines WHERE name = 'Cuvée Brut'), 3, 'Smażone ryby'),
    ((SELECT id FROM wines WHERE name = 'Cellar Master Merlot'), 1, 'Pieczona wieprzowina'),
    ((SELECT id FROM wines WHERE name = 'Cellar Master Merlot'), 2, 'Makaron z sosem mięsnym'),
    ((SELECT id FROM wines WHERE name = 'Cellar Master Merlot'), 3, 'Sery półtwarde');

INSERT INTO tastings (name, badge, description, duration_minutes, max_group_size, price_per_person) VALUES
    ('Degustacja klasyczna', 'Na początek', 'Trzy wina z bieżących roczników, podane z pieczywem i serami z okolicy.', 90, 8, 90.00),
    ('Degustacja Sepremento', 'Flagowa', 'Pionowa degustacja flagowego wina posiadłości z krótkim spacerem między krzewami.', 120, 6, 150.00),
    ('Kolacja w winnicy', NULL, 'Kolacja przy długim stole, do której każde danie ma dobrane wino z naszej piwnicy.', 180, 12, 260.00);

INSERT INTO tasting_wines (tasting_id, wine_id) VALUES
    ((SELECT id FROM tastings WHERE name = 'Degustacja klasyczna'), (SELECT id FROM wines WHERE name = 'Reserve Chardonnay')),
    ((SELECT id FROM tastings WHERE name = 'Degustacja klasyczna'), (SELECT id FROM wines WHERE name = 'Grand Cru Pinot Noir')),
    ((SELECT id FROM tastings WHERE name = 'Degustacja klasyczna'), (SELECT id FROM wines WHERE name = 'Rosé de Toussaint')),
    ((SELECT id FROM tastings WHERE name = 'Degustacja Sepremento'), (SELECT id FROM wines WHERE name = 'Sepremento')),
    ((SELECT id FROM tastings WHERE name = 'Kolacja w winnicy'), (SELECT id FROM wines WHERE name = 'Sepremento')),
    ((SELECT id FROM tastings WHERE name = 'Kolacja w winnicy'), (SELECT id FROM wines WHERE name = 'Reserve Chardonnay')),
    ((SELECT id FROM tastings WHERE name = 'Kolacja w winnicy'), (SELECT id FROM wines WHERE name = 'Late Harvest Riesling')),
    ((SELECT id FROM tastings WHERE name = 'Kolacja w winnicy'), (SELECT id FROM wines WHERE name = 'Cuvée Brut'));

INSERT INTO tasting_sessions (tasting_id, starts_at, seats_total) VALUES
    ((SELECT id FROM tastings WHERE name = 'Degustacja klasyczna'), '2026-12-05 14:00:00+01', 8),
    ((SELECT id FROM tastings WHERE name = 'Degustacja klasyczna'), '2026-12-12 14:00:00+01', 8),
    ((SELECT id FROM tastings WHERE name = 'Degustacja Sepremento'), '2026-12-06 11:00:00+01', 6),
    ((SELECT id FROM tastings WHERE name = 'Kolacja w winnicy'), '2026-12-13 18:30:00+01', 12);

INSERT INTO reservations (user_id, session_id, seats, status) VALUES
    ((SELECT id FROM users WHERE email = 'gosc@corvo-bianco.example'),
     (SELECT s.id FROM tasting_sessions s JOIN tastings t ON t.id = s.tasting_id
      WHERE t.name = 'Degustacja klasyczna' AND s.starts_at = '2026-12-05 14:00:00+01'), 2, 'confirmed'),
    ((SELECT id FROM users WHERE email = 'gosc@corvo-bianco.example'),
     (SELECT s.id FROM tasting_sessions s JOIN tastings t ON t.id = s.tasting_id
      WHERE t.name = 'Kolacja w winnicy'), 4, 'cancelled');
