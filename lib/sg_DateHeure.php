<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_DateHeure : Classe de gestion d'un couple date/heure
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_DateHeure_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_DateHeure_trait.php';
} else {
	trait SG_DateHeure_trait{};
}
class SG_DateHeure extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@DateHeure';
	public $typeSG = self::TYPESG;

	// Valeur interne de l'instant
	public $_instant;

	/**
	 * Construction de l'objet
	 *
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle créer l'instant
	 */
	public function __construct($pQuelqueChose = null) {
		if (!is_null($pQuelqueChose)) {
			if (getTypeSG($pQuelqueChose) === '@Formule') {
				$q = $pQuelqueChose -> calculer();
			} else {
				$q = $pQuelqueChose;
			}
			$tmpTypeSG = getTypeSG($q);

			switch ($tmpTypeSG) {
				case 'integer' :
				case 'double' :
					$this -> _instant = $q;
					break;
				case 'string' :
					$strDateHeure = '';
					// Lit l'instant passé
					$elements = explode(' ', $q);
					if (sizeof($elements) === 2) {
						// On a bien une date ET une heure
						$maDate = new SG_Date($elements[0]);
						$monHeure = new SG_Heure($elements[1]);
						$strDateHeure = $maDate -> toString() . ' ' . $monHeure -> toString();
					} else {
						if (sizeof($elements) === 1) {
							// on a une date OU une heure
							if (strpos($elements[0], '/') !== false) {
								// on a uniquement une date
								$maDate = new SG_Date($elements[0]);
								$strDateHeure = $maDate -> toString() . ' 00:00:00';
							} else {
								if (strpos($elements[0], ':') !== false) {
									// on a uniquement une heure
									$monHeure = new SG_Heure($elements[0]);
									$strDateHeure = '01/01/00 ' . $monHeure -> toString();
								}
							}
						}
					}
					if ($strDateHeure !== '') {
						$elements = explode(' ', $strDateHeure);
						$now = getdate();

						$elementsDate = explode('/', $elements[0]);
						$jour = $elementsDate[0];
						if (isset($elementsDate[1])) {
							$mois = $elementsDate[1];
						} else {
							$mois = '' . $now['mon'];
						}
						if (isset($elementsDate[2])) {
							$annee = $elementsDate[2];
						} else {
							$annee = '' . $now['year'];
						}

						$elementsHeure = explode(':', $elements[1]);
						$heures = $elementsHeure[0];
						if (isset($elementsHeure[1])) {
							$minutes = $elementsHeure[1];
						} else {
							$minutes = '00';
						}
						if (isset($elementsHeure[2])) {
							$secondes = $elementsHeure[2];
						} else {
							$secondes = '00';
						}

						if (is_numeric($jour)) {
							if (is_numeric($mois)) {
								if (is_numeric($annee)) {
									if (is_numeric($heures)) {
										if (is_numeric($minutes)) {
											if (is_numeric($secondes)) {
												$this -> _instant = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
											}
										}
									}
								}
							}
						}
					}
					break;
				case '@Date' :
				case '@Heure' :
				case '@DateHeure' :
					$this -> _instant = $q -> getTimestamp();
					break;
				default :
					if (substr($tmpTypeSG, 0, 1) === "@") {
						// Si objet SynerGaia
						if ($tmpTypeSG === '@Formule') {
							$tmp = $q -> calculer();
							$tmpDateHeure = new SG_DateHeure($tmp);
							$this -> _instant = $tmpDateHeure -> _instant;
						} else {
							$tmpDateHeure = new SG_DateHeure($q -> toString());
							$this -> _instant = $tmpDateHeure -> _instant;
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
		return $this -> _instant;
	}

	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString() {
		if (!is_null($this -> _instant)) {
			$ret = date('d/m/Y H:i', $this -> _instant);
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
	* Conversion en code HTML
	*
	* @return string code HTML
	*/
	function toHTML() {
		return $this -> toString();
	}
	/** 1.0.7
	* EstVide
	*/
	public function EstVide() {
		$ret = new SG_VraiFaux($this -> _instant === null);
		return $ret;
	}

	/**
	* Date
	*
	* @return SG_Date Date
	*/
	function Date() {
		return new SG_Date($this -> getTimestamp());
	}

	/**
	* Heure
	*
	* @return SG_Heure Heure
	*/
	function Heure() {
		return new SG_Heure($this -> getTimestamp());
	}
	/** 1.0.7
	* Annee
	*/
	public function Annee() {
		return new SG_Nombre(intval(date('Y', $this -> _instant)));
	}
	/** 1.0.7
	* Mois
	*/
	public function Mois() {
		$d = intval(date('m', $this -> _instant));
		return new SG_Nombre($d);
	}
	/** 1.0.7
	* Jour
	*/
	public function Jour() {
		$d = intval(date('d', $this -> _instant));
		return new SG_Nombre($d);
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_DateHeure" dateheure="X">' . $this -> toHTML() . '</span>';
	}

	/** 1.3.4 SynerGaia.initChampDateHeure() ; 2.0 stopPropagation ; idem mobile
	* Modification d'un champ date heure
	* @param $pRefChamp référence du champ HTML
	* @return string code HTML
	*/
	function modifierChamp ($pRefChamp = '') {
		$auj = SG_Rien::Aujourdhui();
		$id = SG_Champ::idRandom();
		$ret = '';
/*		if (SG_ThemeGraphique::ThemeGraphique() === 'mobile') {
			$selector = '$("#' . $id . '")';
			$ret = '<input class="dateheure" name="' . $pRefChamp . '" type="text" ';
			$ret.= 'value="' . str_replace('"', '&quot;', $this -> toString()) . '" id="'. $id . '" ondblclick="SynerGaia.stopPropagation(event);"></input>';
			$_SESSION['script'][$id] = $selector . '.DatePicker({format : "d/m/Y", current: "' . $auj -> toString() . '",
			onBeforeShow: function(){' . $selector. '.DatePickerSetDate(' . $selector . '.val(), true);},
			onChange: function(formated, dates){
				var tmpdt = '.$selector . '.val();
				'.$selector . '.val(formated + tmpdt.substring(tmpdt.indexOf(" ")));}
			});';
		} else { */
			$ret = '<input id="' . $id . '" class="champ_DateHeure" type="text" name="' . $pRefChamp . '"';
			$ret.= ' value="' . str_replace('"', '&quot;', $this -> toString()) . '" ondblclick="SynerGaia.stopPropagation(event);"/>';
			$ret.= '<script>SynerGaia.initChampDateHeure("#' . $id . '")</script>';
	//	}
		return $ret;
	}

	/**
	* Intervalle avec le SG_DateHeure passé en paramètre
	* @param $pQuelqueChose le temps du début de l'intervalle
	* @return SG_Nombre intervalle en secondes
	*/
	function Intervalle ($pQuelqueChose = null) {
		$tmp = new SG_DateHeure($pQuelqueChose);
		$ret = new SG_Nombre($tmp -> toFloat() - $this -> toFloat());
		return $ret;
	}
	/** 1.0.5
	*/
	function SuperieurA ($pQuelqueChose = null) {
		if ($pQuelqueChose === null) {
			$date = new SG_DateHeure(now);
		} else {
			$date = new SG_DateHeure($pQuelqueChose);
		}
		$ret = new SG_VraiFaux( $this -> _instant >= $date -> _instant);
		return $ret;
	}
	/** 1.0.5
	*/
	function InferieurA ($pQuelqueChose = null) {
		if ($pQuelqueChose === null) {
			$date = new SG_DateHeure(now);
		} else {
			$date = new SG_DateHeure($pQuelqueChose);
		}
		$ret = new SG_VraiFaux( $this -> _instant <= $date -> _instant);
		return $ret;
	}
	/** 1.0.5 ; 1.3.0 static
	*/
	static function validerTemps ($date, $format = 'Y-m-d H:i:s') {
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}
	/** 1.1 ajout
	* [datetime] => 20100106T105820,38+01
	*/
	function setDateTimeDomino ($dt = '') {
		if ($dt !== '') {
			$this -> _instant = mktime(substr($dt,9,2), substr($dt,11,2), substr($dt,13,2), substr($dt,4,2), substr($dt,6,2), substr($dt,0,4));
		}
	}
	/** 1.1 new
	*/
	function Age() {
		$now = new DateTime();
		$interval = $now -> diff($this -> getDate());
		return new SG_Nombre($interval -> y);
	}
	/** 1.1 new
	*/
	function Egale($pAutreDate = '') {
		if ($pAutreDate === '') {
			$date = SG_Rien::Maintenant();
		} else {
			$date = new SG_DateHeure($pAutreDate);
		}
		if ($this -> _instant === $date -> _instant) {
			$ret = new SG_VraiFaux(true);
		} else {
			$ret = new SG_VraiFaux(false);
		}
		return $ret;
	}
	/** 1.1 New
	*/
	function Ajouter($pQuantite = 0, $pUnite = 'heure') {
		$qte = new SG_Nombre($pQuantite);
		$qte = $qte -> valeur;
		$unite = substr(strtolower(SG_Texte::getTexte($pUnite)),0,2);
		$dt = new SG_DateHeure();
		$dt -> _instant = $this -> _instant;
		if ($qte !== 0) {
			switch ($unite) {
				case 'h':
				case 'he':
				case 'ho':
					$qte = $qte * 3600;
					break;
				case 's':
				case 'se':
					break;
				case 'mn':
				case 'mi':
					$qte = $qte * 60;
					break;
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
			$dt -> _instant += $qte;
		}
		return $dt;
	}
	/** 2.1.1 ajout
	* Indique si la date est de l'année en cours ou non
	**/
	function CetteAnnee() {
		return new SG_VraiFaux($this -> Annee() -> toString() === SG_Rien::Aujourdhui() -> Annee() -> toString());
	}
	/** 2.2 ajout
	* éclater un intervalle en fonction de périodes annuelles
	* @param (@Date ou @DateHeure) $pFin : fin de l'intervalle
	* @return @Collection de deux dates heures : périodes @Debut, @Fin
	**/
	function EclaterAnnees($pFin) {
		$ret = new SG_Collection();
		$andebut = $this -> Annee() -> valeur;
		if(getTypeSG($pFin) === '@Formule') {
			$dfin = $pFin -> calculer();
		} else {
			$dfin = $pFin;
		}
		$anfin = $dfin -> Annee() -> valeur;
		$debut = null;
		for($i = $andebut; $i <= $anfin; $i++) {
			$an = (string)$i;
			if( $debut === null) {
				$debut = $this;
			} else {
				$debut = new SG_DateHeure('1/1/' . $an . ' 00:00:00');
			}
			if ($anfin > $i) {
				$fin = new SG_DateHeure('31/12/' . $an . ' 23:59:59');
			} else {
				$fin = $dfin;
			}
			$o = new SG_Objet();
			$o -> proprietes = array('@Debut' => $debut, '@Fin' => $fin);
			$ret -> elements[] = $o;
		}
		return $ret;
	}
	/** 2.3 ajout
	* enlève la quantité fournie à la date et heure
	* @param (@Nombre) : quantité à soustraire
	* @param (@Texte) : code unité (seuls les 2 1ers caractères sont utilisés) h, he, ho, s, se, mn, mi, j, jo, d, da, m, mo, a, y, an, ye
	*/
	function Soustraire($pQuantite = 0, $pUnite = 'h') {
		$qte = new SG_Nombre($pQuantite);
		$qte = $qte -> valeur;
		$unite = substr(strtolower(SG_Texte::getTexte($pUnite)),0,2);
		$dt = new SG_DateHeure();
		$dt -> _instant = $this -> _instant;
		if ($qte !== 0) {
			switch ($unite) {
				case 'h':
				case 'he':
				case 'ho':
					$qte = $qte * 3600;
					break;
				case 's':
				case 'se':
					break;
				case 'mn':
				case 'mi':
					$qte = $qte * 60;
					break;
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
			$dt -> _instant -= $qte;
		}
		return $dt;
	}
	/** 2.3 AJout
	* Détermine si deux @DateHeure sont sur la même journée (utile pour les agendas)
	* @param $pDate : autre @Date ou @DateHeure à comparer. Si absent ou null, @Aujourdhui. Si @Texte, traduit en @Date
	* @return @VraiFaux
	**/
	function EstMemeDate ($pDate = '') {
		if ($pDate === '') {
			$date = SG_Rien::Aujourdhui();
		} else {
			$date = new SG_Date($pDate);
		}
		if ($date -> _date === $this -> Date() -> _date) {
			$ret = new SG_VraiFaux(true);
		} else {
			$ret = new SG_VraiFaux(false);
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_DateHeure_trait;
}
?>
