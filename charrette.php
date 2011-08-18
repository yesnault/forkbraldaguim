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
						$this->Ccastar = $contenu[1];
					}
					elseif ($contenu[0] == 'Peau') {
						$this->Cpeau = $contenu[1];
					}
					elseif ($contenu[0] == 'Cuir') {
						$this->Ccuir = $contenu[1];
					}
					elseif ($contenu[0] == 'Fourrue') {
						$this->Cfourrure = $contenu[1];
					}
					elseif ($contenu[0] == 'Planche') {
						$this->Cplanche = $contenu[1];
					}
					elseif ($contenu[0] == 'Rondin') {
						$this->Crondin = $contenu[1];
					}
				}
				elseif ($e['objet'] == 'ALIMENT') {
					$contenu = explode(';', $e['contenu']);
					$this->Caliments .= $contenu[1]." de qualité ".$contenu[2]." (".$contenu[3].")<br/>";
				}
				elseif ($e['objet'] == 'MATERIEL') {
					$contenu = explode(';', $e['contenu']);
					$this->Cmateriel .= $contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'EQUIPEMENT') {
					$contenu = explode(';', $e['contenu']);
					$this->Cequipement .= $contenu[1]." de qualité ".$contenu[2]." N".$contenu[3]." ".$contenu[4]."<br/>";
				}
				elseif ($e['objet'] == 'TABAC') {
					$contenu = explode(';', $e['contenu']);
					$this->Ctabac .= $contenu[0]." feuilles de ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'MUNITION') {
					$contenu = explode(';', $e['contenu']);
					$this->Cmunition .= $contenu[0]." x ".$contenu[2]."<br/>";
				}
				elseif ($e['objet'] == 'POTION') {
					$contenu = explode(';', $e['contenu']);
					$this->Cpotion .= $contenu[1]." ".$contenu[2]." ".$contenu[3]."<br/>";
				}
				elseif ($e['objet'] == 'GRAINE') {
					$contenu = explode(';', $e['contenu']);
					$this->Cgraine .= $contenu[0]." graines de ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'INGREDIENT') {
					$contenu = explode(';', $e['contenu']);
					$this->Cingredient .= $contenu[0]." x ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'MINERAI_BRUT') {
					$contenu = explode(';', $e['contenu']);
					$this->Cminerai .= $contenu[0]." x ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'LINGOT') {
					$contenu = explode(';', $e['contenu']);
					$this->Clingot .= $contenu[0]." x ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'PLANTE_BRUTE') {
					$contenu = explode(';', $e['contenu']);
					$this->Cplantebrute .= $contenu[0]." x ".$contenu[1]." ".$contenu[2]."<br/>";
				}
				elseif ($e['objet'] == 'PLANTE_PREPAREE') {
					$contenu = explode(';', $e['contenu']);
					$this->Cplantepreparee .= $contenu[0]." x ".$contenu[1]." ".$contenu[2]."<br/>";
				}
				elseif ($e['objet'] == 'RUNE') {
					$contenu = explode(';', $e['contenu']);
					$this->Crune .= $contenu[0]."<br/>";
				}
			}
			
			$laban = $this->getLaban($b);
			foreach ($laban as $e) {
				if ($e['objet'] == 'ELEMENT') {
					$contenu = explode(';', $e['contenu']);
					if ($contenu[0] == 'Castar') {
						$this->Lcastar = $contenu[1];
					}
					elseif ($contenu[0] == 'Peau') {
						$this->Lpeau = $contenu[1];
					}
					elseif ($contenu[0] == 'Cuir') {
						$this->Lcuir = $contenu[1];
					}
					elseif ($contenu[0] == 'Fourrue') {
						$this->Lfourrure = $contenu[1];
					}
					elseif ($contenu[0] == 'Planche') {
						$this->Lplanche = $contenu[1];
					}
					elseif ($contenu[0] == 'Rondin') {
						$this->Lrondin = $contenu[1];
					}
				}
				elseif ($e['objet'] == 'ALIMENT') {
					$contenu = explode(';', $e['contenu']);
					$this->Laliments .= $contenu[1]." de qualité ".$contenu[2]." (".$contenu[3].")<br/>";
				}
				elseif ($e['objet'] == 'MATERIEL') {
					$contenu = explode(';', $e['contenu']);
					$this->Lmateriel .= $contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'EQUIPEMENT') {
					$contenu = explode(';', $e['contenu']);
					$this->Lequipement .= $contenu[1]." de qualité ".$contenu[2]." N".$contenu[3]." ".$contenu[4]."<br/>";
				}
				elseif ($e['objet'] == 'TABAC') {
					$contenu = explode(';', $e['contenu']);
					$this->Ltabac .= $contenu[0]." feuilles de ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'MUNITION') {
					$contenu = explode(';', $e['contenu']);
					$this->Lmunition .= $contenu[0]." x ".$contenu[2]."<br/>";
				}
				elseif ($e['objet'] == 'POTION') {
					$contenu = explode(';', $e['contenu']);
					$this->Lpotion .= $contenu[1]." ".$contenu[2]." ".$contenu[3]."<br/>";
				}
				elseif ($e['objet'] == 'GRAINE') {
					$contenu = explode(';', $e['contenu']);
					$this->Lgraine .= $contenu[0]." graines de ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'INGREDIENT') {
					$contenu = explode(';', $e['contenu']);
					$this->Lingredient .= $contenu[0]." x ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'MINERAI_BRUT') {
					$contenu = explode(';', $e['contenu']);
					$this->Lminerai .= $contenu[0]." x ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'LINGOT') {
					$contenu = explode(';', $e['contenu']);
					$this->Llingot .= $contenu[0]." x ".$contenu[1]."<br/>";
				}
				elseif ($e['objet'] == 'PLANTE_BRUTE') {
					$contenu = explode(';', $e['contenu']);
					$this->Lplantebrute .= $contenu[0]." x ".$contenu[1]." ".$contenu[2]."<br/>";
				}
				elseif ($e['objet'] == 'PLANTE_PREPAREE') {
					$contenu = explode(';', $e['contenu']);
					$this->Lplantepreparee .= $contenu[0]." x ".$contenu[1]." ".$contenu[2]."<br/>";
				}
				elseif ($e['objet'] == 'RUNE') {
					$contenu = explode(';', $e['contenu']);
					$this->Lrune .= $contenu[0]."<br/>";
				}
			}
			
			$str_braldun .=<<<EOF
