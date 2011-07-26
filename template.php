<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Cache-Control" content="no-cache" />

<link href="default.css" type="text/css" rel="stylesheet">

<title><?php  echo $app->getHtmlTitle(); ?></title>
<?php  echo $app->getHtmlHead(); ?>
<script type="text/javascript"><?php echo $app->getHtmlScript(); ?></script>
</head>
<body>

<div id="menu">
	<ul>
		<li><a href="index.php?action=home">Accueil</a></li>
		<?php if (defined('FORUM_LINK')) {?>
			<li><a href="<?php echo FORUM_LINK ?>" target="_blank">Forum</a></li>
		<?php } ?>
		<?php if (! $app->logged) {?>
		<li><a href="account.php?action=inscription">Inscription</a></li>
		<?php } ?>
		<li><?php echo $app->getLoginLink(); ?></li>
		<?php if ($app->logged) {?>
		<li><a href="account.php?action=account">Gestion du compte</a></li>
		<li><a href="position.php">Position</a></li>
		<li><a href="bestiaire.php?action=bestiaire">Bestiaire</a></li>
		<li><a href="profil.php?action=allprofils">Profils</a></li>
		<li><a href="equipement.php?action=equipement">Equipement</a></li>
		<li><a href="simulateur.php?action=simulation">Simulateur</a></li>
		<li><a href="svgmap2.php?action=position">SVGMAP (beta)</a></li>
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
	<div class="clear_that"></div>
</div>
<div id="footer">
v0.6 - Code source disponible sur : <a href="http://www.guim.info/dokuwiki/dev/braldaguim">Braldaguim</a>
</div>
</body>
</html>

