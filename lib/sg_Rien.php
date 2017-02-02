<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Rien : Classe d'origine pour tous les objets SynerGaïa
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Rien_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Rien_trait.php';
} else {
	trait SG_Rien_trait{};
}
class SG_Rien {
	// Type SynerGaia
	const TYPESG = '@Rien';
	public $typeSG = self::TYPESG;
	
	const FLAGDEMANDER = '@Demander'; // 1.3.0
	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	static function toString() {
		return '';
	}

	/**
	 * Conversion valeur numérique
	 *
	 * @return float valeur numérique
	 */
	static function toFloat() {
		return (double)0;
	}

	/**
	 * Conversion valeur numérique
	 *
	 * @return integer valeur numérique
	 */
	static function toInteger() {
		return (integer)0;
	}

	/**
	 * Conversion en code HTML
	 *
	 * @return string code HTML
	 */
	static function toHTML() {
		return '';
	}
	// 1.1 ajout
	static function Afficher() {
		return '';
	}
	/** 1.3
	 * Teste si la valeur est vide
	 *
	 * @return SG_VraiFaux est vide
	 */
	static function EstVide() {
		return new SG_VraiFaux(true);
	}

	/**
	 * Renvoie un @Rien
	 *
	 */
	static function Vide() {
		return new SG_Rien();
	}

	/** 1.0.5
	 * Renvoie un @VraiFaux VRAI
	 *
	 */
	static function Vrai() {
		return new SG_VraiFaux(SG_VraiFaux::VRAIFAUX_VRAI);
	}

	/** 1.0.5
	 * Renvoie un @VraiFaux FAUX
	 *
	 */
	static function Faux() {
		return new SG_VraiFaux(SG_VraiFaux::VRAIFAUX_FAUX);
	}

	/** 1.0.5
	 * Renvoie un @VraiFaux VRAI
	 *
	 */
	static function Oui() {
		return new SG_VraiFaux(SG_VraiFaux::VRAIFAUX_VRAI);
	}

	/** 1.0.5
	 * Renvoie un @VraiFaux FAUX
	 *
	 */
	static function Non() {
		return new SG_VraiFaux(SG_VraiFaux::VRAIFAUX_FAUX);
	}

	/** 1.0.5
	 * Renvoie un @VraiFaux INDEFINI
	 *
	 */
	static function Indefini() {
		return new SG_VraiFaux(SG_VraiFaux::VRAIFAUX_INDEF);
	}
	
	/**
	 * Renvoie la date du jour
	 *
	 * @return SG_Date aujourd'hui
	 */
	static function Aujourdhui() {
		return new SG_Date(time());
	}

	/**
	* Renvoie l'instant actuel
	*
	* @return SG_DateHeure maintenant
	*/
	static function Maintenant() {
		return new SG_DateHeure(time());
	}

	/** 1.2 ne passe plus par le cache (à cause des photos)
	* Renvoie l'utilisateur connecté
	* @return SG_Utilisateur "moi"
	*/
	static function Moi() {
		$id = SG_SynerGaia::IdentifiantConnexion();
		if ($id == '') {
			$id = 'anonyme';
		}
		if(!isset($_SESSION['@Moi'])) {
			$_SESSION['@Moi'] = new SG_Utilisateur($id);
		} elseif ($_SESSION['@Moi'] -> identifiant !== $id) {
			$_SESSION['@Moi'] = new SG_Utilisateur($id);
		}
		return $_SESSION['@Moi'];
	}

	/** 1.1 référence à SG_Navigation
	* Renvoie l'opération en cours
	* @return SG_Operation opération en cours
	*/
	static function OperationEnCours() {
		return SG_Navigation::OperationEnCours();
	}

	/** 1.3.3 retourne un @Log
	* si paramètre : Ecrit un message en log. Retourne un objet @Log
	* @param indéfini $pMessage Message
	* @return SG_Texte message
	*/
	static function Log($pMessage = '') {
		$type = getTypeSG($pMessage);
		if ($type === '@Formule') {
			$pMessage = $pMessage -> calculer();
		}
		$sgMessage = new SG_Texte($pMessage);
		$message = $sgMessage -> toString();
		journaliser($message);
		return new SG_Log();
	}

