ALTER TABLE user ADD COLUMN updated TINYINT(1);

CREATE TABLE buisson (
	x MEDIUMINT,
	y MEDIUMINT,
	z MEDIUMINT,
	id_buisson TEXT,
	nom_type_buisson TEXT,
	last_update DATE,
	INDEX(x),
	INDEX(y)
) CHARACTER SET utf8;

DELETE FROM ressource WHERE type='lieu';
DELETE FROM ressource WHERE type='legende';
INSERT INTO ressource(type, dirty) VALUES('nid',1);
INSERT INTO ressource(type, dirty) VALUES('buisson',1);
