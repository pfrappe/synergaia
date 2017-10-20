<?php
/** SYNERGAIA fichier pour le traitement de l'objet @SiteInternet */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * Classe SynerGaia de gestion d'un site internet
 * @since 1.3
 * @version 1.3.1
 */
class SG_SiteInternet extends SG_Document {
	/** string Type SynerGaia */
	const TYPESG = '@SiteInternet';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	/** string Dernière erreur rencontrée */
	public $Erreur = '';
	
	/** string URL d'accès au site */
	public $url;
	
	/** string lien location */
	public $location = '';
	
	/** string champ de login */
	public $champLogin;
	
	/** string champ mot de passe */
	public $champMotDePasse;
	
	/** string valeur du login */
	public $login;
	/** string valeur du mot de passe */
	private $motDePasse;
	
	/** boolean a besoin d'un login ? */
	public $isOpen = false;
		
	/** boolean gérée par PHPSSID ? */
	public $phpsessid = false;
	
	/** array cookies de la page */
	public $cookies = array();

	/** string dernier status de la requête */
	public $status = '';

	/** string Réponse du header */
	public $headerResponse;
	
	/**
	 * Construction de l'objet
	 * 
	 * 
	 * @since1.3
	 * @param string $pCode code du site demandé
	 * @param array $pTableau tableau des propriétés
	 */
	public function __construct($pCode = null, $pTableau = null) {
		$base = SG_Dictionnaire::getCodeBase($this -> typeSG);
		$tmpCode = new SG_Texte($pCode);
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code', '');
		$this -> setValeur('@Type', '@SiteInternet');
		// lecture de l'url du site
		$this -> url = $this -> getValeur('@Adresse','');
		if (substr($this -> url, 0, 5) !== 'http:' and substr($this -> url, 0, 6) !== 'https:') {
			$this -> url = 'http://' . $this -> url;
		}
		if (substr($this -> url, -1) === '/') {
			$this -> url = substr($this -> url, 0, -1) ;
		}
		// lecture du mode de gestion phpessid
		$phpsessid = $this -> getValeurPropriete('@PHPSESSID','');
		if ($phpsessid !== '') {
			$this -> phpsessid = $phpsessid -> estVrai();
		}
		// nécessite un login ?
		$this -> champLogin = $this -> getValeur('@ChampLogin', '');
		$this -> champMotDePasse = $this -> getValeur('@ChampMotDePasse', '');
		$this -> motDePasse = $this -> getValeur('@MotDePasse','');
		$this -> login = $this -> getValeur('@Login', '');
		$this -> isOpen = ($this->login === '');
	}

	/**
	 * Ouvrir un site internet pour obtenir une session
	 * 
	 * @since 1.3.0 ajout
	 * @param string|SG_Texte|SG_Formule $pURL adresse de la page à ouvrir (au-delà de la page du site)
	 * @return $this
	 */
	public function Ouvrir($pURL = '') {
		if (!$this -> isOpen) {
			if($pURL !== '') {
				$url = $this -> urlComplete($pURL);
				$urlLogin = $url;
			} else {
				$urlLogin = $this -> getValeur('@AdresseLogin', '');
				$url = '';
			}
			// remplissage...
			// demande de la page de login
			$page = $this -> requete($urlLogin);
			if (getTypeSG($page) === '@Erreur') {
				$ret = $page;
			} elseif($page -> doc -> texte !== '') {
				// recherche des champs login sur la page
				if ($page -> estPageLogin() === true) {
					$page -> doc -> proprietes[$this -> champLogin] = $this -> login;
					$page -> doc -> proprietes[$this -> champMotDePasse] = $this -> motDePasse;
					foreach($page -> doc -> forms as $form) {
						if($form['action'] !== '') {
							$action = $this -> urlComplete($form['action']);
						}
						if(isset($form['methode']) and !is_null($form['methode']) and $form['methode'] !== '') {
							$methode = $form['methode'];
						}
					}
					foreach($page -> doc -> proprietes as &$champ) {
						if(gettype($champ) === 'object') {
							$champ = $champ -> toString();
						}
					}
					// préparation de l'url
					if ($this -> location !== '') {
						$url = $this -> urlComplete($this -> location);
					} elseif ($action !== '') {
						$url = $action;
					}
					//... et post des champs
					$methode = 'POST';
					$r = $this -> requete($url, $methode, $page -> doc -> proprietes);
				}
			}
		}
		return $this;
	}

