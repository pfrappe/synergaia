<?php
/** Fichier de traitement de l'objet @Parametre */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Parametre_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Parametre_trait.php';
} else {
	/** trait vide par défaut pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.4  */
	trait SG_Parametre_trait{};
}

/** SynerGaia  (see AUTHORS file)
 * SG_Parametre : Classe de gestion des paramètres de l'application
 * @since 1.3.4
 * @version 2.6
 */
class SG_Parametre extends SG_Document {
	/** string Type SynerGaia '@Parametre' */
	const TYPESG = '@Parametre';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/** string Code de la base */
	const CODEBASE = 'synergaia_parametres';

	/** string Code du champ d'enregistrement de la valeur */
	const CHAMP_VALEUR = '@ValeurPHP';
	
	/** any vameur en format SynerGaia */
	private $valeur = null;

	/** 
	 * Construction de l'objet
	 * @since 1.0.7
	 * @param indéfini $pCode code du paramètre demandé
	 * @param array $pTableau tableau éventuel des propriétés du document
	 */
	function __construct($pCode = '', $pTableau = null) {
		$tmpCode = new SG_Texte($pCode);
		$code = $tmpCode -> texte;
		$base = self::CODEBASE;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$ret = $this -> initDocumentCouchDB(SG_Dictionnaire::getCodeBase(self::TYPESG)  . '/' . $code, $pTableau);
		$this -> getValeurParm();
		$this -> code = $this -> doc -> codeDocument;
		$this -> setValeur('@Type', self::TYPESG);
	}

	/**
	 * Lecture de la valeur du paramètre (si pas rempli : SG_Rien)
	 * 
	 * @version 2.6 unserialize
	 * @return SG_Objet valeur du paramètre
	 */
	public function getValeurParm() {
		if (is_null($this -> valeur)) {
			$valeur = $this -> doc -> getValeur(self::CHAMP_VALEUR, null);
			if (is_null($valeur)) {
				$this -> valeur = new SG_Rien();
			} else {
				$this -> valeur = unserialize($valeur);
			}
		}	
		$ret = $this -> valeur;
		return $ret;
	}

	/** 
	 * Ecriture de la valeur du paramètre
	 * 
	 * @since 1.3.4
	 * @version 2.6 serialize
	 * @param pQuelqueChose valeur a définir
	 * @return SG_Erreur|SG_Parametre paramètre ou code erreur
	 */
	public function setValeurParm($pValeur = null) {
		if (is_null($pValeur)) {
			$this -> valeur = new SG_Rien();
		} elseif (getTypeSG($pValeur) === SG_Formule::TYPESG) {
			$this -> valeur = $pValeur -> calculer();
		} else {
			$this -> valeur = $pValeur;
		}
		$this -> doc -> setValeur(SG_Parametre::CHAMP_VALEUR, serialize($this -> valeur));
		$this -> doc -> setValeur('@Type', self::TYPESG);
		$res = $this -> Enregistrer();
		if ($res -> estErreur()) {
			$ret = $res;
		} else {
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * Cherche le paramètre, augmente la valeur de 1, l'enregistre et le retourne
	 * Si ce n'est pas du type @Nombre, retourne une erreur
	 * pas de paramètres
	 * @since 2.6
	 * @return SG_Nombre|SG_Erreur la nouvelle valeur ou une erreur
	 */
	function Prochain() {
		$type = getTypeSG($this -> valeur);
		if ($type === SG_Erreur::TYPESG) {
			$ret = $this -> valeur;
		} elseif ($type !== SG_Nombre::TYPESG) {
			$ret = new SG_Erreur ('0280');
		} else {
			// augmenter la valeur et enregistrer
			$this -> setValeurParm($this -> valeur -> Ajouter(1));
			$ret = $this -> valeur;
		}
		return $ret;
	}

	/**
	 * Retourne ou met à jours la valeur comme objet SynerGaia
	 * Si param : set valeur, sinon get
	 * @since 2.6
	 * @return SG_Objet|SG_Parametre soit la valeur si get, soit $this si set
	 */
	function Valeur() {
		if (func_num_args() === 0) {
			$ret = $this -> valeur;
		} else {
			$this -> setValeurParm(func_get_arg(0));
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * Retourne la valeur comme objet SynerGaia
	 * @since 2.6
	 * @return SG_Texte ou dérivé
	 */
	function Code() {
		$ret = new SG_Texte($this -> doc -> codeDocument);
		return $ret;
	}

	/**
	 * Modification dans un ordre préparé
	 * 
	 * @since 2.6 ajout
	 * @param any liste des formules des champs à modifier
	 * @formula : .@Modifier(.@Titre,.@Valeur)
	 * @return SG_GTML
	 */
	function Modifier () {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$txt = $this -> Code() -> AfficherCommeTitre();
			$ret = $txt -> Concatener(parent::Modifier('.@Titre','.@Valeur'));
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}

	/**
	 * Code HTML pour l'affichage propre
	 * 
	 * @since 2.6 ajout
	 * @param : liste des formules à afficher
	 * @formula : .@Afficher(.@Titre,.@Valeur)
	 * @return SG_HTML
	 */
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) { 
			$ret = parent::Afficher('@Code','@Titre');
			$v = $this -> Valeur();
			$v -> titre = 'Valeur';
			$ret -> texte.= $v -> Afficher() -> texte; 
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}


	/** complément de classe créée par compilation
	* @since 2.4. 
	*/
	use SG_Parametre_trait;
}
?>
