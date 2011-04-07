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

require("application.php");

class Simulateur extends Application {
	public function __construct() {
		parent::__construct();
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

		switch($this->action) {
			case 'simulation':
				if (!$this->logged) break;
				$this->simulation();
				break;
			case 'home':
			default:
				$this->home();
				break;
		}
	}

	/*
	Retourne le braldun courant
	*/
	private function getCurrentProfil() {
		$p = null;
		$query = "SELECT *
			FROM profil p
			WHERE p.idBraldun = {$_SESSION['bra_num']};";
		$res = mysql_query($query, $this->db);
		if (mysql_num_rows($res) == 1) {
			$p = mysql_fetch_assoc($res);
		}
		mysql_free_result($res);
		return $p;
	}

	/*
	Retourne les competences du braldun courant
	*/
	private function getCurrentCompetences() {
		$p = array();
		$query = "SELECT nom_systeme
			FROM competence
			WHERE idBraldun = {$_SESSION['bra_num']}
			AND typeCompetence = 'commun';";
		$res = mysql_query($query, $this->db);
		while ($row = mysql_fetch_assoc($res)) {
			$p[] = $row['nom_systeme'];
		}
		mysql_free_result($res);
		return $p;
	}

	public function getHtmlScript() {
		$str =<<<EOF
window.onload = function () {
	if (! window.ActiveXObject) {
		var lst = ["niveau", "for_niv", "agi_niv", "vig_niv", "sag_niv", "connaissancebralduns", "connaissancemonstres", "identifierrune", "pister", "provoquer", "rechercher", "recycler", "tirerencourant"];
		for (elt in lst) {
			document.getElementById(lst[elt]).addEventListener('change', update_all, false);
		}
		var tab = document.getElementsByTagName('input');
		for (var i=0; i<tab.length; i++) {
			if (tab[i].type == 'radio') {
				tab[i].addEventListener('click', update_all, false);
			}
		}
		document.getElementById("niveau_moins").addEventListener('click', plus_moins, false);
		document.getElementById("niveau_plus").addEventListener('click', plus_moins, false);
		document.getElementById("for_niv_moins").addEventListener('click', plus_moins, false);
		document.getElementById("for_niv_plus").addEventListener('click', plus_moins, false);
		document.getElementById("agi_niv_moins").addEventListener('click', plus_moins, false);
		document.getElementById("agi_niv_plus").addEventListener('click', plus_moins, false);
		document.getElementById("vig_niv_moins").addEventListener('click', plus_moins, false);
		document.getElementById("vig_niv_plus").addEventListener('click', plus_moins, false);
		document.getElementById("sag_niv_moins").addEventListener('click', plus_moins, false);
		document.getElementById("sag_niv_plus").addEventListener('click', plus_moins, false);
	}
	else {
		var lst = ["niveau", "for_niv", "agi_niv", "vig_niv", "sag_niv", "connaissancebralduns", "connaissancemonstres", "identifierrune", "pister", "provoquer", "rechercher", "recycler", "tirerencourant"];
		for (elt in lst) {
			document.getElementById(elt).onchange = update_all;
		}
		document.getElementById("niveau_moins").onclick = plus_moins;
		document.getElementById("niveau_plus").onclick = plus_moins;
		document.getElementById("for_niv_moins").onclick = plus_moins;
		document.getElementById("for_niv_plus").onclick = plus_moins;
		document.getElementById("agi_niv_moins").onclick = plus_moins;
		document.getElementById("agi_niv_plus").onclick = plus_moins;
		document.getElementById("vig_niv_moins").onclick = plus_moins;
		document.getElementById("vig_niv_plus").onclick = plus_moins;
		document.getElementById("sag_niv_moins").onclick = plus_moins;
		document.getElementById("sag_niv_plus").onclick = plus_moins;
	}
	update_all();
}

function getTitreByNiveau(niveau) {
	var str = 'n' + niveau;
	var tab = document.getElementsByTagName('input');
	for (var i=0; i<tab.length; i++) {
		if (tab[i].type == 'radio' && tab[i].name == str && tab[i].checked) {
			return tab[i].value;
		}
	}
	return "none;"
}
function plus_moins() {
	this.id.match(/(.+)_(.*)/);
	var sujet = RegExp.$1;
	var action = RegExp.$2;
	var v = parseInt(document.getElementById(sujet).value);
	if (action == 'moins') {
		v--;
	}
	else if (action == 'plus') {
		v++;
	}
	document.getElementById(sujet).value = v;
	update_all();
}

function update_all() {
	var niv = parseInt(document.getElementById('niveau').value);
	var for_niv = parseInt(document.getElementById('for_niv').value);
	var agi_niv = parseInt(document.getElementById('agi_niv').value);
	var vig_niv = parseInt(document.getElementById('vig_niv').value);
	var sag_niv = parseInt(document.getElementById('sag_niv').value);
	
	var competences = {
		"connaissancebralduns" :  10,
		"connaissancemonstres" :  10,
		"identifierrune" :  4,
		"pister" :  10,
		"provoquer" :  10,
		"rechercher" :  50,
		"recycler" :  10,
		"tirerencourant" :  50
	};
	
	var arr_pi = [0, 1];
	var arr_pi_cum = [0, 1];
	for (var i=2; i<25; i++) {
		arr_pi[i] = i * (i - 1);
		arr_pi_cum[i] = arr_pi_cum[i-1] + arr_pi[i];
	}
	
	var for_spe = agi_spe = vig_spe = sag_spe = 0;
	var reg = arm = dla = pv = poids = def = deg = 0;
	
	reg = parseInt(vig_niv/4) + 1;
	arm = parseInt((for_niv + vig_niv)/5) + 2;
	dla = 1440 - 10 * sag_niv;
	pv = 40 + vig_niv * 10;
	poids = 3 + 2 * for_niv;
	
	var for_des = for_niv + 3;
	var agi_des = agi_niv + 3;
	var vig_des = vig_niv + 3;
	var sag_des = sag_niv + 3;
	
	var delta_for = delta_agi = delta_vig = delta_sag = 0;
	for (var k=10; k<=40; k+=10) {
		var tmp = getTitreByNiveau(k);
		if (tmp == 'Force') {delta_for++;}
		else if (tmp == 'Agilite') {delta_agi++;}
		else if (tmp == 'Vigueur') {delta_vig++;}
		else if (tmp == 'Sagesse') {delta_sag++;}

	}
	var for_pi = arr_pi_cum[for_niv - delta_for];
	var agi_pi = arr_pi_cum[agi_niv - delta_agi];
	var vig_pi = arr_pi_cum[vig_niv - delta_vig];
	var sag_pi = arr_pi_cum[sag_niv - delta_sag];
	var pi_totaux = ((niv * (niv + 1)) / 2) * 5;
	
	if (for_niv >= 13) {for_spe += 2;}
	if (for_niv >= 16) {for_spe += 3; deg += 3;}
	if (for_niv >= 20) {deg += 13;}
	
	if (agi_niv >= 13) {agi_spe += 2;}
	if (agi_niv >= 16) {agi_spe += 3; def += 3;}
	if (agi_niv >= 20) {def += 13;}
	
	if (vig_niv >= 13) {vig_spe += 2;}
	if (vig_niv >= 16) {vig_spe += 3; reg += 3;}
	if (vig_niv >= 20) {reg += 13;}
	
	if (sag_niv >= 13) {sag_spe += 2;}
	if (sag_niv >= 16) {sag_spe += 3; dla -= 20;}
	if (sag_niv >= 20) {dla -= 100;}
	
	document.getElementById('for_des').innerHTML = for_des + " D6";
	document.getElementById('agi_des').innerHTML = agi_des + " D6";
	document.getElementById('vig_des').innerHTML = vig_des + " D6";
	document.getElementById('sag_des').innerHTML = sag_des + " D6";
	
	document.getElementById('for_pi').innerHTML = for_pi;
	document.getElementById('agi_pi').innerHTML = agi_pi;
	document.getElementById('vig_pi').innerHTML = vig_pi;
	document.getElementById('sag_pi').innerHTML = sag_pi;
	
	document.getElementById('for_spe').innerHTML = '+ ' + for_spe;
	document.getElementById('agi_spe').innerHTML = '+ ' + agi_spe;
	document.getElementById('vig_spe').innerHTML = '+ ' + vig_spe;
	document.getElementById('sag_spe').innerHTML = '+ ' + sag_spe;
	
	document.getElementById('reg').innerHTML = reg + " D10";
	document.getElementById('arm').innerHTML = arm;
	document.getElementById('dla').innerHTML = time_convert(dla);
	document.getElementById('pv').innerHTML = pv;
	document.getElementById('poids').innerHTML = poids
	document.getElementById('def').innerHTML = def;
	document.getElementById('deg').innerHTML = deg;
	
	document.getElementById('for_bonus').innerHTML = parseInt(for_niv / 3);
	document.getElementById('agi_bonus').innerHTML = parseInt(agi_niv / 3);
	document.getElementById('vig_bonus').innerHTML = parseInt(vig_niv / 3);
	document.getElementById('sag_bonus').innerHTML = parseInt(sag_niv / 3);
	
	var pi_restants = pi_totaux - for_pi - agi_pi - vig_pi - sag_pi;
	for (c in competences) {
		if (document.getElementById(c).checked) {
			pi_restants -= competences[c];
		}
	}
	document.getElementById('pi_totaux').innerHTML = pi_totaux;
	document.getElementById('pi_restants').innerHTML = pi_restants;
	
	document.getElementById('cpt9').checked = (sag_niv >= 13);
	document.getElementById('cpt10').checked = (sag_niv >= 13);
	document.getElementById('cpt11').checked = (sag_niv >= 13);
}

function time_convert(m) {
	var h = parseInt(m / 60);
	m -= 60 * h;
	return h + "h" + m;
}
EOF;
		return $this->html_script.$str;
	}

