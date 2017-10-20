<?php
/** fichier contenant la gestion et le pilotage du logiciel SynerGaïa */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

// Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_SynerGaia_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_SynerGaia_trait.php';
} else {
	/** par défaut trait vide */
	trait SG_SynerGaia_trait{};
}

/** 
 * SG_SynerGaia : Classe décrivant l'application et gérant les objets et paramètres permaments
 * @since 0.0
 * @version 2.6 : réintégration des classes SG_Installation et SG_Update qui disparaissent
 * @todo gérer le multilingue
 */
class SG_SynerGaia extends SG_Objet {
	/** string Type SynerGaia '@SynerGaia' */
	const TYPESG = '@SynerGaia';

	/** string version du logiciel SynerGaïa */
	const VERSION = '2.7';
	/** integer no version du logiciel SynerGaïa */
	const NOVERSION = 2700;
	
	/** string Fichier du contenu du dictionnaire par défaut */
	const DICTIONNAIRE_REFERENCE_FICHIER = 'ressources/dictionnaire.json';

	/** string 1.1 Fichier du contenu du dictionnaire par défaut */
	const LIBELLES_REFERENCE_FICHIER = 'ressources/libelles.json';

	/** string 1.1 Fichier du contenu du dictionnaire par défaut */
	const VILLES_REFERENCE_FICHIER = 'ressources/villes_fr.csv';

	/** string Clé de config de la version du dictionnaire */
	const CLE_CONFIG_HASH_DICTIONNAIRE = 'HashDictionnaireDernierImport';

	/** string 1.1 Clé de config de la version du dictionnaire */
	const CLE_CONFIG_HASH_LIBELLES = 'HashLibellesDernierImport';

	/** string 1.1 Clé de config de la version des villes */
	const CLE_CONFIG_HASH_VILLES = 'HashVillesDernierImport';
	
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** SG_CouchDB canal vers la base de données de SynerGaïa */
	public $sgbd;

	/** string serveur domino accessible
	 * @since 1.1 */
	public $domino;
	
	/** SG_Utilisateur utilisateur en cours de session */
	public $moi;
	
	/** SG_Compilateur compilateur pour les formules 
	 * @since 2.1 */
	public $compilateur;
	
	/** array tableau des messages d'erreur déjà lus (cache) 
	 * @since 2.1 */
	public $libelles = array();
	
	/** SG_Navigation navigateur 
	 * @since 2.3 */
	private $navigation;

	/**
	 * Initiailse l'objet SynerGaia
	 * ouvre le canal vers la base de données (par défaut un SG_CouchDB
	 * !! doit pouvoir s'exécuter avant l'initialisation du cache !! (voir core/ressources.php)
	 */
	public function __construct() {
		$this -> sgbd = new SG_CouchDB();
		$this -> navigation = new SG_Navigation();
	}

	/**
	 * Determine l'identifiant de la personne connectée
	 * @since 1.0.7
	 * @version 1.3.2 static
	 * @return string code identifiant
	 */
	static function IdentifiantConnexion() {
		$ret = '';
		if (isset($_SESSION['@Moi']) and getTypeSG($_SESSION['@Moi']) === '@Utilisateur') {
			$ret = $_SESSION['@Moi'] -> identifiant;
		}
		return $ret;
	}

	/**
	 * Determine la version de SynerGaïa exécutée
	 * @since 1.1
	 * @version2.2 php.ini
	 * @param boolean|SG_VraiFaux $pTout Si @Vrai, afficher le détail des version de tous les modules utilisés. Par défaut @Faux. 
	 * @return string code de version
	 */
	function Version($pTout = false) {
		$ret = self::VERSION;
		$tout = new SG_VraiFaux($pTout);
		if ($tout -> estVrai()) {
			$ret = '<ul><li>SynerGaïa : ' . self::VERSION . '</li><li>PHP : ' . phpversion() . '</li>';
			$ret.= '<li>php.ini chargé : ' . php_ini_loaded_file() . '</li>';
			$versionsgbd = json_decode($this -> sgbd -> version());
			$ret.= '<li>CouchDB : ' . $versionsgbd -> version . '</li>';
			$modules = get_loaded_extensions();
			natcasesort($modules);
			$ret.= '<li>' . implode('</li><li>', $modules) . '</li>';
			$ret.='</ul>';
		}
		return $ret;
	}

	/**
	 * Vide le cache SynerGaïa ou une série de cache
	 * @since 1.1
	 * @version 2.1 retour collection si '?'
	 * @param string|SG_Texte|SG_Formule $pType
	 * @return SG_VraiFaux|SG_Collection
	 */
	function ViderCache($pType = '') {
		$type = new SG_Texte($pType);
		if ($type === '?') {
			$ret = SG_Cache::cles();
		} else {
			$ret = new SG_VraiFaux(SG_Cache::viderCache($type));
		}
		return $ret;
	}

