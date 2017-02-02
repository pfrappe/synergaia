<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.2 (see AUTHORS file)
* SG_Categorie : Classe de texte pour la gestion de catégorie de classement
* La différence avec un @Texte ayant des valeurs possibles est que la liste à proposer provient des valeurs existantes et peut être étendue
* Donc un objet @Categorie est nécessairement stocké dans un @Document et les valeurs sont recherchées par une vue réduite
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Categorie_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Categorie_trait.php';
} else {
	trait SG_Categorie_trait{};
}
class SG_Categorie extends SG_Texte {
	// Type SynerGaia
	const TYPESG = '@Categorie';
	public $typeSG = self::TYPESG;
	
	// type d'objet auquel appartient la catégorie
	public $typeObjet = '';
	
	// 2.1 ajout : liste des valeurs
	public $valeurs = array();
	
	// 2.2 ajout : choix multiple autorisé
	public $multiple = false;

	/** 1.3.4 ajout de l'objet ; 2.1 ajout méthode new
	* Construction de l'objet : voir SG_Texte, mais texte est stocké sous forme de collection ou de textes séparés par des virgules
	**/
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
	
	/** 1.3.4 ajout ; 2.0 stoppropagation dblclick
	* Modification
	* @param string $pRefChamp référence du champ HTML
	* @param $pObjet (string, @Texte ou @Document ou @DictionnaireObjet)
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '', $pValeursPossibles = '') {
		$ret = '';
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
		// Si on a passé une liste de valeurs proposées
		if (is_array($vp)) {
			$idTable = SG_Champ::idRandom();
			$valeurs = json_encode($vp, false);
			$ret .= '<input id="' . $idTable . '" class="champ_Texte categorie" type="text" name="' . $pRefChamp . '" value="' . $valActuelle . '"
			 multiple="1"/><script>SynerGaia.initCategorie("' . $idTable . '",'.$valeurs.')</script>';
/**
			$nb = sizeof($vp);
			for ($i = 0; $i < $nb; $i++) {
				$valeurProposee = $vp[$i];
				if (is_object($valeurProposee)) {
					$valeurProposee = $valeurProposee -> toString();
				}
				$valeurAffichee = '';
				// Eclate si un "|" est présent : ValeurAffichée|ValeurEnregistrée
				if (strpos($valeurProposee, '|') === false) {
					// Pas de '|'
					$valeurAffichee = $valeurProposee;
				} else {
					$elements = explode('|', $valeurProposee);
					$valeurAffichee = $elements[0];
					$valeurProposee = $elements[1];
				}
				$select = '';
				if (in_array($valeurProposee, $this -> valeurs)) {
					$select = 'selected';
				}
				$ret .= '<option value="' . $valeurProposee . '" ' . $select . '>' . $valeurAffichee . '</option>';
			}
			$ret .= '</datalist>';
**/
		} else {
			$ret .= '<textarea class="champ_Texte" name="' . $pRefChamp . '">' . $valActuelle . '</textarea>';
		}
		return $ret;
	}
	/* 1.3.4 ajout ; 2.2 modif js
	* @param $pTypeObjet (objet ou texte) objet de référence pour chercher les valeurs possibles
	* @return collection des textes de valeurs possibles
	*/
	function Choix() {
		// préparation de la phrase de sélection
		$objet = getTypeSG($this -> contenant -> document);
		$nomChamp = $this -> contenant -> codeChamp;
		$codebase = $this -> contenant -> codeBase;
		$n = "doc['" . $nomChamp . "']";
		$jsMap = "function(doc){if(doc['@Type']==='" . $objet . "'){if(" . $n . "!=null){var tags=" . $n . ";for(var i=0;i<tags.length;i++){emit(tags[i],1)}}}}";
		$jsReduce = "function(keys,values,rereduce) {return 1}";
		$js = array('all' => array('map' => $jsMap), 'categorie' => array('map' => $jsMap, 'reduce' => $jsReduce));
		// création de la vue si nécessaire
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
	/** 2.1 ajout ; 2.2 parm $p
	* Cherche si la catégorie contient la chaine ou le @Texte passée en paramètre)
	* @param $pTexte : le mot recherché
	* @param $p : inutilisé (compatibilité avec la méthode SG_Texte::Contient)
	* @return @VraiFaux : vrai si le mot est dans la catégorie
	**/
	function Contient($pTexte = '', $p = '') {
		$texte = SG_Texte::getTexte($pTexte);
		$ret = in_array($texte, $this -> valeurs, true);
		return new SG_VraiFaux($ret);
	}
	/** 2.2 ajout
	* Met à jour la valeur du champ d'un document (le document n'est pas enregistré)
	* @param SG_Document : document à mettre à jour
	* @param string : nom du champ à mettre à jour
	* @param string : valeur du champ
	* @return : le document mis à jour
	**/
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
