-- Note : pour insérer dans les tables, en raison de l'héritage
--        [!!!][!!!][!!!][!!!][!!!][!!!][!!!]
--        il faut UNIQUEMENT insérer dans les tables enfants qui ont des contraintes bien définies
--        si une insertion se fait sur une table abstrate (_compte, _offre...),
--        il y aura des problèmes de cohérence, de contraintes, de doublons... etc.
--        [!!!][!!!][!!!][!!!][!!!][!!!][!!!]

-- Listing des tables abstraites
--  _compte
--  _professionnel
--  _offre

-- Initialisation du schéma
DROP SCHEMA IF EXISTS "sae_db" CASCADE;

CREATE SCHEMA sae_db;

SET SCHEMA 'sae_db';

-------------------------------------------------------------------------------------------------------- Adresse
-- Table Adresse
CREATE TABLE _adresse ( -- Léo -- Léo
    id_adresse SERIAL PRIMARY KEY,
    code_postal CHAR(5) NOT NULL,
    ville VARCHAR(255) NOT NULL,
    numero VARCHAR(255) NOT NULL,
    odonyme VARCHAR(255) NOT NULL,
    complement VARCHAR(255),
    lat FLOAT NOT NULL DEFAULT 0,
    lng FLOAT NOT NULL DEFAULT 0
);
-------------------------------------------------------------------------------------------------------- Comptes
-- ARCHITECTURE DES TABLES CI-DESSOUS :
-- _compte (abstract)
--     _membre
--     _professionnel (abstract)
--         _pro_prive
--         _pro_public

-- Table abstraite _compte (abstr.)
CREATE TABLE _compte (
    id_compte SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    mdp_hash VARCHAR(255) NOT NULL,
    num_tel VARCHAR(255) NOT NULL,
    id_adresse INTEGER,
    secret_totp VARCHAR(255),
    totp_active BOOLEAN DEFAULT FALSE NOT NULL
);

-- Table _membre
CREATE TABLE _membre (
    pseudo VARCHAR(255) UNIQUE,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL
) INHERITS (_compte);

-- Héritage des types de _compte (abstr.)
CREATE TABLE _professionnel (
    nom_pro VARCHAR(255) NOT NULL,
    CONSTRAINT unique_nom_pro UNIQUE (nom_pro)
) INHERITS (_compte);

CREATE TABLE _pro_public ( -- Antoine -- Antoine
    type_orga VARCHAR(255) NOT NULL
) INHERITS (_professionnel);

-------------------------------------------------------------------------------------------------------- RIB
-- Table RIB
CREATE TABLE _RIB (
    id_rib SERIAL PRIMARY KEY,
    code_banque VARCHAR(255) NOT NULL,
    code_guichet VARCHAR(255) NOT NULL,
    numero_compte VARCHAR(255) NOT NULL,
    cle VARCHAR(255) NOT NULL
);
-------------------------------------------------------------------------------------------------------- TAG

CREATE TABLE _pro_prive ( -- Antoine
    num_siren VARCHAR(255) UNIQUE NOT NULL,
    id_rib INTEGER REFERENCES _rib (id_rib) DEFERRABLE INITIALLY IMMEDIATE
) INHERITS (_professionnel);

-- Rajouter les contraintes principales perdues à cause de l'héritage (clés primaires & étrangères & UNIQUE);
ALTER TABLE _professionnel
ADD CONSTRAINT pk_professionnel PRIMARY KEY (id_compte);

ALTER TABLE _professionnel
ADD CONSTRAINT unique_mail_professionnel UNIQUE (email);

ALTER TABLE _membre ADD CONSTRAINT pk_membre PRIMARY KEY (id_compte);

ALTER TABLE _membre ADD CONSTRAINT unique_mail_membre UNIQUE (email);

ALTER TABLE _membre
ADD CONSTRAINT unique_tel_membre UNIQUE (num_tel);

ALTER TABLE _membre
ADD CONSTRAINT fk_membre FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _pro_public
ADD CONSTRAINT pk_pro_public PRIMARY KEY (id_compte);

ALTER TABLE _pro_public
ADD CONSTRAINT unique_mail_pro_public UNIQUE (email);

