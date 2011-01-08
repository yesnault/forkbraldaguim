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
		mysql_set_charset('utf8', $this->db);
		mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
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
			case 'bestiaire':
				if (!$this->logged) break;
				$this->bestiaire();
				break;
			case 'bestiaire_submit':
				if (!$this->logged) break;
				$this->bestiaireParse();
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
	if (! window.ActiveXObject) {
		// show/hide map layer
		document.getElementById("chk_fond").addEventListener('click', show_hide_layer, false);
		document.getElementById("chk_brouillard").addEventListener('click', show_hide_layer, false);
		document.getElementById("chk_joueur").addEventListener('click', show_hide_layer, false);
		document.getElementById("chk_lieumythique").addEventListener('click', show_hide_layer, false);
		document.getElementById("chk_lieustandard").addEventListener('click', show_hide_layer, false);
		document.getElementById("chk_legende").addEventListener('click', show_hide_layer, false);
		// refresh player's position
		//document.getElementById("update_link").addEventListener('click', fetch_position, false);	
	}
	else {
		document.getElementById("chk_fond").onclick = show_hide_layer;
		document.getElementById("chk_brouillard").onclick = show_hide_layer;
		document.getElementById("chk_joueur").onclick = show_hide_layer;
		document.getElementById("chk_lieumythique").onclick = show_hide_layer;
		document.getElementById("chk_lieustandard").onclick = show_hide_layer;
		document.getElementById("chk_legende").onclick = show_hide_layer;
		//document.getElementById("update_link").onclick = fetch_position;
	}
	
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
			$this->position();
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
		$villes = $this->getVilles();
		
		// informations de positionnement
		$zoom = (array_key_exists('zoom', $_REQUEST) && is_numeric($_REQUEST['zoom'])) ? $_REQUEST['zoom'] : 4;
		if ($zoom < 1 ) $zoom = 1;
		$y = (array_key_exists('y', $_REQUEST) && is_numeric($_REQUEST['y'])) ? $_REQUEST['y'] : 0;
		$x = (array_key_exists('x', $_REQUEST) && is_numeric($_REQUEST['x'])) ? $_REQUEST['x'] : 0;
		$url_append = "zoom=$zoom&y=$y&x=$x";
		
		// pour chaque braldun on affiche :
		// un lien pour centrer la carte sur sa position,
		// son nom, sa position
		$tab_bra = '';
		foreach ($bralduns as $braldun) {
			$update_link = '';
			// si c'est le joueur connecté, on affiche un lien pour la maj
			/*if ($_SESSION['bra_num'] == $braldun['braldahim_id']) {
				$update_link =<<<EOF
<img id="update_link" src="img/Throbber-small.png" />
EOF;
			}*/
			$user_pos = $this->getMoveControl($zoom, $braldun['x'], $braldun['y'], "X");
			$tab_bra .=<<<EOF
<tr>
	<td>{$user_pos}	{$braldun['prenom']} {$braldun['nom']} {$update_link}</td>
	<td>{$braldun['x']}</td>
	<td>{$braldun['y']}</td>
</tr>
EOF;
		}
		
		// pour chaque ville on affiche :
		// un lien pour centrer la carte sur sa position,
		// son nom
		$tab_ville = '';
		foreach ($villes as $v) {
			$v_pos = $this->getMoveControl($zoom, $v['x'], $v['y'], "X");
			$tab_ville .=<<<EOF
<tr>
	<td>{$v_pos} {$v['nom_ville']}</td>
	<td>{$v['nom_region']}</td>
</tr>
EOF;
		}
		
		// construction de la calculatrice de deplacement
		$distance = $this->distanceCalc();
		
		// construction des formulaires de zoom/deplacement
		$move_delta = pow(2, $zoom+1); // le decallage est dependant du zoom
		
		$ctrl_zoom_p = $this->getMoveControl($zoom-1, $x, $y, "zoom +");
		$ctrl_zoom_m = $this->getMoveControl($zoom+1, $x, $y, "zoom -");
		$ctrl_haut = $this->getMoveControl($zoom, $x, $y+$move_delta, "Haut");
		$ctrl_bas = $this->getMoveControl($zoom, $x, $y-$move_delta, "Bas");
		$ctrl_gauche = $this->getMoveControl($zoom, $x-$move_delta, $y, "Gauche");
		$ctrl_droite = $this->getMoveControl($zoom, $x+$move_delta, $y, "Droite");
		
		// construction de l'affichage de la page
		$content =<<<EOF

