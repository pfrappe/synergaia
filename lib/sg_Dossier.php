<?php
/** SYNERGAIA fichier pour le traitement del'objet @Dossier
 * @todo à conserver ??
 */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dossier_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dossier_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe */
	trait SG_Dossier_trait{};
}

/**
 * Classe SynerGaia de traitement des dossiers dans un document
 * @version 2.3
 */
class SG_Dossier extends SG_Objet {
	/** string Type SynerGaia '@Dossier' */
	const TYPESG = '@Dossier';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** @var élements du dossier (fichiers internes, renvois vers des @Document, @Dossier = sous-dossiers)
	 * chaque élément est un tableau :[type, titre, datemodification, id ou clé]
	 */
	public $elements = array();
	
	/**
	* Construction de l'objet
	* @param any $pQuelqueChose
	*/
	function __construct($pQuelqueChose = null) {
	}

	// Complément de classe spécifique à l'application (créé par la compilation)
	use SG_Dossier_trait;
}
?>
