<?php
/** fichier contenant le php nécessaire pour la gestion d'une opération */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Operation_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Operation_trait.php';
} else {
	/** 
	 * Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * par défaut trait associé vide 
	 * @since 2.1.1
	 **/
	trait SG_Operation_trait{};
}

/**
 * SG_Operation : Classe de gestion d'une opération
 * @since 0.0
 * @version 2.5
 */
class SG_Operation extends SG_Document {
	/** string Type SynerGaia '@Operation' */
	const TYPESG = '@Operation';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Code de la base */
	const CODEBASE = 'synergaia_operations';

	/** string Code du statut "en attente" */
	const STATUT_ENATTENTE = 'en attente';

	/** string Code du statut "en cours" */
	const STATUT_ENCOURS = 'en cours';

	/** string Code du statut "suspendue" */
	const STATUT_SUSPENDUE = 'suspendue';

	/** string Code du statut "terminée" */
	const STATUT_TERMINEE = 'terminée';

	/** string Code du statut "annulée" */
	const STATUT_ANNULEE = 'annulée';

	/** string Référence unique de l'opération */
	public $reference;

	/** string Code du modèle d'opération */
	public $modele = '';

	/** SG_Formule : Formule principale en cours de traitement (origine)
	 * Sert notamment de référence pour les variables. Cette propriété est sauvegardée dans le champ spécial _Save_Formule de l'opération
	 **/
	public $formule;
	
	/** string texte de la formule SynerGaia (ne pas confondre avec $formule qui contient la SG_Formule active créée) */
	public $phrase;

	/** string Code de la dernière étape traitée */
	public $etape;
	
	/** string Script d'entête du formulaire */
	public $script;

	/** array Array des formules provenant des boutons, urls etc. indexée par le sha1 des textes de formule */
	public $boutons;
	
	/** string bouton en cours (pour les diaporama par exemple)
	 * @since 2.4 ajout */
	public $boutonencours;
	
	/** array de SG_Formules : Contrôles à effectuer avant enregistrement des documents modifiés */
	public $controles;
	
	/** array Erreurs rencontrées dans le calcul
	 * @since 1.1 ajout */
	public $erreurs = array();
	
	/** array proportions des trois parties du corps
	 * @since 1.3.3 ajout */
	public $proportions = [20, 60, 20];
	
	/** string Texte du php (vient du modèle puis des méthodes appelées en direct)
	 * @since 2.1 ajout */
	public $php = '';
	
	/** string code de l'application d'appartenance
	 * @since 2.3 */
	public $appli = '';
	
	/** string étape suivante par défaut de l'opération ; prochain principal à utiliser (par défaut : null)
	 * @since 2.5 */
	public $prochaineEtape = '';
	/** string prochain principal à utiliser (par défaut : null)
	 * @since 2.5 */
	public $prochainPrincipal = null;
	/** array codes de traduction des code d'étape manuels en code interne
	 * @since 2.6 */
	public $codesetapes = array();
	/** timestamp date heure de démarrage de l'opération. Mis à jour par SG_Pilote::declarerOperationActive()
	 * @since 2.7 */
	public $start;
	/** timestamp date.heure de dernière utilisation. Mis à jour par SG_Pilote::declarerOperationActive()
	 * @since 2.7 */
	public $lastuse;

	/**
	* Construction de l'objet
	* @since 0.0
	* @version 2.4 $pSave = false ; pas de SaveFormule ; supp blocs
	* @param indéfini $pQuelqueChose référence éventuelle de l'opération
	* @param array $pTableau tableau éventuel des propriétés du document CouchDB
	* @param boolean $pSave force l'enregistrement de l'opération en cas de nouvelle opératin
	**/
	function __construct($pQuelqueChose = '', $pTableau = null, $pSave = false) {
		// 2.4 Si pas de référence passée : fabrique une nouvelle opération
		if ($pQuelqueChose === '') {
			if (is_array($pTableau) and isset($pTableau['@Code'])) {
				$this -> reference = $pTableau['@Code'];
			} else {
				$this -> reference = sha1(microtime(true) . mt_rand(10000, 90000));
			}
			$this -> etape = '';
			$this -> initDocumentCouchDB(SG_Operation::CODEBASE . '/' . $this -> reference, $pTableau);
			$this -> boutons = $this -> getValeur('@Boutons', array());
			$this -> setValeur('@Type', $this -> typeSG);
			$this -> setValeur('@Code', $this -> reference);
			$this -> appli = SG_Connexion::Application();
			if ($pSave) {
				$this -> Enregistrer();
			}
		} else {
			// Sinon recherche l'opération demandée
			$this -> reference = SG_Texte::getTexte($pQuelqueChose);
			$this -> initDocumentCouchDB(self::CODEBASE . '/' . $this -> reference, $pTableau);
			// le champ formule doit être du type @Formule
			$this -> php = $this -> doc -> getValeur('@PHP', '');
			$this -> fonction = $this -> doc -> getValeur('@Fonction', '');
			$this -> etape = $this -> getValeur('@Etape', '');
		}
		// au moment de la construction, le principal est mis à null. Il sera rempli ailleurs
		$this -> proprietes['@Principal'] = null;
		$this -> script = '';
	}

