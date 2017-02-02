<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de gestion d'une base de données CouchDB
*/
class SG_CouchDB extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@CouchDB';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Url de préfixe des requetes au serveur
	public $url;

	/** 2.3 port 5984 par défaut
	* Construction de l'objet
	* @level 0
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
	* @param string $pURL url de la requete
	* @param string $pMethode méthode HTTP (GET, POST, PUT, DELETE)
	* @param string $pContenu contenu complémentaire de la requete
	* @param string $pContentType Content-Type de la requete
	*
	* @return string résultat de la requete
	* @level 0
	*/
	public function requete($pURL = '', $pMethode = 'GET', $pContenu = '', $pContentType = 'application/x-www-form-urlencoded') {
		$options = array('http' => array('method' => $pMethode, 'header' => "Accept: */*;\r\n" . "Content-Type: " . $pContentType . "\r\n" . "Content-Length: " . strlen($pContenu) . "\r\n", 'content' => $pContenu));
		$contexte = stream_context_create($options);
		$reponse = @file_get_contents($pURL, false, $contexte);
		return $reponse;
	}
	/** 1.0.7 (jamais appelée ??)
	* initDBDocument : crée ou recherche le document CouchDB
	* @param string $pCodeBase code de la base couchDB sans le prefixe
	* @param string $pRefenceDocument référence du document
	* @param string $pJson si on fourni directement du JSON on le construit à partir de là
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
	*
	* @param string $pCodeBase code de la base à tester
	*
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
	*
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
	* @return string UUID
	*/
	public function getUUID() {
		$ret = '';
		$url = $this -> url . '_uuids';
		$resultat = $this -> requete($url, 'GET');
		if (strlen($resultat) !== 0) {
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
	*
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

	/** 2.1 caché en $_SESSION
	* Détermine si une base existe
	*
	* @param string $pNomBase nom de la base demandée
	* @return SG_VraiFaux base existe
	*/
	public function BaseExiste($pNomBase = '') {
		$ret = false;
		if ($pNomBase !== '') {
			$codeAppli = SG_Config::getConfig('CouchDB_prefix', '');
			if(!isset($_SESSION[$codeAppli]['BE'][$pNomBase])) {
				$url = $this -> url . $pNomBase;
				$resultat = $this -> requete($url, 'GET');
				if (strlen($resultat) !== 0) {
					$infos = json_decode($resultat);
					$db_name = $infos -> db_name;
					if (is_null($db_name)) {
						$db_name = '';
					}
					$ret = ($db_name === $pNomBase);
				}
				$_SESSION[$codeAppli]['BE'][$pNomBase] = $ret;
			}
			$ret = $_SESSION[$codeAppli]['BE'][$pNomBase]; //new SG_VraiFaux($retBool);
		}
		return $ret;
	}

	/** 2.1 baseExiste
	* Ajoute une base au serveur CouchDB
	*
	* @param string $pNomBase nom de la base à créer
	* @return SG_VraiFaux
	*/
	public function AjouterBase($pNomBase) {
		$retBool = false;
		if ($pNomBase !== '') {
			if (!$this -> BaseExiste($pNomBase)) {
				$url = $this -> url . $pNomBase;
				$resultat = $this -> requete($url, 'PUT');
				if (strlen($resultat) !== 0) {
					$infos = json_decode($resultat);
					$retBool = ($infos -> ok = true);
				}
			}
		}
		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}

	/** 2.1 baseExiste
	* Supprime une base du serveur CouchDB
	*
	* @param string $pNomBase nom de la base à supprimer
	* @return SG_VraiFaux
	*/
	public function SupprimerBase($pNomBase = '') {
		$retBool = false;
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
				}
			}
		}
		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}

	/** 1.1 utilise SG_Texte ; 2.1 plus de cache
	* Normalise le nom d'une base CouchDB (espaces, accents, ...)
	*
	* @param string $pNomBase nom de la base
	* @return string nom de la base normalisé
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
	
	/** 1.0.7 ; 1.3.1 ajout @Titre
	* Vue d'accès à tous les objets d'un modèle donné
	* 
	* @param string modèle à explorer
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
	/** 1.0.6 ; 1.3.4 traite domino ; 2.0 cas particulier @Utilisateur ; 2.1 $codeBase caché en $_SESSION ; 2.3 init code=doc['_id']
	* getChercherDocuments : chercher les documents d'un modèle donné (et d'un code éventuellement)
	* 
	* @param string : base à explorer
	* @param $pTypeObjet : le type d'objet à retrouver
	* @param $pCodeObjet : le code de l'objet si on le connait
	* @param $pFiltre : formule de filtrage sur la colection des documents récupérés
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
			if($pTypeObjet === '@Utilisateur') {
				$jsSelection = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') { emit(doc['@Identifiant'],doc['_id'])} }";
			} else {
				$jsSelection = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') 
				{ var code=doc['_id'];if (doc['Code'] != null) { code = doc['Code'];} else if (doc['@Code'] != null) {code = doc['@Code']; }; emit(code,doc['_id'])} }";
			}
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
	/** 1.0.7 ; 2.1 stockage dans $_SESSION
	* Retourne le tableau des propriétés d'un objet, éventuellement d'un type précis donné
	* 
	* @param string $pCodeObjet code de l'objet à analyser
	* @param string $pModele est un filtre supplémentaire éventuel
	* @return @Collection dont le tableau est composé d'array ('nom' : propriété, 'modele' : modele de la propriété)
	*/
	function getProprietesObjet ($pCodeObjet = '', $pModele = '', $pForce = false) {
		$ret = new SG_Collection();
		if ($pCodeObjet !== '') {
			$codeAppli = SG_Config::getCodeAppli();
			if ($pForce or ! isset($_SESSION[$codeAppli]['PO'][$pCodeObjet])) {
				$jsSelection = "function(doc){if(doc['@Type']==='@DictionnairePropriete'){";
				$jsSelection .= "var objet=doc['@Code'];idx=objet.indexOf('.');if(idx !=-1) {objet=objet.substring(0,idx);} ;";
				$jsSelection .= "emit(objet,{ 'nom':doc['@Propriete'],'idmodele':doc['@Modele']}); } }";
				$vue = new SG_Vue(SG_Dictionnaire::CODEBASE . '/vue_proprietesobjet', SG_Dictionnaire::CODEBASE, $jsSelection, true);
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
	/** 1.2
	* Retourne le tableau des méthodes d'un objet, éventuellement d'un type précis donné
	* 
	* @param string $pCodeObjet code de l'objet à analyser
	* @param string $pModele est un filtre supplémentaire éventuel
	* @return @Collection dont le tableau est composé d'array ('nom' : méthode, 'modele' : modele de la méthode)
	*/
	function getMethodesObjet ($pCodeObjet = '', $pModele = '') {
		$ret = new SG_Collection();
		if ($pCodeObjet !== '') {
			$jsSelection = "function(doc){if(doc['@Type']==='" . '@DictionnaireMethode' . "'){";
			$jsSelection .= "var objet=doc['@Code'];idx=objet.indexOf('.');if(idx !=-1) {objet=objet.substring(0,idx);} ;";
			$jsSelection .= "emit(objet,{ 'nom':doc['@Methode'], 'idmodele':doc['@Modele']}); } }";
			$vue = new SG_Vue(SG_Dictionnaire::CODEBASE . '/vue_methodesobjet', SG_Dictionnaire::CODEBASE, $jsSelection, true);
			// oncherche à partir de l'id (à partir 1.0.6)
			$collec = $vue -> ChercherValeurs($pCodeObjet);
//			$objet = SG_Dictionnaire::getDictionnaireObjet($pCodeObjet);
// 2.1			if(is_object($objet)) {
//				$collec -> Concatener ($vue -> ChercherValeurs($objet -> doc -> codeDocument));
//			}
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
	/** 1.3.2 test $result
	* 1.1 retourne toujours une collection d'objets ; @Comme = propriété locale ; 1.3.0 test @utilisateur
	* Index inversé des objets liés
	* 
	* @param any $pDocument SG_Document ou dérivé : id document à rechercher
	* @param any $pModele modèle à indexer
	* @param any $pChamp limiter la recherche à un nom de champ du  modèle 
	* @return SG_Collection des documents
	*/
	function getObjetsLies($pDocument, $pModele, $pChamp = '') {
		// Base dans laquelle aller chercher (celle du modele)
		$codeBase = SG_Dictionnaire::getCodeBase($pModele);
		$champ = SG_Texte::getTexte($pChamp);
		$typeObjet = getTypeSG($pDocument);
		$listeChamps = SG_Dictionnaire::getListeChamps($pModele, $typeObjet);
		$jsSelection = "function(doc) { if (doc['@Type']==='" . $pModele . "') {var champ = '';";
		foreach ($listeChamps as $lchamp => $modele) {
			$jsSelection .= "champ = doc['" . $lchamp . "'];if (champ != null) {if (toString.call(champ) === '[object Array]') {";
			$jsSelection .= "var len = champ.length;for (var i = 0; i < len; i++) {emit( champ[i], {'champ':'" . $lchamp . "', 'id':doc['_id'], 'cle':champ[i]});}";
			$jsSelection .= "} else {emit( champ, {'champ':'" . $lchamp . "', 'id':doc['_id'], 'cle':champ});}}";
		}
		$jsSelection .= "} }";
		if(getTYpeSG($pDocument) === '@Utilisateur') {
			$cle = SG_Annuaire::CODEBASE . '/' . $pDocument -> identifiant;
		} else {
			$cle = $pDocument -> getUUID();
		}
		$vue = new SG_Vue('', $codeBase, $jsSelection, true);
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
	
	/** 1.0.7
	* Détermine le document à partir de son @Type et @Idenfitiant
	*
	* @param string $pType type SynerGaïa du document cherché
	* @param string $pChamp nom du champ où se trouve la clé
	* @param string $pIdentifiant valeur de la propriété @Identifiant du document cherché
	*
	* @return any SG_Collection
	*/
	static function getDocumentsFromTypeChamp($pType = '', $pChampCle = '', $pValeur = '') {
		$codeBase = SG_Dictionnaire::getCodeBase($pType);
		$jsSelection = "function(doc){if (doc['@Type']==='" . $pType . "') {emit(doc['" . $pChampCle . "'],doc['_id']);}}";
		$vue = new SG_Vue('', $codeBase, $jsSelection, true);
		return $vue -> ChercherElements($pValeur);
	}
	
	/**
	* chercherFlou : chercher des documents d'un certain modèle et dont certains champs contiennent le texte passé en paramètre
	* 
	* @param any $pTexte : texte à rechercher
	* @param any $pModeles : modèle ou tableau de modèles dans lesquels chercher
	* @param any $pChamps : nom de champ ou champs ou tableau des champs qu'il faut analyser dans ces modèles
	* 
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
		
		return $ret;
	}
	/** 1.1 ajout ; 2.3 sup $prefixe, parm id
	*/
	function RepliquerAvec($pAdresse = '', $pID = '',$pPSW = '',$pContinue = false, $pCreate = false) {
		// préparer les paramètres
		$adresse = new SG_Texte($pAdresse);
		$adresse = $adresse -> texte;
		$id = SG_Texte::getTexte($pID);
		$psw = SG_Texte::getTexte($pPSW);
		$bases = self::ListerBases();
		$continue = new SG_VraiFaux($pContinue);
		if ($continue -> estVrai() === true) {
			$continue = ',"continuous":true';
		} else {
			$continue = '';
		}
		$create = new SG_VraiFaux($pCreate);
		if ($create -> estVrai() === true) {
			$create = ',"create_target":true';
		} else {
			$create = '';
		}
		// traiter la réplication
		$ret = new SG_Collection();
		$bases = SG_SynerGaia::Bases();
		foreach ($bases -> elements as $base) {
			$nom = $base -> base -> codeBaseComplet;
			if ($nom !== '') {
				$requete = '{"source":"' . $nom . '", "target":"http://' . $id . ':' . $psw . '@' . $adresse . ':5984/' . $nom . '"' . $continue . $create . '}';
				$ret = $this -> requete($this -> url, 'POST', $requete);
			}
		}
		return $ret;
	}
	/** 1.1 ajout
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
	/** 1.2 ajout
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
	/** 1.2 ajout
	* @param $pCle : préfixe de sélection
	* @return tableau des objets, propriétés et méthodes (indexes)
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
	// 1.2 ajout
	function version() {
		$ret = $this -> requete($this -> url);
		return $ret;
	}
	/** 2.1 ajout ; 2.3 test $classe
	* obtenir un objet SynerGaia par son id
	**/
	function getObjetByID($pID = '') {
		$ret = null;
		$i = strpos($pID, '/');
		if ($i === false) {
			$ret = new SG_Erreur('id inconnu ' . $pID);
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
				$doc = @file_get_contents($url, false);
				$doc = json_decode($doc, true);
				if (isset($iddoc[2]) and $iddoc[2] === '_attachments') {
					$nom = 'nom inconnu';
					if (isset($doc['_attachments'])) {
						$nom = key($doc['_attachments']);
						$url.= '/' . $nom;
					}
					$fic = @file_get_contents($url, false);
					$ret = array();
					$ret['data'] = $fic;
					$ret['nom'] = $nom;
					$ret['type'] = $doc['_attachments'][$nom]['content_type'];
				} elseif (isset($doc['@Type'])) {
					$classe = $doc['@Type'];
					if (!class_exists($classe)) {
						$classe = SG_Dictionnaire::getClasseObjet($doc['@Type']);
					}
					if ($classe === '' or $classe === null) {
						$ret = new SG_Erreur('0196',$doc['@Type']);
					} elseif (!class_exists($classe)) {
						$ret = new SG_Erreur('0197', $classe);
					} else {
						$ret = new $classe();
						if (property_exists($ret, 'doc')) {
							$docdb = $ret -> doc;
						} else {
							$docdb = $ret; // répertoires
						}
						$docdb -> codeBase = $codeBase;
						$docdb -> codeBaseComplet = $codeBaseComplet;
						$docdb -> codeDocument = $doc['_id'];
						$docdb -> revision = $doc['_rev'];
						$docdb -> proprietes = $doc;
						if (method_exists($ret, 'initDocument')) {
							$ret -> initDocument();
						}
					}
				}
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* getObjetParCode : chercher le premier donné d'un modèle donné (et d'un code éventuellement)
	* 
	* @param string : base à explorer
	* @param $pTypeObjet : le type d'objet à retrouver
	* @param $pCodeObjet : le code de l'objet si on le connait
	* @param $pFiltre : formule de filtrage sur la colection des documents récupérés
	* @return SG_Collection des documents
	*/
	function getObjetParCode($pCodeBase = '', $pTypeObjet = '', $pCodeObjet = '') {
		$codeAppli = SG_Config::getCodeAppli();
		if (!isset($_SESSION[$codeAppli]['gCD'][$pCodeBase])) {
			$_SESSION[$codeAppli]['gCD'][$pCodeBase] = new SG_DictionnaireBase($pCodeBase);
		}
		$base = $_SESSION[$codeAppli]['gCD'][$pCodeBase];
		$acces = $base -> getValeur('@Acces', 'couchdb');
		if ($acces === 'couchdb') {
			if($pTypeObjet === '@Utilisateur') {
				$jsSelection = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') { emit(doc['@Identifiant'],doc['_id'])} }";
			} else {
				$jsSelection = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') 
				{ var code='';if (doc['Code'] != null) { code = doc['Code'];} else if (doc['@Code'] != null) {code = doc['@Code']; }; emit(code,doc['_id'])} }";
			}
			$vue = new SG_Vue('', $pCodeBase, $jsSelection, false);
			if ($vue -> creerVue() === true) {
				$ret = $vue -> vue -> contenuBrut($pCodeObjet, true);
				if (is_array($ret)) {
					if (sizeof($ret) === 1) {
						if(isset($ret[0]['doc'])) {
							$ret = $ret[0]['doc'];
						} else {
							$ret = new SG_Erreur('Pas de doc'); // TODO n°
						}
					} else {					
						$ret = new SG_Erreur('Plusieurs objets');
					}
				} else {
					$ret = new SG_Erreur('Pas trouvé');
				}
			} else {
				$ret = new SG_Erreur('Vue non créée');
			}
		} else {
			$ret = new SG_Erreur('0093', $pCodeBase);
		}
		return $ret;
	}
	/** 2.3 ajout
	* getDocumentsParChamp : chercher le premier donné d'un modèle donné (et d'un code éventuellement)
	* 
	* @param $pTypeObjet string : le type d'objet à retrouver
	* @param $pChamp string : le champ de l'objet à tester
	* @param $pValeur string : la valeur à trouver
	* @return SG_Collection des documents trouvés ou erreur
	*/
	function getDocumentsParChamp($pTypeObjet = '', $pChamp = '',$pValeur = '') {
		$base = SG_Dictionnaire::getCodeBase($pTypeObjet);
		/*$codeAppli = SG_Config::getCodeAppli();
		if (!isset($_SESSION[$codeAppli]['gCD'][$base])) {
			$_SESSION[$codeAppli]['gCD'][$base] = new SG_DictionnaireBase($base);
		}
		$base = $_SESSION[$codeAppli]['gCD'][$base];
		$acces = $base -> getValeur('@Acces', 'couchdb');*/
		$acces = 'couchdb'; // todo tester le type de base de données
		$ret = new SG_Collection();
		if ($acces === 'couchdb') {
			// création de la vue
			$jsSelection = "function(doc) { if (doc['@Type']==='" . $pTypeObjet . "') 
				{ var v='';if (doc['" . $pChamp . "'] != null) { v = doc['" . $pChamp . "'];}; emit(v, doc['_id'])} }";
			$vue = new SG_Vue('', $base, $jsSelection, false);
			if ($vue -> creerVue() === true) {
				// recherche des documents
				$ret -> elements = $vue -> vue -> contenuBrut($pValeur, true);
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
}
?>