ALTER TABLE _pro_public
ADD CONSTRAINT unique_tel_pro_public UNIQUE (num_tel);

ALTER TABLE _pro_public
ADD CONSTRAINT fk_pro_public FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _pro_prive
ADD CONSTRAINT pk_pro_prive PRIMARY KEY (id_compte);

ALTER TABLE _pro_prive
ADD CONSTRAINT unique_mail_pro_prive UNIQUE (email);

ALTER TABLE _pro_prive
ADD CONSTRAINT unique_tel_pro_prive UNIQUE (num_tel);

ALTER TABLE _pro_prive
ADD CONSTRAINT fk_pro_prive_adresse FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _pro_prive
ADD CONSTRAINT fk_pro_prive_rib FOREIGN KEY (id_rib) REFERENCES _rib (id_rib) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- TAG
CREATE TABLE _tag ( -- Antoine
    id_tag SERIAL PRIMARY KEY,
    nom_tag VARCHAR(255) NOT NULL
);

-------------------------------------------------------------------------------------------------------- Offre
-- Table _type_offre (gratuite OU standard OU premium)
-- Antoine
create table _type_offre (
    id_type_offre SERIAL PRIMARY KEY NOT NULL,
    nom VARCHAR(255) NOT NULL,
    prix_ttc FLOAT,
    prix_ht FLOAT
);

-- ARCHITECTURE DES ENFANTS DE _offre :
-- _offre (abstract)
--     _restauration
--     _activite
--     _parc_attraction
--     _spectacle
--     _visite

-- Table globale _offre (abstr.)
CREATE TABLE _offre (
    id_offre SERIAL PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    resume TEXT,
    prix_mini FLOAT,
    date_creation DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour DATE,
    date_suppression DATE,
    est_en_ligne BOOLEAN NOT NULL,
    id_type_offre INTEGER REFERENCES _type_offre (id_type_offre) DEFERRABLE INITIALLY IMMEDIATE,
    id_pro INTEGER,
    id_adresse SERIAL REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE,
    option VARCHAR(50)
);

ALTER TABLE _offre
ADD CONSTRAINT fk_offre_type_offre FOREIGN KEY (id_type_offre) REFERENCES _type_offre (id_type_offre) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _offre
ADD CONSTRAINT fk_offre_adresse FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- Option
CREATE TABLE _option (
    nom VARCHAR(50) PRIMARY KEY NOT NULL, -- A la une ou En relief
    prix_ht FLOAT NOT NULL,
    prix_ttc FLOAT
);

--  ------------------------------------------------------------------------------------------------------ TAGs Offre
-- Maxime
CREATE TABLE _tag_offre (
    id_offre INTEGER,
    id_tag SERIAL REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE,
    PRIMARY KEY (id_offre, id_tag)
);

ALTER TABLE _tag_offre
ADD CONSTRAINT fk_tag FOREIGN KEY (id_tag) REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- Avis

-- Création de la table _avis
CREATE TABLE _avis (
    id_avis SERIAL PRIMARY KEY, -- id unique
    date_publication DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_experience DATE NOT NULL DEFAULT CURRENT_TIMESTAMP, -- date où la personne a visité/mangé/...
    titre VARCHAR(50), -- titre de l'avis
    commentaire VARCHAR(1024), -- commentaire de l'avis
    note FLOAT,
    contexte_passage VARCHAR(255) NOT NULL,
    id_membre INT NOT NULL, -- compte de l'utilisateur  |
    id_offre INT NOT NULL, -- Offre à laquelle est lié l'avis
    reponse TEXT DEFAULT NULL,
    est_lu BOOLEAN NOT NULL DEFAULT FALSE,
    fin_blacklistage TIMESTAMP DEFAULT NULL,
    -- Contrainte avis_unique_par_offre définie dans fonctions.sql
);

-------------------------------------------------------------------------------------------------------- Facture
CREATE TABLE _facture (
    numero_facture VARCHAR(255) PRIMARY KEY,
    id_offre integer NOT NULL,
    date_emission date NOT NULL DEFAULT CURRENT_DATE,
    date_echeance date NOT NULL DEFAULT (DATE_TRUNC('MONTH', CURRENT_DATE) + INTERVAL '1 MONTH')
);

