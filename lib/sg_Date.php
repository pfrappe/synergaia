<?php
/** sg_Date.php SynerGaia contient la classe SG_Date de traitement des dates */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Date_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Date_trait.php';
} else {
	/** trait vide par défaut */
	trait SG_Date_trait{};
}

/** SynerGaia SG_Date : Classe de traitement des dates
 * @version 2.6.0
 * @since 0.0
 */
class SG_Date extends SG_Objet {

	/** string Type SynerGaia */
	const TYPESG = '@Date';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/**
	 * Valeur interne de la date
	 * @version 2.6 c'est un DateTime
	 * @var DateTime
	 */
	public $_date;

	/** 
	 * Construction de l'objet
	 * @version 2.6 instanceof, replace '-'
	 * @version 2.7 correction strstr
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle créer la date
	 */
	public function __construct($pQuelqueChose = null) {
		if ($pQuelqueChose instanceof SG_Formule) {
			$prm = $pQuelqueChose -> calculer();
		} else {
			$prm = $pQuelqueChose;
		}
		if (!is_null($prm)) {
			if ($prm instanceof SG_Date) {
				$this -> _date = $prm -> _date;
			} elseif ($prm instanceof SG_DateHeure) {
				$this -> _date = $prm -> _instant;
				$this -> _date -> setTime(0,0);
			} else {
				if (is_object($prm) and $prm instanceof DateTime) {
					$this -> _date = $prm;
					$this -> _date -> setTime(0,0);
				} else {
					$d = SG_Texte::getTexte($prm);
					if ($d !== '') {
						$d = trim(str_replace('/', '-', $d));
						if (strstr($d, ' ') !== false) {
							$d = substr($d, 0, strpos($d, ' '));
						}
						try {
							$this -> _date = new DateTime($d);
						} catch (Exception $e){
							journaliser('Erreur de date :' . $d);
						}
					}
				}
			}
		}
	}

	/**
	 * Renvoie le timestamp unix de l'objet
	 * Si la date n'est pas initialisée, retourne -1
	 * @version 2.6 test si null
	 * @return integer
	 */
	public function getTimestamp() {
		if (is_null($this -> _date)) {
			$ret = -1;
		} else {
			$ret = $this -> _date -> getTimeStamp();
		}
		return $ret;
	}

