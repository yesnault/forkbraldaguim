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

session_start();

require("application.php");

class Profil extends Application {
	
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
			case 'allprofils':
				if (!$this->logged) break;
				$this->showAllProfils();
				break;
			case 'home':
			default:
				$this->home();
				break;
		}
	}

	/*
	Affiche la liste des profils
	*/
	private function showAllProfils() {
		// liste des profils
		$this->getAllProfils();

		$now = time();
		
		$str =<<<EOF
<input type="button" value="R&eacute;duire" id="collapse" />
<table class="profil" border="1">
<tr class="collapsable">
	<th>Informations Tour</th>
	<th>Sante<br/>Poids<br/>Experience</th>
	<th>Caracteristiques</th>
	<th>Armure<br/>Combat</th>
	<th>Palmares</th>
	<th>Soule</th>
	<th>Comp&eacute;tences</th>
</tr>
EOF;
		// https://github.com/braldahim/braldahim/blob/master/braldahim/application/views/scripts/interface/profil.phtml
		foreach ($this->profils as $profil) {
			$px_max = ($profil['niveau'] + 2) * 5 - 5;
			$pv_max = $profil['nivVigueur'] * 10 + 40;

			$agi_des = ($profil['nivAgilite'] + 3).'&nbsp;D6';
			$agi_bm = $profil['bmAgilite'] + $profil['bmBddfAgilite'];

			$for_des = ($profil['nivForce'] + 3).'&nbsp;D6';
			$for_bm = $profil['bmForce'] + $profil['bmBddfForce'];

			$vig_des = ($profil['nivVigueur'] + 3).'&nbsp;D6';
			$vig_bm = $profil['bmVigueur'] + $profil['bmBddfVigueur'];

			$sag_des = ($profil['nivSagesse'] + 3).'&nbsp;D6';
			$sag_bm = $profil['bmSagesse'] + $profil['bmBddfSagesse'];

			$pv_pos = 100 - ($profil['PvRestant']/$pv_max)* 100;
			$bdf_pos = 100 - $profil['bbdf'];
			$px_pos = 100 - ($profil['pxPerso']/$px_max)* 100;

			$img_data = $this->timeline($now, $profil);

			$competences = $this->drawCompetences($profil['idBraldun']);

/*

	Début&nbsp;:&nbsp;{$profil['dateDebutTour']}<br/>
	R&eacute;veil&nbsp;:&nbsp;{$profil['dateFinLatence']}<br/>
	Activit&eacute;&nbsp;:&nbsp;{$profil['dateDebutCumul']}<br/>
	Fin&nbsp;:&nbsp;{$profil['dateFinTour']}<br/>
	DLA&nbsp;:&nbsp;{$profil['DLA']}<br/>
*/
			$str .=<<<EOF
<tr>
	<td colspan="2" class="p_nom">{$profil['prenom']} {$profil['nom']}&nbsp;({$profil['idBraldun']})</td>
	<td colspan="5">
		<table class="bar">
		<tr>
			<td class="titre">PV</td>
			<td><span class="bar" style="background-position: -{$pv_pos}px 0px"></span></td>
			<td class="titre">BdF</td>
			<td><span class="bar" style="background-position: -{$bdf_pos}px 0px"></span></td>
			<td class="titre">PX</td>
			<td><span class="bar" style="background-position: -{$px_pos}px 0px"></span></td>
		</tr>
		<tr>
			<td class="titre">Tour de jeu</td>
			<td colspan="5"><img src="data:image/gif;base64,{$img_data}" /></td>
		</tr>
		</table>
	</td>
</tr>
<tr class="collapsable">
	<td>
	PA Restant&nbsp;:&nbsp;{$profil['paRestant']}<br/>
	Durée de ce tour&nbsp;:&nbsp;{$profil['dureeCourantTour']}<br/>
	Durée du prochain&nbsp;:&nbsp;{$profil['DureeProchainTour']} {$profil['dureeBmTour']}<br/>
	MAJ du profil&nbsp;:&nbsp;{$profil['last_update']}<br/>
	Derni&egrave;re cnx&nbsp;:&nbsp;{$profil['last_login']}
	</td>

	<td>
	PV&nbsp;:&nbsp;{$profil['PvRestant']}&nbsp;/&nbsp;{$pv_max}&nbsp;+&nbsp;{$profil['bmPVmax']}<br/>
	Bdf&nbsp;:&nbsp;{$profil['bbdf']}%<br/>
	<br/>
	Poids&nbsp:&nbsp;{$profil['poidsTransporte']}&nbsp;/&nbsp;{$profil['poidsTransportable']}<br/>
	<br/>
	Niveau&nbsp;:&nbsp;{$profil['niveau']}<br/>
	PX Perso&nbsp;:&nbsp;{$profil['pxPerso']}&nbsp;/&nbsp;{$px_max}<br/>
	PX Commun&nbsp;:&nbsp;{$profil['pxCommun']}<br/>
	PI&nbsp;:&nbsp;{$profil['pi']}
	</td>

	<td class="tab">
		<table>
		<tr>
			<th></th>
			<th>Niv</th>
			<th>Jet</th>
			<th>BM</th>
		</tr>
		<tr>
			<td>AGI</td>
			<td>{$profil['nivAgilite']}</td>
			<td>{$agi_des}</td>
			<td>{$agi_bm}</td>
		</tr>
		<tr>
			<td>FOR</td>
			<td>{$profil['nivForce']}</td>
			<td>{$for_des}</td>
			<td>{$for_bm}</td>
		</tr>
		<tr>
			<td>VIG</td>
			<td>{$profil['nivVigueur']}</td>
			<td>{$vig_des}</td>
			<td>{$vig_bm}</td>
		</tr>
		<tr>
			<td>SAG</td>
			<td>{$profil['nivSagesse']}</td>
			<td>{$sag_des}</td>
			<td>{$sag_bm}</td>
		</tr>
		<tr>
			<td>Regen.</td>
			<td></td>
			<td>{$profil['regeneration']}&nbsp;D10</td>
			<td>{$profil['bmRegeneration']}</td>
		</tr>
		<tr>
			<td>Vue</td>
			<td></td>
			<td></td>
			<td>{$profil['bmVue']}</td>
		</tr>
		</table>
	</td>

	<td>
	Arm. naturelle&nbsp;:&nbsp;{$profil['armureNaturelle']}<br/>
	Arm. Equipement&nbsp;:&nbsp;{$profil['armureEquipement']}<br/>
	<br/>
	Attaque BM&nbsp;:&nbsp;{$profil['bmAttaque']}<br/>
	Defense BM&nbsp;:&nbsp;{$profil['bmDefense']}<br/>
	Degats BM&nbsp;:&nbsp;{$profil['bmDegat']}<br/>
	<br/>
	Engag&eacute; (ce tour)&nbsp;:&nbsp;{$profil['estEngage']}<br/>
	Engag&eacute; (prochain tour)&nbsp;:&nbsp;{$profil['estEngage']}<br/>
	Intangible&nbsp;:&nbsp;{$profil['estIntangible']}<br/>
	</td>
	
	<td>
	Nb KO&nbsp;:&nbsp;{$profil['nbKo']}<br/>
	Nb Kill monstres&nbsp;:&nbsp;{$profil['nbKill']}<br/>
	Nb KO Braldun&nbsp;:&nbsp;{$profil['nbKoBraldun']}<br/>
	</td>

	<td class="tab">
		<table>
		<tr>
			<th></th>
			<th>Plaquages</th>
		</tr>
		<tr>
			<td>Subis</td>
			<td>{$profil['nbPlaquagesSubis']}</td>
		</tr>
		<tr>
			<td>Effectu&eacute;</td>
			<td>{$profil['nbPlaquagesEffectues']}</td>
		</tr>
		</table>
	</td>

	<td class="tab">
		{$competences}
	</td>
</tr>
EOF;
		}
		$str .= "</table>";
		$this->html_content = $str;
	}
	
	public function getHtmlScript() {
		$str =<<<EOF
window.onload = function () {
	initOnClick();
}
function initOnClick() {
	if (! window.ActiveXObject) {
		document.getElementById("collapse").addEventListener('click', collapse_table, false);
	}
	else {
		document.getElementById("collapse").onclick = collapse_table;
	}
}
function collapse_table() {
	var trlist = document.getElementsByTagName('tr');
	var hide = true;
	for (i=0; i<trlist.length; i++) {
		if (trlist[i].className == "collapsable") {
			if (trlist[i].style.display == 'none') {
				trlist[i].style.display = '';
				hide = false;
			}
			else {
				trlist[i].style.display = 'none';
				hide = true;
			}
		}
	}
	document.getElementById("collapse").value = (hide) ? "Déployer" : "Réduire";
}
EOF;
		return $this->html_script.$str;
	}

	/*
	Retourne la liste des noms des monstres connus
	*/
	private function getAllProfils() {
		if (isset($this->profils)) {
			return $this->profils;
		}
		$this->profils = array();
		$query = "SELECT p.*, date_format(u.last_login, '%Y-%m-%d') as last_login
			FROM profil p, user u
			WHERE u.braldahim_id = p.idBraldun
			ORDER BY idBraldun ASC;";
		$res = mysql_query($query, $this->db);
		while ($row = mysql_fetch_assoc($res)) {
			$this->profils[] = $row;
		}
		mysql_free_result($res);
	}

	/*
	Retourne la liste des compétences du joueur
	*/
	private function getCompetences($id) {
		$comp = array();
		$query = sprintf("SELECT * FROM competence
			WHERE idBraldun=%s ORDER BY idMetier,idCompetence",
			mysql_real_escape_string($id));
		$res = mysql_query($query, $this->db);
		while ($row = mysql_fetch_assoc($res)) {
			$comp[] = $row;
		}
		mysql_free_result($res);
		return $comp;
	}

	/*
	Construit un tableau des competence à afficher
	*/
	private function drawCompetences($id) {
		$comps = $this->getCompetences($id);
		$str = '<table class="competence">';
		foreach ($comps as $comp) {
			$str .=<<<EOF
<tr>
<td>{$comp['nom']}</td><td>{$comp['maitrise']}</td>
</tr>
EOF;
		}
		$str .= '</table>';
		return $str;
	}

	/*
	Construit une image representant le temps de jeu
	*/
	private function timeline($now, $p) {
		$w = 400;
		$h = 15;
		$duree_total = 100000;
		$now_min = $now - $duree_total / 2;
		$now_max = $now + $duree_total / 2;
		$div = $duree_total / $w;

		$img = imagecreatetruecolor($w, $h);
		$c0 = imagecolorallocate($img, 0, 0, 0); // texte
		$c1 = imagecolorallocate($img, 128, 128, 128); // inactif
		$c2 = imagecolorallocate($img, 192, 192, 64); // latence
		$c3 = imagecolorallocate($img, 64, 192, 64); // actif

		// inactif
		imagefilledrectangle($img, 0, 0, $w, $h, $c1);

		// latence
		$lat_end = $cum_start = -1;
		$t = strtotime($p['dateFinLatence']) + $p['dureeBmTour']*60;
		if ($now_min < $t && $t < $now_max) {
			$lat_end = ($t - $now_min) / $div;
		}
		$t = strtotime($p['dateDebutCumul']);
		if ($now_min < $t && $t < $now_max) {
			$cum_start = ($t - $now_min) / $div;
		}
		imagefilledrectangle($img, $lat_end, 0, $cum_start, $h, $c2);

		// DLA
		$dla_end = -1;
		$t = strtotime($p['dateFinTour']);
		if ($now_min < $t && $t < $now_max) {
			$dla_end = ($t - $now_min) / $div;
		}
		imagefilledrectangle($img, $cum_start, 0, $dla_end, $h, $c3);

		// prochain tour
		if ($t < $now_max) {
			$dur = $this->time_to_second($p['DureeProchainTour']);

			$t += $dur / 4; // ajout inactivite : 1/4 durée tour
			$nx_lat_start = ($t - $now_min) / $div;
			$nx_lat_start_time = date('H:i', $t);

			$t += $dur / 4; // ajout latence : 1/4 durée tour
			$nx_lat_end = ($t - $now_min) / $div;
			$nx_lat_end_time = date('H:i', $t);

			$t += $dur / 2; // ajout activite : 1/2 durée tour
			$nx_tour_end = ($t - $now_min) / $div;
			$nx_tour_end_time = date('H:i', $t);

			imagefilledrectangle($img, $nx_lat_start, 0, $nx_lat_end, $h, $c2);
			imagefilledrectangle($img, $nx_lat_end, 0, $nx_tour_end, $h, $c3);

			$this->draw_text($img, $nx_lat_start_time, $nx_lat_start, $c0, $h);
			$this->draw_text($img, $nx_lat_end_time, $nx_lat_end, $c0, $h);
			$this->draw_text($img, $nx_tour_end_time, $nx_tour_end, $c0, $h);
		}

		$this->draw_text($img, date('H:i', strtotime($p['dateDebutCumul'])), $cum_start, $c0, $h);
		$this->draw_text($img, date('H:i', strtotime($p['dateFinLatence'])), $lat_end, $c0, $h);
		$this->draw_text($img, date('H:i', strtotime($p['dateFinTour'])), $dla_end, $c0, $h);

		// contour
		#imagerectangle($img, 0, 0, $w, $h, $c0);

		// now
		imagerectangle($img, $w/2, 0, $w/2, $h, $c0);
		imagefilledpolygon($img, array($w/2 - $h/3, 0, $w/2 + $h/3, 0, $w/2, $h/3), 3, $c0);

		ob_start(NULL);
		imagegif($img);
		$str = ob_get_contents();
		ob_end_clean();
		return base64_encode($str);
	}

	/*
	Converti une heure au format HH:MM:SS en seconde
	*/
	private function time_to_second($time) {
		list($h, $m, $s) = explode(':', $time);
		$s += $h * 3600 + $m * 60;
		return $s;
	}

	/*
	Affiche une barre verticale suivi du texte
	*/
	private function draw_text($img, $text, $x, $c, $h) {
		imagerectangle($img, $x, 0, $x, $h, $c);
		if ($x > 0) {
			imagettftext($img, 8, 0, $x+2, 12, $c, "./DejaVuSans.ttf", $text);
		}
	}
}

$app = new Profil();

include("template.php");
?>