	/** 1.0.7 ; 2.1 ret si pas formule ; 2.3 si deux paramètres : sinon complet
	* Conditionne l'exécution de formule au résultat d'une autre
	*
	* @param indéfini $pCondition Condition testée
	* @param SG_Formule $pValeurSiVrai Valeur si condition vraie
	* @param SG_Formule $pValeurSiFaux Valeur si condition fausse
	* @param SG_Formule $pValeurSiIndefini Valeur si condition indéfinie
	*
	* @return indéfini
	*/
	static function Si($pCondition = '', $pValeurSiVrai = null, $pValeurSiFaux = null, $pValeurSiIndefini = null) {
		$ret = null;
		$resultatCondition = new SG_VraiFaux($pCondition);
		if ($resultatCondition -> valeur === SG_VraiFaux::VRAIFAUX_VRAI) {
			if (getTypeSG($pValeurSiVrai) === '@Formule') {
				$ret = $pValeurSiVrai -> calculer();
			} else {
				$ret = $pValeurSiVrai;
			}
		} elseif ($resultatCondition -> valeur === SG_VraiFaux::VRAIFAUX_FAUX or $pValeurSiIndefini === null) {
			if (getTypeSG($pValeurSiFaux) === '@Formule') {
				$ret = $pValeurSiFaux -> calculer();
			} else {
				$ret = $pValeurSiFaux;
			}
		} elseif ($resultatCondition -> valeur === SG_VraiFaux::VRAIFAUX_INDEF) {
			if (getTypeSG($pValeurSiIndefini) === '@Formule') {
				$ret = $pValeurSiIndefini -> calculer();
			} else {
				$ret = $pValeurSiIndefini;
			}
		} else {
			$ret = new SG_Erreur('La condition du @Si ne donne pas une valeur @VraiFaux'); // todo
		}
		return $ret;
	}

	/** 1.1 correction ($uid) ; 1.3.4 3ème parametre ; 2.1 test isObjetDocument
	* Nouveau : Crée et enregistre un nouvel objet vide dérivé de @Doument
	*
	* @param string $pTypeObjet type de l'objet à créer
	* @param string $pReferenceObjet identifiant de l'objet à créer (pour le forcer)
	* @param objet de type document ; initialise à partir de l'objet passé en paramètre (qui peut ne pas être du même type)
	* @return indéfini nouvel objet
	*/
	static function Nouveau($pTypeObjet = '', $pReferenceObjet = '', $pObjetACopier = null) {
		$ret = null;
		$typeObjet = SG_Texte::getTexte($pTypeObjet);
		$referenceObjet = SG_Texte::getTexte($pReferenceObjet);

		// Fabrique, enregistre et restitue l'objet
		if ($typeObjet !== '') {
			$uid = SG_Dictionnaire::getCodeBase($typeObjet) . '/' . $referenceObjet;
			$classe = SG_Dictionnaire::getClasseObjet($typeObjet);
			if ($classe !== '') {
				if(!class_exists($classe)) {
					$objet = new SG_Erreur('Classe inexistante', $pType);// TODO
				} else {
					$objet = new $classe($uid);
					if (!SG_Dictionnaire::isObjetDocument($typeObjet)) {
						$objet -> typeSG = $typeObjet;
					} else {
						// si dérive de SG_Document, prépare un DocumentCouchDB
						if (!isset($objet -> doc)) {
							$objet -> initDocumentCouchDB();
						}
						if (!is_null($pObjetACopier)) {
							if (getTypeSG($pObjetACopier) === '@Formule') {
								$objetacopier = $pObjetACopier -> Calculer();
							} else {
								$objetacopier = $pObjetACopier;
							}
							if($objetacopier -> DeriveDeDocument() -> EstVrai()) {
								$objet -> doc -> proprietes = $objetacopier -> doc -> proprietes;
							}
						}
						$objet -> setValeur('@Type', $typeObjet);
					}
				}
			} else {
				$objet = new SG_Erreur('0013', $pType);
			}
			$ret = $objet;
		}
		return $ret;
	}
	/** 2.1 parm compilés
	* 1.1 n'est plus static (à cause de @Document.@Chercher) ; 1.3.0 redevient static ; 
	* Cherche dans l'univers SynerGaia les objets demandés
	* @param string $pTypeObjet type de l'objet
	* @param string $pCodeObjet code de l'objet
	* @param string $pFiltre formule de filtre immédiat
	* @return SG_Collection collection des objets trouvés
	*/
	static function Chercher($pTypeObjet = '', $pCodeObjet = '', $pFiltre = '') {
		$typeObjet = SG_Texte::getTexte($pTypeObjet);
		$codeObjet = SG_Texte::getTexte($pCodeObjet);
		$collection = new SG_Collection();
		// Dans quelle base chercher ?
		$codeBase = SG_Dictionnaire::getCodeBase($typeObjet);
		if ($codeBase !== '') {
			$collection = $_SESSION['@SynerGaia']->getChercherDocuments($codeBase, $typeObjet, $codeObjet, $pFiltre);
		}
		return $collection;
	}

