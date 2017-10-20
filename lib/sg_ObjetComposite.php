<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.1 (see AUTHORS file)
 * SG_ObjetComposite : Classe SynerGaia de gestion d'un objet non document mais composé de plusieurs propriétés
 * C'est en fait un objet dont la valeur est un tableau de propriétés
 */
class SG_ObjetComposite extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@ObjetComposite';
	public $typeSG = self::TYPESG;
	
	public $classeCSS = 'objetcomposite';
	
	public $champs = array();
		
	/** 1.1 ajout
	* Construction de l'objet
	*/
	function __construct($pQuelqueChose = null, $pUUId = '') {	
		$this -> initObjetComposite($pQuelqueChose, $pUUId);
	}
	/** 1.1 ajout
	* Initialisation de l'objet
	*/
	public function initObjetComposite($pQuelqueChose = null, $pUUId = '') {
		if(!is_null($pQuelqueChose)) {
			$this -> proprietes = $pQuelqueChose;
		}
	}
	/** 1.1 AJout
	* Conversion en chaine de caractères
	*
	* @return string texte
	*/
	function toString() {<?php
/** SynerGaia fichier pour gérer l'objet @ObjetComposite */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_ObjetComposite : Classe SynerGaia de gestion d'un objet non document mais composé de plusieurs propriétés
 * C'est en fait un objet dont la valeur est un tableau de propriétés
 * 
 * @since 1.1
 */
class SG_ObjetComposite extends SG_Objet {
	/** string Type SynerGaia '@ObjetComposite' */
	const TYPESG = '@ObjetComposite';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	/** string Classe CSS par défaut */
	public $classeCSS = 'sg-composite';
	/** array tableau des objets contenus dans l'objet composite */
	public $champs = array();
		
	/**
	 * Construction de l'objet
	 * 
	 * @since 1.1
	 * @param null|array $pQuelqueChose tableau des propriétés de l'objet
	 * @param string|SG_Texte|SG_Formule $pUUId
	 */
	function __construct($pQuelqueChose = null, $pUUId = '') {	
		$this -> initObjetComposite($pQuelqueChose, $pUUId);
	}

	/**
	 * Initialisation de l'objet
	 * @since 1.1
	 * @param null|array $pQuelqueChose tableau des propriétés de l'objet
	 * @param string|SG_Texte|SG_Formule $pUUId
	 */
	public function initObjetComposite($pQuelqueChose = null, $pUUId = '') {
		if(!is_null($pQuelqueChose)) {
			$this -> proprietes = $pQuelqueChose;
		}
	}

	/**
	 * Conversion en chaine de caractères
	 * C'est la liste des toString de chaque éléments
	 * 
	 * @since 1.1
	 * @return string texte
	 */
	function toString() {
		return '' . implode($this -> proprietes);
	}

	/**
	 * Affichage de l'objet
	 * @since 1.1
	 * @version 1.2 pas @Type
	 * @param any liste de propriétés
	 * @return string contenu HTML affichable
	 */
	public function Afficher() {
		$ret = '';
		// Traite les parametres passés
		$formule = '';
		$formuleorigine = null;
		$nbParametres = func_num_args();
		for ($i = 0; $i < $nbParametres; $i++) {
			$parametre = func_get_arg($i);
			if (getTypeSG($parametre) === '@Formule') {
				$formule .= $parametre -> texte;
				$formuleorigine = $parametre;
			} else {
				$formule .= SG_Texte::getTexte($parametre);
			}
			// Si il reste des parametres, ajout ';' à la fin de la formule
			if (($i + 1) < $nbParametres) {
				$formule .= ';';
			}
		}
		// Aucun paramètre fourni => affiche tous les champs
		if ($formule === '') {
			$modele = getTypeSG($this);
			$listeChamps = SG_Dictionnaire::getListeChamps($modele);
			$ret .= '<ul data-role="listview" class="' . $this -> classeCSS . '">';
			foreach ($listeChamps as $codeChamp => $modeleChamp) {	
				if ($codeChamp !== '_rev') {
					$tmpChamp = new SG_Champ($this -> index . '.' . $codeChamp, $this);
					if ($tmpChamp -> toString() !== '') {
						$ret .= '<li>' . $tmpChamp -> Afficher() . '</li>';
					}
				}
			}
			$ret .= '</ul>';
			// traitement des propriétés présentes mais qui ne sont pas définies dans le dictionnaire
			$autres = false;
			foreach ($this -> proprietes as $key => $valeur) {
				if (! array_key_exists($key, $listeChamps)) {
					if ($key !== '@Type') {
						if ($autres === false) {
							$ret .= '<div class="sg-infosdoc"><ul data-role="listview">';
							$autres = true;
						}
						if (is_array($valeur)) {
							$ret .= '<li><span class="sg-titrechamp">' . $key . '</span> : ' . implode($valeur, ', ') . '</li>';
						} else {
							$ret .= '<li><span class="sg-titrechamp">' . $key . '</span> : ' . $valeur . '</li>';
						}
					}
				}
			}
			if ($autres === true) {
				$ret .= '</ul></div>';
			}
		} else {
			// Utilise les parametres fournis pour afficher les informations
			$tmpFormule = new SG_Formule($formule, $this, null, $formuleorigine);
			$resultat = $tmpFormule -> calculer();
			if (getTypeSG($resultat) !== 'string') {
				$ret .= $resultat -> toHTML();
			}
		}
		return new SG_HTML($ret);
	}

	/**
	 * affiche l'objet comme un champ
	 * @since 1.1
	 */
	function afficherChamp() {
		return $this -> Afficher();
	}

	/**
	 * Calcul le code HTML pour la modification de l'objet
	 * @since 1.1
	 */
	function modifierChamp() {
		return $this -> Modifier();
	}

	/**
	 * Modification de l'objet par l'utilisateur
	 * 
	 * @since 1.1
	 * @return string contenu HTML affichable / modifiable
	 */
	public function Modifier() {
		$ret = '';
		
		// Traite les parametres passés
		$listeProprietes = array();
		$nbParametres = func_num_args();
		if ($nbParametres !== 0) {
			for ($i = 0; $i < $nbParametres; $i++) {
				$parametre = func_get_arg($i);
				if (getTypeSG($parametre) === '@Formule') {
					$listeProprietes[] .= $parametre -> texte;
				} else {
					$listeProprietes[] = SG_Texte::getTexte($parametre);
				}
			}
		} else {
			// si aucun : recupere la liste complete des champs du document
			$listeChamps = SG_Dictionnaire::getListeChamps(getTypeSG($this), '', $this -> champs);
			// Transforme la liste des champs en formules de propriete
			foreach($listeChamps as $key => $modele) {
				$listeProprietes[] = '.' . $key;
			}
		}
		// constitue la liste html
		$ret .= '<ul data-role="listview" class="' . $this -> classeCSS . '">';

		// affichage des champs
		foreach ($listeProprietes as $propriete) {
			// Supprime le '.' au début de la propriété (pirouette)
			$propriete = substr($propriete, 1);
			// si inexistant, créer une propriété vide
			if (! isset($this -> proprietes[$propriete])) {
				$modele = SG_Dictionnaire::getCodeModele(getTypeSG($this) . '.' . $propriete);
				$classe = SG_Dictionnaire::getClasseObjet($modele);
				$objet = new $classe();
				$objet -> index = $this -> index . '.' . $propriete;
				$this -> proprietes[$propriete] = $objet;
			}
			$tmpChamp = new SG_Champ($this -> index . '.' . $propriete, $this);
			$ret .= '<li>' . $tmpChamp -> txtModifier() . '</li>';
		}
		$ret .= '</ul>';
		return $ret;
	}

	/**
	 * Calcule si l'objet composite est vide (toutes les données vides)
	* @since 1.1
	* @return SG_VraiFaux
	*/
	function EstVide() {
		$ret = new SG_VraiFaux(true);
		if (isset($this -> proprietes) and is_array($this -> proprietes)) {
			foreach ($this -> champs as $key => $value) {
				if (isset($this -> proprietes[$key])) {
					if (SG_Texte::getTexte($this -> proprietes[$key]) !== '') {
						$ret = new SG_VraiFaux(false);
						break;
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Calcul le texte de l'objet
	 * @since 1.2 (dans SG_Objet)
	 * @param string $pChamp
	 * @param string $pValeurDefaut
	 * @return string
	 */
	function getTexte($pChamp = null, $pValeurDefaut = null) {
		$ret = $this -> getValeur($pChamp, $pValeurDefaut);
		if ($ret !== '') {
			$ret = $ret -> texte;
		}
		return $ret;
	}
}
?>

		return '' . implode($this -> proprietes);
	}

	/** 1.1 : ajout ; 1.2 pas @Type
	* Affichage de l'objet
	* @param : liste de propriétés
	*
	* @return string contenu HTML affichable
	*/
	public function Afficher() {
		$ret = '';
		// Traite les parametres passés
		$formule = '';
		$formuleorigine = null;
		$nbParametres = func_num_args();
		for ($i = 0; $i < $nbParametres; $i++) {
			$parametre = func_get_arg($i);
			if (getTypeSG($parametre) === '@Formule') {
				$formule .= $parametre -> formule;
				$formuleorigine = $parametre;
			} else {
				$tmpFormule = new SG_Texte($parametre);
				$formule .= $tmpFormule -> toString();
			}
			// Si il reste des parametres, ajout ';' à la fin de la formule
			if (($i + 1) < $nbParametres) {
				$formule .= ';';
			}
		}
		// Aucun paramètre fourni => affiche tous les champs
		if ($formule === '') {
			$modele = getTypeSG($this);
			$listeChamps = SG_Dictionnaire::getListeChamps($modele);
			$ret .= '<ul data-role="listview" class="' . $this -> classeCSS . '">';
			foreach ($listeChamps as $codeChamp => $modeleChamp) {	
				if ($codeChamp !== '_rev') {
					$tmpChamp = new SG_Champ($this -> index . '.' . $codeChamp, $this);
					if ($tmpChamp -> toString() !== '') {
						$ret .= '<li>' . $tmpChamp -> Afficher() . '</li>';
					}
				}
			}
			$ret .= '</ul>';
			// traitement des propriétés présentes mais qui ne sont pas définies dans le dictionnaire
			$autres = false;
			foreach ($this -> proprietes as $key => $valeur) {
				if (! array_key_exists($key, $listeChamps)) {
					if ($key !== '@Type') {
						if ($autres === false) {
							$ret .= '<div class="autreschamps"><ul data-role="listview">';
							$autres = true;
						}
						if (is_array($valeur)) {
							$ret .= '<li><span class="sg-titrechamp">' . $key . '</span> : ' . implode($valeur, ', ') . '</li>';
						} else {
							$ret .= '<li><span class="sg-titrechamp">' . $key . '</span> : ' . $valeur . '</li>';
						}
					}
				}
			}
			if ($autres === true) {
				$ret .= '</ul></div>';
			}
		} else {
			// Utilise les parametres fournis pour afficher les informations
			$tmpFormule = new SG_Formule($formule, $this, null, $formuleorigine);
			$resultat = $tmpFormule -> calculer();
			if (getTypeSG($resultat) !== 'string') {
				$ret .= $resultat -> toHTML();
			}
		}
		return $ret;
	}
	/** 1.1 ajout
	*/
	function afficherChamp() {
		return $this -> Afficher();
	}
	/** 1.1 ajout
	*/
	function modifierChamp() {
		return $this -> Modifier();
	}
	/** 1.1 : ajout
	 * Modification de l'objet par l'utilisateur
	 *
	 * @return string contenu HTML affichable / modifiable
	 */
	public function Modifier() {
		$ret = '';
		
		// Traite les parametres passés
		$listeProprietes = array();
		$nbParametres = func_num_args();
		if ($nbParametres !== 0) {
			for ($i = 0; $i < $nbParametres; $i++) {
				$parametre = func_get_arg($i);
				if (getTypeSG($parametre) === '@Formule') {
					$listeProprietes[] .= $parametre -> formule;
				} else {
					$tmpFormule = new SG_Texte($parametre);
					$listeProprietes[] = $tmpFormule -> toString();
				}
			}
		} else {
			// si aucun : recupere la liste complete des champs du document
			$listeChamps = SG_Dictionnaire::getListeChamps(getTypeSG($this), '', $this -> champs);
			// Transforme la liste des champs en formules de propriete
			foreach($listeChamps as $key => $modele) {
				$listeProprietes[] = '.' . $key;
			}
		}
		// constitue la liste html
		$ret .= '<ul data-role="listview" class="' . $this -> classeCSS . '">';

		// affichage des champs
		foreach ($listeProprietes as $propriete) {
			// Supprime le '.' au début de la propriété (pirouette)
			$propriete = substr($propriete, 1);
			// si inexistant, créer une propriété vide
			if (! isset($this -> proprietes[$propriete])) {
				$modele = SG_Dictionnaire::getCodeModele(getTypeSG($this) . '.' . $propriete);
				$classe = SG_Dictionnaire::getClasseObjet($modele);
				$objet = new $classe();
				$objet -> index = $this -> index . '.' . $propriete;
				$this -> proprietes[$propriete] = $objet;
			}
			$tmpChamp = new SG_Champ($this -> index . '.' . $propriete, $this);
			$ret .= '<li>' . $tmpChamp -> txtModifier() . '</li>';
		}
		$ret .= '</ul>';
		return $ret;
	}
	/** 1.1 ajout
	*/
	function EstVide() {
		$ret = new SG_VraiFaux(true);
		if (isset($this -> proprietes) and is_array($this -> proprietes)) {
			foreach ($this -> champs as $key => $value) {
				if (isset($this -> proprietes[$key])) {
					if (SG_Texte::getTexte($this -> proprietes[$key]) !== '') {
						$ret = new SG_VraiFaux(false);
						break;
					}
				}
			}
		}
		return $ret;
	}
	/** 1.3 ajout déplacé de SG_Objet (1.2)
	*/
	function getTexte($pChamp = null, $pValeurDefaut = null) {
		$ret = $this -> getValeur($pChamp, $pValeurDefaut);
		if ($ret !== '') {
			$ret = $ret -> texte;
		}
		return $ret;
	}
}
?>
