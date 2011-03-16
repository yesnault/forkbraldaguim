<?php
// inclusion du fichier personnalisé
//echo dirname(__FILE__)."/conf.local.php";
if (file_exists(dirname(__FILE__)."/conf.local.php")) {
	include(dirname(__FILE__)."/conf.local.php");
}

// repertoire contenant les fichiers csv
if (! defined('CSV_DIR')) define('CSV_DIR', '/var/www/braldahim/csv');
// nom du fichier contenant la liste des bralduns
if (! defined('FILE_bralduns_csv')) define('FILE_bralduns_csv', CSV_DIR.'/bralduns.csv');
// numero identifiant la communauté
if (! defined('COMMUNAUTE')) define('COMMUNAUTE', 1);

// base de données
if (! defined('DB_HOST')) define('DB_HOST', 'localhost');
if (! defined('DB_NAME')) define('DB_NAME', 'braldahim_db');
if (! defined('DB_USER')) define('DB_USER', 'braldahim_user');
if (! defined('DB_PASS')) define('DB_PASS', 'braldahim_pass');

// niveau de zoom par defaut
if (! defined('DEF_ZOOM')) define('DEF_ZOOM', 3);

?>
