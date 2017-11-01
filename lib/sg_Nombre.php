<?php
/** SynerGaia contient la classe SG_Nombre de traitement des nombres */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Nombre_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Nombre_trait.php';
} else {
	/** trait vide par défaut */
	trait SG_Nombre_trait{};
}
/**
 * SG_Nombre : Classe SynerGaia de gestion d'un nombre
 * @since 0.0
 * @version 2.6
 */
class SG_Nombre extends SG_Objet {
	/** string Type SynerGaia '@Nombre' */
	const TYPESG = '@Nombre';
	/** string Type SynerGaia  */
	public $typeSG = self::TYPESG;

	/** integer|float Valeur interne du nombre (null par défaut) */
	public $valeur;
	
	/** string Unité de mesure (pas encore géré) */
	public $unite = '';

	/**
	 * Construction de l'objet
	 * @since 1.0.7
	 * @version 2.4 si rien : 0
	 * @param any $pQuelqueChose valeur à partir de laquelle le SG_Nombre est créé
	 * @param any $pUnite code unité ou ou objet @Unite de la quantité
	 */
	function __construct($pQuelqueChose = null, $pUnite = null) {
		$tmpTypeSG = getTypeSG($pQuelqueChose);

		switch ($tmpTypeSG) {
			case 'integer' :
				$this -> valeur = (double)$pQuelqueChose;
				break;
			case 'double' :
				$this -> valeur = $pQuelqueChose;
				break;
			case 'string' :
				if ($pQuelqueChose !== '') {
					$floatString = $pQuelqueChose;
					$floatString = str_replace(" ", "", $floatString);
					$floatString = str_replace(",", ".", $floatString);
					$this -> valeur = floatval($floatString);
				} else {
					$this -> valeur = 0;
				}
				break;
			case '@Formule' :
				$tmpNombre = new SG_Nombre($pQuelqueChose -> calculer());
				$this -> valeur = $tmpNombre -> valeur;
				break;
			case '@Nombre' :
				$this -> valeur = $pQuelqueChose -> valeur;
				$this -> unite = $pQuelqueChose -> unite;
				break;
			case 'NULL' :
				$this -> valeur = 0;
				break;
			default :
				// Si objet SynerGaia
				if (substr($tmpTypeSG, 0, 1) === '@') {
					$this -> valeur = $pQuelqueChose -> toFloat();
				}
		}
		if($pUnite) {
			$this -> unite = $pUnite;
		}
	}

	/**
	 * Conversion en chaine de caractères
	 * @since 1.0.7
	 * @return string texte
	 */
	function toString() {
		$ret = (string)$this -> valeur;
		if($this -> unite !== '' and $this -> unite !== null) {
			$ret .= ' ' . $this -> unite;
		}
		return $ret;
	}

	/**
	 * Conversion en code HTML
	 * @since 1.0.7
	 * @version 2.1.1 SG_HTML
	 * @return string code HTML
	 */
	function toHTML() {
		return new SG_HTML($this -> toString());
	}

	/**
	 * Conversion nombre
	 *
	 * @return float nombre
	 */
	function toFloat() {
		return (double)$this -> valeur;
	}

	/**
	 * Conversion valeur numérique
	 *
	 * @return integer valeur numérique
	 */
	function toInteger() {
		return (integer)$this -> valeur;
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="sg-nombre">' . $this -> toHTML() -> texte . '</span>';
	}

	/**
	 * Modification en HTML
	 *
	 * @param string $pRefChamp référence du champ HTML
	 * @return string code HTML
	 */
	function modifierChamp($pRefChamp = '') {
		return '<input class="sg-nombre" type="text" name="' . $pRefChamp . '" value="' . $this -> toString() . '"/>';
	}

