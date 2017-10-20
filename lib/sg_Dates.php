<?php
/** SYNERGAIA fichier pour le traitement de liste de dates */
 defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');


if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dates_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dates_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur 
	 * @since 2.1.1
	 */
	trait SG_Dates_trait{};
}

/**
 * SG_Dates : Classe de traitement de liste de dates
 * @version 2.6.0
 */
class SG_Dates extends SG_Objet {
	/** string Type SynerGaia '@Dates' */
	const TYPESG = '@Dates';
	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/**
	* array Tableau des dates SG_Date
	*/
	public $elements = array();

	/**
	* Construction de l'objet
	*
	* @param indéfini $pQuelqueChose valeur à partir de laquelle créer la date
	*/
	public function __construct($pQuelqueChose = null) {
		if (!is_null($pQuelqueChose)) {
			if (is_array($pQuelqueChose)) {
				$this -> elements = $pQuelqueChose;
			} elseif (getTypeSG($pQuelqueChose) === '@Collection') {
				$this -> elements = $pQuelqueChose -> elements;
			} else {
				$tmp = SG_Texte::getTexte($pQuelqueChose);
				$tmp = explode(',', $tmp);
				foreach($tmp as $dt) {
					$this -> elements[] = new SG_Date($dt);
				}
			}
		}
	}

	/**
	 * met la liste en chaine de caratcères
	 * return string
	 */
	function toString() {
		$tmp = array();
		foreach($this -> elements as $dt) {
			$tmp[] = $dt -> toString();
		}
		return implode(', ', $tmp);
	}

	/**
	 * Calcule le code html pour modifier correctement un champ avec un picker de dates multiples
	 * @param string $pRefChamp référence html du champ
	 * @return string code html
	 * @uses SynerGaia.initChampDates()
	 */
	function modifierChamp($pRefChamp = '') {
		$txt = $this -> toString();
		$idChamp = SG_SynerGaia::idRandom();
		$ret = '<input id="' . $idChamp . '" class="champ_Dates hasDatePicker" type="text" name="' . $pRefChamp . '" value="' . str_replace('"', '&quot;', $txt) . '"/>';
		$ret.= '<script>SynerGaia.initChampDates("#' . $idChamp . '", "' . $txt . '")</script>';
		return $ret;
	}

	/**
	 * Calcule le code html pour afficher la liste des dates comme un champ
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_Dates">' . $this -> toHTML() -> texte . '</span>';
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Dates_trait;
}
?>
