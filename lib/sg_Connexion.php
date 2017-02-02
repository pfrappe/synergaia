<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.3 (see AUTHORS file)
 * SG_Connexion : Classe de gestion de la connexion d'un utilisateur
 */
class SG_Connexion {
	// Type SynerGaia
	const TYPESG = '@Connexion';
	public $typeSG = self::TYPESG;

	// Utlisateur 'anomnyme'
	const ANONYME = 'anonyme';

	// Construction de l'objet
	function __construct() {
	}

	/** 1.0.7 ; 1.3.1 retour
	* initApplication : test si changement d'application et initialise le préfixe du cache
	*/
	static function initApplication() {
		$appli = SG_Connexion::Application();
		$ret = false;
		if (!(isset($_SESSION['page']['application']))) {
			$_SESSION['page']['application'] = $appli;
			$ret = true;
		} elseif ($_SESSION['page']['application'] !== $appli) {
			SG_Connexion::ChangerApplication($appli);
			$ret = true;
		}
		SG_Cache::initPrefixeCacheAppli();
		return $ret;
	}
	/** 1.0.7
	 * Determine si quelqu'un est connecté à l'application
	 *
	 * @return SG_VraiFaux
	 */
	static function EstConnecte($pUtilisateur = '') {
		$tmpEstConnecte = false;
        if ($pUtilisateur === '') {
			$utilisateur = SG_Connexion::Utilisateur();
		} else {
			$utilisateur = $pUtilisateur;
		}
		if (getTypeSG($utilisateur) === '@Utilisateur') {
			if (isset($_SESSION['@Moi']) AND $_SESSION['@Moi'] -> identifiant === $utilisateur -> identifiant) {
				$tmpEstConnecte = true;
			}
		}
        return new SG_VraiFaux($tmpEstConnecte);
    }

    /** 1.0.7 ; 1.3.3 log appli ; 2.1.1 msg log anonyme
     * Demande la connexion d'un utilisateur
     *
     * @param string $pUsername identifiant utilisateur
     * @param string $pPassword mot de passe
     * @param string $pJeton Jeton de connexion (voir fiche utilisateur)
     *
     * @return SG_VraiFaux connexion acceptée
     */
    static function Connexion($pUtilisateur = '', $pPassword = '', $pJeton = '') {
		$precId = '';
        $okConnexion = false;
        if (getTypeSG($pUtilisateur) === '@Utilisateur') {
			if (isset($_SESSION['@Moi'])) {
				if ($_SESSION['@Moi'] -> identifiant !== $pUtilisateur -> identifiant) {
					// déconnexion préalable si changement d'utilisateur
					$precId = $_SESSION['@Moi'] -> identifiant;
					SG_Connexion::Deconnexion();
				}
			}
			// Recherche d'un jeton de connexion
			if ($pJeton !== '') {
				$jeton = $pJeton;
			} else {
				$jeton = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_JETON);
			}
			if ($jeton !== '') {
				// Vérification du jeton pour l'utilisateur
				if ($pUtilisateur -> VerifierJeton($jeton) === true) {
					$okConnexion = 'jeton';
				}
			} 
			if ($okConnexion === false) {
				if ($pUtilisateur -> VerifierMotDePasse($pPassword) -> estVrai()) {
					$okConnexion = 'mot de passe';
				}
			}
		}
		// log connexion
        if ($okConnexion !== false) {
			SG_Connexion::Deconnexion();
			if ($precId !== '') {
				$precId = ' (out : ' . $precId . ')';
			}
			if ($pUtilisateur -> identifiant === 'anonyme') {
				journaliser(self::Application() . ' : connexion ' . $pUtilisateur -> identifiant, false);
			} else {
				journaliser(self::Application() . ' : connexion de ' . $pUtilisateur -> identifiant . ' par ' . $okConnexion . $precId, false);
			}
			$_SESSION['@Moi'] = $pUtilisateur;
			SG_Cache::initPrefixeCacheUser();
			$r = $pUtilisateur -> EstAdministrateur();
			$okConnexion = true;
		}
        $ret = new SG_VraiFaux($okConnexion);
        return $ret;
    }
    
    /** 1.0.7 ; 1.3.2 erreur 84 ; ok si ''.
     * Utilisateur : Identification de l'utilisateur connecté à partir de différents paramètres
     * d'abord paramètre de la fonction, puis contexte de session
     * 
     * @param code utilisateur
     * 
     * @return @Utilisateur, ou @Erreur si inconnu, ou null si vide
     *
     **/
	static function Utilisateur($pIdentifiant = '') {
		$utilisateur = null;
		if (getTypeSG($pIdentifiant) === 'string') {
			$utilisateur = SG_SynerGaia::IdentifiantConnexion();
			if ($utilisateur !== '' and ($utilisateur === $pIdentifiant or $pIdentifiant === '')) {
				$utilisateur = $_SESSION['@Moi'];
			} else {
				if ($pIdentifiant != '') {
					$utilisateur = new SG_Utilisateur($pIdentifiant);
				} else {
					$utilisateur = new SG_Utilisateur(SG_Connexion::ANONYME);
					if ( ! $utilisateur -> Existe() -> estVrai()) {
						$utilisateur = new SG_Erreur('0084');
					}
				}
			}
		}
        return $utilisateur;
    }

    /** 1.0.8
     * Demande la déconnexion de l'utilisateur en cours
     */
    static function Deconnexion() {
        unset($_SESSION['@Moi']);
		unset($_SESSION['users']);
		SG_Cache::viderCacheNavigation();
        SG_Cache::viderCacheSession();
    }
    /** 1.1 test si même identifiant et même psw
    */
    static function ChangerApplication($pApplication = '') {
		$encoreMoi = false;
		if (isset($_SESSION['@Moi'])) {
			if ($_SESSION['@Moi'] -> identifiant !== '') {
				$utilisateur = new SG_Utilisateur($_SESSION['@Moi'] -> identifiant);
			} else {
				$utilisateur = new SG_Utilisateur(SG_Connexion::ANONYME);
			}
			if ($utilisateur -> Existe() -> estVrai()) {
				if ($utilisateur -> getValeur('@MotDePasse') === $_SESSION['@Moi'] -> getValeur('@MotDePasse')) {
					$encoreMoi = true;
				}
			}
		}
		self::Deconnexion();
		$_SESSION['page']['application'] = $pApplication;
		$_SESSION['page']['themes'] = '';
		$_SESSION['page']['theme'] = '';
		$_SESSION['page']['menu'] = '';
		if ($encoreMoi) {
			$_SESSION['@Moi'] = $utilisateur;
		} else {
			header('Location: ' . SG_Navigation::URL_LOGIN . '?redir=' . $_SERVER["REQUEST_URI"]);
			die();
		}
	}
	
	static function Application() {
		$url = explode('/', $_SERVER["REQUEST_URI"]);
		return $url[1];
	}
	/** 1.3.2 ajout
	* ok pour connexion anonyme ? (oui si exite un tel code utilisateur)
	**/
	static function AnonymePossible() {
		$utilisateur = new SG_Utilisateur(SG_Connexion::ANONYME);
		return $utilisateur -> Existe() -> estVrai();
	}
}
?>
