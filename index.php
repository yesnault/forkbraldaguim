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

error_reporting(E_ALL);

session_start();

define('CSV_DIR', '/var/www/guim.info/www/braldahim/csv');
define('FILE_bralduns_csv', CSV_DIR.'/bralduns.csv');
define('COMMUNAUTE', 1); // l'id de la communauté est 1 (c'est un peu hard codé...)

/*
CREATE TABLE user(braldahim_id MEDIUMINT PRIMARY KEY, crypted_password VARCHAR(32), prenom TEXT, nom TEXT, x MEDIUMINT, y MEDIUMINT);
CREATE TABLE carte (x MEDIUMINT, y MEDIUMINT, z MEDIUMINT, type TEXT, id TEXT);
*/

class BraldahimApp {
	private $html_title;
	private $html_content;
	private $html_message;
	private $db;
	private $action;
	
	public $logged; // est on connecté ?
	
	public function __construct() {
		$this->db = mysql_connect("localhost", "braldahim", "braldahim");
		mysql_select_db("braldahim");
		$this->html_title = 'Les premiers Brald&ucirc;ns';
		$this->logged = isset($_SESSION['bra_num']);
		$this->actionParse();
	}

	public function __destruct() {
		//mysql_close($this->db);
	}

	/*
	Controleur à 2 sous.
	On appelle la méthode 'qui_va_bien' en fonction de l'action.
	*/
	public function actionParse() {
		$this->action = 'home';
		if (isset($_REQUEST['action'])) {
			$this->action = $_REQUEST['action'];
		}
		
		$this->html_content = '';
		switch($this->action) {
			case 'login_form':
				if ($this->logged) break;
				$this->getLoginForm();
				break;
			case 'login':
				if ($this->logged) break;
				$this->login();
				break;
			case 'logout':
				if (!$this->logged) break;
				$this->logout();
				break;
			case 'inscription':
				if ($this->logged) break;
				$this->getInscriptionForm();
				break;
			case 'inscription_submit':
				if ($this->logged) break;
				$this->inscriptionParse();
				break;
			case 'position':
				if (!$this->logged) break;
				$this->position();
				break;
			case 'home':
			default:
				$this->home();
				break;
		}
	}

	public function getHtmlTitle() {
		return $this->html_title;
	}

	public function getHtmlMessage() {
		return $this->html_message;
	}

	public function getHtmlContent() {
		return $this->html_content;
	}
	
	/*
	Affiche le lien de connexion ou de deconnexion
	*/
	public function getLoginLink() {
		if (! isset($_SESSION['bra_num'])) {
			return '<a href="index.php?action=login_form">Connexion</a>';
		}
		return '<a href="index.php?action=logout">Deconnexion</a>';
	}
	
	/*
	Action 'home', on affiche un message d'accueil
	*/
	private function home() {
		$this->html_content = <<<EOF
<p>Bienvenue sur la page de positionnement de la communaut&eacute; <b>Les premiers braldh&ucirc;ns</b>.</p>
<p>Pour vous inscrire, utilisez le lien <a href="index.php?action=inscription">Inscription</a>.</p>
<p>Vous pourrez ensuite conna&icirc;tre la position des braldh&ucirc;ns de la communaut&eacute;.</p>
<p>La mise à jour des positions &agrave; lieu tous les jours &agrave; midi.</p>
EOF;
	}

	/*
	Action 'inscription', on affiche le formulaire
	*/
	private function getInscriptionForm() {
		$this->html_content =<<<EOF
<p>Pour vous inscrire, vous avez besoin de votre num&eacute;ro de braldh&ucirc;n et 
de votre mot de passe restreint.
Vous pouvez obtenir votre mot de passe restreint à l'adresse suivante :
<a target="_blank" href="http://sp.braldahim.com/md5/">http://sp.braldahim.com/md5/</a>.</p>

<form action="index.php?action=inscription_submit" method="POST">
	<label for="bra_num">Num&eacute;ro du Braldh&ucirc;n :</label>
	<input id="bra_num" name="bra_num" type="text">
	<br/>
	<label for="bra_pw">Mot de passe restreint :</label>
	<input id="bra_pw" name="bra_pw" type="text">
	<br/>
	<input type="submit">
</form>
EOF;
	}

