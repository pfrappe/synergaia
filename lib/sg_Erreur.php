<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de traitement des erreurs
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Erreur_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Erreur_trait.php';
} else {
	trait SG_Erreur_trait{};
}
class SG_Erreur extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Erreur';
	public $typeSG = self::TYPESG;

	// Code de l'erreur
	public $code = '0000';
	// Informations sur l'erreur
	public $infos;
	// Informations de trace
	public $trace;

	// Message d'erreur "erreur inconnue"
	const MESSAGE_ERREUR_INCONNUE = '0000';
	// Code d'erreur : méthode non trouvée
	const ERR_DICO_ELEMENT_INTROUVABLE = '0002';
	// Code d'erreur : le fichier n'existe pas (import)
	const ERR_FICHIER_NON_TROUVE = '0004';
	// Code d'erreur : le fichier n'est pas un JSON valide (import)
	const ERR_FICHIER_JSON_INVALIDE = '0005';

	/** 1.1 code erreur string
	 * Construction de l'objet
	 *
	 * @param integer $pCodeErreur code de l'erreur
	 * @param string $pInfos informations complémentaires
	 *	  */
	public function __construct($pCodeErreur = '0000', $pInfos = '') {
		$this -> code = SG_Texte::getTexte($pCodeErreur);
		$this -> infos = SG_Texte::getTexte($pInfos);
	}

	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString() {
		return $this -> getMessage();
	}
	/** 1.1 ; 2.1 ne donne plus un SG_HTML
	* @return html texte rouge
	*/
	function toHTML() {
		return '<span class="champ_Erreur">' . $this -> getMessage() . '</span>';
	}

	/** 1.1 base libelles ; 1.3.2 getLibelle parm 3
	 * Message d'erreur
	 *
	 * @return string message de l'erreur
	 */
	public function getMessage() {
		$ret = SG_Libelle::getLibelle($this -> code, true, $this -> infos);
		return $ret;
	}
	//1.2 ajout
	function Titre() {
		return $this -> getMessage();
	}
	/** 1.3.1 ajout
	* Affiche sur le navigateur
	*/
	function afficherChamp() {
		return $this -> toHTML();
	}
	/** 1.3.4 ajout
	* pour tous les objets : false sauf SG_Erreur et dérivés
	**/
	function estErreur() {
		return false;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Erreur_trait;
}
?>