	/**
	 * Affiche le contenu d'une bibliothèque sous ../lib/
	 * La recherche du lieu de la bibliothèque est assurée par la fonction
	 * @version 2.4 div ; getTexte ; param = nom de classe
	 * @param string|SG_Texte|SG_Formule $pClassName nom de la classe à afficher
	 * @return SG_HTML
	 */
	function Lib($pClassName = '') {
		if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
			$classname = SG_Texte::getTexte($pClassName);
			$filename = SG_Autoloader::getClassFileName($classname);
			if (!file_exists($filename)) {
				$ret = new SG_Erreur('0261',$filename);
			} else {
				$txt = '<div class="sg-lib-titre">' . $classname . '</div>';
				$lib = @file_get_contents($filename);
				$ret = new SG_HTML($txt . '<div class="sg-lib"><pre>' . htmlentities($lib, ENT_QUOTES, 'UTF-8') . '</pre></div>');
			}
		} else {
			$ret = new SG_Erreur('0260');
		}
		return $ret;
	}

	/**
	 * Retourne la collection de tous les documents d'un modèle donné
	 * @since 1.0.7
	 * @param string $pModele code du modèle recherché
	 * @uses SG_CouchDB
	 * @return SG_Collection
	 */
	function getAllDocuments($pModele = '') {
		return $this -> sgbd -> getAllDocuments($pModele);
	}

	/**
	 * getChercherDocuments : cherche des documents selon le type de base de données du dictionnaire
	 * @since 1.0.7
	 * @version 1.3.0 err 53 
	 * @param string $pCodeBase
	 * @param string $pTypeObjet
	 * @param string $pCodeObjet
	 * @param string|SG_Formule $pFiltre
	 * @return SG_Collection|SG_Erreur
	 */	
	function getChercherDocuments($pCodeBase = '', $pTypeObjet = '', $pCodeObjet = '', $pFiltre = '') {
		return $this -> sgbd -> getChercherDocuments($pCodeBase, $pTypeObjet, $pCodeObjet, $pFiltre);
	}

	/**
	 * getObjet : cherche un objet de type @Document dans une base à partir de pQuelqueChose (base / document).
	 * Dans le cas d'un objet système (genre @Profil, @Utilisateur, etc, il faut d'abord
	 * lire le document couchdb pour savoir de quel type sera l'objet.
	 * Donc on crée d'abord un DocumentCouchDB, puis l'objet système.
	 * si $pQuelqueChose déja objet, on le retourne tel quel ; si $pQuelqueChose précise champ, rendre l'objet correspondant
	 * @since 1.0.7
	 * @version 2.6 supp param 3 inutilisé
	 * @param string $pQuelqueChose : uuid du document de l'objet (codebase/iddocument)
	 * @param string $pTypeDefaut
	 * @return SG_Objet ou dérivés
	 */
	function getObjet($pQuelqueChose = '', $pTypeDefaut = '') {
		$ret = new SG_Rien();
		if (is_object($pQuelqueChose)) {
			$ret = $pQuelqueChose;
		} elseif (gettype($pQuelqueChose) === 'string') {
			$index = explode('/', $pQuelqueChose);
			if(!isset($index[1])) {
				$ret = new SG_Erreur('0053', implode($index)); // paramètre indisponible
			} else {
				$codedocument = $index[0] . '/' . $index[1];
				$ret = $this -> sgbd -> getObjetByID($codedocument); // 2.1
				$type = getTypeSG($ret);
				// cas des répertoires : on charge le chemin qui vient du navigateur
				if ($type === '@Repertoire') {
					if (isset($_POST[SG_Repertoire::CHAMPCHEMIN])) {
						$ret -> setChemin($_POST[SG_Repertoire::CHAMPCHEMIN]);
						$ret -> ajouterChemin (null);
					}
				}
				if (sizeof($index) >= 3) { // 1.1 on ne cherche qu'une propriété de l'objet
					if ($type === '@Erreur') {
						$ret = ': ' . $ret -> getLibelle();
					} elseif ($type === '@Repertoire') {//... ou un sous-répertoire
						if (isset($_POST[SG_Repertoire::CHAMPCHEMIN])) {
							$ret -> chemin = $_POST[SG_Repertoire::CHAMPCHEMIN];
						}
						$ret = $ret -> AllerA($index[2]);
					} else {
						$ret = $ret -> getValeurPropriete($index[2]);
					}
				}
			}
		}
		return $ret;
	}
	
	/**
	 * Recherche les documents liés à un autre SG_Document
	 * @since 1.0.7
	 * @param SG_Document $pDocument
	 * @param string $pModele
	 * @param string $pChamp
	 * @return SG_Document|SG_Collection ou dérivé
	 */
	function getObjetsLies($pDocument, $pModele, $pChamp) {
		return $this -> sgbd -> getObjetsLies($pDocument, $pModele, $pChamp);
	}

	/**
	 * Chercher une collection de documents sur des indications vagues...
	 * @todo terminer cette méthode
	 * @since 1.0.7
	 * @param string $pTexte = ''
	 * @param string|array $pModeles
	 * @param string|array $pChamps
	 * @return SG_Collection
	 */	
	function chercherVague($pTexte = '', $pModeles, $pChamps) {
		return $this -> sgbd -> chercherVague($pTexte = '', $pModeles, $pChamps);
	}

	/**
	 * getDocumentsFromTypeChamp
	 * @since 1.0.7
	 * @param string $pType = ''
	 * @param string $pChampCle
	 * @param string $pValeur valeur que doit contenir le champ
	 * @return SG_Collection
	 */	
	function getDocumentsFromTypeChamp($pType = '', $pChampCle = '', $pValeur = null) {
		return $this-> sgbd -> getDocumentsFromTypeChamp($pType, $pChampCle, $pValeur);
	}

	/**
	 * BasesSysteme : collection des bases système de SynerGaïa
	 * @since 1.0.6
	 * @version 1.3.1 base des formulaires
	 * @param SG_VraiFaux $pAvecOperations : ajouter ou non la base des opérations
	 * @return SG_Collection contenant des SG_Base
	 */
	static function BasesSysteme($pAvecOperations = false) {
		$ret = new SG_Collection();
		$ret -> elements[SG_Annuaire::CODEBASE] = new SG_Base(SG_Annuaire::CODEBASE);
		$ret -> elements[SG_Dictionnaire::CODEBASE] = new SG_Base(SG_Dictionnaire::CODEBASE);
		$ret -> elements[SG_DictionnaireVue::CODEBASE] = new SG_Base(SG_DictionnaireVue::CODEBASE);
		$ret -> elements[SG_Formulaire::CODEBASE] = new SG_Base(SG_Formulaire::CODEBASE);
		//$ret -> elements[SG_Evenement::CODEBASE] = new SG_Base(SG_Evenement::CODEBASE);
		$ret -> elements[SG_Libelle::CODEBASE] = new SG_Base(SG_Libelle::CODEBASE);
		$ret -> elements[SG_Parametre::CODEBASE] = new SG_Base(SG_Parametre::CODEBASE);
		$ret -> elements[SG_Personne::CODEBASE] = new SG_Base(SG_Personne::CODEBASE);
		$ret -> elements[SG_Ville::CODEBASE] = new SG_Base(SG_Ville::CODEBASE);
		$op = getBooleanValue($pAvecOperations);
		if($op === true) {
			$ret -> elements[SG_Operation::CODEBASE] = new SG_Base(SG_Operation::CODEBASE);
		}
		return $ret;
	}

	/**
	 * Bases : collection des bases de SynerGaïa
	 * @since 1.0.6
	 * @version 2.4 corrigé pour les SG_Document sans base explicite
	 * @version 2.7 test SG_Erreur
	 * @param SG_VraiFaux $pAvecOperations : ajouter ou non la base des opérations
	 * @return SG_Collection contenant des SG_Base
	 */
	static function Bases($pAvecOperations = false) {
		$ret = new SG_Collection();
		$collec = SG_Dictionnaire::ObjetsDocument();
		if ($collec instanceof SG_Erreur) {
			$ret = $collec;
		} else {
			$ret = self::BasesSysteme($pAvecOperations);
			foreach($collec -> elements as $objet) {
				$base = $objet -> getValeur('@Base', '');
				if ($base === '') {
					$base = SG_Dictionnaire::getCodeBase($objet -> getValeur('@Code'));
				}
				if(! array_key_exists($base, $ret -> elements)) {
					$ret -> elements[$base] = new SG_Base($base);
				}
			}
		}
		return $ret;
	}

	/**
	 * Suppression de toutes les opérations antérieures à une date donnée.
	 * Mise au statut 'terminé' des opérations plus vieilles que 5 jours et
	 * @formula :
	 *  @Chercher("@Operation","",.@DateCreation.@Date.@InferieurA(@Aujourdhui.@Ajouter($1.@MultiplierPar(-1)))).@PourChaque(.@Supprimer)");
	 *  @Chercher("@Operation","",.@DateCreation.@Date.@InferieurA(@Aujourdhui.@Ajouter(-5)).@Et(.@Statut.@Egale("encours"))).@PourChaque(.@Terminer)");
	 * @since  1.1 : ajout
	 * @param integer|SG_Nombre|SG_Formule $pNbJours
	 * @return SG_VraiFaux true
	 */
	function PurgerOperations($pNbJours = 5) {
		$nbjours = new SG_Nombre($pNbJours);
		$nbjours = - abs($nbjours -> toInteger());
		if ($nbjours > -5) {
			$nbjours = -5;
		}
		// suppression
		$seuilSupp = SG_Rien::Aujourdhui() -> Ajouter($nbjours);
		$formule = new SG_Formule('@Chercher("@Operation","",.@DateCreation.@Date.@InferieurA(@Date("' . $seuilSupp -> toString() . '")))');
		$collec = $formule -> calculer();
		if(getTypeSG($collec) === '@Collection') {
			foreach ($collec -> elements as $element) {
				$element -> Supprimer();
			}
		}
		// clôture
		$seuilCloture = SG_Rien::Aujourdhui() -> Ajouter(-5);
		$formule = new SG_Formule('@Chercher("@Operation","",.@DateCreation.@Date.@InferieurA(@Date("' . $seuilCloture -> toString() . '")))');
		$collec = $formule -> calculer();
		if(getTypeSG($collec) === '@Collection') {
			foreach ($collec -> elements as $element) {
				if ($element -> getValeur('@Statut') === SG_Operation::STATUT_ENCOURS) {
					$element -> Terminer();
				}
			}
		}
		return new SG_VraiFaux(true);
	}

	/**
	 * Provoque une réplication de ce serveur vers un autre dont on connait l'adresse sur le réseau
	 * @since 1.1 ajout
	 * @version 2.4 alignement des paramètres avec SG_CouchDB
	 * @param string|SG_Texte|SG_Formule $pAdresse : adresse ip du serveur visé
	 * @param string|SG_Texte|SG_Formule $pID
	 * @param string|SG_Texte|SG_Formule $pPSW
	 * @param boolean|SG_VraiFaux|SG_Formule $pContinue La réplication doit-elle se continuer ? par défaut true
	 * @param boolean|SG_VraiFaux|SG_Formule $pCreate Faut-il créer les bases si elles n'existent pas ? par défaut true
	 * @return SG_VraiFaux
	 */
	function RepliquerAvec ($pAdresse = '', $pID = '',$pPSW = '',$pContinue = true, $pCreate = true) {
		$ret = $this -> sgbd -> RepliquerAvec($pAdresse, $pID,$pPSW,$pContinue, $pCreate);
		return $ret;
	}

	/**
	 * Fonction particulière qui permet de tester de la programmation ou inclure une réparation en dur.
	 * Ne pas utiliser en programmation d'application puisqu'elle peut contenir une fonction variable
	 * @since 1.1 ajout
	 * @return SG_VraiFaux false
	 **/
	function Test() {
		return new SG_VraiFaux(false);
	}

	/**
	 * Retourne ou met à jour le titre de l'application
	 * @since 1.3.1 ajout
	 * @version 2.2 met à jour le titre dans config
	 * @param string|SG_Texte|SG_Formule $pTitre : titre remplaçant le titre actuel de l'application
	 * @return SG_Texte|SG_SynerGaia si param vide, titre, sinon $sthis
	 **/
	function Titre($pTitre = '') {
		if ($pTitre !== '') {
			$titre = SG_Texte::getTexte($pTitre);
			if (getTypeSG($titre) === '@Erreur') {
				$ret = $titre;
			} else {
				SG_Config::setConfig('SynerGaia_titre', $titre);
				SG_Navigation::viderCacheBanniere();
				$ret = $this;
			}
		} else {
			$ret = SG_Config::getConfig('SynerGaia_titre', 'SynerGaïa');
		}
		return $ret;
	}

	/**
	 * Retourne ou met à jour le logo de l'application
	 * @since 1.3.1 ajout
	 * @version 2.6 met à jour le logo
	 * @param string|SG_Texte|SG_Formule $pLogo Chemin du logo
	 * @return string
	 **/
	function Logo($pLogo = '') {
		if ($pLogo !== '') {
			$ret = $pLogo;
			$logo = SG_Texte::getTexte($pLogo);
			SG_Config::getConfig('SynerGaia_logo', $logo);
		} else {		
			$ret = SG_Config::getConfig('SynerGaia_logo', SG_Navigation::URL_THEMES . 'defaut/img/favicon.png');
		}
		return $ret;
	}

	/**
	 * Liste des paquets disponibles dans l'application
	 * @since 1.3.1 ajout
	 * @param string|SG_Texte|SG_Formule $pOrigine : "" tous (defaut), "s" standard SynerGaïa, "l" paquets locaux
	 * @return SG_Collection les paquets demandés
	 **/
	function Paquets($pOrigine = '') {
		$parmOrigine = strtolower(substr(SG_Texte::getTexte($pOrigine), 0, 1));
		
		// liste des répertoires à examiner selon paramètre
		$origines = array();
		if($parmOrigine === '' or $parmOrigine === 's') {			
			$origines[] = array('s', SYNERGAIA_PATH_TO_ROOT.'/ressources/packs');
		}
		if($parmOrigine === '' or $parmOrigine === 'l') {			
			$origines[] = array('l', SYNERGAIA_PATH_TO_APPLI.'/var/packs');
		}
		// Cherche les modules complémentaires disponibles
		$paquets = array();
		foreach ($origines as $origine) {
			$typeOrigine = $origine[0];
			$cheminPaquet = $origine[1];	
			// Liste les noms des fichiers JSON du dossier
			$dir = @opendir($cheminPaquet);
			if($dir !== false) {
				while ($file = readdir($dir)) {
					// On cherche les fichiers "normaux"
					if ($file != '.' && $file != '..' && !is_dir($cheminPaquet . $file)) {
						$code = substr($file, 0, -5);
						$paquet = new SG_Paquet($code, $cheminPaquet);
						$paquet -> type =  $typeOrigine;
						$paquets[$typeOrigine . ':' . $code] = $paquet;
					}
				}
				closedir($dir);
			}
		}
		$ret = new SG_Collection();
		$ret -> elements = $paquets;
		return $ret;
	}

	/**
	 * Crée une nouvelle application dépendante de path_to_root actuel. Ne fonctionne que s'il y a déjà une application installée
	 * @since 1.3.4 ajout
	 * @param string|SG_Texte|SG_Formule $pDir code et répertoire de l'application (relatif)
	 * @param string|SG_Texte|SG_Formule $pTitre titre de l'application
	 * @return SG_VrauFaux|SG_Erreur
	 **/
	function NouvelleApplication($pDir = '', $pTitre = '') {
		if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === false) {
			$ret = new SG_Erreur('0097');
		} else {
			$ret = new SG_VraiFaux(true);
			$dir = SG_Texte::getTexte($pDir);
			$titre = SG_Texte::getTexte($pTitre);
			if($dir === '') {
				$ret = new SG_Erreur('0098');
			} else {
				$ret = self::install_repertoires($dir);
				if(! $ret instanceof SG_Erreur) {					
					header('Location: http://' . $_SERVER["HTTP_HOST"] . '/' . $dir . '/index.php');
					die();
				}
			}
		}
		return $ret;
	}

	/**
	 * Initialisation des ressources nécessaires
	 * vide le debug 
	 * @since 2.0
	 * @version 2.4 test $_SESSION et return ; err 0259
	 * @return string|SG_Erreur : si non vide, message d'erreur
	 **/
	static function initialiser() {
		$ret = '';
		$GLOBALS['SG_LOG'] = new SG_Log('Console', SG_LOG::LOG_NIVEAU_DEBUG);

		// 1.3 Definition initiale des parametres de configuration :
		if (!isset($SG_Config['SynerGaia_titre'])) {
			$SG_Config['SynerGaia_titre'] = 'SynerGaïa';
		}
		if (!isset($SG_Config['SynerGaia_url'])) {
			$SG_Config['SynerGaia_url'] = '<a href="http://docum.synergaia.eu">Documentation</a>';
		}
		if (!isset($SG_Config['SynerGaia_theme'])) {
			$SG_Config['SynerGaia_theme'] = 'defaut';
		}
		if (!isset($SG_Config['CouchDB_port'])) {
			$SG_Config['CouchDB_port'] = 5984;
		}

		// 1.3 Definition des parametres d'environnement :
		@set_time_limit(3600);
		@ini_set('max_execution_time', 3600);
		@ini_set('max_input_time', 3600);
		@ini_set('date.timezone', 'Europe/Paris');

		// fonctions et variables du socle
		require_once SYNERGAIA_PATH_TO_ROOT . '/core/socle.php';
		require_once SYNERGAIA_PATH_TO_ROOT . '/core/simple_html_dom.php';
		
		// mise en place de la récupération des erreurs
		set_error_handler('errorHandler', E_ALL);// dans core/socle.php
		set_exception_handler('exceptionHandler');// dans core/socle.php
		// démarrage de la session. A partir d'ici la variable $_SESSION est utilisable
		session_start();
		if (! is_array($_SESSION)) {
			$ret = new SG_Erreur('0259');
		} else {
			$_SESSION['debug']['texte'] = '';// à partir d'ici on peut utiliser tracer()
			// Paramètres de débogage
			unset( $_SESSION['benchmark']); //efface le tableau des compteurs de benchmark
			$_SESSION['timestamp_init'] = microtime(true);
			if (!isset($_SESSION['debug']['on'])) {
				$_SESSION['debug']['on'] = false;
			}
			if ($_SESSION['debug']['on']) {
				$_SESSION['debug']['contenu'] = '<b>===== DEBUG ACTIF ===== </b><br>';
			} else {
				$_SESSION['debug']['contenu'] = '';
			}
			if(!isset($_SESSION['@SynerGaia']) or ! is_object($_SESSION['@SynerGaia'])) {
				$_SESSION['@SynerGaia'] = new SG_SynerGaia();
			}
			if (!isset($_SESSION['principal']) or ! is_array($_SESSION['principal'])) {
				$_SESSION['principal'] = array();
			}
			if (!isset($_SESSION['page']) or ! is_array($_SESSION['page'])) {
				$_SESSION['page'] = array();
			}
			$codeAppli = SG_Config::getConfig('CouchDB_prefix', '');
			if (!isset($_SESSION[$codeAppli]) or ! is_array($_SESSION[$codeAppli])) {
				$_SESSION[$codeAppli] = array();
			}
			SG_Cache::initialiser();
		}
		return $ret;
	}

	/**
	 * recherche un message d'erreur standard de la base des libellés (à partir du code à 4 chifres) et le met en cache ici
	 * @since 2.1 ajout
	 * @version 2.4 simplifié si pas trouvé
	 * @param string $pNumero : numéro du message d'erreur
	 * @return string
	 **/
	function getLibelle($pNumero) {
		$ret = '';
		if (! isset($this -> libelles[$pNumero])) { 
			if (strlen($pNumero) !== 4) {
				$libelle = '';
			} else {
				$baseLibelles = new SG_Base(SG_Libelle::CODEBASE);
				if ($baseLibelles -> Existe() -> estVrai()) {
					$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(SG_Libelle::CODEBASE,'@Libelle', $pNumero);
					if (getTypeSG($doc) !== '@Libelle') {
						$libelle = '(' . $pNumero . ' : le libellé n\'a pas été trouvé). %s';
					} else {
						$libelle = $doc -> getValeur('@Titre');
					}
				} else {
					$libelle = 'Ce code d\'erreur n\'est pas expliqué car la base ' . SG_Libelle::CODEBASE . ' n\est pas installée. Infos complémentaires : %s';
				}
			}
			$this -> libelles[$pNumero] = $libelle;
		}
		$ret = $this -> libelles[$pNumero];
		return $ret;
	}

	/**
	 * adresse physique du serveur sur lequel tourne SynerGaïa. C'est l'adresse dans le réseau
	 * @since 2.3 ajout
	 * @return SG_Texte l'adresse ipV4 du serveur
	 **/
	static function AdresseIP () {
		return new SG_Texte($_SERVER['SERVER_ADDR']);
	}

	/**
	 * Retourne le navigateur actuel
	 * @since 2.3 ajout
	 * @return SG_Navigation
	 **/
	function Navigation() {
		return $this -> navigation;
	}

	/**
	 * enlève les opérations anciennes (plus d'un an), compacte les bases, supprime les vues, supprime les compilations de formules
	 * @since 2.4 ajout
	 * @version 2.6 static ; @PourChaque ; strtolower
	 * @version 2.7 test SG_Erreur
	 * @todo éviter de supprimer les opérations ou formules en cours
	 * @param string|SG_Texte|SG_Formule $pQuoi liste de caractère sur ce qu'il faut nettoyer 'o' opérations, 'b' bases, 'f' formules compilées
	 * @return array|SG_Erreur
	 **/
	static function Nettoyer($pQuoi = 'obf') {
		$ret = array();
		$quoi = strtolower(SG_Texte::getTexte($pQuoi));
		// supprime les opérations de plus d'un an
		if (strpos($quoi,'o') !== false) {
			$date = SG_Rien::Aujourdhui() -> Soustraire(1,'an') -> toString();
			$filtre = new SG_Formule('.@DateCreation.@Date.@InferieurA("' . $date .'")');
			$base = new SG_Base(SG_Operation::CODEBASE);
			$operations = $base -> Chercher($filtre);
			$action = new SG_Formule('.@Supprimer');
			$operations -> PourChaque($action);
			$ret[] = 'Opérations de plus d\'un an supprimées';
		}
		// compacte les bases
		if (strpos($quoi,'b') !== false) {
			$bases = self::Bases();
			if ($bases instanceof SG_Erreur) {
				$ret = $bases;
			} else {
				foreach ($bases -> elements as $base) {
					$ret[] = $base -> Compacter();
				}
			}
		}
		// supprimer les formules compilées
		if (strpos($quoi,'f') !== false) {
			$dir = SYNERGAIA_PATH_TO_APPLI . '/var';
			$handle = @opendir($dir);
			if ($handle === false) {
				$ret = new SG_Erreur('0231', $dir);// inaccessible
			} else {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." and $entry != "..") {
						$path = $dir . '/' . $entry;
						$pref = substr($entry, 0, 3);
						if (!is_dir($path) and ($pref === 'FO_' or $pref === 'OP_')) {
							$ret[] = $entry;
							unlink($path);
						}
					}
				}
			}
		}
		if (is_array($ret)) {
			$ret = new SG_Collection($ret);
		}
		return $ret;
	}

	/**
	 * permet d'ajouter des programmes php spécifiques exécutés par un administrateur
	 * voir aussi  -> Test()
	 * @since 2.4 ajout
	 **/
	static function special() {
		if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === false) {
			$ret = new SG_Erreur('0097','@SynerGaia.@special');
		} else {
			$base = new SG_Base('photossave', true);
			$ret = new SG_VraiFaux(true);
		}
		return $ret;
	}

	/**
	 * afficher des informations sur l'état de PHP et d'Apache (notamment les sessions enregistrées)
	 * autorisé aux seuls administrateurs
	 * @since 2.5 ajout
	 * @param boolean|SG_VraiFaux|SG_Formule $pEcran : si true, sortie en echo
	 * @return SG_Collection|SG_Erreur collection d'informations par session
	 **/
	static function Status($pEcran = false) {
		if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === false) {
			$ret = new SG_Erreur('0097','@SynerGaia.@Status');
		} else {
			$infos = array();
			foreach($_SESSION['principal'] as $key => $entree) {
				$size = selg::mesurerObjet($entree);
				$id = 'principal [' . $key . '] : ';
				$type = getTypeSG($entree);
				if ($type === 'string') {
					$infos[] = $id . $entree;
				} elseif (is_object($entree) and $entree -> DeriveDeDocument() -> estVrai()) {
					$infos[] =  $id . $type . ' (' . $entree -> getUUID() . ') ' . $size . ' octets';
				} elseif ($type === '@Collection') {
					$infos[] =  $id . $type . ' de ' . sizeof($entree -> elements) . ' élements de type (' . getTypeSG($entree -> elements[0]) . ') ' . $size . ' octets' ;
				} else {
					$infos[] =  $id . 'inconnu ' . $type;
				}
			}
			$vide = new SG_Texte();
			foreach($_SESSION['operations'] as $key => $entree) {
				$size = selg::mesurerObjet($entree);
				$id = 'operation [' . $key . '] : ';
				$type = getTypeSG($entree);
				$txt = $id . $type . ' (' . $entree -> getValeur('@DateCreation', '') . ') ' . $size . ' octets';
				$p = $entree -> getPrincipal();
				if ($p !== '') {
					if (is_array($p)) {
						$txt.= PHP_EOL . sizeof($p) . ' éléments';
					}
				}
				$infos[] = $txt;
			}
			$ret = new SG_Collection();
			$ret -> elements = $infos;
		}
		return $ret;
	}

	/**
	 * Mesure la taille d'un objet SynerGaia
	 * @since 2.5 ajout
	 * @param any $pObjet objet à mesurer
	 * @return integer taille estimée en octets
	 **/
	static function mesurerObjet($pObjet) {
		if (is_object($pObjet)) {
			$avant = memory_get_usage();
			$s = clone($pObjet);
			$apres = memory_get_usage();
			$ret = ($apres - $avant);
			unset($s);
		} else {
			$ret = 0;
		}
		return $ret;
	}

	/**
	* Donne un id aléatoire
	* @since 1.3.2 ajout (dans SG_Champ)
	* @version 2.5 transfert ici
	* @return string
	**/
	static function idRandom() {
		return substr(sha1(mt_rand()), 0, 8);
	}
	//============== Vient de l'anicenne classe SG_Installation
	
	/**
	 * Procédure d'installation nécessaire ? oui si pas de config ou pas de couchdb ou pas d'anuaire ou pas de dictionnaire
	 * @version 1.3.0 correction test dictionnaire
	 * @return boolean
	 */
	static function installationNecessaire() {
		$ret = true;

		// Cherche le fichier de configuration
		if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/' . SG_Config::FICHIER)) {
			// test lien nav et présence du javascript
			if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/nav/js/synergaia.js')) {
				// Fait un test de connexion au serveur CouchDB
				$couchDB = new SG_CouchDB();
				$connexionOK = $couchDB -> testConnexion();
				if ($connexionOK === true) {
					// Cherche la base annuaire
					$baseAnnuaire = new SG_Base(SG_Annuaire::CODEBASE);
					$baseAnnuaireExiste = $baseAnnuaire -> Existe() -> estVrai();
					// Cherche la base dictionnaire
					if ($baseAnnuaireExiste === true) {
						$baseDictionnaire = new SG_Base(SG_Dictionnaire::CODEBASE);
						$baseDictionnaireExiste = $baseDictionnaire -> Existe() -> estVrai();
						if ($baseDictionnaireExiste === true) {
							if(SG_Config::getConfig('HashDictionnaireDernierImport','') !== '') {
								$ret = false;
							}
						}
					}
				}
			}
		}
		return $ret;
	}
	
	/**
	 * Méthode de migration d'une version précédente
	 * Cette méthode peut être exécutée plusieurs fois sans risque
	 * Elle vide les caches et supprime les vues à recalculer
	 * 
	 * @version 2.6 Nettoyer préalable (enlever /var/MO_... et /var/OP_...
	 * @param boolean $pRecalcul true par defaut
	 * @return string HTML de la mise à jour
	 */
	static function MettreAJour($pRecalcul = true) {
		$nl = '<br>';
		// Nettoyage préalable
		$ret = '<h1>Nettoyage des anciennes version de formules</h1>'. $nl;
		self::Nettoyer();
		$ret.= 'Nettoyage terminé';
		journaliser('Nettoyage termine');
		// recalcul sur d'anciennes versions
		$version = SG_SynerGaia::NOVERSION;
		$versionprec = SG_Config::getConfig('SynerGaia_version','0000');
		$ret.= '<h1>Mise à jour ' . SG_SynerGaia::VERSION . ' (' . $version . ')</h1>'. $nl;
		if ($pRecalcul == true) {
			if ($versionprec < 1007) {
				$ret .= self::recalcul1007();
			}
			if ($versionprec < 1201) {
				$ret .= self::recalcul1201();
			}
			if ($versionprec < 2601) {
				$ret .= self::recalcul2600();
			}
		}
		// Cache
		$ret .= '<h2>Cache</h2>' . $_SESSION['@SynerGaia']->ViderCache() -> toString(). $nl;
		// Vues allDocuments
		$tmp = self::updateVuesAllDocuments();
		if ($tmp instanceof SG_Erreur) {
			$txt = $tmp -> toHTML() -> texte;
		} else {
			$txt = $tmp;
		}
		$ret .= '<h2>Recalcul des vues</h2>' . str_replace(PHP_EOL, $nl, $txt). $nl;
		// Libellés
		if (self::updateLibellesNecessaire() === true) {
			$update = self::updateLibelles();
			if (getTypeSG($update) !== '@Erreur') {
				if ($update === true) {
					$ret .= '<b>' . SG_Libelle::getLibelle('0071', false) . '</b><br>';
				} else {
					$ret .= '<b><p style="color:#ff0000">' . SG_Libelle::getLibelle('0072', false) . '</p></b> ' . $nl;
					$updateTotal = false;
				}
			} else {
				$ret .= '<b><p style="color:#ff0000">' . $update -> toString() . '</p></b> ' . $nl;
				$updateTotal = false;
			}
		}
		// Dictionnaire
		$updateTotal = true;
		if (self::updateDictionnaireNecessaire() === true) {
			$update = self::updateDictionnaire();
			if (getTypeSG($update) !== '@Erreur') {
				if ($update === true) {
					$ret .= '<b>' . SG_Libelle::getLibelle('0069', false) . '</b>' . $nl;
				} else {
					$ret .= '<b><p style="color:#ff0000">' . SG_Libelle::getLibelle('0070', false) . '</p></b> ' . $nl;
					$updateTotal = false;
				}
			} else {
				$ret .= '<b><p style="color:#ff0000">' . $update -> toString() . '</p></b> ' . $nl;
				$updateTotal = false;
			}
		}
		// recompilation des modèles d'opération et des objets
		$ret .= self::recalcul2100();
		// Mise à jour des villes
		$applivilles = SG_Config::getConfig('CouchDB_villes','');
		if ($applivilles === '') {
			if (self::updateVillesNecessaire() === true) {
				$update = self::updateVilles();
				if (getTypeSG($update) !== '@Erreur') {
					if ($update === true) {
						$ret .= '<h3>' . SG_Libelle::getLibelle('0073', false) . '</h3><br>';
					} else {
						$ret .= '<b><p style="color:#ff0000">' . SG_Libelle::getLibelle('0074', false) . '</p></b><br>';
						$updateTotal = false;
					}
				} else {
					$ret .= '<b><p style="color:#ff0000">' . $update -> toString() . '</p></b><br>';
					$updateTotal = false;
				}
			}
		} else {
			$ret .= '<b>' . SG_Libelle::getLibelle('0206', true, $applivilles) . '</b><br>';
		}
		// enlever la demande de mise à jour sur le navigateur
		if ($updateTotal === true) {
			SG_Config::setConfig('SynerGaia_version', SG_SynerGaia::NOVERSION);
			unset($_SESSION['page']['banniere']);
			$ret .= '<br><i>' . SG_SynerGaia::VERSION . ' : ' . SG_Libelle::getLibelle('0075', false) . '</i>';
		} else {
			$ret .= '<br><b><p style="color:#ff0000"><i>' . SG_SynerGaia::VERSION . ' : ' . SG_Libelle::getLibelle('0076', false, SG_SynerGaia::NOVERSION) . '</i></p></b>';
		}

		$ret = new SG_HTML($ret);
		return $ret;
	}
	
	/**
	 * Installation de SynerGaia
	 * @since 1.3.2 repris de install.php puis de SG_Installation qui disparait
	 * @version 2.1.1 init @Moi
	 * @version création lien /nav
	 * @return SG_VraiFaux
	 */
	static function Installer() {
		$sg_install = array();
		$numPageInstallRecue = '';

		// Cherche les paramètres passés en POST pour savoir à quelle page de l'installation on est
		if (isset($_POST['sg_install_etape'])) {
			$numPageInstallRecue = (string)$_POST['sg_install_etape'];
		}
		$numPageInstallDemandee = $numPageInstallRecue;

		// Valeurs par défaut :
		$sg_install['db_type'] = SG_Config::getConfig('DB_Type', 'CouchDB');
		$sg_install['db_host'] = SG_Config::getConfig('CouchDB_host', '127.0.0.1');
		$sg_install['db_login'] = SG_Config::getConfig('CouchDB_login', 'synergaia');
		$sg_install['db_password'] = SG_Config::getConfig('CouchDB_password', '');
		// préfixe couchdb par défaut
		$ipos = strripos(SYNERGAIA_PATH_TO_APPLI,'/');
		$prefixe = substr(SYNERGAIA_PATH_TO_APPLI, $ipos + 1);
		$sg_install['db_prefix'] = SG_Config::getConfig('CouchDB_prefix', $prefixe);

		$sg_install['admin_login'] = '';
		$sg_install['admin_password'] = '';
		$sg_install['admin_password2'] = '';

		$sg_install['modules'] = '';

		// Erreurs des formulaires
		$sg_install['erreurs'] = array();
		$sg_install['erreurs']['sg_install'] = '';

		$sg_install['erreurs']['db_type'] = '';
		$sg_install['erreurs']['db_host'] = '';
		$sg_install['erreurs']['db_login'] = '';
		$sg_install['erreurs']['db_password'] = '';
		$sg_install['erreurs']['db_prefix'] = '';

		$sg_install['erreurs']['admin_login'] = '';
		$sg_install['erreurs']['admin_password'] = '';
		$sg_install['erreurs']['admin_password2'] = '';

		// Page 0 validée (base de donnée) => cherche les données envoyées
		if ($numPageInstallRecue === '0') {

			if (isset($_POST['sg_install_db_type'])) {
				$sg_install['db_type'] = (string)$_POST['sg_install_db_type'];
			}
			if (isset($_POST['sg_install_db_host'])) {
				$sg_install['db_host'] = (string)$_POST['sg_install_db_host'];
			}
			if (isset($_POST['sg_install_db_login'])) {
				$sg_install['db_login'] = (string)$_POST['sg_install_db_login'];
			}
			if (isset($_POST['sg_install_db_password'])) {
				$sg_install['db_password'] = (string)$_POST['sg_install_db_password'];
			}
			if (isset($_POST['sg_install_db_prefix'])) {
				$sg_install['db_prefix'] = (string)$_POST['sg_install_db_prefix'] . '_';
			}

			// Vérifie la validité des données reçues
			$okPasserEtapeSuivante = true;

			if ($sg_install['db_type'] === '') {
				$sg_install['erreurs']['db_type'] = 'Le type de base de données est obligatoire.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['db_host'] === '') {
				$sg_install['erreurs']['db_host'] = 'Le nom d\'hôte est obligatoire.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['db_login'] === '') {
				$tmpHost = ($sg_install['db_host'] === '') ? 'localhost' : $sg_install['db_host'];
				$sg_install['erreurs']['db_login'] = 'L\'identifiant de connexion est obligatoire. Utilisez <a href="http://' . $tmpHost . ':5984/_utils/" onclick="window.open(this.href);return false;">Futon/CouchDB</a> pour créer un utilisateur si besoin.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['db_password'] === '') {
				$sg_install['erreurs']['db_password'] = 'Le mot de passe de connexion est obligatoire.';
				$okPasserEtapeSuivante = false;
			}

			// Vérifier que le préfixe est correct (caractères autorisés)
			if ($sg_install['db_prefix'] !== SG_CouchDB::NormaliserNomBase($sg_install['db_prefix'])) {
				$sg_install['erreurs']['db_prefix'] = 'Le préfixe n\'est pas valide. Il doit commencer par une lettre et ne peut contenir que des lettres en minuscule, des chiffres, et le caractère "_".';
				$okPasserEtapeSuivante = false;
			}

			if ($okPasserEtapeSuivante === true) {

				// Enregistrer dans le fichier config/config.php
				$saveOK = true;
				// essai d'ouverture de config.php
				$saveOK = $saveOK and SG_Config::setConfig('CouchDB_host', $sg_install['db_host']);
				$saveOK = $saveOK and SG_Config::setConfig('CouchDB_login', $sg_install['db_login']);
				$saveOK = $saveOK and SG_Config::setConfig('CouchDB_password', $sg_install['db_password']);
				$saveOK = $saveOK and SG_Config::setConfig('CouchDB_prefix', $sg_install['db_prefix']);
				$saveOK = $saveOK and SG_Config::setConfig('SynerGaia_path_to_root',  SYNERGAIA_PATH_TO_ROOT);
				if ($saveOK === false) {
					// Erreur à la sauvegarde des parametres
					$sg_install['erreurs']['sg_install'] = 'Le fichier ' . SG_Config::FICHIER . ' n\'a pas pu être créé ou modifié.';
				} else {
					// Faire un test de connexion
					$couchDB = new SG_CouchDB();
					$connexionOK = $couchDB -> testConnexion();
					if ($connexionOK === false) {
						// Erreur au test de connexion
						$tmpHost = ($sg_install['db_host'] === '') ? 'localhost' : $sg_install['db_host'];
						$sg_install['erreurs']['sg_install'] = 'La connexion à CouchDB est impossible. Vérifiez les paramètres saisis et utilisez <a href="http://' . $tmpHost . ':5984/_utils/" onclick="window.open(this.href);return false;">Futon/CouchDB</a> pour créer un utilisateur si besoin.';
					} else {
						// Proposer l'étape suivante
						$numPageInstallDemandee = '1';
					}
				}

			}

		}

		// Page 1 validée (adminitrateur) => cherche les données envoyées
		if ($numPageInstallRecue === '1') {

			if (isset($_POST['sg_install_admin_login'])) {
				$sg_install['admin_login'] = (string)$_POST['sg_install_admin_login'];
			}
			if (isset($_POST['sg_install_admin_password'])) {
				$sg_install['admin_password'] = (string)$_POST['sg_install_admin_password'];
			}
			if (isset($_POST['sg_install_admin_password2'])) {
				$sg_install['admin_password2'] = (string)$_POST['sg_install_admin_password2'];
			}

			// Vérifie la validité des données reçues
			$okPasserEtapeSuivante = true;

			if ($sg_install['admin_login'] === '') {
				$sg_install['erreurs']['admin_login'] = 'L\'identifiant administrateur est obligatoire.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['admin_password'] === '') {
				$sg_install['erreurs']['admin_password'] = 'Le mot de passe est obligatoire.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['admin_password2'] !== $sg_install['admin_password']) {
				if ($sg_install['admin_password'] !== '') {
					$sg_install['erreurs']['admin_password2'] = 'Les mots de passe doivent être identiques.';
				}
				$okPasserEtapeSuivante = false;
			}

			if ($okPasserEtapeSuivante === true) {

				// "Connexion" de l'utilisateur
				$_SESSION['user_id'] = $sg_install['admin_login'];

				// Installation du dictionnaire
				self::updateDictionnaire();

				// Installation des l'application de base
				self::MettreAJour(false);

				// Création du document de la personne dans l'annuaire
				$utilisateur = new SG_Utilisateur($sg_install['admin_login'], null, true);
				$utilisateur -> DefinirMotDePasse($sg_install['admin_password']);
				$utilisateur -> Enregistrer(false, false); // 1.3.2
				$_SESSION['@Moi'] = $utilisateur; // 2.1.1

				// Ajout de l'utilisateur aux profils par défaut
				$profils = array('ProfilUtilisateur', 'ProfilAdministrateur');
				$nbProfils = sizeof($profils);
				for ($i = 0; $i < $nbProfils; $i++) {
					$profil = new SG_Profil($profils[$i]);
					$profil -> AjouterUtilisateur($utilisateur);
				}
				
				// ajout des répertoires pour l'application			
				if(!file_exists(SYNERGAIA_PATH_TO_APPLI . '/nav/js/synergaia.js')) {
					$res = symlink(SYNERGAIA_PATH_TO_ROOT . '/nav', SYNERGAIA_PATH_TO_APPLI . '/nav');
				}
				// Proposer l'étape suivante
				$numPageInstallDemandee = '2';
			}
		}

		// Page 2 validée (modules complémentaires) => cherche les données envoyées
		if ($numPageInstallRecue === '2') {
			if (isset($_POST['sg_install_modules'])) {
				$sg_install['modules'] = $_POST['sg_install_modules'];
			}
			// Vérifie la validité des données reçues
			$okPasserEtapeSuivante = true;

			if ($okPasserEtapeSuivante === true) {
				// Installation des modules complémentaires demandés
				if ($sg_install['modules'] !== '') {
					$nbInstallModules = sizeof($sg_install['modules']);
					for ($i = 0; $i < $nbInstallModules; $i++) {
						$module = $sg_install['modules'][$i];
						$import = new SG_Import('ressources/packs/' . $module . '.json');
						$import -> Importer(SG_Dictionnaire::CODEBASE);
					}
				}

				// Proposer l'étape suivante
				$numPageInstallDemandee = '3';
			}
		}

		// Aucune page en cours => page initiale
		if ($numPageInstallRecue === '') {
			$numPageInstallDemandee = '0';
		}

		// Charge la page correspondante (1.3.2 vidercache seulement en fin étape 3)
		$ret = '';
		if ($numPageInstallDemandee === '0') {
			$ret = self::install_couchdb($sg_install);
		} elseif ($numPageInstallDemandee === '1') {
			$ret = self::install_admin($sg_install);
		}
		if ($numPageInstallDemandee === '2') {
			$ret = self::install_modules($sg_install);
		}
		if ($numPageInstallDemandee === '3') {
			$ret = self::install_activation($sg_install);
		}
		if ($ret !== '') {
			$debut = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" ><title>SynerGaïa - Installation</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="' . SG_Navigation::URL_THEMES . 'defaut/css/install.css"/><body><div class="entete"><h1>Installation SynerGaïa</h1>';
			$fin = '</fieldset></form></div><div class="pied">SynerGaïa - documentation : <a href="http://www.synergaia.eu">http://www.synergaia.eu</a></div></body></html>';
			$ret = $debut . $ret . $fin;
		}
		return $ret;
	}
	
	/**
	 * Recalcul des id @Utilisateurs
	 * @todo à supprimer si plus d'anciennes version
	 * @since 1.0.7
	 */
	static function recalcul1007() {
		$ret = '<h2>(1.0.7) Recalcul des ID des utilisateurs</h2><br>';
		$collec = SG_Annuaire::Utilisateurs();
		foreach($collec -> elements as $utilisateur) {
			$idAvant107 = $utilisateur -> getValeur('@IdAvant107', '');
			if ($idAvant107 === '' or $utilisateur -> doc -> codeDocument !== $utilisateur->identifiant) {
				$newUser = SG_Annuaire::getUtilisateur($utilisateur->identifiant);
				if ($newUser === false) {
					$newUserdoc  = new SG_DocumentCouchDB();
					$newUserdoc -> proprietes = $utilisateur -> doc -> proprietes;
					$newUserdoc -> codeBase = $utilisateur -> doc -> codeBase;
					$newUserdoc -> codeBaseComplet = $utilisateur -> doc -> codeBaseComplet;
					$newUserdoc -> proprietes['_id'] = $utilisateur->getValeur('@Identifiant');
					$newUserdoc -> codeDocument = $utilisateur->getValeur('@Identifiant');
					$newUserdoc -> revision = '';
					unset($newUserdoc -> proprietes['_rev']);
					$newUserdoc -> proprietes['@IdAvant107'] = $utilisateur -> doc -> codeDocument;
					$ret .= $newUserdoc -> getValeur('@IdAvant107') . ' => ' . $newUserdoc -> codeDocument;
					$ret .= $newUserdoc -> Enregistrer() -> estErreur() ? ' ERREUR problème':' : ok';
					$ret .= '<br>';
				}
			}
		}
		// suppression des anciens utilisateurs
		$collec = SG_Annuaire::Utilisateurs();
		foreach($collec -> elements as $utilisateur) {
			if ($utilisateur -> doc ->  codeDocument  !== $utilisateur->getValeur('@Identifiant')) {
				$code = $utilisateur -> doc ->  codeDocument;
				if ($utilisateur -> Supprimer() -> estVrai()) {
					$ret .= '<br> ' . $code . ' supprimé';
				} else {
					$ret .= '<br> ' . $code . ' : ERREUR problème à la suppression !';
				}
			}
		}
		// construction de la vue des utilisateurs
		$users = SG_Rien::Chercher('@Utilisateur');
		$oldusers = array();
		foreach($users -> elements as $user) {
			$oldusers[SG_Annuaire::CODEBASE . '/' .$user -> getValeur('@IdAvant107','?')] = $user-> getUUID(); //SG_Annuaire::CODEBASE . '/' .$user -> identifiant;
		}
		// parcours de tous les objets pour rechercher les champs @Utilisateur et changer l'identifiant
		$objets = SG_Dictionnaire::ObjetsDocument() -> elements;
		foreach($objets as $objet) {
			$champs = SG_Dictionnaire::getListeChamps($objet -> code,"@Utilisateur");
			if ($champs !== array()) {
				$docs = SG_Rien::Chercher($objet->code);
				foreach($docs -> elements as $element) {
					$modif = false;
					foreach($champs as $key => $champ) {
						if(isset($element -> doc -> proprietes[$key])) {
							$c = $element -> doc -> proprietes[$key];
							if(is_array($c)) {                                
								foreach($c as $keyc => $u) {
									if(isset($oldusers[$u])) {
										$c[$keyc] = $oldusers[$u];
										$modif = true;
									} elseif (isset($oldusers[SG_Annuaire::CODEBASE . '/' . $u])) {
										$c[$keyc] = $oldusers[SG_Annuaire::CODEBASE . '/' . $u];
										$modif = true;
									}
								}
							} else {
								if(isset($oldusers[$c])) {
									$c = $oldusers[$c];
									$modif = true;
								} elseif(isset($oldusers[SG_Annuaire::CODEBASE . '/' . $c])) {
									$c = $oldusers[SG_Annuaire::CODEBASE . '/' . $c];
									$modif = true;
								}
							}
							$element -> doc -> proprietes[$key]= $c;
						}
					}
					if($modif) {
						$element -> Enregistrer();
					}
				}
			}
		}
		$ret .= '<br>';
		return $ret;
	}

	/**
	 * suppression de @DocumentPrincipal et .@Principal des formules (sauf @Operation)
	 * @since 1.3.1 : ajout
	 */
	static function recalcul1201() {
		$ret = '<h2>(1.2.1) Simplification des formules (.@DocumentPrincipal et .@Principal)</h2><br>';
		$ret.= '<p>Attention : les formules incluses dans des textes paramétrés NE SONT PAS TRADUITES !</p>';
		$formule = '@Chercher("@DictionnairePropriete","",.@ValeursPossibles.@EstVide.@Non)';
		$formule.= '.@Concatener(@Chercher("@DictionnaireMethode","",.@Action.@EstVide.@Non))';
		$formule.= '.@Concatener(@Chercher("@ModeleOperation","",.@Phrase.@EstVide.@Non))';
		$collec = SG_Formule::executer($formule);
		$n = 0;
		foreach($collec -> elements as $element) {
			$texte = '';
			switch (getTypeSG($element)) {
				case '@DictionnairePropriete' :
					$nomPropriete = '@ValeursPossibles';
					break;
				case '@DictionnaireMethode' :
					$nomPropriete = '@Action';
					break;
				case '@ModeleOperation' :
					$nomPropriete = '@Phrase';
					break;
			}
			// changement
			$modif = false;
			$texte = $element -> getValeurPropriete($nomPropriete, '');
			if ($texte -> Contient('.@DocumentPrincipal.') -> estVrai()) {
				$element -> setValeur($nomPropriete, $texte -> Remplacer('.@DocumentPrincipal', '.'));
				$modif = true;
			}
			if ($texte -> Contient('.@Principal.') -> estVrai()) {
				$element -> setValeur($nomPropriete, $texte -> Remplacer('.@Principal', '.'));
				$modif = true;
			}
			if($modif) {
				$n++;
				$element -> Enregistrer();
				$ret.= '<br><b>"' . $element -> toString() . '"</b> modifié';
			}
		}
		$ret.='<p>Modification 1.2.1 terminée !</p>';
		return $ret;
	}

	/**
	 * Vérification de CouchDB puis installation des bases CouchDB
	 * @since 1.3.4 déplacé de install_0.php puis de SG_Installation
	 * @param array $sg_install tableau des paramètres de configuration
	 * @return string HTML de l'installation
	 * @todo gérer le multilingue
	 */
	static function install_couchdb($sg_install) {
		$ret = '<h2>Connexion à la base de données</h2></div>
<div class="contenu"><form action="" method="post">
<fieldset><input type="hidden" name="sg_install_etape" value="0"/>
<p><label for="sg_install_db_type">Type de base de données <abbr title="obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>SynerGaïa peut stocker ses données dans différents systèmes de base de données. Choisissez celui que vous souhaitez utiliser.</span></p>
<select name="sg_install_db_type"><option value=""';
		if ($sg_install['db_type'] === '') {$ret.= 'selected="selected" ';}
		$ret.= '>sélectionnez :</option><option value="CouchDB"';
		if ($sg_install['db_type'] === 'CouchDB') {$ret.= 'selected="selected" ';}
		$ret.= '>CouchDB</option></select>';
		if ($sg_install['erreurs']['db_type'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_type'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_db_host">Nom d\'hôte <abbr title="Saisie obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Saisissez le nom d\'hôte de la base de données. La valeur "localhost" ou "127.0.0.1" est généralement utilisée.</span></p></p>
<input type="text" name="sg_install_db_host" value="' . $sg_install['db_host'] . '"/>';
		if ($sg_install['erreurs']['db_host'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_host'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_db_login">Nom d\'utilisateur <abbr title="Saisie obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Saisissez le nom d\'utilisateur SynerGaïa connu par le système de base de données.</span></p></p>
<input type="text" name="sg_install_db_login" value=""/>';
		if ($sg_install['erreurs']['db_login'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_login'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_db_password">Mot de passe <abbr title="Saisie obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Saisissez le mot de passe associé à l\'utilisateur de la base de données.</span></p></p>
<input type="password" name="sg_install_db_password" autocomplete="off" value=""/>';
		if ($sg_install['erreurs']['db_password'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_password'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_db_prefix">Préfixe des noms des bases :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Saisissez le préfixe à utiliser pour nommmer les bases. Utile si plusieurs environnements SynerGaïa cohabitent sur le même serveur.</span>
</p></p><input type="text" name="sg_install_db_prefix" value="' . $sg_install['db_prefix'] . '"/>';
		if ($sg_install['erreurs']['db_prefix'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_prefix'] . '</span>';
		}
		$ret.= '</p><p><input type="submit" class="btn" value="Enregistrer les paramètres de connexion"/>';
		if ($sg_install['erreurs']['sg_install'] !== '') {$ret.= '<span class="erreur">' . $sg_install['erreurs']['sg_install'] . '</span>';}
		$ret.= '</p>';
		return $ret;
	}

	/**
	 * Installation de l'administrateur de l'application
	 * @since 1.3.4 reprise de install_1.php puis de SG_Installation
	 * @param array $sg_install tableau des paramètres de config
	 * @return string HTML de l'instalation
	 **/
	static function install_admin($sg_install) {
		$ret = '<h2>Compte administrateur</h2></div><div class="contenu"><form action="" method="post">
<fieldset><input type="hidden" name="sg_install_etape" value="1"/>
<p><label for="sg_install_admin_login">Votre identifiant <abbr title="Saisie obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Identifiant de l\'administrateur de l\'environnement SynerGaïa.</span></p>
<input type="text" name="sg_install_admin_login" value="' . $sg_install['admin_login'] . '"/>';
		if ($sg_install['erreurs']['admin_login'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['admin_login'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_admin_password">Votre mot de passe <abbr title="Saisie obligatoire">*</abbr> :</label>
<input type="password" name="sg_install_admin_password" autocomplete="off" value="' . $sg_install['admin_password'] . '"/>';
		if ($sg_install['erreurs']['admin_password'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['admin_password'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_admin_password2">Répétez votre mot de passe <abbr title="Saisie obligatoire">*</abbr> :</label>
<input type="password" name="sg_install_admin_password2" autocomplete="off" value=""/>';
		if ($sg_install['erreurs']['admin_password2'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['admin_password2'] . '</span>';
		}
		$ret.= '</p><p><input type="submit" class="btn" value="Créer le compte administrateur"/>';
		if ($sg_install['erreurs']['sg_install'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['sg_install'] . '</span>';
		}
		$ret.= '</p>';
		return $ret;
	}

	/**
	 * Installation des modules additionnels à partir de /ressources
	 * @since 1.3.4 repris de install_2.php puis de SG_Installation
	 * @param array $sg_install tableau des paramètres de config
	 * @return string HTML de l'instalation
	 */
	static function install_modules($sg_install) {
		$ret = '<h2>Modules complémentaires</h2></div><div class="contenu"><form action="" method="post">
<fieldset><input type="hidden" name="sg_install_etape" value="2"/>
<p><label for="sg_install_modules">Modules complémentaires :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Sélectionnez ici les modules à installer. Vous pourrez toujours ajouter des modules par la suite.</span></p>
<ul><li><input type="checkbox" name="sg_install_modules[]" value="socle" id="sg_install_module_socle" checked="checked" disabled="disabled" />
<label class="sg-checkbox" for="sg_install_module_socle">Socle minimal SynerGaïa</label></li>';
		// Cherche les modules complémentaires disponibles
		$cheminPacks = SYNERGAIA_PATH_TO_ROOT.'/ressources/packs';

		// Liste les noms des fichiers JSON du dossier
		$nomsFichiers = array();
		$dir = opendir($cheminPacks);
		while ($file = readdir($dir)) {
			// On cherche les fichiers "normaux"
			if ($file != '.' && $file != '..' && !is_dir($cheminPacks . '/' . $file)) {
				// On cherche les fichiers ".json"
				if (substr($file, -5) === '.json') {
					$nomsFichiers[] = $file;
				}
			}
		}
		closedir($dir);

		// Tri de la liste des fichiers
		sort($nomsFichiers);

		// Fabrique la liste des packs à partir des fichiers
		$packs = array();
		$nbFichiers = sizeof($nomsFichiers);
		for ($i = 0; $i < $nbFichiers; $i++) {
			$file = $nomsFichiers[$i];

			$pack = array();

			// Code du pack = nom de fichier
			$pack['code'] = substr($file, 0, -5);

			// Cherche le nom du pack
			$contenuTexte = file_get_contents($cheminPacks . '/' . $file);
			$contenuJSON = json_decode($contenuTexte, true);
			if (sizeof($contenuJSON) !== 0) {
				foreach ($contenuJSON as $key => $val) {
					$pack['nom'] = $key;
				}
				if ($pack['nom'] === '') {
					$pack['nom'] = $pack['code'];
				}
				$packs[] = $pack;
			}
		}

		// Génère la liste des cases à cocher pour les packs disponibles
		$nbPacks = sizeof($packs);
		for ($i = 0; $i < $nbPacks; $i++) {
			$idHTML = 'sg_install_module_' . $i;
			$html = '<li>' . PHP_EOL;
			$html .= ' <input type="checkbox" name="sg_install_modules[]" id="' . $idHTML . '" value="' . $packs[$i]['code'] . '" />' . PHP_EOL;
			$html .= ' <label class="sg-checkbox" for="' . $idHTML . '"/>' . $packs[$i]['nom'] . '</label>' . PHP_EOL;
			$html .= '</li>';
			$ret.= $html . PHP_EOL;
		}
		$ret.= '</ul></p><p><input type="submit" class="btn" value="Terminer l\'installation"/>';
		if ($sg_install['erreurs']['sg_install'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['sg_install'] . '</span>';
		}
		$ret.= '</p>';
		return $ret;
	}

	/**
	 * Activation après installation
	 * @since 1.3.4 repris de install_3.php puis de SG_Installation
	 * @param array $sg_install tableau des paramètres de config
	 * @return string HTML de l'instalation
	**/
	static function install_activation($sg_install) {
		$ret = '<h2>Récapitulatif</h2></div>
		<div class="contenu"><form action="" method="post"><fieldset><h2>L\'installation a été réalisée avec succès.</h2>
		<a class="lien_synergaia" href="' . SG_Navigation::URL_PRINCIPALE . '"> Accéder à ' . SG_Config::getConfig('SynerGaia_titre', 'SynerGaïa') . '</a>';
		$r = SG_Cache::viderCache();
		return $ret;
	}

	/**
	 * Crée les nouveaux répertoires (appli/, appli/config), les redirections (appli/img, appli/js, appli/themes), et les fichiers (config.php et index.php)
	 * @since 1.3.4 ajout
	 * @version 2.6 importé de SG_Installation ; création /var ; test si CouchDB_port existe
	 * @param string $pDir nom du répertoire de l'application
	 * @param string $pTitre titre de l'applicatoin (sinon "SynerGaïa (incomplet))")
	 * @return boolean true
	 */ 
	static function install_repertoires($pDir = '', $pTitre = '') {
		$ret = true;
		$newappli = substr(SYNERGAIA_PATH_TO_APPLI, 0, strrpos(SYNERGAIA_PATH_TO_APPLI, '/')) . '/' . $pDir;
		
		if(!file_exists($newappli)) {
			$res = mkdir($newappli);
		}
		if(!file_exists($newappli . '/index.php')) {
			$res = copy(SYNERGAIA_PATH_TO_APPLI . '/index.php', $newappli . '/index.php');
		}
		// écriture du fichier de config par défaut
		if(!file_exists($newappli . '/config')) {
			$res = mkdir($newappli . '/config/');
		}
		$configfic = $newappli . '/' . SG_Config::FICHIER;
		if(!file_exists($configfic)) {
			$file = fopen($configfic, 'w');
			if ($file !== false) {
				$r= fwrite($file, '<?php defined("SYNERGAIA_PATH_TO_APPLI") or die("403.14 - Directory listing denied.");' . PHP_EOL);
				fwrite($file, PHP_EOL);
				fwrite($file, '$SG_Config[\'SynerGaia_path_to_root\'] = \'' . SYNERGAIA_PATH_TO_ROOT . '\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'SynerGaia_titre\'] = \'SynerGaïa (incomplet)\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'SynerGaia_url\'] = \'' . SG_Config::getConfig('SynerGaia_url') . '\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'SynerGaia_theme\'] = \'defaut\';' . PHP_EOL);
				if (SG_Config::getConfig('CouchDB_port') !== '') {
					fwrite($file, '$SG_Config[\'CouchDB_port\'] = ' . SG_Config::getConfig('CouchDB_port') . ';' . PHP_EOL);
				}
				fwrite($file, '$SG_Config[\'CouchDB_host\'] = \'' . SG_Config::getConfig('CouchDB_host') . '\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'CouchDB_prefix\'] = \'' . $pDir . '_\';' . PHP_EOL);
				fwrite($file, 'ini_set(\'memory_limit\', \'128M\');' . PHP_EOL);
				fwrite($file, PHP_EOL);
				fwrite($file, '?>' . PHP_EOL);
				$res = fclose($file);
			}
		}
		// création du répertoire des classes
		if(!file_exists($newappli . '/var')) {
			$res = mkdir($newappli . '/var/');
		}
		if(!file_exists($newappli . '/nav/js/synergaia.js')) {
			$res = symlink(SYNERGAIA_PATH_TO_ROOT . '/nav', $newappli . '/nav');
		}
		header('Location: http://' . $_SERVER["HTTP_HOST"] . '/' . $pDir . '/index.php');
		return $ret;
	}

	/**
	 * Gère l'arrivée du traitement php
	 * Operation : ajout de typegeneralSG dans Operation, remplacement du nom de classe
	 * ModeleOperation : traduction php et création des classes
	 * DictionnaireObjet : traduction objet et création des classes
	 * @todo compléter Operation passées (?)
	 * @since 2.1 ajout
	 * @version 2.4 Compil d'abord les objets
	 * @version 2.6 importé de SG_Installation
	 * @return string HTML
	 */
	static function recalcul2100() {
		$ret = '<h2>Compilation PHP des Objets</h2><br>';
		// si nécessaire, création du répertoire des classes
		$dir = self::getRepertoireAppli() . '/var/';
		if(!file_exists($dir)) {
			$ret.= '<h3>Création du répertoire /var (ne devrait pas être nécessaire : ereur pendant l\'installation ?)</h3><br>';
			$res = mkdir($dir);
		}
		$ret.= self::compilationObjets();
		$ret.= '<h2>Compilation PHP des Modèles d\'opération</h2><br>';
		$ret.= self::compilationModelesOperation();
		return $ret;
	}

	/**
	 * Compilation des modèles d'opération
	 * @since 2.1 ajout
	 * @version 2.6 importé de SG_Installation
	 * @return string HTML
	 */
	static function compilationModelesOperation() {
		$ret = '<h3>Recompilation des modèles d\'opération</h3><ul>';
		$collec = SG_Rien::Chercher('@ModeleOperation');
		if ($collec instanceof SG_Erreur) {
			$ret.= $collec -> toHTML() -> texte;
		} else {
			foreach ($collec -> elements as $elt) {
				$r = $elt -> Enregistrer();
				if ($r instanceof SG_Erreur) {
					$ret.='<li><span class="sg-erreur">' . $elt -> getValeur('@Code') . ' : ' . $r -> getMessage() . '</span></li>';
				} else {
					$ret.='<li>' . $elt -> getValeur('@Code') . ' : ok</li>';
				}
			}
			$ret.= 'Terminé !';
		}
		return $ret;
	}

	/**
	 * Compilation des objets non-système (ne commencent pas par @)
	 * @since 2.1 ajout
	 * @version 2.6 importé de SG_Installation
	 * @version 2.7 test SG_Erreur
	 * @return string HTML
	 */
	static function compilationObjets() {
		$ret = '<h3>Recompilation des objets applicatifs (ne commençant par @)</h3><ul>';
		$collec = SG_Rien::Chercher('@DictionnaireObjet');
		if ($collec instanceof SG_Erreur) {
			$ret.= $collec -> toHTML() -> texte;
		} else {
			foreach ($collec -> elements as $elt) {
				$code = $elt -> getValeur('@Code','');
				if (substr($code, 0, 1) !== '@') {
					$r = $elt -> Enregistrer();
					if ($r instanceof SG_Erreur) {
						$ret.='<li><span class="sg-erreur">' . $code . ' : ' . $r -> getMessage() . '</span></li>';
					} else {
						$ret.='<li>' . $code . ' : ok</li>';
					}
				}
			}
		}
		$ret.= '</ul>Terminé !';
		return $ret;
	}
	// =========== fin de SG_Installation
	
	// =========== vient de l'anicenne classe SG_Update
	
	/**
	 * Mise à jour nécessaire du dictionnaire ?
	 * @since 1.1 ajout.
	 * @version 2.6 Déplacé de SG_UPDATE
	 * @return boolean
	 */
	static function updateLibellesNecessaire() {
		$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . self::LIBELLES_REFERENCE_FICHIER);
		$hash_dernier = SG_Config::getConfig(self::CLE_CONFIG_HASH_LIBELLES, '');
		return ($hash_actuel !== $hash_dernier);
	}

	/**
	 * Mise à jour nécessaire du dictionnaire ?
	 * @since 1.1 ajout.
	 * @version 2.6 Déplacé de SG_UPDATE
	 * @return boolean
	 */
	static function updateDictionnaireNecessaire() {
		$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . self::DICTIONNAIRE_REFERENCE_FICHIER);
		$hash_dernier = SG_Config::getConfig(self::CLE_CONFIG_HASH_DICTIONNAIRE, '');
		return ($hash_actuel !== $hash_dernier);
	}

	/**
	 * Mise à jour nécessaire des villes ?
	 * @since 1.1 ajout.
	 * @version 2.6 Déplacé de SG_UPDATE
	 * @return boolean
	 */
	static function updateVillesNecessaire() {
		$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . self::VILLES_REFERENCE_FICHIER);
		$hash_dernier = SG_Config::getConfig(self::CLE_CONFIG_HASH_VILLES, '');
		return ($hash_actuel !== $hash_dernier);
	}

	/**
	 * Mise à jour des objets/méthodes/propriétés du dictionnaire
	 * @since 1.1 ajout
	 * @version 2.6 Déplacé de SG_UPDATE ; Importer sans précision de la base ; instanceof
	 */
	static function updateDictionnaire() {
		journaliser('Mise a jour des objets : debut', false);
		// Vide le cache
		SG_Cache::viderCache();
		// Installe / met à jour le dictionnaire par défaut
		$import = new SG_Import(SYNERGAIA_PATH_TO_ROOT . '/' . self::DICTIONNAIRE_REFERENCE_FICHIER);
		$import -> appelEnregistrer = false;
		$ret = $import -> Importer(); //(SG_Dictionnaire::CODEBASE);
		if (! $ret instanceof SG_Erreur) {
			if ($ret -> estVrai() === true) {
				// Enregistre le hash du dictionnaire importé
				$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . self::DICTIONNAIRE_REFERENCE_FICHIER);
				$ret = SG_Config::setConfig(self::CLE_CONFIG_HASH_DICTIONNAIRE, $hash_actuel);
			}
		}
		journaliser('Mise a jour des objets : fin', false);
		return $ret;
	}

	/**
	 * Mise à jour des libellés des messages
	 * @since 1.1 ajout.
	 * @version 2.6 Déplacé de SG_UPDATE
	 * @return boolean
	 */
	static function updateLibelles() {
		journaliser('Mise a jour des libelles : debut', false);
		// Installe / met à jour les libellés par défaut
		$importLibelles = new SG_Import(SYNERGAIA_PATH_TO_ROOT . '/' . self::LIBELLES_REFERENCE_FICHIER);
		$importLibelles -> appelEnregistrer = false;
		$ret = $importLibelles -> Importer(SG_Libelle::CODEBASE);
		if (getTypeSG($ret) !== '@Erreur') {
			if ($ret -> estVrai() === true) {
				// Enregistre le hash des libellés importés
				$hash_actuel = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . self::LIBELLES_REFERENCE_FICHIER);
				$ret = SG_Config::setConfig(self::CLE_CONFIG_HASH_LIBELLES, $hash_actuel);
			}
		}
		journaliser('Mise a jour des libelles : fin', false);
		return $ret;
	}

	/**
	 * Mise à jour des villes françaises
	 * @since 1.1 ajout.
	 * @version 2.6 Déplacé de SG_UPDATE
	 * @return boolean
	 */
	static function updateVilles() {
		journaliser('Mise a jour des villes : debut', false);
		// Installe / met à jour les viles
		$importVilles = new SG_Import(SYNERGAIA_PATH_TO_ROOT . '/' . self::VILLES_REFERENCE_FICHIER);
		$importVilles -> appelEnregistrer = false;
		$ret = $importVilles -> Importer(SG_Ville::CODEBASE);
		if (getTypeSG($ret) !== '@Erreur') {
			if ($ret -> estVrai() === true) {
				// Enregistre le hash des villes importées
				$hash_nouveau = sha1_file(SYNERGAIA_PATH_TO_ROOT . '/' . self::VILLES_REFERENCE_FICHIER);
				$ret = SG_Config::setConfig(self::CLE_CONFIG_HASH_VILLES, $hash_nouveau);
			}
		}
		journaliser('Mise a jour des villes : fin', false);
		return $ret;
	}

	/**
	 * Méthode de migration d'une version précédente
	 * Cette méthode peut être exécutée plusieurs fois sans risque
	 * Elle supprime et recalcule les vues dont la sélection a été modifiée mais pas le nom.
	 * @since 1.0.6 ajout.
	 * @version 2.6 Déplacé de SG_UPDATE
	 * @version 2.7 test SG_Erreur
	 */
	static function updateVuesAllDocuments() {
		$ret = '';
		// recalcul des vues tous documents (retour d'objets)
		$listeObjets = SG_Dictionnaire::ObjetsDocument(false);
		if ($listeObjets instanceof SG_Erreur) {
			$ret = $listeObjets;
		} else {
			journaliser('Recalcul des vues : debut', false);
			$sgbd = $_SESSION['@SynerGaia'] -> sgbd;
			foreach($listeObjets -> elements as $objet) {
				if (is_object($objet)) {
					$code = $objet -> getValeur('@Code');
					$nomvue = $objet -> getValeur('@Base') . '/all_' . strtolower($code) . '_list';
					$vue = new SG_Vue($nomvue, '', '', true);
					if ($vue -> Existe() -> estVrai() === true) {
						$sgbd -> getAllDocuments($code, true);
						$ret .= $vue -> code . ' recalculée' . PHP_EOL;
					}
				}
			}
			journaliser('Recalcul des vues : fin', false);
		}
		return $ret;
	}
	//============ fin de SG_Update

	/**
	 * Retourne le chemin complet du répertoire applicatif avec un slash au bout
	 * @since 2.6
	 * @return string chemin
	 */
	static function getRepertoireAppli() {
		$prefixe = str_replace('_', '', SG_Config::getConfig('CouchDB_prefix', ''));
		$dir = substr(SYNERGAIA_PATH_TO_APPLI, 0, strrpos(SYNERGAIA_PATH_TO_APPLI, '/')) . '/' . $prefixe;
		return $dir;
	}

	/**
	 * Monte de version en fournissant le nouveau chemin des bibliothèques SynerGaia
	 * - mise à jour de config, 
	 * - remplacement du lien ../nav 
	 * - exécution de l'update
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pChemin chemin path to root
	 * @return string|SG_Erreur HTML de la montée de version
	 */
	static function Upgrade($pChemin = '') {
		$ret = '<h1>SynerGaïa : montée de version</h1>';
		$chemin = SG_Texte::getTexte($pChemin);
		if (getTypeSG($chemin) === SG_Erreur::TYPESG) {
			$ret.= $chemin -> Afficher;
		} elseif ($chemin === '') {
			$ret = SG_Libelle::getLibelle('0277');
		} elseif (!is_dir($chemin)) {
			$ret = SG_Libelle::getLibelle('0278');
		} else {
			$res.='<h2>Mise à jour du fichier config.php</h2>';
			SG_Config::setConfig('SynerGaia_path_to_root',$chemin);
			$res.='<h2>Mise à jour répertoire ../nav</h2>';
			rmdir('nav');
			define(SYNERGAIA_PATH_TO_ROOT, $chemin);
			$res.= symlink(SYNERGAIA_PATH_TO_ROOT . '/nav', 'nav');
			// application de la nouvelle version
			$res.= self::MettreAJour();
		}
		return $ret;
	}

	/**
	 * Traitement du assage à une version 2.6+
	 * Suppression de méthodes et propriétés inutiles
	 * 
	 * @since 2.6
	 * @return boolean true
	 */
	static function recalcul2600() {
		// objets
		$ret = '<h2>Supression d\'objets périmés ainsi que les propéiétés et méthodes rattachées</h2><ul>';
		$prop = array('@Update', '@Installation');
		foreach ($prop as $elt) {
			$obj = new SG_DictionnaireObjet($elt);
			$ret.='<li>' . $elt . '</li>';
			// sup proprietes
			$prop = $obj -> Proprietes();
			foreach($prop -> elements as $p) {
				$ret.='<li>' . $p -> getValeur('@Code','??') . '</li>';
				$p -> Supprimer();
			}
			// sup methodes
			$meth = $obj -> Methodes();
			foreach($meth -> elements as $m) {
				$ret.='<li>' . $m -> getValeur('@Code','??') . '</li>';
				$m -> Supprimer();
			}
			// sup objet
			$obj -> Supprimer();
		}
		$ret.='</ul>';

		// propriétés
		$ret.= '<h2>Supression de propriétés périmées</h2><ul>';
		$prop = array('@Parametre.@Valeur', '@Parametre.@Titre', '@Parametre.@ValeurType');
		foreach ($prop as $elt) {
			$p = new SG_DictionnairePropriete($elt);
			$ret.='<li>' . $elt . '</li>';
			$p -> Supprimer();
		}
		$ret.='</ul>';

		// méthodes
		$ret.= '<h2>Supression de méthodes périmées</h2><ul>';
		$meth = array('@Parametre.@Definir', '@Parametre.@Lire');
		foreach ($meth as $elt) {
			$m = new SG_DictionnaireMethode($elt);
			$ret.='<li>' . $elt . '</li>';
			$m -> Supprimer();
		}
		$ret.='</ul>';
		return $ret;
	}

	// Complément de classe spécifique à l'application (créé par la compilation)
	use SG_SynerGaia_trait;
}
?>
