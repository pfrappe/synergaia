<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia représentant un graphique à afficher
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Graphique_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Graphique_trait.php';
} else {
	trait SG_Graphique_trait{};
}
class SG_Graphique extends SG_HTML {
	// Type SynerGaia
	const TYPESG = '@Graphique';
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