-------------------------------------------------------------------------------------------------------- Ligne_facture pour les dates de mise en ligne
CREATE TABLE _ligne_facture_en_ligne (
    type_offre VARCHAR(255) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    quantite INT NOT NULL, -- Nb de jours
    unite VARCHAR(255) NOT NULL DEFAULT 'jour', -- jour
    prix_unitaire_ht DECIMAL(5, 2) NOT NULL,
    prix_total_ht DECIMAL(5, 2) GENERATED ALWAYS AS (ROUND((prix_unitaire_ht * quantite)::NUMERIC, 2)) STORED, -- Prix total calculé automatiquement
    tva DECIMAL(5, 2) NOT NULL GENERATED ALWAYS AS (ROUND((prix_unitaire_ttc / prix_unitaire_ht)::NUMERIC - 1, 2)*100) STORED,
    prix_unitaire_ttc DECIMAL(5, 2) NOT NULL,
    prix_total_ttc DECIMAL(5, 2) GENERATED ALWAYS AS (ROUND((prix_unitaire_ttc * quantite)::NUMERIC, 2)) STORED,
    numero_facture VARCHAR(255) NOT NULL REFERENCES _facture(numero_facture)
);

-------------------------------------------------------------------------------------------------------- Ligne_facture pour les options d'une offre
CREATE TABLE _ligne_facture_option (
    nom_option VARCHAR(255) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    quantite INT NOT NULL, -- Nb de semaines
    unite VARCHAR(255) NOT NULL DEFAULT 'semaine', -- semaine
    prix_unitaire_ht DECIMAL(5, 2) NOT NULL,
    prix_total_ht DECIMAL(5, 2) GENERATED ALWAYS AS (ROUND((prix_unitaire_ht * quantite)::NUMERIC, 2)) STORED, -- Prix total calculé automatiquement
    tva DECIMAL(5, 2) NOT NULL GENERATED ALWAYS AS (ROUND((prix_unitaire_ttc / prix_unitaire_ht)::NUMERIC - 1, 2)*100) STORED,
    prix_unitaire_ttc DECIMAL(5, 2) NOT NULL,
    prix_total_ttc DECIMAL(5, 2) GENERATED ALWAYS AS (ROUND((prix_unitaire_ttc * quantite)::NUMERIC, 2)) STORED,
    numero_facture VARCHAR(255) NOT NULL REFERENCES _facture(numero_facture)
);

-------------------------------------------------------------------------------------------------------- Logs
CREATE TABLE _log_changement_status ( -- Maxime
    id SERIAL PRIMARY KEY,
    id_offre INTEGER NOT NULL,
    date_changement DATE NOT NULL
);

-------------------------------------------------------------------------------------------------------- Restaurants
-- Type de repas 'petit dej' 'diner' etc...
create table _type_repas ( -- Baptiste
    id_type_repas SERIAL PRIMARY KEY,
    nom VARCHAR(255) NOT NULL UNIQUE
);

-- Table _restauration (hérite _offre)
-- (MVC) Léo
CREATE TABLE _restauration (
    gamme_prix VARCHAR(3) NOT NULL
) INHERITS (_offre);

-- Rajout des contraintes perdues pour _restauration à cause de l'héritage
ALTER TABLE _restauration
ADD CONSTRAINT pk_restauration PRIMARY KEY (id_offre);

ALTER TABLE _restauration
ADD CONSTRAINT fk_restauration_adresse FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _restauration
ADD CONSTRAINT fk_restauration_type_offre FOREIGN KEY (id_type_offre) REFERENCES _type_offre (id_type_offre) DEFERRABLE INITIALLY IMMEDIATE;

