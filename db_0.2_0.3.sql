ALTER TABLE profil MODIFY last_update TIMESTAMP DEFAULT 0;
CREATE TABLE nid (
	x MEDIUMINT,
	y MEDIUMINT,
	z MEDIUMINT,
	id_nid TEXT,
	nom_nid TEXT,
	last_update DATE
);
