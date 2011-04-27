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
if (! isset($_SESSION['bra_num'])) exit();

require_once("conf.php");
error_reporting(E_ALL ^ E_NOTICE);

/*
Trie les joueurs sur leur position
*/
function sort_player($a, $b) {
	if ($a->position->x == $b->position->x) {
		if ($a->position->y == $b->position->y) {
			return 0;
		}
		return ($a->position->y < $b->position->y) ? -1 : 1;
	}
	return ($a->position->x < $b->position->x) ? -1 : 1;
}
	
$carte = null;
$zoom = null;
$type = null;

if (isset($_REQUEST['zoom'])) {
	$zoom = $_REQUEST['zoom'];
}
if (isset($_REQUEST['type'])) {
	$type = $_REQUEST['type'];
}
if (isset($_REQUEST['x'])) {
	$x = $_REQUEST['x'];
}
if (isset($_REQUEST['y'])) {
	$y = $_REQUEST['y'];
}
$carte = new Carte(500, $type, $zoom, $x, $y);

$carte->generateImage();

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
class Player {
	public $id;
	public $nom;
	public $prenom;
	public $position;
	
	public function __construct($id, $prenom, $nom, Point $position) {
		$this->id = $id;
		$this->prenom = $prenom;
		$this->nom = $nom;
		$this->position = $position;
	}
	
	public function __toString() {
		return "[{$this->id}] {$this->prenom} {$this->nom} {$this->position}";
	}
}

/*
Genere une carte du monde centrée sur les joueurs
*/
class Carte {
	private $db;
	private $players;
	private $player_size;
	private $size;
	private $type;
	private $origine;
	private $zoom;
	private $user_x;
	private $user_y;
	private $img;
	private $colors;
	private $font_size; // for gd font
	private $ttfont_size; // for ttf font
	private $use_cache;
	private $filename;
	private $p_min; // point min en coordonnées position
	private $p_max; // point max en coordonnées position
	
	private $debug = true;
	
	/*
	Construit une carte de la taille indiqué avec size (en pixel)
	et representant le sujet indiqué par type (fond, joueur, lieu)
	$user_zoom : valeur du zoom utilisateur
	$user_x, $user_y : décalage demandé par l'utilisateur par rapport à l'origine
	*/
	public function __construct($size, $type, $user_zoom, $user_x, $user_y) {
		$this->size = $size;
		$this->type = ($type == null) ? "fond" : $type;
		$this->players_size = 10; // un rond de 5 pixel de diametre
		$this->font_size = 2;
		$this->ttfont_size = 8;
		$this->players = array();
		
		$this->zoom = DEF_ZOOM;
		if ($user_zoom != null && is_numeric($user_zoom) && 0 < $user_zoom && $user_zoom < 100) {
			$this->zoom = floor($user_zoom);
		}
		// niveau de zoom => largeur des tiles
		// 40 est la largeur des tiles dans le zoom le plus fort (1)
		$this->tile_size = 40 / pow(2, $this->zoom-1);
		
		if ($user_x == null || !is_numeric($user_x) || $user_x == 0) {
			$this->user_x = 0;
		}
		else {
			$this->user_x = $user_x;
		}
		
		if ($user_y == null || !is_numeric($user_y) || $user_y == 0) {
			$this->user_y = 0;
		}
		else {
			$this->user_y = $user_y;
		}
		
		$this->img = imagecreatetruecolor($this->size, $this->size);
		$this->createColors();
		
		$this->db = mysql_connect(DB_HOST, DB_USER, DB_PASS);
		mysql_select_db(DB_NAME);
		mysql_set_charset('utf8', $this->db);
		mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
		
		$this->filename = "cache/img/{$this->type}.{$this->zoom}.{$this->user_x}.{$this->user_y}.png";
		
		$this->needToUpdate();
		
		// pour la légende, on n'a pas besoin d'aller plus loin
		if ($type=='legende') return;
		
		// nettoyage du cache si on doit le regénérer
		if (! $this->use_cache) {
			$this->clean_cache();
		}
		
		$this->getPlayers();
		$this->setOrigine();
	}
	
