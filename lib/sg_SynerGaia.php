<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_SynerGaia : Classe décrivant l'application et gérant les objets et paramètres permaments
*/
class SG_SynerGaia extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@SynerGaia';
	public $typeSG = self::TYPESG;

	// version SynerGaïa
	const VERSION = '2.3';
	const NOVERSION = 2300;
	
	// canal vers la base de données de SynerGaïa (SG_CouchDB)
	public $sgbd;

	// serveur domino accessible (1.1)
	public $domino;
	
	// utilisateur en cours
	public $moi;
	
	// 2.1 compilateur pour les formules (SG_Compilateur)
	public $compilateur;
	
	// 2.1 messages d'erreur
	public $libelles = array();
	
	// 2.3 navigateur (SG_Navigation)
	private $navigation;

	// canal vers la base de données (par défaut un @CouchDB)
	// !! doit pouvoir s'exécuter avant l'initialisation du cache !! (voir core/ressources.php)
	public function __construct() {
		$this -> sgbd = new SG_CouchDB();
		$this -> navigation = new SG_Navigation();
	}

	/** 1.0.7 ; 1.3.2 static
	* Determine l'identifiant de la personne connectée
	*
	* @return string identifiant
	* @level 0 (SG_Utilisateur)
	*/
	static function IdentifiantConnexion() {
		$ret = '';
		if (isset($_SESSION['@Moi']) and getTypeSG($_SESSION['@Moi']) === '@Utilisateur') {
			$ret = $_SESSION['@Moi'] -> identifiant;
		}
		return $ret;
	}

	/** 1.1 ; 2.2 php.ini
	* Determine la version de SynerGaïa exécutée
	* @param $pTout (@VraiFaux) : par défaut @Faux. Si @Vrai, afficher le détail des version de tous les modules utilisés
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

	/** 1.1 $pType ; 2.1 retour collection si '?'
	 * Vide le cache SynerGaïa
	 *
	 * @return @VraiFaux
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
	
	function Lib($pFilename = '') {
		$ret = new SG_Erreur('Cette opération vous est interdite !');
		if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
			$filename = $pFilename;
			if (getTypeSG($pFilename) === '@Formule') {
				$filename = $pFilename -> calculer() -> toString();
			}
			if (getTypeSG($filename) !== 'string') {
				$filename = $filename -> toString();
			}
			$txt = @file_get_contents(SYNERGAIA_PATH_TO_ROOT . '/lib/' . $filename);
			$ret = new SG_HTML('<pre>' . htmlentities($txt, ENT_QUOTES, 'UTF-8') . '</pre>');
		}
		return $ret;
	}

	function getAllDocuments($pModele = '') {
		return $this -> sgbd -> getAllDocuments($pModele);
	}
	/* 1.0.7 ; 1.3.0 err 53 ;
	 * getChercherDocuments : cherche des documents selon le type de base de données du dictionnaire
	 */	
	function getChercherDocuments($pCodeBase = '', $pTypeObjet = '', $pCodeObjet = '', $pFiltre = '') {
		return $this -> sgbd -> getChercherDocuments($pCodeBase, $pTypeObjet, $pCodeObjet, $pFiltre);
	}
	/** 2.1 getObjetByID
	* 1.1 : si pQuelqueChose déja objet, le retourne tel quel ; si quelquechose précise champ, rendre l'objet correspondant
	* getObjet : cherche un objet de type @Document dans une base à partir de pQuelqueChose (base / document).
	* Dans le cas d'un objet système (genre @Profil, @Utilisateur, etc, il faut d'abord
	* lire le document couchdb pour savoir de quel type sera l'objet.
	* Donc on crée d'abord un DocumentCouchDB, puis l'objet système.
	* @param (string) $pQuelqueChose : uuid du document de l'objet (codebase/iddocument)
	*/
	function getObjet($pQuelqueChose = '', $pTypeDefaut = '') {
		$ret = new SG_Rien();
		if (is_object($pQuelqueChose)) {
			$ret = $pQuelqueChose;
		} elseif (gettype($pQuelqueChose) === 'string') {
			$index = explode('/', $pQuelqueChose);
			if(!isset($index[1])) {
				$ret = new SG_Erreur('0053'); // paramètre indisponible
			} else {
				$codedocument = $index[0] . '/' . $index[1];
				$ret = $this -> sgbd -> getObjetByID($codedocument); // 2.1
				if (sizeof($index) >= 3) { // 1.1 on ne cherche qu'une proriété de l'objet
					$type = getTypeSG($ret);
					if ($type === '@Erreur') {
						$ret = 'ici ' . $ret -> getLibelle();
					} elseif ($type === '@Repertoire') {
						$ret = $ret -> AllerA($index[2]);
					} else {
						$ret = $ret -> getValeurPropriete($index[2]);
					}
				}
			}
		}
		return $ret;
	}
	/* 1.0.7
	*/
	function getObjetsLies($pDocument, $pModele, $pChamp) {
		return $this -> sgbd -> getObjetsLies($pDocument, $pModele, $pChamp);
	}
	/* 1.0.7
	*/	
	function chercherVague($pTexte = '', $pModeles, $pChamps) {
		return $this -> sgbd -> chercherVague($pTexte = '', $pModeles, $pChamps);
	}
	/** 1.0.7
	 * getDocumentsFromTypeChamp
	 */
	function getDocumentsFromTypeChamp($pType = '', $pChampCle = '', $pValeur = '') {
		return $this-> sgbd -> getDocumentsFromTypeChamp($pType, $pChampCle, $pValeur);
	}
	/** 1.0.6 ; 1.3.1 base des formulaires
	 * BasesSysteme : collection des bases système de SynerGaïa
	 * @param SG_VraiFaux $pAvecOperations : ajouter ou non la base des opérations
	 * @return SG_Collection contenant des SG_Base
	 */
	function BasesSysteme($pAvecOperations = false) {
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
	
	/** 1.0.6
	 * Bases : collection des bases de SynerGaïa
	 * @param SG_VraiFaux $pAvecOperations : ajouter ou non la base des opérations
	 * @return SG_Collection contenant des SG_Base
	 */
	function Bases($pAvecOperations = false) {
		$ret = new SG_Collection();
		$collec = SG_Dictionnaire::ObjetsDocument();
		$ret = self::BasesSysteme($pAvecOperations);
		foreach($collec -> elements as $objet) {
			$base = $objet -> getValeur('@Base');
			if(! array_key_exists($base, $ret -> elements)) {
				$ret -> elements[$base] = new SG_Base($base);
			}
		}
		return $ret;
	}
	/** 1.1 : ajout
	* Suppression de toutes les opérations antérieures à une date donnée.
	* Mise au statut 'terminé' des opérations plus vieilles que 5 jours et 
	* @formule :
	*  @Chercher("@Operation","",.@DateCreation.@Date.@InferieurA(@Aujourdhui.@Ajouter($1.@MultiplierPar(-1)))).@PourChaque(.@Supprimer)");
	*  @Chercher("@Operation","",.@DateCreation.@Date.@InferieurA(@Aujourdhui.@Ajouter(-5)).@Et(.@Statut.@Egale("encours"))).@PourChaque(.@Terminer)");
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
	/** 1.1 ajout
	*/
	function RepliquerAvec ($pAdresse = '', $pContinue = false) {
		$ret = $this -> sgbd -> RepliquerAvec($pAdresse, $pContinue);
		return $ret;
	}
	/** 1.1 ajout
	*/
	function Test() {
		return SG_Navigation::fileUploader();
	}
	/** 1.3.1 ajout ; 2.2 met à jour le titre dans config
	* Retourne ou met à jour le titre de l'application
	* @param texte $pTitre : titre remplaçant le titre actuel de l'application
	* @return (@Texte ou @SynerGaia) : si param vide, titre, sinon $sthis
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
	/** 1.3.1 ajout
	* Retourne ou met à jour le logo de l'application
	**/
	function Logo($pLogo = '') {
		if ($pLogo !== '') {
			$logo = $pLogo;
		} else {		
			$logo = SG_Config::getConfig('SynerGaia_logo', SG_Navigation::URL_THEMES . 'defaut/img/favicon.png');
		}
		return $logo;
	}
	/** 1.3.1 ajout
	* Liste des paquets disponibles dans l'application
	* @param (@Texte) $pOrigine : "" tous (defaut), "s" standard SynerGaïa, "l" paquets locaux
	* @return (@Collection) les paquets demandés
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
	/** 1.3.4 ajout
	* Crée une nouvelle application dépendante de path_to_root actuel. Ne fonctionne que s'il y a déjà une application installée
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
				$ret = SG_Installation::install_repertoires($dir);
				if(getTypeSG($ret) !== '@Erreur') {					
					header('Location: http://' . $_SERVER["HTTP_HOST"] . '/' . $dir . '/index.php');
					die();
				}
			}
		}
		return $ret;
	}
	/** 2.0 ; 2.3 vider debug
	* Initialisation des ressources nécessaires
	**/
	static function initialiser() {
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
		require_once SYNERGAIA_PATH_TO_ROOT . '/core/simple_html_dom.php'; // 1.3.1 ajout

		// 1.3.0 chargement PHPExcel par défaut /var/lib/phpexcel sinon modifier $config.php
		$dirphpexcel = SG_Config::getConfig('phpexcel', '/var/lib/phpexcel/');
		if ($dir = @opendir($dirphpexcel)) {
			require($dirphpexcel .'Classes/PHPExcel.php');
		}
		// démarrage de la session
		session_start();
		$_SESSION['debug']['texte'] = '';// à partir d'ici on peut utiliser tracer()
		set_error_handler('errorHandler', E_ALL);// dans core/socle.php
		set_exception_handler('exceptionHandler');// dans core/socle.php
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
		if(!isset($_SESSION['@SynerGaia'])) {
			$_SESSION['@SynerGaia'] = new SG_SynerGaia();
		}
		SG_Cache::initialiser();
	}
	/** 2.1 ajout
	* recherche un message d'erreur standard  (4 chifres) et le met en cache ici
	* @param (string) $pNumero : numéro du message d'erreur
	**/
	function getLibelle($pNumero) {
		$ret = '';
		if (! isset($this -> libelles[$pNumero])) { 
			if (strlen($pNumero) !== 4) {
				$libelle = '(Pas d\'informations supplémentaires car le libellé n\'a pas été trouvé dans la base des libellés). Infos complémentaires : %s';
			} else {
				$baseLibelles = new SG_Base(SG_Libelle::CODEBASE);
				if ($baseLibelles -> Existe() -> estVrai()) {
					$doc = SG_Rien::Chercher('@Libelle', $pNumero) -> Premier();
					if (getTypeSG($doc) !== '@Libelle') {
						$libelle = '(' . $pNumero . ' Pas d\'informations supplémentaires car le libellé n\'a pas été trouvé dans la base des libellés). Infos complémentaires : %s';
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
	/** 2.3 ajout
	* adresse physique du serveur sur lequel tourne SynerGaïa. C'est l'adresse dans le réseau
	* @return @Texte : l'adresse ipV4 du serveur
	**/
	static function AdresseIP () {
		return new SG_Texte($_SERVER['SERVER_ADDR']);
	}
	/** 2.3 ajout
	* retourne le navigateur actuel
	* @return SG_Navigation
	**/
	function Navigation() {
		return $this -> navigation;
	}
}
?>
