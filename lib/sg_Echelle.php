<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Echelle */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Echelle_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Echelle_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Echelle_trait{};
}

/** SynerGaia
 * Classe SynerGaia de getion d'une échelle de mesure
 * @version 2.1.1
 */
class SG_Echelle extends SG_Document {
	/** string Type SynerGaia */
	const TYPESG = '@Echelle';
	
	/** string base de stockage par défaut */
	const CODEBASE = 'synergaia_echelles';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** string code unité de base */
	public $uniteDeBase = '';
	
	/** @var array tableau de correspondance entre unités
	 * [unite contenant => [coeff, unitecomposant]]
	 */
	public $conversions = array();
	
	/**
	 * Construction de l'échelle de mesure
	 * @since 1.1 ajout
	 * @param string|SG_Texte|SG_Formule $pRefDocument
	 * @param array $pTableau
	 */
	public function __construct ($pRefDocument = null, $pTableau = null) {
		if (strpos($pRefDocument, '.nsf/') !== false) {
			$this -> initDocumentDominoDB($pRefDocument);
		} else {
			$this -> initDocumentCouchDB($pRefDocument, $pTableau);
		}
		$unite = $this -> getValeur('@UniteDeBase','');
		if (getTypeSG($unite) !== 'string') {
			$unite = $unite -> toString();
		}
		$this -> uniteDeBase = $unite;
		$unites = $this -> getValeur('@Unite','');
		foreach ($unite as $key => $tab) {
			$qte = new SG_Nombre($tab[0]);
			$qte = $qte -> valeur;
			$un = new SG_Texte($tab[1]);
			$un = $un -> texte;
			$this -> conversions[$key] = array($qte, $un);
		}
	}

	/**
	 * Liste des unités employées
	 * @since 1.1
	 * @return SG_Collection
	 */
	function Unites() {
		$ret = new SG_Collection();
		$ret -> elements[] = $this -> uniteDeBase;
		foreach ($this -> conversions as $key => $tab) {
			$ret -> elements[] = $tab[1];
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Echelle_trait;
}
?>