	/**
	 * Chercher une page internet, voire un champ précis
	 * 
	 * @since 1.3.0 ajout
	 * @version 2.0 parm 2 et 3 pour compatibilité avec "@Document.@Chercher"
	 * @param string|SG_Texte|SG_Formule $pURL adresse de la page à ouvrir (au-delà de la page du site)
	 * @param string|SG_Texte|SG_Formule $pChamp nom du champ à récupérer
	 * @param string|SG_Texte|SG_Formule
	 * @return SG_PageInternet|SG_Erreur
	 */
	public function Chercher($pURL = '', $pChamp = '', $pSens = 'e') {
		$this -> Erreur = '';
		$url = $this -> urlComplete(SG_Texte::getTexte($pURL));
		$ret = $this -> requete($url);
		if(getTypeSG($ret) !== '@Erreur') {
			if (!$this -> isOpen) { // nécessite un login
				if($ret -> estPageLogin()) {
					$ret = $this -> Ouvrir($url) -> requete($url);
					if(getTypeSG($ret) === '@PageInternet') {
						if($ret -> estPageLogin()) {
							$ret = new SG_Erreur('0065');
						}
					}
				}
			} elseif ($this -> Erreur != '')  {
				$ret = new SG_Erreur($this -> Erreur);
			}
		}
		return $ret;
	}

