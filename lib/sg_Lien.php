<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de gestion d'un lien url
*/
// 2.3 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Lien_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Lien_trait.php';
} else {
	trait SG_Lien_trait{};
}
class SG_Lien extends SG_Texte {
	// Type SynerGaia
	const TYPESG = '@Lien';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	/**
	* Url liée
	*/
	public $url = "";
	/**
	* Contenu du lien
	*/
	public $contenu = "";

	/**
	* Construction de l'objet
	*
	* @param indéfini $pURL url de la cible du lien
	* @param indéfini $pContenu contenu (texte) du lien
	*/
	function __construct($pURL = '', $pContenu = '') {
		$tmpURL = new SG_Texte($pURL);
		$this -> url = $tmpURL -> toString();

		$tmpContenu = new SG_Texte($pContenu);
		$this -> contenu = $tmpContenu -> toString();
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
	* @return string code HTML
	*/

	function toHTML() {
		$ret = '<span class="champ_lien"><a href="' . $this -> url . '">' . $this -> contenu . '</a></span>';
		return new SG_HTML($ret);
	}

	/** 2.3 ajout
	* affiche le champ
	**/
	function afficherChamp() {
		return $this -> toHTML();
	}

	/** 2.3 ajout
	* 
	*/
	function modifierChamp($pRefChamp = '') {
		$ret = '<textarea class="champ_lien" name="' . $pRefChamp . '" ondblclick="SynerGaia.stopPropagation(event);">' . $this -> toString() . '</textarea>';
		return new SG_HTML($ret);
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Lien_trait;
}
?>
