<?php
/** SynerGaia fichier traitant l'objet @DictionnairePropriete */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_DictionnairePropriete_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_DictionnairePropriete_trait.php';
} else {
	/** trait vide par défut pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_DictionnairePropriete_trait{};
}

/**  2.4 (see AUTHORS file)
* SG_DictionnairePropriete : Classe de gestion d'une propriété du dictionnaire
*/
class SG_DictionnairePropriete extends SG_Document {
	/** string Type SynerGaia '@DictionnairePropriete' */
	const TYPESG = '@DictionnairePropriete';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;
	
	/** string Code de la propriété du dictionnaire */
	public $code;

	/**
	 * Construction de l'objet
	 * 
	 * @since 1.0.7
	 * @param string $pCodePropriete code de la propriété demandée
	 * @param array $pTableau tableau éventuel des propriétés du document physique
	 */
	public function __construct($pCodePropriete = null, $pTableau = null) {
		$tmpCode = new SG_Texte($pCodePropriete);
		$base = SG_Dictionnaire::CODEBASE;
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code');
		$this -> setValeur('@Type', '@DictionnairePropriete');
	}

	/**
	 * texte de la formule de valeurs possibles
	 * 
	 * @since 1.1 : ajout
	 * @return string
	 */
	function Formule() {
		return $this -> getValeur('@ValeursPossibles');
	}

	/**
	 * Prépare la valeur de @Code (seulement les objets non système car les objets système ne sont traités que par la programmation PHP)
	 * 
	 * @since 2.1 ajout
	 * @version 2.6 test si $objet erreur
	 * @formula : .@Code=.@Objet.@Texte.@Concatener(".",.@Propriete);
	 * @return SG_VraiFaux
	 */
	function preEnregistrer() {
		$objet = $this -> getValeurPropriete('@Objet');
		if (! is_object($objet) or $objet instanceof SG_Rien) {
			$ret = new SG_Erreur('0301');
		} else {
			$codeObjet = $objet -> getValeur('@Code');
			if (substr($codeObjet, 0,1) !== '@') { // seulement les objets non système
				$this -> setValeur('@Code', $codeObjet . '.' . $this -> getValeur('@Propriete'));
			}
			$ret = new SG_VraiFaux(true);
		}
		return $ret;
	}

	/**
	 * Recompilation de l'objet en entier (seulement les objets non système car les objets système ne sont traités que par la programmation PHP)
	 * 
	 * @since 2.1 ajout
	 * @version 2.3 maj dictionnaire ; return
	 * @return boolean|SG_Erreur
	 */
	function postEnregistrer() {
		$ret = true;
		$objet = $this -> getValeurPropriete('@Objet');
		$codeObjet = $objet -> getValeur('@Code');
		if (substr($codeObjet, 0,1) !== '@') { // seulement les objets non système
			$ret = $objet -> compiler();// todo récupérer les erreurs de compilation
		}
		SG_Dictionnaire::isProprieteExiste($codeObjet, $this -> getValeur('@Propriete'), true); // maj dictionnaire
		return $ret;
	}

	/**
	 * Modification dans un ordre préparé
	 * 
	 * @since 2.1 ajout
	 * @param : 
	 * @formula : .@Modifier(.@Titre,.@Objet,.@Propriete,.@Modele,.@Multiple,.@ValeurDefaut,.@ValeursPossibles,.@Description)
	 * @return SG_GTML
	 */
	function Modifier () {
		$args = func_get_args();
		if (sizeof($args) === 0) { 
			$ret = parent::Modifier('.@Titre','.@Objet','.@Propriete','.@Modele','.@Multiple','.@ValeurDefaut','.@ValeursPossibles','.@Description');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}

	/**
	 * Code HTML pour l'affichage propre
	 * 
	 * @since 2.1 ajout
	 * @param : liste des formules à afficher
	 * @formula : .@Afficher(.@Titre,.@Objet,.@Code,.@Modele,.@Multiple,.@ValeurDefaut,.@ValeursPossibles,.@Description)
	 * @return SG_HTML
	 */
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) { 
			$ret = parent::Afficher('@Titre','@Objet','@Code','@Modele','@Multiple','@ValeurDefaut','@ValeursPossibles','@Description'); 
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}

	// 2.4 complément de classe créée par compilation
	use SG_DictionnairePropriete_trait;
}
?>
