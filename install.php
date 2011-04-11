<?php
$msg = array();

// file_get_content
if (function_exists("file_get_contents")) {
	$c = @file_get_contents("conf.php");
	if (count($c) == 0) {
		$msg[] = array('msg' => "La fonction file_get_content est disponible, mais il n'est pas possible de lire le fichier de configuration.", 'status' => false);
	}
	else {
		$msg[] = array('msg' => "La fonction file_get_content est disponible et fonctionnelle.", 'status' => true);
	}
}
else {
	$msg[] = array('msg' => "La fonction file_get_contents n'est pas disponible.", 'status' => false);
}

// imagettftext
if (function_exists("imagettftext")) {
	$msg[] = array('msg' => "La fonction imagettftext est disponible.", 'status' => true);
}
else {
	$msg[] = array('msg' => "La fonction imagettftext n'est pas disponible.", 'status' => false);
}

// droit d'acces
if (! function_exists("file_put_contents")) {
	$msg[] = array('msg' => "La fonction file_put_contents n'est pas disponible.", 'status' => false);
}
else {
	$files = array('csv', 'cache', 'cache/img');
	foreach ($files as $f) {
		if (@file_put_contents(dirname(__FILE__)."/$f/toto", 'toto') === FALSE) {
			$msg[] = array('msg' => "Impossible d'écrire dans le répertoire '$f'.", 'status' => false);
		}
		else {
			$msg[] = array('msg' => "Le répertoire '$f' est accessible en écriture.", 'status' => true);
		}
		@unlink(dirname(__FILE__)."/$f/toto");
	}
}

// DB
if (! file_exists("conf.local.php")) {
	$msg[] = array('msg' => "Veuillez créer le fichier 'conf.local.php' pour configurer la base de données.", 'status' => false);
}
else {
	include("conf.php");
	if (mysql_connect(DB_HOST, DB_USER, DB_PASS) === FALSE) {
		$msg[] = array('msg' => "Impossible de se connecter à la base de données.", 'status' => false);
	}
	else if(mysql_select_db(DB_NAME) === FALSE) {
		$name = DB_NAME;
		$msg[] = array('msg' => "Impossible de se connecter à la base de données '$name'.", 'status' => false);
	}
	else if (mysql_set_charset('utf8') === FALSE) {
		$msg[] = array('msg' => "Impossible de changer l'encodage vers UTF8.", 'status' => false);
	}
	else if (mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'") === FALSE) {
		$msg[] = array('msg' => "Impossible d'envoyer un requête à la base de données.", 'status' => false);
	}
	else {
		$msg[] = array('msg' => "La base de données fonctionne.", 'status' => true);
	}
}

$content = '';
foreach ($msg as $m) {
	$content .= "<p>{$m['msg']}";
	if ($m['status']) {
		$content .= '<span style="background-color: green">&nbsp;OK</span>';
	}
	else {
		$content .= '<span style="background-color: red">&nbsp;KO</span>';
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Cache-Control" content="no-cache" />
</head>
<body>
<p>Voici le diagnostique de votre installation de braldaguim :</p>
<?php echo $content;?>
</body>
</html>
