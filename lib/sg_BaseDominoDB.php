<?php
/** SYNERGAIA fichier pour le traitement de l'objet @BaseDominoDB */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_BaseDominoDB : Classe de gestion d'une base de documents Domino
 * @since 1.3.4 (see AUTHORS file)
 * @todo obsolète ? ou inutile ?
 */
class SG_BaseDominoDB extends SG_Objet {
	/** string Type SynerGaia */
	const TYPESG = '@BaseDominoDB';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string cookie domino */
	public $cookie = '';
	/** string utlilisateur Lotus Notes */
	public $user = '';
	/** string */
	public $psw = '';
	/** string */
	public $serveur = '';

	/**
	 * Constuction de l'objet (l'accès à Domino se fait par les services WEB)
	 * @since 1.3.4
	 * @param string|SG_Texte|Formule $pServeur
	 * @param string|SG_Texte|Formule $pUser
	 * @param string|SG_Texte|Formule $pPassword
	 */
	public function __construct($pServeur = '', $pUser = '', $pPassword = '') {
	}

	/**
	 * Compacter la base (inutilisé)
	 * @since 2.4 ajout pour compatibilité avec bases CouchDB
	 */
	function Compacter() {
		return new SG_VraiFaux(true);
	}
}
?>