	 private function simulation() {
	 	$p = $this->getCurrentProfil();
		$lst_cur_cpt = $this->getCurrentCompetences();
		$nom_cpt = array("connaissancebralduns", "connaissancemonstres", "identifierrune", "pister", "provoquer", "rechercher", "recycler", "tirerencourant");
		$lst_cpt = array();
		foreach ($nom_cpt as $k) {
			$lst_cpt[$k] = (in_array($k, $lst_cur_cpt)) ? 'checked="checked"' : '';
		}

		$content=<<<EOF
<div class="colonne">
<table>
	<tr>
		<td>Niveau</td>
		<td>
			<input id="niveau" type="text" value="{$p['niveau']}">
			<input type="button" value="-" id="niveau_moins">
			<input type="button" value="+" id="niveau_plus">
		</td>
	</tr>
	<tr><td>PI totaux</td><td><span id="pi_totaux"></span></td></tr>
	<tr><td>PI restants</td><td><span id="pi_restants"></span></td></tr>
</table>

<table>
	<tr><th></th><th>Niveau</th><th>PI</th><th>Dés</th><th>Spé</th><th>Bonus/rune</th></tr>
	<tr>
		<td>Force</td>
		<td>
			<input id="for_niv" type="text" value="{$p['nivForce']}">
			<input type="button" value="-" id="for_niv_moins">
			<input type="button" value="+" id="for_niv_plus">
		</td>
		<td><span id="for_pi">0</span></td>
		<td><span id="for_des">0</span></td>
		<td><span id="for_spe">0</span></td>
		<td><span id="for_bonus">0</span></td>
	</tr>
	<tr>
		<td>Agilité</td>
		<td>
			<input id="agi_niv" type="text" value="{$p['nivAgilite']}">
			<input type="button" value="-" id="agi_niv_moins">
			<input type="button" value="+" id="agi_niv_plus">
		</td>
		<td><span id="agi_pi">0</span></td>
		<td><span id="agi_des">0</span></td>
		<td><span id="agi_spe">0</span></td>
		<td><span id="agi_bonus">0</span></td>
	</tr>
	<tr>
		<td>Vigueur</td>
		<td>
			<input id="vig_niv" type="text" value="{$p['nivVigueur']}">
			<input type="button" value="-" id="vig_niv_moins">
			<input type="button" value="+" id="vig_niv_plus">
		</td>
		<td><span id="vig_pi">0</span></td>
		<td><span id="vig_des">0</span></td>
		<td><span id="vig_spe">0</span></td>
		<td><span id="vig_bonus">0</span></td>
	</tr>
	<tr>
		<td>Sagesse</td>
		<td>
			<input id="sag_niv" type="text" value="{$p['nivSagesse']}">
			<input type="button" value="-" id="sag_niv_moins">
			<input type="button" value="+" id="sag_niv_plus">
		</td>
		<td><span id="sag_pi">0</span></td>
		<td><span id="sag_des">0</span></td>
		<td><span id="sag_spe">0</span></td>
		<td><span id="sag_bonus">0</span></td>
	</tr>
</table>
<table>
	<tr><th>Carac. 2ndaire</th><th>Valeur</th><th>Influenc&eacute;e par</th>
	<tr><td>Reg</td><td><span id="reg">0</span></td><td>vigueur</td></tr>
	<tr><td>Arm nat</td><td><span id="arm">0</span></td><td>force, vigueur</td></tr>
	<tr><td>DLA</td><td><span id="dla">0</span></td><td>sagesse</td></tr>
	<tr><td>PV</td><td><span id="pv">0</span></td><td>vigueur</td></tr>
	<tr><td>Poids</td><td><span id="poids">0</span></td><td>force</td></tr>
	<tr><td>BNS Def</td><td><span id="def">0</span></td><td>agilite</td></tr>
	<tr><td>BNS Deg</td><td><span id="deg">0</span></td><td>force</td></tr>
</table>
</div>

<div class="colonne">
<table>
<tr><th></th><th>Comp&eacute;tence</th><th>Co&ucirc;t</th></tr>
<tr><td><input id="connaissancebralduns" type="checkbox" {$lst_cpt['connaissancebralduns']} ></td><td><label for="connaissancebralduns">Connaissance des Braldûns</label></td><td>10</td></tr>
<tr><td><input id="connaissancemonstres" type="checkbox" {$lst_cpt['connaissancemonstres']}></td><td><label for="connaissancemonstres">Connaissance des Monstres</label></td><td>10</td></tr>
<tr><td><input id="identifierrune" type="checkbox" {$lst_cpt['identifierrune']} ></td><td><label for="identifierrune">Identification des runes</label></td><td>4</td></tr>
<tr><td><input id="pister" type="checkbox" {$lst_cpt['pister']} ></td><td><label for="pister">Pister</label></td><td>10</td></tr>
<tr><td><input id="provoquer" type="checkbox" {$lst_cpt['provoquer']} ></td><td><label for="provoquer">Provoquer</label></td><td>10</td></tr>
<tr><td><input id="rechercher" type="checkbox" {$lst_cpt['rechercher']} ></td><td><label for="rechercher">Recherche de mots runiques</label></td><td>50</td></tr>
<tr><td><input id="recycler" type="checkbox" {$lst_cpt['recycler']} ></td><td><label for="recycler">Recyclage</label></td><td>10</td></tr>
<tr><td><input id="tirerencourant" type="checkbox" {$lst_cpt['tirerencourant']} ></td><td><label for="tirerencourant">Tirer en courant</label></td><td>50</td></tr>

<tr><td><input id="cpt9" type="checkbox" disabled="disabled"></td><td>Dissuader</td><td>A partir du niveau 13 de sagesse</td></tr>
<tr><td><input id="cpt10" type="checkbox" disabled="disabled"></td><td>Psychologie</td><td>A partir du niveau 13 de sagesse</td></tr>
<tr><td><input id="cpt11" type="checkbox" disabled="disabled"></td><td>Réanimer</td><td>A partir du niveau 13 de sagesse</td></tr>
</table>

<table>
<tr><td>Niveau</td><td>Force</td><td>Agilit&eacute;</td><td>Vigueur</td><td>Sagesse</td><td>Aucun</td></tr>
<form id="titre">
<tr>
	<td>10</td>
	<td><input type="radio" name="n10" value="Force" /></td>
	<td><input type="radio" name="n10" value="Agilite" /></td>
	<td><input type="radio" name="n10" value="Vigueur" /></td>
	<td><input type="radio" name="n10" value="Sagesse" /></td>
	<td><input type="radio" name="n10" value="none" checked="checked"/></td>
</tr>
<tr>
	<td>20</td>
	<td><input type="radio" name="n20" value="Force" /></td>
	<td><input type="radio" name="n20" value="Agilite" /></td>
	<td><input type="radio" name="n20" value="Vigueur" /></td>
	<td><input type="radio" name="n20" value="Sagesse" /></td>
	<td><input type="radio" name="n20" value="none" checked="checked"/></td>
</tr>
<tr>
	<td>30</td>
	<td><input type="radio" name="n30" value="Force" /></td>
	<td><input type="radio" name="n30" value="Agilite" /></td>
	<td><input type="radio" name="n30" value="Vigueur" /></td>
	<td><input type="radio" name="n30" value="Sagesse" /></td>
	<td><input type="radio" name="n30" value="none" checked="checked"/></td>
</tr>
<tr>
	<td>40</td>
	<td><input type="radio" name="n40" value="Force" /></td>
	<td><input type="radio" name="n40" value="Agilite" /></td>
	<td><input type="radio" name="n40" value="Vigueur" /></td>
	<td><input type="radio" name="n40" value="Sagesse" /></td>
	<td><input type="radio" name="n40" value="none" checked="checked"/></td>
</tr>
</form>
</table>
</div>
EOF;
		$this->html_content = $content;
	}
}

$app = new Simulateur();
require("template.php");
?>
