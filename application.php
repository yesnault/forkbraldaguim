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

if (! isset($_SESSION)) session_start();

require("conf.php");

class Application {
	protected $html_title;
	protected $html_content;
	protected $html_message;
	protected $html_script;
	protected $db;
	protected $action;
	
	public $logged; // est on connecté ?
	
	public function __construct() {
		$this->db = mysql_connect(DB_HOST, DB_USER, DB_PASS);
		mysql_select_db(DB_NAME);
		mysql_set_charset('utf8', $this->db);
		mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
		$this->html_title = 'Les premiers Brald&ucirc;ns';
		$this->html_script = '';
		$this->logged = isset($_SESSION['bra_num']);
		$this->actionParse();
	}

	public function __destruct() {
		//mysql_close($this->db);
	}

	/*
	Controleur à 2 sous.
	On appelle la méthode 'qui_va_bien' en fonction de l'action.
	*/
	public function actionParse() {
		$this->action = 'home';
		if (isset($_REQUEST['action'])) {
			$this->action = $_REQUEST['action'];
		}
		
		$this->html_content = '';
		switch($this->action) {
			case 'home':
			default:
				$this->home();
				break;
		}
	}

	public function getHtmlTitle() {
		return $this->html_title;
	}

	public function getHtmlMessage() {
		return $this->html_message;
	}

	public function getHtmlContent() {
		return $this->html_content;
	}
	
	public function getHtmlScript() {
		return $this->html_script;
	}
	
	/*
	Affiche le lien de connexion ou de deconnexion
	*/
	public function getLoginLink() {
		if (! isset($_SESSION['bra_num'])) {
			return '<a href="account.php?action=login_form">Connexion</a>';
		}
		return '<a href="account.php?action=logout">Deconnexion</a>';
	}
	
	/*
	Action 'home', on affiche un message d'accueil
	*/
	public function home() {
		$this->html_content = <<<EOF
<p>Bienvenue sur la page de positionnement de la communaut&eacute; <b>Les premiers brald&ucirc;ns</b>.</p>
<p>Pour vous inscrire, utilisez le lien <a href="index.php?action=inscription">Inscription</a>.</p>
<p>Vous pourrez ensuite conna&icirc;tre la position des brald&ucirc;ns de la communaut&eacute;.</p>
<p>La mise à jour des positions &agrave; lieu <u>toutes les 6 heures</u>.</p>
EOF;
	}
}

?>
