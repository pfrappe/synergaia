<?php
/** SynerGaia fichier pour le traitement de l'objet @Cadre */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Cadre_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Cadre_trait.php';
} else {
	/** trait vide par défaut : 
	 * Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe
	 */
	trait SG_Cadre_trait{};
}

/**
 * Classe SynerGaia de traitement des formulaires et cadres du navigateur
 * (correspond à une <div> pouvant contenir une <form>)
 * @since 2.3
 */
class SG_Cadre extends SG_Objet {
	/** string Type SynerGaia '@Cadre' */
	const TYPESG = '@Cadre';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string code du cadre (id= et name=) */
	public $code = '';

	/** string titre affiché dans le cadre */
	public $titre = '';

	/** integer position dans le navigateur */
	public $position = 0;

	/** string largeur de la division avec son unité */
	public $largeur = '';

	/** string hauteur de la division avec son unité */
	public $hauteur = '';

	/** string liste des éléments (SG_HTML ou SG_Cadre) */
	public $elements = array();

	/** string cible par défaut des clics */
	public $cible = '';

	/**
	 * Construction de l'objet : désigne soit un cadre existant, soit un nouveau à créer
	 * 
	 * @version 2.6
	 * @param string|SG_Texte|SG_Formule $pCode code du cadre à traiter
	 * @param  string|SG_Texte|SG_Formule $pTitre
	 */
	function __construct($pCode = '', $pTitre = '') {
		$this -> code = SG_Texte::getTexte($pCode);
		$this -> titre = SG_Texte::getTexte($pTitre);
	}

	/**
	 * Mettre une décoration sur le fond du cadre : image ou couleur
	 * @todo à terminer
	 */
	function Fond() {
		
	}

	// Complément de classe spécifique à l'application (créé par la compilation)
	use SG_Cadre_trait;
}
?>
