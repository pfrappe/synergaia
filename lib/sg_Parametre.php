<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.4 (see AUTHORS file)
 * SG_Parametre : Classe de gestion des paramètres
 */
class SG_Parametre extends SG_Document {
	/**
	 * Type SynerGaia
	 */
	const TYPESG = '@Parametre';
	/**
	 * Type SynerGaia de l'objet
	 */
	public $typeSG = self::TYPESG;
	/**
	 * Code de la base
	 */
	const CODEBASE = 'synergaia_parametres';
	/**
	 * Code du champ d'enregistrement de la valeur
	 */
	const CHAMP_VALEUR = '@Valeur';
	/**
	 * Code du champ d'enregistrement du type
	 */
	const CHAMP_TYPE = '@ValeurType';
	/** 1.0.7
	 * Construction de l'objet
	 *
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
		$this -> initDocumentCouchDB(SG_Dictionnaire::getCodeBase('@Parametre')  . '/' . $code, $pTableau);
		$this -> code = $this -> getValeur('@Code');
		$this -> setValeur('@Type', '@Parametre');
	}

	/**
	 * Lecture de la valeur du paramètre
	 *
	 * @return string valeur du paramètre
	 */
	public function Lire() {
		$valeur = $this -> doc -> getValeur(SG_Parametre::CHAMP_VALEUR, null);
		if (is_null($valeur)) {
			$ret = new SG_Rien();
		} else {
			$type = $this -> doc -> getValeur(SG_Parametre::CHAMP_TYPE, '');
			$ret = faireObjetSynerGaia($valeur, $type);
		}
		if (getTypeSG($ret) === '@Collection') {
			switch (sizeof($ret -> elements)) {
				case 0 :
					$ret = new SG_Rien();
					break;
				case 1 :
					$ret = faireObjetSynerGaia($ret -> elements[0]);
					break;
			}
		}		
		return $ret;
	}

	/** 1.3.4 retour $this -> Lire ou SG_Erreur
	* Ecriture de la valeur du paramètre
	*
	* @param pQuelqueChose valeur a définir
	* @return string valeur du paramètre
	*/
	public function Definir() {
		$args = func_get_args ();
		// simplifier les arguments
		if (sizeof($args) === 1) {
			if (getTypeSG($args[0]) === '@Formule') {
				$args = $args[0] -> calculer();
			} elseif (getTypeSG($args[0]) === '@Collection') {
				$args = $args[0] -> elements;
			}
		}
		$valeurs = array();
		foreach ($args as $arg) {
			$valeur = $arg;
			if (getTypeSG($arg) === '@Formule') {
				$valeur = $arg -> calculer();
			}
			$type = getTypeSG($valeur);
			if (gettype($valeur) === 'object') {
				if ($type === '@Texte' ) {
					$valeur = $valeur -> texte;
				} else {
					$valeur = $valeur -> toString();
				}
			}
			$valeurs[] = $valeur;
		}
		$retDefinir = $this -> setValeur(SG_Parametre::CHAMP_VALEUR, $valeurs);
		$refDefinir = $retDefinir && $this -> setValeur(SG_Parametre::CHAMP_TYPE, $type);
		$refDefinir = $retDefinir && $this -> setValeur('@Type', '@Parametre');
		$retEnregistrer = $this -> Enregistrer();
		if ($retDefinir && ! $retEnregistrer -> estErreur()) {
			$ret = $this -> Lire();
		} else {
			$ret = $retEnregistrer;
		}
		return $ret;
	}
}
?>
