<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.2 (see AUTHORS file)
* Classe SynerGaia de gestion d'un mot de passe
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_MotDePasse_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_MotDePasse_trait.php';
} else {
	trait SG_MotDePasse_trait{};
}
class SG_MotDePasse extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@MotDePasse';
	/**
	 * Salt pour le chiffrement de mot de passe
	 * /!\ ne pas modifier après installation !
	 */
	const SALT = '6g5s.eds8g9r!sd';
	/**
	 * Type SynerGaia de l'objet
	 */
	public $typeSG = self::TYPESG;

	/**
	 * Mot de passe en clair
	 */
	public $mdpTexte = '';
	/**
	 * Hash du mot de passe
	 */
	public $mdpHash = '';

	/**
	 * Construction de l'objet
	 *
	 * @param indéfini $pQuelqueChose hash du mot de passe
	 */
	function __construct($pQuelqueChose = null) {
		$tmpTexte = new SG_Texte($pQuelqueChose);
		$this -> mdpHash = $tmpTexte -> toString();
	}

	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString() {
		return $this -> mdpHash;
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
	 * Renvoie le hash du mot de passe
	 *
	 * @return string hash
	 */
	function getHash() {
		return $this -> mdpHash;
	}

	/**
	 * Définition du mot de passe en clair
	 *
	 * @param indéfini $pQuelqueChose mot de passe
	 */
	public function setMotDePasse($pQuelqueChose = '') {
		$tmpTexte = new SG_Texte($pQuelqueChose);
		$this -> mdpTexte = $tmpTexte -> toString();
		$this -> mdpHash = SG_MotDePasse::chiffrerMotDePasse($this -> mdpTexte);

		return $this -> mdpHash;
	}

	/**
	 * Chriffrement du mot de passe
	 *
	 * @param indéfini $pQuelqueChose mot de passe
	 * @param indéfini $pSalt grain de sel
	 *
	 * @return string hash du mot de passe
	 */
	static function chiffrerMotDePasse($pQuelqueChose = '', $pSalt = '') {
		$tmpTexte = new SG_Texte($pQuelqueChose);
		$password = $tmpTexte -> toString();
		$tmpTexte = new SG_Texte($pSalt);
		$salt = $tmpTexte -> toString();

		$hash = '';
		if ($password !== '') {
			$hash = md5(SG_MotDePasse::SALT . $salt . $password);
		}
		return $hash;
	}

	/**
	 * Vérification du mot de passe
	 *
	 * @param indéfini $pQuelqueChose mot de passe proposé
	 * @param indéfini $pSalt grain de sel
	 *
	 * @return SG_VraiFaux mot de passe accepté
	 */
	public function VerifierMotDePasse($pQuelqueChose = '', $pSalt = '') {
		$retBool = false;
		$tmpTexte = new SG_Texte($pQuelqueChose);
		$password = $tmpTexte -> toString();

		// Interdit les mots de passe vides
		if ($password !== '') {
			$hash = SG_MotDePasse::chiffrerMotDePasse($password, $pSalt);
			$reference = $this -> mdpHash;

			if ($reference === $hash) {
				$retBool = true;
			}
		}
		return new SG_VraiFaux($retBool);
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_MotDePasse">' . $this -> toHTML() . '</span>';
	}

	/**
	 * Modification
	 *
	 * @param $pRefChamp référence du champ HTML
	 *
	 * @return string code HTML
	 */
	function modifierChamp($pRefChamp = '') {
		$ret = '<input class="champ_MotDePasse" type="password" name="' . $pRefChamp . '" value="" autocomplete="off" />';
		$ret .= '(laisser vide pour ne pas le modifier)';
		return $ret;
	}
	/** 2.2 ajout
	* Crypte et met à jour la valeur du champ d'un document si non vide (le document n'est pas enregistré)
	* @param SG_Document : document à mettre à jour
	* @param string : nom du champ à mettre à jour
	* @param string : valeur du champ
	* @return : le document mis à jour
	**/
	static function setChamp($pDocument, $pChamp, $pValeur) {
		// Si le mot de passe n'est pas vide
		if ($pValeur !== '') {
			// Calcule le hash à stocker dans la base
			$valeur = SG_MotDePasse::chiffrerMotDePasse($pValeur);
			$pDocument -> setValeur($pChamp, $valeur);
		}
		return $pDocument;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_MotDePasse_trait;
}
?>
