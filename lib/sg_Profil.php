<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1 (see AUTHORS file)
 * SG_Profil : Classe de gestion d'un profil d'utilisateur
 */
class SG_Profil extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Profil';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Code du profil
	public $code = '';
	// Document du profil
	public $doc;

	/** 1.0.7
	* __construct : Construction de l'objet
	*
	* @param indéfini $pCodeProfil code du profil
	* @param array ou @DocumentCouchDB $pTableau source éventuelle des informations 
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
	/** 1.0.6
	* Ajoute un utilisateur au profil
	*
	* @param indéfini $pUtilisateur
	*
	* @return
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

	/** 1.0.6
	* Supprime un utilisateur du profil
	*
	* @param indéfini $pUtilisateur
	*
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
	/** 1.1 ajout ; 2.0 return, SG_Cache
	* @formula : @Synergaia.@ViderCache("n")
	*/
	function postEnregistrer() {
		// remettre à jour les menus
		$ret = SG_Cache::viderCache('n');
		SG_Navigation::composerThemesDefaut(SG_Rien::ThemeEnCours());
		$_SESSION['refresh']['themes'] = $_SESSION['page']['themes'];
		return $ret;
	}
	/** 2.1. ajout
	* @formula : .@Afficher("Titre : ".@Concatener(.@Titre),"Code : ".@Concatener(.@Code),"Modèles d'opérations : ".@Concatener(.@ModelesOperations),"Utilisateurs : ".@Concatener(.@Utilisateurs))
	**/
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Afficher('@Titre','@Code','@ModelesOperations','@Utilisateurs');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}
	/** 2.1 ajout
	* @formula : .@Modifier(.@Titre,.@Code,.@ModelesOperations,.@Utilisateurs)
	**/
	function Modifier() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Modifier('@Titre','@Code','@ModelesOperations','@Utilisateurs');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}
}
?>
