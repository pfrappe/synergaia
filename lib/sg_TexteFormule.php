<?php
/** SynerGaia fichier de gestion de l'objet @TexteFormule */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_TexteFormule : Classe de gestion d'un texte de formule SynerGaia
 * @version 2.0
 */
class SG_TexteFormule extends SG_Texte {
	/** string Type SynerGaia '@TexteFormule' */
	const TYPESG = '@TexteFormule';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/**
	 * HTML pour la modification du champ
	 * 
	 * @since 1.2 ajout
	 * @version 2.0 parm2
	 * @param string $pRefChamp référence du champ HTML
	 * @param SG_Collection $pValeursPossibles collection des valeurs proposées
	 *
	 * @return string code HTML
	 */
	function modifierChamp($pRefChamp = '', $pValeursPossibles = NULL) {
		$ret = '<textarea contenteditable="true" class="sg-formule" name="' . $pRefChamp . '"';
		$ret.= ' placeholder="Texte de formule SynerGaïa">' . $this -> toString() . '</textarea>';
		return $ret;
	}

	/**
	 * Retourne le texte des formules en mode HTML spécifique
	 * 
	 * @version 1.3.2 Afficher -> afficherChamp(), supp js
	 * @param string $pOption option d'affichage
	 * @return SG_HTML
	 */
	function afficherChamp($pOption = '') {
		$style = '';
		$classe = 'sg-formule';
		$ret = '';
		$option = '';
		// Lit l'option passée
		if ($pOption !== '') {
			$option = SG_Texte::getTexte($pOption);

			// Si ":" dans l'option => style sinon classe
			if (strpos($option, ':') !== false) {
				$style .= $option;
			} else {
				$classe .= ' ' . $option;
			}
		}
		$texte = htmlentities($this -> texte, ENT_QUOTES, 'UTF-8');//str_replace(chr(13), '<br>', );
		if ($option === 'js') {
			$ret .= '<pre>' . $texte . '</pre>';
			$ret .= '<input type="button" value="exécuter" onclick="' . $texte . '"</input>';
		}else{
			$ret .= '<span class="' . $classe . '" style="' . $style . '"><pre>' . $texte . '</pre></span>';
		}
		return new SG_HTML($ret);
	}
}
