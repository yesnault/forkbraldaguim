<?php

/*
    This file is part of braldaguim.

    braldaguim is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    braldaguim is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with braldaguim.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once(dirname(__FILE__)."/conf.php");

class Fetch {
	
	protected $db;
	
	public function __construct() {
		$this->db = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Impossible de se connecter');
		mysql_select_db(DB_NAME);
		mysql_set_charset('utf8', $this->db);
		mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
		
		//mysql_free_result($res);
	}
	
	public function fetchAllPlayers() {
		$query = "SELECT braldahim_id, restricted_password, last_event FROM ".DB_PREFIX."user;";
		$res = mysql_query($query);
		
		if (! $res) die('Impossible de lancer une requete');
		
		while ($row = mysql_fetch_assoc($res)) {
			if (is_null($row['restricted_password']) || $row['restricted_password'] == 'NULL') {
				continue;
			}
			$url = "http://sp.braldahim.com/scripts/profil/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			#$url = "http://www.guim.info/braldahim/cache/282-201103091200";
			$this->fetch_position($url);
			
			$url = "http://sp.braldahim.com/scripts/vue/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			#$url = "http://www.guim.info/braldahim/toto.php?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
			$this->fetch_vue($url);

			$url = "http://sp.braldahim.com/scripts/competences/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=1";
			#$url =  "http://www.guim.info/braldahim/toto";
			$this->fetch_competence($url);

			$url = "http://sp.braldahim.com/scripts/evenements/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			#$url =  "http://www.guim.info/braldahim/toto";
			$this->fetch_evenements($url, $row['braldahim_id'], $row['last_event']);

			$url = "http://sp.braldahim.com/scripts/equipements/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=1";
			#$url =  "http://www.guim.info/braldahim/toto";
			$this->fetch_equipement($url, $row['braldahim_id']);
			
			$url = "http://sp.braldahim.com/scripts/charrette/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=1";
			#$url =  "http://www.guim.info/braldahim/toto";
			$this->fetch_charrette($url, $row['braldahim_id']);
		}
	}
	
	public function fetchOnePlayer($id) {
		$query = sprintf("SELECT braldahim_id, restricted_password, last_event FROM ".DB_PREFIX."user WHERE braldahim_id=%s;",
			mysql_real_escape_string($id));
		$res = mysql_query($query);
		
		if (! $res) die('Impossible de lancer une requete');
		
		while ($row = mysql_fetch_assoc($res)) {
			if (is_null($row['restricted_password']) || $row['restricted_password'] == 'NULL') {
				continue;
			}
			
			$url = "http://sp.braldahim.com/scripts/profil/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			$this->fetch_position($url);
			
			$url = "http://sp.braldahim.com/scripts/vue/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			$this->fetch_vue($url);

			$url = "http://sp.braldahim.com/scripts/competences/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=1";
			$this->fetch_competence($url);

			$url = "http://sp.braldahim.com/scripts/evenements/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			$this->fetch_evenements($url, $row['braldahim_id'], $row['last_event']);

			$url = "http://sp.braldahim.com/scripts/equipements/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=1";
			#$url =  "http://www.guim.info/braldahim/toto";
			$this->fetch_equipement($url, $row['braldahim_id']);
			
			$url = "http://sp.braldahim.com/scripts/charrette/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=1";
			#$url =  "http://www.guim.info/braldahim/toto";
			$this->fetch_charrette($url, $row['braldahim_id']);
		}
	}
	
	/*
	MAJ de la position du joueur
	Va chercher le contenu de l'url, le traite et le stock en db
	*/
	protected function fetch_position($url) {
		$braldun = array();
		$profil = array();
		$content = file($url);
		if (count($content) == 0) {
			echo "Erreur : le fichier est vide\n";
			return;
		}

		// content[0] = info sur le script
		// content[1] = entete
		// content[2] = valeur
		// en tete : idBraldun;prenom;nom;x;y;z;paRestant;DLA;DureeProchainTour;dateDebutTour;dateFinTour;dateFinLatence;dateDebutCumul;dureeCourantTour;dureeBmTour;PvRestant;bmPVmax;bbdf;nivAgilite;nivForce;nivVigueur;nivSagesse;bmAgilite;bmForce;bmVigueur;bmSagesse;bmBddfAgilite;bmBddfForce;bmBddfVigueur;bmBddfSagesse;bmVue;regeneration;bmRegeneration;pxPerso;pxCommun;pi;niveau;poidsTransportable;poidsTransporte;armureNaturelle;armureEquipement;bmAttaque;bmDegat;bmDefense;nbKo;nbKill;nbKoBraldun;estEngage;estEngageProchainTour;estIntangible;nbPlaquagesSubis;nbPlaquagesEffectues
		//282;Bulrog;Polpeur;-23;18;0;0;2011-03-01 03:11:09;23:20:00;2011-02-28 03:51:09;2011-03-01 03:11:09;2011-02-28 09:41:09;2011-02-28 15:31:09;23:20:00;0;90;0;79;7;6;5;4;0;0;0;0;0;0;0;0;0;2;0;15;0;57;10;15;10.11;4;0;0;0;0;10;14;0;oui;non;non;0;0
		$not_integer = array('prenom', 'nom', 'DLA', 'DureeProchainTour', 'dateDebutTour',
			'dateFinTour', 'dateFinLatence', 'dateDebutCumul', 'dureeCourantTour', 'dureeBmTour');
		$boolean = array('estEngage', 'estEngageProchainTour', 'estIntangible');

		if (preg_match("/^ERREUR-/", $content[0]) == 1) {
			// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
			echo "[".date("YmdHi")."] ".$content[0];
			return;
		}
		$keys = explode(';', trim($content[1]));
		$value = explode(';', trim($content[2]));
		$max = count($keys);
		for ($i=0; $i<$max; $i++) {
			if (in_array($keys[$i], $not_integer)) {
				$profil[strtolower($keys[$i])] = "'".mysql_real_escape_string($value[$i])."'";
			}
			else if (in_array($keys[$i], $boolean)) {
				$profil[strtolower($keys[$i])] = ($value[$i] == 'oui') ? 1 : 0;
			}
			else {
				$profil[strtolower($keys[$i])] = $value[$i];
			}

			if ($keys[$i] == 'idBraldun') {$braldun['idBraldun'] = $value[$i];continue;}
			if ($keys[$i] == 'prenom') {$braldun['prenom'] = $value[$i];continue;}
			if ($keys[$i] == 'nom') {$braldun['nom'] = $value[$i];continue;}
			if ($keys[$i] == 'x') {$braldun['x'] = $value[$i];continue;}
			if ($keys[$i] == 'y') {$braldun['y'] = $value[$i];continue;}
		}
		$this->update_braldun($braldun);
		#file_put_contents('cache/'.$braldun['idBraldun'].'-'.date("YmdHi"), $content);

		// update le profil (table avec plus de details)
		$this->update_profil($profil);
	}

	/*
	Met à jour la db avec le braldun concerné
	*/
	protected function update_braldun($braldun) {
		// on passe le flag 'dirty' à true pour que la génération de la carte ait lieu
		$query = sprintf("UPDATE ".DB_PREFIX."user SET prenom='%s', nom='%s', x=%s, y=%s, updated=true WHERE braldahim_id=%s;",
			mysql_real_escape_string($braldun['prenom']),
			mysql_real_escape_string($braldun['nom']),
			mysql_real_escape_string($braldun['x']),
			mysql_real_escape_string($braldun['y']),
			mysql_real_escape_string($braldun['idBraldun']));
		mysql_query($query);
		$query = "UPDATE ".DB_PREFIX."ressource SET dirty=true;";
		mysql_query($query);
	}

	/*
	Met à jour la db avec le braldun concerné : profil detaillé
	*/
	protected function update_profil($profil) {
		if (! array_key_exists('idbraldun', $profil)) {
			return;
		}
		$query = "SELECT idBraldun FROM ".DB_PREFIX."profil WHERE idBraldun=%s;";
		$query = sprintf($query, mysql_real_escape_string($profil['idbraldun']));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."profil(".
				implode(',', array_keys($profil)).
				", last_update) VALUES(".
				implode(',', $profil).
				", current_timestamp);";
			mysql_query($query);
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."profil SET ";
			$lst = array();
			foreach ($profil as $k => $v) {
				$lst[] = "$k=$v";
			}
			$query .= implode(',', $lst);
			$query .= ",last_update=current_timestamp WHERE idBraldun={$profil['idbraldun']} ;";
			mysql_query($query);
		}
	}

	/*
	MAJ de la vue du joueur
	Va chercher le contenu de l'url, le traite et le stock en db

	POSITION;x;y;z;xMin;xMax;yMin;yMax;idBraldun;vueNbCases;vueBm
	--ENVIRONNEMENT;x;y;z;nom_systeme_environnement;nom_environnement
	CADAVRE;x;y;z; id_monstre;nom_type_monstre;$c_taille
	CHARRETTE;x;y;z; id_charrette;nom_type_materiel
	ECHOPPE;x;y;z;id_echoppe;nom_echoppe;nom_systeme_metier;nom_metier;id_braldun
	--CHAMP;x;y;z;id_champ;id_braldun
	CREVASSE;x;y;z;id_crevasse
	ELEMENT;x;y;z;Peau;quantite_peau_element
	ELEMENT;x;y;z;Cuir;quantite_cuir_element 
	ELEMENT;x;y;z;Fourrure;quantite_fourrure_element
	ELEMENT;x;y;z;Planche;quantite_planche_element
	ELEMENT;x;y;z;Rondin;quantite_rondin_element
	ELEMENT;x;y;z;Castar;quantite_castar_element
	EQUIPEMENT;x;y;z;id_element_equipement;nom;nom_type_qualite;niveau_recette_equipement;suffixe_mot_runique
	MATERIEL;x;y;z;id_element_materiel;nom_type_materiel
	MUNITION;x;y;z;nom_type_munition;nom_pluriel_type_munition;quantite_element_munition
	POTION;x;y;z;id_element_potion;type;nom_type_potion;nom_type_qualite;niveau_potion
	ALIMENT;x;y;z;id_element_aliment;nom_type_aliment;nom_type_qualite
	GRAINE;x;y;z;quantite_element_graine;nom_type_graine
	INGREDIENT;x;y;z;quantite_element_ingredient;nom_type_ingredient
	MINERAI_BRUT;x;y;z;quantite_brut_element_minerai;nom_type_minerai
	LINGOT;x;y;z;quantite_lingots_element_minerai;nom_type_minerai
	PLANTE_BRUTE;x;y;z;quantite_element_partieplante;nom_type_partieplante;nom_type_plante
	PLANTE_PREPAREE;x;y;z;quantite_preparee_element_partieplante;nom_type_partieplante;nom_type_plante
	RUNE;x;y;z;id_rune_element_rune
	TABAC;x;y;z;quantite_feuille_element_tabac;nom_court_type_tabac
	BRALDUN;x;y;z;id_braldun;est_ko_braldun;est_intangible_braldun;est_soule_braldun;soule_camp_braldun;id_fk_soule_match_braldun
	--LIEU;x;y;z;id_lieu;nom_lieu;nom_type_lieu;nom_systeme_type_lieu
	MONSTRE;x;y;z;id_monstre;nom_type_monstre;m_taille;niveau_monstre
	--NID;x;y;z;id_nid;nom_nid_type_monstre
	--PALISSADE;x;y;z;id_palissade;est_destructible_palissade
	--BUISSON;x;y;z;id_buisson;nom_type_buisson
	--BOSQUET;x;y;z;id_bosquet;nom_systeme_type_bosquet
	--ROUTE;x;y;z;id_route;type_route
	BALLON_SOULE;x;y;z;present

	*/
	protected function fetch_vue($url) {
		$vue = array();
		$content = file($url);
		// pour le format voir : http://sp.braldahim.com/
		if (count($content) == 0) {
			echo "Erreur : le fichier est vide\n";
			return;
		}
		
		foreach ($content as $line) {
			if (preg_match("/^ERREUR-/", $line) == 1) {
				// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
				echo "ERREUR:\n$line\n";
				return;
			}
			$part = explode(';', trim($line));
			if ($part[0] == 'ENVIRONNEMENT') {
				$this->update_environnement($part);
				continue;
			}
			if ($part[0] == 'ROUTE') {
				$this->update_route($part);
				continue;
			}
			if ($part[0] == 'PALISSADE') {
				$this->update_palissade($part);
				continue;
			}
			if ($part[0] == 'LIEU') {
				$this->update_lieu($part);
				continue;
			}
			if ($part[0] == 'BOSQUET') {
				$this->update_bosquet($part);
				continue;
			}
			if ($part[0] == 'CHAMP') {
				$this->update_champ($part);
				continue;
			}
			if ($part[0] == 'NID') {
				$this->update_nid($part);
				continue;
			}
			if ($part[0] == 'BUISSON') {
				$this->update_buisson($part);
				continue;
			}
		}
		if (KEEP_SCRIPT_FILE == "yes") {
			file_put_contents('cache/'.date("YmdHi").'-'.uniqid(), $content);
		}
	}
	
	/*
	Efface la case (x,y,z) dans toutes les tables sauf :
	  * dans la table passée en paramètre
	  * les entrées qui ont été mise à jour le jour même
	Le but est d'effacer les entrées qui sont trop vieille et qui
	n'apparaissent plus dans la vue.
	*/
	protected function clean_case($x, $y, $z, $table) {
		$liste_table = array('environnement', 'route', 'palissade', 'bosquet', 'lieu', 'champ', 'nid', 'buisson');
		foreach ($liste_table as $t) {
			if ($t == $table) {
				continue;
			}
			else {
				$query = "DELETE FROM ".DB_PREFIX."%s WHERE x=%s AND y=%s AND z=%s AND last_update != current_date;";
				$query = sprintf($query,
					$t,
					mysql_real_escape_string($x),
					mysql_real_escape_string($y),
					mysql_real_escape_string($z));
				mysql_query($query);
			}
		}
	}
	
	/*
	Insère ou met à jour un element de type environnement
	ENVIRONNEMENT;x;y;z;nom_systeme_environnement;nom_environnement
	*/
	protected function update_environnement($line) {
		$query = "SELECT x FROM ".DB_PREFIX."environnement WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."environnement(x, y, z, nom_systeme_environnement, nom_environnement, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
			$query = sprintf($query,
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]),
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]));
			mysql_query($query);
			
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."environnement SET nom_systeme_environnement='%s', nom_environnement='%s', last_update=current_date ";
			$query .= " WHERE x=%s AND y=%s AND z=%s ;";
			$query = sprintf($query,
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]));
			mysql_query($query);
		}
		$this->clean_case($line[1], $line[2], $line[3], 'environnement');
	}

	/*
	Insère ou met à jour un element de type route
	ROUTE;x;y;z;id_route;type_route
	*/
	protected function update_route($line) {
		$query = "SELECT x FROM ".DB_PREFIX."route WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."route(x, y, z, id_route, type_route, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
			$query = sprintf($query,
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]),
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]));
			mysql_query($query);
			
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."route SET id_route='%s', type_route='%s', last_update=current_date ";
			$query .= " WHERE x=%s AND y=%s AND z=%s ;";
			$query = sprintf($query,
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]));
			mysql_query($query);
		}
		$this->clean_case($line[1], $line[2], $line[3], 'route');
	}

	/*
	Insère ou met à jour un element de type palissade
	PALISSADE;x;y;z;id_palissade;est_destructible_palissade
	*/
	protected function update_palissade($line) {
		$query = "SELECT x FROM ".DB_PREFIX."palissade WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."palissade(x, y, z, id_palissade, est_destructible_palissade, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
			$query = sprintf($query,
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]),
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]));
			mysql_query($query);
			
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."palissade SET id_palissade='%s', est_destructible_palissade='%s', last_update=current_date ";
			$query .= " WHERE x=%s AND y=%s AND z=%s ;";
			$query = sprintf($query,
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]));
			mysql_query($query);
		}
		$this->clean_case($line[1], $line[2], $line[3], 'palissade');
	}

	/*
	Insère ou met à jour un element de type bosquet
	BOSQUET;x;y;z;id_bosquet;nom_systeme_type_bosquet
	*/
	protected function update_bosquet($line) {
		$query = "SELECT x FROM ".DB_PREFIX."bosquet WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."bosquet(x, y, z, id_bosquet, nom_systeme_type_bosquet, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
			$query = sprintf($query,
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]),
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]));
			mysql_query($query);
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."bosquet SET id_bosquet='%s', nom_systeme_type_bosquet='%s', last_update=current_date ";
			$query .= " WHERE x=%s AND y=%s AND z=%s ;";
			$query = sprintf($query,
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]));
			mysql_query($query);
		}
		$this->clean_case($line[1], $line[2], $line[3], 'bosquet');
	}

	/*
	Insère ou met à jour un element de type champs
	CHAMP;x;y;z;id_champ;id_braldun
	*/
	protected function update_champ($line) {
		$query = "SELECT x FROM ".DB_PREFIX."champ WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."champ(x, y, z, id_champ, id_braldun, last_update) VALUES(%s, %s, %s, '%s', %s, current_date);";
			$query = sprintf($query,
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]),
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]));
			mysql_query($query);
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."champ SET id_bosquet='%s', id_braldun=%s, last_update=current_date ";
			$query .= " WHERE x=%s AND y=%s AND z=%s ;";
			$query = sprintf($query,
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]));
			mysql_query($query);
		}
		$this->clean_case($line[1], $line[2], $line[3], 'champ');
	}

	/*
	Insère ou met à jour un element de type LIEU
	LIEU;x;y;z;id_lieu;nom_lieu;nom_type_lieu;nom_systeme_type_lieu
	*/
	protected function update_lieu($line) {
		$query = "SELECT x FROM ".DB_PREFIX."lieu WHERE x=%s AND y=%s AND id_lieu='%s';";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[4]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."lieu(x, y, z, id_lieu, nom_lieu, nom_type_lieu, nom_systeme_type_lieu, last_update) VALUES(%s, %s, %s, '%s', '%s', '%s', '%s', current_date);";
			$query = sprintf($query,
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]),
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[6]),
				mysql_real_escape_string($line[7]));
			mysql_query($query);
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."lieu SET nom_lieu='%s', nom_type_lieu='%s', nom_systeme_type_lieu='%s', last_update=current_date ";
			$query .= "WHERE x=%s AND y=%s AND id_lieu='%s';";
			$query = sprintf($query,
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[6]),
				mysql_real_escape_string($line[7]),
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[4]));
			mysql_query($query);
		}
		$this->clean_case($line[1], $line[2], $line[3], 'lieu');
	}

	/*
	Insère ou met à jour un element de type NID
	NID;x;y;z;id_nid;nom_nid_type_monstre
	*/
	protected function update_nid($line) {
		$query = "SELECT x FROM ".DB_PREFIX."nid WHERE x=%s AND y=%s AND id_nid='%s';";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[4]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."nid(x, y, z, id_nid, nom_nid, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
			$query = sprintf($query,
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]),
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]));
			mysql_query($query);
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."nid SET nom_nid='%s', last_update=current_date ";
			$query .= "WHERE x=%s AND y=%s AND id_nid='%s';";
			$query = sprintf($query,
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[4]));
			mysql_query($query);
		}
		$this->clean_case($line[1], $line[2], $line[3], 'nid');
	}

	/*
	Insère ou met à jour un element de type BUISSON
	BUISSON;x;y;z;id_buisson;nom_type_buisson
	*/
	protected function update_buisson($line) {
		$query = "SELECT x FROM ".DB_PREFIX."buisson WHERE x=%s AND y=%s AND id_buisson='%s';";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[4]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO ".DB_PREFIX."buisson(x, y, z, id_buisson, nom_type_buisson, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
			$query = sprintf($query,
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[3]),
				mysql_real_escape_string($line[4]),
				mysql_real_escape_string($line[5]));
			mysql_query($query);
		}
		else {
			// update
			$query = "UPDATE ".DB_PREFIX."buisson SET nom_type_buisson='%s', last_update=current_date ";
			$query .= "WHERE x=%s AND y=%s AND id_buisson='%s';";
			$query = sprintf($query,
				mysql_real_escape_string($line[5]),
				mysql_real_escape_string($line[1]),
				mysql_real_escape_string($line[2]),
				mysql_real_escape_string($line[4]));
			mysql_query($query);
		}
		$this->clean_case($line[1], $line[2], $line[3], 'buisson');
	}

	/*
	MAJ des competences du joueur
	Va chercher le contenu de l'url, le traite et le stock en db
	*/
	protected function fetch_competence($url) {
		$content = file($url);
		if (count($content) == 0) {
			echo "Erreur : le fichier est vide\n";
			return;
		}
		// content[0] = info sur le script
		// content[1] = entete
		// content[2] = valeur

		//TYPE:statique;NB_APPELS:1;MAX_AUTORISE:14
		//idBraldun;typeCompetence;idCompetence;nom;nom_systeme;maitrise;id_fk_metier_competence
		//282;metier;10;Dépiauter;depiauter;84;2
		//282;metier;11;Débusquer;debusquer;70;2
		//282;commun;17;Identification des runes;identifierrune;25;
		//282;commun;36;Connaissance des monstres;connaissancemonstres;57; 

		if (preg_match("/^ERREUR-/", $content[0]) == 1) {
			// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
			echo "[".date("YmdHi")."] ".$content[0];
			return;
		}
		for ($i=2; $i<count($content); $i++) {
			$line = trim($content[$i]);
			if (strlen($line) == 0) {
				continue;
			}
			$line = explode(';', $line);
			$query = "SELECT idBraldun FROM ".DB_PREFIX."competence WHERE idBraldun=%s AND idCompetence=%s;";
			$query = sprintf($query,
				mysql_real_escape_string($line[0]),
				mysql_real_escape_string($line[2]));
			$res = mysql_query($query);
			if (mysql_num_rows($res) == 0) {
				if (empty($line[6])) {
					$line[6] = 'NULL';
				}
				// insert
				$query = "INSERT INTO ".DB_PREFIX."competence(idBraldun, typeCompetence, idCompetence, nom, nom_systeme, maitrise, idMetier, last_update) VALUES(%s, '%s', %s, '%s', '%s', %s, %s, current_date);";
				$query = sprintf($query,
					mysql_real_escape_string($line[0]),
					mysql_real_escape_string($line[1]),
					mysql_real_escape_string($line[2]),
					mysql_real_escape_string($line[3]),
					mysql_real_escape_string($line[4]),
					mysql_real_escape_string($line[5]),
					mysql_real_escape_string($line[6]));
				mysql_query($query);
			}
			else {
				// update
				$query = "UPDATE ".DB_PREFIX."competence SET maitrise=%s, last_update=current_date ";
				$query .= "WHERE idBraldun=%s AND idCompetence=%s;";
				$query = sprintf($query,
					mysql_real_escape_string($line[5]),
					mysql_real_escape_string($line[0]),
					mysql_real_escape_string($line[2]));
				mysql_query($query);
			}
		}
	}

	/*
	MAJ des evenements du joueur
	Va chercher le contenu de l'url, le traite et le stock en db
	*/
	protected function fetch_evenements($url, $braldun, $last_event) {
		$content = file($url);
		if (count($content) == 0) {
			echo "Erreur : le fichier est vide\n";
			return;
		}
		// content[0] = info sur le script
		// content[1] = entete
		// content[X] = valeur

		//TYPE:dynamique;NB_APPELS:6;MAX_AUTORISE:24
		//idBraldun;idEvenement;type;date;details;detailsbot
		//282;420907;Compétence;2011-04-19 08:52:52;<!-- DEBUT_BRALDUN:282-- FIN_A -->Bulrog Polpeur (282)<!-- FIN --> a réussi l'utilisation d'une compétence sur un <!-- DEBUT_MONSTRE:150623-- FIN_A -->Lièvre (150623)<!-- FIN -->;Vous avez r&eacuteussi votre jet d'action 35 % <br>Vous avez rat&eacute votre jet d'am&eacutelioration : 1 %<br>Vous avez gagn&eacute 1 PX<br>Influence sur la balance de faim : -1 %<br><br>Cela vous a co&ucirct&eacute 1 PA<br>Le monstre Lièvre Petit (150623) a les caract&eacuteristiques suivantes :<br>Niveau : entre 0 et 3<br>Point de vie max : entre 0 et 2<br>Point de vie actuels : entre 0 et 2<br>Vue : 1 case<br>D&eacuteg&acircts : pas plus de 18<br>Attaque : pas plus de 18<br>D&eacutefense : pas plus de 18<br>Sagesse : pas plus de 18<br>Vigueur : pas plus de 18<br>R&eacuteg&eacuten&eacuteration : pas plus de 10<br>Armure : entre 0 et 1<br>Dur&eacutee du tour : entre 11:53:00 et 12:51:00<br><br>Vous ne pouvez pas encore pister ce type de monstre !

		if (preg_match("/^ERREUR-/", $content[0]) == 1) {
			// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
			echo "[".date("YmdHi")."] ".$content[0];
			return;
		}

		$last = null;
		for ($i=2; $i<count($content); $i++) {
			$line = trim($content[$i]);
			if (strlen($line) == 0) {
				continue;
			}
			if (preg_match("/([^;]*);([^;]*);([^;]*);([^;]*);([^;]*);(.*)/", $line, $m) == 1) {
				if ($last == null) {
					$last = $m[2];
				}
				// on ne "rejoue" pas les anciens evt
				if ($last < $last_event) {
					break;
				}
				if ($m[3] == 'Compétence') {
					$this->bestiaireParse($m[6], $m[4]);
				}
			}
		}
		$query = "UPDATE ".DB_PREFIX."user SET last_event='$last' WHERE braldahim_id=$braldun;";
		mysql_query($query);
		if (KEEP_SCRIPT_FILE == "yes") {
			file_put_contents('cache/evt-'.$braldun.'-'.date("YmdHi"), $content);
		}
	}

	/*
	Insère en base le resultat d'une identifiation
	$desc : resultat de l'identification
	$date : date de l'identification (les desc change au cours du temps,
	donc on ne conserve pas les trops vieilles identifications)
	*/
	protected function bestiaireParse($desc, $date) {
		$desc = trim($desc);

		if (is_null($desc) || empty($desc)) {
			return;
		}

		$date = (int)(str_replace('-', '', substr($date, 0, 10)));
		// date de la dernière RAZ des monstres
		if ($date < 20110419) {
			return;
		}
		$lines = explode("<br />", $desc);
		$monstre = array();
		$query_keys = array();
		$query_values = array();
		foreach ($lines as $str) {
			if (preg_match("/Le monstre ([^\(]*) \((\d+)\) a les caract.*/", $str, $match) == 1) {
				$monstre['nom'] = trim($match[1]);
				$monstre['id'] = trim($match[2]);
				$query_keys[] = 'nom, id';
				$query_values[] = "'".mysql_real_escape_string($match[1])."',".$match[2];
				continue;
			}
			if (preg_match("/Niveau : entre (\d+) et (\d+)/", $str, $match) == 1) {
				$monstre['niveau_min'] = trim($match[1]);
				$monstre['niveau_max'] = trim($match[2]);
				$query_keys[] = 'niveau_min, niveau_max';
				$query_values[] = $match[1].", ".$match[2];
				continue;
			}
			if (preg_match("/Point de vie max : entre (\d+) et (\d+)/", $str, $match) == 1) {
				$monstre['pv_max_min'] = trim($match[1]);
				$monstre['pv_max_max'] = trim($match[2]);
				$query_keys[] = 'pv_max_min, pv_max_max';
				$query_values[] = $match[1].", ".$match[2];
				continue;
			}
			if (preg_match("/Vue : (\d+) case/", $str, $match) == 1) {
				$monstre['vue'] = trim($match[1]);
				$query_keys[] = 'vue';
				$query_values[] = $match[1];
				continue;
			}
			if (preg_match("/R.+g.+n.+ration : pas plus de (\d+)/", $str, $match) == 1) {
				$monstre['regeneration'] = trim($match[1]);
				$query_keys[] = 'regeneration';
				$query_values[] = $match[1];
				continue;
			}
			if (preg_match("/D&eacuteg&acircts : pas plus de (\d+)/", $str, $match) == 1) {
				$monstre['degats'] = trim($match[1]);
				$query_keys[] = 'degats';
				$query_values[] = $match[1];
				continue;
			}
			if (preg_match("/Attaque : pas plus de (\d+)/", $str, $match) == 1) {
				$monstre['attaque'] = trim($match[1]);
				$query_keys[] = 'attaque';
				$query_values[] = $match[1];
				continue;
			}
			if (preg_match("/D&eacutefense : pas plus de (\d+)/", $str, $match) == 1) {
				$monstre['defense'] = trim($match[1]);
				$query_keys[] = 'defense';
				$query_values[] = $match[1];
				continue;
			}
			if (preg_match("/Sagesse : pas plus de (\d+)/", $str, $match) == 1) {
				$monstre['sagesse'] = trim($match[1]);
				$query_keys[] = 'sagesse';
				$query_values[] = $match[1];
				continue;
			}
			if (preg_match("/Vigueur : pas plus de (\d+)/", $str, $match) == 1) {
				$monstre['vigueur'] = trim($match[1]);
				$query_keys[] = 'vigueur';
				$query_values[] = $match[1];
				continue;
			}
			if (preg_match("/Armure : entre (\d+) et (\d+)/", $str, $match) == 1) {
				$monstre['armure_min'] = trim($match[1]);
				$monstre['armure_max'] = trim($match[2]);
				$query_keys[] = 'armure_min, armure_max';
				$query_values[] = $match[1].", ".$match[2];
				continue;
			}
		}
		// si on a un nom on insère
		if (in_array('nom, id', $query_keys)) {
			$query = "SELECT id FROM ".DB_PREFIX."bestiaire WHERE id={$monstre['id']};";
			$res = mysql_query($query);
			if (mysql_num_rows($res) == 0) {
				mysql_free_result($res);
				$query = "INSERT INTO ".DB_PREFIX."bestiaire(".
					implode(',', $query_keys).
					", last_update) VALUES(".
					implode(',', $query_values).
					", current_date);";
				mysql_query($query);
			}
		}
	}

	/*
	MAJ des equipement du joueur
	Va chercher le contenu de l'url, le traite et le stock en db
	*/
	protected function fetch_equipement($url, $braldun) {
		$content = file($url);
		if (count($content) == 0) {
			echo "Erreur : le fichier est vide\n";
			return;
		}
		// content[0] = info sur le script
		// content[1] = entete
		// content[X] = valeur

		/* Détails d'une ligne :
		EQUIPEMENT;
		id_equipement;nom;qualite;emplacement;niveau;
		id_type_equipement;id_type_emplacement;
		nom_type_emplacement;nom_systeme_type_emplacement;
		nb_runes;
		armure;force;agilite;vigueur;sagesse;vue;attaque;degat;defense;
		suffixe;
		poids;etat_courant;etat_initial;
		ingredient;nom_systeme_type_ingredient;
		armure_equipement_bonus;
		vernis_bm_armure_equipement_bonus;
		agilite_equipement_bonus;
		vernis_bm_agilite_equipement_bonus;
		force_equipement_bonus;
		vernis_bm_force_equipement_bonus;
		sagesse_equipement_bonus;
		vernis_bm_sagesse_equipement_bonus;
		vigueur_equipement_bonus;
		vernis_bm_vigueur_equipement_bonus;
		vernis_bm_vue_equipement_bonus;
		vernis_bm_attaque_equipement_bonus;
		vernis_bm_degat_equipement_bonus;
		vernis_bm_defense_equipement_bonus;
		id_rune_equipement_rune1;nom_type_rune1;
		id_rune_equipement_rune2;nom_type_rune2;
		id_rune_equipement_rune3;nom_type_rune3;
		id_rune_equipement_rune4;nom_type_rune4;
		id_rune_equipement_rune5;nom_type_rune5;
		id_rune_equipement_rune6;nom_type_rune6;
		ou :
		AUCUN_EQUIPEMENT 
		*/
		if (preg_match("/^ERREUR-/", $content[0]) == 1) {
			// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
			echo "[".date("YmdHi")."] ".$content[0];
			return;
		}

		$keys = explode(";", $content[1]);
		array_shift($keys); //suppr de EQUIPEMNT
		array_pop($keys); // suppr du ; en trop
		$values = array();
		for ($i=2; $i<count($content); $i++) {
			$line = trim($content[$i]);
			if (strlen($line) == 0) {
				continue;
			}
			if (preg_match("/AUCUN_EQUIPEMENT/", $line, $m) !== 1) {
				$tmp = explode(";", $line);
				array_shift($tmp);
				array_pop($tmp);
				array_pop($tmp); // suppr des 2 ; en trop
				array_pop($tmp);
				array_walk($tmp, function (&$str) {$str = mysql_real_escape_string($str);});
				$values[] = $tmp;
			}
		}
		$query = "DELETE FROM ".DB_PREFIX."equipement WHERE braldun=$braldun";
		mysql_query($query);
		foreach ($values as $v) {
			$query = "INSERT INTO ".DB_PREFIX."equipement (braldun,`"
				.join("`,`", $keys)
				."`) VALUES ($braldun,'"
				.join("', '", $v)
				."');";
			mysql_query($query);
		}
		
		if ($content[1] == "AUCUNE_CHARRETTE") {
			return;
		}
	}
	
	/*
	MAJ du contenu de la charrette du joueur
	Va chercher le contenu de l'url, le traite et le stock en db
	*/
	protected function fetch_charrette($url, $braldun) {
		$content = file($url);
		if (count($content) == 0) {
			echo "Erreur : le fichier est vide\n";
			return;
		}
		// content[0] = info sur le script
		// content[X] = valeur

		/*
		CHARRETTE;idCharrette;durabiliteMax;durabiliteActuelle;poidsTransportable;poidsTransporte
		ou : 
		AUCUNE_CHARRETTE
		
		TYPE:statique;NB_APPELS:11;MAX_AUTORISE:14
		CHARRETTE;5;2000;1403;40;4.659
		ELEMENT;Castar;1609
		ALIMENT;14883;Gigot braisé;Standard;21
		ALIMENT;14884;Gigot braisé;Standard;21
		*/
		if (preg_match("/^ERREUR-/", $content[0]) == 1) {
			// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
			echo "[".date("YmdHi")."] ".$content[0];
			return;
		}
		
		$query = "DELETE FROM ".DB_PREFIX."charrette WHERE idBraldun=$braldun";
		mysql_query($query);
		
		if ($content[1] == "AUCUNE_CHARRETTE") {
			return;
		}
		
		for ($l=2; $l<count($content); $l++) {
			$match = null;
			if (preg_match("/([^;]+);(.*)/", $content[$l], $match)) {
				$query = "INSERT INTO ".DB_PREFIX."charrette (idBraldun, objet, contenu)
					VALUES ($braldun, '"
					.$match[1]."', '"
					.mysql_real_escape_string($match[2])."');";
				mysql_query($query);
			}
		}
	}
}
?>
