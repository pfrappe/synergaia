<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Date : Classe de traitement des dates
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Date_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Date_trait.php';
} else {
	trait SG_Date_trait{};
}
class SG_Date extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Date';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	/**
	 * Valeur interne de la date
	 */
	public $_date;

	/** 1.1 : traite DateTime ; 1.3.0 correction ; 2.3 si @DateHeure
	 * Construction de l'objet
	 *
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle créer la date
	 */
	public function __construct($pQuelqueChose = null) {
		if (!is_null($pQuelqueChose)) {
			$tmpTypeSG = getTypeSG($pQuelqueChose);

			switch ($tmpTypeSG) {
				case 'integer' :
				case 'double' :
					$this -> _date = strtotime(date('d-m-Y', $pQuelqueChose));
					break;
				case 'string' :
					// Lit la date passée
					$elements = explode('/', $pQuelqueChose);
					if (sizeof($elements) === 3) {
						$jour = $elements[0];
						$mois = $elements[1];
						$annee = $elements[2];
						if (is_numeric($jour)) {
							if (is_numeric($mois)) {
								if (is_numeric($annee)) {
									$this -> _date = mktime(0, 0, 0, $mois, $jour, $annee);
								}
							}
						}
					}
					break;
				case 'object' :
					if(get_class($pQuelqueChose) === 'DateTime') {
						$this -> _date = $pQuelqueChose -> getTimestamp();
					}
					break;
				case '@Date' :
					$this -> _date = $pQuelqueChose -> _date;
					break;
				case '@DateHeure' :
					$d = $pQuelqueChose -> _instant;
					$this -> _date = mktime(0, 0, 0, date('m', $d)  , date('d', $d), date('Y', $d));
					break;
				default :
					if (substr($tmpTypeSG, 0, 1) === "@") {
						// Si objet SynerGaia
						if ($tmpTypeSG === '@Formule') {
							$tmp = $pQuelqueChose -> calculer();
							$tmpDate = new SG_Date($tmp);
							$this -> _date = $tmpDate -> _date;
						} else {
							$tmpDate = new SG_Date($pQuelqueChose -> toString());
							$this -> _date = $tmpDate -> _date;
						}
					}
			}
		}
	}

	/**
	 * Renvoie le timestamp unix de l'objet
	 *
	 * @return integer
	 */
	public function getTimestamp() {
		return $this -> _date;
	}
	/** 1.1 : ajout
	 * Renvoie un objet php DateTime
	 */
	public function getDate() {
		$ret = new DateTime();
		$ret -> setTimestamp($this -> _date);
		return $ret;
	}

	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString() {
		if (!is_null($this -> _date)) {
			$ret = date('d/m/Y', $this -> _date);
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * Conversion valeur numérique
	 *
	 * @return float valeur numérique
	 */
	function toFloat() {
		return (double)$this -> getTimestamp();
	}

	/**
	 * Conversion valeur numérique
	 *
	 * @return integer valeur numérique
	 */
	function toInteger() {
		return (integer)$this -> getTimestamp();
	}

	/**
	* Affichage
	* @return string code HTML
	*/
	function afficherChamp() {
		return '<span class="champ_Date">' . $this -> toHTML() -> texte . '</span>';
	}

	/** 1.3.3 idRandom et init ; 2.0 stopPropagation
	* Modification
	*
	* @param $pRefChamp référence du champ HTML
	*
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '') {
		// Identifiant unique du tableau
		$idChamp = SG_Champ::idRandom();
		$ret = '<input id="' . $idChamp . '" class="champ_Date" type="text" name="' . $pRefChamp . '"';
		$ret.= ' value="' . str_replace('"', '&quot;', $this -> toString()) . '" ondblclick="SynerGaia.stopPropagation(event);"/>';
		$ret.= '<script>SynerGaia.initChampDate("#' . $idChamp . '")</script>';
		return $ret;
	}
	/** 1.1 : ajout
	* EstVide
	*/
	public function EstVide() {
		$ret = new SG_VraiFaux($this -> _date === null);
		return $ret;
	}
	/** 1.0.7 : ajout ; 1.3.1 cas null
	* Annee
	*/
	public function Annee() {
		if (is_null($this -> _date)) {
			$ret = new SG_Rien();
		} else {
			$d = intval(date('Y', $this -> _date));
			$ret = new SG_Nombre($d);
		}
		return $ret;
	}
	/** 1.0.7 : ajout
	* Mois
	*/
	public function Mois() {
		if (is_null($this -> _date)) {
			$ret = new SG_Rien();
		} else {
			$d = intval(date('m', $this -> _date));
			$ret = new SG_Nombre($d);
		}
		return $ret;
	}
	/** 1.0.7 : ajout
	* Jour
	*/
	public function Jour() {
		if (is_null($this -> _date)) {
			$ret = new SG_Rien();
		} else {
			$d = intval(date('d', $this -> _date));
			$ret = new SG_Nombre($d);
		}
		return $ret;
	}
	/** 1.1 : ajout
	*/   
    function SuperieurA ($pQuelqueChose = null) {
		if ($pQuelqueChose === null) {
			$date = new SG_Date(today);
		} else {
			$date = new SG_Date($pQuelqueChose);
		}
		$ret = new SG_VraiFaux( $this -> _date >= $date -> _date);
		return $ret;
	}
	/** 1.1 : ajout
	*/   
    function InferieurA ($pQuelqueChose = null) {
		if ($pQuelqueChose === null) {
			$date = new SG_Date(today);
		} else {
			$date = new SG_Date($pQuelqueChose);
		}
		$ret = new SG_VraiFaux( $this -> _date <= $date -> _date);
		return $ret;
	}
	/** 1.1 : ajout
	* AJouter ou retranche du temps
	*/
	public function Ajouter($pJours = 0, $pMois = 0, $pAnnees = 0) {
		$intval = '';
		$n = new SG_Nombre($pJours);
		$n = $n -> toInteger();
		if($n !== 0) {
			$intval .= $n . ' days ';
		}
		$n = new SG_Nombre($pMois);
		$n = $n -> toInteger();
		if($n !== 0) {
			if($intval !== '' and $n > 0) {
				$intval .= '+';
			}
			$intval .= $n . ' months ';
		}
		$n = new SG_Nombre($pAnnees);
		$n = $n -> toInteger();
		if($n !== 0) {
			if($intval !== '' and $n > 0) {
				$intval .= '+';
			}
			$intval .= $n . ' years ';
		}
		$date = new SG_Date($this -> getDate() -> add(DateInterval::createFromDateString($intval)));
		return $date;
	}
	/** 1.1 new
	*/
	function Age() {
		$now = new DateTime();
		$interval = $now -> diff($this -> getDate());
		return new SG_Nombre($interval -> y);
	}	/** 1.1 new
	*/
	function Egale($pAutreDate = '') {
		if ($pAutreDate === '') {
			$date = SG_Rien::Aujourdhui();
		} else {
			$date = new SG_Date($pAutreDate);
		}
		if ($this -> _date === $date -> _date) {
			$ret = new SG_VraiFaux(true);
		} else {
			$ret = new SG_VraiFaux(false);
		}
		return $ret;
	}
	/** 1.3.4 ajout
	* Format string pour tri (aaaa/mm/jj)
	**/
	function AnMoisJour() {
		$ret = new SG_Texte('');
		if (is_null($this -> _date)) {
			$ret = '';
		} else {
			$ret -> texte = date('Y', $this -> _date) . '/' . date('m', $this -> _date) . '/' . date('d', $this -> _date);
		}
		return $ret;
	}
	/** 2.1.1 ajout ; 2.3 correction days
	* calcule le nombre de jours écoulé avec la date passée en paramètre
	* @param (SG_Date) $pDate : autre date pour l'intervalle
	**/
	function DureeDepuis($pDate = null) {
		$ret = 0;
		if ($pDate !== null) {
			$d = new SG_Date($pDate);
			$d = $d -> getDate();
			$interval = $d -> diff($this -> getDate());
			$ret = $interval -> days;
		}
		return new SG_Nombre($ret);
	}
	/** 2.1.1 ajout
	* Indique si la date est de l'année en cours ou non
	**/
	function CetteAnnee() {
		return new SG_VraiFaux($this -> Annee() -> toString() === SG_Rien::Aujourdhui() -> Annee() -> toString());
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
	/** 2.3 ajout
	* enlève la quantité fournie à la date et heure
	* @param (@Nombre) : quantité à soustraire
	* @param (@Texte) : code unité (seuls les 2 1ers caractères sont utilisés) j, jo, d, da, m, mo, a, y, an, ye
	*/
	function Soustraire($pQuantite = 0, $pUnite = 'j') {
		$qte = new SG_Nombre($pQuantite);
		$qte = $qte -> valeur;
		$unite = substr(strtolower(SG_Texte::getTexte($pUnite)),0,2);
		$dt = new SG_Date();
		$dt -> _date = $this -> _date;
		if ($qte !== 0) {
			switch ($unite) {
				case 'j':
				case 'jo';
				case 'd':
				case 'da':
					$qte = $qte * 86400; // nb secondes dans jour
					break;
				case 'm':
				case 'mo':
					$qte = $qte * 2592000; // nb seconds dans 30 jours;
					break;
				case 'a':
				case 'y':
				case 'an':
				case 'ye':
					$qte = $qte * 31536000; // nb seconds dans 365 jours;
				break;
			}
			$dt -> _date -= $qte;
		}
		return $dt;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Date_trait;
}
?>
