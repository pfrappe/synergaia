<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de gestion d'une personne
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Personne_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Personne_trait.php';
} else {
	trait SG_Personne_trait{};
}
class SG_Personne extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Personne';
	public $typeSG = self::TYPESG;
	
	// code base par défaut
	const CODEBASE = 'synergaia_personnes';

	public $code;
	
	/** 1.1
	* Construction de l'objet
	*
	* @param string $pCode code éventuel de la personne (permet la différenciation des homonymes)
	* @param indefini $pTableau tableau éventuel des propriétés du document CouchDB ou SG_DocumentCouchDB
	* @param string $pNom nom de famille
	* @param string $pPrenom prénom usuel 
	*/
	public function __construct($pCode = '', $pTableau= null, $pNom = '', $pPrenom = '') {
		$tmpCode = new SG_Texte($pCode);
		$base = self::CODEBASE;
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($pCode, $pTableau);
		$this -> code = $this -> getValeur('@Code');
		if ($pNom !== '') {
			$nom = new SG_Texte($pNom);
			$this -> setValeur('@Nom', $nom -> texte);
		}
		if ($pPrenom !== '') {
			$prenom = new SG_Texte($pPrenom);
			$this -> setValeur('@Prenom', $prenom -> texte);
		}
	}
	/** 1.1
	*/
	public function Titre() {
		return $this -> getValeur('@Nom') . ' ' . $this -> getValeur('@Prenom');
	}
	/** 1.1
	*/
	public function Age() {
		$ret = new SG_VraiFaux(false);
		$dt = $this -> getValeur('@NaissanceDate', false);
		if ($dt !== false) {
			$ret = $dt -> Age();
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Personne_trait;
}
?>
