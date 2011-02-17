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

class Bestiaire extends Application {
	
	public function __construct() {
		parent::__construct();
		$this->actionParse();
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
				$this->bestiaireParse();
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
	Le bestiaires comporte {$montres_count} montres :
	<ul>{$liste}</ul>
</div>

<div id="monstre_detail">
{$str_monstre}
</div>
<div id="monstre_saisie">
<p>Collez le r&eacute;sultat de votre identification ici : </p>
	<form action="bestiaire.php" method="POST">
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

$app = new Bestiaire();

include("template.php");
?>

