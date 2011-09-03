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

include("svgmap_element.php");

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
		$p->panneau_speed = "0.3";
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
		$this->panneau_w = 150;
		$this->panneau_x = $this->w;
		
		$open_close_buisson_x = $this->w - $this->panneau_w - 40;
		$open_close_ville_x = $open_close_buisson_x - 40;
		$open_close_joueur_x = $open_close_ville_x - 40;
		
		
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

		$svg .= '<g class="palissade">';
		foreach ($this->palissades as $i) {
			$svg .= $i->toSVG();
		}
		$svg .= "</g>";

		$svg .= '<g class="buisson">';
		foreach ($this->buissons as $i) {
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

{$this->getPanneauJoueur()}
{$this->getPanneauVille()}
{$this->getPanneauBuisson()}
<g class="panneau_bouton">
	<rect id="panneau_open_close_joueur" x="{$open_close_joueur_x}" y="0" width="30" height="30"/>
	<rect id="panneau_open_close_ville" x="{$open_close_ville_x}" y="0" width="30" height="30"/>
	<rect id="panneau_open_close_buisson" x="{$open_close_buisson_x}" y="0" width="30" height="30"/>
</g>
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
	Construit le panneau lateral avec le nom des membres
	*/
	private function getPanneauJoueur() {
		$p = Props::getProps();
		$this->getJoueurs();
		$x = $this->panneau_x + 5;
		$y = 20;

		$str =<<<EOF
<g id="panneau_joueur" class="panneau">
	<animateTransform id="panneau_joueur_in"
		attributeName="transform" type="translate"
		dur="{$p->panneau_speed}" begin="indefinite"
		from="0" to="-{$this->panneau_w}"
		fill="freeze" />
	<animateTransform id="panneau_joueur_out"
		attributeName="transform" type="translate"
		dur="{$p->panneau_speed}" begin="indefinite"
		from="-{$this->panneau_w}" to="0"
		fill="freeze" />
	<rect x="{$this->panneau_x}" y="0" width="{$this->panneau_w}" height="{$this->h}"/>
	<text x="{$x}" y="{$y}" class="panneau_titre">Joueurs</text>
EOF;
		foreach ($this->joueurs as $e) {
			$y += 20;
			$str .=<<<EOF
<text id="centre_joueur{$e->id}_{$e->position->x}_{$e->position->y}" x="{$x}" y="{$y}">{$e->prenom} {$e->nom}</text>
EOF;
		}
		$str .= "</g>";
		return $str;
	}
	
	/*
	Construit le panneau lateral avec le nom des villes
	*/
	private function getPanneauVille() {
		$p = Props::getProps();
		$this->getVilles();
		$x = $this->panneau_x + 5;
		$y = 20;

		$str =<<<EOF
<g id="panneau_ville" class="panneau">
	<animateTransform id="panneau_ville_in"
		attributeName="transform" type="translate"
		dur="{$p->panneau_speed}" begin="indefinite"
		from="0" to="-{$this->panneau_w}"
		fill="freeze" />
	<animateTransform id="panneau_ville_out"
		attributeName="transform" type="translate"
		dur="{$p->panneau_speed}" begin="indefinite"
		from="-{$this->panneau_w}" to="0"
		fill="freeze" />
	<rect x="{$this->panneau_x}" y="0" width="{$this->panneau_w}" height="{$this->h}"/>
	<text x="{$x}" y="{$y}" class="panneau_titre">Villes</text>
EOF;
		foreach ($this->villes as $e) {
			$vx = $e->start->x + $e->w / 2;
			$vy = $e->start->y + $e->h / 2;
			$y += 20;
			$str .=<<<EOF
<text id="centre_ville_{$vx}_{$vy}" x="{$x}" y="{$y}">{$e->nom}</text>
EOF;
		}
		$str .= "</g>";
		return $str;
	}
	
	/*
	Construit le panneau lateral avec le nom des buissons
	*/
	private function getPanneauBuisson() {
		$p = Props::getProps();
		$this->getTypeBuissons();
		$x = $this->panneau_x + 5;
		$y = 20;

		$str =<<<EOF
<g id="panneau_buisson" class="panneau">
	<animateTransform id="panneau_buisson_in"
		attributeName="transform" type="translate"
		dur="{$p->panneau_speed}" begin="indefinite"
		from="0" to="-{$this->panneau_w}"
		fill="freeze" />
	<animateTransform id="panneau_buisson_out"
		attributeName="transform" type="translate"
		dur="{$p->panneau_speed}" begin="indefinite"
		from="-{$this->panneau_w}" to="0"
		fill="freeze" />
	<rect x="{$this->panneau_x}" y="0" width="{$this->panneau_w}" height="{$this->h}"/>
	<text x="{$x}" y="{$y}" class="panneau_titre">Buisson</text>
EOF;
		foreach ($this->type_buissons as $e) {
			$y += 20;
			$type = Buisson::sanitize($e);
			$str .=<<<EOF
<text id="buisson_type_{$type}" x="{$x}" y="{$y}">{$e}</text>
EOF;
		}
		$str .= "</g>";
		return $str;
	}
	
	/*
	Recupere les joueurs de la communauté
	*/
	private function getJoueurs() {
		if (! empty($this->joueurs)) {
			return $this->joueurs;
		}
		global $echelle;
		$query = "SELECT braldahim_id, u.prenom, u.nom, u.x, u.y,
		p.PvRestant, p.nivVigueur*10+40 as pvmax, p.pxPerso, p.niveau,
		p.bbdf
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
			$j->niveau = $row['niveau'];
			$j->pxperso = $row['pxPerso'];
			$j->bbdf = $row['bbdf'];
			
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
		if (! empty($this->villes)) {
			return $this->villes;
		}
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
				preg_replace("/Buisson d['|e]/", '', $row['nom_type_buisson']),
				new Point($row['x']*$echelle, $row['y']*-1*$echelle));
		}
		mysql_free_result($res);
	}
	
	/*
	Recupere les type de buissons
	*/
	private function getTypeBuissons() {
		global $echelle;
		$query = "SELECT distinct(nom_type_buisson) as nom
		FROM ".DB_PREFIX."buisson
		ORDER BY nom_type_buisson;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->type_buissons[] = preg_replace("/Buisson d['|e]/", '', $row['nom']);
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

$carte = new Carte(800, 600);
// utilisation de "ob_gzhandler" pour gzipper le fichier svg,
// la taille est divisé par 10
ob_start("ob_gzhandler");
$carte->display();
ob_end_flush();

?>
