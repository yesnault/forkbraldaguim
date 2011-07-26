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
if (! isset($_SESSION['bra_num'])) return;

require_once("conf.php");
error_reporting(E_ALL ^ E_NOTICE);

$carte = null;

$colors = array(
	// Couleur pour le brouillard
	'fog_never'	=> "array(128, 128, 128, 32)",
	'fog_hard'	=> "array(128, 128, 128, 48)",
	'fog_medium'	=> "array(128, 128, 128, 64)",
	'fog_light'	=> "array(128, 128, 128, 80)",
	'fog_clear'	=> "array(255, 255, 255, 127)",
	);

if (isset($_REQUEST['standalone'])) {
	$carte = new Carte(700, 500);
	// utilisation de "ob_gzhandler" pour gzipper le fichier svg,
	// la taille est divisé par 10
	ob_start("ob_gzhandler");
	$carte->display();
	ob_end_flush();
}

$echelle = 50; # largeur d'une tuile en pixel

/*
Classe utilitaire représentant un point (comme dans les cours de 1ere année...)
*/
class Point {
	public $x;
	public $y;
	public function __construct($x, $y) {
		$this->x = $x;
		$this->y = $y;
	}
	public function __toString() {
		return "({$this->x}, {$this->y})";
	}
	
	public function distanceMax(Point $p) {
		return max(abs($p->y - $this->y), abs($p->x - $this->x));
	}
	public function equals(Point $p) {
		return ($this->x == $p->x && $this->y == $p->y);
	}
}

/*
Classe représantant un joueur et sa postion
*/
class Joueur {
	public $id;
	public $nom;
	public $prenom;
	public $position;
	
	public function __construct($id, $prenom, $nom, Point $position) {
		$this->id = $id;
		$this->prenom = $prenom;
		$this->nom = $nom;
		$this->position = $position;
		$this->position->x += 0.5;
		$this->position->y += 0.5;
	}
	
	public function __toString() {
		return "[{$this->id}] {$this->prenom} {$this->nom} {$this->position}";
	}

	public function toSVG() {
		$svg =<<<EOF
<g id="joueur{$this->id}">
	<circle cx="{$this->position->x}" cy="{$this->position->y}" r="0.5" />
	<text x="{$this->position->x}" y="{$this->position->y}">{$this->prenom}</text>
	<use xlink:href="#png_joueur" x="{$this->position->x}" y="{$this->position->y}" width="19" height="22" />
</g>
EOF;
		return $svg;
	}
}

class Tuile {
	public $type;
	public $position;

	public function __construct($type, Point $position) {
		$this->type = $type;
		$this->position = $position;
	}

	public function toSVG() {
		$svg =<<<EOF
	<rect x="{$this->position->x}" y="{$this->position->y}" height="1" width="1" class="{$this->type}"/>
EOF;
		return $svg;
	}
}

class Lieu extends Tuile {
	public function toSVG() {
		$x = $this->position->x + 0.5;
		$y = $this->position->y + 0.5;
		$svg =<<<EOF
	<rect x="{$this->position->x}" y="{$this->position->y}" height="1" width="1" />
	<text x="{$x}" y="{$y}" transform="rotate(45 {$x} {$y})">{$this->type}</text>
EOF;
		return $svg;
	}
}

class Buisson extends Tuile {
	public function toSVG() {
		$svg =<<<EOF
	<rect x="{$this->position->x}" y="{$this->position->y}" height="1" width="1" class="{$this->type}"/>
	<text x="{$this->position->x}" y="{$this->position->y}">{$this->type}</text>
EOF;
		return $svg;
	}
}

class Ville {
	public $nom;
	public $start;
	public $w;
	public $h;
	public $text_position;

	public function __construct($nom, Point $start, Point $end) {
		$this->nom = $nom;
		$this->start = $start;
		$this->w = $end->x - $start->x;
		$this->h = $end->y - $start->y;
		$this->text_position = new Point($end->x-$this->w/2, $start->y);
	}
	
