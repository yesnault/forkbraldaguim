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
		
		$str =<<<EOF
<table class="monstre_tab_detail profil" border="1">
<tr>
	<th>Informations Tour</th>
	<th>Sante</th>
	<th>Caracteristiques</th>
	<th>Experience</th>
	<th>Poids</th>
	<th>Armure</th>
	<th>Combat</th>
	<th>Palmares</th>
	<th>Soule</th>
</tr>
EOF;
		// https://github.com/braldahim/braldahim/blob/master/braldahim/application/views/scripts/interface/profil.phtml
		foreach ($this->profils as $profil) {
			$px_max = ($profil['niveau'] + 2) * 5;
			$pv_max = $profil['nivVigueur'] * 10 + 40;

			$agi_des = ($profil['nivAgilite'] + 3).'&nbsp;D6';
			$agi_bm = $profil['bmAgilite'] + $profil['bmBddfAgilite'];

			$for_des = ($profil['nivForce'] + 3).'&nbsp;D6';
			$for_bm = $profil['bmForce'] + $profil['bmBddfForce'];

			$vig_des = ($profil['nivVigueur'] + 3).'&nbsp;D6';
			$vig_bm = $profil['bmVigueur'] + $profil['bmBddfVigueur'];

			$sag_des = ($profil['nivSagesse'] + 3).'&nbsp;D6';
			$sag_bm = $profil['bmSagesse'] + $profil['bmBddfSagesse'];

		/*
	<td>{$profil['DLA']}</td>
	<td>{$profil['dateDebutTour']}</td>
	<td>{$profil['dateFinTour']}</td>
	<td>{$profil['dateFinLatence']}</td>
	<td>{$profil['dateDebutCumul']}</td>
*/

			$str .=<<<EOF
<tr>
	<td colspan="9" class="p_nom">{$profil['prenom']} {$profil['nom']}&nbsp;({$profil['idBraldun']})</td>
</tr>
<tr>
	<td>
	Durée de ce tour&nbsp;:&nbsp;{$profil['dureeCourantTour']}<br/>
	Durée du prochain&nbsp;:&nbsp;{$profil['DureeProchainTour']} {$profil['dureeBmTour']}<br/>
	MAJ du profil&nbsp;:&nbsp;{$profil['last_update']}
	</td>

	<td>
	PV&nbsp;:&nbsp;{$profil['PvRestant']}&nbsp;/&nbsp;{$pv_max}&nbsp;+&nbsp;{$profil['bmPVmax']}<br/>
	Bdf&nbsp;:&nbsp;{$profil['bbdf']}%
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
	Niveau&nbsp;:&nbsp;{$profil['niveau']}<br/>
	PX Perso&nbsp;:&nbsp;{$profil['pxPerso']}&nbsp;/&nbsp;{$px_max}<br/>
	PX Commun&nbsp;:&nbsp;{$profil['pxCommun']}<br/>
	PI&nbsp;:&nbsp;{$profil['pi']}
	</td>

	<td>
	{$profil['poidsTransporte']}&nbsp;/&nbsp;{$profil['poidsTransportable']}
	</td>
	
	<td>
	Arm. naturelle&nbsp;:&nbsp;{$profil['armureNaturelle']}<br/>
	Arm. Equipement&nbsp;:&nbsp;{$profil['armureEquipement']}<br/>
	</td>

	<td>
	Attaque BM&nbsp;:&nbsp;{$profil['bmAttaque']}<br/>
	Defense BM&nbsp;:&nbsp;{$profil['bmDefense']}<br/>
	Degats BM&nbsp;:&nbsp;{$profil['bmDegat']}<br/>
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
</tr>
EOF;
		}
		$str .= "</table>";
		$this->html_content = $str;
	}
	
	/*
	Retourne la liste des noms des monstres connus
	*/
	private function getAllProfils() {
		if (isset($this->profils)) {
			return $this->profils;
		}
		$this->profils = array();
		$query = "SELECT *
			FROM profil
			ORDER BY idBraldun ASC;";
		$res = mysql_query($query, $this->db);
		while ($row = mysql_fetch_assoc($res)) {
			$this->profils[] = $row;
		}
		mysql_free_result($res);
	}
}

$app = new Profil();

include("template.php");
?>

