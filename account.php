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

if (! isset($_SESSION)) session_start();

require("application.php");
require("fetch.php");

class Account extends Application {
	
	public function __construct() {
		parent::__construct();
	}

	/*
	Controleur à 2 sous.
	On appelle la méthode 'qui_va_bien' en fonction de l'action.
	*/
	public function actionParse() {
		if (isset($_REQUEST['action'])) {
			$this->action = $_REQUEST['action'];
		}
		switch($this->action) {
			case 'login_form':
				if ($this->logged) break;
				$this->getLoginForm();
				break;
			case 'login':
				if ($this->logged) break;
				$this->login();
				break;
			case 'logout':
				if (!$this->logged) break;
				$this->logout();
				break;
			case 'inscription':
				if ($this->logged) break;
				$this->getInscriptionForm();
				break;
			case 'inscription_submit':
				if ($this->logged) break;
				$this->inscriptionParse();
				break;
			case 'account':
				if (!$this->logged) break;
				$this->account();
				break;
			case 'account_mdp_submit':
				if (!$this->logged) break;
				$this->account_update_mdp();
				break;
			case 'account_mdpr_submit':
				if (!$this->logged) break;
				$this->account_update_mdpr();
				break;
			case 'fetch_me':
				if (!$this->logged) break;
				$this->fetch_me();
				break;
			case 'home':
			default:
				$this->home();
				break;
		}
	}

	/*
	Action 'login_form', on affiche le formulaire
	*/
	private function getLoginForm() {
		$this->html_content =<<<EOF
<p>Pour vous connecter veuillez indiquer votre num&eacute;ro de brald&ucirc;n et votre mot de passe.</p>
	
<form action="account.php?action=login" method="POST">
	<label for="bra_num">Num&eacute;ro du Brald&ucirc;n :</label>
	<input id="bra_num" name="bra_num" type="text">
	<br/>
	<label for="bra_pw">Mot de passe :</label>
	<input id="bra_pw" name="bra_pw" type="password">
	<br/>
	<input type="submit" value="Connexion">
</form>
EOF;
	}

	 /*
        Action 'login', on teste le formulaire :
          - soit erreur affichée à l'utilisateur
          - soit ajout en session
        */
	private function login() {
		$bra_num = ((isset($_REQUEST['bra_num'])) ? $_REQUEST['bra_num'] : null);
		$bra_pw = ((isset($_REQUEST['bra_pw'])) ? $_REQUEST['bra_pw'] : null);
		$bra_num = trim($bra_num);
		$bra_pw = trim($bra_pw);
	
		if (empty($bra_num) || empty($bra_pw)) {
			$this->html_message = "Veuillez remplir tous les champs.";
			$this->getLoginForm();
			return;
		}
		if (!is_numeric($bra_num)) {
			$this->html_message = "Le champs 'Num&eacute;ro' doit &ecirc;tre un nombre.";
			$this->getLoginForm();
			return;
		}
		if (strlen($bra_pw) == 0) {
			$this->html_message = "Le champs 'Mot de passe' doit ne doit pas &ecirc;tre vide.";
			$this->getLoginForm();
			return;
		}
		$query = sprintf("SELECT braldahim_id FROM ".DB_PREFIX."user WHERE braldahim_id=%s AND crypted_password='%s';",
			mysql_real_escape_string($bra_num),
			mysql_real_escape_string(md5($bra_pw)));
		$res = mysql_query($query, $this->db);
		if (mysql_num_rows($res) != 0) {
			// authentification ok
			$query = sprintf("UPDATE ".DB_PREFIX."user SET last_login=now() WHERE braldahim_id=$bra_num");
			mysql_query($query, $this->db);
			$_SESSION['bra_num'] = $bra_num;
			$this->html_message = "Authentification r&eacute;ussie.";
			$this->logged = true;
			$this->home();
		}
		else {
			$this->html_message = "&Eacute;chec de l'authentification.";
			$this->getLoginForm();
			$this->logged = false;
		}
		mysql_free_result($res);
	}

