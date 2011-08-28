<?php

require("fetch.php");

// si on est en mode CLI, alors on met à jour tous les joueurs
if ( isset($_SERVER['argc']) && $_SERVER['argc'] >= 1 ) {
	if ($_SERVER['argv'][1] == "statique" || $_SERVER['argv'][1] == "dynamique") {
		$fetch = new Fetch();
		$fetch->fetchAllPlayers($_SERVER['argv'][1]);
	}
	else {
		echo "Argument inconnu : ".$argv[1]."\n";
	}
}
// si on est en mode WEB, on ne met à jour que le joueur demandé
else {
	session_start();
	if (! isset($_SESSION['bra_num'])) {
		echo "not connected";
		exit;
	}
	/*$fetch = new Fetch();
	$fetch->fetchOnePlayer($_SESSION['bra_num']);*/
	//sleep(1);
	echo "ok";
// mettre un timer pour éviter que l'utilisateur mette à jour trop souvent
// par exemple bloquer pour 1h la maj en se basant sur last_updated
}
?>
