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

class PositionSvg extends Application {
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
		return '';
	}
	
	/*
	Affiche la position des membres de la communauté
	*/
	private function position() {
		$content =<<<EOF
<embed id="embed" src="svgmap.php?standalone=" type="image/svg+xml" width="800" height="600" >
EOF;
		$this->html_content = $content;
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
			ORDER BY nom_region, nom_ville;";
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

$app = new PositionSvg();
require("template.php");
?>
