<?php
/** SYNERGAIA Fichier contenant la classe SynerGaïa SG_CouchDB */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * Classe SynerGaia de gestion d'une base de données CouchDB
 * @version 2.6
 * @since 0.0
 */
class SG_CouchDB extends SG_Objet {
	/** string Type SynerGaia '@CouchDB' */
	const TYPESG = '@CouchDB';
	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/** string Url de préfixe des requetes au serveur */
	public $url;

	/** 
	 * Construction de l'objet SG_CouchDB à partir des infos du fichier de config (host, port, admin, psw)
	 * @version 2.3 port 5984 par défaut
	 */
	public function __construct() {
		$host = SG_Config::getConfig('CouchDB_host', '');
		$port = SG_Config::getConfig('CouchDB_port', '5984');
		$login = SG_Config::getConfig('CouchDB_login', '');
		$password = SG_Config::getConfig('CouchDB_password', '');

		$auth = '';
		if ($login !== '') {
			$auth = $login . ':' . $password . '@';
		}
		$this -> url = 'http://' . $auth . $host . ':' . $port . '/';
	}

	/**
	 * Execute une requete CouchDB sur le serveur
	 * 
	 * @since 0.0
	 * @version 2.6 mémory limit 512 ; try ; err 0308, 0309
	 * @param string $pURL url de la requete
	 * @param string $pMethode méthode HTTP (GET, POST, PUT, DELETE)
	 * @param string $pContenu contenu complémentaire de la requete
	 * @param string $pContentType Content-Type de la requete
	 * @return string|SG_Erreur résultat de la requête
	 */
	public function requete($pURL = '', $pMethode = 'GET', $pContenu = '', $pContentType = 'application/x-www-form-urlencoded') {
		$options = array('http' => array('method' => $pMethode, 'header' => "Accept: */*;\r\n" . "Content-Type: " . $pContentType . "\r\n" . "Content-Length: " . strlen($pContenu) . "\r\n", 'content' => $pContenu));
		$contexte = stream_context_create($options);
		try {
			ini_set('memory_limit', '512M'); // 2.1 pour répertoire //TODO Supprimer ?
			$ret = file_get_contents($pURL, false, $contexte);
			ini_restore('memory_limit');
		} catch (Exception $e) {
			$t = $e -> getTrace()[0]['args'];
			if (isset($t[4]['http_response_header']) and substr($t[4]['http_response_header'][0],0,12)  === 'HTTP/1.0 404') {
				$ret = new SG_Erreur('0308', $e -> getMessage());
			} else {
				$ret = new SG_Erreur('0309', $e -> getMessage());
			}
		}
		return $ret;
	}

	/**
	 * initDBDocument : crée ou recherche le document CouchDB
	 * @todo vérifier si jamais appelée ??
	 * @since 1.0.7
	 * @param string $pCodeBase code de la base couchDB sans le prefixe
	 * @param string $pRefenceDocument référence du document
	 * @param string $pTableau si on fourni directement du JSON on le construit à partir de là
	 */
	function initDBDocument($pCodeBase = '', $pRefenceDocument = null, $pTableau = null) {
		if (getTypeSG($pCodeBase) === '@DocumentCouchDB') {
			$ret = $pCodeBase;
		} else {
			$codeBase = SG_Texte::getTexte($pCodeBase);
			$codeDocument = '';
			$referenceDocument = SG_Texte::getTexte($pRefenceDocument);
			if ($referenceDocument !== '') {
				if (strpos($referenceDocument, '/') === false) {
					$codeBase = $referenceDocument;
				} else {
					$elements = explode('/', $referenceDocument);
					$codeBase = $elements[0];
					if (sizeof($elements) > 1) {
						$codeDocument = $elements[1];
						if (sizeof($elements) > 2) {
							$codeDocument .= '/' . $elements[2];
						}
					}
				}
				// Si on a un doublon dans le code de base (répété au début du code du document)
				if (substr($codeDocument, 0, strlen($codeBase) + 1) === ($codeBase . '/')) {
					$codeDocument = substr($codeDocument, strlen($codeBase) + 1);
				}
			}
			$ret = new SG_DocumentCouchDB($codeBase, $codeDocument, $pTableau);
		}
		return $ret;
	}

	/**
	 * Determine si le codeBase correspond à une base système CouchDB
	 * @param string $pCodeBase code de la base à tester
	 * @return boolean base système
	 */
	static public function isBaseSysteme($pCodeBase = '') {
		$ret = false;
		if (substr($pCodeBase, 0, 1) === '_') {
			$ret = true;
		}
		return $ret;
	}

	/**
	 * Teste la connexion au serveur CouchDB
	 * @return boolean connexion ok
	 */
	public function testConnexion() {
		$statut = $this -> requete($this -> url);
		if ($statut === false) {
			$ret = false;
		} else {
			$ret = true;
		}
		return $ret;
	}

	/**
	 * Calcule un Unique Universal ID à partir du serveur CouchDB
	 * 
	 * @version 2.7 test SG_Erreur
	 * @return string UUID
	 */
	public function getUUID() {
		$ret = '';
		$url = $this -> url . '_uuids';
		$resultat = $this -> requete($url, 'GET');
		if ($resultat instanceof SG_Erreur) {
			$ret = $resultat;
		} elseif (strlen($resultat) !== 0) {
			$uuids = json_decode($resultat);
			$ret = $uuids -> uuids;
			if (is_null($ret)) {
				$ret = '';
			} else {
				$ret = $ret[0];
			}
		}
		return $ret;
	}

	/**
	 * Liste les bases existantes sur le serveur CouchDB
	 * @return array liste des bases
	 */
	public function ListerBases() {
		$ret = array();
		$url = $this -> url . '_all_dbs';
		$resultat = $this -> requete($url, 'GET');
		if (strlen($resultat) !== 0) {
			$ret = json_decode($resultat);
		}
		return $ret;
	}

