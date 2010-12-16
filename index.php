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

require_once("conf.php");

class BraldahimApp {
	private $html_title;
	private $html_content;
	private $html_message;
	private $html_script;
	private $db;
	private $action;
	
	public $logged; // est on connecté ?
	
	public function __construct() {
		$this->db = mysql_connect(DB_HOST, DB_USER, DB_PASS);
		mysql_select_db(DB_NAME);
		$this->html_title = 'Les premiers Brald&ucirc;ns';
		$this->html_script = '';
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
	
	public function getHtmlScript() {
		$str =<<<EOF
window.onload = function () {
	initOnClick();
}
function initOnClick() {
	// show/hide map layer
	document.getElementById("chk_fond").addEventListener('click', show_hide_layer, false);
	document.getElementById("chk_joueur").addEventListener('click', show_hide_layer, false);
	document.getElementById("chk_lieumythique").addEventListener('click', show_hide_layer, false);
	document.getElementById("chk_lieustandard").addEventListener('click', show_hide_layer, false);
	document.getElementById("chk_legende").addEventListener('click', show_hide_layer, false);
	
	// refresh player's position
	document.getElementById("update_link").addEventListener('click', fetch_position, false);
}
function show_hide_layer() {
	this.id.match(/[^_]+_(.*)/);
	var id_layer = "map_"+RegExp.$1;
	if (this.checked) {
		document.getElementById(id_layer).style.visibility = 'visible';
	}
	else {
		document.getElementById(id_layer).style.visibility = 'hidden';
	}
}
function calcDistance() {
	var dp1 = document.getElementById("dist_player1");
	var dp2 = document.getElementById("dist_player2");
	var dr = document.getElementById("dist_result");
	var pos1 = dp1.options[dp1.selectedIndex].value.split(";");
	var pos2 = dp2.options[dp2.selectedIndex].value.split(";");
	var dx = pos2[0]-pos1[0];
	var dy = pos2[1]-pos1[1];
	var hyp = Math.floor(Math.sqrt(Math.pow(dx,2) + Math.pow(dy,2)));
	dr.innerHTML = dx+" cases horizontales et "+dy+" cases verticales, soit un déplacement de "+hyp+" cases.";
}
function getHTTPObject() {
	if (window.XMLHttpRequest) {
		return new XMLHttpRequest();
	}
	else if (window.ActiveXObject) {
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
	else {
		return null;
	}
}
function fetch_position() {
	httpObject = getHTTPObject();
	if (httpObject == null) return;
	httpObject.open("GET", "fetch.php", true);
	httpObject.send(null);
	httpObject.onreadystatechange = updatePositionIcon;
	document.getElementById('update_link').src = 'img/Throbber-small.gif';
}
function updatePositionIcon() {
	if (httpObject.readyState == 4) {
		if (httpObject.responseText == 'ok') {
			document.getElementById('update_link').src = 'img/Throbber-small.png';
		}
		else {
			document.getElementById('update_link').src = 'img/error.png';
		}
	}
}
EOF;
		return $this->html_script.$str;
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
<p>Bienvenue sur la page de positionnement de la communaut&eacute; <b>Les premiers brald&ucirc;ns</b>.</p>
<p>Pour vous inscrire, utilisez le lien <a href="index.php?action=inscription">Inscription</a>.</p>
<p>Vous pourrez ensuite conna&icirc;tre la position des brald&ucirc;ns de la communaut&eacute;.</p>
<p>La mise à jour des positions &agrave; lieu <u>toutes les 6 heures</u>.</p>
EOF;
	}

	/*
	Action 'inscription', on affiche le formulaire
	*/
	private function getInscriptionForm() {
		$this->html_content =<<<EOF
<p>Pour vous inscrire, vous avez besoin de votre num&eacute;ro de brald&ucirc;n et 
de votre mot de passe restreint.
Vous pouvez obtenir votre mot de passe restreint à l'adresse suivante :
<a target="_blank" href="http://sp.braldahim.com/md5/">http://sp.braldahim.com/md5/</a>.</p>

<form action="index.php?action=inscription_submit" method="POST">
	<label for="bra_num">Num&eacute;ro du Brald&ucirc;n :</label>
	<input id="bra_num" name="bra_num" type="text">
	<br/>
	<label for="bra_pw">Mot de passe <b><u>restreint</u></b> :</label>
	<input id="bra_pw" name="bra_pw" type="text">
	<br/>
	<input type="submit" value="Inscription">
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
			$this->html_message = "Un brald&ucirc;n existe d&eacute;j&agrave; avec cet identifiant.";
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
	<label for="bra_num">Num&eacute;ro du Brald&ucirc;n :</label>
	<input id="bra_num" name="bra_num" type="text">
	<br/>
	<label for="bra_pw">Mot de passe :</label>
	<input id="bra_pw" name="bra_pw" type="password">
	<br/>
	<input type="submit" value="Connexion">
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
		$bralduns = $this->getBralduns();
		$zoom = (array_key_exists('zoom', $_REQUEST) && is_numeric($_REQUEST['zoom'])) ? $_REQUEST['zoom'] : 0;
		
		// pour chaque braldun on affiche sa position
		$tab_bra = '';
		foreach ($bralduns as $braldun) {
			$update_link = '';
			// si c'est le joueur connecté, on affiche un lien pour la maj
			if ($_SESSION['bra_num'] == $braldun['braldahim_id']) {
				$update_link =<<<EOF
<img id="update_link" src="img/Throbber-small.png" />
EOF;
			}
			$tab_bra .=<<<EOF
<tr>
	<td>{$braldun['prenom']} {$braldun['nom']} {$update_link}</td>
	<td>{$braldun['x']}</td>
	<td>{$braldun['y']}</td>
</tr>
EOF;
		}
		
		// construction de la calculatrice de deplacement
		$distance = $this->distanceCalc();
		
		// construction des formulaires de zoom/deplacement
		$controle = $this->getMoveForm("zoom=".($zoom-1), "zoom -");
		$controle .= $this->getMoveForm("zoom=".($zoom+1), "zoom +");
		
		// construction de l'affichage de la page
		$content =<<<EOF
<div id="position">
	<table id="tab_position" border="1">
	<tr><th>Brald&ucirc;ns</th><th>X</th><th>Y</th></tr>
	$tab_bra
	</table>
	$distance
</div>
<div id="map_wrapper">
	<div class="map_item" id="map_fond"><img src="map.php?type=fond&zoom=$zoom" /></div>
	<div class="map_item" id="map_lieumythique"><img src="map.php?type=lieumythique&zoom=$zoom" /></div>
	<div class="map_item" id="map_lieustandard"><img src="map.php?type=lieustandard&zoom=$zoom" /></div>
	<div class="map_item" id="map_joueur"><img src="map.php?type=joueur&zoom=$zoom" /></div>
	<div class="map_item" id="map_legende"><img src="map.php?type=legende&zoom=$zoom" /></div>
	
	<div id="map_info">
		<p>Affichage des informations :</p>
		
		<br/><input type="checkbox" id="chk_fond" checked="checked" />
		<label for="chk_fond">Fond de carte</label>
		
		<br/><input type="checkbox" id="chk_joueur" checked="checked" />
		<label for="chk_joueur">Membres</label>
		
		<br/><input type="checkbox" id="chk_lieumythique" checked="checked" />
		<label for="chk_lieumythique">Lieux mythiques et lieux de qu&ecirc;tes </label>
		
		<br/><input type="checkbox" id="chk_lieustandard" />
		<label for="chk_lieustandard">Lieux standard</label>
		
		<br/><input type="checkbox" id="chk_legende" />
		<label for="chk_legende">Legende</label>
		<br />
		$controle
	</div>
</div>
EOF;
		$this->html_content = $content;
	}
	
	/*
	Construit un formulaire pour le deplacement/zoom indiqué
	$action : chaine à ajouter à l'attribut action du formulaire
	$name : valeur du tag input
	*/
	private function getMoveForm($action, $name) {
		$str =<<<EOF
<a href="index.php?action=position&{$action}">{$name}</a><br />
EOF;
		return $str;
	}
	
	/*
	Affiche un calculateur de distance entre bralduns
	*/
	private function distanceCalc() {
		$bralduns = $this->getBralduns();
		$str = '';
		$str .= '<div id="dist">La distance s&eacute;parant ';
		$str .= '<select id="dist_player1">';
		foreach ($bralduns as $braldun) {
			$c = ($_SESSION['bra_num'] == $braldun['braldahim_id']) ? ' selected="selected" ' : '';
			$str .= "<option {$c} value=\"{$braldun['x']};{$braldun['y']}\">{$braldun['prenom']} {$braldun['nom']}</option>";
		}
		$str .= '</select> de <select id="dist_player2">';
		foreach ($bralduns as $braldun) {
			$str .= "<option value=\"{$braldun['x']};{$braldun['y']}\">{$braldun['prenom']} {$braldun['nom']}</option>";
		}
		$str .= '</select> est de :';
		$str .= '<br/><span id="dist_result"></span>';
		$str .= '<br/><input type="button" value="Distance" onClick="javascript:calcDistance();"/>';
		$str .= "</div>";
		return $str;
	}
	
	/*
	Retourne un tableau contenant tous les membres de la communauté
	*/
	private function getBralduns() {
		if (isset($this->bralduns)) {
			return $this->bralduns;
		}
		$this->bralduns = array();
		$query = "SELECT braldahim_id, prenom, nom, x, y FROM user ORDER BY braldahim_id ASC;";
		$res = mysql_query($query, $this->db);
		while ($row = mysql_fetch_assoc($res)) {
			$tmp = array();
			foreach ($row as $k => $v) {
				$tmp[$k] = $v;
			}
			$this->bralduns[] = $tmp;
		}
		mysql_free_result($res);
		
		return $this->bralduns;
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
body {
	background-color: #003B00;
	color: #FFFFFF;
}
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
	margin: 2em 2em 1em;
}
#message {
	background-color: #EF7E68;
	text-align: center;
}

#dist {
	float: left;
	padding: 0 2em;
}
#tab_position {
	float: left;
	border-collapse: collapse;
	border-color: #5D8231;
}
#tab_position td, #tab_position th {
	padding: .5em .3em;
}


#map_wrapper {
	clear: both;
	height: 520px;
	position: relative;
}
.map_item {
	position: absolute;
	top: 0;
	left: 0;
	border: 1px solid black;
	margin: 10px 10px;
}
#map_legende, #map_lieustandard {
	visibility: hidden;
}
#map_info {
	position: absolute;
	top: 0;
	left: 520px;
	margin: 10px 10px;
}
</style>

<title><?php  echo $app->getHtmlTitle(); ?></title>
<script type="text/javascript"><?php echo $app->getHtmlScript(); ?></script>
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
