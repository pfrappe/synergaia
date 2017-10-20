<?php
/** fichier de gestion d'une base logique SynerGaïa */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
 
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Base_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Base_trait.php';
} else {
	/** trait vide par défaut pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Base_trait{};
}

/**
 * SG_Base : Classe de gestion d'une base de données logique
 * @version 2.6
 */
class SG_Base extends SG_Objet {
	/** string Type SynerGaia '@Base' */
	const TYPESG = '@Base';

	/** string Code de la base par défaut */
	const CODEBASE = 'synergaia';
	
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** SG_BaseCouchDB|SG_BaseDomino Base physique associée */
	public $base;
	
	/** string Code de base physique associée */
	public $codeBase;
	
	/** string mode d'accès d'une base sécurisée
	 * @since 1.3.4
	 */
	public $acces = '';

	/**
	 * Construction de l'objet
	 * @since 0.0
	 * @version 1.3.4 maj -> acces
	 * @param string $pCodeBase code de la base
	 * @param boolean $pCreerSiInexistante creer la base si besoin
	 */
	function __construct($pCodeBase = null, $pCreerSiInexistante = false) {
		$acces = 'couchdb';
		$docbase = new SG_DictionnaireBase($pCodeBase);
		if ($docbase -> Existe() -> estVrai()) {
			$acces = $docbase -> getValeur('@Acces');
		}
		if ($acces === 'couchdb' or $acces === '' or $acces === null) {
			$this -> base = new SG_BaseCouchDB($pCodeBase, $pCreerSiInexistante);
			$this -> codeBase = $this -> base -> codeBase;
			$this -> acces = 'couchdb';
		} elseif ($acces === 'domino') {
			$this -> codeBase = $docbase -> getValeur('@Code');
			$this -> base = new SG_BaseDominoDB($docbase -> getValeur('@AdresseIP'), $docbase -> getValeur('@Administrateur'), $docbase -> getValeur('@MotDePasse'));		
			$this -> acces = 'domino';
		} else {
			$this -> erreurs[] = new SG_Erreur('0027', $acces . '=>' . $pCodeBase);
		}
	}

	/**
	 * Retourne une chaine pour affichage (code de la base)
	 * @since 1.0.6
	 * @return string code de la base
	 */
	function toString() {
		return $this -> codeBase;
	}

	/**
	* Supprime la base
	*
	* @return SG_VraiFaux
	*/
	function Supprimer() {
		return $this -> base -> Supprimer();
	}

	/**
	 * La base existe-t-elle ?
	 *
	 * @return SG_VraiFaux existe ou pas
	 */
	function Existe() {
		return $this -> base -> Existe();
	}

	/**
	 * Créé un nouveau document dans la base
	 *
	 * @return SG_DocumentCouchDB nouveau document physique
	 */
	function CreerDocument() {
		$doc = $this -> base -> CreerDocument();
		$sgDoc = new SG_Document();
		$sgdoc -> doc = $doc;
		return $sgDoc;
	}

	/**
	 * Sauvegarder le contenu de la base dans un fichier proposé en lien
	 * @since 1.06 ajout
	 * @version 2.0 libelle 0109
	 * @param string|SG_Texte|SG_Formule $pDir
	 * @param string|SG_Texte|SG_Formule $pPrefixe
	 * @return SG_Texte|SG_Erreur Nom du fchier de sauvegarde ou message d'erreur
	 */
	function Sauvegarder($pDir = '', $pPrefixe = '') {
		$ret = '';
		$ret = $this -> base -> Sauvegarder($pDir, $pPrefixe);
		if(is_array($ret)) {
			$collec = new SG_Collection();
			$collec -> elements = $ret;
			$ret = $collec;
		}
		return $ret;
	}

	/**
	* Sauvegarder le contenu de la base dans un fichier proposé en lien
	* 
	* @param indefini $nomFichier nom du fichier à restaurer
	* @return @Texte si ok ou @Erreur + message si problème
	*/
	function Restaurer($nomFichier = '') {
		$ret = '';
		$type = getTypeSG($nomFichier);
		$fichier = $nomFichier;
		if ($type !== 'string') {
			if ($type === '@Formule') {
				$fichier = $nomFichier -> calculer();
			}
			$fichier = SG_Texte::getTexte($fichier);
		}
		$restaureOK = $this -> base -> Restaurer($fichier);
		if (getTypeSG($restaureOK) === 'string') {
			$ret = new SG_Texte($restaureOK);
		} else {
			$ret = $restaureOK;
		}
		return $ret;
	}

	/** 
	 * Compacte la base
	 * @since 1.1 : ajout
	 * @return SG_Texte
	 */
	public function Compacter() {
		$ret = new SG_Texte($this -> base -> Compacter());
		return $ret;
	}

	/**
	 * Chercher selon le filtre, indépendamment du @Type (uniquement couchdb)
	 * Cette recherche permet de créer une collection de tout ou partie des documents d'une base indépendamment d'un @Type
	 * @since 1.3.0 ajout
	 * @version 2.6 correction codeBase, codeBaseComplet
	 * @param SG_Formule $pFiltre filtre sur lma recherche
	 * @return SG_Collection
	 */
	public function Chercher($pFiltre = '') {
		$ret = new SG_Collection();
		if (getTypeSG($this -> base) === SG_BaseCouchDB::TYPESG) {
			$vue = new SG_VueCouchDB();
			$vue -> codeBase = $this -> base -> codeBase;
			$vue -> codeBaseComplet = $this -> base -> codeBaseComplet;
			$vue -> code = $this -> base -> codeBaseComplet . '/_all_docs/';
			$ret = $vue -> Contenu('', $pFiltre, true);
		}
		return $ret;
	}

	/**
	 * collection des infos sur la base
	 * @since 2.4 ajout
	 * @return SG_Collection tableau des infos
	 **/
	public function Infos() {
		$ret = $this -> base -> Infos();
		return $ret;
	}

	/**
	 * Effectue la formule sur chaque document de la base
	 * @since 2.6
	 * @param SG_Formule $pFormule formule à exécuter sur chaque document
	 * @return SG_Nombre|SG_Erreur nombre de documents traités ou erreur
	 */
	function PourChaque($pFormule = '') {
		if (! $pFormule instanceof SG_Formule) {
			$ret = new SG_Erreur('0289');
		} else {
			
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Base_trait;
}
?>
