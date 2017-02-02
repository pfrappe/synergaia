<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1 (see AUTHORS file)
 * SG_DictionnaireMethode : Classe de gestion d'une méthode du dictionnaire
 */
class SG_DictionnaireMethode extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@DictionnaireMethode';

	//Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Document physique associé
	public $doc;

	// Code de la méthode du dictionnaire
	public $code;

	/** 1.0.6
	* Construction de l'objet
	* @param string $pCode code de la méthode demandée
	* @param array $pTableau tableau éventuel de propriétés
	*/
	public function __construct($pCode = null, $pTableau = null) {
		$tmpCode = new SG_Texte($pCode);
		$base = SG_Dictionnaire::getCodeBase('@DictionnaireMethode');
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code', '');
		$this -> setValeur('@Type', '@DictionnaireMethode');
	}

	/** 2.0 parm
	* Conversion en chaine de caractères
	* @return string code de la méthode
	*/
	function toString($pDefaut = null) {
		return $this -> code;
	}
	/** 1.1 : ajout
	 * texte de la formule
	 */
	function Formule() {
		return $this -> getValeur('@Action','');
	}
	/** 2.1 ajout ; 2.1.1 tous les codes vides ; 2.3 err 0193
	 * @formula : .@Code=.@Objet.@Texte.@Concatener(".",.@Methode);
	*/
	function preEnregistrer() {
		$objet = $this -> getValeurPropriete('@Objet');
		if ($objet === '@Rien') {
			$ret = new SG_Erreur('0193');
		} else {
			$codeObjet = $objet -> getValeur('@Code');
			$code = $this -> getValeur('@Code','');
			if ($code === '') { // seulement les objets qui n'ont pas encore de code
				$this -> setValeur('@Code', $codeObjet . '.' . $this -> getValeur('@Methode'));
			}
			$ret = new SG_VraiFaux(true);
		}
		return $ret;
	}
	/** 2.1 ajout ; 2.3 $ret
	* Recompilation de l'objet en entier (seulement les objets non système)
	*/
	function postEnregistrer() {
		$ret = false;
		$objet = $this -> getValeurPropriete('@Objet');
		$codeObjet = $objet -> getValeur('@Code');
		if (substr($codeObjet, 0,1) !== '@') { // seulement les objets non système
			$ret = $objet -> compiler();
		} else {
			$compil = new SG_Compilateur();
			$ret = $compil -> compilerObjetSysteme($objet);
		}
		return $ret;
	}
	/** 2.1 ajout
	* Modification dans un ordre préparé
	* @param : 
	* @formula : .@Modifier(.@Titre,.@Objet,.@Methode,.@Action,.@Modele,.@Description)
	**/
	function Modifier () {
		$args = func_get_args();
		if (sizeof($args) === 0) { 
			$ret = parent::Modifier('.@Titre','.@Objet','.@Methode','.@Action','.@Modele','.@Description');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}
	/** 2.1 ajout
	* @param : liste des formules à afficher
	* @formula : .@Afficher(.@Titre,.@Objet,.@Methode,.@Action,.@Modele,.@Description)
	**/
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) { 
			$ret = parent::Afficher('@Titre','@Objet','@Methode','@Action','@Modele','@Description'); 
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}
}
?>