	/*
        Action 'logout', on efface la session
        */
        private function logout() {
		session_unset();
		
		$_SESSION = array();
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}
		session_destroy();
		$this->home();
		$this->logged = false;
	}

	/*
	Action 'inscription_submit', on teste le formulaire :
	  - soit erreur affichée à l'utilisateur
	  - soit ajout en db
	*/
	private function inscriptionParse() {
		$bra_num = ((isset($_REQUEST['bra_num'])) ? $_REQUEST['bra_num'] : null);
		$bra_pw = ((isset($_REQUEST['bra_pw'])) ? $_REQUEST['bra_pw'] : null);
		$bra_num = trim($bra_num);
		$bra_pw = trim($bra_pw);
	
		if (empty($bra_num) || empty($bra_pw)) {
			$this->html_message = "Veuillez remplir tous les champs.";
			$this->getInscriptionForm();
			return;
		}
		if (!is_numeric($bra_num)) {
			$this->html_message = "Le champs 'Num&eacute;ro' doit &ecirc;tre un nombre.";
			$this->getInscriptionForm();
			return;
		}
		/*if (strlen($bra_pw) != 32) {
			$this->html_message = "Le champs 'Mot de passe restreint' doit avoir 32 caract&egrave;res.";
			$this->getInscriptionForm();
			return;
		}*/
		// teste si le braldun est membre de la communauté
		// on charge le fichie csv en provenance de braldahim.com
		// id_braldun;prenom_braldun;nom_braldun;niveau_braldun;sexe_braldun;nb_ko_braldun;nb_braldun_ko_braldun;nb_plaque_braldun;nb_braldun_plaquage_braldun;nb_monstre_kill_braldun;id_fk_mere_braldun;id_fk_pere_braldun;id_fk_communaute_braldun;id_fk_rang_communaute_braldun;url_blason_braldun;url_avatar_braldun;est_pnj_braldun
		$content = file(FILE_bralduns_csv);
		$communaute_id = -1;
		$keys = explode(';', $content[0]);
		foreach ($content as $line) {
			if (preg_match("/^{$bra_num};/", $line) != 1) continue;
			$values = explode(';', $line);
			// recherche de l'indice de id_fk_communaute_braldun
			$max = count($keys);
			for ($i=0; $i<$max; $i++) {
				if ($keys[$i] == 'id_fk_communaute_braldun') {
					$communaute_id = $values[$i];
					break;
				}
			}
		}
		unset($content);
		if ($communaute_id != COMMUNAUTE) {
			$this->html_message = "Le brald&ucirc;n {$bra_num} ne fait pas partie de la communaut&eacute; ".COMMUNAUTE_NOM.".";
			$this->getInscriptionForm();
			return;
		}
	
		$query = sprintf("SELECT braldahim_id FROM ".DB_PREFIX."user WHERE braldahim_id=%s;", mysql_real_escape_string($bra_num));
		$res = mysql_query($query, $this->db);
		if (mysql_num_rows($res) != 0) {
			$this->html_message = "Un brald&ucirc;n existe d&eacute;j&agrave; avec cet identifiant.";
			$this->getInscriptionForm();
			mysql_free_result($res);
			return;
		}
		mysql_free_result($res);
	
		$query = sprintf("INSERT INTO ".DB_PREFIX."user(braldahim_id, crypted_password) VALUES(%s, '%s');",
			mysql_real_escape_string($bra_num),
			mysql_real_escape_string(md5($bra_pw)));
		mysql_query($query, $this->db);
		$this->html_message = "Inscription r&eacute;alis&eacute;e avec succ&egrave;s. Veuillez mettre &agrave; jour votre mot de passe <b><u>restreint</u></b>.";
		$_SESSION['bra_num'] = $bra_num;
		$this->logged = true;
		$this->account();
	}
	
	/*
	Action 'inscription', on affiche le formulaire
	*/
	private function getInscriptionForm() {
		$this->html_content =<<<EOF
<p>Pour vous inscrire, vous avez besoin de votre num&eacute;ro de brald&ucirc;n et de votre mot de passe.
<!-- Vous pouvez obtenir votre mot de passe restreint à l'adresse suivante :
<a target="_blank" href="http://sp.braldahim.com/md5/">http://sp.braldahim.com/md5/</a>. --></p>

<form action="account.php" method="POST">
	<input type="hidden" name="action" value="inscription_submit" />
	<label for="bra_num">Num&eacute;ro du Brald&ucirc;n&nbsp;:</label>
	<input id="bra_num" name="bra_num" type="text">
	<br/>
	<label for="bra_pw">Mot de passe&nbsp;:</label>
	<input id="bra_pw" name="bra_pw" type="password">
	<br/>
	<input type="submit" value="Inscription">
</form>
EOF;
	}

	/*
	Affiche les options de gestion de compte
	*/
	private function account() {
		$p = null;
		$query = "SELECT idBraldun, last_update, time_to_sec(current_timestamp - last_update) as diff
			FROM ".DB_PREFIX."profil WHERE idBraldun = {$_SESSION['bra_num']};";
		$res = mysql_query($query, $this->db);
		if (mysql_num_rows($res) == 1) {
			$p = mysql_fetch_assoc($res);
		}
		mysql_free_result($res);
		if ($p == null) {
			$this->html_message = "Impossible de trouver votre brald&ucirc;n.";
		}
		
		$content =<<<EOF
<div class="mdp">
	<p>Changer de mot de passe de connexion (en clair)&nbsp;:</p>
	<form action="account.php" method="POST">
	<input type="hidden" name="action" value="account_mdp_submit" />
	<input type="password" name="mdp" value="" />
	<input type="submit" value="Mise à jour" />
	</form>
</div>
<div class="mdp">
	<p>Changer de mot de passe restreint (crypt&eacute;)&nbsp;:</p>
	<form action="account.php" method="POST">
	<input type="hidden" name="action" value="account_mdpr_submit" />
	<input type="password" name="mdpr" value="" />
	<input type="submit" value="Mise à jour" />
	</form>
</div>
EOF;

		$content .=<<<EOF
<div class="mdp">
	<p>Pour mettre à jour les informations de votre brald&ucirc;n (position, identification de monstres, caract&eacute;ristique, ...), vous pouvez cliquer sur le bouton suivant.</p>
	<p>Votre dernière mise à jour a eu lieu le : {$p['last_update']}</p>
EOF;
		// si on a le droit de mettre a jour
		if ($p == null || $p['diff'] == NULL || $p['diff'] > UPDATE_DELAY) {
			$content .=<<<EOF
	<form action="account.php" method="POST">
	<input type="hidden" name="action" value="fetch_me" />
	<input type="submit" value="Mise à jour" />
	</form>
EOF;
		}
		else {
			$content .= "<p>Votre derni&egrave;re mise &agrave; jour est r&eacute;cente, pas besoin de mettre &agrave; jour (délai ".UPDATE_DELAY."s).</p>";
		}
		$content .= "</div>";
		$this->html_content = $content;
	}

	private function account_update_mdp() {
		$mdp = ((isset($_REQUEST['mdp'])) ? $_REQUEST['mdp'] : null);
		$mdp = trim($mdp);

		if (is_null($mdp) || empty($mdp)) {
			$this->html_message = "Le mot de passe est vide.";
			$this->account();
			return;
		}
		$query = sprintf("UPDATE ".DB_PREFIX."user SET crypted_password='%s' WHERE braldahim_id=%s;", mysql_real_escape_string(md5($mdp)), mysql_real_escape_string($_SESSION['bra_num']));
		mysql_query($query, $this->db);
		$this->html_message = "Mot de passe mis &agrave; jour.";
		$this->account();
	}

	private function account_update_mdpr() {
		$mdp = ((isset($_REQUEST['mdpr'])) ? $_REQUEST['mdpr'] : null);
		$mdp = trim($mdp);

		if (is_null($mdp) || empty($mdp)) {
			$this->html_message = "Le mot de passe resteint est vide.";
			$this->account();
			return;
		}
		$query = sprintf("UPDATE ".DB_PREFIX."user SET restricted_password='%s' WHERE braldahim_id=%s;", mysql_real_escape_string($mdp), mysql_real_escape_string($_SESSION['bra_num']));
		mysql_query($query, $this->db);
		$this->html_message = "Mot de passe restreint mis &agrave; jour.";
		$this->account();
	}

	private function fetch_me() {
		$fetch = new Fetch();
		$fetch->fetchOnePlayer_dynamique($_SESSION['bra_num']);
		$fetch->fetchOnePlayer_statique($_SESSION['bra_num']);
		$this->html_message = "Les informations de votre brald&ucirc;n ont &eacute;t&eacute; mise &agrave; jour.";
		$this->account();
	}
}
$app = new Account();
include("template.php");
?>

