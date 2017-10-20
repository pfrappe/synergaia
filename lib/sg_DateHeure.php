<?php
/** sg_DateHeure.php SynerGaia contient la classe SG_DateHeure de traitement des dates et heures */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_DateHeure_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_DateHeure_trait.php';
} else {
	/** trait vide par défaut */
	trait SG_DateHeure_trait{};
}

/**
 * SG_DateHeure : Classe de gestion d'un couple date/heure
 * @version 2.6.0 _instant est un DateTime
 */
class SG_DateHeure extends SG_Objet {
	/** string Type SynerGaia */
	const TYPESG = '@DateHeure';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** Valeur interne de l'instant
	 * @version 2.6 c'est un DateTime
	 * @var DateTime
	 */
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
				case 'double' : // timestamp unix
					$this -> _instant = new DateTime('@' . $q);
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
												$t = $annee . '-' . $mois . '-' . $jour . ' ' . $heures . ':' . $minutes . ':' . $secondes;
												$this -> _instant = DateTime::createFromFormat('Y-m-d H:i:s', $t);
											}
										}
									}
								}
							}
						}
					}
					break;
				case '@Date' :
					$this -> _instant = $q -> _date;
					break;
				case '@Heure' :
					$this -> _instant = $q -> _heure;
					break;
				case '@DateHeure' :
					$this -> _instant = $q -> _instant;
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
	 * @version 2.6 DateTime -> retourne +-aaaammjj.hhmnss
	 * @return integer
	 */
	public function getTimestamp() {
		if (is_null($this -> _instant)) {
			$ret = false;
		} else {
			
			$ret = $this -> _instant -> getTimeStamp();
		}
		return $ret;
	}

	/**
	 * Conversion en chaine de caractères
	 * @version 2.6 DateTime
	 * @return string texte
	 */
	function toString() {
		if (!is_null($this -> _instant)) {
			$ret = $this -> _instant -> format('d/m/Y H:i');
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * Conversion valeur numérique sous la forme aaaammjj.hhmnss
	 * 
	 * @since 0.1
	 * @return float valeur numérique
	 */
	function toFloat() {
		return (double)$this -> getTimestamp();
	}

	/**
	 * Conversion valeur numérique Unix
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
	/**
	 * EstVide
	 * @since 1.0.7
	 * @return SG_VrauFaux
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
		if (is_null($this -> _instant)) {
			$ret = false;
		} else {
			$ret = new SG_Date($this -> _instant -> format('d/m/Y'));
		}
		return $ret;
	}

	/**
	 * Heure
	 *
	 * @return SG_Heure Heure
	 */
	function Heure() {
		if (is_null($this -> _instant)) {
			$ret = false;
		} else {
			$ret = new SG_Heure($this -> _instant -> format('H:i:s'));
		}
		return $ret;
	}
	/**
	 * Annee
	 * @since 1.0.7
	 * @return SG_Nombre
	 */
	public function Annee() {
		if (is_null($this -> _instant)) {
			$ret = new SG_Rien();
		} else {
			$ret = new SG_Nombre(intval($this -> _instant -> format('Y')));
		}
		return $ret;
	}
	/**
	 * Mois
	 * @since 1.0.7
	 * @return SG_Nombre
	 */
	public function Mois() {
		if (is_null($this -> _instant)) {
			$ret = false;
		} else {
			$ret = new SG_Nombre(intval($this -> _instant -> format('m')));
		}
		return $ret;
	}
	/**
	 * Jour
	 * @since 1.0.7
	 * @return SG_Nombre
	 */
	public function Jour() {
		if (is_null($this -> _instant)) {
			$ret = false;
		} else {
			$ret = new SG_Nombre(intval($this -> _instant -> format('d')));
		}
		return $ret;
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="sg-dateheure" dateheure="X">' . $this -> toHTML() . '</span>';
	}

	/**
	 * Modification d'un champ date heure
	 * @param string $pRefChamp référence du champ HTML
	 * @return string code HTML
	 */
	function modifierChamp ($pRefChamp = '') {
		$auj = SG_Rien::Aujourdhui();
		$id = SG_SynerGaia::idRandom();
		$ret = '<input id="' . $id . '" class="sg-dateheure" type="text" name="' . $pRefChamp . '"';
		$ret.= ' value="' . str_replace('"', '&quot;', $this -> toString()) . '" ondblclick="SynerGaia.stopPropagation(event);"/>';
		$ret.= '<script>SynerGaia.initChampDateHeure("#' . $id . '")</script>';
		return $ret;
	}

	/**
	 * Intervalle avec le SG_DateHeure passé en paramètre
	 * @param SG_DateHeure|SG_Formule $pQuelqueChose le temps du début de l'intervalle
	 * @return SG_Nombre intervalle en secondes
	 */
	function Intervalle ($pQuelqueChose = null) {
		$tmp = new SG_DateHeure($pQuelqueChose);
		$ret = new SG_Nombre($tmp -> toFloat() - $this -> toFloat());
		return $ret;
	}
	
	/** 
	 * Compare avec la date heure passée en paramètre
	 * @since 1.0.5
	 * @param SG_DateHeure|SG_Formule $pQuelqueChose date-heure à comparer
	 * @return SG_VraiFaux
	 */
	function SuperieurA ($pQuelqueChose = null) {
		if ($pQuelqueChose === null) {
			$date = new SG_DateHeure(new DateTime());
		} else {
			$date = new SG_DateHeure($pQuelqueChose);
		}
		$ret = new SG_VraiFaux( $this -> _instant >= $date -> _instant);
		return $ret;
	}
	/**
	 * Compare avec la date heure passée en paramètre
	 * @since 1.0.5
	 * @param SG_DateHeure|SG_Formule $pQuelqueChose date-heure à comparer
	 * @return SG_VraiFaux
	 */
	function InferieurA ($pQuelqueChose = null) {
		if ($pQuelqueChose === null) {
			$date = new SG_DateHeure(new DateTime());
		} else {
			$date = new SG_DateHeure($pQuelqueChose);
		}
		$ret = new SG_VraiFaux( $this -> _instant <= $date -> _instant);
		return $ret;
	}
	/**
	 * valide avec un format
	 * @since 1.0.5
	 * @param SG_DateHeure|SG_Formule $date date-heure à valider
	 * @param string $format format de date à contrôler (par défaut 'Y-m-d H:i:s')
	 * @return boolean
	 */
	static function validerTemps ($date, $format = 'Y-m-d H:i:s') {
		$d = DateTime::createFromFormat($format, $date);
		return $d and $d -> format($format) == $date;
	}
	/**
	 * initialise la date à partir d'un format Domino [datetime] => 20100106T105820,38+01
	 * @since 1.1
	 * @param SG_DateHeure|SG_Formule $dt date-heure à comparer
	 * @return SG_DateHeure $this
	 */
	function setDateTimeDomino ($dt = '') {
		if ($dt !== '') {
			$t = substr($dt,0,4) . '-' . substr($dt,4,2) . '-' . substr($dt,6,2) . ' ' . substr($dt,9,2) . ':' . substr($dt,11,2) . ':' . substr($dt,13,2);
			$this -> _instant = DateTime::createFromFormat('Y-m-d h:i:s', t);
		}
		return $this;
	}
	/**
	 * Calcule l'âge en années complètes
	 * @since 1.1
	 * @return SG_Nombre nombre d'années d'âge
	 */
	function Age() {
		$now = new DateTime();
		$interval = $now -> diff($this -> getDate());
		return new SG_Nombre($interval -> y);
	}
	/**
	 * Inidique si la date-heure est la même que celle passée en paramètre
	 * @since 1.1 new
	 * @param SG_DateHeure|SG_Formule date-heure à comparer
	 * @return SG_VraiFaux
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

	/**
	 * Ajoute une quantité de temps à la date-heure (retourne un autre objet)
	 * @since 1.1
	 * @version 2.6 DateTime
	 * @param SG_Nombre|SG_Formule|integer Quantité à ajouter
	 * @param SG_Texte|SG_Formule|string unté de la quantité
	 * @return SG_DateHeure la nouvelle date heure
	 */
	function Ajouter($pQuantite = 0, $pUnite = 'h') {
		$qte = new SG_Nombre($pQuantite);
		$qte = $qte -> valeur;
		$unite = substr(strtolower(SG_Texte::getTexte($pUnite)),0,2);
		$dt = new SG_DateHeure();
		$dt -> _instant = $this -> _instant;
		if ($dt -> _instant != null and $qte !== 0) {
			switch ($unite) {
				case 'h':
				case 'he':
				case 'ho':
					$q = 'T' . $qte . 'H';
					break;
				case 's':
				case 'se':
					$q = 'T' . $qte . 'S';
					break;
				case 'mn':
				case 'mi':
					$q = 'T' . $qte . 'M';
					break;
				case 'j':
				case 'jo':
				case 'd':
				case 'da':
					$q = $qte . 'D';
					break;
				case 'm':
				case 'mo':
					$q = $qte . 'M';
					break;
				case 'a':
				case 'y':
				case 'an':
				case 'ye':
					$q = $qte . 'Y';
					break;
				default:
					$q = '';
					break;
				
			}
			$dt -> _instant -> add(new DateInterval('P' . $q));
		}
		return $dt;
	}

	/** 
	 * Indique si la date est de l'année en cours ou non
	 * @since 2.1.1
	 * @return SG_VraiFaux 
	 */
	function CetteAnnee() {
		return new SG_VraiFaux($this -> Annee() -> toString() === SG_Rien::Aujourdhui() -> Annee() -> toString());
	}

	/**
	 * éclater un intervalle en fonction de périodes annuelles
	 * @since 2.2
	 * @param SG_Date|SG_DateHeure|SG_Formule $pFin : fin de l'intervalle
	 * @return SG_Collection de deux dates heures : périodes @Debut, @Fin
	 */
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
	/** 
	 * enlève la quantité fournie à la date et heure
	 * @since 2.3
	 * @version 2.6 DateTime
	 * @param SG_Nombre|SG_Formule $pQuantite quantité à soustraire
	 * @param SG_Texte|SG_Formule $pUnite code unité (seuls les 2 1ers caractères sont utilisés) h, he, ho, s, se, mn, mi, j, jo, d, da, m, mo, a, y, an, ye
	 * @return SG_DateHeure Un objet après calcul
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
					$q = 'T' . $qte . 'H';
					break;
				case 's':
				case 'se':
					$q = 'T' . $qte . 'S';
					break;
				case 'mn':
				case 'mi':
					$q = 'T' . $qte . 'M';
					break;
				case 'j':
				case 'jo';
				case 'd':
				case 'da':
					$q = $qte . 'D';
					break;
				case 'm':
				case 'mo':
					$q = $qte . 'M';
					break;
				case 'a':
				case 'y':
				case 'an':
				case 'ye':
					$q = $qte . 'Y';
					break;
				default:
					$q = '';
					break;
			}
			$dt -> _instant -> sub (new DateInterval('P' . $q));
		}
		return $dt;
	}

	/** 
	 * Détermine si deux @DateHeure sont sur la même journée (utile pour les agendas)
	 * @since 2.3
	 * @param $pDate : autre @Date ou @DateHeure à comparer. Si absent ou null, @Aujourdhui. Si @Texte, traduit en @Date
	 * @return @VraiFaux
	 */
	function EstMemeDate ($pDate = '') {
		if ($pDate === '') {
			$date = SG_Rien::Aujourdhui();
		} else {
			$date = new SG_Date($pDate);
		}
		if ($date -> _date -> format('Y-m-d') == $this -> Date() -> _date -> format('Y-m-d')) {
			$ret = new SG_VraiFaux(true);
		} else {
			$ret = new SG_VraiFaux(false);
		}
		return $ret;
	}

	/**
	 * Contrpole si une date-heure est située entre deux temps
	 * @Vrai si la date est dans l'intervalle (bornes incluses)
	 * @since 2.4
	 * @param (date ou dateheure) borne inférieure (défaut 0)
	 * @param (date ou dateheure) borne supérieure (défaut 0)
	 * @return @VraiFaux
	 */
	public function Entre($pInf = 0, $pSup = 0) {
		if($pInf === 0) {
			$inf = SG_Rien::Maintenant();
		} else {
			$inf = new SG_DateHeure($pInf);
		}
		if($pSup === 0) {
			$sup = SG_Rien::Maintenant();
		} else {
			$sup = new SG_DateHeure($pSup);
		}
		if (is_null($sup -> _instant) or is_null($inf -> _instant)) {
			$ret = new SG_Erreur('0215');
		} elseif ($inf > $sup) {
			$ret = new SG_Erreur('0217');
		} else {
			try {
				$ret = ($inf -> _instant <= $this -> _instant) and ($sup -> _instant >= $this -> _instant);
				$ret = new SG_VraiFaux($ret);
			} catch (Exception $e) {
				$ret = new SG_Erreur('0216');
			}
		}
		return $ret;
	}

	/**
	 * Format string pour tri et catégorisation (aaaa/mm)
	 * 
	 * @since 2.7
	 * @return string
	 **/
	function AnMois() {
		$ret = new SG_Texte('');
		if (is_null($this -> _instant)) {
			$ret = '';
		} else {
			$ret -> texte = $this -> _instant -> format('Y') . '/' . $this -> _instant -> format('m');
		}
		return $ret;
	}
	
	// 2.1.1. complément de classe créée par compilation
	use SG_DateHeure_trait;
}
?>
