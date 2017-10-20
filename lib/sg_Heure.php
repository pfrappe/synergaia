<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Heure */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Heure_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Heure_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.1.1
	 */
	trait SG_Heure_trait{};
}

/**
 * Classe SynerGaia de gestion d'une heure
 * @version 2.1.1
 */
class SG_Heure extends SG_Objet {
	/** string Type SynerGaia '@Heure' */
	const TYPESG = '@Heure';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** integer Valeur interne de l'heure (unix) */
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
	 * Calcul du code html pour l'affichage d'un champ @Heure
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_Heure">' . $this -> toHTML() . '</span>';
	}

	/**
	 * Calcul du code html pour la modification d'un champ @Heure
	 *
	 * @param string $pRefChamp référence du champ HTML
	 *
	 * @return string code HTML
	 */
	function modifierChamp($pRefChamp = '') {
		return '<input class="champ_Heure" type="text" name="' . $pRefChamp . '" value="' . str_replace('"', '&quot;', $this -> toString()) . '"/>';
	}

	/**
	 * Calcul si le champ est vide ou non
	 * @since 1.1
	 * @return SG_VraiFaux
	 */
	public function EstVide() {
		$ret = new SG_VraiFaux($this -> _heure === null);
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Heure_trait;
}
?>
