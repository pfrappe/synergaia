<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* SG_Annuaire : Classe SynerGaia de gestion d'un annuaire d'utilisateurs
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Annuaire_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Annuaire_trait.php';
} else {
	trait SG_Annuaire_trait{};
}
class SG_Annuaire extends SG_Base {
	/**
	 * Type SynerGaia
	 */
	const TYPESG = '@Annuaire';
	/**
	 * Type SynerGaia de l'objet
	 */
	public $typeSG = self::TYPESG;
	/**
	 * Code de la base
	 */
	const CODEBASE = 'synergaia_annuaire';
	
	/** 1.1 cache $_SESSION
	* getUtilisateur : cherche un utilisateur à partir de son identifiant
	* @param any $pUtilisateur code ou formule donnant un code d'opération
	* @return SG_Utilisateur trouvé ou false
	*/
	static function getUtilisateur ($pUtilisateur = '') {
		$ret = false;
		$typeSG = getTypeSG($pUtilisateur);
		if ($typeSG === '@Utilisateur') {
			$ret = $pUtilisateur;
		} else {
			$typeSG = new SG_Texte($pUtilisateur);
			$codeUtilisateur = $typeSG -> texte;
			if ($codeUtilisateur === '') {
				$ret = $_SESSION['@Moi'];
			} else {
				if (isset($_SESSION['users'][$codeUtilisateur])) {
					$ret = $_SESSION['users'][$codeUtilisateur];
				} else {
					$ret = new SG_Utilisateur('');
					$ret -> identifiant = $codeUtilisateur;
					$collec = $_SESSION['@SynerGaia']->getDocumentsFromTypeChamp('@Utilisateur', '@Identifiant', $codeUtilisateur);
					if(! $collec -> EstVide() -> estVrai()) {
						$ret -> doc = $collec -> Premier() -> doc;
					}
					$_SESSION['users'][$codeUtilisateur] = $ret;
				}
			}
		}
		if ($ret !== false) {
			if (! $ret -> Existe() -> estVrai()) {
				$ret = false;
			}
		}
		return $ret;
	}
	/** 1.0.7
	* Utilisateurs : @Collection des @Utilisateurs
	*/
	static function Utilisateurs() {
		return SG_Rien::Chercher('@Utilisateur');
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Annuaire_trait;
}
?>
