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
}, false);

function showBraldunInfo(evt) {
	if (!evt) var evt = window.event;

	if(evt.preventDefault)
		evt.preventDefault();
	/*
	elt = document.getElementById('info');
	jid = evt.target.parentNode.id.substr(6);
	if (elt.style.display == 'none') {
		for (i in joueurs) {
			if (joueurs[i].id == jid) {
				alert(joueurs[i].nom);
			}
		}
		elt.style.display = 'block';
	}
	else {
		elt.style.display = 'none';
		return;
	}
	*/
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