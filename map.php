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

$carte = null;
if (isset($_REQUEST['type'])) {
	$carte = new Carte(500, $_REQUEST['type']);
}
else {
	$carte = new Carte(500);
}

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
	private $img;
	private $colors;
	private $font_size;
	
	private $debug = true;
	
	/*
	Construit une carte de la taille indiqué avec size (en pixel)
	et representant le sujet indiqué par type (fond, joueur, lieu)
	*/
	public function __construct($size, $type="fond") {
		$this->size = $size;
		$this->type = $type;
		$this->players_size = 10; // un rond de 5 pixel de diametre
		$this->zoom = 1;
		$this->font_size = 2;
		$this->players = array();
		
		$this->img = imagecreatetruecolor($this->size, $this->size);
		$this->createColors();
		
		// pour la légende, on n'a pas besoin d'aller plus loin
		if ($type=='legende') return;
		
		$this->db = mysql_connect("localhost", "braldahim", "braldahim");
		mysql_select_db("braldahim");
		
		$this->getPlayers();
		$this->setBarycentre();
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
		$x = ($this->size/2) + ($p->x - $this->origine->x) * $this->zoom;
		$y = ($this->size/2) - ($p->y - $this->origine->y) * $this->zoom;
		return new Point($x, $y);
	}
	
	/*
	On passe une position en coordonnées physique (par rapport à l'image) et
	la fonction retourne les coordonnées logique de la case
	*/
	private function pixelToPosition(Point $p) {
		// FIXME le "+1" permet de réaligner les joueur et les tiles...
		$x = $this->origine->x + (($p->x - ($this->size/2)) / $this->zoom)+1;
		$y = $this->origine->y + (($p->y - ($this->size/2)) / ($this->zoom * -1));
		return new Point(floor($x), floor($y));
	}
	
	
	/*
	Recupere les joueurs de la communauté
	*/
	private function getPlayers() {
		//$query = "SELECT braldahim_id, prenom, nom, x, y FROM user WHERE x IS NOT NULL AND y IS NOT NULL ORDER BY braldahim_id ASC LIMIT 1;";
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
	private function setBarycentre() {
		//on additionne les positions pour le calcul du barycentre
		$tot_x = $tot_y = 0;
		foreach ($this->players as $p) {
			$tot_x += $p->position->x;
			$tot_y += $p->position->y;
		}
		
		// on place le barycentre des joueurs comme origine
		$this->origine = new Point($tot_x/count($this->players), $tot_y/count($this->players));
		
		// On cherche la position la plus éloignée du barycentre (sur x ou y) en valeur absolue
		// On cherche également le nom le plus long à afficher (pour prévoir de la marge)
		$position_max = 1;
		$nom_max = 0;
		foreach ($this->players as $p) {
			$position_max = max($position_max, $p->position->distanceMax($this->origine));
			$nom_max = max($nom_max, strlen("{$p->prenom} {$p->nom}"));
		}
		
		// Le zoom est égale au rapport entre la largeur en pixel de l'image
		// et le nombre de case séparant le joueur le plus éloigné du barycentre.
		// Plus exactement, on ne prend que la moitié de la distance la plus longue
		// car le barycentre est au centre le l'image et l'image est un carré.
		
		// On enlève également 1.5 (ou 2) fois le nombre de caractère du nom le plus long
		// (en pixel) pour etre certain de pouvoir afficher tous les noms qui toucheraient
		// le bord de l'image.
		
		$text = imagefontwidth($this->font_size) * $nom_max;
		$this->zoom = floor(($this->size - 1.5*$text) / ($position_max * 2));
		// On prend une valeur entière de zoom pour tombre juste et pas se prendre la tete
		// sur le tiling du fond de carte.
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
	
	private function drawPlayer($p) {
		// coordonnées du centre
		$pos = $this->positionToPixel($p->position);
		
		//$name = "{$p->prenom} {$p->nom} {$p->position}";
		$name = "{$p->prenom} {$p->nom}";
		$name_width = imagefontwidth($this->font_size) * strlen($name);
		$name_height = imagefontheight($this->font_size);
		
		// dessin du point
		imagefilledellipse($this->img,
			$pos->x, $pos->y,
			$this->players_size, $this->players_size,
			$this->colors['blue']);
		
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
		$step = $this->zoom;
		for ($x = 0; $x<$this->size; $x+=$step) {
			for ($y = 0; $y<$this->size; $y+=$step) {
				$p_physique = new Point($x, $y);
				$p_logique = $this->pixelToPosition($p_physique);
				$tile = $this->getTile($p_logique->x, $p_logique->y);
				if ($tile == null) {
					continue;
				}
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
					$p_physique->x + $step,
					$p_physique->y + $step,
					$color);
			}
		}
	}
	
	/*
	Retourne les détails correspondant à une case
	*/
	private function getTile($x, $y) {
		$tile = null;
		// on va essayer toutes les tables dans un ordre précis
		// et on s'arrête dès qu'on a une info pertinente
		$table_list = array('palissade', 'route', 'bosquet', 'environnement');
		foreach ($table_list as $table) {
			$query = "SELECT * FROM {$table} WHERE x='{$x}' AND y='{$y}';";
			$res = mysql_query($query);
			if (mysql_num_rows($res) == 0) {
				mysql_free_result($res);
				continue;
			}
			else {
				$row = mysql_fetch_assoc($res);
				$tile = $row;
				$tile['type'] = $table;
				mysql_free_result($res);
				break;
			}
		}
		return $tile;
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
			
			$name_width = imagefontwidth($this->font_size) * strlen($row['nom_lieu']);
			$name_height = imagefontheight($this->font_size);
			
			// dessin du point
			imagefilledellipse($this->img,
				$pos->x, $pos->y,
				$this->players_size, $this->players_size,
				$this->colors['lieu_point']);
			
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
			$this->colors['background']);
		
		imagestring($this->img, $this->font_size,
			10, $this->size - $h,
			iconv("UTF8", "ISO-8859-1", $str), $this->colors['red']);
	}

	/*
	Génère l'image
	*/
	public function generateImage() {
		$time_start = microtime(true);
		
		switch($this->type) {
			case "fond":
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
		imagecolortransparent($this->img, $this->colors['transparent']);
		
		// dessin de la grille
		//$this->drawGrid();
		
		/*
		imagefilledellipse($this->img,
			($this->size/2),
			($this->size/2),
			$this->players_size, $this->players_size,
			$this->colors['blue']);
		*/
		
		
		header ("Content-type: image/png");
		imagepng($this->img);
		imagedestroy($this->img);
		
	}
}

?>