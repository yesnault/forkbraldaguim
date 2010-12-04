CREATE TABLE user(
	braldahim_id MEDIUMINT PRIMARY KEY,
	crypted_password VARCHAR(32),
	prenom TEXT,
	nom TEXT,
	x MEDIUMINT,
	y MEDIUMINT
);

CREATE TABLE carte (
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT, type TEXT,
	id TEXT,
	last_update DATE,
	INDEX (x,y)
);

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
);

CREATE TABLE environnement(
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	nom_systeme_environnement TEXT,
	nom_environnement TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
);

CREATE TABLE palissade(
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	id_palissade TEXT,
	est_destructible_palissade TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
);

CREATE TABLE route(
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	id_route TEXT,
	type_route TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
);

CREATE TABLE bosquet(
	x MEDIUMINT not null,
	y MEDIUMINT not null,
	z MEDIUMINT,
	id_bosquet TEXT,
	nom_systeme_type_bosquet TEXT,
	last_update DATE,
	INDEX (x),
	INDEX (y)
);

CREATE TABLE ressource(
	type VARCHAR(255) PRIMARY KEY,
	dirty BOOLEAN
);

INSERT INTO ressource(type, dirty) VALUES('fond', false);
INSERT INTO ressource(type, dirty) VALUES('joueur', false);
INSERT INTO ressource(type, dirty) VALUES('lieu', false);
INSERT INTO ressource(type, dirty) VALUES('lieumythique', false);
INSERT INTO ressource(type, dirty) VALUES('lieustandard', false);
INSERT INTO ressource(type, dirty) VALUES('legende', false);