	/**
	* Cherche les opérations pour l'utilisateur connecté
	*
	* @return SG_Collection collection des opérations trouvées
	* TODO traiter les opérations dérivées... $typegeneralsg
	*/
	static function MesOperations() {
		$collection = new SG_Collection();

		$codeBase = SG_Operation::CODEBASE;

		$jsFormule = '';

		$champType = '@Type';
		$type = '@Operation';
		$jsFormule .= "(doc['" . $champType . "']==='" . $type . "')";

		$champResponsable = '@Responsable';
		$responsable = SG_SynerGaia::IdentifiantConnexion();
		$jsFormule .= "&&(doc['" . $champResponsable . "']==='" . $responsable . "')";

		// Cherche les documents correspondants
		$jsSelection = "function(doc) { if (" . $jsFormule . ") { emit(doc['@Code'],doc['_id']); } }";
		$vue = new SG_Vue('', $codeBase, $jsSelection, true);
		$collection = $vue -> ChercherElements();

		return $collection;
	}

	/**
	* Cherche les opérations en attente pour l'utilisateur connecté
	*
	* @return SG_Collection collection des opérations trouvées
	* TODO traiter les opérations dérivées... $typegeneralsg
	*/
	static function MesOperationsEnAttente() {
		$collection = new SG_Collection();

		$codeBase = SG_Operation::CODEBASE;

		$jsFormule = '';

		$champType = '@Type';
		$type = '@Operation';
		$jsFormule .= "(doc['" . $champType . "']==='" . $type . "')";

		$champResponsable = '@Responsable';
		$reponsable = SG_SynerGaia::IdentifiantConnexion();
		$jsFormule .= "&&(doc['" . $champResponsable . "']==='" . $reponsable . "')";

		$champStatut = '@Statut';
		$statut = SG_Operation::STATUT_ENATTENTE;
		$jsFormule .= "&&(doc['" . $champStatut . "']==='" . $statut . "')";

		// Cherche les documents correspondants
		$jsSelection = "function(doc) { if (" . $jsFormule . ") { emit(doc['@Code'],doc['_id']); } }";
		$vue = new SG_Vue('', $codeBase, $jsSelection, true);
		$collection = $vue -> ChercherElements();

		return $collection;
	}

	/**
	* Cherche les opérations suspendues pour l'utilisateur connecté
	*
	* @return SG_Collection collection des opérations trouvées
	* TODO traiter les opérations dérivées... $typegeneralsg
	*/
	static function MesOperationsSuspendues() {
		$collection = new SG_Collection();

		$codeBase = SG_Operation::CODEBASE;

		$jsFormule = '';

		$champType = '@Type';
		$type = '@Operation';
		$jsFormule .= "(doc['" . $champType . "']==='" . $type . "')";

		$champResponsable = '@Responsable';
		$reponsable = SG_SynerGaia::IdentifiantConnexion();
		$jsFormule .= "&&(doc['" . $champResponsable . "']==='" . $reponsable . "')";

		$champStatut = '@Statut';
		$statut = SG_Operation::STATUT_SUSPENDUE;
		$jsFormule .= "&&(doc['" . $champStatut . "']==='" . $statut . "')";

		// Cherche les documents correspondants
		$jsSelection = "function(doc) { if (" . $jsFormule . ") { emit(doc['@Code'],doc['_id']); } }";
		$vue = new SG_Vue('', $codeBase, $jsSelection, true);
		$collection = $vue -> ChercherElements();

		return $collection;
	}
	
	/** 1.0
	* active ou désactive la variable $SESSION['debug_on'] selon le paramètre
	* 
	* @param indefini $pOn soit true ou false, soit @VraiFaux
	* @return : @Rien.@Debug
	*/
	static function ActiverDebug($pOn = true) {
		$typeOn = getTypeSG($pOn);
		if ($typeOn === '@Formule') {
			$pOn = $pOn -> calculer();
		}
		if (getTypeSG($pOn) === '@VraiFaux') {
			$tmpdebug = $pOn -> estVrai();
		} else {
			$tmpdebug = $pOn;
		}
		if ($tmpdebug) {
			$_SESSION['debug']['on'] = true;
			journaliser ('<b>===== DEBUG ACTIF ===== </b>');
		} else {
			journaliser ('<b>----- debug inactif -----</b>');
			$_SESSION['debug']['on'] = false;
		}
		
		return SG_Rien::Debug();
	}
	