	/*
	Met à jour l'attribut 'use_cache', qui indique si on doit utiliser
	les images en cache ou bien les regénérer.
	On se base sur la présence de joueurs qui ont été mis à jour.
	*/
	private function needToUpdate() {
		// debug ou existence des fichiers
		if ($this->debug || !file_exists($this->filename)) {
			$this->use_cache = false;
			return;
		}
		
		// des joueurs ont été mis à jour
		$query = "SELECT count(*) FROM ".DB_PREFIX."user WHERE updated=true;";
		$res = mysql_query($query);
		$this->use_cache = false;
		if ($row = mysql_fetch_row($res)) {
			$this->use_cache = ($row[0] == 0);
		}
		mysql_free_result($res);
		if (! $this->use_cache) {
			return;
		}
		
		// des ressources ont été mise à jour
		$query = sprintf("SELECT dirty FROM ".DB_PREFIX."ressource WHERE type='%s';",
			mysql_real_escape_string($this->type));
		$res = mysql_query($query);
		$this->use_cache = false;
		if ($row = mysql_fetch_row($res)) {
			 $this->use_cache = ($row[0] == 0);
		}
		mysql_free_result($res);
	}

	/*
	Parcourt le 'cache' et efface tous les fichiers png
	*/
	private function clean_cache() {
		array_map("unlink", glob("cache/img/{$this->type}*.png"));
	}
	
	/*
	Change la valeur du champ dirty dans la table ressource pour le type en cours
	*/
	private function updateRessource() {
		$query = sprintf("UPDATE ".DB_PREFIX."ressource SET dirty=false WHERE type='%s';",
			mysql_real_escape_string($this->type));
		mysql_query($query);
	}
	
	/*
	Change la valeur du champ updated dans la table user
	*/
	private function updateUser($id) {
		$query = sprintf("UPDATE ".DB_PREFIX."user SET updated=false WHERE braldahim_id=%s;",
			mysql_real_escape_string($id));
		mysql_query($query);
	}
	
	private function createColors() {
		$colors = array(
			'black'		=> array(0, 0, 0),
			'red'		=> array(255, 0, 0),
			'blue'		=> array(0, 0, 255),
			// couleurs générales
			'name_bg'	=> array(255, 255, 255, 20),
			'grid'		=> array(255, 255, 255),
			'background'	=> array(0, 59, 0),
			'legendbg'	=> array(59, 159, 59),
			'transparent'	=> array(10, 10, 10),
			'transparent_alpha'	=> array(10, 10, 10, 127),
			'player'	=> array(70, 220, 240),
			
			// Couleur pour le type ROUTE
			'route'		=> array(180, 180, 180),
			'echoppe'	=> array(180, 180, 180),
			'ville'		=> array(180, 180, 180),
			'ruine'		=> array(80, 80, 80),
			'balise'	=> array(150, 130, 120),
			'palissade'	=> array(255, 185, 63),
			// Couleur pour le type ENVIRONNEMENT
			'plaine'	=> array(0, 153, 0),
			'eau'		=> array(130, 200, 230),
			'peuprofonde'	=> array(130, 200, 230),
			'profonde'	=> array(100, 170, 200),
			'mer'	=> array(0, 0, 139),
			'lac'	=> array(0, 0, 139),
			'montagne'	=> array(200, 200, 200),
			'mine'	=> array(200, 200, 200),
			'caverne'	=> array(200, 200, 200),
			'marais'	=> array(65, 105, 225),
			'gazon'	=> array(0, 101, 0),
			// Couleur pour le type BOSQUET
			'peupliers'	=> array(70, 220, 70),
			'hetres'	=> array(70, 220, 70),
			'chenes'	=> array(70, 220, 70),
			'erables'	=> array(70, 220, 70),
			// Couleur pour le type champ
			'champ'		=> array(150, 100, 50),
			// Couleur pour le type champ
			'nid'		=> array(150, 10, 10),
			// Couleur pour les lieux importants
			'lieu_point'	=> array(255, 0, 0),
			'lieu_str'	=> array(0, 0, 0),
			// Couleur pour le brouillard
			'fog_never'	=> array(128, 128, 128, 32),
			'fog_hard'	=> array(128, 128, 128, 48),
			'fog_medium'	=> array(128, 128, 128, 64),
			'fog_light'	=> array(128, 128, 128, 80),
			'fog_clear'	=> array(255, 255, 255, 127),
		);
		
		$this->colors = array();
		foreach ($colors as $k => $v) {
			if (count($v) == 3) {
				$this->colors[$k] = imagecolorallocate($this->img, $v[0], $v[1], $v[2]);
			}
			else {
				$this->colors[$k] = imagecolorallocatealpha($this->img, $v[0], $v[1], $v[2], $v[3]);
			}
		}
	}
	
