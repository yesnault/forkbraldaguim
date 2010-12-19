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
if (! isset($_SESSION['bra_num'])) exit();

require_once("conf.php");

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
	private $font_size;
	private $use_cache;
	private $filename;
	
	private $debug = false;
	
	/*
	Construit une carte de la taille indiqué avec size (en pixel)
	et representant le sujet indiqué par type (fond, joueur, lieu)
	*/
	public function __construct($size, $type, $user_zoom, $user_x, $user_y) {
		$this->size = $size;
		$this->type = ($type == null) ? "fond" : $type;
		$this->players_size = 10; // un rond de 5 pixel de diametre
		$this->font_size = 2;
		$this->players = array();
		
		$this->zoom = 4;
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
		$query = "SELECT count(*) FROM user WHERE updated=true;";
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
		$query = sprintf("SELECT dirty FROM ressource WHERE type='%s';",
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
		array_map("unlink", glob('cache/img/${$this->type}*.png'));
	}
	
	/*
	Change la valeur du champ dirty dans la table ressource pour le type en cours
	*/
	private function updateRessource() {
		$query = sprintf("UPDATE ressource SET dirty=false WHERE type='%s';",
			mysql_real_escape_string($this->type));
		mysql_query($query);
	}
	
	/*
	Change la valeur du champ updated dans la table user
	*/
	private function updateUser($id) {
		$query = sprintf("UPDATE user SET updated=false WHERE braldahim_id=%s;",
			mysql_real_escape_string($id));
		mysql_query($query);
	}
	
	private function createColors() {
		$colors = array(
			'black'		=> array(0, 0, 0),
			'red'		=> array(255, 0, 0),
			'blue'		=> array(0, 0, 255),
			// couleurs générales
			'name_bg'	=> array(255, 255, 255, 10),
			'line'		=> array(255, 255, 255),
			'background'	=> array(0, 59, 0),
			'legendbg'	=> array(59, 159, 59),
			'transparent'	=> array(10, 10, 10),
			'player'	=> array(70, 220, 240),
			
			// Couleur pour le type ROUTE
			'route'		=> array(128, 128, 128),
			'ruine'		=> array(80, 80, 80),
			'balise'	=> array(100, 130, 100),
			'echoppe'	=> array(128, 128, 128),
			'ville'		=> array(128, 128, 128),
			'palissade'	=> array(255, 185, 63),
			// Couleur pour le type ENVIRONNEMENT
			'plaine'	=> array(128, 255, 128),
			'eau'		=> array(20, 20, 255),
			'profonde'	=> array(20, 20, 255),
			'peuprofonde'	=> array(20, 20, 255),
			// Couleur pour le type BOSQUET
			'peupliers'	=> array(50, 200, 50),
			'hetres'	=> array(50, 200, 50),
			// Couleur pour les lieux importants
			'lieu_point'	=> array(255, 0, 0),
			'lieu_str'	=> array(0, 0, 0),
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
	Recupere les joueurs de la communauté
	*/
	private function getPlayers() {
		$query = "SELECT braldahim_id, prenom, nom, x, y FROM user WHERE x IS NOT NULL AND y IS NOT NULL ORDER BY braldahim_id ASC;";
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
	*/
	private function drawPlayer($p) {
		// coordonnées du centre
		$pos = $this->positionToPixel($p->position);
		// pour faire pointer au centre de la case
		//$pos->x += +$this->zoom/2;
		$pos->x += +$this->tile_size/2;
		$pos->y += +$this->tile_size/2;
		
		//$name = "{$p->prenom} {$p->nom} {$p->position}";
		$name = "{$p->prenom} {$p->nom}";
		$name_width = imagefontwidth($this->font_size) * strlen($name);
		$name_height = imagefontheight($this->font_size);
		
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
		
		// dessin du fond du nom
		imagefilledrectangle($this->img,
			$pos->x - $name_width / 2,
			$pos->y + $this->players_size / 2,
			$pos->x + $name_width / 2,
			$pos->y + $this->players_size / 2 + $name_height,
			$this->colors['name_bg']);
		
		// dessin du nom
		imagestring($this->img, $this->font_size,
			$pos->x - $name_width / 2,
			$pos->y + $this->players_size / 2,
			iconv("UTF8", "ISO-8859-1", $name), $this->colors['red']);
	}
	
	
	/*
	Dessine la grille
	*/
	private function drawGrid() {
		// On part du centre ($this->size/2), on enleve/ajoute 0.5 car le point est au milieu
		// de la case et on veut le tour de la case.
		// On ajout/retire $i pour boucler, et on met à l'echelle avec le zoom.
		// => $i est le numéro de la case en partant du centre
		// On boucle tant qu'on a pas atteind une bordure de l'image
		$p_logique = new Point($this->origine->x -0.5, $this->origine->y-0.5);
		$p_physique = $this->positionToPixel($p_logique);
		do {
			imageline($this->img, $p_physique->x, 0, $p_physique->x, $this->size, $this->colors['line']);
			imageline($this->img, 0, $p_physique->y, $this->size, $p_physique->y, $this->colors['line']);
			$p_logique = new Point($p_logique->x - 1*$this->zoom, $p_logique->y - 1*$this->zoom);
			$p_physique = $this->positionToPixel($p_logique);
		}
		while($p_physique->x > 0 && $p_physique->y > 0);
		
		$p_logique = new Point($this->origine->x -0.5, $this->origine->y-0.5);
		$p_physique = $this->positionToPixel($p_logique);
		do {
			imageline($this->img, $p_physique->x, 0, $p_physique->x, $this->size, $this->colors['line']);
			imageline($this->img, 0, $p_physique->y, $this->size, $p_physique->y, $this->colors['line']);
			$p_logique = new Point($p_logique->x + 1*$this->zoom, $p_logique->y + 1*$this->zoom);
			$p_physique = $this->positionToPixel($p_logique);
		}
		while($p_physique->x < $this->size && $p_physique->y < $this->size);
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
		$p_min = $this->pixelToPosition(new Point(0, $this->size)); // coin bas gauche (min X et min Y)
		$p_max = $this->pixelToPosition(new Point($this->size, 0)); // coin haut droite (max X et max Y)
		$tiles_list = $this->getTiles(
			$p_min->x-1, // -1 pour avoir les tiles à cheval sur le bord gauche
			$p_max->x,
			$p_min->y,
			$p_max->y+1 // +1 pour avoir les tiles à cheval sur le bord haut
			);
		foreach ($tiles_list as $name => $tile) {
			list($x, $y) = explode(';', $name);
			$p_physique = $this->positionToPixel(new Point($x, $y));
			$color = '';
			switch($tile['type']) {
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
	private function getTiles($x_min, $x_max, $y_min, $y_max) {
		$tiles = array();;
		// on va essayer toutes les tables dans un ordre précis
		// et on s'arrête dès qu'on a une info pertinente
		$table_list = array('palissade', 'route', 'bosquet', 'environnement');
		foreach ($table_list as $table) {
			$query = "SELECT * FROM {$table} WHERE x BETWEEN {$x_min} AND {$x_max} AND y BETWEEN {$y_min} AND {$y_max}";
			$res = mysql_query($query);
			if (mysql_num_rows($res) == 0) {
				mysql_free_result($res);
				continue;
			}
			else {
				while ($row = mysql_fetch_assoc($res)) {
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
		// On va chercher tous les lieux qui se trouvent
		// entre les bornes maximales de la carte
		$p_min = $this->pixelToPosition(new Point(0, $this->size)); // coin bas gauche (min X et min Y)
		$p_max = $this->pixelToPosition(new Point($this->size, 0)); // coin haut droite (max X et max Y)
		$query = "SELECT x, y, nom_lieu
			FROM lieu
			WHERE x BETWEEN {$p_min->x} AND {$p_max->x}
			AND y BETWEEN {$p_min->y} AND {$p_max->y}";
		if ($where != null && $where != '') {
			$query .= " AND $where ";
		}
		$query .= ";";
		
		$res = mysql_query($query);
		while ($row = mysql_fetch_assoc($res)) {
			// coordonnées du centre
			$pos = $this->positionToPixel(new Point($row['x'], $row['y']));
			// pour faire pointer au centre de la case
			$pos->x += +$this->tile_size/2;
			$pos->y += +$this->tile_size/2;
		
			$name_width = imagefontwidth($this->font_size) * strlen($row['nom_lieu']);
			$name_height = imagefontheight($this->font_size);
			
			// dessin du point
			imagefilledellipse($this->img,
				$pos->x, $pos->y,
				$this->players_size, $this->players_size,
				$this->colors['lieu_point']);
			
			// dessin du contour du point
			imageellipse($this->img,
				$pos->x, $pos->y,
				$this->players_size, $this->players_size,
				$this->colors['black']);
			
			// dessin du fond du nom
			/*imagefilledrectangle($this->img,
				$pos->x - $name_width / 2,
				$pos->y,
				$pos->x + $name_width / 2,
				$pos->y + $name_height,
				$this->colors['name_bg']);
			*/
			// dessin du nom
			imagestring($this->img, $this->font_size,
				$pos->x - $name_width / 2,
				$pos->y,
				iconv("UTF8", "ISO-8859-1", $row['nom_lieu']), $this->colors['lieu_str']);
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
		
		imagefilledrectangle($this->img, $x, $y, $x+150, $y+250, $this->colors['legendbg']);
		imagerectangle($this->img, $x, $y, $x+150, $y+250, $this->colors['black']);
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
		
		imagefilledrectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['ruine']);
		imagerectangle($this->img, $x, $y, $x+$h, $y+$h, $this->colors['black']);
		imagestring($this->img, $this->font_size, $x+$h+10, $y, 'Ruine', $this->colors['black']);
	}
	
	/*
	Affiche le temps utilisé pour générer l'image.
	*/
	private function addTimeUsed($time) {
		$str = sprintf("Généré en %.2fs", $time);
		$w = imagefontwidth($this->font_size) * strlen($str);
		$h = imagefontheight($this->font_size);
		imagefilledrectangle($this->img,
			10,
			$this->size - $h,
			10 + $w,
			$this->size,
			$this->colors['name_bg']);
		
		imagestring($this->img, $this->font_size,
			10, $this->size - $h,
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
					// dessin de l'environnement
					$this->drawTile();
					// temps de génération
					$this->addTimeUsed(microtime(true) - $time_start);
					// ajout des infos de debug
					if ($this->debug) {
						$this->info();
					}
					break;
				case "joueur":
					// mise en place du fond
					imagefilledrectangle($this->img, 0, 0, $this->size, $this->size, $this->colors['transparent']);
					// dessin des joueurs
					foreach ($this->players as $p) {
						$this->drawPlayer($p);
						$this->updateUser($p->id);
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
					break;
					
			}
			$this->updateRessource();
			imagecolortransparent($this->img, $this->colors['transparent']);
			imagepng($this->img, $this->filename);
			imagedestroy($this->img);
		}
		// dessin de la grille
		//$this->drawGrid();
		
		// on va chercher le fichier précédement créé
		if (file_exists($this->filename)) {
			header('Content-Disposition: attachment; filename='.basename($this->filename));
			header('Content-type: image/png');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . filesize($this->filename));
			ob_clean();
			flush();
			readfile($this->filename);
			exit;
		}
	}
}

?>