	/** 1.0
	* retourne la valeur actuelle de $SESSION['debug_on']
	*/
	static function Debug() {
		$ret = new SG_VraiFaux($_SESSION['debug']['on']);
		return $ret;
	}
	/** 1.0.1
	* Permet de tester le temps de réponse d'une formule
	* @param SG_Formule $pFormule : texte de la formule à tester
	* @param nombre $pNb : nombre de fois que la formule doit être testée (par défaut : 1)
	* @param string $pCodeDebug : le code de benchmark qui sera utiliser pour stocker les temps de réponse (par défaut 'Tester')
	* 
	* @return SG_Collection : collection des résultats de la formule (1 élément par exécution)
	*/
	static function Tester($pFormule = null, $pNb = 1, $pCodeDebug = 'Tester') {
		journaliser ('=>Tester :' . getTypeSG($pFormule) . ' ' . getTypeSG($pNb) . ' ' . getTypeSG($pCodeDebug) );
		if ($pNb === 1) {
			$nb = 1;
		} else {
			$nb = $pNb -> calculer() -> toInteger();
		}
		if ($pCodeDebug === 'Tester') {
			$codedebug = $pCodeDebug;
		} else {
			$codedebug = $pCodeDebug -> calculer() -> toString();
		}
		journaliser (' Tester (' . $codedebug . ') : ' . $pFormule -> toString() . '. ' . $nb . ' fois');
		$collec = new SG_Collection();
		for ($i = 0; $i < $nb ; $i++) {
			SG_Pilote::Benchmark($codedebug, true);
			$collec -> ajouter($pFormule -> calculer());
			SG_Pilote::Benchmark($codedebug, false);
		}
		journaliser ('<-Tester');
		return $collec;
	}
	/** 2.3 err 0184
	* 1.1 test si retour collection ; 1.3.1 ajout menu accueil même vide ; 2.0 modif formule ; 2.1.1 remplace formule
	* retourne la collection des thèmes de mes opérations, triés par ordre de position
	* @param boolean : force le recalcul plutôt que la valeur en cache
	* @return : 
	* @formula : @Moi.@ModelesOperations.@PourChaque(.@Theme).@Ajouter(@Theme("Accueil")).@Unique.@Trier(.@Position)
	*/ 
	static function MesThemes($recalcul = false) {
		if ($recalcul or !isset($_SESSION['page']['mesthemes'])) {
			if (!isset($_SESSION['@Moi'])) {
				$ret = new SG_Erreur('0184');
			} else {
				$mmo = $_SESSION['@Moi'] -> ModelesOperations();
				$mmot = array();
				foreach($mmo -> elements as $elt) {
					if (isset($elt -> doc -> proprietes['@Theme'])) {
						$code = $elt -> doc -> proprietes['@Theme'];
						if(! isset($mmot[$code])) {
							$thm = $elt -> getValeurPropriete('@Theme','');
							if ($thm !== '') {
								$mmot[$code] = $thm;
							}
						}
					}
				}
				$ret = new SG_Collection();
				$ret -> elements = $mmot;
				$accueil = new SG_Theme("Accueil");
				$ret -> Ajouter($accueil);
				$ret = $ret -> Unique();
				$formule = new SG_Formule('.@Position');
				$collec = $ret -> Trier($formule);
				$compacter = false;
				if (getTypeSG($collec) === '@Collection') {
					foreach($collec -> elements as $key => $theme) {
						if ($theme === NULL) {
							unset ($collec -> elements[$key]);
							$compacter = true;
						} elseif ($theme -> Titre() === '' or $theme -> Titre() === null) {
							unset ($collec -> elements[$key]);
							$compacter = true;
						}
			
					}
					if ($compacter) { $collec->elements = array_values($collec->elements);}
				} else {
					$collec = new SG_Collection();
				}
				$ret = $_SESSION['page']['mesthemes'] = $collec;
			}
		} else {
			$ret = $_SESSION['page']['mesthemes'];
		}
		return $ret;
	}
	