	/*
	On passe une position en coordonnées logique (par rapport au jeu) et
	la fonction retourne les coordonnées du centre de la case en coordonées
	physique de l'image
	*/
	private function positionToPixel(Point $p) {
		//retirer 0.5 pour etre en haut à gauche
		$x = ($this->size/2) + ($p->x - $this->origine->x) * $this->tile_size;
		$y = ($this->size/2) - ($p->y - $this->origine->y) * $this->tile_size;
		return new Point($x, $y);
	}
	
	/*
	On passe une position en coordonnées physique (par rapport à l'image) et
	la fonction retourne les coordonnées logique de la case
	*/
	private function pixelToPosition(Point $p) {
		// FIXME le "+1" permet de réaligner les joueur et les tiles...
		$x = $this->origine->x + (($p->x - ($this->size/2)) / $this->tile_size)+1;
		$y = $this->origine->y + (($p->y - ($this->size/2)) / ($this->tile_size * -1));
		return new Point(floor($x), floor($y));
	}
	
	/*
	Tronque les coordonnées d'un point en pixel
	pour qu'il ne dépasse pas les dimensions de l'image
	*/
	private function truncatePoint($p) {
		if ($p->x < 0) $p->x = 0;
		if ($p->y < 0) $p->y = 0;
		if ($p->x > $this->size) $p->x = $this->size;
		if ($p->y > $this->size) $p->y = $this->size;
	}
	
