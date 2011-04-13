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

class Bestiaire extends Application {
	
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
			case 'bestiaire':
				if (!$this->logged) break;
				$this->bestiaire();
				break;
			case 'bestiaire_submit':
				if (!$this->logged) break;
				$this->bestiaire();
				//$this->bestiaireParse();
				break;
			case 'home':
			default:
				$this->home();
				break;
		}
	}

	/*
	Affiche le bestiaire :
	  - liste des monstres
	  - affichage détaillé
	  - interface de saisie
	*/
	private function bestiaire() {
		// liste des monstres
		$this->getListeMonstres();
		$montres_count = count($this->getListeMonstres());
		$liste = '';
		foreach ($this->monstres as $m) {
			$uem = urlencode(utf8_decode($m));
			$liste .= '<li><a href="bestiaire.php?action=bestiaire&m='.$uem.'">'.$m.'</a></li>';
		}
		
		// description detaillee
		$m = ((isset($_REQUEST['m'])) ? $_REQUEST['m'] : null);
		$m = trim($m);
		$str_monstre = '';
		if (! is_null($m) && ! empty($m)) {
			$monstre0 = $this->getFicheMonstre($m, 0);
			$monstre = $this->getFicheMonstre($m, 1);
			$nb_fiche = $monstre0['count'] + $monstre['count'];
			$nom = (isset($monstre['nom'])) ? $monstre['nom'] : $monstre0['nom'];
			$str_monstre =<<<EOF
Le monstre <b>{$nom}</b> ({$nb_fiche} fiches) a les caractéristiques suivantes :
<table class="monstre_tab_detail" border="1">
<tr>
	<th>&nbsp;</th>
	<th colspan="2">Actuellement</th>
	<th colspan="2">Avant le 14/02/2011</th>
</tr>
<tr>
	<th>&nbsp;</th>
	<th>Min</th>
	<th>Max</th>
	<th>Min</th>
	<th>Max</th>
</tr>
<tr>
	<td>Niveau</td>
	<td>{$monstre['niveau_min']}</td>
	<td>{$monstre['niveau_max']}</td>
	<td>{$monstre0['niveau_min']}</td>
	<td>{$monstre0['niveau_max']}</td>
</tr>
<tr>
	<td>Point de vie max</td>
	<td>{$monstre['pv_max_min']}</td>
	<td>{$monstre['pv_max_max']}</td>
	<td>{$monstre0['pv_max_min']}</td>
	<td>{$monstre0['pv_max_max']}</td>
</tr>
<tr>
	<td>Vue </td>
	<td>{$monstre['vue_min']}</td>
	<td>{$monstre['vue_max']}</td>
	<td>{$monstre0['vue_min']}</td>
	<td>{$monstre0['vue_max']}</td>
</tr>
<tr>
	<td>Force</td>
	<td>{$monstre['force_min']}</td>
	<td>{$monstre['force_max']} {$monstre['force_unite']}</td>
	<td>{$monstre0['force_min']}</td>
	<td>{$monstre0['force_max']} {$monstre0['force_unite']}</td>
</tr>
<tr>
	<td>Agilit&eacute;</td>
	<td>{$monstre['agilite_min']}</td>
	<td>{$monstre['agilite_max']} {$monstre['agilite_unite']}</td>
	<td>{$monstre0['agilite_min']}</td>
	<td>{$monstre0['agilite_max']} {$monstre0['agilite_unite']}</td>
</tr>
<tr>
	<td>Sagesse</td>
	<td>{$monstre['sagesse_min']}</td>
	<td>{$monstre['sagesse_max']} {$monstre['sagesse_unite']}</td>
	<td>{$monstre0['sagesse_min']}</td>
	<td>{$monstre0['sagesse_max']} {$monstre0['sagesse_unite']}</td>
</tr>
<tr>
	<td>Vigueur</td>
	<td>{$monstre['vigueur_min']}</td>
	<td>{$monstre['vigueur_max']} {$monstre['vigueur_unite']}</td>
	<td>{$monstre0['vigueur_min']}</td>
	<td>{$monstre0['vigueur_max']} {$monstre0['vigueur_unite']}</td>
</tr>
<tr>
	<td>R&eacute;g&eacute;n&eacute;ration</td>
	<td>{$monstre['regeneration_min']}</td>
	<td>{$monstre['regeneration_max']}</td>
	<td>{$monstre0['regeneration_min']}</td>
	<td>{$monstre0['regeneration_max']}</td>
</tr>
<tr>
	<td>Armure</td>
	<td>{$monstre['armure_min']}</td>
	<td>{$monstre['armure_max']}</td>
	<td>{$monstre0['armure_min']}</td>
	<td>{$monstre0['armure_max']}</td>
</tr>
<tr>
	<td>Distance de la cible</td>
	<td colspan="2">{$monstre['distance']}</td>
	<td colspan="2">{$monstre0['distance']}</td>
</tr>
</table>
EOF;
		}
		
		$content = <<<EOF
<div id="monstre_liste">
	Le bestiaires comporte {$montres_count} montres :
	<ul>{$liste}</ul>
</div>

<div id="monstre_detail">
{$str_monstre}
</div>
EOF;
/*
<div id="monstre_saisie">
<p>Collez le r&eacute;sultat de votre identification ici : </p>
	<form action="bestiaire.php" method="POST">
	<input type="hidden" name="action" value="bestiaire_submit" />
	<textarea id="desc" name="desc"></textarea><br />
	<input type="submit" value="Enregistrer" />
	</form>
</div>
*/
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
				", last_update) VALUES(".
				implode(',', $query_values).
				", current_date);";
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
	On indique la période d'enregistrement des fiches :
	  * 0 : avant le 2011-02-14
	  * 1 : apres le 2011-02-14
	*/
	private function getFicheMonstre($nom, $period=1) {
		$time_cond = '1=1';
		if ($period == 0) {
			$time_cond = "last_update <= '2011-02-14'";
		}
		else if ($period == 1) {
			$time_cond = "last_update > '2011-02-14'";
		}

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
			AND $time_cond
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

$app = new Bestiaire();

include("template.php");
?>