	/** 1.1 : ajout
	 * Renvoie un objet php DateTime
	 * @since 1.1
	 * @return DateTime
	 */
	public function getDate() {
		return $this -> _date;
	}

	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString() {
		if (!is_null($this -> _date)) {
			$ret = $this -> _date -> format('d/m/Y');
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * Conversion valeur numérique
	 * @return float valeur numérique
	 */
	function toFloat() {
		return (double)$this -> getTimestamp();
	}

	/**
	 * Conversion valeur numérique
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
		return '<span class="sg-date">' . $this -> toHTML() -> texte . '</span>';
	}

	/**
	 * Crée un champ HTML pour la modification
	 * Un id Random estaffecté au champ et appel js 
	 * @param $pRefChamp référence du champ HTML
	 * @return string code HTML
	 * @uses SynerGaia.initChampDate() SynerGaia.stopPropagation()
	 */
	function modifierChamp($pRefChamp = '') {
		// Identifiant unique du tableau
		$idChamp = SG_SynerGaia::idRandom();
		$ret = '<input id="' . $idChamp . '" class="sg-date" type="text" name="' . $pRefChamp . '"';
		$ret.= ' value="' . str_replace('"', '&quot;', $this -> toString()) . '" ondblclick="SynerGaia.stopPropagation(event);"/>';
		$ret.= '<script>SynerGaia.initChampDate("#' . $idChamp . '")</script>';
		return $ret;
	}

	/**
	 * EstVide
	 * @since 1.1
	 * @return SG_VraiFaux
	 */
	public function EstVide() {
		$ret = new SG_VraiFaux($this -> _date === null);
		return $ret;
	}

	/**
	 * Annee
	 * @since 1.0.7
	 * @return SG_Nombre|SG_Rien
	 */
	public function Annee() {
		if (is_null($this -> _date)) {
			$ret = new SG_Rien();
		} else {
			$d = intval($this -> _date -> format('Y'));
			$ret = new SG_Nombre($d);
		}
		return $ret;
	}

	/**
	 * Mois
	 * @since 1.0.7
	 * @return SG_Nombre|SG_Rien
	 */
	public function Mois() {
		if (is_null($this -> _date)) {
			$ret = new SG_Rien();
		} else {
			$d = intval($this -> _date -> format('m'));
			$ret = new SG_Nombre($d);
		}
		return $ret;
	}

	/**
	 * Jour
	 * @since 1.0.7
	 * @return SG_Nombre|SG_Rien
	 */
	public function Jour() {
		if (is_null($this -> _date)) {
			$ret = new SG_Rien();
		} else {
			$d = intval($this -> _date -> format('d'));
			$ret = new SG_Nombre($d);
		}
		return $ret;
	}

	/**
	 * Indique si la date en cours est supérieure strictement à la date passée en paramètre
	 * @since 1.1
	 * @param indéfini $pQuelqueChose valeur à comparer
	 * @return SG_VraiFaux
	 */   
    function SuperieurA ($pQuelqueChose = null) {
		if ($pQuelqueChose === null) {
			$date = new SG_Date(today);
		} else {
			$date = new SG_Date($pQuelqueChose);
		}
		$ret = new SG_VraiFaux( $this -> _date > $date -> _date);
		return $ret;
	}

	/**
	 * cette date est-elle strictement inférieure à la date passée en paramètre
	 * @since 1.1
	 * @param indéfini $pQuelqueChose valeur à comparer
	 * @return SG_VraiFaux
	 */   
    function InferieurA ($pQuelqueChose = null) {
		if ($pQuelqueChose === null) {
			$date = new SG_Date(today);
		} else {
			$date = new SG_Date($pQuelqueChose);
		}
		$ret = new SG_VraiFaux( $this -> _date < $date -> _date);
		return $ret;
	}

	/**
	 * Ajouter ou retranche du temps à la date en cours (qui est modifiée)
	 * @since 1.1
	 * @version 2.6 alignement des pareamètres sur 
	 * @param SG_Nombre|SG_Formule|integer Quantité à ajouter
	 * @param SG_Texte|SG_Formule|string unté de la quantité
	 * @return SG_Date la date mise à jour
	 */
	public function Ajouter($pQuantite = 0, $pUnite = 'j') {
		$qte = new SG_Nombre($pQuantite);
		$qte = $qte -> valeur;
		$unite = substr(strtolower(SG_Texte::getTexte($pUnite)),0,2);
		$dt = new SG_Date();
		$dt -> _date = $this -> _date;
		if ($dt -> _date != null and $qte !== 0) {
			switch ($unite) {
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
			$dt -> _date -> add(new DateInterval('P' . $q));
		}
		return $dt;
	}

	/**
	 * Calcule l'âge actuel en année
	 * @since 1.1
	 * @version 2.6 parm
	 * @param SG_Date|SG_Formule $dt une date pour le décès
	 * @return SG_Nombre
	 */
	function Age() {
		if (func_num_args() >= 1) {
			$dt = new SG_Date(func_get_arg(0));
			$now = $dt -> _date;
		} else {
			$now = new DateTime();
		}
		$interval = $now -> diff($this -> getDate());
		return new SG_Nombre($interval -> y);
	}
	
	/**
	 * Indique si les deux dates sont identiques
	 * @since 1.1
	 * @param SG_Date $pAutreDate
	 * @return SG_VraiFaux
	 */
	function Egale($pAutreDate = '') {
		if ($pAutreDate === '') {
			$date = SG_Rien::Aujourdhui();
		} else {
			$date = new SG_Date($pAutreDate);
		}
		if (! is_null($date -> _date)) {
			if ($this -> _date === $date -> _date) {
				$ret = new SG_VraiFaux(true);
			} else {
				$ret = new SG_VraiFaux(false);
			}
		} else {
			$ret = new SG_VraiFaux(SG_VraiFaux::VRAIFAUX_INDEF);
		}
		return $ret;
	}

	/**
	 * Format string pour tri (aaaa/mm/jj)
	 * @since 1.3.4
	 * @return string
	 **/
	function AnMoisJour() {
		$ret = new SG_Texte('');
		if (is_null($this -> _date)) {
			$ret = '';
		} else {
			$ret -> texte = $this -> _date -> format('Y') . '/' . $this -> _date -> format('m') . '/' . $this -> _date -> format('d');
		}
		return $ret;
	}

	/**
	* calcule le nombre de jours écoulé avec la date passée en paramètre
	* @since 2.1.1
	* @version 2.7 correction $d->date, _date()
	* @param SG_Date $pDate : autre date pour l'intervalle
	* @return SG_Nombre
	**/
	function DureeDepuis($pDate = null) {
		$ret = 0;
		if ($pDate !== null) {
			$d = new SG_Date($pDate);
			if (! is_null($this -> _date) and ! is_null($d -> _date)) {
				$interval = $d -> _date -> diff($this -> _date);
				$ret = $interval -> days;
			}
		}
		return new SG_Nombre($ret);
	}

	/** 2.1.1 ajout
	 * Indique si la date est de l'année en cours ou non
	 * @since 2.1.1
	 * @return SG_VraiFaux
	 **/
	function CetteAnnee() {
		return new SG_VraiFaux($this -> Annee() -> toString() === SG_Rien::Aujourdhui() -> Annee() -> toString());
	}

	/**
	 * Permet la concaténation de texte directement
	 * @since 2.3
	 * @param texte à concatener
	 * @return SG_Texte
	 **/
	function Concatener() {
		$args = func_get_args();
		$ret = new SG_Texte($this -> toString());
		$ret = call_user_func_array(array($ret,'Concatener'), $args);
		return $ret;
	}

	/**
	 * enlève la quantité fournie à la date et heure
	 * @since 2.3
	 * @version 2.6 DateTime
	 * @param (@Nombre) : quantité à soustraire
	 * @param (@Texte) : code unité (seuls les 2 1ers caractères sont utilisés) j, jo, d, da, m, mo, a, y, an, ye
	 * @return SG_Date Une SG_Date après modification
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
					$u = 'D'; // nb secondes dans jour
					break;
				case 'm':
				case 'mo':
					$u = 'M'; // nb seconds dans 30 jours;
					break;
				case 'a':
				case 'y':
				case 'an':
				case 'ye':
					$u = 'Y'; // nb seconds dans 365 jours;
					break;
			}
			$dt -> _date -> sub(new DateInterval('P' . $qte . $u));
		}
		return $dt;
	}

	/**
	 * Retourne @Vrai si la date est dans l'intervalle (bornes incluses)
	 * @since 2.4
	 * @param (date ou dateheure) borne inférieure (défaut 0)
	 * @param (date ou dateheure) borne supérieure (défaut 0)
	 * @return @VraiFaux
	 */
	public function Entre($pInf = 0, $pSup = 0) {
		/** récupération des paramètres */
		if (getTypeSG($pInf) === '@Formule') {
			$inf = $pInf -> calculer();
		} else {
			$inf = $pInf;
		}
		if (getTypeSG($pSup) === '@Formule') {
			$sup = $pSup -> calculer();
		} else {
			$sup = $pSup;
		}
		/** calcul des limites */
		if (getTypeSG($inf) === '@Periode' and $sup === 0) {
			$sup = $inf -> Fin();
			$inf = $inf -> Debut();
		} else {
			if($inf === 0) {
				$inf = SG_Rien::Aujourdhui();
			} else {
				$inf = new SG_Date($inf);
			}
			if($sup === 0) {
				$sup = SG_Rien::Aujourdhui();
			} else {
				$sup = new SG_Date($sup);
			}
		}
		/** comparaison */
		if (is_null($sup -> _date) or is_null($inf -> _date)) {
			$ret = new SG_Erreur('0212',"0212");
		} elseif ($inf -> SuperieurA($sup) -> estVrai()) {
			$ret = new SG_Erreur('0214',"0214");
		} else {
			try {
				$ret = !($this -> _date < $inf -> _date or $this -> _date > $sup -> _date);
				$ret = new SG_VraiFaux($ret);
			} catch (Exception $e) {
				$ret = new SG_Erreur('0213',"0213");
			}
		}
		/** retour */
		return $ret;
	}

	/**
	 * Calcule le premier jour de la semaine contenant le jour (commence au lundi)
	 * @since 2.5
	 * @return SG_Date : le jour du début de semaine (éventuellement année précédente)
	 **/
	public function Lundi() {
		$ret = new SG_Date();
		$d = $this -> _date -> format('w');
		if ($d === '0') {
			$ret = $this -> Soustraire(6);
		} else {
			$ret = $this -> Soustraire(intval($d) - 1);
		}
		return $ret;
	}

	/**
	 * Format string pour tri (aaaa/mm)
	 * 
	 * @since 2.7
	 * @return string
	 **/
	function AnMois() {
		$ret = new SG_Texte('');
		if (is_null($this -> _date)) {
			$ret = '';
		} else {
			$ret -> texte = $this -> _date -> format('Y') . '/' . $this -> _date -> format('m');
		}
		return $ret;
	}

	/** 2.1.1. complément de classe créée par compilation */
	use SG_Date_trait;
}
?>
