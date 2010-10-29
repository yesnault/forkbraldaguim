#!/bin/bash

for file in bralduns.csv communautes.csv competences.csv distinctions.csv environnements.csv equipements.csv lieux_villes.csv metiers.csv plantes.csv rangs_communautes.csv regions.csv titres.csv villes.csv zones.csv
do
	wget -q -t 2 -N -P csv http://public.braldahim.com/$file
done



# environnements.csv
# CREATE TABLE environnements (nom_environnement TEXT; nom_systeme_environnement TEXT);

# villes.csv
# CREATE TABLE villes (id_ville INT; nom_ville TEXT; est_capitale_ville TEXT; x_min_ville INT; y_min_ville INT; x_max_ville INT; y_max_ville INT; id_region INT; nom_region TEXT);

# zones.csv
# CREATE TABLE zones (id_zone INT; id_fk_environnement_zone INT; nom_systeme_environnement TEXT; x_min_zone INT; x_max_zone INT; y_min_zone INT; y_max_zone INT);