	/**
	 * Création d'une opération à partir d'une formule
	 * @version 2.4 $pSave = false
	 * @param string|SG_Texte|SG_Formule $pPhrase phrase SynerGaia
	 * @param boolean $pSave indique s'il faut forcer la sauvegarde de l'opération créée
	 * @return SG_Operation
	 */
	static function Creer($pPhrase = '', $pSave = true) {
		$phrase = SG_Texte::getTexte($pPhrase);

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

		if ($pSave) {
			$operation -> Enregistrer();
		}
		return $operation;
	}

	/**
	 * Création d'une opération à partir d'un modèle d'opération
	 * @version 2.4 $pSave false ; si titre '', code
	 * @param indéfini $pModeleOperation code du modèle d'opération
	 * @param boolean $pSave indique s'il faut forcer la sauvegarde de l'opération créée
	 * @return SG_Operation
	 */
	static function CreerDuModele($pModeleOperation = '', $pSave = false) {
		$nom = 'MO_' . SG_Texte::getTexte($pModeleOperation);
		$modeleOperation = SG_Navigation::getModeleOperation($pModeleOperation);
		$code = $modeleOperation -> getCodeDocument();
		$phrase = $modeleOperation -> getValeur('@Phrase', '');
		$titre = $modeleOperation -> getValeur('@Titre', $modeleOperation -> code);
		$theme = $modeleOperation -> getValeur('@Theme', '');
		$php = $modeleOperation -> getValeur('@PHP', '');
		if ($php === '') {
			$operation = SG_Operation::Creer($phrase, false);
		} else {
			if (!class_exists($nom)) {
				$operation = new SG_Erreur('0151', $nom);
			} else {
				$operation = new $nom();
			}
		}
		if (getTypeSG($operation) !== '@Erreur') {
			$operation -> modele = $code;
			$operation -> setValeur('@ModeleOperation', $code);
			$operation -> setValeur('@Theme', $theme);
			$operation -> setValeur('@Titre', $titre);
			$operation -> php = $modeleOperation -> getValeur('@PHP', '');
			if ($pSave) {
				$operation -> Enregistrer();
			}
		}
		return $operation;
	}

