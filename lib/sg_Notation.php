<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.1 (see AUTHORS file)
* SG_Nombre : Classe de gestion d'une enquête ou d'une notation
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Notation_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Notation_trait.php';
} else {
	trait SG_Notation_trait{};
}
class SG_Notation extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Notation';
	public $typeSG = self::TYPESG;
	
	// titre du modèle
	public $titre = '';
	
	// nom des notes
	public $libelleNotes = array();
	
	// nom des éléments
	public $libelleElements = array();
	
	// nom du commentaire
	public $libelleCommentaire = '';
	
	//icones
	public $iconevide = ''; //' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/ui-radio-button-uncheck.png';
	public $iconeplein = SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/accept1.png'; //ui-radio-button.png';

	/** 1.0.7
	 * Construction de l'objet
	 *
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle le SG_Nombre est créé
	 * @param indéfini $pUnite code unité ou ou objet @Unite de la quantité
	 */
	function __construct($pQuelqueChose = null) {
	}
	/** 1.1
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
	/** 1.1 ajout
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
	/** 1.1 ajout
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
	/** 1.1 ajout
	*/
	function entete() {
		$ret = '<div id="champ_Notation" class="notation"><table class="corpstable">';
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
	
	// 1.1 ajout
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
		return $ret;
	}
	// 1.1 ajout
	function Afficher() {
		return $this -> afficherChamp();
	}
	// 1.1 ajout
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
	function LibelleCommentaire {
		return new SG_Texte("Commentaires")
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Notation_trait;
}
?>