	public function toSVG() {
		$svg =<<<EOF
<g class="ville">
	<rect x="{$this->start->x}" y="{$this->start->y}" height="{$this->h}" width="{$this->w}" />
	<text x="{$this->text_position->x}" y="{$this->text_position->y}">{$this->nom}</text>
</g>
EOF;
		return $svg;
	}
}

class Zone extends Ville {
	public function toSVG() {
		global $colors;
		$svg =<<<EOF
<g class="zone {$this->nom}">
	<rect x="{$this->start->x}" y="{$this->start->y}" height="{$this->h}" width="{$this->w}" />
</g>
EOF;
		return $svg;
	}
}
/*
Genere une carte du monde centrée sur les joueurs
*/
class Carte {
	private $db;
	private $joueurs;
	private $size;
	private $type;
	private $origine;
	private $zoom;
	private $user_x;
	private $user_y;
	private $img;
	private $colors;
	
	private $debug = false;
	
	/*
	Construit une carte de la taille indiqué avec size (en pixel)
	et representant le sujet indiqué par type (fond, joueur, lieu)
	$user_zoom : valeur du zoom utilisateur
	$user_x, $user_y : décalage demandé par l'utilisateur par rapport à l'origine
	*/
	public function __construct($w, $h) {
		$this->w = $w;
		$this->h = $h;
		$this->joueurs = array();
		$this->villes = array();
		$this->zones = array();
		$this->routes = array();
		$this->palissades = array();
		$this->champs = array();
		$this->bosquets = array();
		$this->lieuMythiques = array();
		$this->lieuStandards = array();
		$this->environnements = array();
		$this->nids = array();
		$this->buissons = array();
		
		$this->db = mysql_connect(DB_HOST, DB_USER, DB_PASS);
		mysql_select_db(DB_NAME);
		mysql_set_charset('utf8', $this->db);
		mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
		
		$this->getJoueurs();
		$this->getVilles();
		$this->getZones();
		$this->getRoutes();
		$this->getPalissades();
		$this->getChamps();
		$this->getBosquets();
		$this->getLieuMythiques();
		$this->getLieuStandards();
		$this->getEnvironnements();
		$this->getNids();
		$this->getBuissons();
	}