	/**
	 * Calcule le titre de l'opération
	 * @version 2.3 op person. : formule ; <br>
	 * @version 2.6 remplacement // par >
	 * @version 2.7 span id et oclick
	 * @return string
	 * @todo prendre libellé dans base
	 **/
	function Titre() {
		$titre = '';
		$modele = $this -> getValeur('@ModeleOperation', '');
		if ($modele !== '') {
			$titre = str_replace('//', ' > ',$this -> getValeur('@Titre', ''));
		} else {
			$titre = 'Opération personnalisée : <br><span id="formuleperso" class="sg-formule" onclick=SynerGaia.copy(event,null,\'formuleperso\')>' . $this -> Formule() -> texte . '</span>';
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

	/**
	* Conversion du titre de l'opération en chaine de caractères
	* @version 2.6 parm
	* @param string $pDefaut valeur par défaut 
	* @return string UUID du document de l'opération
	*/
	public function toString($pDefaut = NULL) {
		$titre = $this -> getValeur('@Titre', $pDefaut);
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

	/**
	 * Mettre l'opération en attente
	 * @version 1.1 : Enregistrer
	 * @return SG_Texte message de retour
	 */
	function MettreEnAttente() {
		$this -> setValeur('@Statut', SG_Operation::STATUT_ENATTENTE);
		$this -> Enregistrer();
		return new SG_Texte('Opération mise en attente.');
	}

	/**
	 * Suspendre l'opération
	 * @param 1.1 : Enregistrer
	 * @return SG_Texte message de retour
	 */
	function Suspendre() {
		$this -> setValeur('@Statut', SG_Operation::STATUT_SUSPENDUE);
		$this -> Enregistrer();
		return new SG_Texte('Opération suspendue.');
	}

	/**
	 * Annuler l'opération
	 * @version 1.1 : Enregistrer
	 * @return SG_Texte message de retour
	 */
	function Annuler() {
		$this -> setValeur('@Statut', SG_Operation::STATUT_ANNULEE);
		$this -> Enregistrer();
		return new SG_Texte('Opération annulée.');
	}

	/**
	 * Terminer l'opération
	 * @since 1.1 : ajout
	 * @return SG_Operation l'opération
	 */
	function Terminer() {
		$this -> setValeur('@Statut', SG_Operation::STATUT_TERMINEE);
		$this -> Enregistrer();
		return $this;
	}

	/**
	 * Alerter un utilisateur à propos de l'opération
	 * @since 1.1 ajout
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

	/**
	 * Renvoie le document ou objet principal de l'opération
	 * Il a du être initialisé dans Navigation::setPrincipal() ou lancement de l'opération
	 * @since 1.1 : Ajout (remplace DocumentPrincipal déprécié
	 * @version 2.5 getPrincipal()
	 * @return indéfini document ou objet principal
	 */
	function Principal() {
		$ret = $this -> getPrincipal();
		if($ret === null) { // ne peut pas arriver...
			$ret = new SG_Collection();
			$this -> setPrincipal($ret);
		} elseif (gettype($ret) === 'string') {
			// si seulement une référence, chercher le document dans la base
			$ret = $_SESSION['@SynerGaia'] -> getObjet($ret);
			// on en profite pour mettre à jour le princial de l'opération
			$this -> setPrincipal($ret);
		}
		return $ret;
	}

	/**
	 * Fabrique l'entete à disposer en haut de l'opération en cours d'exécution
	 * 
	 * @version 1.1 icone
	 * @return string entete (HTML) de l'opération
	 */
	function genererEntete() {
		$code = '';
		$icone = '';
		$modele = null;
		$opeModele = $this -> getValeur('@ModeleOperation', '');
		if ($opeModele !== '') {
			$modele = new SG_ModeleOperation($opeModele);
			$code = $modele -> code;
			$icone = new SG_Icone($modele -> getValeur('@IconeOperation'));
			$icone -> categ = '64x64/silkicons';
			if ($icone -> Existe() -> estVrai() === true) {
				$icone = $icone -> toHTMl() -> texte;
			} else {
				$icone = '';
			}
		}
		$entete = $icone . '<span class="sg-ope-titre" title="' . $code . '">' . $this -> Titre() . '</span>';
		if ($opeModele !== '') {
			$entete.= $this -> listeBoutons(false, $modele);
		}
		return $entete;
	}

	/** 
	 * Restitue la liste des boutons propres à l'opération. 
	 * De puis la version 1.3, il n'y a plus que le bouton de modification du modèle si on est administrateur
	 * 
	 * @version 1.3 suspendre et annuler enlevés
	 * @version 2.6 parm $pModele ; balise <img>
	 * @param boolean $pListe si vrai, sous forme de liste html (<li>)
	 * @param SG_ModeleOperation $pModele
	 * @return string
	 */
	function listeBoutons ($pListe = false, $pModele = null) {
		$ret = '';
		if ($pModele instanceof SG_ModeleOperation) {
			// Bouton Modifier du modele de l'opération (uniquement si profil administrateur)
			if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === true) {
				//url pour modifier le modèle
				$url = SG_Navigation::URL_PRINCIPALE;
				$url .= '?' . SG_Navigation::URL_VARIABLE_FORMULE . '=.@Modifier';
				$url .= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=' . $pModele -> getUUID();
				//lien
				$ret = '<a href="' . $url . '" class="sg-modif-modele" title="Modifier le modèle">';
				$ret.= '<img class="sg-raccourci" src="nav/themes/defaut/img/icons/16x16/silkicons/computer_edit.png"/></a>';
			}
		}
		return $ret;
	}

	/**
	 * Traiter : traite une étape de l'opération
	 * 
	 * @since 0.0
	 * @version 2.6 récup erreur sur bouton
	 * @param string $pEtape : code de la dernière étape traitée
	 * @param string $pBouton : code du bouton à calculer
	 * @param any $pObjet si fourni, les formules porteront sur cet objet plutôt que le principal (cas des formules parallèle au traitement principal)
	 * @param string pTyperes si fourni, le résultat n'est pas mis en forme pour l'affichage (résultat brut)
	 * @return array|SG_Erreur tableau des résultats d'instruction + ['submit'] texte du bouton submit principal
	 */
	public function Traiter($pEtape = '', $pBouton = '', $pObjet = '', $pTyperes = '') {
		SG_Pilote::declarerOperationActive($this);
		$ret = array('erreurs'=>'');
		$_SESSION['saisie'] = false;
		$resultat = null;
		$etape = $pEtape;
		if ($pBouton !== '') {
			// boutons programmés de l'opération (par exemple clic dans une vue)
			if(!isset($this -> boutons[$pBouton])) {
				$ret = $this -> mettreErreur('0050', $pBouton);
				$this -> erreurs[] = $ret;
			} else {
				$bouton = $this -> boutons[$pBouton];
				$this -> boutonencours = $pBouton;
				if ($bouton instanceof SG_Formule) {
					if (!is_object($pObjet)) {
						$o = $this -> getPrincipal();
					} else {
						$o = $pObjet;
					}
					if (is_null($bouton -> operation)) {
						$bouton -> operation = $this;
					}
					try {
						$res = $bouton -> calculerSur($o);
						if (getTypeSG($res) === '@HTML') {
							$res -> titre = $bouton -> titre;
						}
						$ret[] = $res;
					} catch (Exception $e) {
						if (isset($e -> erreur)) {
							$ret = $e -> erreur;
						} else {
							$err = @unserialize($e -> getMessage(), array("string","SG_Erreur"));
							if ($err === false) {
								$this -> mettreErreur($e -> getMessage());
							} else {
								$this -> mettreErreur($err);
							}
						}
					}
				} elseif (is_string($bouton)) {// formule de bouton à exécuter
					$formule = new SG_Formule($bouton);
					$ret = $formule -> calculerSur($pObjet);
				} elseif (is_array($bouton)) {// fichier à télécharger
					if ($bouton === array()) {
						$ret = $this -> mettreErreur('0267');
					} elseif (isset($bouton[0]) and $bouton[0] === 'fic') {
						if (isset($bouton[2])) {
							$ret[] = $this -> TelechargerFichier($bouton[1], $bouton[2]);
						} else {
							$ret = $this -> mettreErreur('0163', $bouton[0]);
						}
					}
				} else {
					$ret = $this -> mettreErreur('0164', $bouton[0]);
				}
			}
			if(is_array($ret)) {
				if (isset($_SESSION['saisie']) and $_SESSION['saisie'] === true) {
					$ret['submit'] = SG_Libelle::getLibelle('0118',false);
				}
			}
		} else {
			try {
				// exécute la formule spécifique de l'opération ../var/MO_xxx.php
				$ret = $this -> traiterSpecifique($etape, $pTyperes);
				if ($ret instanceof SG_Erreur) {
					$this -> erreurs[] = $ret;
					$ret = array('erreurs' => $ret -> toHTML(true));
				} elseif (is_array($ret)) {
					$ret['erreurs'] = '';
				}
			} catch (Exception $e) {
				$ret = array();
				$this -> erreurs[] = $e -> getMessage() . ' (' . $e -> getFile() . ' ligne ' . $e -> getLine() . ')';
			}
		}
		return $ret;
	}

	/** 
	 * récupère le texte de l'aide associée à l'opération
	 * @since 1.1 ajout
	 * @return SG_HTML
	 */
	public function Aide() {
		$ret = new SG_HTML('');
		$opeModele = $this -> getValeurPropriete('@ModeleOperation', '');
		if($opeModele instanceof SG_Erreur) {
			$ret = $opeModele;
		} elseif ($opeModele !== '') {
			$ret = $opeModele -> Aide();
		}
		return $ret;
	}
	
	/**
	 * code url de la prochaine étape ('index.php?o=xxx & e=yyy')
	 * @return string 
	 */
	function urlProchaineEtape () {
		$url = SG_Navigation::URL_PRINCIPALE . '?';
		$url .= SG_Navigation::URL_VARIABLE_OPERATION . '=' . $this -> reference;
		if ($this -> prochaineEtape != '') {
			$url .= '&' . SG_Navigation::URL_VARIABLE_ETAPE . '=' . $this -> prochaineEtape;
		}
		return $url;
	}

	/**
	 * prépare l'opération pour l'enregistrement
	 * @version 2.4 pas de SaveFormule ; unset dans boutons ; test objet
	 * @since 2.1 php
	 * @return boolean true
	 */
	function preEnregistrer() {
		$this -> setValeur('@Phrase', $this -> phrase);
		$this -> setValeur('@Etape', $this -> etape);
		$this -> setValeur('@PHP', $this -> php);
		// suppression des champs qui font récursion dans le json
		if (isset($this -> doc -> proprietes['@Boutons'])) {
			foreach ($this -> doc -> proprietes['@Boutons'] as $key => $elt) {
				if (is_object($elt)) {
					$elt -> objet = null;
					$elt -> formuleparent = null;
					$elt -> objetPrincipal = null;
					$elt -> operation = null;
				}
			}
		}
		return true;
	}

	/**
	 * indique si l'opération a été lancée il y a longtemps et n'a plus d'activité
	 * @version 2.4 correction ; défaut 7200 secondes (2h), si pas mod : date création
	 * @since 1.3.3
	 * @param integer $pDelai délai de péremption en secondes (par défaut 3600 = 1h)
	 * @return boolean
	 */
	function estInactive($pDelai = 7200) {
		$ret = false;
		$mod = $this -> getValeurPropriete('@DateModification','');
		if ($mod === '') {
			$mod = $this -> getValeurPropriete('@DateCreation');
		}
		if($mod instanceof SG_Erreur) {
			$ret = true;
		} elseif ($mod instanceof SG_DateHeure) {
			$delai = $mod -> Intervalle(SG_Rien::Maintenant());
			$ret = abs($delai -> toInteger()) > $pDelai;
		}
		return $ret;		
	}

	/**
	 * Exécute la branche php correspondant à l'étape
	 * @todo voir si à supprimer car inutilisé
	 * @since 2.1
	 * @param integer $pNoBranche indice de la branche à exécuter
	 * @param SG_Objet $pObjet objet principal sur lequel porte l'opération
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

	/**
	 * Teste si un objet est un SG_Operation ou une classe dérivée de SG_Operation
	 * @since 2.1
	 * @param SG_Objet $pOperation : l'objet à tester
	 * @return boolean réponse
	 **/
	static function isOperation($pOperation) {
		if (is_object($pOperation)) {
			$ret = (get_class($pOperation) === 'SG_Operation' or is_subclass_of($pOperation, 'SG_Operation'));
		} else {
			$ret = ($pOperation === 'SG_Operation' or $pOperation === '@Operation' or is_subclass_of($pOperation, 'SG_Operation'));
		}
		return $ret;
	}

	/**
	 * Telecharge un fichier (surcharge la méthode de SG_Document pour l'appliquer sur le document principal ou sur le paramètre doc de $pRefChamp)
	 * 
	 * @since 2.1
	 * @version 2.2 correct si refchamp uuid
	 * @param string|SG_Texte|SG_Formule $pRefChamp
	 * @param string|SG_Texte|SG_Formule $pFichier nom du fichier à télécharger
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
			$ret = $this -> mettreErreur('0165', getTypeSG($p));
		}
		return $ret;
	}

	/**
	 * retourne "@Operation" pour les operation PHP compilées
	 * @since 2.1
	 * @return string '@Operation'
	 */ 
	function getTypeSG() {
		return '@Operation';
	}

	/**
	 * surchargée dans les opérations dérivées
	 * @since 2.1
	 * @version 2.3 : err 0190
	 * @param string $etape code de l'étape à traiter (inutilisé ici)
	 * @param string $typeres type de résultat (inutilisé ici)
	 * @return SG_Erreur 0001
	 **/
	function traiterSpecifique($etape = '', $typeres='') {
		return new SG_Erreur('0001');
	}

	/**
	 * retourne un parametre de l'url de lancement ('p1' à 'p3')
	 * @since 2.1.1
	 * @param string $pNo n° de parametre
	 * @return SG_Texte valeur du paramètre
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

	/**
	 * On regarde d'abord sur l'objet puis éventuellement dans propriété de l'opération en cours
	 * @since 2.1.1 ajout pour compilateur
	 * @param string $pNom le nom de la propriété ou de la méthode dans le dictionnaire
	 * @param string $pNomMethode le nom de la propriété ou de la méthode de la classe
	 * @param SG_Objet $pObjet celui dont on cherche une propriété ou une méthode
	 * @return any|SG_Erreur la valeur ou @Erreur(166) ou @Erreur(175)
	 **/
	function getProprieteOuMethode($pNom, $pNomMethode, $pObjet = null) {
		 if (!is_object($pObjet)) {
			$ret = $this -> mettreErreur('0175',$pNom . SG_Texte::getTexte($pObjet));
		} elseif (isset($pObjet -> proprietes[$pNom])) {
			$ret = $pObjet -> getValeurPropriete($pNom, '');
		} elseif (isset($this -> proprietes[$pNom])) {
			$ret = $this -> proprietes[$pNom];
		} elseif (method_exists($pObjet, $pNomMethode)){
			$ret = $pObjet -> $pNomMethode();
		} else {
			$ret = $pObjet -> getValeurPropriete($pNom, '');
		}
		return $ret;
	}

	/**
	 * Affiche le texte de la formule prise en charge par l'opération
	 * @since 2.2
	 * @return SG_Texte la formule
	 **/
	function Formule() {
		return new SG_Texte($this -> phrase);
	}
	/** 
	 * Calcule une suite de formules sur le même élément (sert dans le passage de paramètres)
	 * @since 2.2
	 * @version 2.4 test stdClass ; mettreErreur
	 * @param SG_Objet $pElement élément sur lequel on exécute les calculs
	 * @param array tableau de $pFormules à exécuter
	 * @return array tableau des résultats
	 **/
	static function calculerSur($pElement, $pFormules) {
		$ret = array();
		foreach($pFormules as $f) {
			if (getTypeSG($f) === '@Formule') {
				if(get_class($pElement) === 'stdClass') {
					$ret[] = $this -> mettreErreur('0200');
				} else {
					$ret[] = $f -> calculerSur($pElement);
				}
			} else {
				$ret[] = $f;
			}
		}
		return $ret;
	}

	/**
	 * Met l'erreur dans la liste et la retourne pour utilisation éventuelle
	 * @since 2.4
	 * @param string $pCode code ou une @Erreur
	 * @param string $pInfos infos complémentaires
	 * @param string $pTrace information de debug
	 * @return SG_Erreur
	 **/
	function mettreErreur($pCode, $pInfos = '', $pTrace = '') {
		if(getTypeSG($pCode) === '@Erreur') {
			$ret = $pCode;
			$this -> erreurs[$pCode -> code] = $ret;
		} else {
			$ret = new SG_Erreur($pCode, $pInfos, $pTrace);
			$this -> erreurs[$pCode] = $ret;
		}
		$e = new Exception($ret -> code);
		$e -> erreur = $ret;
		return $ret;
	}

	/**
	 * Arrete l'opération à cet endroit
	 * @since 2.4 ajout
	 * @param string $pCode code ou une @Erreur
	 * @param string $pInfos infos complémentaires
	 * @param string $pTrace information de debug
	 * @throws Exception Force l'arrêt
	 **/
	static function STOP($pCode, $pInfos = '', $pTrace = '') {
		if (getTypeSG($pCode) === '@Erreur') {
			$erreur = $pCode;
		} else {
			$erreur = new SG_Erreur($pCode, $pInfos, $pTrace);
		}
		$e = new Exception($erreur -> code);
		$e -> erreur = $erreur;
		throw $e;
	}

	/**
	 * Met le document comme principal de l'opération, éventuellement en modif
	 * @since 2.4 ajout
	 * @since 2.6 abandon parm $Modif
	 * @param any $pObjet l'objet à mettre en document principal
	 * @return SG_Operation $this
	 **/
	function setPrincipal($pObjet = null) {
		if (is_object($pObjet) and $pObjet instanceof SG_Operation) {
			$this -> proprietes['@Principal'] = null;
		} else {
			$this -> proprietes['@Principal'] = $pObjet;
		}
		return $this;
	}

	/**
	 * Récupère l'objet principal de l'opération
	 * @since 2.5
	 * @return SG_Objet le doc principal actuel ou $this
	 **/
	function getPrincipal() {
		if (isset($this -> proprietes['@Principal']) and ! is_null($this -> proprietes['@Principal'])) {
			$ret = $this -> proprietes['@Principal'];
		} else {
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * si plus de 70% de mémoire utilisée, Réduit les documents dans les propriétés de l'opération si elles sont une SG_Collection
	 * Cela évite un dépassement mémoire au moment de la fin du traitement (sauvegarde de la session):
	 * "PHP Fatal error:  Allowed memory size of 268435456 bytes exhausted (tried to allocate 49770496 bytes) in Unknown on line 0"
	 * @since 2.5
	 * @version 2.7 test is_object
	 * @return integer taile de la mémoire utilisée après la réduction
	 **/
	function reduirePrincipal() {
		foreach($this -> proprietes as $pkey => &$pelt) {
			if (getTypeSG($pelt) === '@Collection') {
				foreach ($pelt -> elements as $key => &$elt) {
					if (is_object($elt) and $elt -> DeriveDeDocument() -> estVrai()) {
						$id = new SG_IDDoc($elt);
						$id -> proprietes = $elt -> proprietes; // pour garder les propriétés particulière notamment les types de données ['@Type_xxx']
						$this -> proprietes[$pkey] -> elements[$key] = $id;
					}
				}
				$this -> proprietes[$pkey] -> reduit = true;
			}
		}
		$ret = memory_get_usage();
		return $ret;
	}

	/**
	 * Traite le cas de 'Objet.Fonction' sans paramètre.
	 * Méthode appelée dans l'exécution des formules compilées.
	 * 
	 * @since 2.5
	 * @version 2.6 traiter si $pObjet est array
	 * @param SG_Objet $pParent l'objet porteur de la propriété ou de la méthode
	 * @param SG_Objet $pObjet objet sur lequel porte la fonction
	 * @param string $nomp nom si propriété
	 * @param string $nomf nom si fonction
	 * @param string $nom nom dans la formule d'origine
	 * @return SG_Objet|SG_Erreur valeur calculée ou trouvée (ou erreur)
	 * @throws SG_Erreur si $pObjet est une erreur
	 * @todo traitement des preenregistrer et postenregistrer pas clair... à revoir et généraliser dans fonctioninitiale
	 **/
	static function execFonctionSansParametre ($pParent, $pObjet, $nomp, $nomf, $nom) {
		$txtvide = new SG_Texte('');
		if(is_array($pObjet)) {
			$objet = reset($pObjet);
		} else {
			$objet = $pObjet;
		}
		$type = getTypeSG($objet);
		if (!is_object($objet)) {
			$ret = new SG_Erreur('0166', $type . '.' . $nomp .  ' :' . SG_Texte::getTexte($objet));
		} elseif (getTypeSG($objet) === '@Erreur') {
			self::STOP($objet);
		} elseif (isset($objet -> proprietes[$nomp])) {
			// propriete locale;
			$ret = $objet -> proprietes[$nomp];
		} elseif (SG_Dictionnaire::isProprieteExiste($type,$nomp)) {
			// propriété au dictionnaire
			$ret = $objet -> getValeurPropriete($nomp, $txtvide);
		} elseif (isset($pParent -> proprietes[$nomp])) {
			// propriété de l'objet en cours d'exécution : opération (ou document)
			$ret = $pParent -> proprietes[$nomp];
		} elseif (method_exists($objet,$nomf)){
			// méthode spécifique type FN_nom
			$ret = $objet -> $nomf();
		} elseif ($nomf !== $nom and (method_exists($objet, $nom))){
			// méthode spécifique telle quelle
			$ret = $objet -> $nom();
		} else {
			// sinon propriété du document
			$ret = $objet -> getValeurPropriete($nomp, $txtvide);
		}
		return $ret;
	}
	
	/**
	 * Préparation d'une formule avant son traitement, généralement comme paramètre d'une étape compilée
	 * 
	 * @since 2.6
	 * @param string $pNo
	 * @param string $pMethode
	 * @param SG_Objet $pObjet
	 * @return SG_Formule mise à jour
	 */
	 function preparerFormule($pNo, $pMethode, $pObjet) {
		$ret = new SG_Formule();
		$ret -> fonction = 'fn' . $pNo;
		$ret -> methode = '.' . $pMethode;
		$ret -> objetPrincipal = $pObjet;
		$ret -> objet = $pObjet;
		$ret -> setParent($this);
		$ret -> operation = $this;
		return $ret;
	}

	/**
	 * Exécute un appel de fonction (ou de popriété) quand il y a des paramètres
	 * 
	 * @since 2.6
	 * @param SG_Objet $pObjet
	 * @param string $nom 
	 * @param string $nomf
	 * @param srring $nomp
	 * @param array $pParms tableau des paramètres de la fonction
	 * @return SG_Objet résultat de la fonction ou de la propriété
	 */
	static function execFonctionAvecParametres ($pObjet, $nom, $nomf, $nomp, $pParms) {
		if (is_array($pParms)) {
			$parms = $pParms;
		} else {
			$parms = array($pParms);
		}
		if (SG_Dictionnaire::isProprieteExiste(getTypeSG($pObjet), $nomp)) {
			$ret = call_user_func_array(array($pObjet, 'MettreValeur'), array_merge(array($nomp),$parms));
		} elseif (method_exists($pObjet, $nomf)){
			$ret = call_user_func_array(array($pObjet, $nomf), $parms);
		} elseif ($nomf !== $nom and method_exists($pObjet,$nom)) {
			$ret = call_user_func_array(array($pObjet, $nom), $parms);
		} elseif ($pObjet instanceof SG_Erreur) {
			$ret = $pObjet;
		} else {
			// si on a un SG_IDDoc, on tente avec le document
			if ($pObjet instanceof SG_IDDoc) {
				$ret = self::execFonctionAvecParametres ($pObjet -> Document(), $nom, $nomf, $nomp, $parms);
			} else {
				$ret = new SG_Erreur('0150',getTypeSG($pObjet) . '.' . $nomf);
			}
		}
		return $ret;
	}

	/**
	 * Contrôle le résultat d'une instruction avant de passer à la suivante
	 * 
	 * @since 2.6
	 * @param any $pResultat resultat brut à contrôler
	 * @param any $pRetDernier dernier résultat partiel obtenu
	 * @return SG_Rien|SG_Objet|SG_Erreur
	 */
	static function controlerResultat($pResultat, $pRetDernier) {
		$ret = $pResultat;
		if (is_array($ret)) { 
			switch (sizeof($ret)) {
				case 0 : $ret = $pRetDernier;break;
				case 1 : $ret = $ret[0];break;
				default: $ret = new SG_Collection($ret);
			}
		}
		if(is_null($ret)){
			$ret = new SG_Rien();
		}
		if(!is_object($ret)) {
			$ret = new SG_Erreur('0281',getTypeSG($ret));
		}
		return $ret;
	}
	
	/**
	 * Traite une étape (suite d'instructions avant étiquette ou fin de branche)
	 * Si le code est 'rien' on ne fait rien et on retourne ''
	 * 
	 * @since 2.6 (repris de compilation)
	 * @param string $pCode code de l'étape (éventuellement code manuel)
	 * @param SG_Objet $objet objet principal de l'étape
	 * @param string $typeres type de résultat ('f' si on veut un résultat brut de l'éxécution de la formule)
	 * @param sting $pSuite code de l'étape suivante
	 * @return SG_Objet|SG_Erreur
	 */
	function traiterEtape($pCode, $objet, $typeres = '', $pSuite) {
		// préparer envoi vers l'étape suivante (peut être modifié dans l'étape)
		if($typeres === '') {
			$this -> prochaineEtape = $pSuite;
		}
		$ret = false;
		$resultat = array();
		$code = $pCode;
		if ($code === 'rien') {
			$resultat = '';
		} else {
			// appel de la fonction d'étape
			$nomfn = SG_Compilateur::PREFIXE_FONCTION . $code;
			if (! method_exists($this, $nomfn)) {
				$resultat = new SG_Erreur('0296', $code . ' (' . $pCode . ')');
			} else {
				try{
					$dernierresultat = '';
					$resultat = $this -> $nomfn($objet, $dernierresultat);
					if ($typeres === 'f') {
						// le dernier résultat doit être retourné brut
						$resultat = $dernierresultat;
					} elseif ($resultat === array()) {
						// si à la fin on a aucun résultat on retourne le résultat de la dernière fonction mis en html
						$resultat[] = new SG_HTML($dernierresultat);
					}
				} catch (Exception $e) {
					if(isset($e -> erreur)) {
						$resultat = $e -> erreur;
					} else {
						$resultat = new SG_Erreur('0250', strrchr($e-> getFile(), '/') . ' (ligne ' . $e -> getLine(). ') ' . $e -> getMessage());
					}
				}
			}
		}
		return $resultat;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Operation_trait;
}
?>
