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

class Charrette extends Application {
	
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
			case 'charrette':
				if (!$this->logged) break;
				$this->charrette();
				break;
			case 'home':
			default:
				$this->home();
				break;
		}
	}

	/*
	Affiche le contenu de la charrette
	*/
	private function charrette() {
		// liste des bralduns
		$this->getBralduns();
		$liste = '';
		foreach ($this->bralduns as $b) {
			$liste .= '<li><a href="charrette.php?action=charrette&b='
				.$b['braldahim_id'].'">'
				.$b['prenom'].' '.$b['nom']
				.'</a></li>';
		}
		
		// description detaillee
		$b = ((isset($_REQUEST['b'])) ? $_REQUEST['b'] : null);
		$str_braldun = '';
		if (! is_null($b) && ! empty($b)) {
			$charrette = $this->getCharrette($b);
			foreach ($charrette as $e) {
				if ($e['objet'] == 'ELEMENT') {
					$contenu = explode(';', $e['contenu']);
					if ($contenu[0] == 'Castar') {
						$this->castar = $contenu[1];
					}
					elseif ($contenu[0] == 'Peau') {
						$this->peau = $contenu[1];
					}
					elseif ($contenu[0] == 'Cuir') {
						$this->cuir = $contenu[1];
					}
					elseif ($contenu[0] == 'Fourrue') {
						$this->fourrure = $contenu[1];
					}
					elseif ($contenu[0] == 'Planche') {
						$this->planche = $contenu[1];
					}
					elseif ($contenu[0] == 'Rondin') {
						$this->rondin = $contenu[1];
					}
				}
				elseif ($e['objet'] == 'ALIMENT') {
					$contenu = explode(';', $e['contenu']);
					$this->aliments .= $contenu[1]." de qualité ".$contenu[2]." (".$contenu[3].")<br/>";
				}
				elseif ($e['objet'] == 'MATERIEL') {
					$contenu = explode(';', $e['contenu']);
					$this->materiel .= $contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'TABAC') {
					$contenu = explode(';', $e['contenu']);
					$this->tabac .= $contenu[0]." feuilles de ".$contenu[1]."<br/>";
				}
			}
			$str_braldun .=<<<EOF
<table class="monstre_tab_detail" border="1">
<tr>
	<td>Castar</td>
	<td>{$this->castar}</td>
</tr>
<tr>
	<td>Peau</td>
	<td>{$this->peau}</td>
</tr>
<tr>
	<td>Cuir</td>
	<td>{$this->cuir}</td>
</tr>
<tr>
	<td>Fourrure</td>
	<td>{$this->fourrure}</td>
</tr>
<tr>
	<td>Planche</td>
	<td>{$this->planche}</td>
</tr>
<tr>
	<td>Rondin</td>
	<td>{$this->rondin}</td>
</tr>
<tr>
	<td>Aliments</td>
	<td>{$this->aliments}</td>
</tr>
<tr>
	<td>Materiel</td>
	<td>{$this->materiel}</td>
</tr>
<tr>
	<td>Tabac</td>
	<td>{$this->tabac}</td>
</tr>
</table>
EOF;
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
	Retourne le contenu de la charrette d'un braldun
	*/
	private function getCharrette($id) {
		$query = "SELECT *
			FROM ".DB_PREFIX."charrette
			WHERE idBraldun=".mysql_real_escape_string($id);
		$res = mysql_query($query, $this->db);
		$content = array();
		while ($row = mysql_fetch_assoc($res)) {
			$content[] = $row;
		}
		mysql_free_result($res);
		return $content;
	}
}

$app = new Charrette();

include("template.php");
?>