<table class="monstre_tab_detail" border="1">
<tr>
	<th>Element</th>
	<th>Laban</th>
	<th>Charrette</th>
</tr>
<tr>
	<td>Castar</td>
	<td>{$this->Lcastar}</td>
	<td>{$this->Ccastar}</td>
</tr>
<tr>
	<td>Peau</td>
	<td>{$this->Lpeau}</td>
	<td>{$this->Cpeau}</td>
</tr>
<tr>
	<td>Cuir</td>
	<td>{$this->Lcuir}</td>
	<td>{$this->Ccuir}</td>
</tr>
<tr>
	<td>Fourrure</td>
	<td>{$this->Lfourrure}</td>
	<td>{$this->Cfourrure}</td>
</tr>
<tr>
	<td>Planche</td>
	<td>{$this->Lplanche}</td>
	<td>{$this->Cplanche}</td>
</tr>
<tr>
	<td>Rondin</td>
	<td>{$this->Lrondin}</td>
	<td>{$this->Crondin}</td>
</tr>
<tr>
	<td>Minerai</td>
	<td>{$this->Lminerai}</td>
	<td>{$this->Cminerai}</td>
</tr>
<tr>
	<td>Lingot</td>
	<td>{$this->Llingot}</td>
	<td>{$this->CLingot}</td>
</tr>
<tr>
	<td>Aliments</td>
	<td>{$this->Laliments}</td>
	<td>{$this->Caliments}</td>
</tr>
<tr>
	<td>Rune</td>
	<td>{$this->Lrune}</td>
	<td>{$this->Crune}</td>
</tr>
<tr>
	<td>Equipement</td>
	<td>{$this->Lequipement}</td>
	<td>{$this->Cequipement}</td>
</tr>
<tr>
	<td>Materiel</td>
	<td>{$this->Lmateriel}</td>
	<td>{$this->Cmateriel}</td>
</tr>
<tr>
	<td>Tabac</td>
	<td>{$this->Ltabac}</td>
	<td>{$this->Ctabac}</td>
</tr>
<tr>
	<td>Munition</td>
	<td>{$this->Lmunition}</td>
	<td>{$this->Cmunition}</td>
</tr>
<tr>
	<td>Potion</td>
	<td>{$this->Lpotion}</td>
	<td>{$this->Cpotion}</td>
</tr>
<tr>
	<td>Graine</td>
	<td>{$this->Lgraine}</td>
	<td>{$this->Cgraine}</td>
</tr>
<tr>
	<td>Ingredient</td>
	<td>{$this->Lingredient}</td>
	<td>{$this->Cingredient}</td>
</tr>
<tr>
	<td>Plante Brute</td>
	<td>{$this->Lplantebrute}</td>
	<td>{$this->Cplantebrute}</td>
</tr>
<tr>
	<td>Plante Prepar&eacute;e</td>
	<td>{$this->Lplantepreparee}</td>
	<td>{$this->Cplantepreparee}</td>
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
	
	/*
	Retourne le contenu du laban d'un braldun
	*/
	private function getLaban($id) {
		$query = "SELECT *
			FROM ".DB_PREFIX."laban
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

