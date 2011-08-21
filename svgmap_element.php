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
	<text x="{$pv_x}" y="{$pv_y}" style="display:inline; color:#000">PV : {$this->pvrestant} / {$this->pvmax}</text>
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
?>