<?php

require_once(dirname(__FILE__)."/conf.php");
mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Impossible de se connecter');
mysql_select_db(DB_NAME);
mysql_set_charset('utf8');
mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");

$files = array(
'bralduns.csv',
'communautes.csv',
'competences.csv',
'distinctions.csv',
'environnements.csv',
'lieux_villes.csv',
'metiers.csv',
'plantes.csv',
'rangs_communautes.csv',
'regions.csv',
'titres.csv',
'villes.csv',
'zones.csv'
);

foreach ($files as $f) {
	file_put_contents("csv/$f", file_get_contents("http://public.braldahim.com/$f"));
}

/*
zones.csv
id_zone	id_fk_environnement_zone	nom_systeme_environnement	x_min_zone	x_max_zone	y_min_zone	y_max_zone
*/
$content = file('csv/zones.csv');
if (count($content) != 0) {
	mysql_query("TRUNCATE zone;");
	$query_start = "INSERT INTO zone VALUES(";
	for ($i=1; $i<count($content); $i++) {
		$v = explode(';', trim($content[$i]));
		$query = $query_start."{$v[0]}, {$v[1]}, '{$v[2]}', {$v[3]}, {$v[4]}, {$v[5]}, {$v[6]});";
		mysql_query($query);
	}
}

/*
villes.csv
id_ville	nom_ville	est_capitale_ville	x_min_ville	y_min_ville	x_max_ville	y_max_ville	id_region	nom_region
*/
$content = file('csv/villes.csv');
if (count($content) != 0) {
	mysql_query("TRUNCATE ville;");
	$query_start = "INSERT INTO ville VALUES(";
	for ($i=1; $i<count($content); $i++) {
		$v = explode(';', trim($content[$i]));
		$query = $query_start."{$v[0]}, '{$v[1]}', '{$v[2]}', {$v[3]}, {$v[4]}, {$v[5]}, {$v[6]}, {$v[7]}, '{$v[8]}');";
		mysql_query($query);
	}
}

?>

