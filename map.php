<?php
error_reporting(E_ALL);

session_start();
if (! isset($_SESSION['bra_num'])) exit();
/*
Genere la carte des positions
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
}

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

class Carte {
	private $db;
	private $players;
	private $player_size;
	private $size;
	private $origine;
	private $zoom;
	private $img;
	private $colors;
	private $font_size;
	
	private $debug = true;
	
	/*
	Construit une carte de la taille indiqué avec size (en pixel)
	*/
	public function __construct($size) {
		$this->size = $size;
		$this->players_size = 10; // un rond de 5 pixel de diametre
		$this->zoom = 1;
		$this->font_size = 2;
		$this->players = array();
		
		$this->img = imagecreatetruecolor($this->size, $this->size);
		$this->createColors();
		
		$this->db = mysql_connect("localhost", "braldahim", "braldahim");
		mysql_select_db("braldahim");
		
		$this->getPlayers();
		$this->setBarycentre();
	}
	
	private function createColors() {
		$this->colors = array();
		$this->colors['black'] = imagecolorallocate($this->img, 0, 0, 0);
		$this->colors['red'] = imagecolorallocate($this->img, 255, 0, 0);
		$this->colors['blue'] = imagecolorallocate($this->img, 0, 0, 255);
		$this->colors['name_bg'] = imagecolorallocatealpha($this->img, 255, 255, 255, 64);
		$this->colors['line'] = imagecolorallocate($this->img, 255, 255, 255);
		$this->colors['background'] = imagecolorallocate($this->img, 0, 59, 0);
	}
	
	/*
	On passe une position en coordonnées logique (par rapport au jeu) et
	la fonction retourne les coordonnées du centre de la case en coordonées
	physique de l'image
	*/
	private function positionToPixel(Point $p) {
		$x = ($this->size/2) + ($p->x - $this->origine->x) * $this->zoom;
		$y = ($this->size/2) - ($p->y - $this->origine->y) * $this->zoom;
		return new Point($x, $y);
	}
	
	/*
	On passe une position en coordonnées physique (par rapport à l'image) et
	la fonction retourne les coordonnées logique de la case
	*/
	private function pixelToPosition(Point $p) {
		$x = $this->origine->x + (($p->x - ($this->size/2)) / $this->zoom);
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
		
		// On enlèveégalement 2 fois le nombre de caractère du nom le plus long
		// (en pixel) pour etre certain de pouvoir afficher tous les noms qui toucheraient
		// le bord de l'image.
		
		$text = imagefontwidth($this->font_size) * $nom_max;
		$this->zoom = floor(($this->size - 2*$text) / ($position_max * 2));
		// On prend une valeur entière de zoom pour tombre juste et pas se prendre la tete
		// sur le tiling du fond de carte.
	}
	
	public function info() {
		/*
		foreach ($this->players as $p) {
			echo "$p<br>";
		}
		*/
		if ($this->debug) {
			imagestring($this->img, $this->font_size,
				10, 10,
				"centre: {$this->origine} - zoom: {$this->zoom}",
				$this->colors['red']);
		}
	}
	
	private function drawPlayer($p) {
		// coordonnées du centre
		$pos = $this->positionToPixel($p->position);
		
		$name = "{$p->prenom} {$p->nom} {$p->position}";
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
			$pos->y,
			$pos->x + $name_width / 2,
			$pos->y + $name_height,
			$this->colors['name_bg']);
		
		// dessin du nom
		imagestring($this->img, $this->font_size,

			$pos->x - $name_width / 2,
			$pos->y,
			$name, $this->colors['red']);
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
		$x = 0;
		for ($i=0; ($this->size/2) - $x > 0; $i++) {
			$x =  (-0.5+$i) * $this->zoom;
			// verticale
			imageline($this->img, ($this->size/2) - $x, 0, ($this->size/2) - $x, $this->size, $this->colors['line']);
			imageline($this->img, ($this->size/2) + $x, 0, ($this->size/2) + $x, $this->size, $this->colors['line']);
			// horizontale
			imageline($this->img, 0, ($this->size/2) - $x, $this->size, ($this->size/2) - $x, $this->colors['line']);
			imageline($this->img, 0, ($this->size/2) + $x, $this->size, ($this->size/2) + $x, $this->colors['line']);
		}
		$this->drawTile($i);
	}
	
	/*
	Affiche les image pour chaque case.
	On lui passe en paramètre le nombre max de cases à afficher en partant du centre.
	*/
	private function drawTile($max) {
		// on boucle sur deltaX
		for ($dx=-$max; $dx<$max; $dx++) {
			// -0.5 permet d'être dans le coin haut gauche de l'image
			$x =  (-0.5+$dx) * $this->zoom;
			// on boucle sur deltaY
			for ($dy=-$max; $dy<$max; $dy++) {
				$y =  (-0.5+$dy) * $this->zoom;
				$tile = $this->getTile(floor($dx), floor($dy));
				
				/*
				imagestring($this->img, $this->font_size,
					($this->size/2) - $x,
					($this->size/2) - $y,
					$tile['type'][0],
					$this->colors['red']);
					*/
				imagestring($this->img, 1,
					($this->size/2) - $x,
					($this->size/2) - $y,
					"$dx,$dy",
					$this->colors['red']);
			}
		}
	}
	
	private function getTile($x, $y) {
		$tile = null;
		$query = "SELECT type, id FROM carte WHERE x='{$x}' AND y='{$y}';";
		$res = mysql_query($query);
		if (mysql_num_rows($res) == 0) {
			$tile = array();
			$tile['type'] = 'NA';
			$tile['id'] = 'NA';
		}
		else {
			$tile = mysql_fetch_assoc($res);
		}
		mysql_free_result($res);
		return $tile;
	}
	
	public function generateImage() {
		// mise en place du fond
		imagefilledrectangle($this->img, 0, 0, $this->size, $this->size, $this->colors['background']);
		
		// dessin de la grille
		//$this->drawGrid();
		
		// dessin des joueurs
		foreach ($this->players as $p) {
			$this->drawPlayer($p);
		}
		
		/*imagefilledellipse($this->img,
			($this->size/2),
			($this->size/2),
			$this->players_size, $this->players_size,
			$this->colors['blue']);
		*/
		// ajout des infos de debug
		//$this->info();
		
		header ("Content-type: image/png");
		imagepng($this->img);
		imagedestroy($this->img);
	}
}

$carte = new Carte(500);
$carte->generateImage();

?>