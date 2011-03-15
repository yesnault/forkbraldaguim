<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Cache-Control" content="no-cache" />

<style type="text/css">
body {
	background-color: #003B00;
	color: #FFFFFF;
}
a:link, a:visited {color: #BBBBFF;}
a:hover {color: #F0AE21;}
#menu ul {
	list-style-type: none;
	padding: 0;
	text-align: center;
	margin: .5em 0 .5em;
}
#menu ul li {display: inline;}
#menu ul li a {
	font-weight: bold;
	text-decoration: none;
	padding: .2em 1em;
	background-color: #5D8231;
}
#menu ul li a:hover {
	color: #F0AE21;
}
#main {
	margin: 1em 2em;
}
#message {
	background-color: #EF7E68;
	text-align: center;
}

#position {
	margin: 1em 0 0 0;
}
#dist {
	float: left;
	padding: 0 2em;
}
.tab_position {
	float: left;
	border-collapse: collapse;
	border-color: #5D8231;
	margin: 0 1em 1em 0;
}
.tab_position td, .tab_position th {
	padding: .2em .2em;
}
.tab_position img {
	border: none;
}

#map_wrapper {
	clear: both;
	height: 520px;
	position: relative;
}
.map_item {
	position: absolute;
	top: 0;
	left: 0;
	border: 1px solid black;
	margin: 10px 10px;
}
#map_legende, #map_lieustandard {
	visibility: hidden;
}
#map_info {
	position: absolute;
	top: 0;
	left: 520px;
	margin: 10px 10px;
}

#monstre_liste {
	float: left;
	padding: 0 1em 0 0;
	margin: 0 1em 0 0;
	border-right: 1px solid #5D8231;
}
#monstre_detail {
	float: left;
	padding: 0 2em 0 0;
	max-width: 600px;
}
.monstre_tab_detail {
	float: left;
	border-collapse: collapse;
	border-color: #5D8231;
	margin: 0 1em 1em 0;
}
.monstre_tab_detail td, .monstre_tab_detail th {
	padding: .2em .2em;
	text-align: center;
}
#monstre_saisie {
	float: left;
}
#monstre_saisie textarea {
	width: 250px;
	height: 300px;
	overflow: auto;
}

.mdp {
	float: left;
	width: 300px;
	border: 1px solid black;
	padding: .5em 1em;
	margin: 0 1em 0 0;
}
.profil td {
	text-align: left;
	font-size: 80%;
	vertical-align: top;
}
.profil td.p_nom {
	font-size: 90%;
	font-weight: bold;
	background-color: #DF920D;
}
.profil td.tab {
	padding: 0;
	margin: 0;
}
.profil table {
	border: 1px solid #5D8231;
	border-collapse: collapse;
	width: 100%;
	padding: -0.2em;
}
.profil table td, .profil table th {
	font-size: 100%;
	border: 1px solid #5D8231;
}
</style>

<title><?php  echo $app->getHtmlTitle(); ?></title>
<script type="text/javascript"><?php echo $app->getHtmlScript(); ?></script>
</head>
<body>

<div id="menu">
	<ul>
		<li><a href="index.php?action=home">Accueil</a></li>
		<?php if (! $app->logged) {?>
		<li><a href="account.php?action=inscription">Inscription</a></li>
		<?php } ?>
		<li><?php echo $app->getLoginLink(); ?></li>
		<?php if ($app->logged) {?>
		<li><a href="account.php?action=account">Gestion du compte</a></li>
		<li><a href="position.php?action=position">Position</a></li>
		<li><a href="bestiaire.php?action=bestiaire">Bestiaire</a></li>
		<li><a href="profil.php?action=allprofils">Profils</a></li>
		<?php } ?>
</ul>
</div>

<div id="main">
	<div id="message">
		<?php  echo $app->getHtmlMessage(); ?>
	</div>
	<div id="content">
		<?php  echo $app->getHtmlContent(); ?>
	</div>
</div>
</body>
</html>

