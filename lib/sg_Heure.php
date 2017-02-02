<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de gestion d'une heure
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Heure_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Heure_trait.php';
} else {
	trait SG_Heure_trait{};
}
class SG_Heure extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Heure';
	public $typeSG = self::TYPESG;

	// Valeur interne de l'heure
	public $_heure;

	/**
	 * Construction de l'objet
	 *
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle créer l'heure
	 */
	public function __construct($pQuelqueChose = null) {

		if (!is_null($pQuelqueChose)) {
			$tmpTypeSG = getTypeSG($pQuelqueChose);

			switch ($tmpTypeSG) {
				case 'integer' :
				case 'double' :
					$this -> _heure = $pQuelqueChose;
					break;
				case 'string' :
					// Lit l'heure passée
					$elements = explode(':', $pQuelqueChose . ':00:00');
					if (sizeof($elements) >= 3) {
						if (is_numeric($elements[0])) {
							$heures = $elements[0];
							$minutes = $elements[1];
							$secondes = $elements[2];
							if (is_numeric($heures)) {
								if (is_numeric($minutes)) {
									if (is_numeric($secondes)) {
										$this -> _heure = mktime($heures, $minutes, $secondes, 0, 0, 0);
									}
								}
							}
						}
					}
					break;
				case self::TYPESG :
					$this -> _heure = $pQuelqueChose -> _heure;
					break;
				default :
					if (substr($tmpTypeSG, 0, 1) === "@") {
						// Si objet SynerGaia
						if ($tmpTypeSG === '@Formule') {
							$tmp = $pQuelqueChose -> calculer();
							$tmpHeure = new SG_Heure($tmp);
							$this -> _heure = $tmpHeure -> _heure;
						} else {
							$tmpHeure = new SG_Heure($pQuelqueChose -> toString());
							$this -> _heure = $tmpHeure -> _heure;
						}
					} else {
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
		return $this -> _heure;
	}

	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString() {
		if (!is_null($this -> _heure)) {
			$ret = date('H:i:s', $this -> _heure);
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

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_Heure">' . $this -> toHTML() . '</span>';
	}

	/**
	 * Modification
	 *
	 * @param $pRefChamp référence du champ HTML
	 *
	 * @return string code HTML
	 */
	function modifierChamp($pRefChamp = '') {
		return '<input class="champ_Heure" type="text" name="' . $pRefChamp . '" value="' . str_replace('"', '&quot;', $this -> toString()) . '"/>';
	}
	/** 1.1
	* EstVide
	*/
	public function EstVide() {
		$ret = new SG_VraiFaux($this -> _heure === null);
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Heure_trait;
}
?>