	/*
	Recupere les joueurs de la communauté
	*/
	private function getPlayers() {
		$query = "SELECT braldahim_id, prenom, nom, x, y
		FROM ".DB_PREFIX."user
		WHERE x IS NOT NULL
		AND y IS NOT NULL
		AND restricted_password IS NOT NULL
		ORDER BY braldahim_id ASC;";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->players[] = new Player(
				$row['braldahim_id'],
				$row['prenom'],
				$row['nom'],
				new Point($row['x'], $row['y']));
		}
		mysql_free_result($res);
	}

	/*
	Calcul les coordonnées du centre du repere de la carte
	Calcul egalement l'echelle de la carte (min, max)
	*/
	private function setOrigine() {
		$this->origine = new Point(0, 0);
		
		// On décalle l'origine comme le souhaite l'utilisateur
		$this->origine->x += $this->user_x;
		$this->origine->y += $this->user_y;
		
		$this->p_min = $this->pixelToPosition(new Point(0, $this->size)); // coin bas gauche (min X et min Y)
		$this->p_max = $this->pixelToPosition(new Point($this->size, 0)); // coin haut droite (max X et max Y)
		// Calcul du zoom pour afficher tous les joueurs en cas de valeur par defaut
		/*
		// On cherche la position la plus éloignée du centre (sur x ou y) en valeur absolue
		// On cherche également le nom le plus long à afficher (pour prévoir de la marge)
		$position_max = 1;
		$nom_max = 0;
		foreach ($this->players as $p) {
			$position_max = max($position_max, $p->position->distanceMax($this->origine));
			$nom_max = max($nom_max, strlen("{$p->prenom} {$p->nom}"));
		}*/
	}
	
	private function info() {
		$str = "centre: {$this->origine} - zoom: {$this->zoom}";
		
		imagefilledrectangle($this->img,
			10,
			10,
			10 + imagefontwidth($this->font_size) * strlen($str),
			10 + imagefontheight($this->font_size),
			$this->colors['background']);
		
		imagestring($this->img, $this->font_size,
			10, 10, iconv("UTF8", "ISO-8859-1", $str), $this->colors['red']);	
	}
	
	/*
	Dessine un joueur : un point plus son nom
	$p : objet Player
	$prev_count : nombre de joueurs avec la meme position
	*/
	private function drawPlayer($p, $prev_count=0) {
		// Pour obtenir un fond transparent et des polices antialiasées
		// il faut supprimer l'alphablending et activé la transparence du png
		// avec imagesavealpha
		imagesavealpha($this->img, true);
		imagealphablending($this->img, false);
		imagefill($this->img, 0, 0, $this->colors['transparent_alpha']);
		
		// coordonnées du centre
		$pos = $this->positionToPixel($p->position);
		// pour faire pointer au centre de la case
		$pos->x += $this->tile_size/2;
		$pos->y += $this->tile_size/2;
		
		// nom et bounding box
		$name = "{$p->prenom} {$p->nom}";
		$bbox = imagettfbbox($this->ttfont_size, 0, "./DejaVuSans.ttf", $name);
		$name_width = $bbox[2] - $bbox[0];
		$name_height = $bbox[5] - $bbox[3];
		
		// dessin du point
		imagefilledellipse($this->img,
			$pos->x, $pos->y,
			$this->players_size, $this->players_size,
			$this->colors['player']);
		
		// dessin du contour du point
		imageellipse($this->img,
			$pos->x, $pos->y,
			$this->players_size, $this->players_size,
			$this->colors['black']);

		$delta_y = $prev_count * $name_height;
		
		// dessin du fond du nom
		imagefilledrectangle($this->img,
			$pos->x - $name_width / 2,
			$pos->y - $name_height + $this->players_size / 2 - $delta_y,
			$pos->x + $name_width / 2,
			$pos->y + $this->players_size / 2 - $delta_y,
			$this->colors['name_bg']);
		
		// dessin du nom	
		imagettftext($this->img, $this->ttfont_size, 0,
			$pos->x - $name_width / 2,
			$pos->y - $name_height + $this->players_size / 2 - $delta_y,
			$this->colors['red'], "./DejaVuSans.ttf", $name);
	}
	
	/*
	Dessine la grille
	*/
	private function drawGrid() {
		// on affiche 2 items verticalement et horizontalement
		$distance = floor($this->size / 3);
		$pixel = new Point($distance, 0);
		$position = $this->pixelToPosition($pixel);
		imagestring($this->img, $this->font_size,
			$pixel->x,
			3,
			$position->x, $this->colors['grid']);
		
		$pixel = new Point($distance*2, 0);
		$position = $this->pixelToPosition($pixel);
		imagestring($this->img, $this->font_size,
			$pixel->x,
			3,
			$position->x, $this->colors['grid']);
		
		$pixel = new Point(0, $distance);
		$position = $this->pixelToPosition($pixel);
		imagestring($this->img, $this->font_size,
			3,
			$pixel->y,
			$position->y, $this->colors['grid']);
		
		$pixel = new Point(0, $distance*2);
		$position = $this->pixelToPosition($pixel);
		imagestring($this->img, $this->font_size,
			3,
			$pixel->y,
			$position->y, $this->colors['grid']);
	}
	
	/*
	Affiche les images pour chaque case.
	*/
	private function drawTile() {
		// On parcours l'ensemble des pixels de gauche à droite et de haut en bas.
		// Pour chaque position en pixel (x,y), on récupère la position logique en tile.
		// Ensuite on intérroge la DB pour obtenir le type de tile à afficher.
		// On boucle, en incrémentant du zoom. Cela dit comme c'est excessivement couteux
		// on utilise un multiple du zoom (donc le dessin est moins précis)
		
		// On cherche les position minimun et maximum sur le repère "position",
		// puis on interroge la base de données avec des "x between ... AND ..."
		// afin de recuperer l'ensemble des points pour lesquels on a une info.
		// C'est incroyablement plus rapide que de lancer une requête par point logique :
		// il y a un facteur 100 (0.1s à 10s) !
		
		$tiles_list = $this->getTiles();
		foreach ($tiles_list as $name => $tile) {
			list($x, $y) = explode(';', $name);
			$p_physique = $this->positionToPixel(new Point($x, $y));
			$color = '';
			switch($tile['type']) {
				case 'champ':
					$color = $this->colors['champ'];
					break;
				case 'palissade':
					$color = $this->colors['palissade'];
					break;
				case 'route':
					$color = $this->colors[strtolower($tile['type_route'])];
					break;
				case 'environnement':
					$color = $this->colors[strtolower($tile['nom_systeme_environnement'])];
					break;
				case 'bosquet':
					$color = $this->colors[strtolower($tile['nom_systeme_type_bosquet'])];
					break;
				default:
					$color = $this->colors['black'];
			}
			imagefilledrectangle($this->img,
				$p_physique->x,
				$p_physique->y,
				$p_physique->x + $this->tile_size,
				$p_physique->y + $this->tile_size,
				$color);
		}
	}
	
	/*
	Retourne les détails correspondant à l'ensemble des cases passées en paramètre.
	*/
	private function getTiles() {
		$x_min = $this->p_min->x-1; // -1 pour avoir les tiles à cheval sur le bord gauche
		$x_max = $this->p_max->x;
		$y_min = $this->p_min->y;
		$y_max = $this->p_max->y+1; // +1 pour avoir les tiles à cheval sur le bord haut
		$tiles = array();;
		// on va essayer toutes les tables dans un ordre précis
		// et on s'arrête dès qu'on a une info pertinente
		$table_list = array('champ', 'palissade', 'route', 'bosquet', 'environnement');
		foreach ($table_list as $table) {
			$query = "SELECT * FROM ".DB_PREFIX."{$table} WHERE x BETWEEN {$x_min} AND {$x_max} AND y BETWEEN {$y_min} AND {$y_max}";
			$res = mysql_query($query);
			if (mysql_num_rows($res) == 0) {
				mysql_free_result($res);
				continue;
			}
			else {
				while ($row = mysql_fetch_assoc($res)) {
					// la clé du tableau tiles sera 'x;:y'
					$name = $row['x'].';'.$row['y'];
					if (! array_key_exists($name, $tiles)) {
						$tiles[$name] = $row;
						$tiles[$name]['type'] = $table;
					}
				}
				mysql_free_result($res);
			}
		}
		return $tiles;
	}
	
	/*
	Dessine les zones issues du csv
	*/
	private function drawZone() {
		$zones = $this->getZone();
		foreach ($zones as $z) {
			$p1 = $this->positionToPixel(new Point($z['x_min_zone'], $z['y_max_zone']));
			$p2 = $this->positionToPixel(new Point($z['x_max_zone'], $z['y_min_zone']));
			$this->truncatePoint(&$p1);
			$this->truncatePoint(&$p2);
			imagefilledrectangle($this->img,
				$p1->x,
				$p1->y,
				$p2->x + $this->tile_size,
				$p2->y + $this->tile_size,
				$this->colors[$z['nom_systeme_environnement']]);
		}
	}
	
	/*
	Retourne un tableau de toutes les zones
	*/
	private function getZone() {
		$zones = array();
		$query = "SELECT nom_systeme_environnement,
			x_min_zone, y_min_zone,
			x_max_zone, y_max_zone
			FROM ".DB_PREFIX."zone";
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			mysql_free_result($res);
		}
		else {
			while ($row = mysql_fetch_assoc($res)) {
				$zones[] = $row;
			}
			mysql_free_result($res);
		}
		return $zones;
	}
	
	/*
	Dessine les villes issues du csv
	*/
	private function drawVille() {
		$zones = array();
		$query = "SELECT nom_ville,
			x_min_ville, y_min_ville,
			x_max_ville, y_max_ville
			FROM ".DB_PREFIX."ville";
		$res = mysql_query($query);
		if (mysql_num_rows($res) != 0) {
			while ($row = mysql_fetch_assoc($res)) {
				// on calcul les 4 points du polygone.
				$p1 = $this->positionToPixel(new Point($row['x_min_ville'], $row['y_max_ville']));
				$p2 = $this->positionToPixel(new Point($row['x_max_ville'], $row['y_max_ville']));
				$p3 = $this->positionToPixel(new Point($row['x_max_ville'], $row['y_min_ville']));
				$p4 = $this->positionToPixel(new Point($row['x_min_ville'], $row['y_min_ville']));
				$this->truncatePoint(&$p1);
				$this->truncatePoint(&$p2);
				$this->truncatePoint(&$p3);
				$this->truncatePoint(&$p4);
				// on ne dessine pas la ville si elle n'est pas "dans le champ"
				if ($p1->equals($p2) || $p2->equals($p3)) continue;
				// dessin du contour de la ville
				imagepolygon($this->img,
					array(
						$p1->x, $p1->y,
						$p2->x + $this->tile_size, $p2->y,
						$p3->x + $this->tile_size, $p3->y + $this->tile_size,
						$p4->x, $p4->y + $this->tile_size,
					),
					4, $this->colors['red']);
				
				$bbox = imagettfbbox($this->ttfont_size, 0, "./DejaVuSans.ttf", $row['nom_ville']);
				$name_width = $bbox[2] - $bbox[0];
				$name_height = $bbox[5] - $bbox[3];
				// dessin du nom
				imagettftext($this->img, $this->ttfont_size, 0,
					$p1->x + (($p2->x + $this->tile_size - $p1->x) / 2) - ($name_width / 2),
					$p3->y - $name_height + $this->tile_size,
					$this->colors['black'], "./DejaVuSans.ttf", $row['nom_ville']);
			}
		}
		mysql_free_result($res);
	}
	
	/*
	Dessine uniquement les lieux mythique et les lieux de quetes
	*/
	private function drawLieuMythiqueQuetes() {
		$this->drawLieu("nom_systeme_type_lieu IN ('lieumythique', 'quete', 'ruine')");
	}
	
	/*
	Dessine tous les lieux non spécifiques
	*/
	private function drawLieuStandard() {
		$this->drawLieu("nom_systeme_type_lieu NOT IN ('lieumythique', 'quete', 'ruine')");
	}
	
	/*
	Dessine l'ensemble des lieux qui se trouve dans l'espace couvert par la carte
	On peut restreindre la liste en passant une clause where.
	*/
	private function drawLieu($where=null) {
		imagesavealpha($this->img, true);
		imagealphablending($this->img, false);
		imagefill($this->img, 0, 0, $this->colors['transparent_alpha']);
		
		// On va chercher tous les lieux qui se trouvent
		// entre les bornes maximales de la carte
		$p_min = $this->pixelToPosition(new Point(0, $this->size)); // coin bas gauche (min X et min Y)
		$p_max = $this->pixelToPosition(new Point($this->size, 0)); // coin haut droite (max X et max Y)
		$query = "SELECT x, y, nom_lieu
			FROM ".DB_PREFIX."lieu
			WHERE x BETWEEN {$p_min->x} AND {$p_max->x}
			AND y BETWEEN {$p_min->y} AND {$p_max->y}";
		if ($where != null && $where != '') {
			$query .= " AND $where ";
		}
		$query .= ";";
		
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->drawPointText(new Point($row['x'], $row['y']), $row['nom_lieu'], $this->colors['lieu_point'], false);
		}
		mysql_free_result($res);
	}
	
	/*
	Dessine la légende
	*/
	private function drawLegend() {
		$x = 50;
		$y = 100;
		$h = imagefontheight($this->font_size);
		
		imagefilledrectangle($this->img, $x, $y, $x+150, $y+300, $this->colors['legendbg']);
		imagerectangle($this->img, $x, $y, $x+150, $y+300, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+10, $y+$h, 'Legende', $this->colors['black']);
		$x += 30;
		$y += 50;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['plaine']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Plaine', $this->colors['black']);
		$y += 2*$h;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['eau']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Eau', $this->colors['black']);
		$y += 2*$h;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['peupliers']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Bosquet', $this->colors['black']);
		$y += 2*$h;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['route']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Route', $this->colors['black']);
		$y += 2*$h;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['balise']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Balise', $this->colors['black']);
		$y += 2*$h;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['palissade']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Palissade', $this->colors['black']);
		$y += 2*$h;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['champ']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Champ', $this->colors['black']);
		$y += 2*$h;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['nid']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Nid', $this->colors['black']);
		$y += 2*$h;
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['ruine']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Ruine', $this->colors['black']);
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
	
	/*
	Dessine les nids
	*/
	private function drawNid() {
		// Pour obtenir un fond transparent et des polices antialiasées
		// il faut supprimer l'alphablending et activer la transparence du png
		// avec imagesavealpha
		imagesavealpha($this->img, true);
		imagealphablending($this->img, false);
		imagefill($this->img, 0, 0, $this->colors['transparent_alpha']);
		
		// On va chercher tous les objets qui se trouvent
		// entre les bornes maximales de la carte
		$p_min = $this->pixelToPosition(new Point(0, $this->size)); // coin bas gauche (min X et min Y)
		$p_max = $this->pixelToPosition(new Point($this->size, 0)); // coin haut droite (max X et max Y)
		$query = "SELECT x, y, nom_nid
			FROM ".DB_PREFIX."nid
			WHERE x BETWEEN {$p_min->x} AND {$p_max->x}
			AND y BETWEEN {$p_min->y} AND {$p_max->y};";
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->drawPointText(new Point($row['x'], $row['y']), $row['nom_nid'], $this->colors['nid'], false);
		}
		mysql_free_result($res);
	}

	/*
	Dessine un point et un texte
	*/
	private function drawPointText($position, $text, $color, $useBg=false) {
		// coordonnées du centre
		$pos = $this->positionToPixel($position);
		// pour faire pointer au centre de la case
		$pos->x += $this->tile_size/2;
		$pos->y += $this->tile_size/2;
		
		// bounding box
		$bbox = imagettfbbox($this->ttfont_size, 0, "./DejaVuSans.ttf", $text);
		$text_width = $bbox[2] - $bbox[0];
		$text_height = $bbox[5] - $bbox[3];
		
		// dessin du point
		imagefilledellipse($this->img,
			$pos->x, $pos->y,
			$this->players_size, $this->players_size,
			$color);
		
		// dessin du contour du point
		imageellipse($this->img,
			$pos->x, $pos->y,
			$this->players_size, $this->players_size,
			$this->colors['black']);
		
		// dessin du fond du text
		if ($useBg) {
			imagefilledrectangle($this->img,
				$pos->x - $text_width / 2,
				$pos->y - $text_height + $this->players_size / 2,
				$pos->x + $text_width / 2,
				$pos->y + $this->players_size / 2,
				$this->colors['name_bg']);
		}
		
		// dessin du text
		imagettftext($this->img, $this->ttfont_size, 0,
			$pos->x - $text_width / 2,
			$pos->y - $text_height + $this->players_size / 2,
			$this->colors['lieu_str'], "./DejaVuSans.ttf", $text);
	}

	/*
	Affiche le temps utilisé pour générer l'image.
	*/
	private function addTimeUsed($time) {
		$str = sprintf("Généré en %.2fs", $time);
		$w = imagefontwidth($this->font_size) * strlen($str);
		$h = imagefontheight($this->font_size);
		imagefilledrectangle($this->img,
			0, $this->size - $h,
			2 + $w, $this->size,
			$this->colors['name_bg']);
		
		imagestring($this->img, $this->font_size,
			2, $this->size - $h,
			iconv("UTF8", "ISO-8859-1", $str), $this->colors['red']);
	}

	/*
	Génère l'image
	*/
	public function generateImage() {
		// si on n'utilise pas le cache alors on génère l'image
		if (! $this->use_cache) {
			switch($this->type) {
				case "fond":
					$time_start = microtime(true);
					// mise en place du fond
					imagefilledrectangle($this->img, 0, 0, $this->size, $this->size, $this->colors['background']);
					// dessin des zones géographiques
					$this->drawZone();
					// dessin de l'environnement
					$this->drawTile();
					// dessin des villes
					$this->drawVille();
					// dessin de la grille
					$this->drawGrid();
					// temps de génération
					$this->addTimeUsed(microtime(true) - $time_start);
					// ajout des infos de debug
					if ($this->debug) {
						$this->info();
					}
					imagecolortransparent($this->img, $this->colors['transparent']);
					break;
				case "brouillard":
					// brouille les zones non visitées
					$this->drawBrouillard();
					break;
				case "nid":
					$this->drawNid();
					break;
				case "joueur":
					// dessin des joueurs
					$prev_pos = null; // position du joueur precedent
					$prev_count = 0; // nombre de joueurs avec la meme position
					// lo'rdre n'est pas celui x,y, mais id_bral => il faut faire un recherche ou retrier
					$sorted_players = $this->players;
					usort($sorted_players, "sort_player");
					foreach ($sorted_players as $p) {
						if ($prev_pos != null && $prev_pos->equals($p->position)) {
							$prev_count++;
						}
						else {
							$prev_count = 0;
						}
						$this->drawPlayer($p, $prev_count);
						$this->updateUser($p->id);
						$prev_pos = $p->position;
					}
					break;
				case "lieu":
					// mise en place du fond
					imagefilledrectangle($this->img, 0, 0, $this->size, $this->size, $this->colors['transparent']);
					// dessin des lieux importants
					$this->drawLieu();
					break;
				case "lieumythique":
					// mise en place du fond
					imagefilledrectangle($this->img, 0, 0, $this->size, $this->size, $this->colors['transparent']);
					// dessin des lieux importants
					$this->drawLieuMythiqueQuetes();
					break;
				case "lieustandard":
					// mise en place du fond
					imagefilledrectangle($this->img, 0, 0, $this->size, $this->size, $this->colors['transparent']);
					// dessin des lieux importants
					$this->drawLieuStandard();
					break;
				case "legende":
					// mise en place du fond
					imagefilledrectangle($this->img, 0, 0, $this->size, $this->size, $this->colors['transparent']);
					// dessin des lieux importants
					$this->drawLegend();
					imagecolortransparent($this->img, $this->colors['transparent']);
					break;
					
			}
			$this->updateRessource();
			
			imagepng($this->img, $this->filename);
			imagedestroy($this->img);
		}
		
		// on va chercher le fichier précédement créé
		if (file_exists($this->filename)) {
			if (! $this->debug) header('Content-Disposition: attachment; filename='.basename($this->filename));
			header('Content-type: image/png');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . filesize($this->filename));
			//header('Cache-Control: public, max-age=60' );
			ob_clean();
			flush();
			readfile($this->filename);
			exit;
		}
	}
}

?>
