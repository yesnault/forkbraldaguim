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
	
	private $db;
	
	public function __construct() {
		$this->db = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Impossible de se connecter');
		mysql_select_db(DB_NAME);
		mysql_set_charset('utf8', $this->db);
		mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
		
		//mysql_free_result($res);
	}
	
	public function fetchAllPlayers() {
		$query = "SELECT braldahim_id, restricted_password FROM user;";
		$res = mysql_query($query);
		
		if (! $res) die('Impossible de lancer une requete');
		
		while ($row = mysql_fetch_assoc($res)) {
			if (is_null($row['restricted_password']) || $row['restricted_password'] == 'NULL') {
				continue;
			}
			$url = "http://sp.braldahim.com/scripts/profil/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			//$url = "http://www.guim.info/braldahim/toto.php?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
			$this->fetch_position($url);
			
			$url = "http://sp.braldahim.com/scripts/vue/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			//$url = "http://www.guim.info/braldahim/toto.php?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
			$this->fetch_vue($url);
		}
	}
	
	public function fetchOnePlayer($id) {
		$query = sprintf("SELECT braldahim_id, restricted_password FROM user WHERE braldahim_id=%s;",
			mysql_real_escape_string($id));
		$res = mysql_query($query);
		
		if (! $res) die('Impossible de lancer une requete');
		
		while ($row = mysql_fetch_assoc($res)) {
			if (is_null($row['restricted_password']) || $row['restricted_password'] == 'NULL') {
				continue;
			}
			$url = "http://sp.braldahim.com/scripts/profil/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=1";
			//$url = "http://www.guim.info/braldahim/toto.php?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
			$this->fetch_position($url);
			
			$url = "http://sp.braldahim.com/scripts/vue/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['restricted_password']}&version=2";
			//$url = "http://www.guim.info/braldahim/toto.php?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
			$this->fetch_vue($url);
		}
	}
	
	/*
	MAJ de la position du joueur
	Va chercher le contenu de l'url, le traite et le stock en db
	*/
	private function fetch_position($url) {
		$braldhun = array();
		$content = file($url);
		// content[0] = info sur le script
		// content[1] = entete
		// content[2] = valeur
		// en tete : idBraldun;prenom;nom;x;y;z;paRestant;DLA;DureeProchainTour;PvRestant;bmPVmax;bbdf;nivAgilite;nivForce;nivVigueur;nivSagesse;bmAgilite;bmForce;bmVigueur;bmSagesse;bmBddfAgilite;bmBddfForce;bmBddfVigueur;bmBddfSagesse;bmVue;regeneration;bmRegeneration;pxPerso;pxCommun;pi;niveau;poidsTransportable;poidsTransporte;armureNaturelle;armureEquipement;bmAttaque;bmDegat;bmDefense;nbKo;nbKill;nbKoBraldun;estEngage;estEngageProchainTour;estIntangible;nbPlaquagesSubis;nbPlaquagesEffectues 
		/* idBraldun;prenom;nom;x;y;z;paRestant;DLA;DureeProchainTour;
			dateDebutTour;dateFinTour;dateFinLatence;
			dateDebutCumul;dureeCourantTour;dureeBmTour;
			PvRestant;bmPVmax;bbdf;
			nivAgilite;nivForce;nivVigueur;nivSagesse;
			bmAgilite;bmForce;bmVigueur;bmSagesse;
			bmBddfAgilite;bmBddfForce;bmBddfVigueur;bmBddfSagesse;
			bmVue;regeneration;bmRegeneration;
			pxPerso;pxCommun;pi;niveau;poidsTransportable;poidsTransporte;armureNaturelle;
			armureEquipement;bmAttaque;bmDegat;bmDefense;nbKo;nbKill;nbKoBraldun;
			estEngage;estEngageProchainTour;estIntangible;nbPlaquagesSubis;nbPlaquagesEffectues
		*/
		
		if (preg_match("/^ERREUR-/", $content[0]) == 1) {
			// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
			echo "[".date("YmdHi")."] ".$content[0];
			return;
		}
		$keys = explode(';', $content[1]);
		$value = explode(';', $content[2]);
		$max = count($keys);
		for ($i=0; $i<$max; $i++) {
			if ($keys[$i] == 'idBraldun') {$braldhun['idBraldun'] = $value[$i];continue;}
			if ($keys[$i] == 'prenom') {$braldhun['prenom'] = $value[$i];continue;}
			if ($keys[$i] == 'nom') {$braldhun['nom'] = $value[$i];continue;}
			if ($keys[$i] == 'x') {$braldhun['x'] = $value[$i];continue;}
			if ($keys[$i] == 'y') {$braldhun['y'] = $value[$i];continue;}
		}
		$this->update_braldhun($braldhun);
		#file_put_contents('cache/'.$braldhun['idBraldun'].'-'.date("YmdHi"), $content);
	}

	/*
	Met à jour la db avec le braldhun concerné
	*/
	private function update_braldhun($braldhun) {
		// on passe le flag 'updated' à true pour que la génération de la carte ait lieu
		$query = sprintf("UPDATE user SET prenom='%s', nom='%s', x=%s, y=%s, updated=true WHERE braldahim_id=%s;",
			mysql_real_escape_string($braldhun['prenom']),
			mysql_real_escape_string($braldhun['nom']),
			mysql_real_escape_string($braldhun['x']),
			mysql_real_escape_string($braldhun['y']),
			mysql_real_escape_string($braldhun['idBraldun']));
		mysql_query($query);
		$query = "UPDATE ressource SET dirty=true;";
		mysql_query($query);
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
	BUISSON;x;y;z;id_buisson;nom_type_buisson
	--BOSQUET;x;y;z;id_bosquet;nom_systeme_type_bosquet
	--ROUTE;x;y;z;id_route;type_route
	BALLON_SOULE;x;y;z;present

	*/
	private function fetch_vue($url) {
		$vue = array();
		$content = file($url);
		// pour le format voir : http://sp.braldahim.com/
		
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
		}
		#file_put_contents('cache/'.date("YmdHi").'-'.uniqid(), $content);
	}
	
	/*
	Efface la case (x,y,z) dans toutes les tables sauf :
	  * dans la table passée en paramètre
	  * les entrées qui ont été mise à jour le jour même
	Le but est d'effacer les entrées qui sont trop vieille et qui
	n'apparaissent plus dans la vue.
	*/
	private function clean_case($x, $y, $z, $table) {
		$liste_table = array('environnement', 'route', 'palissade', 'bosquet', 'lieu', 'champ', 'nid');
		foreach ($liste_table as $t) {
			if ($t == $table) {
				continue;
			}
			else {
				$query = "DELETE FROM %s WHERE x=%s AND y=%s AND z=%s AND last_update != current_date;";
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
	private function update_environnement($line) {
		$query = "SELECT x FROM environnement WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO environnement(x, y, z, nom_systeme_environnement, nom_environnement, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
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
			$query = "UPDATE environnement SET nom_systeme_environnement='%s', nom_environnement='%s', last_update=current_date ";
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
	private function update_route($line) {
		$query = "SELECT x FROM route WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO route(x, y, z, id_route, type_route, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
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
			$query = "UPDATE route SET id_route='%s', type_route='%s', last_update=current_date ";
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
	private function update_palissade($line) {
		$query = "SELECT x FROM palissade WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO palissade(x, y, z, id_palissade, est_destructible_palissade, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
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
			$query = "UPDATE palissade SET id_palissade='%s', est_destructible_palissade='%s', last_update=current_date ";
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
	private function update_bosquet($line) {
		$query = "SELECT x FROM bosquet WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO bosquet(x, y, z, id_bosquet, nom_systeme_type_bosquet, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
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
			$query = "UPDATE bosquet SET id_bosquet='%s', nom_systeme_type_bosquet='%s', last_update=current_date ";
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
	private function update_champ($line) {
		$query = "SELECT x FROM champ WHERE x=%s AND y=%s AND z=%s;";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO champ(x, y, z, id_champ, id_braldun, last_update) VALUES(%s, %s, %s, '%s', %s, current_date);";
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
			$query = "UPDATE champ SET id_bosquet='%s', id_braldun=%s, last_update=current_date ";
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
	private function update_lieu($line) {
		$query = "SELECT x FROM lieu WHERE x=%s AND y=%s AND id_lieu='%s';";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[4]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO lieu(x, y, z, id_lieu, nom_lieu, nom_type_lieu, nom_systeme_type_lieu, last_update) VALUES(%s, %s, %s, '%s', '%s', '%s', '%s', current_date);";
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
			$query = "UPDATE lieu SET nom_lieu='%s', nom_type_lieu='%s', nom_systeme_type_lieu='%s', last_update=current_date ";
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
	LIEU;x;y;z;id_lieu;nom_lieu;nom_type_lieu;nom_systeme_type_lieu
	NID;x;y;z;id_nid;nom_nid_type_monstre
	*/
	private function update_nid($line) {
		$query = "SELECT x FROM nid WHERE x=%s AND y=%s AND id_nid='%s';";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[4]));
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			// insert
			$query = "INSERT INTO nid(x, y, z, id_nid, nom_nid, last_update) VALUES(%s, %s, %s, '%s', '%s', current_date);";
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
			$query = "UPDATE lieu SET nom_nid='%s', last_update=current_date ";
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
}

// si on est en mode CLI, alors on met à jour tous les joueurs
if ( isset($_SERVER['argc']) && $_SERVER['argc'] >= 1 ) {
	$fetch = new Fetch();
	$fetch->fetchAllPlayers();
}
// si on est en mode WEB, on ne met à jour que le joueur demandé
else {
	session_start();
	if (! isset($_SESSION['bra_num'])) {
		echo "not connected";
		exit;
	}
	/*$fetch = new Fetch();
	$fetch->fetchOnePlayer($_SESSION['bra_num']);*/
	//sleep(1);
	echo "ok";
}
?>
