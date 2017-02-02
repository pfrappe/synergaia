<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3 (see AUTHORS file)
* Classe SynerGaia de gestion des tables
*/
class SG_Table extends SG_Document {
	/**
	 * Type SynerGaia
	 */
	const TYPESG = '@Table';
	/**
	 * Type SynerGaia de l'objet
	 */
	public $typeSG = self::TYPESG;

	/**
	 * Code de la table
	 */
	public $code = '';
	/**
	 * Document de la table
	 */
	public $doc;
	
	/**
	 * Construction de l'objet
	 *
	 * @param string $pCode code de la table
	 */
	public function __construct($pCode = '') {
		if ($pCode !== '') {
			$tmpCode = new SG_Texte($pCode);
			$code = $tmpCode -> toString();
			// Si on a passé une référence complète (codebase/codedocument), nettoyer
			if (substr($code, 0, strlen(SG_Dictionnaire::CODEBASE) + 1) === (SG_Dictionnaire::CODEBASE . '/')) {
				$code = substr($code, strlen(SG_Dictionnaire::CODEBASE) + 1);
			}
			$this -> code = $code;
			$this -> doc = new SG_Document(SG_Dictionnaire::CODEBASE . '/' . $this -> code);
		}
	}

	public function MettreValeurs() {
		$args = func_get_args ();
		// simplifier les arguments
		if (sizeof($args) === 1) {
			if (getTypeSG($args[0]) === '@Formule') {
				$args = $args[0] -> calculer();
			} elseif (getTypeSG($args[0]) === '@Collection') {
				$args = $args[0] -> elements;
			}
		}
		$valeurs = array();
		foreach ($args as $arg) {
			$valeur = $arg;
			if (getTypeSG($arg) === '@Formule') {
				$valeur = $arg -> calculer();
			}
			if (gettype($valeur) === 'object') {
				$valeur = $valeur -> valeur;
			} else {
				$valeur = $valeru -> toString();
			}
			$valeurs[] = valeur;
		}
		$this -> setValeurPropriete('Valeurs', $valeurs);
		return $this;
	}
}
?>
