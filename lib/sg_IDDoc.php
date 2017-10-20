<?php
/** SYNERGAIA fichier pour le traitement de l'objet @IDDoc */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_IDDoc_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_IDDoc_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe */
	trait SG_IDDoc_trait{};
}

/**
 * SG_IDDoc : classe SynerGaia de gestion d'un ID de document CouchDB
 * @todo utiliser partout où c'est nécessaire
 * @since 2.4
 */
class SG_IDDoc extends SG_Objet{
	/** string Type SynerGaia '@IDDoc'*/
	const TYPESG = '@IDDoc';

	/** string séparateur appli-base ('_' si inf version 3.0, sinon ':') */
	const SEP = '_';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** string Code de la base */
	public $codeBase = '';

	/** string Code complet de la base avec prefixe */
	public $codeAppli;

	/** string Code du document (en fait l'id physique) */
	public $codeDocument = '';
	
	/**
	 * Construction de l'objet. Si contradiction entre appli de l'id et paramètre, c'est l'id qui prime)
	 * 
	 * @since 2.4 ajout
	 * @param string|SG_Document|SG_Texte|SG_Formule $pQuelqueChose : SG_Document ou string ou SG_Texte (iddoc ou base/iddoc ou appli:base/iddoc)
	 * @param string|SG_Texte|SG_Formule $pAppli : string ou SG_Texte (appli d'origine de l'objet) si non fourni, appli en cours
	 */
	function __construct($pQuelqueChose = '', $pAppli = '') {
		// code appli en paramètre ? sinon appli en cours
		$appli = $pAppli;
		$this -> codeAppli = SG_Texte::getTexte($pAppli);
		if ($this -> codeAppli === '') {
			if (isset($_SESSION['page']['application'])) {
				$this -> codeAppli = $_SESSION['page']['application'];
			} else {
				$this -> codeAppli = SG_Config::getCodeAppli();
			}
		}
		if (is_object($pQuelqueChose) and $pQuelqueChose -> DeriveDeDocument()) {
			// on a passé un @Document
			$this -> codeBase = $pQuelqueChose -> doc -> codeBase;
			$this -> codeDocument = $pQuelqueChose -> doc -> codeDocument;
		} else {
			// on a passé un texte ou une formule
			$id = SG_Texte::getTexte($pQuelqueChose);
			// extraction code appli ?
			$ipos = strpos($id, self::SEP);
			if ($ipos !== false) {
				$this -> codeAppli = substr($id, 0, $ipos);
				$id = substr($id, $ipos + 1);
			}
			// $id ne contient plus que base/iddoc
			$ipos = strpos($id, '/');
			if ($ipos !== false) {
				$this -> codeBase = substr($id, 0, $ipos);
				$this -> codeDocument = substr($id, $ipos + 1);
			} else {
				$this -> codeDocument = $id;
			}
		}
	}

	/**
	 * Code complet de la base (appli_codeBase)
	 * @since 2.4
	 * @return string
	 */
	function codeBaseComplet() {
		$ret = $this -> codeAppli . self::SEP . $this -> codeBase;
		return $ret;
	}

	/**
	 * Code complet de la base (appli_codeBase)
	 * @since 2.4
	 * @return string
	 */
	function Texte() {
		return new SG_Texte($this -> getTexteComplet());
	}

	/**
	 * Code complet de la base (appli_codeBase)
	 * @since 2.4
	 * @return string
	 */
	function toHTML() {
		$ret = '<span class="sg-iddoc">' . $this -> getTexteComplet() . '</span>';
		return new SG_HTML($ret);
	}

	/**
	 * Code id sans l'appli
	 * @since 2.4
	 * @return string
	 */
	function getTexte() {
		return $this -> codeBase . '/' . $this -> codeDocument;
	}

	/**
	 * Code id complet
	 * @since 2.4
	 * @return string
	 */
	function getTexteComplet() {
		return $this -> codeAppli . self::SEP . $this -> codeBase . '/' . $this -> codeDocument;
	}

	/**
	 * Calcule le code html pour l'afficher sur le navigateur
	 * @since 2.4
	 * @return SG_HTML
	 */
	function Afficher() {
		return $this -> Document() -> toHTML();
	}

	/**
	 * Code complet de la base (appli_codeBase)
	 * @since 2.4
	 * @return string
	 */
	function toString() {
		return $this -> getTexte();
	}

	/**
	 * Retourne le document associé à l'id
	 * 
	 * @since 2.4
	 * @return  SG_Document
	 */
	function Document() {
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($this -> codeBase . '/' . $this -> codeDocument);
		if (isset($this -> proprietes)) {
			$ret -> proprietes = $this -> proprietes;
		}
		return $ret;
	}

	/**
	 * retourne l'uid du document visé
	 * 
	 * @since 2.6
	 * @return string l'uid
	 */
	function getUUID() {
		return $this -> codeBase . '/' . $this -> codeDocument;
	}
	// 2.4 complément éventuel de classe créée par compilation (voir début de ce fichier)
	use SG_IDDoc_trait;	
}
?>
