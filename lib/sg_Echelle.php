<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de getion d'une échelle de mesure
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Echelle_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Echelle_trait.php';
} else {
	trait SG_Echelle_trait{};
}
class SG_Echelle extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Echelle';
	public $typeSG = self::TYPESG;
	
	// base de stockage par défaut
	const CODEBASE = 'synergaia_echelles';
	
	//unité de base
	public $uniteDeBase = '';
	
	// tableau de correspondance entre unités
	// [unite contenant => [coeff, unitecomposant]]
	public $conversions = array();
	
	/** 1.1 ajout
	* Construction de l'échelle de mesure
	*
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
	/** 1.1 ajout
	* Liste des unités employées
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
