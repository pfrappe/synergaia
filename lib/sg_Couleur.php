<?php
/** SynerGaia 2.7 Contient la classe SG_Couleur */ 
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

// Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Couleur_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Couleur_trait.php';
} else {
	/** trait vide par défaut */
	trait SG_Couleur_trait{};
}

/** SG_Couleur : Classe de traitement des couleurs sur le navigateur
 * 
 * @since 2.7
 */
class SG_Couleur extends SG_Objet {
	/** string Type SynerGaia */
	const TYPESG = '@Couleur';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Classe CSS désignant la couleur (permet de définir couleur, fond, bordure) */
	public $classeCSS = '';

	/** array Liste des classes standards de couleur de SynerGaia */
	public $listeClasses = array('bleu', 'bleuclair', 'gris', 'jaune', 'rose', 'vert', 'marron', 'rouge');

	/** string couleur de caractère (#xxx ou #xyxyxy) */
	public $color = '';

	/** string couleur de fond (#xxx ou #xyxyxy) */
	public $bgcolor = '';

	/** string couleur de bordure (#xxx ou #xyxyxy) */
	public $bordercolor = '';

	/**
	 * Calcule la phrase html à insérer dans la balise de l'objet qualifié par la couleur
	 * 
	 * @since 2.7
	 * @return string html à insérer dans une balise
	 */
	function toHTML() {
		$css = '';
		if ($this -> classeCSS !== '') {
			$css = ' sg-' . $this -> classeCSS . '"';
		}
		$style = '';
		if ($this -> color !== '') {
			$style.= 'color:#' . $this -> color . ';';
		}
		if ($this -> bgcolor !== '') {
			$style.= 'background-color:#' . $this -> bgcolor . ';';
		}
		if ($this -> bordercolor !== '') {
			$style.= 'border-color:#' . $this -> bordercolor . ';';
		}
		$ret = '';
		if ($css !== '') {
			$ret.= $css;
		}
		if ($style !== '') {
			$ret.= ' style="' . $style . '"';
		}
		return $ret;
	}

	// 2.7 complément de classe créée par compilation
	use SG_Couleur_trait;
}
?>
