<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Utilisateur : Classe de gestion d'un utilisateur
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Utilisateur_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Utilisateur_trait.php';
} else {
	trait SG_Utilisateur_trait{};
}

class SG_Utilisateur extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Utilisateur';
	
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;
	
	// Identifiant de l'utilisateur
	public $identifiant = '';
	
	//1.1 ajout
	// est administrateur (@VraiFaux)
	public $admin;
	
	// 2.3 ajout
	// code application si gestion multiple
	public $appli = '';

	/** 1.1 getUtilisateur pour le cache
	* Construction de l'objet
	*
	* @param indéfini $pQuelqueChose indentifiant de l'utilisateur
	* @param string $pTableau tableau éventuel des propriétés du document CouchDB
	* @param boolean $pCreerSiInexistant créer l'utilisateur si non trouvé
	*/
	public function __construct($pQuelqueChose = '', $pTableau = null, $pCreerSiInexistant = false) {
		$codeBase = SG_Dictionnaire::getCodeBase($this -> typeSG);
		if($pTableau !== null) {
			// contruire l'objet à partir du tableau de propriétés			
			$tmpCode = new SG_Texte($pQuelqueChose);
			$tmpCode = $tmpCode -> toString();
			$i =  strpos($tmpCode, '/');
			if ($i !== false) {
				$tmpCode = substr($tmpCode, $i + 1);
			}
			$this -> identifiant = $tmpCode;
			$this -> initDocumentCouchDB($codeBase . '/' . $this -> identifiant, $pTableau);
		} else if (!is_null($pQuelqueChose)) {
			// Si j'ai un identifiant fourni => cherche dans l'annuaire
			$tmpTypeSG = getTypeSG($pQuelqueChose);

			switch ($tmpTypeSG) {
				case 'string' :
					// Si on a passé une référence complète (codebase/codedocument), nettoyer
					if (substr($pQuelqueChose, 0, strlen($codeBase) + 1) === ($codeBase . '/')) {
						$pQuelqueChose = substr($pQuelqueChose, strlen($codeBase) + 1);
					}
					// ne pas tester !== 
					if ($pQuelqueChose != '') {
						$this -> identifiant = $pQuelqueChose;
					}
					break;
				case '@Utilisateur' :
					$identifiant = $pQuelqueChose -> identifiant;
					$this -> identifiant = $identifiant;
					break;
				default :
					if (substr($tmpTypeSG, 0, 1) === '@') {
						// Si objet SynerGaia
						if ($tmpTypeSG === '@Formule') {
							$tmp = $pQuelqueChose -> calculer();
							$tmpUtilisateur = new SG_Utilisateur($tmp);
							$this -> identifiant = $tmpUtilisateur -> identifiant;
						} else {
							$tmpUtilisateur = new SG_Utilisateur($pQuelqueChose -> toString());
							$this -> identifiant = $tmpUtilisateur -> identifiant;
						}
					} else {
					}
			}

			if ($this -> identifiant !== '') {
				$tmpUser = SG_Annuaire::getUtilisateur($this -> identifiant);
				if ($tmpUser !== false) {
					$this -> doc = $tmpUser -> doc;
					$this -> appli = $tmpUser -> getValeur('@Application',''); // 2.3
				} else {
					// Si je dois créer l'utilisateur
					if ($pCreerSiInexistant === true) {
						$this -> initDocumentCouchDB($codeBase . '/' . $this -> identifiant);
						$this -> setValeur('@Type', $this -> typeSG);
						$this -> setValeur('@Identifiant', $this -> identifiant);
						$this -> Enregistrer();
					}
				}
			}
		}
		// si nouveau, ajouter un documentCouchDB vide
		if (!isset($this->doc)) {
			$this -> initDocumentCouchDB();
			$this -> setValeur('@Type', '@Utilisateur');
			$this -> doc -> setBase(SG_Dictionnaire::getCodeBase('@Utilisateur'));
		}
	}

	/** 2.0 parm
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString($pDefaut = NULL) {
		return $this -> toHTML();
	}

	/** 1.0.6 ; 2.0 parm
	 * Conversion en code HTML
	 *
	 * @return string code HTML
	 */
	function toHTML($pDefaut = NULL) {
		$ret = '';
		if (!is_null($this -> doc)) {
			$prenom = $this -> getValeur('Prenom', '');
			$nom = $this -> getValeur('Nom', '');
			if ($prenom !== '') {
				$ret .= $prenom;
			}
			if ($nom != '') {
				if ($prenom !== '') {
					$ret .= ' ';
				}
				$ret .= $nom;
			}
		}
		if ($ret === '') {
			$ret = $this -> identifiant;
		}
		return $ret;
	}

	/**
	 * Calcule l'identité mail de l'utilisateur selon RFC 2822
	 *
	 * @return string identité mail de l'utilisateur
	 */
	function getIdentiteMail() {
		$ret = '';
		$nom = $this -> getValeur('Nom', '');
		$prenom = $this -> getValeur('Prenom', '');
		$mail = $this -> getValeur('Email', '');
		if ($prenom !== '') {
			$ret .= $prenom . ' ';
		}
		if ($nom !== '') {
			$ret .= $nom . ' ';
		}
		if ($mail !== '') {
			if (($nom . $prenom) !== '') {
				$ret .= '<' . $mail . '>';
			} else {
				$ret .= $mail;
			}
		}
		return $ret;
	}

	/**
	 * Détermine la liste des profils de l'utilisateur
	 *
	 * @return SG_Collection collection des profils de l'utilisateur
	 */
	function Profils($pForce = true) {
		$codeCache = 'Profils(' . $this-> identifiant . ')';
		if (SG_Cache::estEnCache($codeCache) && $pForce === false) {
			$listeProfils = SG_Cache::valeurEnCache($codeCache);
		} else {
			$listeProfils = new SG_Collection();
			$listeTousLesProfils = SG_Rien::Chercher('@Profil');
			$nbProfils = $listeTousLesProfils -> Compter() -> toInteger();
			$utilisateurUUID = $this -> getUUID();
			for ($i = 0; $i < $nbProfils; $i++) {
				$profil = $listeTousLesProfils -> elements[$i];
				$utilisateurs = $profil -> getValeur('@Utilisateurs');
				if (gettype($utilisateurs) !== 'array') {
					$utilisateurs = array($utilisateurs);
				}
				if (in_array($utilisateurUUID, $utilisateurs) === true) {
					$listeProfils -> Ajouter($profil);
				}
			}
			SG_Cache::mettreEnCache($codeCache, $listeProfils);
		}
		return $listeProfils;
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		$lien = $this-> LienVers($this -> toHTML(), 'DocumentConsulter');
		return '<span class="champ_Utilisateur">' . $lien -> toString() . '</span>';
	}

	/** 1.3.1 param 2 ; 2.1 php7
	* Modification d'un champ @Utlisateur
	* @param $pRefChamp référence du champ HTML
	* @param $pListeUtilisateurs (@Collection ou @Formule) liste des valeurs possibles (sinon tous)
	* @return string code HTML
	*/
	function modifierChamp($codeChampHTML = '', $pListeElements = null) {
		$ret = '<select class="champ_Utilisateur" type="text" name="' . $codeChampHTML . '">';

		// Propose le choix par défaut (vide)
		$ret .= '<option value="">(aucun)</option>';

		// Calcule la liste des utilisateurs
		if (is_null($pListeElements)) {
			$listeUtilisateurs = SG_Rien::Chercher('@Utilisateur');
		} else {
			if (getTypeSG($pListeElements) === '@Formule') {
				$listeUtilisateurs = $pListeElements -> calculer();
			} else {
				$listeUtilisateurs = $pListeElements;
			}
			if (getTypeSG($pListeElements) !== '@Collection') {
				$listeUtilisateurs = new SG_Collection();
			}
		}
		$nbUtilisateurs = $listeUtilisateurs -> Compter() -> toInteger();
		for ($i = 0; $i < $nbUtilisateurs; $i++) {
			$utilisateur = $listeUtilisateurs -> elements[$i];
			$selected = '';

			if ($utilisateur -> identifiant === $this -> identifiant) {
				$selected = ' selected="selected"';
			}
			$ret .= '<option value="' . $utilisateur -> identifiant . '"' . $selected . '>' . $utilisateur -> toHTML() . '</option>';
		}

		$ret .= '</select>';

		return $ret;
	}

	/**
	* Compare à un autre utilisateur
	*
	* @param quelquechose $pUtilisateur autre utilisateur
	*
	* @return SG_VraiFaux identiques ?
	*/
	public function Egale($pUtilisateur= '') {
		$tmpUtilisateur = new SG_Utilisateur($pUtilisateur);
		$autre_utilisateur = $tmpUtilisateur -> getUUID();

		$tmpBool = false;
		if ($autre_utilisateur === $this -> getUUID()) {
			$tmpBool = true;
		}

		$ret = new SG_VraiFaux($tmpBool);
		return $ret;
	}

	/** 1.3.4 return ; 2.1 'D' majuscule, pas enregistrer
	* Définition du mot de passe pour l'utilisateur
	*
	* @param string $pMotDePasse nouveau mot de passe
	*/
	public function DefinirMotDePasse($pMotDePasse = '') {
		$hash = SG_MotDePasse::chiffrerMotDePasse(SG_Texte::getTexte($pMotDePasse));
		$this -> setValeur('@MotDePasse', $hash);
		return $this;
	}

	/** 1.3.2 traite si tout vide
	* Test du mot de passe pour l'utilisateur
	*
	* @param string $pMotDePasse mot de passe proposé
	*
	* @return SG_VraiFaux mot de passe accepté
	*/
	public function VerifierMotDePasse($pMotDePasse = '') {
		$reference = $this -> getValeur('@MotDePasse', '');
		if ($reference === '' and $pMotDePasse === '') {
			$ret = new SG_VraiFaux(true);
		} else {
			$tmpMotDePasse = new SG_MotDePasse($reference);
			$ret = $tmpMotDePasse -> VerifierMotDePasse($pMotDePasse);
		}
		return $ret;
	}

	/** 1.0.2 ; 2.1 MOP ; 2.3 foreach
	* Liste des modèles d'opérations disponibles pour l'utilisateur
	*
	* @return SG_Collection liste de modèles d'opérations
	*/
	public function ModelesOperations() {
		$identifiantCompletUtilisateur = $this -> getUUID();
		$codeCache = 'MOP(' . $this -> identifiant . ')';
		if (SG_Cache::estEnCache($codeCache)) {
			$ret = SG_Cache::valeurEnCache($codeCache);
		} else {
			$jsSelection  = "function(doc) {if ((doc['@Type']==='@Profil')&&(doc['@Utilisateurs'].indexOf('" . $identifiantCompletUtilisateur . "')!==-1)){";
			$jsSelection .= "for (var i in doc['@ModelesOperations']){emit(null,doc['@ModelesOperations'][i]);}}}";
			$vue = new SG_Vue('', SG_Dictionnaire::CODEBASE, $jsSelection, true);
			$collectionCodesModelesOperations = $vue -> ChercherValeurs() -> Unique();
			$mesModelesOperations = new SG_Collection();
			foreach ($collectionCodesModelesOperations -> elements as $codeModeleOperation) {
				$modeleOperation = new SG_ModeleOperation($codeModeleOperation);
				$mesModelesOperations -> Ajouter($modeleOperation);
			}
			$ret = $mesModelesOperations;
			SG_Cache::mettreEnCache($codeCache, $mesModelesOperations);
		}
		return $ret;
	}

	/** 1.1 initialisé dans -> admin
	 * Determine si l'utilisateur est administrateur SynerGaïa
	 *
	 * @return SG_VraiFaux administrateur
	 * @formula : .Profils.@Contient(@Profil("ProfilAdministrateur"))
	 */
	public function EstAdministrateur() {
		if (! isset($this -> admin)) {
			$profilAdmin = new SG_Profil('ProfilAdministrateur');
			$this -> admin = $this -> Profils() -> Contient($profilAdmin);
		}
		return $this -> admin;
	}

	/** 1.0.7
	 * Renvoie le jeton de l'utilisateur, si l'utilisateur en cours est celui demandé (ou s'il est admin)
	 *
	 * @return SG_Texte jeton utilisateur
	 */
	public function Jeton() {
		$ret = new SG_Texte('');
		if (isset($_SESSION['@Moi'])) {
			if (($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) or ($this -> identifiant === $_SESSION['@Moi'] -> identifiant)) {
				$ret = new SG_Texte(sha1($this -> identifiant . $this -> getValeur('@MotDePasse', '')));
			}
		}
		return $ret;
	}
	/** 1.0	 * 
	 * VerifierJeton : retourne True ou False selon que le jeton en paramèetre est celui de l'utiliisateur connecté
	 */
	public function VerifierJeton($pJeton = '') {
		$ret = false;
		if ($pJeton !== '') {
			$j = sha1($this -> identifiant . $this -> getValeur('@MotDePasse', ''));
			$ret = ($pJeton === $j);
		}
		return $ret;
	}
	/** 1.0.6
	 * EstAnonyme : @Vrai ou Faux selon que l'utilisateur est 'anonyme' ou non
	 * @return @VraiFaux 
	 */
	 function EstAnonyme() {
		 $ret = new SG_VraiFaux(false);
		 if (SG_Rien::Moi() -> identifiant === 'anonyme') {
			 $ret = new SG_VraiFaux(true);
		 }
		 return $ret;
	 }
	 /** 1.1
	 * corrige le code document comme identifiant si nécessaire (en principe uniquement à la création !)
	 */
	 function preEnregistrer() {
		 if (isset($this -> doc)) {
			 if($this-> getValeur('_id', '') === '') {
				 $this->setValeur('_id', $this -> getValeur('@Identifiant'));
			 }
			 if($this-> getValeur('_rev', '') === '') {
				 // nouveau : préparer les identifiants
				 $this->setValeur('_id', $this -> getValeur('@Identifiant'));
				 $this->identifiant = $this -> getValeur('@Identifiant');
				 $this -> doc -> revision = '1-0123465789';
				 $this-> doc -> proprietes['_rev'] = $this -> doc -> revision;
			 }
			 if ($this -> doc -> codeDocument !== $this -> identifiant and $this -> identifiant !== '') {
				 $this -> doc -> codeDocument = $this -> identifiant;
			 }
		 }
		 unset($_SESSION['users'][$this -> identifiant]);
		 return true;
	 }
	/** 2.1. ajout
	* @formula : .@Afficher(.@Identifiant,.Nom,.Prenom,.Email,.@Jeton)
	**/
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Afficher('@Identifiant','Nom','Prenom','Email','@Jeton');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}
	/** 2.1 ajout
	* @formula : .@Modifier(.@Identifiant,.Nom,.Prenom,.Email,.@Raccourcis,.@MotDePasse)
	**/
	function Modifier() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Modifier('@Identifiant','Nom','Prenom','Email','@Raccourcis','@MotDePasse');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}
	/** 2.1 ajout
	* Recrée un clone de l'utilisateur avec le nouvel identifiant et supprime l'ancien
	**/
	function ChangerIdentifiant($pNouveau = '') {
		$ret = new SG_Erreur('Changement d\'identifiant non fait');
		$nouveau = SG_Texte::getTexte($pNouveau);
		if ($nouveau === '') {
			$nouveau = $this -> getValeur('@Identifiant');
		}
		if ($nouveau !== $this -> doc -> codeDocument) {
			$ret = new SG_Utilisateur($nouveau, $this -> doc -> proprietes);
			$ok = $ret -> Enregistrer();
			if ($ok == true) {
				$this -> Supprimer();
			}
		}
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Utilisateur_trait;
}
?>
