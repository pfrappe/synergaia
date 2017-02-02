<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.4 (see AUTHORS file)
 * 
 * SG_DominoDB : Classe de gestion des bases de données Domino. 
 * Il ne doit y en avoir qu'un seul d'ouvert si on ne veut risquer des login incessants.
 *
 */
class SG_DominoDB extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@DominoDB';
	public $typeSG = self::TYPESG;
	// login par défaut
	public $cookie = '';
	public $user = '';
	public $psw = '';
	public $serveur = '';
	
	// 1.3.4 ajout : login en cours (ip => [user =>, cookie =>, psw =>])
	public $connexion = array();
	
	// 1.3.4 ajout array(clé minuscule => valeur)
	public $lastheaders = '';
	
	// 1.3.4 anti-boucles
	private $fois = 0;
	
	/** 1.1
	* Construction d'un objet @DominoDB
	*/
	public function __construct($pServeur = '', $pUser = '', $pPassword = '') {
		$serveur = new SG_Texte($pServeur);
		$this -> serveur = $serveur -> texte;		
		if ($this -> serveur === '') {
			$this -> serveur = SG_Config::getConfig('DominoDB_host', '');
		}
		$port = SG_Config::getConfig('DominoDB_port', '');
		
		$user = new SG_Texte($pUser);
		$this -> user = $user -> texte;
		if ($this -> user === '') {
			$this -> user = SG_Config::getConfig('DominoDB_login', '');
		}
		
		$psw = new SG_Texte($pPassword);
		$this -> psw = $psw -> texte;
		if ($this -> psw === '') {
			$this -> psw = SG_Config::getConfig('DominoDB_password', '');
		}
	}
	
	/** 1.1 ; 1.3.4 @param return , connect avec base @Texte
	* établir la connexion et obtenir un cookie
	* @param @DictionnaireBase : base à accéder (sinon valeurs de cet objet)
	* @return : '' ou @Erreur
	*/
	public function Connecter($pDocBase = '', $pURL = '') {
		$ret = '';
		$docbase = $pDocBase;
		if (getTypeSG($docbase) === '@DictionnaireBase') {
			$serveur = $docbase -> getValeur('@AdresseIP');
			$user = $docbase -> getValeur('@Administrateur');
			$psw = $docbase -> getValeur('@MotDePasse');
		} else {
			$serveur = $this -> serveur;
			$user = $this -> user;
			$psw = $this -> psw;
		}
		if($this -> serveur === '' and sizeof($this -> connexion) > 0) { // premier de la liste
			reset($this-> connexion);
			$serveur = $this -> serveur = key($this-> connexion);
			$user = $this -> user = $this-> connexion[$serveur]['user'];
			$psw = $this -> psw = $this-> connexion[$serveur]['psw'];
		}
		if (! isset($this-> connexion[$serveur]) or !isset($this-> connexion[$serveur]['user']) or $this-> connexion[$serveur]['user'] !== $user
			or  !isset($this-> connexion[$serveur]['cookie']) or $this-> connexion[$serveur]['cookie'] === '') {
			$req = 'username=' . $user . '&password=' . $psw;
			$opts = array(
				'http'=>array(
					'method' => "POST",
					'content' => $req,
					'header' => 'Accept-language: en\r\n' 
						. 'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)\r\n'
						. 'Content-type: application/x-www-form-urlencoded\r\n'
						. 'Content-length: ' . strlen($req) . '\r\n' 
					)
				);
			$context = stream_context_create($opts);
			if ($this -> cookie !== '') {
				$fp = @fopen('http://' . $serveur . '/names.nsf?Logout', 'r', false, $context);
			}
			$url = 'http://' . $serveur . '/names.nsf?login';
			if (!($fp = @fopen($url, 'r', false, $context))) {
				$ret = new SG_Erreur('0021');
			}
			if ($ret === '') {
				$meta = stream_get_meta_data($fp);
				$cookie = '';
				for ($j = 0; isset($meta["wrapper_data"][$j]); $j++) {
					$hr = strtolower($meta["wrapper_data"][$j]);
					if (strstr($hr, 'set-cookie')) {
						$cookie = substr($meta["wrapper_data"][$j],12); 
					} elseif (strstr($hr, 'content-type')) {
						$contenttype = substr($meta["wrapper_data"][$j],12); 
					}
				}
				fclose($fp);
				$this -> cookie = $cookie;
			}
			if ($ret === '') {
				$this -> connexion[$serveur] = array('user' => $user, 'cookie' => $cookie, 'psw' => $psw);
			}
		}
		return $ret;
	}
	
	/** 1.1 ; 1.3.4 test retour connecter ; anti-boucle
	* obtenir le résultat d'une URL Domino
	*/
	public function getURL($pServeur = '', $pURL = '') {
		$ret = '';
		$serveur = $pServeur;
		if(getTypeSG($pServeur) === '@DictionnaireBase') {
			$serveur = $pServeur -> getValeur('@AdresseIP','');
		} elseif($pServeur === '') {
			$serveur = $this -> serveur;
		}
		if (!isset($this -> connexion[$serveur]['cookie']) or $this -> connexion[$serveur]['cookie'] === '') {
			$ret = $this -> Connecter($serveur);
		}
		$cookie = '';
		if(isset($this -> connexion[$serveur]['cookie'])) {
			$cookie = $this -> connexion[$serveur]['cookie'];
		}
		if($ret === '') {
			if ($cookie === '') {
				$ret = new SG_Erreur('0024');
			} else {
				$url = SG_Texte::getTexte($pURL);
				$req = 'username=' . $this -> connexion[$serveur]['user'] . '&password=' . $this -> connexion[$serveur]['psw'];
				$opts = array(
					'http' => array(
						'method'=>"POST",
						'content' => $req,
						'header'=>"Accept-language: en\r\n" . "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)\r\n"
							. "Content-type: application/x-www-form-urlencoded \r\n"
							. "Cookie: " . $cookie . "\r\n"
							. "Content-length: ". strlen($req) . "\r\n"
					)
				);
				$context = stream_context_create($opts);
				$ret = @file_get_contents("http://" . $serveur . '/' . $url, false, $context);
				if ($ret === false) {
					$ret = new SG_Erreur('0103');
				} elseif ($this -> estLogin($ret)) {// login périmé : retester une fois
					$this -> fois ++;
					if ($this -> fois > 10) {
						$ret = new SG_Erreur('0104');
						$this -> fois = 0;
					} else {
						unset($this -> connexion[$serveur]['user']);
						unset($this -> connexion[$serveur]['cookie']);
						$this -> cookie = '';
						$ret = $this -> Connecter($serveur);
						$ret = $this -> getURL($pServeur, $pURL);
					}
				} else {
					$this -> lastheaders = array();
					foreach($http_response_header as $hd) {
						$key = strtolower(strstr($hd, ':', true));
						$value = trim(substr(strstr($hd, ':'), 1));
						$this -> lastheaders[$key] = $value;
					}
				}
			}
		}
		return $ret;
	}
	/** 1.3.4 ajout
	* Tester si le retour est une demande de login
	**/
	function estLogin($pHTML = '') {
		$html = new SG_HTML($pHTML);
		$html -> analyser();
		$ret = (isset($html -> forms[0]['action']) and substr($html -> forms[0]['action'], -6) === '?Login'); // y a-t-il une action '?Login'
		return $ret;
	}
}
?>
