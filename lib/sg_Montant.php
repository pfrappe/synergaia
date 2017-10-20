<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Montant */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Montant_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Montant_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.1.1
	 */
	trait SG_Montant_trait{};
}

/**
 * Classe SynerGaia de gestion d'un montant (monétaire)
 * @version 2.1.1
 * @todo : à terminer ou remplacer par @Nombre avec @Unite
 */
class SG_Montant extends SG_Nombre {
	/** string Type SynerGaia '@Montant' */
	const TYPESG = '@Montant';

	/** Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	// 2.1.1. complément de classe créée par compilation
	use SG_Montant_trait;
}
?>
