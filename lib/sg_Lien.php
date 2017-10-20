<?php
/** SynerGaia fichier pour la gestion de l'objet @Lien */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Lien_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Lien_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Lien_trait{};
}

/**
* Classe SynerGaia de gestion d'un lien url 
* @version 2.3
*/
class SG_Lien extends SG_Texte {
	/** string Type SynerGaia '@Lien' */
	const TYPESG = '@Lien';
	/** Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/**
	* string Url liée
	*/
	public $url = "";
	/**
	* string Contenu du lien
	*/
	public $contenu = "";

	/**
	* Construction de l'objet
	*
	* @version 2.6 getTexte
	* @param indéfini $pURL url de la cible du lien
	* @param indéfini $pContenu contenu (texte) du lien
	*/
	function __construct($pURL = '', $pContenu = '') {
		$this -> url = SG_Texte::getTexte($pURL);
		$this -> contenu = SG_Texte::getTexte($pContenu);
	}

	/**
	* Conversion en chaine de caractères
	*
	* @return string texte
	*/
	function toString() {
		return $this -> contenu;
	}

	/**
	* Conversion en code HTML
	*
	* @return SG_HTML code HTML
	*/
	function toHTML() {
		$ret = '<span class="champ_lien"><a href="' . $this -> url . '">' . $this -> contenu . '</a></span>';
		return new SG_HTML($ret);
	}

	/**
	 * Calcule le code HTML pour afficher le champ
	 * 
	 * @since 2.3 ajout
	 * @return SH_HTML
	 */
	function afficherChamp() {
		return $this -> toHTML();
	}

	/**
	 * Calcul le code HTML de la modification du lien
	 * @since 2.3 ajout
	 * @param string $pRefChamp
	 * @return SH_HTML
	 */
	function modifierChamp($pRefChamp = '') {
		$ret = '<textarea class="champ_lien" name="' . $pRefChamp . '" ondblclick="SynerGaia.stopPropagation(event);">' . $this -> toString() . '</textarea>';
		return new SG_HTML($ret);
	}
	
	// 2.1.1. complément de classe créée par compilation
	use SG_Lien_trait;
}
?>