	/**
	 * Détermine si une base existe
	 * @version 2.4 getTexte
	 * @version 2.6 test erreur
	 * @param string $pNomBase nom de la base demandée
	 * @return SG_VraiFaux base existe ou non
	 */
	public function BaseExiste($pNomBase = '') {
		if (is_string($pNomBase)) {
			$nombase = $pNomBase;
		} else {
			$nombase = SG_Texte::getTexte($pNomBase);
		}
		$ret = false;
		if ($nombase !== '') {
			$codeAppli = SG_Config::getConfig('CouchDB_prefix', '');
			if(!isset($_SESSION[$codeAppli]['BE'][$nombase])) {
				$url = $this -> url . $nombase;
				$resultat = $this -> requete($url, 'GET');
				if($resultat instanceof SG_Erreur) {
					$ret = false;
				} elseif (strlen($resultat) !== 0) {
					$infos = json_decode($resultat);
					$db_name = $infos -> db_name;
					if (is_null($db_name)) {
						$db_name = '';
					}
					$ret = ($db_name === $nombase);
				}
				$_SESSION[$codeAppli]['BE'][$nombase] = $ret;
			}
			$ret = $_SESSION[$codeAppli]['BE'][$nombase]; //new SG_VraiFaux($retBool);
		}
		return $ret;
	}

	/** 
	 * Ajoute une base au serveur CouchDB
	 * @version 2.1 teste si BaseExiste()
	 * @version 2.6 test SG_Erreur
	 * @param string $pNomBase nom de la base à créer
	 * @return SG_VraiFaux si tout s'est bien passé
	 */
	public function AjouterBase($pNomBase) {
		$retBool = false;
		if ($pNomBase !== '') {
			if (!$this -> BaseExiste($pNomBase)) {
				$url = $this -> url . $pNomBase;
				$resultat = $this -> requete($url, 'PUT');
				if($resultat instanceof SG_Erreur) {
					$retBool = false;
				} elseif (strlen($resultat) !== 0) {
					$infos = json_decode($resultat);
					$retBool = ($infos -> ok = true);
				}
			}
		}
		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}

	/**
	* Supprime une base du serveur CouchDB
	* @version 2.6 retourne true plutôt que false si base n'existe pas
	* @param string $pNomBase nom de la base à supprimer
	* @return SG_VraiFaux si tout s'est bien passé
	*/
	public function SupprimerBase($pNomBase = '') {
		$retBool = true;
		if ($pNomBase !== '') {
			if ($this -> BaseExiste($pNomBase)) {
				$url = $this -> url . $pNomBase;
				$resultat = $this -> requete($url, 'DELETE');
				if (strlen($resultat) !== 0) {
					$infos = json_decode($resultat);
					$retBool = $infos -> ok;
					if (is_null($infos -> ok)) {
						$retBool = false;
					}
				} else {
					$retBool = false;
				}
			}
		}
		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}

	/** 
	 * Normalise le nom d'une base CouchDB (espaces, accents, ...)
	 * @version 2.1 plus de cache
	 * @param string|SG_Texte|SG_Formule $pNomBase nom de la base
	 * @param boolean|SG_VraiFaux|SG_Formule $pForce force le recalcul dans $_SESSION['bases']
	 * @return string nom de la base normalisé
	 * @uses SG_Rien::Normaliser()
	 */
	static public function NormaliserNomBase($pNomBase = '', $pForce = false) {
		$nomNormalise = '';
		if ($pForce === false and isset($_SESSION['bases'][$pNomBase])) {
			$nomNormalise = $_SESSION['bases'][$pNomBase];
		} else {
			if($pNomBase === '') {
				$nomNormalise = '';
			} else {
				$nomNormalise = SG_Rien::Normaliser($pNomBase);
			}
			$_SESSION['bases'][$pNomBase] = $nomNormalise;
		}
		return $nomNormalise;
	}
	
	/** 
	 * Vue d'accès à tous les objets d'un modèle donné
	 * @version 1.3.1 ajout @Titre
	 * @since 1.0.1
	 * @param string $pModele modèle à explorer
	 * @param boolean $force recalcule la définition de la vue 
	 * @return SG_Collection des documents
	 */
	function getAllDocuments($pModele = '', $force = false) {
		$ret = new SG_Collection();
		if ($pModele !== '') {
			$codeBase = SG_Dictionnaire::getCodeBase($pModele);
			$nomVue = 'all_' . $pModele . '_list';

			$jsSelection = "function(doc) {if (doc['@Type']==='" . $pModele . "'){var titre=''; var code=''; var texte=''; ";
			if($pModele === '@Utilisateur') {
				$jsSelection .= "if (doc['Prenom']) {titre=doc['Nom'] + ' ' + doc['Prenom']} else {titre=doc['Nom']}; code=doc['Identifiant']; ";
			} else {
				$jsSelection .= "if(doc['Titre']) {titre=doc['Titre']} else if(doc['@Titre']) {titre=doc['@Titre']} else {titre=''} 
				if(doc['Code']) {code=doc['Code']} else if (doc['@Code']) {code=doc['@Code']} ";
			}
			$jsSelection .= "if(!code){texte=titre} else {texte=code+' ('+titre+')'} emit(null,texte + '|' + doc['_id']);}}";
			$vue = new SG_Vue($codeBase . '/' . strtolower($nomVue),$codeBase,$jsSelection, true);
			if($force === true) {
				$vue -> Enregistrer();
			}					
			$ret = $vue -> ChercherValeurs();
		}
		return $ret;
	}

	/** 
	 * getChercherDocuments : chercher les documents d'un modèle donné (et d'un code éventuellement)
	 * @since 1.0.6
	 * @version 2.3 init code=doc['_id']
	 * @param string $pCodeBase	base à explorer
	 * @param string $pTypeObjet	le type d'objet à retrouver
	 * @param string|SG_Texte|SG_Formule $pCodeObjet	le code de l'objet si on le connait
	 * @param string|SG_Texte|SG_Formule $pFiltre : formule de filtrage sur la collection des documents récupérés
	 * @return SG_Collection des documents
	 */
	function getChercherDocuments($pCodeBase = '', $pTypeObjet = '', $pCodeObjet = '', $pFiltre = '') {
		$codeAppli = SG_Config::getCodeAppli();
		if (!isset($_SESSION[$codeAppli]['gCD'][$pCodeBase])) {
			$_SESSION[$codeAppli]['gCD'][$pCodeBase] = new SG_DictionnaireBase($pCodeBase);
		}
		$base = $_SESSION[$codeAppli]['gCD'][$pCodeBase];
		$acces = $base -> getValeur('@Acces', 'couchdb');
		if ($acces === 'couchdb') {
			$jsSelection = self::javascript('1', $pTypeObjet); // code iddoc
			$vue = new SG_Vue('', $pCodeBase, $jsSelection, true);
			$ret = $vue -> ChercherElements($pCodeObjet, $pFiltre);
		} elseif ($acces === 'domino') {
			$code = SG_Texte::getTexte($pCodeObjet);
			if ($code !== '') {
				$ret = new SG_Document($code, $base);
			} else {
				$ret = new SG_Erreur('0092', $pCodeBase);
			}
		} else {
			$ret = new SG_Erreur('0093', $pCodeBase);
		}
		return $ret;
	}

