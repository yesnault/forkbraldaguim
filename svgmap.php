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

$echelle = 50; # largeur d'une tuile en pixel
$carte = null;

$colors = array(
	// Couleur pour le brouillard
	'fog_never'	=> "array(128, 128, 128, 32)",
	'fog_hard'	=> "array(128, 128, 128, 48)",
	'fog_medium'	=> "array(128, 128, 128, 64)",
	'fog_light'	=> "array(128, 128, 128, 80)",
	'fog_clear'	=> "array(255, 255, 255, 127)",
	);

$carte = new Carte(800, 600);
// utilisation de "ob_gzhandler" pour gzipper le fichier svg,
// la taille est divisé par 10
ob_start("ob_gzhandler");
$carte->display();
ob_end_flush();


/*
Classe contenant les propriétés de la carte.
C'est un singleton pour le partager entre tous les objets.
*/
class Props {
	private static $instance;
	
	private function __construct(){}
	
	public static function getProps() {
		if (!isset(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	public function __clone() {
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}

	public function __wakeup() {
		trigger_error('Unserializing is not allowed.', E_USER_ERROR);
	}
}

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
	public $rayon;
	
	public function __construct($id, $prenom, $nom, Point $position) {
		global $echelle;
		$this->id = $id;
		$this->prenom = $prenom;
		$this->nom = $nom;
		$this->position = $position;
		$this->position->x += 0.5 * $echelle;
		$this->position->y += 0.5 * $echelle;
		$this->rayon = $echelle / 2;
	}

	public function toSVG() {
#	<circle cx="{$this->position->x}" cy="{$this->position->y}" r="{$this->rayon}" />
		$svg =<<<EOF
<g id="joueur{$this->id}">
	<text x="{$this->position->x}" y="{$this->position->y}">{$this->prenom}</text>
	<use xlink:href="#png_joueur" x="{$this->position->x}" y="{$this->position->y}" />
</g>
EOF;
		return $svg;
	}
	
	public function getInfo() {
		$p = Props::getProps();
		
		$nom_x = $p->info_x + 5;
		$nom_y = $p->info_y + 20;
		$pv_x = $p->info_x + 5;
		$pv_y = $p->info_y + 35;
		
		$close_w = 14;
		$close_cx = $p->info_x + $p->info_w;
		$close_cy = $p->info_y;
		
		$svg =<<<EOF
<g id="info_joueur{$this->id}" class="info_joueur">
	<rect x="{$p->info_x}" y="{$p->info_y}" height="{$p->info_h}" width="{$p->info_w}" class="info_bg" />
	<text x="{$nom_x}" y="{$nom_y}" style="display:inline; color:#000">{$this->prenom} {$this->nom}</text>
	<text x="{$pv_x}" y="{$pv_y}" style="display:inline; color:#000">{$this->pvrestant} / {$this->pvmax}</text>
	<g id="close_info_joueur{$this->id}" class="close_info_joueur">
		<circle r="14" cx="{$close_cx}" cy="{$close_cy}" />
	</g>
</g>
EOF;
		return $svg;
	}
}

class Tuile {
	public $type;
	public $position;
	public $w;
	public $h;

	public function __construct($type, Point $position) {
		global $echelle;
		$this->type = $type;
		$this->position = $position;
		$this->w = $echelle;
		$this->h = $echelle;
	}

	public function toSVG() {
		$svg =<<<EOF
	<rect x="{$this->position->x}" y="{$this->position->y}" height="{$this->h}" width="{$this->w}" class="{$this->type}"/>
EOF;
		return $svg;
	}
}

class Champ extends Tuile {
	public function toSVG() {
		$svg =<<<EOF
	<use xlink:href="#png_champ" x="{$this->position->x}" y="{$this->position->y}" />
EOF;
		return $svg;
	}
}

class Balise extends Tuile {
	public function toSVG() {
		global $echelle;
		$xa = $this->position->x + 0.1 * $echelle;
		$ya = $this->position->y + 0.1 * $echelle;
		$xb = $xa;
		$yb = $ya + 3;
		$xc = $xa;
		$yc = $ya + 6;
//<use xlink:href="#png_balise" x="{$this->position->x}" y="{$this->position->y}" />
$svg =<<<EOF
<g class="balise">
	<rect class="a" x="{$xa}" y="{$ya}" height="32" width="7" rx="1.3" ry="1.3" />
	<rect class="b" x="{$xb}" y="{$yb}" height="3" width="7" />
	<rect class="c" x="{$xc}" y="{$yc}" height="3" width="7" />
</g>
EOF;
		return $svg;
	}
}

class Lieu extends Tuile {
	public $nom;
	public function __construct($nom, $type, Point $position) {
		global $echelle;
		$this->type = $type;
		$this->nom = $nom;
		$this->position = $position;
		$this->w = $echelle;
		$this->h = $echelle;
	}

	public function toSVG() {
		global $echelle;
		$x = $this->position->x + 0.5 * $echelle;
		$y = $this->position->y + 0.5 * $echelle;
		$svg =<<<EOF
	<rect x="{$this->position->x}" y="{$this->position->y}" height="{$this->h}" width="{$this->w}" />
	<text x="{$x}" y="{$y}" transform="rotate(45 {$x} {$y})">{$this->nom}</text>
	<use xlink:href="#png_{$this->type}" x="{$this->position->x}" y="{$this->position->y}"  />
EOF;
		return $svg;
	}
}

class Buisson extends Tuile {
	public function toSVG() {
	#<rect x="{$this->position->x}" y="{$this->position->y}" height="{$this->h}" width="{$this->w}" class="{$this->type}"/>
		$svg =<<<EOF
	<text x="{$this->position->x}" y="{$this->position->y}">{$this->type}</text>
	<use xlink:href="#png_buisson" x="{$this->position->x}" y="{$this->position->y}"  />
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
	private $colors;
	private $my_position;
	private $w;
	private $h;
	private $p;
	
	/*
	Construit une carte de la taille indiquée avec $w/$h (en pixel)
	*/
	public function __construct($w, $h) {
		$this->w = $w;
		$this->h = $h;
		$this->my_position = null;
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
		
		$p = Props::getProps();
		$p->w = $w;
		$p->h = $h;
		$p->info_w = 400;
		$p->info_h = 100;
		$p->info_x = 100;
		$p->info_y = 150;
	}

	/*
	Génère le svg en parcourant les tableaux remplis dans le constructeurs
	*/
	public function toSVG() {
		// translation pour être soit en 0,0 soit centré sur le joueur
		$tr_x = $this->w / 2;
		$tr_y = $this->h / 2;
		if ($this->my_position != null) {
			$tr_x = $this->my_position->x * -1 + $this->w / 2;
			$tr_y = $this->my_position->y * -1 + $this->h / 2;
		}
		// position du panneau d'info
		$info_w = 150;
		$info_x = $this->w - $info_w;
		
		$info_str = "";
		
		$svg =<<<EOF
<svg version="1.1" baseProfile="full"
	xmlns="http://www.w3.org/2000/svg"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:ev="http://www.w3.org/2001/xml-events"
	width="{$this->w}px" height="{$this->h}px"
	>
<script xlink:href="js/SVGPan.js"/>
<script xlink:href="js/svgmap.js"/>
<defs>
{$this->getStyle()}
<g id="png_joueur"><image xlink:href="img/b/braldun.png" width="19" height="22" /></g>
<g id="png_champ"><image xlink:href="img/b/champ.png" width="32" height="29" /></g>
<g id="png_buisson"><image xlink:href="img/b/buisson.png" width="30" height="30" /></g>
<g id="png_nid"><image xlink:href="img/b/nid.png" width="30" height="30" /></g>

<g id="png_balise"><image xlink:href="img/b/balise.png" width="30" height="30" /></g>

<g id="png_apothicaire"><image xlink:href="img/b/apothicaire.png" width="32" height="32" /></g>
<g id="png_cuisinier"><image xlink:href="img/b/cuisinier.png" width="32" height="32" /></g>
<g id="png_forgeron"><image xlink:href="img/b/forgeron.png" width="32" height="32" /></g>
<g id="png_menuisier"><image xlink:href="img/b/menuisier.png" width="32" height="32" /></g>
<g id="png_tanneur"><image xlink:href="img/b/tanneur.png" width="32" height="32" /></g>

<g id="png_academie"><image xlink:href="img/b/academie.png" width="32" height="32" /></g>
<g id="png_assembleur"><image xlink:href="img/b/assembleur.png" width="32" height="32" /></g>
<g id="png_auberge"><image xlink:href="img/b/auberge.png" width="32" height="32" /></g>
<g id="png_banque"><image xlink:href="img/b/banque.png" width="32" height="32" /></g>
<g id="png_centreformation"><image xlink:href="img/b/centreformation.png" width="32" height="32" /></g>
<g id="png_gare"><image xlink:href="img/b/gare.png" width="32" height="32" /></g>
<g id="png_hall"><image xlink:href="img/b/hall.png" width="32" height="32" /></g>
<g id="png_hopital"><image xlink:href="img/b/hopital.png" width="32" height="32" /></g>
<g id="png_mairie"><image xlink:href="img/b/mairie.png" width="32" height="32" /></g>
<g id="png_marche"><image xlink:href="img/b/marche.png" width="32" height="32" /></g>
<g id="png_notaire"><image xlink:href="img/b/notaire.png" width="32" height="32" /></g>
<g id="png_tabatiere"><image xlink:href="img/b/tabatiere.png" width="32" height="32" /></g>
<g id="png_tribunal"><image xlink:href="img/b/tribunal.png" width="32" height="32" /></g>
<g id="png_tribune"><image xlink:href="img/b/tribune.png" width="32" height="32" /></g>
</defs>
<g id="viewport" transform="translate({$tr_x}, {$tr_y})">
EOF;
// https://github.com/braldahim/braldahim/tree/master/braldahim-static/public/images/vue/batiments
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
			$info_str .= $i->getInfo();
		}
		$svg .= "</g>";

		// on ajoute le panneau d'info en dehors de id="viewport"
		// pour qu'il ne soit pas pris en compte par svgpan
		$svg .=<<<EOF
</g>
{$info_str}
</svg>
EOF;
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
		$style = '<style type="text/css"><![CDATA['.file_get_contents("svgmap.css").']]></style>';
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
	
		global $echelle;
		$query = "SELECT braldahim_id, u.prenom, u.nom, u.x, u.y,
		p.PvRestant, p.nivVigueur*10+40 as pvmax
		FROM ".DB_PREFIX."user u, ".DB_PREFIX."profil p
		WHERE p.idBraldun = u.braldahim_id
 		AND u.x IS NOT NULL
		AND u.y IS NOT NULL
		AND restricted_password IS NOT NULL
		ORDER BY braldahim_id ASC;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$j = new Joueur(
				$row['braldahim_id'],
				$row['prenom'],
				$row['nom'],
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
			$j->pvrestant = $row['PvRestant'];
			$j->pvmax = $row['pvmax'];
			$this->joueurs[] = $j;
			if ($row['braldahim_id'] == $_SESSION['bra_num']) {
				$this->my_position = new Point($row['x']*$echelle, $row['y']*-1*$echelle);
			}
		}
		mysql_free_result($res);
	}

	/*
	Recupere les villes
	*/
	private function getVilles() {
		global $echelle;
		$query = "SELECT nom_ville,
			x_min_ville, y_min_ville,
			x_max_ville, y_max_ville
			FROM ".DB_PREFIX."ville";

		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->villes[] = new Ville(
				$row['nom_ville'],
				new Point($row['x_min_ville']*$echelle, $row['y_max_ville']*-1*$echelle),
				new Point($row['x_max_ville']*$echelle, $row['y_min_ville']*-1*$echelle)
				);
		}
		mysql_free_result($res);
	}

	/*
	Retourne un tableau de toutes les zones
	*/
	private function getZones() {
		global $echelle;
		$query = "SELECT nom_systeme_environnement,
			x_min_zone, y_min_zone,
			x_max_zone, y_max_zone
			FROM ".DB_PREFIX."zone";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->zones[] = new Zone(
				$row['nom_systeme_environnement'],
				new Point($row['x_min_zone']*$echelle, ($row['y_max_zone']+2)*-1*$echelle),
				new Point(($row['x_max_zone']+2)*$echelle, $row['y_min_zone']*-1*$echelle)
				);
		}
		mysql_free_result($res);
	}
	
	/*
	Recupere les routes
	*/
	private function getRoutes() {
		global $echelle;
		$query = "SELECT x, y, type_route
		FROM ".DB_PREFIX."route
		ORDER BY x, y, type_route DESC;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			if ($row['type_route'] == 'balise') {
				$this->routes[] = new Balise(
					$row['type_route'],
					new Point($row['x']*$echelle, $row['y']*-1*$echelle));
			}
			else {
				$this->routes[] = new Tuile(
					$row['type_route'],
					new Point($row['x']*$echelle, $row['y']*-1*$echelle));
			}
		}
		mysql_free_result($res);
	}

	/*
	Recupere les palissade
	*/
	private function getPalissades() {
		global $echelle;
		$query = "SELECT x, y
		FROM ".DB_PREFIX."palissade
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->palissades[] = new Tuile(
				'palissade',
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les champs
	*/
	private function getChamps() {
		global $echelle;
		$query = "SELECT x, y
		FROM ".DB_PREFIX."champ
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->champs[] = new Champ(
				'champ',
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les bosquets
	*/
	private function getBosquets() {
		global $echelle;
		$query = "SELECT x, y, nom_systeme_type_bosquet
		FROM ".DB_PREFIX."bosquet
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->bosquets[] = new Tuile(
				$row['nom_systeme_type_bosquet'],
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les lieux standards
	*/
	private function getLieuStandards() {
		global $echelle;
		#$query = "SELECT x, y, nom_systeme_type_lieu
		$query = "SELECT x, y, nom_lieu, nom_systeme_type_lieu
		FROM ".DB_PREFIX."lieu
		WHERE nom_systeme_type_lieu NOT IN ('lieumythique', 'quete', 'ruine')
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->lieuStandards[] = new Lieu(
				$row['nom_lieu'],
				$row['nom_systeme_type_lieu'],
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les lieux standards
	*/
	private function getLieuMythiques() {
		global $echelle;
		#$query = "SELECT x, y, nom_systeme_type_lieu
		$query = "SELECT x, y, nom_lieu, nom_systeme_type_lieu
		FROM ".DB_PREFIX."lieu
		WHERE nom_systeme_type_lieu IN ('lieumythique', 'quete', 'ruine')
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->lieuMythiques[] = new Lieu(
				$row['nom_lieu'],
				$row['nom_systeme_type_lieu'],
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les environnements
	*/
	private function getEnvironnements() {
		global $echelle;
		$query = "SELECT x, y, nom_systeme_environnement
		FROM ".DB_PREFIX."environnement
		WHERE nom_systeme_environnement != 'plaine'
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->environnements[] = new Tuile(
				strtolower($row['nom_systeme_environnement']),
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les nids standards
	*/
	private function getNids() {
		global $echelle;
		$query = "SELECT x, y, nom_nid
		FROM ".DB_PREFIX."nid
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->nids[] = new Lieu(
				$row['nom_nid'],
				'nid',
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
		}
		mysql_free_result($res);
	}

	/*
	Recupere les buissons
	*/
	private function getBuissons() {
		global $echelle;
		$query = "SELECT x, y, nom_type_buisson
		FROM ".DB_PREFIX."buisson
		ORDER BY x, y;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->buissons[] = new Buisson(
				$row['buisson'],
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
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
