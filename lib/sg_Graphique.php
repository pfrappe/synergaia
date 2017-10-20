<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Graphique
 * @todo voir si utilisé ?? */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Graphique_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Graphique_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.1.1
	 */
	trait SG_Graphique_trait{};
}

/**
 * Classe SynerGaia représentant un graphique à afficher
 * L'objectif de cette classe est d'y accrocher les code javascript pour l'animation
 * @version 2.1.1
 */
class SG_Graphique extends SG_HTML {
	/** string Type SynerGaia '@Graphique' */
	const TYPESG = '@Graphique';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** 2.0
	function Afficher($pOption = '') {
		return $this -> texte;
	}
	*/
	// 2.1.1. complément de classe créée par compilation
	use SG_Graphique_trait;
}
?>