	/**
	 * Retourne le tableau des propriétés d'un objet, éventuellement d'un type précis donné
	 * @since 1.0.7
	 * @version 2.1 stockage cache dans $_SESSION[$codeAppli]['PO'][$pCodeObjet]
	 * @param string $pCodeObjet code de l'objet à analyser
	 * @param string $pModele est un filtre supplémentaire éventuel
	 * @param boolean $pForce force le recalcul et la remise en cache
	 * @return @Collection dont le tableau est composé d'array ('nom' : propriété, 'modele' : modele de la propriété)
	 */
	function getProprietesObjet ($pCodeObjet = '', $pModele = '', $pForce = false) {
		$ret = new SG_Collection();
		if ($pCodeObjet !== '') {
			$codeAppli = SG_Config::getCodeAppli();
			if ($pForce or ! isset($_SESSION[$codeAppli]['PO'][$pCodeObjet])) {
				$vue = new SG_Vue(SG_Dictionnaire::CODEBASE . '/vue_proprietesobjet', SG_Dictionnaire::CODEBASE, self::javascript('6'), true);
				// oncherche à partir du code (<1.0.5) puis de l'id (à partir 1.0.6)
				$v = $vue -> ChercherValeurs($pCodeObjet);
				$_SESSION[$codeAppli]['PO'][$pCodeObjet] = $vue -> ChercherValeurs($pCodeObjet);
			}
			$collec = $_SESSION[$codeAppli]['PO'][$pCodeObjet];
			if($pModele !== '') {				
				$modele = SG_Dictionnaire::getDictionnaireObjet($pModele);
				$idModele = '';
				if (is_object($modele)) {
					$idModele = $modele -> getUUID();
				}
				$tableau = array();
				foreach ($collec -> elements as $element) {
					if ($element['idmodele'] === $pModele) {
						$tableau[] = $element;
					} elseif ($element['idmodele'] === $idModele) {
						$tableau[] = $element;
					}
				}
				$collec -> elements = $tableau;
			}
			$ret = $collec;
		}
		return $ret;
	}

	/**
	 * Retourne le tableau des méthodes d'un objet, éventuellement d'un type précis donné
	 * 
	 * @since 1.2
	 * @param string $pCodeObjet code de l'objet à analyser
	 * @param string $pModele est un filtre supplémentaire éventuel
	 * @return @Collection dont le tableau est composé d'array ('nom' : méthode, 'modele' : modele de la méthode)
	 */
	function getMethodesObjet ($pCodeObjet = '', $pModele = '') {
		$ret = new SG_Collection();
		if ($pCodeObjet !== '') {
			$js = self::javascript('10');
			$vue = new SG_Vue(SG_Dictionnaire::CODEBASE . '/vue_methodesobjet', SG_Dictionnaire::CODEBASE, $js, true);
			// oncherche à partir de l'id
			$collec = $vue -> ChercherValeurs($pCodeObjet);
			if($pModele !== '') {				
				$modele = SG_Dictionnaire::getDictionnaireObjet($pModele);
				$idModele = '';
				if (is_object($modele)) {
					$idModele = $modele -> getUUID();
				}
				$tableau = array();
				foreach ($collec -> elements as $element) {
					if ($element['idmodele'] === $pModele) {
						$tableau[] = $element;
					} elseif ($element['idmodele'] === $idModele) {
						$tableau[] = $element;
					}
				}
				$collec -> elements = $tableau;
			}
			$ret = $collec;
		}
		return $ret;
	}

	/**
	 * Index inversé des objets liés à un document dans CouchDB. Retourne toujours une collection d'objets
	 * 
	 * @since 1.0.7
	 * @version 2.5 utilise javascript()
	 * @param SG_Document $pDocument id document à rechercher
	 * @param string|SG_Texte|SG_Formule $pModele modèle à indexer
	 * @param string|SG_Texte|SG_Formule $pChamp limiter la recherche à un nom de champ du  modèle 
	 * @return SG_Collection des documents liés
	 */
	function getObjetsLies($pDocument, $pModele, $pChamp = '') {
		// Base dans laquelle aller chercher (celle du modele)
		$codeBase = SG_Dictionnaire::getCodeBase($pModele);
		$champ = SG_Texte::getTexte($pChamp);
		$typeObjet = getTypeSG($pDocument);
		$listeChamps = SG_Dictionnaire::getListeChamps($pModele, $typeObjet);
		$js = self::javascript('11', $pModele, $listeChamps);
		if(getTYpeSG($pDocument) === '@Utilisateur') {
			$cle = SG_Annuaire::CODEBASE . '/' . $pDocument -> identifiant;
		} else {
			$cle = $pDocument -> getUUID();
		}
		$vue = new SG_Vue('', $codeBase, $js, true);
		$ret = new SG_Collection();
		$result = $vue -> ChercherValeurs($cle);
		if(is_object($result)) {
			$tab = $result -> elements;
			foreach ($tab as $row) {
				if ($champ === '' or ($champ !== '' and $row['champ'] == $champ)) {
					$doc = $_SESSION['@SynerGaia'] -> getObjet($codeBase . '/' . $row['id']);
					$doc -> proprietes['@Comme'] = $row['champ'];
					$ret -> Ajouter($doc);
				}
			}
		}
		return $ret;
	}

	/**
	 * Détermine le document à partir de son @Type et @Idenfitiant
	 * @since 1.0.7
	 * @version 2.5 utilise javascript()
	 * @param string $pType type SynerGaïa du document cherché
	 * @param string $pChamp nom du champ où se trouve la clé
	 * @param string $pValeur valeur de la propriété @Identifiant du document cherché
	 * @return SG_Collection
	 */
	static function getDocumentsFromTypeChamp($pType = '', $pChamp = '', $pValeur = '') {
		$codeBase = SG_Dictionnaire::getCodeBase($pType);
		$js = self::javascript('2', $pType, $pChamp);
		$vue = new SG_Vue('', $codeBase, $js, true);
		return $vue -> ChercherElements($pValeur);
	}
	