	/**
	 * Comparaison à un autre nombre
	 *
	 * @param indéfini $pQuelqueChose objet avec lequel comparer
	 * @return SG_VraiFaux vrai si les deux nombres sont égaux
	 */
	function Egale($pQuelqueChose) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		return new SG_VraiFaux($this -> valeur === $autreNombre -> valeur);
	}

	/**
	* EstVide si aucune valeur attribuée
	* @since 1.0.7
	* @return SG_VraiFaux
	*/
	public function EstVide() {
		$ret = new SG_VraiFaux($this -> valeur === null);
		return $ret;
	}

	/**
	 * Comparaison à un autre nombre
	 * @version 2.4 Inferieur => InferieurA
	 * @param indéfini $pQuelqueChose objet avec lequel comparer
	 * @return SG_VraiFaux vrai si les deux nombres sont égaux
	 */
	function InferieurA($pQuelqueChose = 0) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		$comparaison = null;
		if ($this -> valeur !== null) {
			if ($this -> valeur < $autreNombre -> valeur) {
				$comparaison = true;
			} else {
				$comparaison = false;
			}
		}
		return new SG_VraiFaux($comparaison);
	}

	/**
	 * Comparaison à un autre nombre
	 * vrai si ce nombre est strictement supérieur à celui passé en paramètre
	 * 
	 * @version 2.4 Superieur => SuperieurA
	 * @param indéfini $pQuelqueChose objet avec lequel comparer
	 * @return SG_VraiFaux 
	 */
	function SuperieurA($pQuelqueChose = 0) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		$ret = null;
		if ($this -> valeur !== null) {
			if ($this -> valeur > $autreNombre -> valeur) {
				$ret = true;
			} else {
				$ret = false;
			}
		}
		return new SG_VraiFaux($ret);
	}

	/**
	 * Incrémentation du nombre
	 * 
	 * @since 1.0.7
	 * @version 1.3 : incrémente sur lui-même
	 * @version 2.6 test integer ; getNombre ; return $this
	 * @param integer|SG_Nombre|SG_Formule $pNbre valeur de l'incrément (par défaut 1)
	 * @return SG_Nombre le SG_Nombre modifié
	 */
	function Incrementer($pNbre = 1) {
		if (is_integer($pNbre)) {
			$this -> valeur += $pNbre;
		} else {
			$this -> valeur += SG_Nombre::getNombre($pNbre);
		}
		return $this;
	}

	/**
	 * Ajout d'un autre nombre
	 * @since 1.0.7
	 * @version 2.4 liste param
	 * @param indéfini $pQuelqueChose valeur du nombre à ajouter
	 * @return SG_Nombre résultat de l'addition
	 */
	function Ajouter($pQuelqueChose = 0) {
		$args = func_get_args();
		$val = $this -> valeur;
		foreach ($args as $arg) {
			$val+= SG_Nombre::getNombre($arg);
		}
		return new SG_Nombre($val, $this -> unite);
	}

	/**
	 * Soustraire un autre nombre
	 * @since 1.0.7
	 * @param indéfini $pQuelqueChose valeur du nombre à soustraire
	 * @return SG_Nombre résultat de la soustraction
	 */
	function Soustraire($pQuelqueChose = 0) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		return new SG_Nombre($this -> valeur - $autreNombre -> valeur, $this -> unite);
	}

	/**
	 * Multiplier par un autre nombre
	 * @since 1.0.7
	 * @param SG_Nombre|SG_Formule $pQuelqueChose valeur du nombre à multiplier
	 * @return SG_Nombre résultat de la multiplication
	 */
	function MultiplierPar($pQuelqueChose = 1) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		return new SG_Nombre($this -> valeur * $autreNombre -> valeur, $this -> unite);
	}

	/**
	 * Diviser par un autre nombre.
	 * Si le diviseur est nul, on renvoie une erreur '0304'
	 * 
	 * @since 1.0.7
	 * @version 2.6 test diviseur erreur 0304
	 * @param SG_Nombre|SG_Formule $pQuelqueChose valeur du nombre diviseur
	 * @return SG_Nombre|SG_Erreur résultat de la division
	 */
	function DiviserPar($pQuelqueChose = 1) {
		if ($pQuelqueChose instanceof SG_Erreur) {
			$ret = $pQuelqueChose;
		} else {
			$diviseur = new SG_Nombre($pQuelqueChose);
			if ($diviseur instanceof SG_Erreur) {
				$ret = $diviseur;
			} elseif ($diviseur -> valeur === 0) {
				$ret = new SG_Erreur('0304');
			} else {
				$ret = new SG_Nombre($this -> valeur / $diviseur -> valeur, $this -> unite);
			}
		}
		return $ret;
	}

	/**
	 * Arrondir un nombre à un nombre de décimales
	 * @since 1.0.7
	 * @param SG_Nombre|SG_Formule $pQuelqueChose nombre de décimales
	 * @return SG_Nombre résultat
	 */
	function Arrondir($pQuelqueChose = 0) {
		$nbDecimales = new SG_Nombre($pQuelqueChose);
		return new SG_Nombre(round($this -> valeur, $nbDecimales -> valeur), $this -> unite);
	}
	/**
	 * Donne le pourcentage d'un nombre par rapport à un autre
	 * @since 1.0.7
	 * @version 2.3 arrondi
	 * @param SG_Nombre|SG_Formule $pQuelqueChose nombre de référence
	 * @param SG_Nombre|SG_Formule $pArrondi nombre de décimales acceptées (défaut 1)
	 * @return SG_Nombre résultat
	 */
	function PourcentageDe($pQuelqueChose = 0, $pArrondi = 1) {
		$nb = new SG_Nombre($pQuelqueChose);
		$ret = new SG_Nombre(0, '%');
		if($nb -> valeur === 0) {
			$ret = new SG_Erreur('0194');//Division par zéro
		} elseif (! $this -> EstVide() -> estVrai()){
			$ret = new SG_Nombre($this -> valeur / ($nb -> valeur) * 100, '%');
		}
		if (!is_null($pArrondi)) {
			$arrondi = SG_Nombre::getNombre($pArrondi);
		} else {
			$arrondi = 1;
		}
		if (getTypeSG($arrondi) !== '@Erreur') {
			$ret -> valeur = round($ret -> valeur, $arrondi);
		}
		return $ret;
	}

	/**
	* Retourne @Vrai si le nombre est dans l'intervalle (bornes incluses)
	* @since 1.3.0 sous le nom EstDans
	* @version 2.4 EstDans => Entre
	* @param SG_Nombre|SG_Formule $pInferieur borne inférieure (défaut 0)
	* @param SG_Nombre|SG_Formule $pSuperieur borne supérieure (défaut 0)
	* @return @VraiFaux
	*/
	public function Entre($pInferieur = 0, $pSuperieur = 0) {
		$inf = new SG_Nombre($pInferieur);
		$sup = new SG_Nombre($pSuperieur);
		$n = $this -> toFloat();
		$ret = new SG_VraiFaux($inf -> toFloat() <= $n and $sup -> toFloat() >= $n);
		return $ret;
	}

	/**
	 * retourne en numérique la valeur passée en paramètre
	 * @since  2.2 ajout
	 * @param string|integer|float|SG_Nombre|SG_Formule $pValeur valeur à traduire
	 * @return nombre
	 **/
	static function getNombre($pValeur) {
		if (is_numeric($pValeur)) {
			$ret = $pValeur;
		} else {
			if (getTypeSG($pValeur) === '@Formule') {
				$valeur = $pValeur -> calculer();
			} else {
				$valeur = $pValeur;
			}
			if (getTypeSG($valeur) === '@Nombre') {
				$ret = $valeur -> valeur;
			} else {
				$ret = floatval($valeur -> toString());
			}
		}
		return $ret;
	}

	/**
	 * Permet la concaténation de texte directement derrière un nombre et retourne un SG_Texte
	 * @since 2.3 ajout
	 * @param SG_Objet texte à concatener
	 * @return SG_Texte nombre + texte
	 **/
	function Concatener() {
		$args = func_get_args();
		$ret = new SG_Texte($this -> toString());
		$ret = call_user_func_array(array($ret,'Concatener'), $args);
		return $ret;
	}

	/**
	 * Crée une collection qui énumère les entiers depuis le nombre jusqu'à la fin.
	 * Si les nombres ne sont pas des entiers, on prend la valeur basse.
	 * 
	 * @since 2.4 ajout
	 * @param integer|SG_Nombre|SG_Formule $pFin le nombre de fin.
	 * @return la SG_Collection
	 **/
	function Jusqua ($pFin = 0) {
		$debut = $this -> toInteger();
		$fin = (integer) SG_Nombre::getNombre($pFin);
		$ret = new SG_Collection();
		for ($i = $debut; $i <= $fin; $i++) {
			$ret -> elements[] = new SG_Nombre($i);
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Nombre_trait;
}
?>
