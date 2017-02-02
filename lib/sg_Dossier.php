<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de traitement des dossiers dans un document
*/
// Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dossier_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dossier_trait.php';
} else {
	trait SG_Dossier_trait{};
}
class SG_Dossier extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Dossier';
	public $typeSG = self::TYPESG;
	
	// élements du dossier (fichiers internes, renvois vers des @Document, @Dossier = sous-dossiers)
	// chaque élément est un tableau :[type, titre, datemodification, id ou clé]
	public $elements = array();
	
	/**
	* Construction de l'objet
	*/
	function __construct($pQuelqueChose = null) {
	}

	// Complément de classe spécifique à l'application (créé par la compilation)
	use SG_Dossier_trait;
}
?>
