<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Categorie */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Categorie_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Categorie_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Categorie_trait{};
}

/**
 * SG_Categorie : Classe de texte pour la gestion de catégorie de classement
 * La différence avec un @Texte ayant des valeurs possibles est que la liste à proposer provient des valeurs existantes et peut être étendue
 * Donc un objet @Categorie est nécessairement stocké dans un @Document et les valeurs sont recherchées par une vue réduite
 * @since 1.3.4
 * @version 2.2
 */
class SG_Categorie extends SG_Texte {
	/** string Type SynerGaia '@Categorie' */
	const TYPESG = '@Categorie';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** string type d'objet auquel appartient la catégorie */
	public $typeObjet = '';
	
	/** array liste des valeurs
	 * @since 2.1
	 */
	public $valeurs = array();
	
	/** boolean : choix multiple autorisé
	 * @since 2.2
	 */
	public $multiple = false;

	/**
	 * Construction de l'objet : voir SG_Texte, mais texte est stocké sous forme de collection ou de textes séparés par des virgules
	 * @since 1.3.4 
	 * @version 2.1 ajout méthode new
	 * @param any $pQuelqueChose
	 */
	function __construct($pQuelqueChose = null) {
		parent::__construct($pQuelqueChose);
		if (is_array($pQuelqueChose)) {
			$this -> valeurs = $pQuelqueChose;
		} elseif (getTypeSG($pQuelqueChose) === '@Collection') {
			$this -> valeurs = $pQuelqueChose -> elements;
			$this -> texte = implode(',', $this -> valeurs);
		} else {
			$this -> valeurs = explode(',', $this -> texte);
		}
	}
	
	/**
	 * Calcul du code html pour la modification comme champ
	 * @since 1.3.4 
	 * @version 2.0 stoppropagation dblclick
	 * @param string $pRefChamp référence du champ HTML
	 * @param string|SG_Texte|SG_Formule  $pValeursPossibles
	 * @return string code HTML
	 * @uses JS SynerGaia.initCategorie()
	 */
	function modifierChamp($pRefChamp = '', $pValeursPossibles = '') {
		$ret = '';
		// recherche de la liste des valeurs possibles
		if( is_null($pValeursPossibles) or $pValeursPossibles === '') {
			$vp = $this -> Choix();
		} else {
			$vp = $pValeursPossibles;
		}
		if (getTypeSG($vp) === '@Collection') {
			$vp = $vp -> elements;
		}
		if(is_array($this -> valeurs)) {
			$valActuelle = implode(',', $this -> valeurs);
		} else {
			$valActuelle = '';
		}
		if (is_array($vp)) {
			// Si on a passé une liste de valeurs proposées
			$idTable = SG_SynerGaia::idRandom();
			$valeurs = json_encode($vp, false);
			$ret .= '<input id="' . $idTable . '" class="sg-exte sg-categorie" type="text" name="' . $pRefChamp . '" value="' . $valActuelle . '"
			 multiple="1"/><script>SynerGaia.initCategorie("' . $idTable . '",'.$valeurs.')</script>';
		} else {
			// sinon rien que de la saisie
			$ret .= '<textarea class="sg-texte" name="' . $pRefChamp . '">' . $valActuelle . '</textarea>';
		}
		return $ret;
	}

	/**
	 * Calcul du code html pour le choix de l'une des valeurs
	 * @since 1.3.4
	 * @version 2.2 modif js
	 * @param string|SG_Texte|SG_Formule $pTypeObjet objet de référence pour chercher les valeurs possibles
	 * @return SG_Collection collection des textes de valeurs possibles
	 */
	function Choix() {
		// préparation de la phrase de sélection
		$objet = getTypeSG($this -> contenant -> document);
		$nomChamp = $this -> contenant -> codeChamp;
		$codebase = $this -> contenant -> codeBase;
		// création de la vue catégories si nécessaire
		$js = SG_CouchDB::javascript('9', $objet, $nomChamp);
		$vue = new SG_Vue('', $codebase, $js, true);
		$rows = $vue -> Categorie();
		// création de la collection des résultats
		$ret = new SG_Collection();
		if(! is_null($rows)) {
			foreach($rows as $value) {
				$ret -> elements[] = $value['key'];
			}
		}
		return $ret;
	}

	/**
	 * Cherche si la catégorie contient la chaine ou le @Texte passée en paramètre)
	 * @since 2.1
	 * @version 2.2 parm $p
	 * @param string|SG_Texte|SG_Formule $pTexte : le mot recherché
	 * @param string|SG_Texte|SG_Formule $p : inutilisé (compatibilité avec la méthode SG_Texte::Contient)
	 * @return SG_VraiFaux : vrai si le mot est dans la catégorie
	 */
	function Contient($pTexte = '', $p = '') {
		$texte = SG_Texte::getTexte($pTexte);
		$ret = in_array($texte, $this -> valeurs, true);
		return new SG_VraiFaux($ret);
	}

	/**
	 * Met à jour la valeur du champ d'un document (le document n'est pas enregistré)
	 * @since 2.2 
	 * @param SG_Document $pDocument : document à mettre à jour
	 * @param string $pChamp : nom du champ à mettre à jour
	 * @param string $pValeur : valeur du champ
	 * @return SG_Document le document mis à jour
	 */
	static function setChamp($pDocument, $pChamp, $pValeur) {
		if (getTypeSG($pValeur) === '@Collection') {
			$valeur = $pValeur -> elements;
		} elseif (is_array($pValeur)) {
			$valeur = $pValeur;
		} else {
			$valeur = explode(',',$pValeur);
		}
		$pDocument -> setValeur($pChamp, $valeur);
		return $pDocument;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Categorie_trait;
}
?>
