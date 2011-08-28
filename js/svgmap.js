// contient l'etat des panneaux d'information
var panneau_in_out = new Object();
panneau_in_out.joueur = false;
panneau_in_out.ville = false;
panneau_in_out.buisson = false;

// contient l'état des info des joueurs
var info_joueur = new Object();

window.addEventListener('load', function() {
	elt=document.getElementsByTagName('g');
	rx = /^joueur\d+/;
	rxinfo = /^info_joueur\d+/;
	rxcloseinfo = /^close_info_joueur\d+/;
	for (i in elt) {
		// ajoute le comportement aux bralduns
		if (rx.test(elt[i].id)) {
			elt[i].addEventListener('click', showBraldunInfo, false);
		}
		// masque les popups par defaut
		if (rxinfo.test(elt[i].id)) {
			elt[i].style.display = 'none';
			info_joueur[elt[i].id] = false;
		}
		// masque les popups dynamiquement
		if (rxcloseinfo.test(elt[i].id)) {
			elt[i].addEventListener('click', closeBraldunInfo, false);
		}
	}
	// affiche masque les panneaux d'information
	document.getElementById('panneau_open_close_joueur').addEventListener('click', panneauOpenClose, false);
	document.getElementById('panneau_open_close_ville').addEventListener('click', panneauOpenClose, false);
	document.getElementById('panneau_open_close_buisson').addEventListener('click', panneauOpenClose, false);
	
	// centre sur les joueurs/villes
	elt=document.getElementsByTagName('text');
	rxcentreJ = /^centre_joueur\d+_-?\d+_-?\d+/;
	rxcentreV = /^centre_ville_-?\d+_-?\d+/;
	for (i in elt) {
		if (rxcentreJ.test(elt[i].id)) {
			elt[i].addEventListener('click', centreElement, false);
		}
		else if (rxcentreV.test(elt[i].id)) {
			elt[i].addEventListener('click', centreElement, false);
		}
	}
}, false);

function showBraldunInfo(evt) {
	if (!evt) var evt = window.event;

	if(evt.preventDefault)
		evt.preventDefault();
	
	s = 'info_'+evt.target.parentNode.id;
	
	// on parcours la liste des info_joueur
	for (e in info_joueur) {
		// si c'est fermé on l'ouvre
		if (s == e && !info_joueur[e]) {
			document.getElementById(e).style.display = 'block';
			info_joueur[e] = true;
		}
		// on ferme les autres panneaux ouverts
		else if (info_joueur[e]) {
			document.getElementById(e).style.display = 'none';
			info_joueur[e] = false;
		}
	}
}

function closeBraldunInfo(evt) {
	if (!evt) var evt = window.event;

	if(evt.preventDefault)
		evt.preventDefault();
	// on cherche l'element nommé "info_joueurXXX"
	rx = /^close_info_joueur(\d+)/;
	rx.test(evt.target.parentNode.id);
	document.getElementById('info_joueur'+RegExp.$1).style.display = 'none';
	info_joueur['info_joueur'+RegExp.$1] = false;
}

/*
 * Centre la vue sur un element (joueur, ville, ...)
 * L'action est déclenchée par les listes des panneaux d'informations
 */
function centreElement(evt) {
	if (!evt) var evt = window.event;

	if(evt.preventDefault) {
		evt.preventDefault();
	}
	
	rxcentre = /^centre_.+_(-?\d+)_(-?\d+)/;
	rxcentre.test(evt.target.id);
	x = parseInt(RegExp.$1);
	y = parseInt(RegExp.$2);
	e = x * -1 + 425;
	f = y * -1 + 325;
	svgRoot.setAttribute("transform", "translate(" + e + " " + f + ")");	
}

/*
 * Ouvre le panneau séléctionné et ferme les autres.
 * Ferme le panneau séléctionné s'il est ouvert
 */
function panneauOpenClose(evt) {
	if (!evt) var evt = window.event;

	if(evt.preventDefault) {
		evt.preventDefault();
	}
	// les identifiants des declencheur contiennent le nom
	rxid = /^panneau_open_close_(.+)/;
	rxid.test(evt.target.id);
	s = RegExp.$1;
	
	// on parcours la liste des panneaux
	for (e in panneau_in_out) {
		// si le panneau est fermé on l'ouvre
		if (s == e && !panneau_in_out[e]) {
			document.getElementById('panneau_'+e+'_in').beginElement();
			panneau_in_out[e] = true;
		}
		// on ferme les autres panneaux ouverts
		else if (panneau_in_out[e]) {
			document.getElementById('panneau_'+e+'_out').beginElement();
			panneau_in_out[e] = false;
		}
	}
}

