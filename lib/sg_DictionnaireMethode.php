<?php
/** SYNERGAIA fichier pour le traitement de l'objet @DictionnaireMethode */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_DictionnaireMethode : Classe de gestion d'une méthode du dictionnaire
 * @version 2.1 
 */
class SG_DictionnaireMethode extends SG_Document {
	/** string Type SynerGaia '@DictionnaireMethode' */
	const TYPESG = '@DictionnaireMethode';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/** string Code de la méthode du dictionnaire */
	public $code;

	/**
	 * Construction de l'objet
	 * @since 1.0.6
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

	/**
	 * Conversion en chaine de caractères
	 * @version 2.0 parm
	 * @param any $pDefaut inutilisé (compatibilité)
	 * @return string code de la méthode
	 */
	function toString($pDefaut = null) {
		return $this -> code;
	}

	/**
	 * texte de la formule
	 * @since 1.1 : ajout
	 * @return string texte de la méthode
	 */
	function Formule() {
		return $this -> getValeur('@Action','');
	}

	/**
	 * Traitement avant enregistrement : calcul du code
	 * formule SG : .@Code=.@Objet.@Texte.@Concatener(".",.@Methode);
	 * 
	 * @since 2.1 ajout
	 * @version 2.1.1 tous les codes vides
	 * @version 2.3 err 0193
	 * @version 2.6 recalcul systématique du code
	 * @return SG_VraiFaux|SG_Erreur
	*/
	function preEnregistrer() {
		$objet = $this -> getValeurPropriete('@Objet');
		if (getTypeSG($objet) === '@Rien') {
			$ret = new SG_Erreur('0193');
		} else {
			$codeObjet = $objet -> getValeur('@Code');
			$code = $this -> getValeur('@Code','');
			$this -> setValeur('@Code', $codeObjet . '.' . $this -> getValeur('@Methode'));
			$ret = new SG_VraiFaux(true);
		}
		return $ret;
	}

	/**
	 * Traitement après enregistrement : 
	 * - Recompilation de l'objet en entier (seulement les objets non système)
	 * 
	 * @since 2.1 ajout
	 * @version 2.3 $ret
	 */
	function postEnregistrer() {
		$ret = false;
		$objet = $this -> getValeurPropriete('@Objet');
		$codeObjet = $objet -> getValeur('@Code');
		if (substr($codeObjet, 0,1) !== '@') { // seulement les objets non système
			$ret = $objet -> compiler();
		} else {
			$compil = new SG_Compilateur();
			$compil -> titre = 'Méthode : ' . $this -> toString();
			$ret = $compil -> compilerObjetSysteme($objet);
		}
		return $ret;
	}

	/**
	 * Calcul du code html pour modifier le document éventuellement dans un ordre préparé
	 * formule SynerGaïa : .@Modifier(.@Titre,.@Objet,.@Methode,.@Action,.@Modele,.@Description)
	 * 
	 * @since 2.1 ajout
	 * @param SG_Texte|SG_Formule parm liste de paramètres de champs à modifier 
	 * 
	 * @return SG_HTML
	 */
	function Modifier () {
		$args = func_get_args();
		if (sizeof($args) === 0) { 
			$ret = parent::Modifier('.@Titre','.@Objet','.@Methode','.@Action','.@Modele','.@Description');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}

	/**
	 * Calcul du code html pour l'affichage de la méthode
	 * formule SynerGaïa : .@Afficher(.@Titre,.@Objet,.@Methode,.@Action,.@Modele,.@Description)
	 * @since 2.1 ajout
	 * @param : liste des formules à afficher
	 * @return SG_HTML
	 */
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