	/**
	* ThemeEnCours : retourne le @Theme en cours (sinon null)
	* @return @Theme : thème auquel appartient l'opération en cours
	* @formula : @OperationEnCours.@Theme
	*/ 
	static function ThemeEnCours () {
		$ret = null;
		if (isset($_SESSION['page']['theme'])) {
			$themeEnCoursId = $_SESSION['page']['theme'];
			if ($themeEnCoursId !== '') {
				$ret = new SG_Theme($themeEnCoursId);
			}
		}
		return $ret;
	}
	/** 1.3.3 return @HTML ; 2.3 test err
	* retourne le menu du @Theme fourni en paramètre (sinon null) si le thème m'appartient
	* @param string ou formule donnat un string : Thème dont on veut le menu
	* @param boolean : force le recalcul du thème (plutôt que celui du cache)
	* @return @HTML : le texte du menu 
	* @formula : @Si(@MesThemes.@Contient(theme),@Navigation.@MenuTheme(theme),@Rien)
	*/ 	
	static function MenuTheme($pTheme = '', $recalcul = true) {
		$ret = '';
		$nom = $pTheme;
		if (getTypeSG($pTheme) === '@Formule') {
			$t = new SG_Texte($pTheme);
			$nom = $t -> toString();
		}
		$themes = self::MesThemes();
		if (getTypeSG($themes) === '@Erreur') {
			$ret = $themes;
		} else {
			foreach ($themes -> elements as $theme) {
				if ($theme->Titre() == $nom) {
					$ret = SG_Navigation::MenuTheme($theme, $recalcul);
					break;
				}
			}
			$ret = new SG_HTML($ret);
		}
		return $ret;
	}
	/* 1.0
	* Active ou désactive l'envoi effectif des messages
	* @param indéfini flag : soit "oui" ou @Vrai ou true, soit "non" ou @Faux ou false
	* @return @Vrai si envoi effectif activé, @Faux si envoi désactivé
	*/
	static function MailEnvoi ($pActif = true) {
		if( ! SG_Rien::Moi() -> EstAdministrateur() -> estVrai()) {
			$ret = new SG_Erreur('Méthode réservée à l\'administrateur');
 		} else {
			$actif = '';
			$actuel = SG_Config::getConfig('Mail_Envoi', 'oui');
			$ret = new SG_VraiFaux ($actuel);
			if (is_bool($pActif)) {
				$actif = $pActif;
			} else {
				$type = getTypeSG($pActif);
				if ($type === '@Formule') {
					$actif = $pActif -> calculer();
					$type = getTypeSG($actif);
				} else {
					$actif = $pActif;
				}
				if ($type !== 'string') {
					$actif = $actif -> toString();
				}
				if (strtolower($actif) === 'oui') {
 					$actif = true;
				} elseif (strtolower($actif)=== 'non') {
					$actif = false;
				}
			}
			if(is_bool($actif)) {
				if ($actif === true) {
					$ok = SG_Config::setConfig('Mail_Envoi', 'oui');
				} elseif ($actif === false) {
					$ok = SG_Config::setConfig('Mail_Envoi', 'non');
				}
				if ($ok === false) {
					$ret = new SG_Erreur('Modification config impossible : pas de changement');
				} else {
					$actuel = SG_Config::getConfig('Mail_Envoi', 'oui');
					$ret = new SG_VraiFaux ($actuel);
				}
			} else {
				$ret = new SG_Erreur('Paramètre inconnu : pas de changement');
			}
		}
		return $ret;
	}
	/** 1.0
	* 
	*/
	static function NouvelleLigne () {
		return PHP_EOL;
	}
	
