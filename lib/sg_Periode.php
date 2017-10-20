<?php
/** fichier contenant les classes de gestion de @Periode */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');


if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Periode_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Periode_trait.php';
} else {
	/** trait vide par défaut (Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur) */
	trait SG_Periode_trait{};
}

/** SynerGaia
 * SG_Periode : Classe de traitement d'une période entre deux dates
 * @since 2.6
 * @version 2.6.0
 */
class SG_Periode extends SG_Objet {
	/** Type SynerGaia */
	const TYPESG = '@Periode';
	/** Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/**
	* SG_Date Date de début de la période
	*/
	public $debut;

	/**
	* SG_Date Date de fin de la période
	*/
	public $fin;

	/**
	* Construction de l'objet
	* @param indéfini $pQuelqueChose valeur à partir de laquelle créer la date
	*/
	public function __construct($pQuelqueChose = null) {
		if (!is_null($pQuelqueChose)) {
			if (func_num_args() === 2) {
				$this -> debut = new SG_Date($pQuelqueChose);
				$this -> fin = new SG_Date(func_get_arg(1));
			}elseif (is_array($pQuelqueChose)) {
				$this -> debut = new SG_Date(array_values($pQuelqueChose)[0]);
				$this -> fin = new SG_Date(array_values($pQuelqueChose)[1]);
			} elseif (getTypeSG($pQuelqueChose) === '@Collection') {
				$this -> debut = new SG_Date(array_values($pQuelqueChose -> elements)[0]);
				$this -> fin = new SG_Date(array_values($pQuelqueChose -> elements)[1]);
			} else {
				if (func_num_args() === 1) {
					$tmp = SG_Texte::getTexte($pQuelqueChose);
					if (strstr($tmp,',') !== false) {
						$tmp = explode(',', $tmp);
					} elseif (strstr($tmp,'-') !== false) {
						$tmp = explode('-', $tmp);
					} else {
						$tmp = explode(' ', $tmp);
					}
					$this -> debut = new SG_Date($tmp[0]);
					if (isset($tmp[1])) {
						$this -> fin = new SG_Date($tmp[1]);
					}
				}
			}
		}
	}

	/**
	* Mettre en chaine les deux dates séparées par un tiret
	* @since 2.6
	* @return string
	**/
	function toString() {
		$ret = '';
		if (is_object($this -> debut)) {
			$ret.= $this -> debut -> toString();
		}
		$ret.= '-';
		if (is_object($this -> fin)) {
			$ret.= $this -> fin -> toString();;
		}
		return $ret;
	}

	/**
	* Mettre en HTML les deux dates séparées par un tiret
	* @since 2.6
	* @return SG_HTML
	**/
	function toHTML() {
		$ret = new SG_HTML('<div class="sg-periode">' . $this -> toString() . '</div>');
		return $ret;
	}

	/**
	* Modifier correctement un champ avec un picker de dates multiples
	* @since 2.6
	* @param string $pRefChamp référence html du champ
	* @return string Le texte HTML correspondant
	* @uses SynerGaia.initChampDate()
	**/
	function modifierChamp($pRefChamp = '') {
		if (is_null($this -> debut)) {
			$deb = '';
		} else {
			$deb = $this -> debut -> toString();
		}
		if (is_null($this -> fin)) {
			$fin = '';
		} else {
			$fin = $this -> fin -> toString();
		}
		$idChamp = SG_SynerGaia::idRandom();
		$ret = '<div id="' . $idChamp . '" class="sg-periode" name="' . $pRefChamp . '">';
		$ret.= '<input id="' . $idChamp . '-deb" class="sg-periode-deb sg-date hasDatePicker" type="text" name="' . $pRefChamp . '[\'@Debut\']" value="' . $deb . '"/>';
		$ret.= ' - <input id="' . $idChamp . '-fin" class="sg-periode-fin sg-date hasDatePicker" type="text" name="' . $pRefChamp . '[\'@Fin\']" value="' . $fin . '"/>';
		$ret.= '</div>';
		$ret.= '<script>SynerGaia.initChampDate("#' . $idChamp . '-deb");SynerGaia.initChampDate("#' . $idChamp . '-fin");</script>';
		return $ret;
	}

	/**
	 * Afficher la liste des dates
	 * @since 2.6
	 * @return string code HTML
	 */
	function afficherChamp() {
		if ($this -> EstVide() -> estVrai()) {
			$ret = '';
		} else {
			$ret = '<span class="sg-periode">' . $this -> toString() . '</span>';
		}
		return $ret;
	}

	/**
	 * Affiche le champ en modification
	 * @since 2.6
	 * @return SG_HTML code pour la saisie d'une période
	 */
	function Modifier() {
		return new SG_HTML($this -> modifierChamp());
	}

	/**
	 * Calcule la durée en jours de la période (l'une des bornes est excluse).
	 * Retourne 0 si la période est incomplète
	 * @since 2.6
	 * @return SG_Nombre
	 */
	function Duree() {
		if (is_object($this -> debut) and is_object($this -> fin)) {
			$ret = $this -> fin -> DureeDepuis($this -> debut);
		} else {
			$ret = new SG_Nombre(0);
		}
		return $ret;
	}

	/**
	 * Met à jour ou retourne le début de la période.
	 * Retourne SG_Rien si la période n'a pas de début
	 * @since 2.6
	 * @return SG_Periode|SG_Nombre|SG_Rien
	 */
	function Debut() {
		if (func_num_args() === 1) {
			$this -> debut = new SG_Date(func_get_arg(0));
			$ret = $this;
		} else {
			if (is_object($this -> debut)) {
				$ret = $this -> debut;
			} else {
				$ret = new SG_Rien();
			}
		}
		return $ret;
	}

	/**
	 * Met à jour ou retourne la fin de la période.
	 * Retourne SG_Rien si la période n'a pas de fin
	 * @since 2.6
	 * @return SG_Periode|SG_Nombre|SG_Rien
	 */
	function Fin() {
		if (func_num_args() === 1) {
			$this -> fin = new SG_Date(func_get_arg(0));
			$ret = $this;
		} else {
			if (is_object($this -> fin)) {
				$ret = $this -> fin;
			} else {
				$ret = new SG_Rien();
			}
		}
		return $ret;
	}

	/**
	 * Teste si la liste des périodes est vide
	 * 
	 * @since 2.6
	 * @return SG_VraiFaux
	 */
	function EstVide() {
		$ret = true;
		if (is_object($this -> debut) and !($this -> debut -> EstVide() -> estVrai())) {
			$ret = false;
		} elseif (is_object($this -> fin) and !($this -> fin -> EstVide() -> estVrai())) {
			$ret = false;
		}
		return new SG_VraiFaux($ret);
	}

	/** 2.1.1. complément de classe créée par compilation */
	use SG_Periode_trait;
}
?>
