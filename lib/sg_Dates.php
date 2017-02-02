<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* SG_Dates : Classe de traitement des dates multiples
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dates_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Dates_trait.php';
} else {
	trait SG_Dates_trait{};
}
class SG_Dates extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Dates';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	/**
	* Tableau des dates (@Date)
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
	**/
	function toString() {
		$tmp = array();
		foreach($this -> elements as $dt) {
			$tmp[] = $dt -> toString();
		}
		return implode(', ', $tmp);
	} 
	/**
	* Modifier correctement un champ avec un picker de dates multiples
	* @param (string) $pRefChamp référence html du champ
	**/
	function modifierChamp($pRefChamp = '') {
		$txt = $this -> toString();
		$idChamp = SG_Champ::idRandom();
		$ret = '<input id="' . $idChamp . '" class="champ_Dates hasDatePicker" type="text" name="' . $pRefChamp . '" value="' . str_replace('"', '&quot;', $txt) . '"/>';
		$ret.= '<script>SynerGaia.initChampDates("#' . $idChamp . '", "' . $txt . '")</script>';
		return $ret;
	}
	/**
	* Afficher la liste des dates
	* @return string code HTML
	**/
	function afficherChamp() {
		return '<span class="champ_Dates">' . $this -> toHTML() -> texte . '</span>';
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Dates_trait;
}
?>
