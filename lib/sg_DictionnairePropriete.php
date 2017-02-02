<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
 * SG_DictionnairePropriete : Classe de gestion d'une propriété du dictionnaire
 */
class SG_DictionnairePropriete extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@DictionnairePropriete';

	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;
	
	// Code de la propriété du dictionnaire
	public $code;

	/** 1.0.7
	* Construction de l'objet
	*
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
	/** 1.1 : ajout
	* texte de la formule
	*/
	function Formule() {
		return $this -> getValeur('@ValeursPossibles');
	}
	/** 2.1 ajout
	* Prépare la valeur de @Code (seulement les objets non système car les objets système ne sont traités que par la programmation PHP)
	* @formula : .@Code=.@Objet.@Texte.@Concatener(".",.@Propriete);
	*/
	function preEnregistrer() {
		$objet = $this -> getValeurPropriete('@Objet');
		$codeObjet = $objet -> getValeur('@Code');
		if (substr($codeObjet, 0,1) !== '@') { // seulement les objets non système
			$this -> setValeur('@Code', $codeObjet . '.' . $this -> getValeur('@Propriete'));
		}
		return new SG_VraiFaux(true);
	}
	/** 2.1 ajout ; 2.3 maj dictionnaire ; return
	* Recompilation de l'objet en entier (seulement les objets non système car les objets système ne sont traités que par la programmation PHP)
	* return : true ou SG_Erreur
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
	/** 2.1 ajout
	* Modification dans un ordre préparé
	* @param : 
	* @formula : .@Modifier(.@Titre,.@Objet,.@Propriete,.@Modele,.@Multiple,.@ValeurDefaut,.@ValeursPossibles,.@Description)
	**/
	function Modifier () {
		$args = func_get_args();
		if (sizeof($args) === 0) { 
			$ret = parent::Modifier('.@Titre','.@Objet','.@Propriete','.@Modele','.@Multiple','.@ValeurDefaut','.@ValeursPossibles','.@Description');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}
	/** 2.1 ajout
	* @param : liste des formules à afficher
	* @formula : .@Afficher(.@Titre,.@Objet,.@Code,.@Modele,.@Multiple,.@ValeurDefaut,.@ValeursPossibles,.@Description)
	**/
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) { 
			$ret = parent::Afficher('@Titre','@Objet','@Code','@Modele','@Multiple','@ValeurDefaut','@ValeursPossibles','@Description'); 
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}
}
?>
