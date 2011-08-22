panneau_in_out = 'out';

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
		}
		// masque les popups dynamiquement
		if (rxcloseinfo.test(elt[i].id)) {
			elt[i].addEventListener('click', closeBraldunInfo, false);
		}
	}
	// affiche masque le panneau d'information
	document.getElementById('panneau_open_close').addEventListener('click', panneauOpenClose, false);
}, false);

window.addEventListener('load', function() {
	elt=document.getElementsByTagName('text');
	rxcentre = /^centre_joueur\d+_-?\d+_-?\d+/;
	for (i in elt) {
		if (rxcentre.test(elt[i].id)) {
			//elt[i].addEventListener('click', centreBraldun(elt[i], RegExp.$1, RegExp.$2), false);
			elt[i].addEventListener('click', centreBraldun, false);
		}
	}
}, false);

function showBraldunInfo(evt) {
	if (!evt) var evt = window.event;

	if(evt.preventDefault)
		evt.preventDefault();
	
	evt_id = evt.target.parentNode.id;
	info_elt = document.getElementById('info_'+evt_id);
	if (info_elt != null) {
		if (info_elt.style.display == 'none') {
			info_elt.style.display = 'block';
		}
		else {
			info_elt.style.display = 'none';
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
}

function centreBraldun(evt) {
	if (!evt) var evt = window.event;

	if(evt.preventDefault) {
		evt.preventDefault();
	}
	
	rxcentre = /^centre_joueur\d+_(-?\d+)_(-?\d+)/;
	rxcentre.test(evt.target.id);
	x = parseInt(RegExp.$1);
	y = parseInt(RegExp.$2);
	e = x * -1 + 425;
	f = y * -1 + 325;
	svgRoot.setAttribute("transform", "translate(" + e + " " + f + ")");
}

function panneauOpenClose(evt) {
	if (!evt) var evt = window.event;

	if(evt.preventDefault) {
		evt.preventDefault();
	}
	if (panneau_in_out == 'in') {
		document.getElementById('panneau_out').beginElement();
		panneau_in_out = 'out';
	}
	else {
		document.getElementById('panneau_in').beginElement();
		panneau_in_out = 'in';
	}
}