-- Lien entre restauration et type_repas
create table _restaurant_type_repas ( -- Baptiste
    id_offre SERIAL REFERENCES _restauration (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE,
    id_type_repas SERIAL REFERENCES _type_repas (id_type_repas) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE,
    PRIMARY KEY (id_offre, id_type_repas)
);

ALTER TABLE _restaurant_type_repas
ADD CONSTRAINT fk_restaurant_type_repas_offre FOREIGN KEY (id_offre) REFERENCES _restauration (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _restaurant_type_repas
ADD CONSTRAINT fk_restaurant_type_repas_type FOREIGN KEY (id_type_repas) REFERENCES _type_repas (id_type_repas) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;

-- Type de restaurant : gastronomie, kebab, etc..
create table _tag_restaurant (
    -- Maxime
    id_tag_restaurant SERIAL PRIMARY KEY,
    nom_tag VARCHAR(255) NOT NULL
);

-- table 1 restaurant <-> 1..* tag
-- Maxime
create table _tag_restaurant_restauration (
    id_offre SERIAL REFERENCES _restauration (id_offre) DEFERRABLE INITIALLY IMMEDIATE,
    id_tag_restaurant SERIAL REFERENCES _tag_restaurant (id_tag_restaurant) DEFERRABLE INITIALLY IMMEDIATE,
    PRIMARY KEY (id_offre, id_tag_restaurant)
);

ALTER TABLE _tag_restaurant_restauration
ADD CONSTRAINT fk_tag_restaurant_restauration_offre FOREIGN KEY (id_offre) REFERENCES _restauration (id_offre) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _tag_restaurant_restauration
ADD CONSTRAINT fk_tag_restaurant_restauration_tag FOREIGN KEY (id_tag_restaurant) REFERENCES _tag_restaurant (id_tag_restaurant) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- Activités
-- Table _activite (hérite de _offre)
-- (MVC) Léo
CREATE TABLE _activite (
    duree_activite TIME,
    age_requis INTEGER,
    prestations VARCHAR(255)
) INHERITS (_offre);

-- Rajout des contraintes perdues pour _activite à cause de l'héritage
ALTER TABLE _activite
ADD CONSTRAINT pk_activite PRIMARY KEY (id_offre);

ALTER TABLE _activite
ADD CONSTRAINT fk_activite_adresse FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _activite
ADD CONSTRAINT fk_activite_type_offre FOREIGN KEY (id_type_offre) REFERENCES _type_offre (id_type_offre) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- TAG Activité
create table _tag_activite ( -- Maxime
    id_offre SERIAL REFERENCES _activite (id_offre) DEFERRABLE INITIALLY IMMEDIATE,
    id_tag SERIAL REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE,
    PRIMARY KEY (id_offre, id_tag)
);

ALTER TABLE _tag_activite
ADD CONSTRAINT fk_tag_activite_offre FOREIGN KEY (id_offre) REFERENCES _activite (id_offre) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _tag_activite
ADD CONSTRAINT fk_tag_activite_tag FOREIGN KEY (id_tag) REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- Spectacles
-- Table _spectacle (hérite de _offre)
CREATE TABLE _spectacle (capacite INTEGER, duree TIME) INHERITS (_offre);
-- Rajout des contraintes perdues pour _spectacle à cause de l'héritage
ALTER TABLE _spectacle
ADD CONSTRAINT pk_spectacle PRIMARY KEY (id_offre);

ALTER TABLE _spectacle
ADD CONSTRAINT fk_spectacle_adresse FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _spectacle
ADD CONSTRAINT fk_spectacle_type_offre FOREIGN KEY (id_type_offre) REFERENCES _type_offre (id_type_offre) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- TAG Spectacles
create table _tag_spectacle ( -- Maxime
    id_offre SERIAL REFERENCES _spectacle (id_offre) DEFERRABLE INITIALLY IMMEDIATE,
    id_tag SERIAL REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE,
    PRIMARY KEY (id_offre, id_tag)
);

ALTER TABLE _tag_spectacle
ADD CONSTRAINT fk_tag_spectacle_offre FOREIGN KEY (id_offre) REFERENCES _spectacle (id_offre) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _tag_spectacle
ADD CONSTRAINT fk_tag_spectacle_tag FOREIGN KEY (id_tag) REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- Visites
-- Table _visite (hérite de _offre)
-- (MVC) Léo
CREATE TABLE _visite (
    duree TIME,
    avec_guide BOOLEAN
) INHERITS (_offre);

-- Rajout des contraintes perdues pour _visite à cause de l'héritage
ALTER TABLE _visite ADD CONSTRAINT pk_visite PRIMARY KEY (id_offre);

ALTER TABLE _visite
ADD CONSTRAINT fk_visite_adresse FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _visite
ADD CONSTRAINT fk_visite_type_offre FOREIGN KEY (id_type_offre) REFERENCES _type_offre (id_type_offre) DEFERRABLE INITIALLY IMMEDIATE;

-- langues parlées durant la visite
CREATE TABLE _langue ( -- Antoine
    id_langue SERIAL PRIMARY KEY,
    nom VARCHAR(255)
);

-- Table de lien pour les langues parlées durant les visites
CREATE TABLE _visite_langue ( -- Antoine
    id_offre SERIAL REFERENCES _visite (id_offre) DEFERRABLE INITIALLY IMMEDIATE,
    id_langue SERIAL REFERENCES _langue (id_langue) DEFERRABLE INITIALLY IMMEDIATE
);

ALTER TABLE _visite_langue
ADD CONSTRAINT fk_visite_langue_offre FOREIGN KEY (id_offre) REFERENCES _visite (id_offre) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _visite_langue
ADD CONSTRAINT fk_visite_langue_langue FOREIGN KEY (id_langue) REFERENCES _langue (id_langue) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- TAG Visites
create table _tag_visite ( -- Maxime
    id_offre SERIAL REFERENCES _visite (id_offre) DEFERRABLE INITIALLY IMMEDIATE,
    id_tag SERIAL REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE,
    PRIMARY KEY (id_offre, id_tag)
);

ALTER TABLE _tag_visite
ADD CONSTRAINT fk_tag_visite_offre FOREIGN KEY (id_offre) REFERENCES _visite (id_offre) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _tag_visite
ADD CONSTRAINT fk_tag_visite_tag FOREIGN KEY (id_tag) REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- Parcs d'attractions
-- Table _parc_attraction (hérite de _offre)
CREATE TABLE _parc_attraction ( -- (MVC) Léo
    nb_attractions INTEGER,
    age_requis INTEGER
) INHERITS (_offre);

-- Rajout des contraintes perdues pour _parc_attraction à cause de l'héritage
ALTER TABLE _parc_attraction
ADD CONSTRAINT pk_parc_attraction PRIMARY KEY (id_offre);

ALTER TABLE _parc_attraction
ADD CONSTRAINT fk_parc_attraction_adresse FOREIGN KEY (id_adresse) REFERENCES _adresse (id_adresse) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _parc_attraction
ADD CONSTRAINT fk_parc_attraction_type_offre FOREIGN KEY (id_type_offre) REFERENCES _type_offre (id_type_offre) DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- TAG Parcs
create table _tag_parc_attraction ( -- Maxime
    id_offre SERIAL REFERENCES _parc_attraction (id_offre) DEFERRABLE INITIALLY IMMEDIATE,
    id_tag SERIAL REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE,
    PRIMARY KEY (id_offre, id_tag)
);

ALTER TABLE _tag_parc_attraction
ADD CONSTRAINT fk_tag_parc_attraction_offre FOREIGN KEY (id_offre) REFERENCES _parc_attraction (id_offre) DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _tag_parc_attraction
ADD CONSTRAINT fk_tag_parc_attraction_tag FOREIGN KEY (id_tag) REFERENCES _tag (id_tag) DEFERRABLE INITIALLY IMMEDIATE;

--------------------------------------------------------------------------------------------------------
-- Table Horaire
CREATE TABLE _horaire ( -- Antoine
    id_horaire SERIAL PRIMARY KEY,
    jour VARCHAR(8) NOT NULL,
    ouverture TIME NOT NULL,
    fermeture TIME NOT NULL,
    pause_debut TIME,
    pause_fin TIME,
    id_offre INTEGER NOT NULL
);

-------------------------------------------------------------------------------------------------------- Tarif Publique
-- Table TARIF public
CREATE TABLE _tarif_public ( -- Baptiste
    id_tarif SERIAL PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    prix INTEGER,
    id_offre INTEGER NOT NULL
);

-------------------------------------------------------------------------------------------------------- Table ternaire restauration avis et note détaillée
CREATE TABLE _avis_restauration_note (
    id_avis INT REFERENCES _avis (id_avis) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE,
    id_restauration INT REFERENCES _restauration (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE,
    note_ambiance FLOAT,
    note_service FLOAT,
    note_cuisine FLOAT,
    rapport_qualite_prix FLOAT,
    PRIMARY KEY (id_avis, id_restauration)
);

ALTER TABLE _avis_restauration_note
ADD CONSTRAINT fk_avis_restauration_note_avis FOREIGN KEY (id_avis) REFERENCES _avis (id_avis) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE _avis_restauration_note
ADD CONSTRAINT fk_avis_restauration_note_restauration FOREIGN KEY (id_restauration) REFERENCES _restauration (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- Prestations
CREATE TABLE _prestation ( -- Prestations des activités
    id_prestation SERIAL PRIMARY KEY,
    id_offre INT,
    nom VARCHAR(50) NOT NULL,
    inclus BOOLEAN
);

ALTER TABLE _prestation
ADD CONSTRAINT fk_prestation_activite FOREIGN KEY (id_offre) REFERENCES _activite (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;

-------------------------------------------------------------------------------------------------------- Liaison prestation et activité
CREATE TABLE _activite_prestation (
    id_activite INTEGER NOT NULL REFERENCES _activite (id_offre),
    id_prestation INTEGER NOT NULL REFERENCES _prestation (id_prestation),
    PRIMARY KEY (id_activite, id_prestation)
);

-------------------------------------------------------------------------------------------------------- Images
CREATE TABLE t_image_img (
    -- IMG = IMaGe
    img_path VARCHAR(255) PRIMARY KEY,
    img_date_creation DATE NOT NULL,
    img_description TEXT,
    img_date_suppression DATE,
    id_offre INTEGER REFERENCES _offre (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE,
    id_parc INTEGER REFERENCES _parc_attraction (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE,
    -- Contrainte d'exclusivité : soit offre_id, soit id_parc doit être non nul, mais pas les deux
    CONSTRAINT chk_offre_parc_exclusif CHECK (
        (
            id_offre IS NOT NULL
            AND id_parc IS NULL
        )
        OR (
            id_offre IS NULL
            AND id_parc IS NOT NULL
        )
    )
);

ALTER TABLE T_Image_Img
ADD CONSTRAINT fk_image_offre FOREIGN KEY (id_offre) REFERENCES _offre (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE T_Image_Img
ADD CONSTRAINT fk_image_parc FOREIGN KEY (id_parc) REFERENCES _parc_attraction (id_offre) ON DELETE CASCADE DEFERRABLE INITIALLY IMMEDIATE;

------------------------------------------------------------------------------------------------------- Historique des périodes en ligne pour chaque offre
-- Les date_debut est la date actuelle par défaut, si aucune valeur n'est donnée
create table _periodes_en_ligne (
    id_offre INT NOT NULL,
    type_offre VARCHAR(255), -- Pas de référence, si les types changent plus tard...
    prix_ht DECIMAL(5, 2) NOT NULL, -- Prix HT du type de l'offre pour 1 jour
    prix_ttc DECIMAL(5, 2) NOT NULL,
    date_debut DATE NOT NULL DEFAULT CURRENT_DATE,
    date_fin DATE DEFAULT NULL
);

-------------------------------------------------------------------------------------------------------- Souscription à certaines options
CREATE TABLE _souscription (
    id_souscription SERIAL PRIMARY KEY,
    id_offre INTEGER NOT NULL,
    nom_option VARCHAR(50) NOT NULL,
    prix_ht DECIMAL(5, 2) NOT NULL, -- Prix HT du type de l'offre pour 1 jour
    prix_ttc DECIMAL(5, 2) NOT NULL,
    tva DECIMAL(5, 2) NOT NULL GENERATED ALWAYS AS (ROUND((prix_ttc / prix_ht)::NUMERIC - 1, 2)*100) STORED,
    date_association DATE NOT NULL DEFAULT CURRENT_DATE,
    date_lancement DATE NOT NULL,
    date_annulation DATE DEFAULT NULL,
    CONSTRAINT check_lundi_lancement CHECK (
        EXTRACT(
            DOW
            FROM date_lancement
        ) = 1
    ),
    nb_semaines INTEGER NOT NULL
);
