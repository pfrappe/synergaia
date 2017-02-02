<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Nombre : Classe de gestion d'un nombre
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Nombre_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Nombre_trait.php';
} else {
	trait SG_Nombre_trait{};
}
class SG_Nombre extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Nombre';
	public $typeSG = self::TYPESG;

	// Valeur interne du nombre
	public $valeur = null;
	
	// Unité de mesure (pas géré encore)
	public $unite = '';

	/** 1.0.7
	* Construction de l'objet
	*
	* @param indéfini $pQuelqueChose valeur à partir de laquelle le SG_Nombre est créé
	* @param indéfini $pUnite code unité ou ou objet @Unite de la quantité
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
					$this -> valeur = null;
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
				$this -> valeur = null;
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

	/** 1.0.7
	* Conversion en chaine de caractères
	*
	* @return string texte
	*/
	function toString() {
		$ret = (string)$this -> valeur;
		if($this -> unite !== '' and $this -> unite !== null) {
			$ret .= ' ' . $this -> unite;
		}
		return $ret;
	}

	/** 1.0.7 ; 2.1.1 SG_HTML
	* Conversion en code HTML
	*
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
		return (integer)$this -> toFloat();
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_Nombre">' . $this -> toHTML() -> texte . '</span>';
	}

	/**
	 * Modification
	 *
	 * @param $pRefChamp référence du champ HTML
	 *
	 * @return string code HTML
	 */
	function modifierChamp($pRefChamp = '') {
		return '<input class="champ_Nombre" type="text" name="' . $pRefChamp . '" value="' . $this -> toString() . '"/>';
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
	/** 1.0.7
	* EstVide si aucune valeur attribuée
	*/
	public function EstVide() {
		$ret = new SG_VraiFaux($this -> valeur === null);
		return $ret;
	}
	/**
	 * Comparaison à un autre nombre
	 *
	 * @param indéfini $pQuelqueChose objet avec lequel comparer
	 * @return SG_VraiFaux vrai si les deux nombres sont égaux
	 */
	function Inferieur($pQuelqueChose = 0) {
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
	 *
	 * @param indéfini $pQuelqueChose objet avec lequel comparer
	 * @return SG_VraiFaux vrai si les deux nombres sont égaux
	 */
	function Superieur($pQuelqueChose = 0) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		$comparaison = null;
		if ($this -> valeur !== null) {
			if ($this -> valeur > $autreNombre -> valeur) {
				$comparaison = true;
			} else {
				$comparaison = false;
			}
		}
		return new SG_VraiFaux($comparaison);
	}

	/** 1.0.7 ajout ; 1.3 : incrémente sur lui-même
	 * Incrémentation du nombre
	 *
	 * @param indéfini $pQuelqueChose valeur de l'incrément (par défaut 1)
	 * @return le @Nombre modifié
	 */
	function Incrementer($pQuelqueChose = 1) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		$this -> valeur += $autreNombre -> valeur;
		return '';
	}

	/** 1.0.7
	 * Ajout d'un autre nombre
	 * @param indéfini $pQuelqueChose valeur du nombre à ajouter
	 * @return SG_Nombre résultat de l'addition
	 */
	function Ajouter($pQuelqueChose = 0) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		return new SG_Nombre($this -> valeur + $autreNombre -> valeur, $this -> unite);
	}

	/** 1.0.7
	 * Soustraire un autre nombre
	 * @param indéfini $pQuelqueChose valeur du nombre à soustraire
	 * @return SG_Nombre résultat de la soustraction
	 */
	function Soustraire($pQuelqueChose = 0) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		return new SG_Nombre($this -> valeur - $autreNombre -> valeur, $this -> unite);
	}

	/** 1.0.7
	* Multiplier par un autre nombre
	* @param indéfini $pQuelqueChose valeur du nombre à multiplier
	* @return SG_Nombre résultat de la multiplication
	*/
	function MultiplierPar($pQuelqueChose = 1) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		return new SG_Nombre($this -> valeur * $autreNombre -> valeur, $this -> unite);
	}

	/** 1.0.7
	* Diviser par un autre nombre
	* @param indéfini $pQuelqueChose valeur du nombre diviseur
	* @return SG_Nombre résultat de la division
	*/
	function DiviserPar($pQuelqueChose = 1) {
		$autreNombre = new SG_Nombre($pQuelqueChose);
		return new SG_Nombre($this -> valeur / $autreNombre -> valeur, $this -> unite);
	}

	/** 1.0.7
	* Arrondir un nombre à un nombre de décimales
	* @param $pQuelqueChose nombre de décimales
	* @return SG_Nombre résultat
	*/
	function Arrondir($pQuelqueChose = 0) {
		$nbDecimales = new SG_Nombre($pQuelqueChose);
		return new SG_Nombre(round($this -> valeur, $nbDecimales -> valeur), $this -> unite);
	}
	/** 1.0.7 ; 2.3 arrondi
	* Donne le pourcentage d'un nombre par rapport à un autre
	* @param $pQuelqueChose nombre de référence
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
	/** 1.3.0 ajout
	* @Vrai si le nombre est dans l'intervalle (bornes incluses)
	* @param (nombre) borne inférieure
	* @param (nombre) borne supérieure
	* @return @VraiFaux
	*/
	public function EstDans($pInferieur = 0, $pSuperieur = 0) {
		$inf = new SG_Nombre($pInferieur);
		$sup = new SG_Nombre($pSuperieur);
		$n = $this -> toFloat();
		$ret = new SG_VraiFaux($inf -> toFloat() <= $n and $sup -> toFloat() >= $n);
		return $ret;
	}
	/** 2.2 ajout
	* @param valeur à traduire
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
	/** 2.3 ajout
	* Permet la concaténation de texte directement
	* @param texte à concatener
	* @return SG_Texte
	**/
	function Concatener() {
		$args = func_get_args();
		$ret = new SG_Texte($this -> toString());
		$ret = call_user_func_array(array($ret,'Concatener'), $args);
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Nombre_trait;
}
?>
