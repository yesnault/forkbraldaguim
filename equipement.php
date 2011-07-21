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

class Equipement extends Application {
	
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
			case 'equipement':
				if (!$this->logged) break;
				$this->equipement();
				break;
			case 'home':
			default:
				$this->home();
				break;
		}
	}

	/*
	Affiche les equipements :
	  - liste des braldun
	  - affichage détaillé
	*/
	private function equipement() {
		// liste des bralduns
		$this->getBralduns();
		$liste = '';
		foreach ($this->bralduns as $b) {
			$liste .= '<li><a href="equipement.php?action=equipement&b='
				.$b['braldahim_id'].'">'
				.$b['prenom'].' '.$b['nom']
				.'</a></li>';
		}
		
		// description detaillee
		$b = ((isset($_REQUEST['b'])) ? $_REQUEST['b'] : null);
		$str_braldun = '';
		if (! is_null($b) && ! empty($b)) {
			$equipements = $this->getEquipement($b);
			foreach ($equipements as $e) {
				$usure = 100 - ($e['etat_courant']/$e['etat_initial'])* 100;
				$str_braldun .=<<<EOF
<table class="monstre_tab_detail" border="1">
<tr>
	<th colspan="4">{$e['nom']}<br/>de qualit&eacute; {$e['qualite']} {$e['suffixe']}</th>
</tr>
<tr>
	<td colspan="4">{$e['emplacement']}</td>
</tr>
<tr>
	<td colspan="4">
		<span class="bar" style="background-position: -{$usure}px 0px "></span>
		{$e['etat_courant']} / {$e['etat_initial']}
	</td>
</tr>
<tr>
	<td>Niveau</td>
	<td colspan="3">{$e['niveau']}</td>
</tr>
<tr>
	<td>Bonus</td>
	<td><acronym title="Equipement">E</acronym></td>
	<td><acronym title="Regional">R</acronym></td>
	<td><acronym title="Vernis">V</acronym></td>
</tr>
<tr>
	<td>Armure</td>
	<td>{$e['armure']}</td>
	<td>{$e['armure_equipement_bonus']}</td>
	<td>{$e['vernis_bm_armure_equipement_bonus']}</td>
</tr>
<tr>
	<td>Force</td>
	<td>{$e['force']}</td>
	<td>{$e['force_equipement_bonus']}</td>
	<td>{$e['vernis_bm_force_equipement_bonus']}</td>
</tr>
<tr>
	<td>Agilite</td>
	<td>{$e['agilite']}</td>
	<td>{$e['agilite_equipement_bonus']}</td>
	<td>{$e['vernis_bm_agilite_equipement_bonus']}</td>
</tr>
<tr>
	<td>Vigueur</td>
	<td>{$e['vigueur']}</td>
	<td>{$e['vigueur_equipement_bonus']}</td>
	<td>{$e['vernis_bm_vigueur_equipement_bonus']}</td>
</tr>
<tr>
	<td>Sagesse</td>
	<td>{$e['sagesse']}</td>
	<td>{$e['sagesse_equipement_bonus']}</td>
	<td>{$e['vernis_bm_sagesse_equipement_bonus']}</td>
</tr>
<tr>
	<td>Vue</td>
	<td>{$e['vue']}</td>
	<td></td>
	<td>{$e['vernis_bm_vue_equipement_bonus']}</td>
</tr>
<tr>
	<td>Attaque</td>
	<td>{$e['attaque']}</td>
	<td></td>
	<td>{$e['vernis_bm_attaque_equipement_bonus']}</td>
</tr>
<tr>
	<td>Degat</td>
	<td>{$e['degat']}</td>
	<td></td>
	<td>{$e['vernis_bm_degat_equipement_bonus']}</td>
</tr>
<tr>
	<td>Defense</td>
	<td>{$e['defense']}</td>
	<td></td>
	<td>{$e['vernis_bm_defense_equipement_bonus']}</td>
</tr>
<tr>
	<td>Poids</td>
	<td colspan="3">{$e['poids']}</td>
</tr>
<tr>
	<td>Ingredient</td>
	<td colspan="3">{$e['ingredient']}</td>
</tr>
<tr>
	<td>Runes</td>
	<td colspan="3">{$e['nb_runes']}</td>
</tr>
<tr>
	<td>Rune 1</td>
	<td colspan="3">{$e['nom_type_rune1']}</td>
</tr>
<tr>
	<td>Rune 2</td>
	<td colspan="3">{$e['nom_type_rune2']}</td>
</tr>
<tr>
	<td>Rune 3</td>
	<td colspan="3">{$e['nom_type_rune3']}</td>
</tr>
<tr>
	<td>Rune 4</td>
	<td colspan="3">{$e['nom_type_rune4']}</td>
</tr>
<tr>
	<td>Rune 5</td>
	<td colspan="3">{$e['nom_type_rune5']}</td>
</tr>
<tr>
	<td>Rune 6</td>
	<td colspan="3">{$e['nom_type_rune6']}</td>
</tr>
</table>
EOF;
			}
		}
		
		$content = <<<EOF
<div id="monstre_liste">
	<ul>{$liste}</ul>
</div>

<div id="monstre_detail">
{$str_braldun}
</div>
EOF;
		$this->html_content = $content;
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
	Retourne une fiche "type" pour un monstre
	*/
	private function getEquipement($id) {
		$query = "SELECT *
			FROM ".DB_PREFIX."equipement
			WHERE braldun=".mysql_real_escape_string($id)."
			ORDER BY emplacement";
		$res = mysql_query($query, $this->db);
		$equipement = array();
		while ($row = mysql_fetch_assoc($res)) {
			$equipement[] = $row;
		}
		mysql_free_result($res);
		return $equipement;
	}
}

$app = new Equipement();

include("template.php");
?>