	/**
	 * chercherFlou : chercher des documents d'un certain modèle et dont certains champs contiennent le texte passé en paramètre
	 * @todo pas terminé : utilisé ?
	 * @since 1.1
	 * @param any $pTexte : texte à rechercher
	 * @param any $pModeles : modèle ou tableau de modèles dans lesquels chercher
	 * @param any $pChamps : nom de champ ou champs ou tableau des champs qu'il faut analyser dans ces modèles
	 * @return SG_Collection des documents trouvés, composite si plusieurs modèles analysés
	 */	 
	function chercherFlou($pTexte = '', $pModeles, $pChamps) {
		$ret = new SG_Collection();
		$texte = $pTexte;
		$type = getTypeSG($pTexte);
		if (getTypeSG($texte) === '@Formule') {
			$texte = $pTexte -> calculer();
			$type = getTypeSG($texte);
		}
		if (getTypeSG($texte) !== 'string') {
			$texte = $texte -> toString();
			$type = getTypeSG($texte);
		}
		// toujours vide...
		return $ret;
	}
	/**
	 * Lance une réplication avec un autre serveur CouchDB
	 * @since 1.1 ajout
	 * @version 2.6 SG_SynerGaia static
	 * @param string|SG_Texte|SG_Formule $pAdresse : adresse ip du serveur visé
	 * @param string|SG_Texte|SG_Formule$pID : id de l'administrateur CouchDB sur le serveur visé
	 * @param string|SG_Texte|SG_Formule$pPSW : mot de passe de l'administrateur CouchDB sur le serveur visé
	 * @param boolean|SG_VraiFaux|SG_Formule $pContinue : true si la réplication doit se poursuivre en continu (false par défaut)
	 * @param boolean|SG_VraiFaux|SG_Formule $pCreate : true si on accepte de créer les nouvelles base sur le serveur visé (false par défaut)
	 * @return SG_Collection Collection des objets SG_Replication créés
	 */
	function RepliquerAvec($pAdresse = '', $pID = '',$pPSW = '', $pContinue = true, $pCreate = true) {
		// préparer les paramètres
		$adresse = SG_Texte::getTexte($pAdresse);
		$id = SG_Texte::getTexte($pID);
		$psw = SG_Texte::getTexte($pPSW);
		$bases = self::ListerBases();
		$continue = new SG_VraiFaux($pContinue);
		if ($continue -> estVrai() === true) {
			$continue = true;
		} else {
			$continue = false;
		}
		$create = new SG_VraiFaux($pCreate);
		if ($create -> estVrai() === true) {
			$create = true;
		} else {
			$create = false;
		}
		// enregistrer les réplications de chaque base
		$ret = new SG_Collection();
		$bases = SG_SynerGaia::Bases();
		$url = $this -> url . SG_Replication::CODEBASE . '/';
		foreach ($bases -> elements as $base) {
			if ($base -> acces === '' or $base -> acces === 'couchdb') {
				$doc = new SG_DocumentCouchDB();
				$doc -> codeBase = SG_Replication::CODEBASE;
				$doc -> codeBaseComplet = SG_Replication::CODEBASE;
				$nom = $base -> base -> codeBaseComplet;
				$cible = 'http://' . $id . ':' . $psw . '@' . $adresse . ':5984/' . $nom;
				// local vers cible
				$doc -> proprietes = array('source'=>$nom, 'target'=> $cible, 'continuous' => $continue, 'create_target' => $create);
				if ($nom !== '') {
					$tmp = $doc -> Enregistrer();
					if (getTypeSG($tmp) === '@Erreur') {
						$ret -> elements[] = $tmp;
					} else {
						$ret -> elements[] = new SG_Texte($nom);
					}
				}
				// cible vers local
				$doc = new SG_DocumentCouchDB();
				$doc -> codeBase = SG_Replication::CODEBASE;
				$doc -> codeBaseComplet = SG_Replication::CODEBASE;
				$nom = $base -> base -> codeBaseComplet;
				// (inversion source et target)
				$doc -> proprietes = array('source'=>$cible, 'target'=> $nom, 'continuous' => $continue, 'create_target' => false);
				if ($nom !== '') {
					$tmp = $doc -> Enregistrer();
					if (getTypeSG($tmp) === '@Erreur') {
						$ret -> elements[] = $tmp;
					} else {
						$ret -> elements[] = new SG_Texte($nom);
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * renvoie la liste des villes de la base couchdb synergaia_villes de l'application pour utilisation dans ajax
	 * @since 1.1 ajout
	 * @param string $pCle début de cle spécifique
	 * @param string $pSelected valeur d'une clé sélectée
	 * @return string html <option>
	 */
	function getVillesAjax($pCle = '', $pSelected = '') {
		if ($pSelected === '') {
			$ret = '<option value="" selected></option>';
		} else {
			$ret = '<option value=""></option>';
		}
		$codeBase = SG_Ville::CODEBASE;
		$js = "function(doc){if (doc['@Type']==='@Ville') {emit(doc['@Titre'],doc['@Titre'] + ' (' + doc['@Departement'] + ')|" . $codeBase . "/' + doc['_id']);}}";		
		$vue = new SG_Vue('', $codeBase, $js, true);
		if ($vue -> creerVue() === true) {
			$result = $vue -> vue -> contenuBrut($pCle, false, false);
			foreach ($result as $element) {
				$value = $element['value'];
				$i = strpos($value, '|');
				$ville = substr($value,0,$i);
				$cle = substr($value,$i+1);
				if ($cle === $pSelected) {
					$ret .= '<option value="' . $cle . '" selected>' . $ville . '</option>';
				} else {					
					$ret .= '<option value="' . $cle . '">' . $ville . '</option>';
				}
			}
		}
		return $ret;
	}

	/**
	 * Renvoi les mots du dictionnaire (objets, propriétés, méthodes) commençant par la clé
	 * @since 1.2 ajout
	 * @param string $pCle
	 * @return array html de la liste de mots
	 */
	function getMots($pCle = '') {
		$codeBase = SG_Dictionnaire::CODEBASE;
		$js = "function(doc){if (doc['@Type']==='@DictionnaireObjet'||doc['@Type']==='@DictionnairePropriete'||doc['@Type']==='@DictionnaireMethode') {";
		$js.= "emit(doc['@Code'],doc['@Code']);}}";		
		$vue = new SG_Vue($codeBase . '/vue_mots', $codeBase, $js, true);
		$ret = array();
		if ($vue -> creerVue() === true) {
			$result = $vue -> vue -> contenuBrut($pCle, false, false); // seulement mots même début
			foreach ($result as $element) {
				$ret[] = $element['value'];
			}
		}
		return $ret;
	}

	/** 
	 * Envoi la liste des mots començant par la clé avec le type d'objet
	 * @since 1.2 ajout
	 * @param string $pCle : préfixe de sélection
	 * @return array tableau des objets, propriétés et méthodes (indexes)
	 */
	function getModeleDesMots($pCle = '') {
		$codeBase = SG_Dictionnaire::CODEBASE;
		$js = "function(doc){if (doc['@Type']==='@DictionnaireObjet') {emit(doc['@Code'],doc['@Modele']);} ";
		$js.= "else if (doc['@Type']==='@DictionnairePropriete') {emit(doc['@Propriete'],doc['@Modele']);} ";
		$js.= "else if (doc['@Type']==='@DictionnaireMethode') {emit(doc['@Methode'],doc['@Modele']);}}";		
		$vue = new SG_Vue($codeBase . '/vue_modelesmots', $codeBase, $js, true);
		$ret = array();
		if ($vue -> creerVue() === true) {
			$result = $vue -> vue -> contenuBrut($pCle, false, false); // seulement mots même début
			foreach ($result as $element) {
				$ret[$element['value']] = '';
			}
		}
		return $ret;
	}

	/**
	 * Retourne la version de CouchDB
	 * @since 1.2 ajout
	 * @return string n° de la version
	 */
	function version() {
		$ret = $this -> requete($this -> url);
		return $ret;
	}

	/**
	 * obtenir un objet SynerGaia par son id
	 * @since 2.1 
	 * @version 2.4 prm à new $classe($pID)
	 * @param string $pID 
	 * @param boolean $pBrut retourner le document brut ou un tableau simplifié
	 * @return array|SG_Document|SG_Erreur 
	 **/
	function getObjetByID($pID = '', $pBrut = false) {
		$ret = null;
		$i = strpos($pID, '/');
		if ($i === false) {
			$ret = new SG_Erreur('0256', $pID);
		} else {
			$iddoc = explode('/', $pID);
			$codeBase = $iddoc[0];
			$id = $iddoc[1];
			// Si on cherche une base non système on ajoute le prefixe
			$codeBaseComplet = SG_Config::getCodeBaseComplet($codeBase);
			$url = $this -> url . $codeBaseComplet . '/' . $id;
			if (isset($iddoc[2]) and $iddoc[2] !== '_attachments') {
				$url.= '/' . $iddoc[2];
				$doc = @file_get_contents($url, false);
				$doc = json_decode($doc, true);
			} else {
				ini_set('memory_limit', '512M');
				try {
					$doc = json_decode(@file_get_contents($url, false), true);
				} catch (Exception $e) {
					$doc = new SG_Erreur($url, $e -> getMessage());
				}
				ini_restore('memory_limit');
				if ($pBrut === true or getTypeSG($doc) === '@Erreur') {
					$ret = $doc;
				} elseif (isset($iddoc[2]) and $iddoc[2] === '_attachments') {
					$nom = 'nom inconnu';
					if (isset($doc['_attachments'])) {
						$nom = key($doc['_attachments']);
						$url.= '/' . $nom;
					}
					$fic = @file_get_contents($url, false);
					$ret = array();
					$ret['data'] = $fic;
					$ret['nom'] = $nom;
					if (isset($doc['_attachments'][$nom]['content_type'])) {
						$ret['type'] = $doc['_attachments'][$nom]['content_type'];
					} else {
						$ret['type'] = '';
					}
				} else {
					$ret = self::creerObjet($doc);
				}
			}
		}
		return $ret;
	}
	/** 
	 * getObjetParCode : chercher le premier donné d'un modèle donné (et d'un code éventuellement)
	 * 
	 * @since 2.1 ajout
	 * @version 2.6 0234 ERREUR_EXEC ; param $pCreer
	 * @param string $pCodeBase base à explorer
	 * @param string $pTypeObjet : le type d'objet à retrouver
	 * @param string $pCodeObjet : le code de l'objet si on le connait
	 * @param boolean $pMultiple : acceptation (true) ou refus (false) des documents en double (si false, les doublons provoquent une erreur)
	 * @param boolean $pCreer : si pas trouvé, créer un objet vide ?
	 * @return SG_Collection des documents
	 */
	function getObjetParCode($pCodeBase = '', $pTypeObjet = '', $pCodeObjet = '', $pMultiple = false, $pCreer = false) {
		$typeObjet = SG_Texte::getTexte($pTypeObjet);
		$codeBase = SG_Texte::getTexte($pCodeBase);
		$codeObjet = SG_Texte::getTexte($pCodeObjet);
		if ($codeBase === '') {
			$codeBase = SG_Dictionnaire::getCodeBase($typeObjet);
		}
		$codeAppli = SG_Config::getCodeAppli();
		if (!isset($_SESSION[$codeAppli]['gCD'][$codeBase])) {
			$_SESSION[$codeAppli]['gCD'][$codeBase] = new SG_DictionnaireBase($codeBase);
		}
		$base = $_SESSION[$codeAppli]['gCD'][$codeBase];
		$acces = $base -> getValeur('@Acces', 'couchdb');
		if ($acces === 'couchdb') {
			$jsSelection = self::javascript('1', $typeObjet);
			$vue = new SG_Vue('', $codeBase, $jsSelection, false);
			if ($vue -> creerVue() === true) {
				$ret = $vue -> vue -> contenuBrut($codeObjet, true);
				if (is_array($ret) and sizeof($ret) > 0) {
					if (sizeof($ret) === 1) {
						if(isset($ret[0]['doc'])) {
							$ret = self::creerObjet($ret[0]['doc']);
						} else {
							$ret = new SG_Erreur('0236'); // pas un doc
						}
					} else {
						if ($pMultiple === true) {		
							$new = new SG_Collection();
							foreach ($ret as $elt) {
								if(isset($elt['doc'])) {
									$doc = self::creerObjet($elt['doc']);
									$new -> elements[] = $doc;
								} else {
									$new -> elements[] = new SG_Erreur('0232'); // pas un doc
								}
							}
							$ret = $new;
						} else {
							$ret = new SG_Erreur('0233'); // plusieurs : interdits
						}
					}
				} else {
					if ($pCreer === true) {
						$classe = SG_Dictionnaire::getClasseObjet($typeObjet);
						$ret = new $classe($codeObjet);
					} else {
						$ret = new SG_Erreur('0234',$codeObjet,SG_Erreur::ERREUR_EXEC); // pas trouvé
					}
				}
			} else {
				$ret = new SG_Erreur('0235'); // erreur sur vue
			}
		} else {
			$ret = new SG_Erreur('0093', $codeBase); // erreur sur base
		}
		return $ret;
	}

	/** 
	 * getDocumentsParChamp : chercher le premier donné d'un modèle donné (et d'un code éventuellement)
	 * @since 2.3 ajout
	 * @param string $pTypeObjet : le type d'objet à retrouver
	 * @param string $pChamp : le champ de l'objet à tester
	 * @param string $pValeur : la valeur à trouver
	 * @return SG_Collection des documents trouvés ou erreur
	 */
	function getDocumentsParChamp($pTypeObjet = '', $pChamp = '',$pValeur = '') {
		$base = SG_Dictionnaire::getCodeBase($pTypeObjet);
		$acces = 'couchdb'; // todo tester le type de base de données
		$ret = new SG_Collection();
		if ($acces === 'couchdb') {
			// création de la vue
			$jsSelection = self::javascript('4', $pTypeObjet, $pChamp);
			$vue = new SG_Vue('', $base, $jsSelection, false);
			if ($vue -> creerVue() === true) {
				// recherche des documents
				$ret = $vue -> Contenu($pValeur, '', true);
				if (! is_array($ret -> elements)) {
					$ret = new SG_Erreur('0189', $pTypeObjet . '.' . $pChamp);
				}
			} else {
				$ret = new SG_Erreur('0188', $pTypeObjet . '.' . $pChamp);
			}
		} else {
			$ret = new SG_Erreur('0187', $base);
		}
		return $ret;
	}

	/** 2.4 ajout
	 * retourne tous les ids d'un type de document ou ceux qui commmencent par un prefixe
	 * @param string $pNomBaseOuModele modèle de document
	 * @param string $pPrefixe
	 * @param boolean $pBase force le recalcul de la vue
	 * @return SG_Collection|SG_Erreur tableau de textes contenant les id's des documents
	 **/
	function getAllIDs($pNomBaseOuModele = '', $pPrefixe = '', $pBase = false) {
		$ret = new SG_Collection();
		if ($pNomBaseOuModele === '') {
			$ret = new SG_Erreur('0262');
		} else {
			if ($pBase === false) {
				$codeBase = SG_Dictionnaire::getCodeBase($pNomBaseOuModele);
				$nomVue = 'all_' . $pNomBaseOuModele . '_ids';
				$jsSelection = self::javascript('3', $pNomBaseOuModele);
				$vue = new SG_Vue($codeBase . '/' . strtolower($nomVue),$codeBase,$jsSelection, false);
				if ($vue -> creerVue() === true) {
					$ret -> elements = $vue -> vue -> contenuBrut($pPrefixe, false, false);
				} else {
					$ret = new SG_Collection();
				}
			} else {
				$codeBaseComplet = SG_Config::getCodeBaseComplet($pNomBaseOuModele);
				$url = $_SESSION['@SynerGaia'] -> sgbd -> url . $codeBaseComplet . '/_all_docs';
				if ($pPrefixe !== '') {
					$url.= '?startkey="' . $pPrefixe . '"&endkey="' . $pPrefixe . 'ZZZZZZZZZZZZZZZZZZZ"';
				}
				$res = $_SESSION['@SynerGaia'] -> sgbd -> requete($url, "GET");
				try {
					ini_set('memory_limit', '512M');
					$res = json_decode($res, true);
					$ret -> elements = $res['rows'];
					$res = '';
					ini_restore('memory_limit');
				} catch (Exception $e) {
					$ret = new SG_Erreur('0119', $e -> getMessage());
				}
			}
		}
		return $ret;
	}

	/**
	 * Crée un objet SynerGaïa de type @Document à partir du tableau des propriétés
	 * @since 2.4 ajout
	 * @param array $doc liste des propriétés telles que récupérées de CouchDB
	 * @return SG_Document
	 **/
	static function creerObjet($doc) {
		$ret = null;
		if (isset($doc['@Type'])) {
			$classe = $doc['@Type'];
			if (!class_exists($classe)) {
				$classe = SG_Dictionnaire::getClasseObjet($doc['@Type']);
			}
			if ($classe === '' or $classe === null) {
				$ret = new SG_Erreur('0196',$doc['@Type']);
			} elseif (!class_exists($classe)) {
				$ret = new SG_Erreur('0197', $classe);
			} else {
				$ret = new $classe('', $doc);
				$base = SG_Dictionnaire::getCodeBase($doc['@Type']);
				$ret -> doc = new SG_DocumentCouchDB ($base, '', $doc);
				if (method_exists($ret, 'initDocument')) {
					$ret -> initDocument();
				}
			}
		}
		return $ret;
	}

	/**
	* Retourne une collection d'objets de type @Document de code à code (accès CouchDB seul)
	* @since 2.4 ajout
	* @param string : type d'objet
	* @param string : code du premier objet
	* @param string : code de l'objet de fin
	* @return @Collection des objets (éventuellement vide) ou erreur si paramètres inconséquents
	**/
	static function getCollectionObjetsParCode ($pTypeObjet = '', $pCodeDebut = '', $pCodeFin = '') {
		$type = SG_Texte::getTexte($pTypeObjet);
		$debut = SG_Texte::getTexte($pCodeDebut);
		$fin = SG_Texte::getTexte($pCodeFin);
		$base = SG_Dictionnaire::getCodeBase($type);
		$appli = SG_Config::getCodeAppli();
		// constitution du javascript de sélection
		$jsSelection = self::javascript('1', $type);
		$vue = new SG_Vue('', $base, $jsSelection, false);
		if ($vue -> creerVue() === true) {
			$ret = $vue -> vue -> getCollection($debut, $fin, true);
			if (is_array($ret) and sizeof($ret) > 0) {
				if (sizeof($ret) === 1) {
					if(isset($ret[0]['doc'])) {
						$ret = self::creerObjet($ret[0]['doc']);
					} else {
						$ret = new SG_Erreur('0243'); // pas un doc
					}
				} else {
					$new = new SG_Collection();
					foreach ($ret as $elt) {
						if(isset($elt['doc'])) {
							$doc = self::creerObjet($elt['doc']);
							$new -> elements[] = $doc;
						} else {
							$new -> elements[] = new SG_Erreur('0242'); // pas un doc
						}
					}
					$ret = $new;
				}
			} else {
				$ret = new SG_Erreur('0244',$type); // pas trouvé
			}
		} else {
			$ret = new SG_Erreur('0245'); // erreur sur vue
		}
		return $ret;
	}

	/** 2.4 ajout
	* Retourne une collection d'objets de type @Document pour un champ d'une valeur de départ à une valeur de fin (accès CouchDB seul)
	* @param (SG_Texte ou string) $pTypeObjet : type d'objet
	* @param (SG_Texte ou string) $pChamp : champ
	* @param (SG_Texte ou string) $pCodeDebut : valeur du premier objet
	* @param (SG_Texte ou string) $pCodeFin : valeur de l'objet de fin
	* @return @Collection des objets (éventuellement vide) ou erreur si paramètres inconséquents
	**/
	static function getCollectionObjetsParChamp ($pTypeObjet = '', $pChamp = '', $pCodeDebut = '', $pCodeFin = '') {
		$type = SG_Texte::getTexte($pTypeObjet);
		$champ = SG_Texte::getTexte($pChamp);
		$debut = SG_Texte::getTexte($pCodeDebut);
		$fin = SG_Texte::getTexte($pCodeFin);
		$base = SG_Dictionnaire::getCodeBase($type);
		$appli = SG_Config::getCodeAppli();
		// constitution du javascript de sélection (recherche sur valeur de champ)
		$jsSelection = self::javascript('2', $type, $champ);
		$vue = new SG_Vue('', $base, $jsSelection, false);
		if ($vue -> creerVue() === true) {
			$ret = $vue -> vue -> getCollection($debut, $fin, true);
			if (is_array($ret) and sizeof($ret) > 0) {
				if (sizeof($ret) === 1) {
					if(isset($ret[0]['doc'])) {
						$ret = self::creerObjet($ret[0]['doc']);
					} else {
						$ret = new SG_Erreur('0247'); // pas un doc
					}
				} else {
					$new = new SG_Collection();
					foreach ($ret as $elt) {
						if(isset($elt['doc'])) {
							$doc = self::creerObjet($elt['doc']);
							$new -> elements[] = $doc;
						} else {
							$new -> elements[] = new SG_Erreur('0246'); // pas un doc
						}
					}
					$ret = $new;
				}
			} else {
				$ret = new SG_Erreur('0248',$type); // pas trouvé
			}
		} else {
			$ret = new SG_Erreur('0265'); // erreur sur vue
		}
		return $ret;
	}
	/** 2.4 ajout
	* Retourne une collection d'objets de type @Document selon le MD5 du PREMIER fichier
	* @param (SG_Texte ou string) $pTypeObjet : type d'objet
	* @param (SG_Texte ou string) $pMD5 : md5 du premier fichier
	* @return @Collection des objets (éventuellement vide) ou erreur si paramètres inconséquents
	**/
	static function getObjetsParMD5 ($pTypeObjet = '', $pMD5 = '') {
		$type = SG_Texte::getTexte($pTypeObjet);
		$base = SG_Dictionnaire::getCodeBase($type);
		$debut = SG_Texte::getTexte($pMD5);
		// constitution du javascript de sélection (recherche sur valeur de champ)
		$jsSelection = self::javascript('5', $type);
		$vue = new SG_Vue('', $base, $jsSelection, false);
		$ret = $vue -> Contenu($debut, '', true);
		if (getTypeSG($ret) === '@Collection') {
			if (sizeof($ret -> elements) === 1) {
				$ret = $ret -> elements[0];
			}
		} else {
			$ret = new SG_Erreur('0266',$type . ' ' . $debut); // pas trouvé
		}
		return $ret;
	}

	/** 2.4 ajout
	 * Crée le code javascript pour diverses vues
	 * @param string : code de phrase ('1' à  '12')
	 * @param $pTypeObjet : type d'objet sélecté ou ''
	 * @param $pChamp : nom du champ testé
	 * @return : phrase de javascript ou tableau map et reduce
	 * @todo '12' : cas des événements qui chevauchent les bornes (test date fin, test mois dans la période)
	 */
	static function javascript($pCodeJS, $pTypeObjet = '', $pChamp = '') {
		if ($pCodeJS === '1') {
			// recherche sur le code
			if($pTypeObjet === '@Utilisateur') {
				$ret = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') { emit(doc['@Identifiant'],doc['_id'])} }";
			} else {
				$ret = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') 
					{ var code='';if (doc['Code'] != null) { code = doc['Code'];} else if (doc['@Code'] != null) {code = doc['@Code']; }; emit(code,doc['_id'])} }";
			}
		} elseif ($pCodeJS === '2') {
			// recherche sur un champ texte (clé est vide si champ absent)
			$ret = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') 
				{var champ='';if (doc['" . $pChamp . "'] != null) { champ = doc['" . $pChamp . "'];";
			if (substr($pChamp, 0, 1) !== '@') {
				$ret.= "} else if (doc['@" . $pChamp . "'] != null) {champ = doc['@" . $pChamp . "'];";
			}
			$ret.= "}; emit(champ,doc['_id'])} }";
		} elseif ($pCodeJS === '3') {
			// recherche sur un type d'objet
			$ret = "function(doc) {if (doc['@Type']==='" . $pTypeObjet . "'){emit(doc['_id']);}}";
		} elseif ($pCodeJS === '4') {
			// recherche sur un champ avec retour de l'id du document (clé = '' si champ absent)
			$ret = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') 
				{ var v='';if (doc['" . $pChamp . "'] != null) { v = doc['" . $pChamp . "'];}; emit(v, doc['_id'])} }";
		} elseif ($pCodeJS === '5') {
			// recherche sur un MD5 du premier fichier avec retour de l'id du document (clé = '' si champ absent)
			$ret = "function(doc){if(doc['@Type']==='" . $pTypeObjet . "'&&doc['@MD5']!=null&&doc['@MD5']!=''){emit(doc['@MD5'],doc['_id']);}}";
		} elseif ($pCodeJS === '6') {
			// modele d'une propriété dans le dictionnaire
			$ret = "function(doc){if(doc['@Type']==='@DictionnairePropriete'){
				var objet=doc['@Code'];idx=objet.indexOf('.');if(idx !=-1) {objet=objet.substring(0,idx);} ;
				emit(objet,{ 'nom':doc['@Propriete'],'idmodele':doc['@Modele']}); } }";
		} elseif ($pCodeJS === '7') {
			// id => titre
			$ret = "function(doc){if(doc['@Type']){var titre='';if(doc['Titre'] != null){titre = doc['Titre'];}
				else if(doc['@Titre'] != null){titre = doc['@Titre']; }; emit(doc['_id'],titre)}";
		} elseif ($pCodeJS === '8') {
			// id => code
			$ret = "function(doc){if(doc['@Type']!=''){var code='';if(doc['Code'] != null){code = doc['Code'];}
				else if(doc['@Code'] != null){code = doc['@Code']; }; emit(doc['_id'],code)}";
		} elseif ($pCodeJS === '9') {
			// id => categorie (map et reduce)
			$jsMap = "function(doc){if(doc['@Type']==='" . $pTypeObjet . "'){var c=doc['" . $pChamp . "']; if(c!=null){ if(Array.isArray(c)) {
				for(var i=0;i<c.length;i++){emit(c[i],1)}} else {emit(c,1);}}}}";
			$jsReduce = "function(keys,values,rereduce) {return 1}";
			$ret = array('all' => array('map' => $jsMap), 'categorie' => array('map' => $jsMap, 'reduce' => $jsReduce));
		} elseif ($pCodeJS === '10') {
			// objet => 'nom': méthode, 'idmodele' : modele
			$ret = "function(doc){if(doc['@Type']==='@DictionnaireMethode'){";
			$ret.= "var objet=doc['@Code'];idx=objet.indexOf('.');if(idx !=-1) {objet=objet.substring(0,idx);} ;";
			$ret.= "emit(objet,{ 'nom':doc['@Methode'], 'idmodele':doc['@Modele']}); } }";
		} elseif ($pCodeJS === '11') {
			// champ => 'champ': nom, 'id', iddoc, 'cle' : valeur
			$ret = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') {var champ = '';";
			foreach ($pChamp as $lchamp => $modele) {
				$ret.= "champ = doc['" . $lchamp . "'];if (champ != null) {if (toString.call(champ) === '[object Array]') {";
				$ret.= "var len = champ.length;for (var i = 0; i < len; i++) {emit( champ[i], {'champ':'" . $lchamp . "', 'id':doc['_id'], 'cle':champ[i]});}";
				$ret.= "} else {emit( champ, {'champ':'" . $lchamp . "', 'id':doc['_id'], 'cle':champ});}}";
			}
			$ret.= "}}";
		} elseif ($pCodeJS === '12') {
			// pour @Calendrier->get3Mois() : 3 mois sur date début
			// champ => 'champ': nom, 'val' : valeur
			$champ = "doc['" . $pChamp['nom'] . 
			$ret = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') {val=doc['" . $pChamp['nom'] . "'];if(val!= null && val != ''){";
			$ret.= "emit(val.substr(6,4) + val.substr(3,2), doc)}}}";
		} else {
			$ret = new SG_Erreur('0238', $pCodeJS);
		}
		return $ret;
	}
	/** 2.4 ajout
	* trouve le champ titre d'un document sans apport du document
	* @param $pUID (base + id)
	**/
	function getTitre($pUID = '') {
		$ret = '';
		$i = strpos($pUID, '/');
		if ($i === false) {
			$ret = new SG_Erreur('0257', $pUID);
		} else {
			$iddoc = explode('/', $pUID);
			$codeBase = $iddoc[0];
			$id = $iddoc[1];
			$vue = new SG_Vue('', $codeBase, self::javascript('7'), true);
			$ret = $vue -> ChercherValeur($id);
		}	
		return $ret;
	}

	/** 2.4 ajout
	 * trouve le champ code d'un document sans apport du document
	 * @param string $pUID (base + id)
	 */
	function getCode($pUID) {
		$ret = '';
		$i = strpos($pUID, '/');
		if ($i === false) {
			$ret = new SG_Erreur('0258', $pUID);
		} else {
			$iddoc = explode('/', $pUID);
			$codeBase = $iddoc[0];
			$id = $iddoc[1];
			$vue = new SG_Vue('', $codeBase, self::javascript('8'), true);
			$ret = $vue -> ChercherValeur($id);
		}	
		return $ret;
	}

	/**
	 * Récupère 3 mois d'événements dans un calendrier
	 * 
	 * @since 2.6
	 * @param string $pTypeObjet Type d'objet à récupérer
	 * @param string $pChamp nom du champ Debut
	 * @param array $pMois tableau des 3 valeurs de mois
	 * @return array de SG_Document
	 */
	function get3Mois($pTypeObjet, $pChamp, $pMois) {
		$ret = array();
		$base = SG_Dictionnaire::getCodeBase($pTypeObjet);
		$js = self::javascript('12', $pTypeObjet, array('nom' => $pChamp, 'val' => $pMois));
		$vue = new SG_Vue('', $base, $js, true);
		// on calcule les dates limite (aaaamm)
			
		$an = intval(substr($pMois, 0, 4));
		$mois = intval(substr($pMois, 4));
		if ($mois === 1) {
			$debut = ''.strval($an - 1).'12';
			$fin = ''.strval($an).'02';
		} elseif ($mois === 12) {
			$debut = ''.strval($an).'11';
			$fin = ''.strval($an + 1).'01';
		} else {
			$debut = ''.strval($an).substr('0'.strval($mois - 1),-2);
			$fin = ''.strval($an).substr('0',strval($mois + 1),-2);
		}
		// on cherche les documents entre les dates
		if ($vue -> creerVue() === true) {
			$ret = $vue -> vue -> getCollection($debut, $fin, true);
		}
		return $ret;
	}
}
?>
