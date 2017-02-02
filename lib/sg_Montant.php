<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de gestion d'un montant (monétaire)
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Montant_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Montant_trait.php';
} else {
	trait SG_Montant_trait{};
}
class SG_Montant extends SG_Nombre {
    // Type SynerGaia
    const TYPESG = '@Montant';
    // Type SynerGaia de l'objet
    public $typeSG = self::TYPESG;
	// 2.1.1. complément de classe créée par compilation
	use SG_Montant_trait;
}
?>
