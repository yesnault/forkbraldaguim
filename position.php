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

if (! isset($_SESSION)) session_start();

require("application.php");

class Position extends Application {
	
	public function __construct() {
		parent::__construct();
	}

	/*
	Controleur à 2 sous.
	On appelle la méthode 'qui_va_bien' en fonction de l'action.
	*/
	public function actionParse() {
		$this->action = '';
		if (isset($_REQUEST['action'])) {
			$this->action = $_REQUEST['action'];
		}
		
		switch($this->action) {
			case 'home':
				$this->home();
				break;
			case 'position':
			default:
				if (!$this->logged) break;
				$this->position();
				break;
		}
	}

	public function getHtmlScript() {
		$checkbox_states = (isset($_REQUEST['checkbox_states'])) ?  $_REQUEST['checkbox_states'] : '';
		$str =<<<EOF
// initialisation :
// * charge l'etat des checkbox
// * masque/affiche les calques en fontion des checkbox
window.onload = function () {
	checkbox_states = '{$checkbox_states}';
	checkbox_list = ["chk_fond", "chk_brouillard", "chk_joueur", "chk_lieumythique", "chk_lieustandard", "chk_nid", "chk_buisson", "chk_legende"];
	if (checkbox_states != '') {
		for (var i=0; i<checkbox_list.length; i++) {
			document.getElementById(checkbox_list[i]).checked = (checkbox_states.charAt(i) == "1");
			checkbox_list[i].match(/[^_]+_(.*)/);
			var id_layer = "map_"+RegExp.$1;
			document.getElementById(id_layer).style.visibility = (checkbox_states.charAt(i) == "1") ? 'visible' : 'hidden';
		}
		update_map_ctrl_link();
	}
	initOnClick();
}

// Ajoute les listeners de click sur les checkbox
function initOnClick() {
	if (! window.ActiveXObject) {
		// show/hide map layer
		for (var i=0; i<checkbox_list.length; i++) {
			document.getElementById(checkbox_list[i]).addEventListener('click', show_hide_layer, false);
		}
	}
	else {
		for (var i=0; i<checkbox_list.length; i++) {
			document.getElementById(checkbox_list[i]).onclick = show_hide_layer;
		}
	}
}

// Affiche/masque le calque correspondant à la checkbox emettant l'evenement
// Met à jour la variable checkbox_states
function show_hide_layer() {
	this.id.match(/[^_]+_(.*)/);
	var id_layer = "map_"+RegExp.$1;
	if (this.checked) {
		document.getElementById(id_layer).style.visibility = 'visible';
	}
	else {
		document.getElementById(id_layer).style.visibility = 'hidden';
	}
	checkbox_states = '';
	for (var i=0; i<checkbox_list.length; i++) {
		checkbox_states += (document.getElementById(checkbox_list[i]).checked) ? "1" : "0";
	}
	update_map_ctrl_link();
}

// Met à jour tous les liens de navigation pour se souvenir de ce qui est affiché/masqué
function update_map_ctrl_link() {
	var lstA = document.getElementsByTagName("A");
	for (var i=0; i<lstA.length; i++) {
		if (lstA[i].className.indexOf("map_ctrl_link") != -1) {
			lstA[i].href = lstA[i].href.replace(/checkbox_states=\d*/,"checkbox_states=" + checkbox_states);
		}
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
EOF;
		return $this->html_script.$str;
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
		
		$zoom = $x = $y = 0;
		
		// extraction du zoom
		$zoom = (array_key_exists('zoom', $_REQUEST) && is_numeric($_REQUEST['zoom'])) ? $_REQUEST['zoom'] : DEF_ZOOM;
		if ($zoom < 1 ) $zoom = 1;
		
		
		// pour chaque braldun on affiche :
		// un lien pour centrer la carte sur sa position,
		// son nom, sa position
		$tab_bra = '';
		$img = '<img src="img/centrer.png" />';
		foreach ($bralduns as $braldun) {
			$update_link = '';
			// si c'est le joueur connecté, on affiche un lien pour la maj
			// et on retiens sa position pour le centrage par defaut
			if ($_SESSION['bra_num'] == $braldun['braldahim_id']) {
				$x = $braldun['x'];
				$y = $braldun['y'];
			}
			$user_pos = $this->getMoveControl($zoom, $braldun['x'], $braldun['y'], $img);
			$tab_bra .=<<<EOF
<tr>
	<td>{$user_pos}	<a href="http://jeu.braldahim.com/voir/braldun/?braldun={$braldun['braldahim_id']}&direct=profil" target="_blank">{$braldun['prenom']} {$braldun['nom']}</a> {$update_link}</td>
	<td>{$braldun['x']}</td>
	<td>{$braldun['y']}</td>
</tr>
EOF;
		}
		
		// extraction de x et y et construction de l'url de positionnement
		$y = (array_key_exists('y', $_REQUEST) && is_numeric($_REQUEST['y'])) ? $_REQUEST['y'] : $y;
		$x = (array_key_exists('x', $_REQUEST) && is_numeric($_REQUEST['x'])) ? $_REQUEST['x'] : $x;
		$url_append = "zoom=$zoom&y=$y&x=$x";
		
		// pour chaque ville on affiche :
		// un lien pour centrer la carte sur sa position,
		// son nom
		$tab_ville = '';
		foreach ($villes as $v) {
			$v_pos = $this->getMoveControl($zoom, $v['x'], $v['y'], $img);
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
		
		$ctrl_zoom_p = $this->getMoveControl($zoom-1, $x, $y, '<img src="img/zoomplus.png" />');
		$ctrl_zoom_m = $this->getMoveControl($zoom+1, $x, $y, '<img src="img/zoommoins.png"/>');
		$ctrl_haut = $this->getMoveControl($zoom, $x, $y+$move_delta, '<img src="img/boussoleN.png" />');
		$ctrl_bas = $this->getMoveControl($zoom, $x, $y-$move_delta, '<img src="img/boussoleS.png" />');
		$ctrl_gauche = $this->getMoveControl($zoom, $x-$move_delta, $y, '<img src="img/boussoleO.png" />');
		$ctrl_droite = $this->getMoveControl($zoom, $x+$move_delta, $y, '<img src="img/boussoleE.png" />');
		
		// construction de l'affichage de la page
		$content =<<<EOF

<div id="map_wrapper">
	<div class="map_item" id="map_fond"><img src="map.php?type=fond&$url_append" /></div>
	<div class="map_item" id="map_brouillard"><img src="map.php?type=brouillard&$url_append" /></div>
	<div class="map_item" id="map_lieumythique"><img src="map.php?type=lieumythique&$url_append" /></div>
	<div class="map_item" id="map_lieustandard"><img src="map.php?type=lieustandard&$url_append" /></div>
	<div class="map_item" id="map_nid"><img src="map.php?type=nid&$url_append" /></div>
	<div class="map_item" id="map_buisson"><img src="map.php?type=buisson&$url_append" /></div>
	<div class="map_item" id="map_joueur"><img src="map.php?type=joueur&$url_append" /></div>
	<div class="map_item" id="map_legende"><img src="map.php?type=legende&$url_append" /></div>
	
	<div id="map_info">
		<p>Affichage des informations :</p>
		
		<input type="checkbox" id="chk_fond" checked="checked" />
		<label for="chk_fond">Fond de carte</label>
		
		<br/><input type="checkbox" id="chk_brouillard" checked="checked" />
		<label for="chk_brouillard">Brouillard</label>
		
		<br/><input type="checkbox" id="chk_joueur" checked="checked" />
		<label for="chk_joueur">Membres</label>
		
		<br/><input type="checkbox" id="chk_lieumythique" checked="checked" />
		<label for="chk_lieumythique">Lieux mythiques et lieux de qu&ecirc;tes </label>
		
		<br/><input type="checkbox" id="chk_lieustandard" />
		<label for="chk_lieustandard">Lieux standard</label>
		
		<br/><input type="checkbox" id="chk_nid" checked="checked" />
		<label for="chk_nid">Nids</label>

		<br/><input type="checkbox" id="chk_buisson" />
		<label for="chk_buisson">Buissons</label>

		<br/><input type="checkbox" id="chk_legende" />
		<label for="chk_legende">Legende</label>
		<br />
		<table id="map_control">
			<tr><td class="map_zoom">$ctrl_zoom_m</td><td></td><td class="map_zoom">$ctrl_zoom_p</td></tr>
			<tr><td></td><td>$ctrl_haut</td><td></td></tr>
			<tr><td>$ctrl_gauche</td><td><img src="img/boussoleC.png" /></td><td>$ctrl_droite</td></tr>
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
<a class="map_ctrl_link" href="position.php?action=position&zoom={$zoom}&x={$x}&y={$y}&checkbox_states=">{$inner}</a>
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
		$query = "SELECT braldahim_id, prenom, nom, x, y
		FROM ".DB_PREFIX."user
		WHERE restricted_password IS NOT NULL
		ORDER BY braldahim_id ASC;";
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
			FROM ".DB_PREFIX."ville
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
}

$app = new Position();
require("template.php");
?>