	/**
	* Execute une requete sur le serveur et retourne le code html reçu.
	* ATTENTION il n'y a aucune exécution de javascript ni inclusion des CSS !!
	* 
	* @since 1.3.0 ajout
	* @version 1.3.1 try catch
	* @param string $pURL url de la requete
	* @param string $pMethode méthode HTTP (GET, POST, PUT, DELETE)
	* @param string $pContenu contenu complémentaire de la requete
	* @param string $pContentType Content-Type de la requete
	* @return (@PageInternet ou @Erreur) résultat de la requete
	*/
	public function requete($pURL = '', $pMethode = 'GET', $pContenu = array(), $pContentType = 'application/x-www-form-urlencoded') {
		$this -> Erreur = '';
		$header = "Accept: */*;\r\n";
		$header.= "Content-Type: " . $pContentType . "\r\n";
		// écriture des cookies
		$cookies = '';
		foreach($this -> cookies as $nom => $cookie) {
			if($cookies !== '') {
				$cookies .=';';
			}
			$cookies.= ' ' . $nom . '=' . $cookie;
		}
		if ($cookies !== '') {
			$header.= "Cookie:" . $cookies . "\r\n";
		}
		// fabrication des champs
		$champs = array();
		foreach($pContenu as $key => $champ) {
			$champhtml = '';
			if (is_object($champ)) {
				if(getTypeSG($champ) === '@VraiFaux') {
					if ($champ -> estVrai()) {
						$champhtml = 'on';
					}
				} else {
					$champhtml = $champ -> toString();
				}
			} else {
				$champhtml = '' . $champ;
			}
			$champs[$key] = str_replace(array(chr(13),chr(10)),' ', $champhtml);
		}
		$content = http_build_query($champs);
		// constitution du header
		$header.= "Content-Length: " . strlen($content) . "\r\n";
		$options = array('http' => array('method' => $pMethode, 'header' => $header, 'content' => $content));
		$contexte = stream_context_create($options);
		// envoi
		if(is_null($pURL)) {
			$ret = new SG_Erreur('0066');
		} else {
			$ret = '';
			try {
				$texte = file_get_contents($pURL, false, $contexte);
			} catch (ErrorException $e) {
				$ret = new SG_Erreur($e -> getMessage());
			}
			if ($ret === '') {
				// décoder le header reçu
				if(!is_null($http_response_header) and $http_response_header !== '') {
					$this -> headerResponse = $http_response_header;
					$this -> location = '';
					foreach($http_response_header as $key => $value) {
						if (substr($value, 0, 5) === "HTTP/") {
							$this -> status = trim(strstr($value, ' '));
						} elseif (substr($value, 0, 10) === 'Set-Cookie') {
							$cookies = explode(';', substr($value, 12));
							foreach($cookies as $cookie) {
								$cookienom = trim(strstr($cookie, '=', true));
								$cookievaleur = trim(substr(strstr($cookie, '='), 1));
								$ok = true;
								// cas des sites php avec session
								if($cookienom == 'PHPSESSID') {
									if($this -> phpsessid === true) {
										//if(isset($this->cookies['PHPSESSID'])) {
										//	$ok = false;
										//}
										if ($this -> status !== '200 OK') {
											$ok = false;
										}
									}
								}
								if ($ok) {
									$this -> cookies[$cookienom] = $cookievaleur;
								}
							}
						} elseif (substr($value, 0, 8) === 'Location') {
							$this -> location = $this -> urlComplete(trim(substr($value, 9)));
						}
					}
					if (is_null($texte)) {
						$ret = new SG_Erreur('erreur html');
					} else {
						$html = new SG_HTML($texte);
						$ret = $this -> initPageInternet($pURL, $html);
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Simule le remplissage et l'envoi via un bouton
	 * 
	 * @since 1.3.1 ajout
	 * @param string|SG_Texte|SG_Formule nom du bouton à cliquer
	 * @param string|SG_Texte|SG_Formule nom du champ
	 * @param string|SG_Texte|SG_Formule valeur du champ
	 * @param + @param nfois pour les champs suivants
	 * @return (SG_HTML) résultat de la requête
	 * @todo à terminer
	 */
	function Envoyer ($pNomBouton = '') {
	} 

	/**
	 * Simule le clic sur un bouton
	 * 
	 * @since 1.3.1 ajout
	 * @param string|SG_Texte|SG_Formule nom du bouton à cliquer
	 * @return SG_HTML résultat de la requête
	 * @todo à terminer
	 */
	function Clic ($pNomBouton = '') {
	}

	/**
	 * concatene l'adresse du site et l'action
	 * @since 1.3.1 ajout
	 * @param string $pURL
	 * @return string l'URL complète
	 */
	function urlComplete ($pURL = '') {
		if ($pURL === '') {
			$ret = $this -> url;
		} elseif (substr($pURL, 0, 5) === 'http:' or substr($pURL, 0, 6) === 'https:') {
			$ret = $pURL;
		} elseif (substr($pURL, 0, 1) === '/' or substr($pURL, 0, 1) === '?') {
			$ret = $this -> url . $pURL;
		} else {
			$ret = $this -> url . '/' . $pURL;
		}
		return $ret;
	}

	/**
	 * initialise une @PageInternet à partir du texte html reçu d'une requête
	 * 
	 * @since 1.3.1 ajout
	 * @param string $pURL : l'adresse de la page
	 * @param string $pHTML : le texte html de la page
	 * @return SG_PageInternet la page créée
	 */
	function initPageInternet ($pURL = '', $pHTML = '') {
		if(getTypeSG($pHTML) === '@Erreur') {
				$ret = $pHTML;
		} else {
			$ret = new SG_PageInternet('');
			$ret -> site = $this;
			$ret -> url = $pURL;
			$ret -> doc = $pHTML;
			$ret -> doc -> analyser(); // calculer les champs éventuels
		}
		return $ret;
	}
}