	/*
	Action 'inscription_submit', on teste le formulaire :
	  - soit erreur affichée à l'utilisateur
	  - soit ajout en db
	*/
	private function inscriptionParse() {
		$bra_num = ((isset($_REQUEST['bra_num'])) ? $_REQUEST['bra_num'] : null);
		$bra_pw = ((isset($_REQUEST['bra_pw'])) ? $_REQUEST['bra_pw'] : null);
		$bra_num = trim($bra_num);
		$bra_pw = trim($bra_pw);
		
		if (empty($bra_num) || empty($bra_pw)) {
			$this->html_message = "Veuillez remplir tous les champs.";
			$this->getInscriptionForm();
			return;
		}
		if (!is_numeric($bra_num)) {
			$this->html_message = "Le champs 'Num&eacute;ro' doit &ecirc;tre un nombre.";
			$this->getInscriptionForm();
			return;
		}
		if (strlen($bra_pw) != 32) {
			$this->html_message = "Le champs 'Mot de passe restreint' doit avoir 32 caract&egrave;res.";
			$this->getInscriptionForm();
			return;
		}
		// teste si le braldun est membre de la communauté
		// on charge le fichie csv en provenance de braldahim.com
		// id_braldun;prenom_braldun;nom_braldun;niveau_braldun;sexe_braldun;nb_ko_braldun;nb_braldun_ko_braldun;nb_plaque_braldun;nb_braldun_plaquage_braldun;nb_monstre_kill_braldun;id_fk_mere_braldun;id_fk_pere_braldun;id_fk_communaute_braldun;id_fk_rang_communaute_braldun;url_blason_braldun;url_avatar_braldun;est_pnj_braldun
		$content = file(FILE_bralduns_csv);
		$communaute_id = -1;
		$keys = explode(';', $content[0]);
		foreach ($content as $line) {
			if (preg_match("/^{$bra_num};/", $line) != 1) continue;
			$values = explode(';', $line);
			// recherche de l'indice de id_fk_communaute_braldun
			$max = count($keys);
			for ($i=0; $i<$max; $i++) {
				if ($keys[$i] == 'id_fk_communaute_braldun') {
					$communaute_id = $values[$i];
					break;
				}
			}
		}
		unset($content);
		if ($communaute_id != COMMUNAUTE) {
			$this->html_message = "Le brald&ucirc;n {$bra_num} ne fait pas partie des Permiers Brald&ucirc;ns.".$communaute_id;
			$this->getInscriptionForm();
			return;
		}
		
		$query = sprintf("SELECT braldahim_id FROM user WHERE braldahim_id=%s;", mysql_real_escape_string($bra_num));
		$res = mysql_query($query, $this->db);
		if (mysql_num_rows($res) != 0) {
			$this->html_message = "Un braldh&ucirc;n existe d&eacute;j&agrave; avec cet identifiant.";
			$this->getInscriptionForm();
			mysql_free_result($res);
			return;
		}
		mysql_free_result($res);
		
		$query = sprintf("INSERT INTO user(braldahim_id, crypted_password) VALUES(%s, '%s');",
			mysql_real_escape_string($bra_num),
			mysql_real_escape_string($bra_pw));
		mysql_query($query, $this->db);
		$this->html_message = "Inscription r&eacute;alis&eacute;e avec succ&egrave;s.";
	}
	
	/*
	Action 'login_form', on affiche le formulaire
	*/
	private function getLoginForm() {
		$this->html_content =<<<EOF
<p>Pour vous connecter veuillez indiquer votre num&eacute;ro de brald&ucirc;n et votre mot de passe de braldahim.com.</p>

<form action="index.php?action=login" method="POST">
	<label for="bra_num">Num&eacute;ro du Braldh&ucirc;n :</label>
	<input id="bra_num" name="bra_num" type="text">
	<br/>
	<label for="bra_pw">Mot de passe :</label>
	<input id="bra_pw" name="bra_pw" type="password">
	<br/>
	<input type="submit">
</form>
EOF;
	}
	
