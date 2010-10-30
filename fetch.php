<?php
/*
MAJ de la position du joueur
Va chercher le contenu de l'url, le traite et le stock en db
*/
function fetch_position($url) {
	$braldhun = array();
	$content = file($url);
	// content[0] = info sur le script
	// content[1] = entete
	// content[2] = valeur
	// en tete : idBraldun;prenom;nom;x;y;z;paRestant;DLA;DureeProchainTour;PvRestant;bmPVmax;bbdf;nivAgilite;nivForce;nivVigueur;nivSagesse;bmAgilite;bmForce;bmVigueur;bmSagesse;bmBddfAgilite;bmBddfForce;bmBddfVigueur;bmBddfSagesse;bmVue;regeneration;bmRegeneration;pxPerso;pxCommun;pi;niveau;poidsTransportable;poidsTransporte;armureNaturelle;armureEquipement;bmAttaque;bmDegat;bmDefense;nbKo;nbKill;nbKoBraldun;estEngage;estEngageProchainTour;estIntangible;nbPlaquagesSubis;nbPlaquagesEffectues 
	
	if (preg_match("/^ERREUR-/", $content[0]) == 1) {
		// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
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
	update_braldhun($braldhun);
}

/*
Met à jour la db avec le braldhun concerné
*/
function update_braldhun($braldhun) {
	$query = sprintf("UPDATE user SET prenom='%s', nom='%s', x=%s, y=%s WHERE braldahim_id=%s;",
		mysql_real_escape_string($braldhun['prenom']),
		mysql_real_escape_string($braldhun['nom']),
		mysql_real_escape_string($braldhun['x']),
		mysql_real_escape_string($braldhun['y']),
		mysql_real_escape_string($braldhun['idBraldun']));
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
CHAMP;x;y;z;id_champ;id_braldun
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
LIEU;x;y;z;id_lieu;nom_lieu;nom_type_lieu;nom_systeme_type_lieu
MONSTRE;x;y;z;id_monstre;nom_type_monstre;m_taille;niveau_monstre
NID;x;y;z;id_nid;nom_nid_type_monstre
--PALISSADE;x;y;z;id_palissade;est_destructible_palissade
BUISSON;x;y;z;id_buisson;nom_type_buisson
BOSQUET;x;y;z;id_bosquet;nom_systeme_type_bosquet
--ROUTE;x;y;z;id_route;type_route
BALLON_SOULE;x;y;z;present

*/
function fetch_vue($url) {
	$vue = array();
	$content = file($url);
	// pour le format voir : http://sp.braldahim.com/
	
	foreach ($content as $line) {
		if (preg_match("/^ERREUR-/", $line) == 1) {
			// erreur lors de l'appel du script (cf : http://sp.braldahim.com/)
			return;
		}
		$part = explode(';', trim($line));
		if ($part[0] == 'ENVIRONNEMENT') {
			update_environnement($part);
			continue;
		}
		if ($part[0] == 'ROUTE') {
			update_route($part);
			continue;
		}
		if ($part[0] == 'PALISSADE') {
			update_palissade($part);
			continue;
		}
	}
}

/*
Insère ou met à jour un element de type environnement
ENVIRONNEMENT;x;y;z;nom_systeme_environnement;nom_environnement
*/
function update_environnement($line) {
	$query = "SELECT x FROM carte WHERE x=%s AND y=%s AND z=%s AND type='ENVIRONNEMENT';";
	$query = sprintf($query,
		mysql_real_escape_string($line[1]),
		mysql_real_escape_string($line[2]),
		mysql_real_escape_string($line[3]));
	$res = mysql_query($query);
	if (mysql_num_rows($res) == 0) {
		// insert
		$query = "INSERT INTO carte(x, y, z, type, id) VALUES(%s, %s, %s, 'ENVIRONNEMENT', '%s');";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]),
			mysql_real_escape_string($line[4]));
		mysql_query($query);
		
	}
	else {
		// update
		$query = "UPDATE carte SET x=%s, y=%s, z=%s, id='%s' ";
		$query .= " WHERE x=%s AND y=%s AND z=%s AND type='ENVIRONNEMENT';";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]),
			mysql_real_escape_string($line[5]),
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		mysql_query($query);
	}
}

/*
Insère ou met à jour un element de type route
ROUTE;x;y;z;id_route;type_route
*/
function update_route($line) {
	$query = "SELECT x FROM carte WHERE x=%s AND y=%s AND z=%s AND type='ROUTE';";
	$query = sprintf($query,
		mysql_real_escape_string($line[1]),
		mysql_real_escape_string($line[2]),
		mysql_real_escape_string($line[3]));
	$res = mysql_query($query);
	if (mysql_num_rows($res) == 0) {
		// insert
		$query = "INSERT INTO carte(x, y, z, type, id) VALUES(%s, %s, %s, 'ROUTE', '%s');";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]),
			mysql_real_escape_string($line[5]));
		mysql_query($query);
	}
	else {
		// update
		$query = "UPDATE carte SET x=%s, y=%s, z=%s, id='%s'";
		$query .= "WHERE x=%s AND y=%s AND z=%s AND type='ROUTE';";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]),
			mysql_real_escape_string($line[5]),
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		mysql_query($query);
	}
}

/*
Insère ou met à jour un element de type palissade
PALISSADE;x;y;z;id_palissade;est_destructible_palissade
*/
function update_palissade($line) {
	$query = "SELECT x FROM carte WHERE x=%s AND y=%s AND z=%s AND type='PALISSADE';";
	$query = sprintf($query,
		mysql_real_escape_string($line[1]),
		mysql_real_escape_string($line[2]),
		mysql_real_escape_string($line[3]));
	$res = mysql_query($query);
	if (mysql_num_rows($res) == 0) {
		// insert
		$query = "INSERT INTO carte(x, y, z, type, id) VALUES(%s, %s, %s, 'PALISSADE', '%s');";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]),
			mysql_real_escape_string($line[5]));
		mysql_query($query);
	}
	else {
		// update
		$query = "UPDATE carte SET x=%s, y=%s, z=%s, id='%s'";
		$query .= "WHERE x=%s AND y=%s AND z=%s AND type='PALISSADE';";
		$query = sprintf($query,
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]),
			mysql_real_escape_string($line[5]),
			mysql_real_escape_string($line[1]),
			mysql_real_escape_string($line[2]),
			mysql_real_escape_string($line[3]));
		mysql_query($query);
	}
}

$db = mysql_connect("localhost", "braldahim", "braldahim") or die('Impossible de se connecter');
mysql_select_db("braldahim");

$query = "SELECT braldahim_id, crypted_password FROM user;";
$res = mysql_query($query);

if (! $res) die('Impossible de lancer une requete');

while ($row = mysql_fetch_assoc($res)) {
	if (is_null($row['crypted_password']) || $row['crypted_password'] == 'NULL') {
		continue;
	}
	$url = "http://sp.braldahim.com/scripts/profil/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
	//$url = "http://www.guim.info/braldahim/toto.php?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
	fetch_position($url);
	
	$url = "http://sp.braldahim.com/scripts/vue/?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
	//$url = "http://www.guim.info/braldahim/toto.php?idBraldun={$row['braldahim_id']}&mdpRestreint={$row['crypted_password']}&version=1";
	fetch_vue($url);
}
mysql_free_result($res);


?>