<div id="map_wrapper">
	<div class="map_item" id="map_fond"><img src="map.php?type=fond&$url_append" /></div>
	<div class="map_item" id="map_brouillard"><img src="map.php?type=brouillard&$url_append" /></div>
	<div class="map_item" id="map_lieumythique"><img src="map.php?type=lieumythique&$url_append" /></div>
	<div class="map_item" id="map_lieustandard"><img src="map.php?type=lieustandard&$url_append" /></div>
	<div class="map_item" id="map_joueur"><img src="map.php?type=joueur&$url_append" /></div>
	<div class="map_item" id="map_legende"><img src="map.php?type=legende&$url_append" /></div>
	
	<div id="map_info">
		<p>Affichage des informations :</p>
		
		<br/><input type="checkbox" id="chk_fond" checked="checked" />
		<label for="chk_fond">Fond de carte</label>
		
		<br/><input type="checkbox" id="chk_brouillard" checked="checked" />
		<label for="chk_brouillard">Brouillard</label>
		
		<br/><input type="checkbox" id="chk_joueur" checked="checked" />
		<label for="chk_joueur">Membres</label>
		
		<br/><input type="checkbox" id="chk_lieumythique" checked="checked" />
		<label for="chk_lieumythique">Lieux mythiques et lieux de qu&ecirc;tes </label>
		
		<br/><input type="checkbox" id="chk_lieustandard" />
		<label for="chk_lieustandard">Lieux standard</label>
		
		<br/><input type="checkbox" id="chk_legende" />
		<label for="chk_legende">Legende</label>
		<br />
		<table>
			<tr><td>$ctrl_zoom_m</td><td>$ctrl_zoom_p</td></tr>
			<tr><td></td><td>$ctrl_haut</td><td></td></tr>
			<tr><td>$ctrl_gauche</td><td></td><td>$ctrl_droite</td></tr>
			<tr><td></td><td>$ctrl_bas</td><td></td></tr>
		</table>
	</div>
</div>
<div id="position">
	<table class="tab_position" border="1">
	<tr><th>Brald&ucirc;ns</th><th>X</th><th>Y</th></tr>
	$tab_bra
	</table>
	<table class="tab_position" border="1">
	<tr><th>Ville</th><th>R&eacute;gion</th></tr>
	$tab_ville
	</table>
	$distance
