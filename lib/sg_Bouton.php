<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
 * Classe SynerGaia de gestion d'un bouton affiché
 * 1.1 : permet aussi l'affichage d'une fenêtre modale
 */
class SG_Bouton extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Bouton';
	public $typeSG = self::TYPESG;

	// Libellé du bouton
	public $nom;

	// Formule à exécuter au clic
	public $formule = '';
	
	// Objet associé au bouton, sur lequel portera la formule
	public $objet;
	
	// 2.3 si "m" indique qu'il s'agit d'un modèle d'opération à lancer
	public $type;

	// Code de l'étape de l'opération correspondante
	public $code = '';

	// référence du document principal concerné
	public $refdocument = '';
	
	// 2.1.1 paramètres
	public $parms;

	/** 
	* 1.1 ajout de $pObjet ; 2.1.1 paramètres d'url ; 2.3 calcul sha code ; supp enreg ope ; @param3
	* Construction de l'objet ; la formule est juste stockée : elle sera exécutée en cliquant sur le bouton
	*
	* @param indéfini $pNom nom du bouton
	* @param indéfini $pFormule formule à exécuter ou code du modèle d'opération à lancer
	* @param indefini $pType : par défaut null, sinon "m" indique qu'on veut lancer un nouveau modèle d'opération
	*/
	function __construct($pNom = '', $pFormule = '', $pType = null) {
		//préparation des paramètres
		$this -> nom = SG_Texte::getTexte($pNom);
		// la formule est juste stockée : elle sera exécutée en cliquant sur le bouton
		if (getTypeSG($pFormule) === '@Formule') {
			$this -> formule = $pFormule;
			if ($this -> formule -> formule !== '') {
				$code = sha1($this -> formule -> formule);
			} else {
				$code = sha1($this -> formule -> fonction . '#' . $this -> formule -> methode);
			}
		} elseif (is_string($pFormule)) {
			$this -> formule = $pFormule;
			$code = sha1($this -> formule);
		} else {
			$this -> formule = SG_Texte::getTexte($pFormule);
			$code = sha1($this -> formule);
		}
		$this -> code = $code;
		// code de bouton et d'étape
		$operation = SG_Navigation::OperationEnCours();
		if (!isset($operation -> boutons[$code])) {
			$operation -> boutons[$code] = $this->formule;
			if($operation !== null) {
				$operation -> setValeur('@Boutons', $operation -> boutons);
				//$operation -> Enregistrer();
			}
		}
		if (func_num_args() > 2) {
			$this -> parms = array();
			for ($i = 2;$i < func_num_args();$i++) {
				$p = func_get_arg($i);
				if (is_object($p)) {
					$p = $p -> toString();
				}
				$this -> parms['p' . ($i - 1)] = $p;
			}
		}
	}

	/**
	* Conversion en chaine de caractères
	*
	* @return string texte
	*/
	function toString() {
		return $this -> formule;
	}
	
	function url($pTypeAppel = SG_Navigation::URL_VARIABLE_BOUTON) {		
		$operation = SG_Navigation::OperationEnCours();
		$url = SG_Navigation::URL_VARIABLE_OPERATION . '=' . $operation -> reference;
		
		if ($this -> code !== '') {
			$url .= '&' . $pTypeAppel . '=' . $this -> code;
		}
		if ($this -> refdocument !== '') {
			$url .= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=' . $this -> refdocument;
		}
		if (is_array($this -> parms)) {
			foreach ($this -> parms as $key => $elt) {
				$url.= '&' . $elt;
			}
		}
		return $url;
	}

	/** 2.0 SG_HTML
	* Conversion en code HTML
	*
	* @return string code HTML
	*/
	function toHTML() {
		$ret = '<button type="button"  onClick="SynerGaia.launchOperation(event,\'' . $this -> url() . '\')" class="sg-bouton">' . $this -> nom . '</button>';
		return new SG_HTML($ret);
	}
	
	function Afficher() {
		return $this -> toHTML();
	}
	
	/** 1.1 ajout ; 2.0 SG_HTML
	*/
	function Fenetre() {
		$ret = '<div class="modal hide fade" id="' . $this -> code . '-window">';
		$ret .= '<div class="modal-header"> <a class="close" data-dismiss="modal"><img src="../' . SG_Navigation::URL_THEMES . 'default/img/icons/16x16/silkicons/cancel.png"</a>';
		$ret .= '<h3>' . $this -> nom . '</h3>';
		$ret .= '</div><div class="modal-body"></div><div class="modal-footer"> <a class="btn btn-info" data-dismiss="modal">Fermer</a></div></div>' . PHP_EOL;
		$ret .= '<span onclick="SynerGaia.adroite(' . $this -> url(SG_Navigation::URL_VARIABLE_CODE) . ')" data-target="#' . $this -> code . '-window">' . $this -> nom . '</a>';
		return new SG_HTML($ret);
	}
	/** 1.1 ajout ; 2.0 SG_HTML
	*/
	function ADroite() {
		$ret = '';
		$url = explode('?', $this -> url(SG_Navigation::URL_VARIABLE_BOUTON));
		$ret .= '<span class="sg-bouton" onclick="SynerGaia.launchOperation(event,\'' . $url[0] . '\'),$(\'#formdroite\').serialize()">' . $this -> nom . '</span>';
		return new SG_HTML($ret);
	}
	/** 1.3.3 ajout ; 2.0 SG_HTML
	*/
	function AGauche() {
		$ret = '';
		$url = explode('?', $this -> url(SG_Navigation::URL_VARIABLE_BOUTON));
		$ret .= '<span class="sg-bouton" onclick="SynerGaia.launchOperation(event,\''. $url[0] . '\',$(\'#formgauche\').serialize())">' . $this -> nom . '</span>';// . $this -> code . '-window\', \'
		return new SG_HTML($ret);
	}
}
?>