	static function Table($pCode = '') {
		$valeurs = array();
		$code = $pCode;
		if (getTypeSG($pCode) === '@Formule') {
			$code = $pCode -> calculer();
		}
		if (gettype($code) === 'object') {
			$code = $code -> toString();
		}
		$codeCache = '@Table(' . $code . ')';
		if (SG_Cache::estEnCache($codeCache, false)) {
			$valeurs = json_decode(SG_Cache::valeurEnCache($codeCache, false));
		} else {
			$collec = SG_Rien::Chercher('@Table', $code);
			if (sizeof($collec -> elements) !== 0) {
				$valeurs = $collec -> elements[0] -> getValeur('Valeurs');
				SG_Cache::mettreEnCache(json_encode($codeCache, $valeurs, false));
			}
		}
		$ret = new SG_Collection();
		$ret -> elements = $valeurs;
		return $ret;
	}
	/** 1.0
	 * getValeurPropriete : pour éviter de provoquer des erreurs quand la pile de recherche de l'objet parent arrive à @Rien
	 */
	static function getValeurPropriete ($pNom) {
		return '';
	}
	/** 1.1 ajout ; 1.3.2 message 0013 -> 0078 ; ok si $pValeur est objet ; 2.3 0186
	* crée un objet SynerGaïa vide à partir de son type
	* @param string $pType type d'objet à créer
	**/
	static function creerObjet($pType = '', $pValeur = null) {
		$classe = SG_Dictionnaire::getClasseObjet($pType, false);
		if ($classe !== '') {
			if (! class_exists($classe)) {
				$objet = new SG_Erreur('0186', $classe);
			} else {
				$objet = new $classe($pValeur);
				if ($classe !== '@Document') {
					if (is_array($pValeur)) {
						$objet -> proprietes = $pValeur;
					}
					if(!is_array($objet -> proprietes)) {
						$objet -> proprietes = array();
					}
					$objet -> proprietes['@Type'] = $pType;
					if (method_exists($objet, 'initObjet')) {
						$objet -> initObjet();
					}
				}
			}
		} elseif (is_object($pValeur)) {
			$objet = $pValeur;
		} else {
			$objet = new SG_Erreur('0078', $pType);
		}
		return $objet;
	}	
	/** 1.1 utilise getClasseObjet
	* Retourne un objet SynerGaïa à partir d'un tableau. Il doit contenir une propriété "typeSG" sauf si l'objet est déjà fourni.
	* Cette fonction peut travailler récursivement
	*
	* @param string $pTableau le tableau à p artir duquel on construit l'objet
	* @param indéfini $pValeurDefaut valeur de la propriété si le champ n'existe pas (défaut null)
	* @param boolean $pRecursive nombre de niveau de récursivité (0 : pas récursif, -1 tout récursif).
	* @return indéfini retourne l'objet SynerGaïa si typeSG défini, ou la valeur initiale fournie sinon
	*/
	static public function creerObjetSynerGaia($pTableau = null, $pRecursive = 0) {
		$ret = $pTableau;
		// Si la valeur est nulle ne cherche pas plus
		if (! is_null($pTableau)) {
			if (is_array($pTableau)) {
				if (isset($pTableau['typeSG'])) {
					$objet = SG_Rien::Nouveau($pTableau['typeSG']);//creerObjetVide
					// remplir les propriétés avec les autres valeurs du tableau
					if (getTypeSG($objet) !== '@Rien') {
						foreach($pTableau as $key => $valeur) {
							if ($key !== 'typeSG') {
								if ($pRecursive !== 0) {
									$objet -> $key = SG_Rien::creerObjetSynerGaia($valeur, $pRecursive - 1);
								} else {
									$objet -> $key = $valeur;
								}
							}
						}
					}
				} else {
					// Ce n'est pas un objet mais si il y a récursivité, on continue l'exploration au cas où il y aurait des objets
					$objet = $pTableau;			
					if ($pRecursive !== 0) {			
						foreach($objet as $key => $valeur) {
							$objet[$key] = SG_Rien::creerObjetSynerGaia($valeur, $pRecursive - 1);
						}
					}
				}
				$ret = $objet;					
			}
		}
		return $ret;
	}
	/** 1.1 ajout ; 1.3.1 correction $code ; 1.3.4 vue domino directe
	* Crée une collection à partir d'une vue sur un serveur de vue
	*/
	static function ChercherVue($pCodeVue = '', $pFiltre = '', $IncludeDocs = false) {
		$codevue = SG_Texte::getTexte($pCodeVue);
		$defvue = SG_Rien::Chercher('@DictionnaireVue', $codevue);
		if (sizeof($defvue -> elements) === 0) {
			$elts = explode(':', $codevue);
			if (sizeof($elts) > 1) {
				$vue = new SG_VueDominoDB($elts[0], $elts[1]);
				$ret = $vue -> Contenu();
			} else {
				$ret = new SG_Erreur('0030', $codevue);
			}
		} else {
			$defvue = $defvue -> Premier();
			if (! is_object($defvue)) {
				$ret = new SG_Erreur('0032', $codevue);
			} else {
				$objet = $_SESSION['@SynerGaia'] -> getObjet($defvue -> getValeur('@Objet', ''), '@DictionnaireObjet');
				$base =  strtolower($objet -> getValeur('@Base', ''));
				if($base === '') {
					$base = strtolower($objet -> getValeur('@Code', ''));
				}
				$canal = 'couchdb';
				if (strpos($base, '.nsf') !== false) {
					$canal = 'domino';
				}
				if ($canal === '' or $canal === 'couchdb') {
					$ret = new SG_Collection();
					if ($objet !== '') {
						$code = $objet -> getValeur('@Code');
						$nomVue = $code . '_' . $defvue -> code;
						// phrase de sélection
						$jsSelection = "function(doc) {if (doc['@Type']==='" . $code . "'";
						$filtre = $defvue -> getValeur('@Filtre', '');
						if ($filtre !== '') {
							$jsSelection .= " && (" . $filtre .")";
						}
						$jsSelection .= "){emit(";
						$cle = $defvue -> getValeur('@Cle', '');
						if ($cle !== '') {
							$jsSelection .= "doc['" . $cle . "'], {";
						} else {
							$jsSelection .= "doc['_id'], {";
						}
						$colonnes = $defvue -> getValeur('@Colonnes');
						$colonnes = explode(',', $colonnes);
						foreach ($colonnes as $col) {
							$col = str_replace('"', "'", $col);
							$jsSelection .= " ". $col . ":doc[" . $col . "],";
						}
						$jsSelection .= " '_base': '" . $base . "', '_id': doc['_id'],'@Type':doc['@Type']";
						$jsSelection .= "});}}";
						// création de la vue
						$vue = new SG_Vue($base . '/' . strtolower($nomVue),$base,$jsSelection, true);
						$ret = $vue -> Contenu('', $pFiltre, $IncludeDocs);
					}
					/*
					$selection = 
					$vuephysique = new SG_Vue($vue -> code, $base, $selection, true);
					$ret = new SG_Erreur('0022', $canal);
					*/
				} elseif ($canal === 'domino') { // IBM Domino
					if ($defvue -> code === '') {
						$ret = new SG_Erreur('0023');
					} else {
						$vue = new SG_VueDominoDB($base, $defvue -> code);
						$ret = $vue -> Contenu('', $pFiltre, $IncludeDocs);
					}
				} else {
					$ret = new SG_Erreur('0025', $canal);
				}
			}
		}
		return $ret;
	}
	/** 1.1 new ; 1.3.0 variables dans opération en cours;  ; 2.0 défini par modele.propriété ; 2.3 corrigé modele.propriete
	*/
	static function Demander() {
		$ret = '';
		$args = func_get_args();
		if (isset($args[0])) {
			$_SESSION['saisie'] = true;
			// crée un document basé sur l'opération			
			$opEnCours = SG_Navigation::OperationEnCours();
			$doc = $opEnCours;
			$ret = $opEnCours -> getChampEnregistrer();
			
			// crée les propriétés (une par paramètre)
			$ret .= '<ul data-role="listview">';
			$docs = SG_Dictionnaire::ObjetsDocument();
			for($i = 0; $i < sizeof($args); $i++) {
				$proprietes = explode(',',SG_Texte::getTexte($args[$i]));
				if (isset($proprietes[1]) and $proprietes[1] !== '' and strpos($proprietes[1], '.') !== false) {
					// le type est du genre modele.propriété
					$ipos = strpos($proprietes[1], '.');
					$type = substr($proprietes[1], 0, $ipos);
					$tmpdoc = SG_Rien::Nouveau($type);
					$champ = new SG_Champ(substr($proprietes[1], $ipos + 1), $tmpdoc);
					//$champ -> codeBase = SG_Dictionnaire::getCodeBase($type);
					$champ -> document = $doc;
					$champ -> codeDocument = $doc -> doc -> codeDocument;
					$champ -> codeBase = $doc -> doc -> codeBase;
					$champ -> refChamp = $champ -> codeBase . '/' . $champ -> codeDocument . '/' . $proprietes[0];
					// bind the field to the current operation
					//$champ -> initContenu();
					$doc -> proprietes['@Type_' . $proprietes[0]] = SG_Dictionnaire::getCodeModele($proprietes[1]);
				} else {
					$champ = new SG_Champ('');
					if (isset($proprietes[1]) and $proprietes[1] !== '') {
						// on a un type de champ
						$champ -> typeObjet = $proprietes[1];
						// si c'est un @Document, on crée un champ de type lien
						if(array_key_exists($proprietes[1], $docs -> elements)) {
							$champ -> typeLien = $proprietes[1];
						}
					}
					$champ -> libelle = $proprietes[0];
					$champ -> codeChamp = $proprietes[0];
					//$champ -> typeLien = '';
					$champ -> multiple = false;
					$champ -> valeur = '';
					$opEnCours -> proprietes['@Type_' . $champ -> codeChamp] = $champ -> typeObjet;
					if (isset($proprietes[2]) and $proprietes[2] !== '') {
						$champ -> libelle = $proprietes[2];
					}
					$champ -> codeBase = $doc -> doc -> codeBase;
					$champ -> document = $doc;
					$champ -> codeDocument = $doc -> doc -> codeDocument;
					$champ -> refChamp = $champ -> codeBase . '/' . $champ -> codeDocument . '/' . $champ -> codeChamp;
					// bind the field to the current operation
					$champ -> initContenu();
				}
				$ret .= '<li>' . $champ -> txtModifier() . '</li>';
			}
			$ret .= '</ul>';
			$opEnCours -> setValeur('@Principal',null);
			$_SESSION['principal'][$opEnCours -> reference] = $opEnCours;
		}
		$ret = new SG_HTML($ret);
		return $ret;
	}
	//1.2 ajout : permet de se passer de gérer la compatibilité avec .@Principal dans les anciennes versions
	static function Principal() {
		$ret = SG_Navigation::OperationEnCours() -> Principal();
		return $ret;
	}
	// 1.3.0 vient de SG_Texte
	// normalise le nom en enlevant les caractères hors nomes et en compactant (restent lettres chiffres et _
	static function Normaliser($pTexte = '') {
		$texte = new SG_Texte($pTexte);
		$orig = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ &~#{}()[]|`^@=°¨£¤%+-?!,;.:§/$µ*\\\'';
		$dest = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby___________________________________';
		$nomNormalise = strtr(strtolower(utf8_decode($texte -> texte)), utf8_decode($orig), $dest);
		// Si commence par un chiffre : ajout un préfixe
		$p = substr($nomNormalise, 0, 1);
		if (($p >= '0') and ($p <= '9')) {
			$nomNormalise = 'sg_' . $nomNormalise;
		}
		return $nomNormalise;
	}
	/* 1.3.0 ajout
	* Retourne un @Nombre entier au hasard entre 1 et $pMax (par défaut 32768 = 2^15)
	*/
	static function AuHasard($pMax = 32768) {
		$max = $pMax;
		if(getTypeSG($max) === '@Formule') {
			$max = $max -> calculer();
		}
		if (getTypeSG($max) === '@Nombre') {
			$max = $max -> value;
		}
		$max = intval($max);
		if ($max < 1) {
			$max = 1;
		}
		return rand(1, $max);
	}
	/** 1.3.0 ajout
	* Branchement vers une autre étape de l'opération
	* @param SG_Texte ou SG_Formule donnant un SG_Texte : code de l'étape (vide = début)
	*/
	static function ContinuerA($pEtapeOperation = '') {
		$etape = new SG_Texte($pEtapeOperation);
		$_SESSION['page']['etape_prochaine'] = $etape -> texte;
		return '';
	}
	/** 1.3.1 ajout
	* @return (SG_Texte) vide
	*/
	static function Titre() {
		return new SG_Texte('');
	}
	/** 1.3.1 ajout
	* @return (SG_Texte) vide
	*/
	static function Egale($pQuelqueChose) {
		if(getTypeSG($pQuelqueChose) === self::TYPESG) {
			$ret = new SG_VraiFaux(true);
		} else {
			$ret = new SG_VraiFaux(false);
		}
		return $ret;
	}
	/** 1.3.1 ajout
	* @return (@VraiFaux) L'objet est défini et n'est pas @Erreur
	**/
	static function EstDefini($pQuelqueChose = null) {
		$r = $pQuelqueChose;
		if(getTypeSG($pQuelqueChose) === '@Formule') {
			$r = $pQuelqueChose -> calculer();
		}	
		if(is_null($r) or getTypeSG($r) === '@Erreur') {
			$ret = new SG_VraiFaux(false);
		} else {
			$ret = new SG_VraiFaux(true);
		}
		return $ret;
	}
	/** 1.3.1 ajout
	* Initialise la connexion à un site Internet
	* @param (@Texte) code du site à ouvrir
	* @return (@SiteInternet) le site
	* @formula Chercher("@SiteInternet","code du site")
	**/
	static function SiteInternet($pCodeSite = '', $pRefresh = false) {
		$codeSite = SG_Texte::getTexte($pCodeSite);
		$refresh = SG_VraiFaux::getBooleen($pRefresh);
		if($refresh or !isset($_SESSION['page']['sites'][$codeSite])) {
			$_SESSION['page']['sites'][$codeSite] = self::Chercher('@SiteInternet',$codeSite) -> Premier();
		}
		return $_SESSION['page']['sites'][$codeSite];
	}
	/** 1.3.1 ajout
	*/
	static function Concatener($pTexte) {
		$ret = new SG_Texte('');
		$ret = $ret -> Concatener($pTexte);
		return $ret;
	}
	/** 1.3.2 ajout
	*/
	static function JSON($pTexte) {
		return new SG_Texte('');
	}
	/** 1.3.4 ajout
	* pour tous les objets : false sauf SG_Erreur et dérivés
	**/
	function estErreur() {
		return false;
	}
	/** 1.3.4 ajout
	*/ 
	function EstUn($pType = '') {
		return new SG_VraiFaux(SG_Texte::getTexte($pType) === self::TYPESG);
	}
	/** 2.0 ajout
	* Conditionne l'exécution de formule au résultat d'une autre
	*
	* @param indéfini $pCondition Condition testée
	* @param SG_Formule $pValeurSiVrai formule à exécuter si condition vraie (plusieurs possibles)
	* @param SG_Nombre : nombre maximum d'exécutions (par défaut 10)
	* @return indéfini : la dernière valeur calculée (ou erreur)
	*/
	static function TantQue($pCondition = '', $pValeurSiVrai = null, $pNbMax = 10) {
		$ret = null;
		$resultatCondition = new SG_VraiFaux($pCondition);
		if($resultatCondition -> valeur === SG_VraiFaux::VRAIFAUX_VRAI) {
			$max = new SG_Nombre($pNbMax);
			if(getTypeSG($max) === '@Erreur') {
				$ret = $max;
			} elseif(getTypeSG($max) !== '@Nombre') {
				$ret = new SG_Erreur('0107', getTypeSG($max));
			} else {
				for ($i = 1; $i <= $max -> valeur ; $i++) {
					if (getTypeSG($pValeurSiVrai) === '@Formule') {
						$ret = $pValeurSiVrai -> calculer();
					}
				}
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* Fait rester sur la même étape au lieu de passer à l'étape suivante (sauf si on utilise un bouton)
	* @param : code de l'étape suivante
	**/
	static function EtapeSuivante($pEtape = '') {
		$etape = SG_Texte::getTexte($pEtape);
		$_SESSION['page']['etape_prochaine'] = $etape;
		return new SG_Texte($etape);
	}
	/** 2.1 ajout : cette méthode est exécutée directement dans le compilateur
	* Fournit le code de l'étape en-cours
	* @param : ce paramètre n'est utilisé que par le compilateur
	**/
	static function EtapeEnCours($etape = '1') {
		return new SG_Texte($etape);
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Rien_trait;
}
?>
