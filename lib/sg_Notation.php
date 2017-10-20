<?php
/** SYNERGAIA fichier pour traiter l'objet @Notation */
 defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Notation_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Notation_trait.php';
} else {
	/** trait vide par défaut pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 */
	trait SG_Notation_trait{};
}

/**
* SG_Nombre : Classe de gestion d'une enquête ou d'une notation
* @since 1.1
*/
class SG_Notation extends SG_Objet {
	/** string Type SynerGaia '@Notation' */
	const TYPESG = '@Notation';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** string titre du modèle */
	public $titre = '';
	
	/** string nom des notes */
	public $libelleNotes = array();
	
	/** string nom des éléments */
	public $libelleElements = array();
	
	/** string nom du commentaire */
	public $libelleCommentaire = '';
	
	/** string icones*/
	public $iconevide = ''; //' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/ui-radio-button-uncheck.png';
	/** string icones*/
	public $iconeplein = SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/accept1.png'; //ui-radio-button.png';

	/**
	 * initialisation de l'objet
	 * @since 1.1
	 */
	function initObjet() {
		$modele = getTypeSG($this);
		$typeobjet = SG_Dictionnaire::getDictionnaireObjet($modele);
		//titre
		$this -> titre = $typeobjet -> getValeur('@Titre', '');
		//LibelleNotes
		$result = SG_Formule::executer('.@LibelleNotes', $this);
		if (getTypeSG($result) === '@Collection') {
			$this -> libelleNotes = $result -> elements;
		} else {
			$this -> libelleNotes = $result;
		}
		//libelleElements
		$result = SG_Formule::executer('.@LibelleElements', $this);
		if (getTypeSG($result) === '@Collection') {
			$this -> libelleElements = $result -> elements;
		} else {
			$this -> libelleElements = $result;
		}
		//LibelleCommentaire
		$this -> libelleCommentaire = SG_Texte::getTexte(SG_Formule::executer('.@LibelleCommentaire', $this));
	}

	/** 1.1 ajout
	*/ 
	function getTypeSG() {
		return $this -> getValeur('@Type', '@Notation');
	}

	/** 1.1 ajout
	*/
	function Titre() {
		return $this -> titre;
	}

	/**
	 * Note de la notation
	 * @since 1.1 ajout
	 * @param string|SG_Texte|SG_Formule $pElement
	 */
	function Note($pElement = '') {
		$notes = $this -> getValeur('@Notes', array());
		$element = SG_Texte::Normaliser($pElement);
		if (isset($notes[$element])) {
			$ret = $notes[$element];
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * Commentaires de la notation
	 * @since 1.1 ajout
	 * @param string|SG_Texte|SG_Formule $pElement
	 */
	function Commentaire($pElement = '') {
		$commentaires = $this -> getValeur('@Commentaires', array());
		$element = SG_Texte::Normaliser($pElement);
		if (isset($commentaires[$element])) {
			$ret = $commentaires[$element];
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * calcule l'affichage de l'entête
	 * 
	 * @since 1.1 ajout
	 * @return string
	 */
	function entete() {
		$ret = '<div id="champ_Notation" class="notation"><table class="sg-collection">';
		$ret .= '<thead><tr><th>' . $this -> titre . '</th>';
		foreach ($this -> libelleNotes as $libelleNote) {
			$ret .= '<th>' . SG_Texte::getTexte($libelleNote) . '</th>';
		}
		if ($this -> libelleCommentaire !== '') {
			$ret .= '<th>' . $this -> libelleCommentaire . '</th>';
		}
		$ret .= '</tr></thead><tbody>';
		return $ret;
	}
	
	/**
	 * Calcule le code html d'un champ Notation
	 * 
	 * @since 1.1 ajout
	 * @version 2.6
	 * @return SG_HTML
	 */	 	
	 function afficherChamp() {
		$ret = $this -> entete();
		
		foreach($this -> libelleElements as $element) {
			$elt = SG_Texte::getTexte($element);
			$ret .= '<tr><td>' . $elt . '</td>';
			$index = SG_Texte::Normaliser($elt);
			$val = $this -> Note($index);
			foreach ($this -> libelleNotes as $note) {
				if ($val === $note -> texte) {
					$ret .= '<td><img src="' . $this -> iconeplein . '"></td>';
				} else {
					if ($this -> iconevide !== '') {
						$ret .= '<td><img src="' . $this -> iconevide . '"></td>';
					} else {
						$ret .= '<td></td>';
					}
				}
			}
			if ($this -> libelleCommentaire !== '') {
				$ret .= '<td>' . $this -> Commentaire($element) . '</td>';
			}
			$ret .= '</tr>';
		}
		$ret .= '</tbody></table></div>';
		return new SG_HTML($ret);
	}

	/**
	 * Affichage d'un champ @Notation
	 * @since 1.1 ajout
	 */
	function Afficher() {
		return $this -> afficherChamp();
	}

	/**
	 * calcule le code html d'un champ @Notation en modification
	 * @since 1.1 ajout
	 * @return
	 */
	function modifierChamp() {
		$ret = $this -> entete();
		$uidNotation = $this -> index;
		foreach($this -> libelleElements as $element) {
			// intitulé de ligne
			$elt = SG_Texte::getTexte($element);
			$ret .= '<tr><td>' . $elt . '</td>';
			// valeur de note
			$index = SG_Texte::Normaliser($elt);
			$val = $this -> Note($index);
			$codeChampNote = SG_Champ::codeChampHTML($uidNotation . '.@Notes.' . $index);
			foreach ($this -> libelleNotes as $note) {
				$n = SG_Texte::getTexte($note);
				if ($val === $n) {
					$ret .= '<td><input type="radio" name="' . $codeChampNote . '" value="' . $n . '" checked></td>';
				} else {
					$ret .= '<td><input type="radio" name="' . $codeChampNote . '" value="' . $n . '"></td>';
				}
			}
			//commentaire
			if ($this -> libelleCommentaire !== '') {
				$codeChampNote = SG_Champ::codeChampHTML($uidNotation . '.@Commentaires.' . $index);
				$texte = new SG_Texte($this -> Commentaire[$index]);
				$ret .= '<td>' . $texte -> modifierChamp($codeChampNote) . '</td>';
			}
			$ret .= '</tr>';
		}
		$ret .= '</tbody></table></div>';
		return $ret;
	}

	/** 1.1 ajout
	*/
	function EstVide() {
		$texte = implode('', $this -> getValeur('@Notes','')) . implode('', $this -> getValeur('@Commentaires',''));
		if ($texte === '') {
			$ret = new SG_VraiFaux(true);
		} else {
			$ret = new SG_VraiFaux(false);
		}
		return $ret;
	}

	/** 2.1 ajout
	*/
	function LibelleCommentaire() {
		return new SG_Texte("Commentaires");
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Notation_trait;
}
?>
