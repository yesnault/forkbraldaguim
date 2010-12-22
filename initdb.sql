CREATE TABLE user(
	braldahim_id MEDIUMINT PRIMARY KEY,
	crypted_password VARCHAR(32),
	prenom TEXT,
	nom TEXT,
	x MEDIUMINT,
	y MEDIUMINT
) CHARACTER SET utf8;

CREATE TABLE carte (
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT, type TEXT,
	id TEXT,
	last_update DATE,
	INDEX (x,y)
) CHARACTER SET utf8;

CREATE TABLE lieu(x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	id_lieu TEXT,
	nom_lieu TEXT,
	nom_type_lieu TEXT,
	nom_systeme_type_lieu TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
) CHARACTER SET utf8;

CREATE TABLE environnement(
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	nom_systeme_environnement TEXT,
	nom_environnement TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
) CHARACTER SET utf8;

CREATE TABLE palissade(
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	id_palissade TEXT,
	est_destructible_palissade TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
) CHARACTER SET utf8;

CREATE TABLE route(
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	id_route TEXT,
	type_route TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
) CHARACTER SET utf8;

CREATE TABLE bosquet(
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	id_bosquet TEXT,
	nom_systeme_type_bosquet TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
) CHARACTER SET utf8;

CREATE TABLE ressource(
	type VARCHAR(255) PRIMARY KEY,
	dirty BOOLEAN
) CHARACTER SET utf8;

INSERT INTO ressource(type, dirty) VALUES('fond', false);
INSERT INTO ressource(type, dirty) VALUES('joueur', false);
INSERT INTO ressource(type, dirty) VALUES('lieu', false);
INSERT INTO ressource(type, dirty) VALUES('lieumythique', false);
INSERT INTO ressource(type, dirty) VALUES('lieustandard', false);
INSERT INTO ressource(type, dirty) VALUES('legende', false);

CREATE TABLE zone(
	id_zone MEDIUMINT not null,
	id_fk_environnement_zone MEDIUMINT not null,
	nom_systeme_environnement TEXT,
	x_min_zone MEDIUMINT,
	x_max_zone MEDIUMINT,
	y_min_zone MEDIUMINT,
	y_max_zone MEDIUMINT,
	INDEX (x_min_zone),
	INDEX (x_max_zone),
	INDEX (y_min_zone),
	INDEX (y_max_zone)
) CHARACTER SET utf8;

CREATE TABLE ville(
	id_ville MEDIUMINT not null,
	nom_ville TEXT,
	est_capitale_ville TEXT,
	x_min_ville MEDIUMINT,
	y_min_ville MEDIUMINT,
	x_max_ville MEDIUMINT,
	y_max_ville MEDIUMINT,
	id_region MEDIUMINT,
	nom_region TEXT,
	INDEX(id_ville)
) CHARACTER SET utf8;