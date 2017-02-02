<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.0 (see AUTHORS file)
 * SG_TexteFormule : Classe de gestion d'un texte de formule SynerGaia
 */
class SG_TexteFormule extends SG_Texte {
	// Type SynerGaia
	const TYPESG = '@TexteFormule';
	public $typeSG = self::TYPESG;
	
	/** 1.2 ajout ; 1.3.1 ajust height, placeholder ; 2.0 parm2
	* HTML pour la modification du champ
	* @param string $pRefChamp référence du champ HTML
	* @param SG_Collection $pValeursPossibles collection des valeurs proposées
	*
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '', $pValeursPossibles = NULL) {
		$ret = '<textarea class="champ_TexteFormule" name="' . $pRefChamp . '"';
		$ret.= ' placeholder="Texte de formule SynerGaïa">' . $this -> toString() . '</textarea>';
		return $ret;
	}
	/** 1.3.0 interpretation du javascript ; 1.3.1 ajout ; 1.3.2 Afficher -> afficherChamp(), supp js
	* Retourne le texte des formules en mode HTML spécifique
	**/
	function afficherChamp($pOption = '') {
		$style = '';
		$classe = 'champ_TexteFormule';
		$ret = '';
		$option = '';
		// Lit l'option passée
		if ($pOption !== '') {
			$tmpOption = new SG_Texte($pOption);
			$option = $tmpOption -> texte;

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