	public function toSVG() {
		$svg =<<<EOF
<svg version="1.1" baseProfile="full"
	xmlns="http://www.w3.org/2000/svg"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:ev="http://www.w3.org/2001/xml-events"
	width="{$this->w}px" height="{$this->h}px"
	>
<script xlink:href="js/SVGPan.js"/>
<defs>
{$this->getStyle()}
<g id="png_joueur">
<image xlink:href="img/b/braldun.png" width="19" height="22" />
</g>
</defs>
<g id="viewport" transform="translate(350, 250)">
EOF;
		foreach ($this->zones as $i) {
			$svg .= $i->toSVG();
		}

		$svg .= '<g class="environnement">';
		foreach ($this->environnements as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";
		
		$svg .= '<g class="bosquet">';
		foreach ($this->bosquets as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= '<g class="route">';
		foreach ($this->routes as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= '<g class="champ">';
		foreach ($this->champs as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= '<g class="buisson">';
		foreach ($this->buissons as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= '<g class="palissade">';
		foreach ($this->palissades as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= '<g class="lieu mythique">';
		foreach ($this->lieuMythiques as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= '<g class="lieu standard">';
		foreach ($this->lieuStandards as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= '<g class="nid">';
		foreach ($this->nids as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		foreach ($this->villes as $i) {
			$svg .= $i->toSVG();
		}

		$svg .= '<g class="joueur">';
		foreach ($this->joueurs as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= "</g></svg>";

		return $svg;
	}

	public function display() {
		header('Content-type: image/svg+xml');
		echo $this->toSVG();
		exit();
	}
	
	/*
	Inline style
	*/
	private function getStyle() {
		$style =<<<EOF
<style type="text/css"><![CDATA[

text {
	display: none;
}

/* zone */
.montagne rect {
	fill: #742E09;
	stroke: none;
	stroke-width: 0; 
}
.plaine rect {
	fill: #009900;
	stroke: none;
	stroke-width: 0; 
}
.gazon rect {
	fill: #006500;
	stroke: none;
	stroke-width: 0; 
}
.marais rect {
	fill: #82C46C;
	stroke: none;
	stroke-width: 0; 
}

/* environnement */
.bosquet {
}
.route rect {
	fill: #B4B4B4;
	stroke: none;
	stroke-width: 0; 
}
.palissade {
}
.lac rect, .mer rect {
	fill: #00008B;
	stroke: none;
	stroke-width: 0;
}
.champ rect {
	fill: #966496;
	/*stroke: none;
	stroke-width: 0;*/
	stroke: #000000;
	stroke-width: 0.1px;
}
.buisson rect {
	fill: #64C80A;
	stroke: none;
	stroke-width: 0; 
}
.lieu rect {
	fill: #646464;
	stroke: none;
	stroke-width: 0; 
}
.lieu text {
	font-size: 0.1px;
	display: inline;
	text-anchor: middle;
}
.nid rect {
	fill: #960A0A;
	stroke: #000000;
	stroke-width: 0.1px;
}
.caverne rect {
	fill: #C8C8C8;
	stroke: none;
	stroke-width: 0;
}
.mine rect {
	fill: #000000;
	stroke: none;
	stroke-width: 0;
}

/* joueur */
.joueur circle {
	fill: #46DCF0;
	stroke: #FF0000;
	stroke-width: 0.1px;
}
.joueur text {
	font-size: 1px;
	display: inline;
	text-anchor: middle;
}

.ville rect {
	/*fill: #B4B4B4;*/
	fill: none;
	stroke: #FF0000;
	stroke-width: 0.1px;
}
.ville text {
	font-size: 3px;
	display: inline;
	text-anchor: middle;
}

/* tuile */
rect.montagne {
	fill: #742E09;
	stroke: none;
	stroke-width: 0; 
}
rect.palissade {
	fill: #FFB93F;
	stroke: none;
	stroke-width: 0; 
}
rect.balise {
	fill: #968278;
	stroke: none;
	stroke-width: 0; 
}
rect.ville {
}
rect.echoppe {
}
rect.eau, rect.peuprofonde {
	fill: #82C8E6;
	stroke: none;
	stroke-width: 0; 
}
rect.profonde {
	fill: #64AAC8;
	stroke: none;
	stroke-width: 0; 
}
rect.peupliers, rect.hetres, rect.chenes, rect.erables {
	fill: #46DC46;
	stroke: none;
	stroke-width: 0;
}


]]></style>
EOF;
/*
// Couleur pour le type ROUTE
'echoppe'	=> "B4B4B4",
'ruine'		=> "505050",
*/
		return $style;
	}

	/*
	Recupere les joueurs de la communauté
	*/
	private function getJoueurs() {
		$query = "SELECT braldahim_id, prenom, nom, x, y
		FROM ".DB_PREFIX."user
		WHERE x IS NOT NULL
		AND y IS NOT NULL
		AND restricted_password IS NOT NULL
		ORDER BY braldahim_id ASC;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->joueurs[] = new Joueur(
				$row['braldahim_id'],
				$row['prenom'],
				$row['nom'],
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les villes
	*/
	private function getVilles() {
		$query = "SELECT nom_ville,
			x_min_ville, y_min_ville,
			x_max_ville, y_max_ville
			FROM ".DB_PREFIX."ville";

		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->villes[] = new Ville(
				$row['nom_ville'],
				new Point($row['x_min_ville'], $row['y_max_ville']*-1),
				new Point($row['x_max_ville'], $row['y_min_ville']*-1)
				);
		}
		mysql_free_result($res);
	}

	/*
	Retourne un tableau de toutes les zones
	*/
	private function getZones() {
		$query = "SELECT nom_systeme_environnement,
			x_min_zone, y_min_zone,
			x_max_zone, y_max_zone
			FROM ".DB_PREFIX."zone";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->zones[] = new Zone(
				$row['nom_systeme_environnement'],
				new Point($row['x_min_zone'], $row['y_max_zone']*-1),
				new Point($row['x_max_zone']+2, $row['y_min_zone']*-1+2)
				);
		}
		mysql_free_result($res);
	}
	
	/*
	Recupere les routes
	*/
	private function getRoutes() {
		$query = "SELECT x, y, type_route
		FROM ".DB_PREFIX."route
		ORDER BY x, y, type_route DESC;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->routes[] = new Tuile(
				$row['type_route'],
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les palissade
	*/
	private function getPalissades() {
		$query = "SELECT x, y
		FROM ".DB_PREFIX."palissade
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->palissades[] = new Tuile(
				'palissade',
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les champs
	*/
	private function getChamps() {
		$query = "SELECT x, y
		FROM ".DB_PREFIX."champ
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->champs[] = new Tuile(
				'champ',
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les bosquets
	*/
	private function getBosquets() {
		$query = "SELECT x, y, nom_systeme_type_bosquet
		FROM ".DB_PREFIX."bosquet
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->bosquets[] = new Tuile(
				$row['nom_systeme_type_bosquet'],
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les lieux standards
	*/
	private function getLieuStandards() {
		#$query = "SELECT x, y, nom_systeme_type_lieu
		$query = "SELECT x, y, nom_lieu
		FROM ".DB_PREFIX."lieu
		WHERE nom_systeme_type_lieu NOT IN ('lieumythique', 'quete', 'ruine')
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->lieuStandards[] = new Lieu(
				$row['nom_lieu'],
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les lieux standards
	*/
	private function getLieuMythiques() {
		#$query = "SELECT x, y, nom_systeme_type_lieu
		$query = "SELECT x, y, nom_lieu
		FROM ".DB_PREFIX."lieu
		WHERE nom_systeme_type_lieu IN ('lieumythique', 'quete', 'ruine')
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->lieuMythiques[] = new Lieu(
				$row['nom__lieu'],
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les environnements
	*/
	private function getEnvironnements() {
		$query = "SELECT x, y, nom_systeme_environnement
		FROM ".DB_PREFIX."environnement
		WHERE nom_systeme_environnement != 'plaine'
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->environnements[] = new Tuile(
				strtolower($row['nom_systeme_environnement']),
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les nids standards
	*/
	private function getNids() {
		$query = "SELECT x, y, nom_nid
		FROM ".DB_PREFIX."nid
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->nids[] = new Lieu(
				$row['nom_nid'],
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les buissons
	*/
	private function getBuissons() {
		$query = "SELECT x, y, nom_type_buisson
		FROM ".DB_PREFIX."buisson
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->buissons[] = new Buisson(
				$row['buisson'],
				new Point($row['x'], $row['y']*-1));
		}
		mysql_free_result($res);
	}

	/*
	Assombrit les zones qui n'ont jamais été visitées ou bien qui n'ont pas
	été visitées depsui longtemps
	*/
	private function drawBrouillard() {
		date_default_timezone_set(date_default_timezone_get());
		imagesavealpha($this->img, true);
		imagealphablending($this->img, false);
		imagefill($this->img, 0, 0, $this->colors['fog_never']);
		
		$now = time();
		// configuration du brouillard en fonction du temps
		$fog_hard = 30 * 86400;
		$fog_medium = 10 * 86400;
		$fog_light = 2 * 86400;
		//$fog_never = 
		
		$tiles_list = $this->getTiles();
		foreach ($tiles_list as $name => $tile) {
			list($x, $y) = explode(';', $name);
			$p_physique = $this->positionToPixel(new Point($x, $y));
			
			list($y, $m, $d) = explode('-', $tile['last_update']);
			$tile_time = mktime(0, 0, 0, $m, $d, $y);
			
			$color = '';
			// si l'enregistrement est plus vieux que :
			if ($now - $tile_time > $fog_hard) {
				$color = $this->colors['fog_hard'];
			}
			else if ($now - $tile_time > $fog_medium) {
				$color = $this->colors['fog_medium'];
			}
			else if ($now - $tile_time > $fog_light) {
				$color = $this->colors['fog_light'];
			}
			else {
				$color = $this->colors['fog_clear'];
			}
			
			imagefilledrectangle($this->img,
				$p_physique->x,
				$p_physique->y,
				$p_physique->x + $this->tile_size,
				$p_physique->y + $this->tile_size,
				$color);
		}
	}
}

?>
