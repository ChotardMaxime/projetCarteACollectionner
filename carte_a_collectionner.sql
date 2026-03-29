-- =========================================
-- TABLE : utilisateur
-- =========================================
CREATE TABLE IF NOT EXISTS utilisateur (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nom_profil    VARCHAR(100)  NOT NULL,
    mot_de_passe  VARCHAR(255)  NOT NULL,
    role          VARCHAR(50)   NOT NULL DEFAULT 'utilisateur',
    date_creation DATE          NOT NULL DEFAULT (CURRENT_DATE),
    photo_url     VARCHAR(255)  DEFAULT NULL
);

-- =========================================
-- TABLE : carte
-- =========================================
CREATE TABLE IF NOT EXISTS carte (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nom       VARCHAR(100)                                        NOT NULL,
    valeur    DECIMAL(10,2)                                       NOT NULL DEFAULT 0,
    image_url VARCHAR(255)                                        DEFAULT NULL,
    rarete    ENUM('commune','peu commune','rare','ultra rare')   NOT NULL DEFAULT 'commune'
);

-- =========================================
-- TABLE : collection (table de liaison)
-- =========================================
CREATE TABLE IF NOT EXISTS inventaire (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur   INT  NOT NULL,
    id_carte         INT  NOT NULL,
    date_acquisition DATE NOT NULL DEFAULT (CURRENT_DATE),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (id_carte)       REFERENCES carte(id)       ON DELETE CASCADE
) ;

-- =========================================
-- DONNEES DE TEST
-- =========================================

-- Utilisateurs (mot de passe : "050127" hashé avec bcrypt)
INSERT INTO utilisateur (nom_profil, mot_de_passe, role, date_creation) VALUES
    ('Max',      '$2b$12$ddPvGF/664cvbPUfdBYckursSsWq2bY/77ISdsZpVaZ049I6169TC', 'admin',        '2024-01-15'),
    ('Dresseur', '$2b$12$zYEKmlH.cM3WImojelw5LuNn6ZnbWUKar8goHpRCpH5.iTpTHX09y', 'utilisateur', '2024-01-15');

-- Les 12 cartes Pokémon
INSERT INTO carte (nom, valeur, image_url, rarete) VALUES
    ('Dracaufeu',   350.00, 'img/dracaufeu.png',   'ultra rare'),
    ('Mewtwo',      220.00, 'img/mewtwo.png',       'ultra rare'),
    ('Pikachu',     180.00, 'img/pikachu.png',      'rare'),
    ('Lugia',       290.00, 'img/lugia.png',        'ultra rare'),
    ('Florizarre',   45.00, 'img/florizarre.png',   'commune'),
    ('Évoli',        75.00, 'img/evoli.png',         'peu commune'),
    ('Raichu',       60.00, 'img/raichu.png',        'peu commune'),
    ('Rayquaza',    310.00, 'img/rayquaza.png',      'ultra rare'),
    ('Tortank',     130.00, 'img/tortank.png',       'rare'),
    ('Ectoplasma',   95.00, 'img/ectoplasma.png',   'peu commune'),
    ('Dracolosse',  110.00, 'img/dracolosse.png',   'rare'),
    ('Mew',         400.00, 'img/mew.png',           'ultra rare'),
    ('Tortank',     120.00, 'img/tortank.png',     'rare'),
    ('Rayquaza',    450.00, 'img/rayquaza.png',    'ultra rare'),
    ('Mew',         310.00, 'img/mew.png',         'ultra rare'),
    ('Ectoplasma',   95.00, 'img/ectoplasma.png',  'rare'),
    ('Dracolosse',  110.00, 'img/dracolosse.png',  'rare'),
    ('Leviator',     55.00, 'img/leviator.png',    'peu commune'),
    ('Ronflex',      40.00, 'img/ronflex.png',     'peu commune'),
    ('Lucario',      65.00, 'img/lucario.png',     'rare'),
    ('Magicarpe',     5.00, 'img/magicarpe.png',   'commune'),
    ('Groudon',     280.00, 'img/groudon.png',     'ultra rare'),
    ('Kyogre',      275.00, 'img/kyogre.png',      'ultra rare'),
    ('Togepi',       15.00, 'img/togepi.png',      'commune'),
    ('Arcanin',      35.00, 'img/arcanin.png',     'peu commune'),
    ('Alakazam',     85.00, 'img/alakazam.png',    'rare');

-- L'utilisateur 1 possède 3 cartes
INSERT INTO inventaire (id_utilisateur, id_carte, date_acquisition) VALUES
    (2, 3, '2024-02-10'),  -- Pikachu
    (2, 4, '2024-03-05'),  -- Lugia
    (2, 9, '2024-04-20');  -- Tortank