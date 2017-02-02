<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Operation : Classe de gestion d'une opération
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Operation_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Operation_trait.php';
} else {
	trait SG_Operation_trait{};
}
class SG_Operation extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Operation';
	public $typeSG = self::TYPESG;

	// Code de la base
	const CODEBASE = 'synergaia_operations';
	/**
	 * Code du statut "en attente"
	 */
	const STATUT_ENATTENTE = 'en attente';
	/**
	 * Code du statut "en cours"
	 */
	const STATUT_ENCOURS = 'en cours';
	/**
	 * Code du statut "suspendue"
	 */
	const STATUT_SUSPENDUE = 'suspendue';
	/**
	 * Code du statut "terminée"
	 */
	const STATUT_TERMINEE = 'terminée';
	/**
	 * Code du statut "annulée"
	 */
	const STATUT_ANNULEE = 'annulée';
	/**
	 * Référence unique de l'opération
	 */
	public $reference;
	/**
	 * Code du modèle d'opération
	 */
	public $modele = '';

	// SG_Formule : Formule principale en cours de traitement (origine)
	// Sert notamment de référence pour les variables. Cette propriété est sauvegardée dans le champ spécial _Save_Formule de l'opération
	public $formule = null;
	
	// string : texte de la formule SynerGaia (ne pas confondre avec $formule qui contient la SG_Formule active créée)
	public $phrase = '';

	// Blocs et étapes de l'opération
	public $blocs;

	// Code de la dernière étape traitée
	public $etape;
	
	// Script d'entête du formulaire
	public $script;

	// Bouts de formule (tableau) provenant des boutons, urls etc.
	public $boutons;
	
	// 1.1 ajout : Erreurs rencontrées dans le calcul
	public $erreurs = array();
	
	// 1.3.3 ajout : proportions des trois parties du corps
	public $proportions = [20, 60, 20];
	
	// 2.1 ajout : Texte du php (vient du modèle puis des méthodes appelées en direct)
	public $php = '';
	
	// 2.3 ajout code de l'application d'appartenance
	public $appli = '';
	
	/** 1.0.6 ; 1.3.4 new SG_Formule ; 2.1 php
	* Construction de l'objet
	* @param indéfini $pReference référence éventuelle de l'opération
	* @param array $pTableau tableau éventuel des propriétés du document CouchDB
	* @param boolean $pSave force l'enregistrement de l'opération en cas de nouvelle opératin
	**/
	function __construct($pReference = '', $pTableau = null, $pSave = true) {
		// Si pas de référence passée : fabrique une nouvelle opération
		if ($pReference === '') {
			$this -> reference = sha1(microtime(true) . mt_rand(10000, 90000));
			$this -> blocs = array();
			$this -> boutons = array();
			$this -> etape = '';
			$this -> initDocumentCouchDB(SG_Operation::CODEBASE . '/' . $this -> reference, $pTableau);
			$this -> setValeur('@Type', $this -> typeSG);
			$this -> setValeur('@Code', $this -> reference);
			$this -> appli = SG_Connexion::Application();
			if ($pSave) {
				$this -> Enregistrer();
			}
		} else {
			// Sinon recherche l'opération demandée
			$tmpReference = new SG_Texte($pReference);
			$this -> reference = $tmpReference -> texte;
			$this -> initDocumentCouchDB(self::CODEBASE . '/' . $this -> reference, $pTableau);
			// le champ formule doit être du type @Formule
			$formule = $this -> doc -> getValeur('SaveFormule');
			if ($formule !== null) {
				$this -> formule = new SG_Formule();
				$this -> formule -> proprietes = $formule;
			}
			$this -> php = $this -> doc -> getValeur('@PHP', '');
			$this -> fonction = $this -> doc -> getValeur('@Fonction', '');
			$this -> etape = $this -> getValeur('@Etape', '');
		}
		$this -> script = '';
	}
	/**
	* Création d'une opération à partir d'une formule
	* @param indéfini $pPhrase phrase SynerGaia
	* @return SG_Operation
	*/
	static function Creer($pPhrase = '', $save = true) {
		$tmpPhrase = new SG_Texte($pPhrase);
		$phrase = $tmpPhrase -> toString();

		// Fabrique une nouvelle opération
		$operation = new SG_Operation('', null, false);

		// Crée la formule de référence
		$operation -> formule = new SG_Formule($phrase, $operation, $operation);
		$operation -> phrase = $phrase;

		// Définit le demandeur et le responsable
		$identifiantUtilisateurEnCours = SG_SynerGaia::IdentifiantConnexion();
		$operation -> setValeur('@Demandeur', $identifiantUtilisateurEnCours);
		$operation -> setValeur('@Responsable', $identifiantUtilisateurEnCours);

		// Définit le statut
		$operation -> setValeur('@Statut', self::STATUT_ENATTENTE);

		if ($save) {	
			$operation -> Enregistrer();
		}
		return $operation;
	}
	/**
	* Création d'une opération à partir d'un modèle d'opération
	* @param indéfini $pCodeModeleOperation code du modèle d'opération
	* @return SG_Operation
	*/
	static function CreerDuModele($pModeleOperation = '', $save = true) {
		$nom = 'MO_' . SG_Texte::getTexte($pModeleOperation);
		$modeleOperation = SG_Navigation::getModeleOperation($pModeleOperation);
		$code = $modeleOperation -> getCodeDocument();
		$phrase = $modeleOperation -> getValeur('@Phrase', '');
		$titre = $modeleOperation -> getValeur('@Titre', $code);
		$theme = $modeleOperation -> getValeur('@Theme', '');
		$php = $modeleOperation -> getValeur("@PHP", '');
		if ($php === '') {
			$operation = SG_Operation::Creer($phrase, false);
		} else {
			if (!class_exists($nom)) {
				$operation = new SG_Erreur('0151',$nom);
				$modeleOperation -> setValeur('@PHP', '');
				$modeleOperation -> Enregistrer();
				$operation = new $nom();
			} else {
				$operation = new $nom();
			}
		}
		if (getTypeSG($operation) !== '@Erreur') {
			$operation -> modele = $code;
			$operation -> setValeur('@ModeleOperation', $code);
			$operation -> setValeur('@Theme', $theme);
			$operation -> setValeur('@Titre', $titre);
			$operation -> php = $modeleOperation -> getValeur("@PHP", '');
			if ($save) {
				$operation -> Enregistrer();
			}
		}
		return $operation;
	}
	/** 2.3 op person. : formule ; <br>
	* Calcule le titre de l'opération
	* @return : string
	**/
	function Titre() {
		$titre = '';
		$modele = $this -> getValeur('@ModeleOperation', '');
		if ($modele !== '') {
			$titre = $this -> getValeur('@Titre', '');
		} else {
			$titre = 'Opération personnalisée : <br>' . $this -> Formule() -> texte;
		}
		return $titre;
	}
	/**
	* Détermine le lien vers l'opération
	* @param string $pEtape code de l'étape demandée
	* @return string url de l'opération
	*/
	function url($pEtape = '') {
		$url = SG_Navigation::URL_PRINCIPALE . '?' . SG_Navigation::URL_VARIABLE_OPERATION . '=' . $this -> reference;
		if ($pEtape !== '') {
			$url .= '&' . SG_Navigation::URL_VARIABLE_ETAPE . '=' . $pEtape;
		}
		return $url;
	}
	/**
	* Lecture de l'UUID du document de l'opération
	* @return string UUID du document de l'opération
	*/
	public function getUUID() {
		return self::CODEBASE . '/' . $this -> reference;
	}
	/** 2.0 parm
	* Conversion en chaine de caractères
	* @return string UUID du document de l'opération
	*/
	public function toString($pDefaut = NULL) {
		$titre = $this -> getValeur('@Titre', '');
		if ($titre === '') {
			if (is_object($this -> formule)) {
				$titre = $this -> formule -> toString();
			}
		}
		return $titre;
	}
	/**
	* Fabrique le code HTML du lien vers l'opération
	* @return string code html du lien
	*/
	function Lien() {
		$ret = '';

		$modeleOperation = new SG_ModeleOperation($this -> getValeur('@ModeleOperation', ''));
		$titre = $this -> Titre();
		$icone = 'application_xp_terminal.png';
		if ($modeleOperation -> code !== '') {
			$titre = $modeleOperation -> getValeur('@Titre', $modeleOperation -> code);
			$icone = $modeleOperation -> getValeur('@IconeOperation', '');
		}
		$titre = new SG_Texte($titre);
		$titre = $titre -> toHTML();
		$url = SG_Navigation::getUrlBase() . $this -> url($this -> etape);

		$ret .= '<a href="' . $url . '" title="' . $titre . '" class="operation">';

		if ($icone !== '') {
			// TODO : icone du modèle d'opération : gérer le thème graphique
			$ret .= '<img src="' . SG_Navigation::getUrlBase() . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/' . $icone . '" alt="' . $titre . '" />';
		}

		$ret .= '<span>' . $titre . '</span>';
		$ret .= '</a>';

		return $ret;
	}
	/** 1.1 : Enregistrer
	* Mettre l'opération en attente
	* @return SG_Texte message de retour
	*/
	function MettreEnAttente() {
		$this -> setValeur('@Statut', SG_Operation::STATUT_ENATTENTE);
		$this -> Enregistrer();
		return new SG_Texte('Opération mise en attente.');
	}
	/** 1.1 : Enregistrer
	* Suspendre l'opération
	* @return SG_Texte message de retour
	*/
	function Suspendre() {
		$this -> setValeur('@Statut', SG_Operation::STATUT_SUSPENDUE);
		$this -> Enregistrer();
		return new SG_Texte('Opération suspendue.');
	}
	/** 1.1 : Enregistrer
	* Annuler l'opération
	* @return SG_Texte message de retour
	*/
	function Annuler() {
		$this -> setValeur('@Statut', SG_Operation::STATUT_ANNULEE);
		$this -> Enregistrer();
		return new SG_Texte('Opération annulée.');
	}
	/** 1.1 : ajout
	* Terminer l'opération
	* @return l'opération
	*/
	function Terminer() {
		$this -> setValeur('@Statut', SG_Operation::STATUT_TERMINEE);
		$this -> Enregistrer();
		return $this;
	}
	/** 1.1 ajout
	* Alerter un utilisateur à propos de l'opération
	* @param indéfini $pUtilisateur Utilisateur à alerter
	* @return SG_VraiFaux
	*/
	function Alerter($pUtilisateur = '') {
		$ret = new SG_VraiFaux(false);
		$utilisateur = '';
		if ($pUtilisateur !== '') {
			$utilisateur = new SG_Utilisateur($pUtilisateur);
		} else {
			$idRresponsable = $this -> getValeur('@Responsable', '');
			if ($idRresponsable !== '') {
				$utilisateur = new SG_Utilisateur($idRresponsable);
			}
		}
		if ($utilisateur !== '') {
			$objet = '[SynerGaia] Une operation requiert votre attention.';
			$titreOperation = $this -> getValeur('@Titre', '');
			$lienVersOperation = $this -> Lien();
			$message = 'L\'opération <b>' . $titreOperation . '</b> requiert votre attention.<br/>';
			$message .= $lienVersOperation;

			$alerte = new SG_Memo();
			$alerte -> AjouterDestinataire($utilisateur);
			$alerte -> DefinirObjet($objet);
			$alerte -> DefinirContenu($message);
			$ret = $alerte -> Envoyer();
		}
		return $ret;
	}

	/** 1.1 : Ajout (remplace DocumentPrincipal déprécié
	* Renvoie le document ou objet principal de l'opération
	*
	* @return indéfini document ou objet principal
	*/
	function Principal() {
		// a du être initialisé dans Navigation::setPrincipal() ou lancement de l'opération
		$id = $this->reference;
		if (isset($_SESSION['principal'][$id])) {
			$ret = $_SESSION['principal'][$id];
		} else {
			$doc = $this -> getValeur('@Principal', null);
			if($doc === null) {
				$_SESSION['principal'][$id] = new SG_Collection();
			} elseif (gettype($doc) === 'string') {
				// si seulement une référence, chercher le document dans la base
				$_SESSION['principal'][$id] = $_SESSION['@SynerGaia'] -> getObjet($doc);
			} else {
				$_SESSION['principal'][$id] = $this -> getValeurPropriete('@Principal', $this);
			}
			$ret = $_SESSION['principal'][$id];
		}
		return $ret;
	}

	/** 1.1 : deprecated (see Principal()) ; 2.2 abandon
	* @return indéfini document ou objet principal
	function DocumentPrincipal() {
		return $this -> Principal();
	}*/
	/** 1.1 icone
	 * Fabrique l'entete à disposer en haut de l'opération en cours d'exécution
	 *
	 * @return string entete (HTML) de l'opération
	 *
	 */
	function genererEntete() {
		$icone = '';
		$opeModele = $this -> getValeur('@ModeleOperation', '');
		if ($opeModele !== '') {
			$modele = new SG_ModeleOperation($opeModele);
			$icone = new SG_Icone($modele -> getValeur('@IconeOperation'));
			$icone -> categ = '64x64';
			if ($icone -> Existe() -> estVrai() === true) {
				$icone = $icone -> toHTMl();
			} else {
				$icone = '';
			}
		}
		$entete = $icone . $this -> Titre();
		if ($opeModele !== '') {
			$entete .= $this -> listeBoutons(false);
		}
		return $entete;
	}
	/** 1.1 'suspendre' retiré si anomnyme ; 1.3 suspendre et annuler enlevés
	*/
	function listeBoutons ($pListe = false) {
		$opeModele = $this -> getValeur('@ModeleOperation', '');
		if ($opeModele !== '') {
			$modele = new SG_ModeleOperation($opeModele);
			// Modifier du modele de l'opération (uniquement si profil administrateur)
			$lienModifModele = '';
			if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === true) {
				$urlModifModele = SG_Navigation::URL_PRINCIPALE;
				$urlModifModele .= '?' . SG_Navigation::URL_VARIABLE_FORMULE . '=.@Modifier';
				$urlModifModele .= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=' . $modele -> getUUID();
				$lienModifModele = ' <a href="' . $urlModifModele . '">modifier le modèle</a>';
			}
			$lienSuspendreOperation = '';
			$lienAnnulerOperation = '';
			if ($pListe === true) {
				$ret = '';
				if($lienModifModele !== '') {
					$ret .= '<li>' . $lienModifModele . '</li>';
				}
				$ret .= '<li>' . $lienSuspendreOperation . '</li><li>' . $lienAnnulerOperation .'</li>';
			} else {
				$ret = $lienModifModele . $lienSuspendreOperation . $lienAnnulerOperation;
			}
		} else {
			$ret= '';
		}
		return $ret;
	}
	/** 2.0 class bouton ; 2.1 traitement PHP compilé, ==='fic' ; 2.3 $_SESSION
	* 1.3.0 si résultat étape vide renvoie un contenu '' (pour tester boucles d'étape) ; 1.3.3 array de résultats
	* 1.1 : si étiquette vide, pas de bouton ; ajout parm $pObjet
	* 1.1 extraction et report dans index.php de la mise en formulaire
	* 1.1 suppression param $pDoc et $pFichier inutilisés
	* Traiter : traite une étape de l'opération
	* @param string $pEtape : code de la dernière étape traitée
	* @param string $pBouton : code du bouton à calculer
	* @param objet : si fourni, les formules porteront sur cet objet plutôt que le principal (cas des formules parallèle au traitement principal)
	* @param typeres : si fourni, le résultat n'est pas mis en forme pour l'affichage (résultat brut)
	* @return (array) : tableau des reésultats d'instruction + ['submit'] texte du bouton submit principal
	*/
	public function Traiter($pEtape = '', $pBouton = '', $pObjet = '', $pTyperes = '') {
		$ret = array();
		$_SESSION['saisie'] = false;
		$resultat = null;
		$etape = $pEtape;
		if ($pBouton !== '') {
			// boutons programmés de l'opération
			if(!isset($this -> boutons[$pBouton])) {
				$ret = new SG_Erreur('0050', $pBouton);
			} else {
				$bouton = $this -> boutons[$pBouton];
				if (getTypeSG($bouton) === '@Formule') {
					$bouton -> operation = $this;
					if (!is_object($pObjet)) {
						$ret[] = $bouton -> calculerSur($this -> Principal());
					} else {
						$ret[] = $bouton -> calculerSur($pObjet);
					}
				} elseif (is_string($bouton)) {
					$formule = new SG_Formule($bouton);
					$ret = $formule -> calculerSur($pObjet);
				} elseif (is_array($bouton) and $bouton[0] === 'fic') {
					if (isset($bouton[2])) {
						$ret[] = $this -> TelechargerFichier($bouton[1], $bouton[2]);
					} else {
						$ret = new SG_Erreur('0163', $bouton[0]);
					}
				} else {
					$ret = new SG_Erreur('0164', $bouton[0]);
				}
			}
			if(is_array($ret)) {
				if (isset($_SESSION['saisie']) and $_SESSION['saisie'] === true) {
					$ret['submit'] = SG_Libelle::getLibelle('0118',false);
				}
			}
		} else {
			try {
				$ret = $this -> traiterSpecifique($etape, $pTyperes);
				if (getTypeSG($ret) === '@Erreur') {
					$ret = array('erreurs' => $ret -> toHTML());
				} elseif (is_array($ret)) {
					$ret['erreurs'] = '';
				}
			} catch (Exception $e) {
				$ret = array();
				$this -> erreurs[] = $e -> getMessage() . ' (' . $e -> getFile() . ' ligne ' . $e -> getLine() . ')';
			}
		}
		$_SESSION['operations'][$this -> reference] = $this;
		return $ret;
	}
	//1.1 ajout
	public function Aide() {
		$ret = new SG_HTML('');
		$opeModele = $this -> getValeurPropriete('@ModeleOperation', '');
		if ($opeModele !== '') {
			$ret = $opeModele -> Aide();
		}
		return $ret;
	}
	/**
	* @return index.php?o=xxx & e=yyy
	*/
	function urlProchaineEtape () {
		$url = SG_Navigation::URL_PRINCIPALE . '?';
		$url .= SG_Navigation::URL_VARIABLE_OPERATION . '=' . SG_Navigation::OperationEnCours() -> reference;
		if (isset($_SESSION['page']['etape_prochaine'])) {
			$url .= '&' . SG_Navigation::URL_VARIABLE_ETAPE . '=' . $_SESSION['page']['etape_prochaine'];
		}
		return $url;
	}
	// 2.1 php
	function preEnregistrer() {	
		$this -> setValeur('@Phrase', $this -> phrase);
		$this -> setValeur('@Etape', $this -> etape);
		$this -> setValeur('@PHP', $this -> php);
		// la formule ne doit pas être interptétée mais stockée directement comme objet @Formule
		$this -> setValeur('SaveFormule', $this -> formule, true);
	}
	/** 1.3.3 ajout
	* indique si l'opération a été lancée il y a longtemps et n'a plus d'actvité
	* @param $pDelai (integer) délai de péremption en secondes (par défaut 3600 = 1h)
	***/
	function estInactive($pDelai = 3600) {
		$delai = $this -> getValeurPropriete('@DateModification');
		if(getTypeSG($delai) === '@Erreur') {
			return true;
		}
		$delai = $delai -> Intervalle(SG_Rien::Maintenant());
		$perime = abs($delai -> toInteger()) > $pDelai;
		return $perime;		
	}
	/** 2.1 ajout
	* Exécute la branche php correspondant à l'étape
	* @param (integer) : indice de la branche à exécuter
	* @return (string html) : résultat de l'opération
	**/
	function executerPHP($pNoBranche = 0, $pObjet = null) {
		$branche = $this -> branches[$pNoBranche];
		if($pObjet === null) {
			$objet = new SG_Rien();
		} else {
			$objet = $pObjet;
		}
		$ret = $branche -> Executer($this, $objet);
		return $ret;
	}
	/** 2.1 ajout
	* Teste si un objet est un SG_Operation ou une classe dérivée de SG_Operation
	* @param (object) $pOperation : l'objet à tester
	* @return (boolean) : réponse
	**/
	static function isOperation($pOperation) {
		if (is_object($pOperation)) {
			$ret = (get_class($pOperation) === 'SG_Operation' or is_subclass_of($pOperation, 'SG_Operation'));
		} else {
			$ret = ($pOperation === 'SG_Operation' or $pOperation === '@Operation' or is_subclass_of($pOperation, 'SG_Operation'));
		}
		return $ret;
	}
	/** 2.1 ajout ; 2.1.1 fonctionne dans les formules de texte riche ; 2.2 correct si refchamp uuid
	* Telecharge un fichier (surcharge la méthode de SG_Document pour l'appliquer sur le document principal ou sur le paramètre doc de $pRefChamp)
	**/
	function TelechargerFichier ($pRefChamp = null, $pFichier = null) {
		$refchamp = SG_Texte::getTexte($pRefChamp);
		$p = $this -> Principal();
		if(is_null($p) or getTypeSG($p) === '@Collection') {
			if($pRefChamp !== '' and ! is_null($pRefChamp)) {
				$refchamp = explode('/', $refchamp);
				if (is_array($refchamp)) {
					$p = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($refchamp[0] . '/' . $refchamp[1]);
					if(isset($refchamp[2])) {
						$refchamp = $refchamp[2];
					} else {
						$refchamp = '';
					}
				}
			}
		} elseif ($p -> DeriveDeDocument() -> estVrai()) {
			$refchamp = explode('/', $refchamp);
			if (is_array($refchamp) and isset($refchamp[2])) {
				$refchamp = $refchamp[2];
			}
		}
		if (method_exists($p, 'TelechargerFichier')) {
			$ret = $p -> TelechargerFichier($refchamp, $pFichier);
		} else {
			$ret = new SG_Erreur('0165', getTypeSG($p));
		}
		return $ret;
	}
	/** 2.1 ajout
	* retourne "@Operation" pour les operation PHP compilées
	*/ 
	function getTypeSG() {
		return '@Operation';
	}
	/** 2.1 ajout ; 2.3 err 0190
	* surchargée dans les opérations dérivées
	**/
	function traiterSpecifique($etape = '', $typeres='') {
tracer($this);
		return new SG_Erreur('0190');
	}
	/** 2.1.1 ajout
	* retourne un parametre de l'url de lancement ('p1' à 'p3')
	* @param n° de parametre
	**/
	function Parametre($pNo = "1") {
		$n = SG_Texte::getTexte($pNo);
		if (isset($this -> proprietes['$' . $n])) {
			$ret = $this -> proprietes['$' . $n];
		} else {
			$ret = '';
		}
		return new SG_Texte($ret);
	}
	/** 2.1.1 ajout pour compilateur
	* On regarde d'abord sur l'objet puis éventuellement dans propriété de l'opération en cours
	* @param (SG_Objet) $pObjet : celui dont on cherche une propriété ou une méthode
	* @param (string) $pNom : le nom de la propriété ou de la méthode
	* @return : la valeur ou @Erreur(166) ou @Erreur(175)
	**/
	function getProprieteOuMethode($pNom, $pNomMethode, $pObjet = null) {
journaliser($pNom);
journaliser(SG_Texte::getTexte($pObjet));
		 if (!is_object($pObjet)) {
			$ret = new SG_Erreur('0175',$pNom . SG_Texte::getTexte($pObjet));
		} elseif (isset($pObjet -> proprietes[$pNom])) {
			$ret = $pObjet -> getValeurPropriete($pNom, '');
		} elseif (isset($this -> proprietes[$pNom])) {
			$ret = $this -> proprietes[$pNom];
		} elseif (method_exists($pObjet, $pNomMethode)){
			$ret = $pObjet -> $pNomMethode();
		} else {
			$ret = $pObjet -> getValeurPropriete($pNom, '');
			//$ret = new SG_Erreur('0175',getTypeSG($pObjet) . $pNom);
		}
		return $ret;
	}
	/** 2.2 ajout
	* Affiche le texte de la formule prise en charge par l'opération
	* @return @Texte : la formule
	**/
	function Formule() {
		return new SG_Texte($this -> phrase);
	}
	/** 2.2 ajout
	* Calcule une suite de formules sur le même élément (sert dans le passage de paramètres)
	* @param $pElement élément sur lequel on exécute les calculs
	* @param tableau de $pFormules à exécuter
	* @return tableau des résultats
	**/
	static function calculerSur($pElement, $pFormules) {
		$ret = array();
		foreach($pFormules as $f) {
			if (getTypeSG($f) === '@Formule') {
				$ret[] = $f -> calculerSur($pElement);
			} else {
				$ret[] = $f;
			}
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Operation_trait;
}
?>
