<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* SG_Base : Classe de gestion d'une base de données logique
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Base_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Base_trait.php';
} else {
	trait SG_Base_trait{};
}
class SG_Base extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Base';
	public $typeSG = self::TYPESG;

	// Code de la base par défaut
	const CODEBASE = 'synergaia';

	// Base physique associée
	public $base;
	
	// Code de base physique associée
	public $codeBase;
	
	//1.3.4 mode d'accès
	public $acces = '';

	/** 1.1 Domino et DictionnaireBase ; 1.3.4 maj -> acces
	 * Construction de l'objet
	 *
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
		//	if (!isset($_SESSION['@SynerGaïa'] -> domino)) {
		//		$_SESSION['@SynerGaïa'] -> domino = new SG_DominoDB();
		//	}
			$this -> codeBase = $docbase -> getValeur('@Code');
			$this -> base = new SG_BaseDominoDB($docbase -> getValeur('@AdresseIP'), $docbase -> getValeur('@Administrateur'), $docbase -> getValeur('@MotDePasse'));		
			$this -> acces = 'domino';
		} else {
			$this -> erreurs[] = new SG_Erreur('0027', $acces . '=>' . $pCodeBase);
		}
	}
	/** 1.0.6
	 * Retourne une chaine pour affichage (code de la base)
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

	/** 1.06 ajout ; 2.0 libelle 0109
	 * Sauvegarder le contenu de la base dans un fichier proposé en lien
	 *
	 * @return @Texte Nom du fchier de sauvegarde ou @Erreur message d'erreur
	 */
	function Sauvegarder() {
		$ret = '';

		// Nom de fichier temporaire
		$nomFichier = 'synergaia_save_' . $this -> base -> codeBase.'_'.date('Y_m_d');
		$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $nomFichier . '.json';

		$sauvegardeOK = $this -> base -> Sauvegarder($tmpFile);

		if ($sauvegardeOK -> estVrai() === true) {
			$ret = new SG_Texte($tmpFile);
		} else {
			$ret = new SG_Erreur('0109', $nomFichier);
		}
		return $ret;
	}

	/**
	* Sauvegarder le contenu de la base dans un fichier proposé en lien
	* 
	* @param indefini nom du fichier à restaurer
	*
	* @return @Texte si ok ou @Erreur + message si problème
	*/
	function Restaurer($nomFichier='') {
		$ret = '';
		$type = getTypeSG($nomFichier);
		$fichier = $nomFichier;
		if ($type !== 'string') {
			if ($type === '@Formule') {
				$fichier = $nomFichier -> calculer();
				$type = getTypeSG($fichier);
			}
			$fichier = new SG_Texte($fichier);
			$fichier = $fichier -> toString();
		}
		$restaureOK = $this -> base -> Restaurer($fichier);
		if (getTypeSG($restaureOK) === 'string') {
			$ret = new SG_Texte($restaureOK);
		} else {
			$ret = $restaureOK;
		}

		return $ret;
	}
	/** 1.1 : ajout
	* Compacte la base
	*/
	public function Compacter() {
		$ret = new SG_Texte($this -> base -> Compacter());
		return $ret;
	}
	/** 1.3.0 ajout ; 1.3.1 correctif
	* Chercher selon le filtre, indépendamment du @Type (uniquement couchdb)
	*/
	public function Chercher($pFiltre = '') {
		$ret = new SG_Collection();
		if (getTypeSG($this -> base) === '@BaseCouchDB') {
			$vue = new SG_VueCouchDB();
			$vue -> code = $this -> base -> codeBaseComplet . '/_all_docs';
			$ret = $vue -> Contenu('', $pFiltre, true);
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Base_trait;
}
?>