</div>
EOF;
		$this->html_content = $content;
	}
	
	/*
	Construit un lien pour le deplacement/zoom indiqué
	$zoom : niveau de zoom
	$x, $y : positionnement
	$inner : contenu du lien à afficher
	*/
	private function getMoveControl($zoom, $x, $y, $inner) {
		$str =<<<EOF
<a href="index.php?action=position&zoom={$zoom}&x={$x}&y={$y}">{$inner}</a>
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

	/*
	Retourne un tableau contenant les villes
	*/
	private function getVilles() {
		if (isset($this->villes)) {
			return $this->villes;
		}
		$this->villes = array();
		$query = "SELECT nom_ville, nom_region,
			floor(x_min_ville + (x_max_ville - x_min_ville) / 2) as x,
			floor(y_min_ville +(y_max_ville - y_min_ville) / 2) as y
			FROM ville
			ORDER BY nom_ville ASC;";
		$res = mysql_query($query, $this->db);
		while ($row = mysql_fetch_assoc($res)) {
			$tmp = array();
			foreach ($row as $k => $v) {
				$tmp[$k] = $v;
			}
			$this->villes[] = $tmp;
		}
		mysql_free_result($res);
		
		return $this->villes;
	}
	
	/*
	Affiche le bestiaire :
	  - liste des monstres
	  - affichage détaillé
	  - interface de saisie
	*/
	private function bestiaire() {
		// liste des monstres
		$liste_monstres = $this->getListeMonstres();
		$liste = '';
		foreach ($liste_monstres as $m) {
			$uem = urlencode(utf8_decode($m));
			$liste .= '<li><a href="index.php?action=bestiaire&m='.$uem.'">'.$m.'</a></li>';
		}
		
		// description detaillee
		$m = ((isset($_REQUEST['m'])) ? $_REQUEST['m'] : null);
		$m = trim($m);
		$str_monstre = '';
		if (! is_null($m) && ! empty($m)) {
			$monstre = $this->getFicheMonstre($m);
			$str_monstre =<<<EOF
Le monstre <b>{$monstre['nom']}</b> ({$monstre['count']} fiches) a les caractéristiques suivantes :
<ul>
<li>Niveau : entre {$monstre['niveau_min']} et {$monstre['niveau_max']}</li>
<li>Point de vie max : entre {$monstre['pv_max_min']} et {$monstre['pv_max_max']}</li>
<li>Vue : entre {$monstre['vue_min']} et {$monstre['vue_max']}</li>
<li>Force : entre {$monstre['force_min']} et {$monstre['force_max']} {$monstre['force_unite']}</li>
<li>Agilité : entre {$monstre['agilite_min']} et {$monstre['agilite_max']} {$monstre['agilite_unite']}</li>
<li>Sagesse : entre {$monstre['sagesse_min']} et {$monstre['sagesse_max']} {$monstre['sagesse_unite']}</li>
<li>Vigueur : entre {$monstre['vigueur_min']} et {$monstre['vigueur_max']} {$monstre['vigueur_unite']}</li>
<li>Régénération : entre {$monstre['regeneration_min']} et {$monstre['regeneration_max']}</li>
<li>Armure : entre {$monstre['armure_min']} et {$monstre['armure_max']}</li>
<li>Vous avez effectué cette compétence à une distance de {$monstre['distance']} de la cible.</li>
</ul>
EOF;
		}
		
		$content = <<<EOF
<div id="monstre_liste">
	<ul>{$liste}</ul>
</div>

<div id="monstre_detail">
{$str_monstre}
</div>

<div id="monstre_saisie">
<p>Collez le r&eacute;sultat de votre identification ici : </p>
	<form action="index.php" method="POST">
	<input type="hidden" name="action" value="bestiaire_submit" />
	<textarea id="desc" name="desc"></textarea><br />
	<input type="submit" value="Enregistrer" />
	</form>
</div>
EOF;
		$this->html_content = $content;
	}
	
	/*
	Insère en base le resultat d'une identifiation
	*/
	private function bestiaireParse() {
		$desc = ((isset($_REQUEST['desc'])) ? $_REQUEST['desc'] : null);
		$desc = trim($desc);
		
		if (is_null($desc) || empty($desc)) {
			$this->html_message = "La description est vide.";
			$this->bestiaire();
			return;
		}
		
		$lines = explode("\n", $desc);
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
			if (preg_match("/Vue : entre (\d+) et (\d+)/", $str, $match) == 1) {
				$monstre['vue_min'] = trim($match[1]);
				$monstre['vue_max'] = trim($match[2]);
				$query_keys[] = 'vue_min, vue_max';
				$query_values[] = $match[1].", ".$match[2];
				continue;
			}
			if (preg_match("/Force : entre (\d+) et (\d+) (D\d+)/", $str, $match) == 1) {
				$monstre['force_min'] = trim($match[1]);
				$monstre['force_max'] = trim($match[2]);
				$monstre['force_unite'] = trim($match[3]);
				$query_keys[] = 'force_min, force_max, force_unite';
				$query_values[] = $match[1].", ".$match[2].", '".mysql_real_escape_string($match[3])."'";
				continue;
			}
			if (preg_match("/Agilit.+ : entre (\d+) et (\d+) (D\d+)/", $str, $match) == 1) {
				$monstre['agilite_min'] = trim($match[1]);
				$monstre['agilite_max'] = trim($match[2]);
				$monstre['agilite_unite'] = trim($match[3]);
				$query_keys[] = 'agilite_min, agilite_max, agilite_unite';
				$query_values[] = $match[1].", ".$match[2].", '".mysql_real_escape_string($match[3])."'";
				continue;
			}
			if (preg_match("/Sagesse : entre (\d+) et (\d+) (D\d+)/", $str, $match) == 1) {
				$monstre['sagesse_min'] = trim($match[1]);
				$monstre['sagesse_max'] = trim($match[2]);
				$monstre['sagesse_unite'] = trim($match[3]);
				$query_keys[] = 'sagesse_min, sagesse_max, sagesse_unite';
				$query_values[] = $match[1].", ".$match[2].", '".mysql_real_escape_string($match[3])."'";
				continue;
			}
			if (preg_match("/Vigueur : entre (\d+) et (\d+) (D\d+)/", $str, $match) == 1) {
				$monstre['vigueur_min'] = trim($match[1]);
				$monstre['vigueur_max'] = trim($match[2]);
				$monstre['vigueur_unite'] = trim($match[3]);
				$query_keys[] = 'vigueur_min, vigueur_max, vigueur_unite';
				$query_values[] = $match[1].", ".$match[2].", '".mysql_real_escape_string($match[3])."'";
				continue;
			}
			if (preg_match("/R.+g.+n.+ration : entre (\d+) et (\d+)/", $str, $match) == 1) {
				$monstre['regeneration_min'] = trim($match[1]);
				$monstre['regeneration_max'] = trim($match[2]);
				$query_keys[] = 'regeneration_min, regeneration_max';
				$query_values[] = $match[1].", ".$match[2];
				continue;
			}
			if (preg_match("/Armure : entre (\d+) et (\d+)/", $str, $match) == 1) {
				$monstre['armure_min'] = trim($match[1]);
				$monstre['armure_max'] = trim($match[2]);
				$query_keys[] = 'armure_min, armure_max';
				$query_values[] = $match[1].", ".$match[2];
				continue;
			}
			if (preg_match("/Vous avez .* une distance de (\d+) de la cible/", $str, $match) == 1) {
				$monstre['distance'] = trim($match[1]);
				$query_keys[] = 'distance';
				$query_values[] = $match[1];
				continue;
			}
		}
		// si on a un nom on insère
		if (in_array('nom, id', $query_keys)) {
			$query = "INSERT INTO fiche_monstre(".
				implode(',', $query_keys).
				") VALUES(".
				implode(',', $query_values).
				");";
			mysql_query($query, $this->db);
		}
		// sinon erreur
		else {
			$this->html_message = "Ce n'est pas une description...";
		}
		$this->bestiaire();
	}
	
	/*
	Retourne la liste des noms des monstres connus
	*/
	private function getListeMonstres() {
		if (isset($this->monstres)) {
			return $this->monstres;
		}
		$this->monstres = array();
		$query = "SELECT distinct nom
			FROM fiche_monstre
			ORDER BY nom ASC;";
		$res = mysql_query($query, $this->db);
		while ($row = mysql_fetch_assoc($res)) {
			$this->monstres[] = $row['nom'];
		}
		mysql_free_result($res);
		
		return $this->monstres;
	}
	
	/*
	Retourne une fiche "type" pour un monstre
	*/
	private function getFicheMonstre($nom) {
		$query = "SELECT nom,
			floor(AVG(niveau_min)) as niveau_min, floor(AVG(niveau_max)) as niveau_max,
			floor(AVG(pv_max_min)) as pv_max_min, floor(AVG(pv_max_max)) as pv_max_max,
			floor(AVG(vue_min)) as vue_min, floor(AVG(vue_max)) as vue_max,
			floor(AVG(force_min)) as force_min, floor(AVG(force_max)) as force_max,force_unite,
			floor(AVG(agilite_min)) as agilite_min, floor(AVG(agilite_max)) as agilite_max,agilite_unite,
			floor(AVG(sagesse_min)) as sagesse_min, floor(AVG(sagesse_max)) as sagesse_max,sagesse_unite,
			floor(AVG(vigueur_min)) as vigueur_min, floor(AVG(vigueur_max)) as vigueur_max,vigueur_unite,
			floor(AVG(regeneration_min)) as regeneration_min, floor(AVG(regeneration_max)) as regeneration_max,
			floor(AVG(armure_min)) as armure_min, floor(AVG(armure_max)) as armure_max,
			floor(AVG(distance)) as distance,
			count(id) as count
			FROM fiche_monstre
			WHERE nom='".mysql_real_escape_string(utf8_encode(urldecode($nom)))."'
			GROUP BY nom";
		$res = mysql_query($query, $this->db);
		$monstre = array();
		if ($row = mysql_fetch_assoc($res)) {
			$monstre = $row;
		}
		mysql_free_result($res);
		return $monstre;
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
	margin: 1em 2em;
}
#message {
	background-color: #EF7E68;
	text-align: center;
}

#position {
	margin: 1em 0 0 0;
}
#dist {
	float: left;
	padding: 0 2em;
}
.tab_position {
	float: left;
	border-collapse: collapse;
	border-color: #5D8231;
	margin: 0 1em 1em 0;
}
.tab_position td, #tab_position th {
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

#monstre_liste {
	float: left;
	padding: 0 2em 0 0;
}
#monstre_detail {
	float: left;
	padding: 0 2em 0 0;
	max-width: 600px;
}
#monstre_saisie {
	float: left;
}
#monstre_saisie textarea {
	width: 250px;
	height: 300px;
	overflow: auto;
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
		<li><a href="index.php?action=bestiaire">Bestiaire</a></li>
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
