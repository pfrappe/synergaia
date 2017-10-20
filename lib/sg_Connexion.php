<?php
/** SynerGaia Contient la classe SynerGaïa SG_Connexion */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Connexion : Classe de gestion de la connexion d'un utilisateur
 * @version 2.6.0
 */
class SG_Connexion {
	/** Type SynerGaia */
	const TYPESG = '@Connexion';
	/** @var string $typeSG contient le Type SynerGaïa de l'objet ('@Connexion') */
	public $typeSG = self::TYPESG;

	/** @var string code de l'utilisateur 'anomnyme' */
	const ANONYME = 'anonyme';

	/** Construction de l'objet */
	function __construct() {
	}

	/**
	 * initApplication : test si changement d'application et initialise le préfixe du cache
	 * 1.3.1 retour ; 2.4 si ['page'] pas tableau
	 * @since 1.0.7
	 */
	static function initApplication() {
		$appli = SG_Connexion::Application();
		$ret = false;
		if (!is_array($_SESSION['page'])) {
			$_SESSION['page'] = array('application' => $appli);
		} else {
			if (!(isset($_SESSION['page']['application']))) {
				$_SESSION['page']['application'] = $appli;
				$ret = true;
			} elseif ($_SESSION['page']['application'] !== $appli) {
				SG_Connexion::ChangerApplication($appli);
				$ret = true;
			}
			SG_Cache::initPrefixeCacheAppli();
		}
		if (isset($_SESSION['@Moi'])) {
			if (is_object($_SESSION['@Moi'])) {
				if (! is_null($_SESSION['@Moi'] -> admin)) {
					$_SESSION['admin'] = $_SESSION['@Moi'] -> admin -> estVrai();
				}
			} else {
				unset($_SESSION['@Moi']); // mieux vaut se re-signer...
				$ret = false;
			}
		} else {
			unset($_SESSION['@Moi']); // mieux vaut se re-signer...
			$ret = false;
		}
		return $ret;
	}

	/** 
	 * Détermine si un utilisateur est connecté à l'application
	 * @since 1.0.7
	 * @param SG_Utilisateur $pUtilisateur utilisateur à tester
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

	/** 
	 * Demande la connexion d'un utilisateur
	 * et écrit la connexion dans la log du serveur
	 * @since 1.0.7
	 * @param string $pUtilisateur identifiant utilisateur
	 * @param string $pPassword mot de passe
	 * @param string $pJeton Jeton de connexion (voir fiche utilisateur)
	 * @return SG_VraiFaux connexion acceptée
	 */
	static function Connexion($pUtilisateur = '', $pPassword = '', $pJeton = '') {
		$precId = '';
		$okConnexion = false;
		if (getTypeSG($pUtilisateur) === '@Utilisateur') {
			if (isset($_SESSION['@Moi'])) {
				if ($_SESSION['@Moi'] -> identifiant !== $pUtilisateur -> identifiant) {
					/** déconnexion préalable si changement d'utilisateur */
					$precId = $_SESSION['@Moi'] -> identifiant;
					SG_Connexion::Deconnexion();
				}
			}
			/** Recherche d'un jeton de connexion */
			if ($pJeton !== '') {
				$jeton = $pJeton;
			} else {
				$jeton = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_JETON);
			}
			if ($jeton !== '') {
				/** Vérification du jeton pour l'utilisateur */
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
		/** log connexion */
		if ($okConnexion !== false) {
			SG_Connexion::Deconnexion();
			if ($precId !== '') {
				$precId = ' (out : ' . $precId . ')';
			}
			if ($pUtilisateur -> identifiant === 'anonyme') {
				$texte = self::Application() . ' : connexion ' . $pUtilisateur -> identifiant;
			} else {
				$texte = self::Application() . ' : connexion de ' . $pUtilisateur -> identifiant . ' par ' . $okConnexion . $precId;
			}
			$GLOBALS['SG_LOG'] -> log($texte, SG_LOG::LOG_NIVEAU_INFO);
			$_SESSION['@Moi'] = $pUtilisateur;
			SG_Cache::initPrefixeCacheUser();
			$r = $pUtilisateur -> EstAdministrateur();
			$okConnexion = true;
		}
		$ret = new SG_VraiFaux($okConnexion);
		return $ret;
	}

	/**
	 * Utilisateur : Identification de l'utilisateur connecté à partir de différents paramètres
	 * d'abord paramètre de la fonction, puis contexte de session
	 * @since 1.0.7
	 * @version 2.6 getAnonyme
	 * @param SG_Texte|SG_Formule|string pIdentifiant code utilisateur
	 * @return SG_Utilisateur|SG_Erreur si inconnu, ou null si vide
	 */
	static function Utilisateur($pIdentifiant = '') {
		$ret = null;
		if (is_string($pIdentifiant)) {
			$ret = SG_SynerGaia::IdentifiantConnexion(); // utilisateur actuel
			if ($ret !== '' and ($ret === $pIdentifiant or $pIdentifiant === '')) {
				$ret = $_SESSION['@Moi'];
			} else {
				if ($pIdentifiant !== '') {
					$ret = SG_Annuaire::getUtilisateur($pIdentifiant, true); //new SG_Utilisateur($pIdentifiant);
				} else {
					$ret = SG_Annuaire::getAnonyme();
					if ($ret instanceof SG_Erreur) {
						$ret = new SG_Erreur('0084', $ret -> getMessage());
					}
				}
			}
		}
		return $ret;
	}

	/** 
	 * Demande la déconnexion de l'utilisateur en cours
	 * @since 1.0.8
	 */
	static function Deconnexion() {
		unset($_SESSION['@Moi']);
		unset($_SESSION['users']);
		SG_Cache::viderCacheNavigation();
		SG_Cache::viderCacheSession();
	}

	/** 
	 * Traite un changement d'application
	 * 
	 * @since 1.0.8
	 * @version 2.6 déconnexion seulement si plus moi
	 * @param string $pApplication code de l'application
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
		SG_Cache::viderCacheNavigation();
		$_SESSION['page']['application'] = $pApplication;
		$_SESSION['page']['themes'] = '';
		$_SESSION['page']['theme'] = '';
		$_SESSION['page']['menu'] = '';
		if ($encoreMoi) {
			$_SESSION['@Moi'] = $utilisateur;
		} else {
			self::Deconnexion();
			header('Location: ' . SG_Navigation::URL_LOGIN . '?redir=' . $_SERVER["REQUEST_URI"]);
			die();
		}
	}

	/**
	 * retourne le code de l'application
	 * @since 1.3.0
	 * @return string
	 */
	static function Application() {
		$url = explode('/', $_SERVER["REQUEST_URI"]);
		return $url[1];
	}

	/**
	 * ok pour connexion anonyme ? (oui si exite un tel code utilisateur)
	 * @since 1.3.2
	 * @return boolean
	 **/
	static function AnonymePossible() {
		$utilisateur = new SG_Utilisateur(SG_Connexion::ANONYME);
		return $utilisateur -> Existe() -> estVrai();
	}
}
?>