	/*
	Action 'login', on teste le formulaire :
	  - soit erreur affichée à l'utilisateur
	  - soit ajout en session
	*/
	private function login() {
		$bra_num = ((isset($_REQUEST['bra_num'])) ? $_REQUEST['bra_num'] : null);
		$bra_pw = ((isset($_REQUEST['bra_pw'])) ? $_REQUEST['bra_pw'] : null);
		$bra_num = trim($bra_num);
		$bra_pw = trim($bra_pw);
		
		if (empty($bra_num) || empty($bra_pw)) {
			$this->html_message = "Veuillez remplir tous les champs.";
			$this->getLoginForm();
			return;
		}
		if (!is_numeric($bra_num)) {
			$this->html_message = "Le champs 'Num&eacute;ro' doit &ecirc;tre un nombre.";
			$this->getLoginForm();
			return;
		}
		if (strlen($bra_pw) == 0) {
			$this->html_message = "Le champs 'Mot de passe' doit ne doit pas &ecirc;tre vide.";
			$this->getLoginForm();
			return;
		}
		$query = sprintf("SELECT braldahim_id FROM user WHERE braldahim_id=%s AND crypted_password='%s';",
			mysql_real_escape_string($bra_num),
			mysql_real_escape_string(md5($bra_pw)));
		$res = mysql_query($query, $this->db);
		if (mysql_num_rows($res) != 0) {
			// authentification ok
			$_SESSION['bra_num'] = $bra_num;
			$this->html_message = "Authentification r&eacute;ussie.";
			$this->logged = true;
		}
		else {
			$this->html_message = "&Eacute;chec de l'authentification.";
			$this->getLoginForm();
			$this->logged = false;
		}
		mysql_free_result($res);
	}
	
	/*
	Action 'logout', on efface la session
	*/
	private function logout() {
		session_unset();
		
		$_SESSION = array();
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}
		session_destroy();
		$this->home();
		$this->logged = false;
	}
	
	/*
	Affiche la position des membres de la communauté
	*/
	private function position() {
		if (! isset($_SESSION['bra_num'])) {
			return;
		}
		$query = "SELECT braldahim_id, prenom, nom, x, y FROM user ORDER BY braldahim_id ASC;";
		$res = mysql_query($query, $this->db);
		
		$content = '<table id="tab_position" border="1">';
		$content .= '<tr><th>Braldh&ucirc;ns</th><th>X</th><th>Y</th></tr>';
		while ($row = mysql_fetch_assoc($res)) {
			$content .= "<tr><td>{$row['prenom']} {$row['nom']}</td><td>{$row['x']}</td><td>{$row['y']}</td></tr>";
		}
		$content .= '</table>';
		$content .= '<img id="img_map" src="map.php" />';
		mysql_free_result($res);
		$this->html_content = $content;
	}
}

$app = new BraldahimApp();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr"> 
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Cache-Control" content="no-cache" />

<style type="text/css">
body {background-color: #003B00; color: #FFFFFF;}
a:link, a:visited {color: #BBBBFF;}
a:hover {color: #F0AE21;}
#menu ul {
	list-style-type: none;
	padding: 0;
	text-align: center;
	margin: .5em 0 .5em;
}
#menu ul li {display: inline;}
#menu ul li a {
	font-weight: bold;
	text-decoration: none;
	padding: .2em 1em;
	background-color: #5D8231;
}
#menu ul li a:hover {
	color: #F0AE21;
}
#main {
	margin: 0 2em;
}
#message {
	background-color: #EF7E68;
	text-align: center;
}

#tab_position {
	border-collapse: collapse;
	border-color: #5D8231;
}
#tab_position td, #tab_position th {
	padding: .5em .3em;
}
#img_map {
	margin: 2em;
	border: 1px solid black;
}
</style>

<title><?php  echo $app->getHtmlTitle(); ?></title>
</head>
<body>

<div id="menu">
	<ul>
		<li><a href="index.php?action=home">Accueil</a></li>
		<?php if (! $app->logged) {?>
		<li><a href="index.php?action=inscription">Inscription</a></li>
		<?php } ?>
		<li><?php echo $app->getLoginLink(); ?></li>
		<?php if ($app->logged) {?>
		<li><a href="index.php?action=position">Position</a></li>
		<?php } ?>
	</ul>
</div>

<div id="main">
	<div id="message">
		<?php  echo $app->getHtmlMessage(); ?>
	</div>
	<div id="content">
		<?php  echo $app->getHtmlContent(); ?>
	</div>
</div>

</body>
</html>