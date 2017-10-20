<?php
/** fichier contenant les classes de gestion de @Profil
 * @version 2.6 ajout du trait
 */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Profil_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Profil_trait.php';
} else {
	/** 
	 * Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * par défaut trait associé vide 
	 * @since 2.6
	 */
	trait SG_Profil_trait{};
}

/**
 * SG_Profil : Classe de gestion d'un profil d'utilisateur 
 * @version 2.1
 */
class SG_Profil extends SG_Document {
	/** string Type SynerGaia '@Profil' */
	const TYPESG = '@Profil';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/** string Code du profil */
	public $code = '';

	/** SG_DocumentCouchDB Document du profil */
	public $doc;

	/**
	 * Construction de l'objet
	 * @since 1.0.7
	 * @param indéfini $pCodeProfil code du profil
	 * @param array|SG_DocumentCouchDB $pTableau source éventuelle des informations 
	 */
	function __construct($pCodeProfil = '', $pTableau = null) {
		$tmpCode = new SG_Texte($pCodeProfil);
		$base = SG_Dictionnaire::getCodeBase($this -> typeSG);
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code');
		$this -> setValeur('@Type', '@Profil');
	}

	/**
	 * Ajoute un utilisateur au profil
	 * @since 1.0.6
	 * @param SG_Utilisateur|SG_Texte $pUtilisateur
	 * @return SG_Profil $this
	 */
	public function AjouterUtilisateur($pUtilisateur) {
		$utilisateur = new SG_Utilisateur($pUtilisateur);
		$utilisateurUUID = $utilisateur -> getUUID();

		$utilisateurs = $this -> getValeur('@Utilisateurs', '');
		if (getTypeSG($utilisateurs) === 'string') {
			if ($utilisateurs === '') {
				$utilisateurs = array();
			} else {
				$utilisateurs = array($utilisateurs);
			}
		}

		if (!in_array($utilisateurUUID, $utilisateurs)) {
			$utilisateurs[] = $utilisateurUUID;
		}

		$this -> setValeur('@Utilisateurs', $utilisateurs);
		$this -> Enregistrer();

		return $this;
	}

	/**
	 * Supprime un utilisateur du profil
	 * @since 1.0.6
	 * @param indéfini $pUtilisateur
	 * @return
	 */
	public function SupprimerUtilisateur($pUtilisateur) {
		$utilisateur = new SG_Utilisateur($pUtilisateur);
		$utilisateurUUID = $utilisateur -> getUUID();

		$utilisateurs = $this -> getValeur('@Utilisateurs', '');
		if (getTypeSG($utilisateurs) === 'string') {
			if ($utilisateurs === '') {
				$utilisateurs = array();
			} else {
				$utilisateurs = array($utilisateurs);
			}
		}
		$utilisateurs_new = array();
		if (in_array($utilisateurUUID, $utilisateurs)) {
			$nbUtilisateurs = sizeof($utilisateurs);
			for ($i = 0; $i < $nbUtilisateurs; $i++) {
				if ($utilisateurs[$i] !== $utilisateurUUID) {
					$utilisateurs_new[] = $utilisateurs[$i];
				}
			}
		}
		$this -> setValeur('@Utilisateurs', $utilisateurs_new);
		$this -> Enregistrer();
		return $this;
	}

	/**
	 * Après enregistrement, rafraichir les caches et recalculer les thèmes de l'utilisateur
	 * @since 1.1 ajout
	 * @version 2.0 return, SG_Cache
	 * @formula : @Synergaia.@ViderCache("n")
	 * @return SG_VraiFaux
	 */
	function postEnregistrer() {
		// remettre à jour les menus
		$ret = SG_Cache::viderCache('n');
		SG_Navigation::composerThemesDefaut(SG_Rien::ThemeEnCours());
		$_SESSION['refresh']['themes'] = $_SESSION['page']['themes'];
		return $ret;
	}

	/**
	 * Affichage d'un profil
	 * @since 2.1. ajout
	 * @formula : .@Afficher("Titre : ".@Concatener(.@Titre),"Code : ".@Concatener(.@Code),"Modèles d'opérations : ".@Concatener(.@ModelesOperations),"Utilisateurs : ".@Concatener(.@Utilisateurs))
	 */
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Afficher('@Titre','@Code','@ModelesOperations','@Utilisateurs');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}

	/**
	 * Modification d'un profil
	 * @since 2.1 ajout
	 * @formula : .@Modifier(.@Titre,.@Code,.@ModelesOperations,.@Utilisateurs)
	 */
	function Modifier() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Modifier('@Titre','@Code','@ModelesOperations','@Utilisateurs');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}

	// 2.6 complément de classe créée par compilation
	use SG_Profil_trait;
}
?>
