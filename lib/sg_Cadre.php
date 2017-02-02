<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de traitement des formulaires et cadres du navigateur
* (correspond à une <div> pouvant contenir une <form>)
*/
// Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Cadre_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Cadre_trait.php';
} else {
	trait SG_Cadre_trait{};
}
class SG_Cadre extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Cadre';
	public $typeSG = self::TYPESG;
	
	// code du cadre (id= et name=)
	public $code = '';
	// titre affiché dans le cadre
	public $titre = '';
	// position dans le navigateur
	public $position = 0;
	// largeur de la division
	public $largeur = '';
	// hauteur de la division
	public $hauteur = '';
	// liste des éléments (SG_HTML ou SG_Cadre) 
	public $elements = array();
	// cible par défaut des clics
	public $cible = '';
	
	/**
	* Construction de l'objet
	*/
	function __construct($pCode = '', $pTitre = '') {
		$this -> code = SG_Texte::getTexte($pCode);
		$this -> titre = SG_Texte::getTexte($pTitre);
	}

	// Complément de classe spécifique à l'application (créé par la compilation)
	use SG_Cadre_trait;
}
?>
