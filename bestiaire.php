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
			$nb_fiche = $monstre['count'];
			$nom = $monstre['nom'];
			$str_monstre =<<<EOF
Le monstre <b>{$nom}</b> ({$nb_fiche} fiches) a les caractéristiques suivantes :
<table class="monstre_tab_detail" border="1">
<tr>
	<th>&nbsp;</th>
	<th>Min</th>
	<th>Max</th>
</tr>
<tr>
	<td>Niveau</td>
	<td>{$monstre['niveau_min']}</td>
	<td>{$monstre['niveau_max']}</td>
</tr>
<tr>
	<td>Point de vie max</td>
	<td>{$monstre['pv_max_min']}</td>
	<td>{$monstre['pv_max_max']}</td>
</tr>
<tr>
	<td>Vue </td>
	<td></td>
	<td>{$monstre['vue']}</td>
</tr>
<tr>
	<td>R&eacute;g&eacute;n&eacute;ration</td>
	<td></td>
	<td>{$monstre['regeneration']}</td>
</tr>
<tr>
	<td>D&eacute;gats</td>
	<td></td>
	<td>{$monstre['degats']}</td>
</tr>
<tr>
	<td>Attaque</td>
	<td></td>
	<td>{$monstre['attaque']}</td>
</tr>
<tr>
	<td>Defense</td>
	<td></td>
	<td>{$monstre['defense']}</td>
</tr>
<tr>
	<td>Sagesse</td>
	<td></td>
	<td>{$monstre['sagesse']}</td>
</tr>
<tr>
	<td>Vigueur</td>
	<td></td>
	<td>{$monstre['vigueur']}</td>
</tr>
<tr>
	<td>Armure</td>
	<td>{$monstre['armure_min']}</td>
	<td>{$monstre['armure_max']}</td>
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
		$this->html_content = $content;
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
			FROM ".DB_PREFIX."bestiaire
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
	private function getFicheMonstre($nom, $period=1) {
		$query = "SELECT nom,
			floor(AVG(niveau_min)) as niveau_min, floor(AVG(niveau_max)) as niveau_max,
			floor(AVG(pv_max_min)) as pv_max_min, floor(AVG(pv_max_max)) as pv_max_max,
			floor(AVG(vue)) as vue,
			floor(AVG(regeneration)) as regeneration,
			floor(AVG(degats)) as degats,
			floor(AVG(attaque)) as attaque,
			floor(AVG(defense)) as defense,
			floor(AVG(sagesse)) as sagesse,
			floor(AVG(vigueur)) as vigueur,
			floor(AVG(armure_min)) as armure_min, floor(AVG(armure_max)) as armure_max,
			count(id) as count
			FROM ".DB_PREFIX."bestiaire
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

