<?php
/** SYNERGAIA fichier pour le traitement del'objet @Annuaire */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Annuaire_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Annuaire_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Annuaire_trait{};
}

/**
 * SG_Annuaire : Classe SynerGaia de gestion d'un annuaire d'utilisateurs
 * @since 0.0
 * @version 2.1.1
 * @version 2.6 ajout getAnonyme, modif getUtilisateur
 */
class SG_Annuaire extends SG_Base {
	/** string Type SynerGaia '@Annuaire' */
	const TYPESG = '@Annuaire';

	/** string Code de la base */
	const CODEBASE = 'synergaia_annuaire';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;
	
	/**
	 * Cherche dans l'annuaire un utilisateur à partir de son identifiant
	 * Si le paramètre passé est un objet SG_Utilisateur ou un SG_Erreur, on le retourne
	 * S'il est '', on retourne l'utilisateur en cours s'il y en a un sinon erreur 0252
	 * Si le paramètre donne une chaine de caractère, on cherche l'utilisateur ayant cet identifiant
	 * - d'abord dans la liste des identifiants déjà utilisés (sauf si force = true)
	 * - sinon dans l'annuaire
	 * Si doublon d'identifiant, on génère une erreur 0305
	 * 
	 * @since 0.0
	 * @version 1.1 cache $_SESSION
	 * @version 2.6 parm $pForce pour forcer la recherche de l'utilisateur dans l'annuaire (sans cache)
	 * @version test $collec SG_Erreur
	 * @param any $pUtilisateur code ou formule donnant un identifiant d'utilisateur
	 * @param boolean $pForce si true, force directement la recherche de l'utilisateur dans l'annuaire (false par défaut)
	 * @return SG_Utilisateur|SG_Erreur trouvé ou erreur ou false si inconnu
	 */
	static function getUtilisateur ($pUtilisateur = '', $pForce = false) {
		if ($pForce === true) {
			// on cherche directement un utilisateur unique dans l'annuaire
			$code = SG_Texte::getTexte($pUtilisateur);
			$collec = $_SESSION['@SynerGaia']->getDocumentsFromTypeChamp('@Utilisateur', '@Identifiant', $code);
			if ($collec instanceof SG_Erreur) {
				$ret = $collec;
			} elseif ($collec instanceof SG_Collection) {
				if (sizeof($collec -> elements) === 1) {
					$ret = $collec -> Premier();
				} elseif (sizeof($collec -> elements) === 0) {
					$ret = false;
				} else {
					$ret = new SG_Erreur('0306');
				}
			} else {
				$ret = new SG_Erreur('0306');
			}
		} else {
			$ret = false;
			if ($pUtilisateur instanceof SG_Utilisateur or $pUtilisateur instanceof SG_Erreur) {
				$ret = $pUtilisateur;
			} else {
				$code = SG_Texte::getTexte($pUtilisateur);
				if ($code === '') {
					if (! isset($_SESSION['@Moi'])) {
						$ret = new SG_Erreur('0252');
						journaliser($_SESSION,false);
					} else {
						$ret = $_SESSION['@Moi'];
					}
				} else {
					if (isset($_SESSION['users'][$code])) {
						$ret = $_SESSION['users'][$code];
					} else {
						$ret = new SG_Utilisateur('');
						$ret -> identifiant = $code;
						$collec = $_SESSION['@SynerGaia']->getDocumentsFromTypeChamp('@Utilisateur', '@Identifiant', $code);
						if ($collec instanceof SG_Erreur) {
							$ret = $collec;
						} elseif(sizeof($collec -> elements) === 0) {
							$ret = false; // inconnu
						} elseif (sizeof($collec -> elements) > 1) {
							$ret = new SG_Erreur('0305');
						} else {
							$ret = $collec -> Premier();
						}
						$_SESSION['users'][$code] = $ret;
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * SG_Collection des SG_Utilisateur
	 * Formule SG : '@Chercher("@Utilisateur")'
	 * 
	 * @since 1.0.7
	 * @return SG_Collection
	 */
	static function Utilisateurs() {
		return SG_Rien::Chercher('@Utilisateur');
	}

	/**
	 * recherche l'utilisateur anonyme sinon erreur
	 * 
	 * @since 2.6
	 * $return SG_Utilisateur|SG_Erreur
	 */
	static function getAnonyme() {
		$ret = self::getUtilisateur(SG_Connexion::ANONYME, true);
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Annuaire_trait;
}
?>
