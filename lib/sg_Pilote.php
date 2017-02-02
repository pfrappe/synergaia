<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Pilote : Classe de pilotage du traitement
* Classe dont les fonctions sont statiques
*/
class SG_Pilote {
	// Type SynerGaia de l'objet
	const TYPESG = '@Pilote';
	public $typeSG = self::TYPESG;
	
	function __contruct() {
	}
	/** 2.1 récup sgget après logout ; 2.3 restructurer ; récup erreur sur assumerPagePrecedente
	* 1.3.1 ; 1.3.2 reforçage du thème en cours ; chrono ; test install ; 1.3.3 nettoieSession ; debug
	* Ligne principale du traitement entre l'envoi de l'url par l'utilisateur et le retour sur son navigateur ($page)
	**/
	static function Traiter() {
		$ret = array();
		if (isset($_GET[SG_Navigation::URL_LOGOUT])) {
			// logout : sortie immédiate
			SG_Connexion::Deconnexion();
			$page = SG_Navigation::pageLogout();
		} else {
			// test si changement d'application et l'initialise
			SG_Connexion::initApplication();
			$page = '';
			// Test si l'installation a déjà été faite
			if (SG_Installation::installationNecessaire()) {
				$page = SG_Installation::Installer();
			} else {
				// initialise les composantes du thème graphique (standard ou mobile)	
				SG_ThemeGraphique::initThemeGraphique(SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_SCREEN));
				// test de l'utilisateur
				$erreur = self::verifierConnexion();
				if($erreur !== '') {
					$paramCode = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_CODE);
					// si erreur de connexion, envoyer une page de login selon le mode d'accès
					if ($paramCode === '') {
						// navigation standard
						$page = SG_Navigation::pageLogin($erreur);
					} else {
						// exécution code sgget après logout
						$ret['operation'] = SG_Navigation::pageLogin($erreur);
						$ret['erreurs'] = $erreur;
						$page = json_encode($ret);
					}						
				} else {
					// prise en compte des infos d'une page précédente
					$operation = self::assumerPagePrecedente();
					if (get_class($operation) === 'SG_Erreur') {
						$ret['operation'] = '';
						$ret['erreurs'] = $operation -> toHTML();
						$page = json_encode($ret);
					} else {
						// A PARTIR D'ICI, ON TRAITE LA DEMANDE (CODE SG_GET, NOUVELLE OPÉRATION OU NOUVELLE ÉTAPE)
						// est-ce une fonction sg_get ? Dans ce cas $page contient le seul résultat de l'opération demandée sous forme json
						$page = self::traiterAppelSGGet($operation);
						if (is_null($page)) {
							// pas sg_Get : on continue : le retour sera une page complète HTML
							self::nettoieSession();
							self::initialiserNouvelleEtape();
							
							if (SG_Navigation::estMobile()) {
								$page .= self::traiterMobile($operation);
							} else {
								$paramTheme = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_THEME);
								if ($paramTheme !== '') {
									// affichage d'un thème
									$res = self::demarrerUnNouveauTheme($paramTheme);
									$page.= self::afficherLeHaut();
								} else {
									// y a-t-il une opération demandée ?
									$operation = self::preparerOperationDemandee($operation);
									$page.= self::afficherLeHaut($operation);
									if (is_null($operation)) {
										$res = self::afficherAccueil();
									} else {
										// On l'exécute si elle est correcte
										$res = self::traiterOperationDemandee($operation);
									}
								}
								$body = SG_Navigation::Body($res, $operation);
								$page.= '<div id="menuetcorps">' . $body . '</div>';
								$page.= SG_Navigation::finBody();
								// Infos de debug
								$page.= '<div id="pied" class="noprint">' . SG_Navigation::Pied() . '</div>';
							} // fin !estMobile
						} // fin page nulle
					} // fin test erreur
				} // fin logging
			}// fin install
		}
		echo $page;
	}
	/** 1.3.3 ajout ; 2.2 gauche et droite "page"
	**/
	static function traiterMobile($operation) {
		$page = '';
		$paramTheme = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_THEME);
		if ($paramTheme !== '') {
			$res = self::demarrerUnNouveauTheme($paramTheme);
			$page.= self::afficherLeHaut();
		} else {
			// y a-t-il une opération demandée ?
			$operation = self::preparerOperationDemandee($operation);
			$page.= self::afficherLeHaut($operation);
			if (is_null($operation)) {
				$res = SG_Navigation::Themes();
			} else {
				// On l'exécute si elle est correcte
				$res = self::traiterOperationDemandee($operation);
			}
		}
		$page.= '<div id="gauche" data-role="page" style="display:none;"></div>';
		$page.= '<div id="operation" data-role="page" >' . SG_Navigation::Body($res, $operation) . '</div>';
		$page.= '<div id="droite" data-role="page" style="display:none;"></div>';
		$page.= SG_Navigation::finBody();
		return $page;
	}
	// Vérifier si identification faite ou faisable
	static function verifierConnexion() {
		$erreurLogin = '';
		if (isset($_GET[SG_Navigation::URL_LOGIN])) { // demande de login
			SG_Connexion::Deconnexion();
			if ($_GET[SG_Navigation::URL_LOGIN] === '') {
				$erreurLogin = SG_Libelle::getLibelle('0083'); // 'présentez-vous'
			} elseif ($_GET[SG_Navigation::URL_LOGIN] === 'u') {
				$username = '';
				$psw = '';
				if (isset($_POST['username'])) {
					$erreurLogin = SG_Libelle::getLibelle('0082'); // 'id ou psw incorrect'
					$username = $_POST['username'];
					if (SG_Connexion::AnonymePossible() and $username === '') {
						$username = SG_Connexion::ANONYME;
					}
					if ($username !== '') {					
						$utilisateur = SG_Connexion::Utilisateur($username);
						if (isset($_POST['password'])) {
							$psw = $_POST['password'];
						}
						if (SG_Connexion::Connexion($utilisateur, $psw) -> estVrai()) {
							$erreurLogin = '';
						}
					}
				}
			} else {
				$erreurLogin = SG_Libelle::getLibelle('0085'); // code != u
			}
		} else {
			$id = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_IDENTIFIANT);
			if ($id === '') {
				$id = SG_SynerGaia::IdentifiantConnexion();
			}
			$utilisateur = SG_Connexion::Utilisateur($id);
			if (!(SG_Connexion::EstConnecte($utilisateur) -> estVrai())) {
				if (!SG_Connexion::Connexion($utilisateur) -> estVrai()) {
					$erreurLogin = SG_Libelle::getLibelle('0086'); // 'user déconnecté'
				}
			}
		}
		return $erreurLogin;
	}
	/** 2.1 ope dans $_SESSION, sous-classe SG_Operation, formule immédiate ; 2.3 $_SESSION['operations'] ; test erreur de compil
	* 1.1 Ajout (vient de index.php) ; 1.3.0 ; 1.3.2 déplacée de SG_Navigation ; 1.3.4 '0091' ; 
	* prépare une opération à partir de l'url
	* ordre de priorité : f=formule, m=modèle, o=opération
	* @param une opération actuelle
	*/
	static function preparerOperationDemandee($pOperation) {
		$operation = null;
		// est-ce une formule immédiate ?	
		$paramFormule = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_FORMULE);
		if ($paramFormule !== '') {
			if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
				unset($_SESSION['parms']);
				$compil = new SG_Compilateur();
				$operation = $compil -> Traduire($paramFormule);
				if (!is_object($operation) or get_class($operation) !== 'SG_Erreur') {
					$nom = sha1($paramFormule);
					$operation = $compil -> compilerOperation($nom, $paramFormule, $compil -> php, 'OP_');
					if (!is_object($operation) or get_class($operation) !== 'SG_Erreur') {
						$classe = 'OP_' . $nom;
						$operation = new $classe();
						$operation -> php = 'oui';
					}
				}
			} else {
				$operation = new SG_Erreur('0017', $_SESSION['@Moi'] -> identifiant);
			}
		} else {
			// est-ce le lancement d'un modèle ?
			$paramModele = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_MODELEOPERATION);
			if ($paramModele !== '') {
				$connu = SG_Navigation::ModeleOperationDisponible($paramModele);
				if ($connu -> estVrai()) {
					unset($_SESSION['parms']);
					$operation = SG_Operation::CreerDuModele($paramModele);
					$_SESSION['operations'][$operation -> reference] = $operation;
				} elseif ($connu -> valeur === SG_VraiFaux::VRAIFAUX_INDEF) {
					$operation = new SG_Erreur('0091', $paramModele);
				} else {
					$operation = new SG_Erreur('0015', $paramModele . ' : ' . $_SESSION['@Moi'] -> identifiant);
				}
			} else {
				// est-ce une opération existante ?
				$paramOperation = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_OPERATION);
				if ($paramOperation !== '') {
					// est-ce qu'on en a bien une en paramètre ?
					if (get_class($pOperation) === 'SG_Operation' or is_subclass_of($pOperation, 'SG_Operation')) {
						// est-ce la bonne ?
						if ($pOperation -> reference === $paramOperation) {
							$operation = $pOperation;
						} else {
							// sinon on la recherche
							$operation = SG_Navigation::obtenirOperation($paramOperation);
							if ($operation === null) {
								$operation = new SG_Erreur('0016', $paramOperation);
							} else {
								$paramEtape = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_ETAPE);
								$operation -> setValeur('@Etape', $paramEtape);
							}
						}
					}
				}
			}
		}
		return $operation;
	}
	/** 1.3.1
	* Traitement de la fonction sg_get demandée
	* @param opération en cours
	* @return null si pas sg_get ; sinon résultat à renvoyer tel quel
	*/
	static function traiterAppelSGGet($pOperation) {
		$ret = null;
		$paramCode = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_CODE);
		if ($paramCode !== '') {
			$paramRecalcul = (SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_RECALCUL) !== '');
			$contenu = SG_Navigation::executerCodeSGGet($paramCode, true, $pOperation);
			if (getTypeSG($contenu) === '@Erreur') {
				$contenu = $contenu -> toHTML();
			}
			$ret = $contenu;
		}
		return $ret;
	}
	/** 2.3 test erreur
	* ASSUMER OU TERMINER LA PAGE PRÉCÉDENTE REÇUE
	* @return : SG_Operation ou SG_Erreur
	**/
	static function assumerPagePrecedente() {
		$operation = null;
		$paramOperation = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_OPERATION);
		if ($paramOperation !== '') {
			// Traitement des paramètres POST (mise à jour des données des documents)
			$operation = SG_Navigation::traitementParametres_HTTP_POST($paramOperation);
			if (get_class($operation) !== 'SG_Erreur') {
				// Traitement des paramètres FILES (fichiers joints)
				$operation = SG_Navigation::traitementParametres_HTTP_FILES($operation);
			}
		}
		return $operation;
	}
	/** 1.3.3 textes ; 2.1 sup ['nopage']
	* initialisations au début du traitement d'une nouvelle étape
	**/
	static function initialiserNouvelleEtape() {
		//raz des contenus de la page
		$_SESSION['libs'] = array(); // bibliothèques nécessaires dans le header
		$_SESSION['page']['ref_document'] = '';
		$_SESSION['page']['aide'] = '';
		$_SESSION['page']['textes'] = array();
		$_SESSION['page']['entete'] = '';
		$_SESSION['page']['erreurs'] = '';
		$_SESSION['script'] = array();
		if(!isset($_SESSION['page']['theme'])) {
			$_SESSION['page']['theme'] = '';
		}
	}
	//1.3.3 : SG_Navigation::composerMenuDefaut($theme);
	// c'est un démarrage de thème ; 2.1 -> code
	static function demarrerUnNouveauTheme($paramTheme) {
		if (is_string($paramTheme)) {
			$theme = new SG_Theme($paramTheme);
			$_SESSION['page']['theme'] = $paramTheme;
		} else {
			$theme = $paramTheme;
			$_SESSION['page']['theme'] = $theme -> Code() -> texte;
		}
		SG_Navigation::composerThemesDefaut($theme);
		SG_Navigation::declarerOperationEnCours(null);
		$aide = $theme -> Aide();
		if (is_object($aide)) {
			$aide = $aide -> toString();
		}
		if (SG_ThemeGraphique::ThemeGraphique() === 'mobile') {
			$contenuPrincipal = SG_Navigation::MenuTheme($theme, true);
		} else {
			$contenuPrincipal = '<div id="themeaide">' . $aide . '</div>';
		}
		$_SESSION['page']['entete'] = $theme -> Titre();
		return $contenuPrincipal;
	}
	
	/** 2.1 save en fin, test erreur, test stdClass
	*  traiter une opération : 
	* @param $operation : opération en cours
	**/
	static function traiterOperationDemandee($operation) {
		$boutons = '';
		$entete = '';
		$url = '';
		$bandeauAide = '';
		$erreurs = '';
		if (getTypeSG($operation) === '@Erreur') {
			$entete = 'Erreur';
			$ret = $operation -> toHTML();
		} else {
			$r = SG_Navigation::declarerOperationEnCours($operation);
			// sur quel objet principal ?
			$r = SG_Navigation::setPrincipal($operation);
			// calcul de l'étape et des boutons associés
			$paramEtape = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_ETAPE);
			$paramBouton = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_BOUTON);
			// passage des paramètres d'url (p1, p2, p3)
			for ($i = 1; $i <= 3; $i++) {
				$operation -> proprietes['$' . $i] = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM . $i);
			}
			// exécution (1.3.0 boucle while sur étapes vides (étiquette |> ))
			if (get_class($operation) === 'stdClass') {
				$ret = new SG_Erreur('0167');
			} else {
				$ret = $operation -> Traiter($paramEtape, $paramBouton);
				$n = 0;
				while ($ret === '' and $_SESSION['page']['etape_prochaine'] !== '' and $_SESSION['page']['etape_prochaine'] !== $paramEtape) {
					$n++;
					if ($n > 5) break; // pour éviter les boucles... TODO réécrire pour enlever ce test
					$ret = $operation -> Traiter($_SESSION['page']['etape_prochaine'], '');
				}
				// MISE EN FORMULAIRE
				// erreurs éventuelles
				if (sizeof($operation -> erreurs) >= 1) {
					$erreurs = 'Il y a eu au moins une erreur :';
					foreach ($operation -> erreurs as $erreur) {
						if (is_string($erreur)) {						
							$erreurs .= '<li>'. $erreur . '</li>';
						} else {
							$erreurs .= '<li>'. $erreur -> getMessage() . '</li>';
						}
					}
				}
				// entête et aide
				if (SG_ThemeGraphique::ThemeGraphique() !== 'mobilex') {
					$entete = $operation -> genererEntete();
				}
				$bandeauAide = SG_Navigation::pageAide($operation);
				// bouton et divers
				$url = $operation -> url();
				$boutons = $operation -> listeBoutons(true);
			}
		}
		$_SESSION['page']['boutons'] = $boutons;
		$_SESSION['page']['entete'] = $entete;
		$_SESSION['page']['url'] = $url;
		$_SESSION['page']['aide'] = $bandeauAide;
		$_SESSION['page']['erreurs'] = $erreurs;
		return $ret;
	}
		
	static function afficherAccueil() {
		// on a aucun paramètre : on présente les menus des thèmes. 
		//Si un thème 'Accueil' existe, on présente celui-là
		SG_Navigation::declarerOperationEnCours(null);
		$entete = $_SESSION['page']['application'] ;
		if (SG_ThemeGraphique::ThemeGraphique() === 'mobile') {
			$contenuPrincipal = SG_Navigation::pageAccueil();
		} else {
			$theme = SG_Rien::Chercher('@Theme','Accueil');
			if ($theme -> Compter() -> toInteger() !== 0) {
				$theme = $theme -> Premier();
				self::demarrerUnNouveauTheme($theme);
				$contenuPrincipal = $theme -> Aide();
			} else {
				$contenuPrincipal = '<h1>Bienvenue sur SynerGaïa !</h1><br><br><br>Cliquez sur un des thèmes ci-dessus pour commencer.';
			}
		}
		return array($contenuPrincipal);
	}
	/** 1.3.2 afficher le haut de la page ; 2.3 retirer test mobile
	*/
	static function afficherLeHaut($operation = null) {
		// header : css et script
		$page = SG_Navigation::Header($_SESSION['page']['application']);
		//if (SG_ThemeGraphique::ThemeGraphique() !== 'mobilex') {
		// forçage du thème qui a pu changer dans $_SESSION en passant par ailleurs dans d'autres fenêtres
		if(! is_null($operation)) {
			$theme = $operation -> getValeurPropriete('@Theme');
			if(getTypeSG($theme) === '@Theme' and $theme -> Code() -> texte !== $_SESSION['page']['theme']) {
				SG_Navigation::composerThemesDefaut($theme);
			//1.3.3	SG_Navigation::composerMenuDefaut($theme);
			}
		}
		if (!isset($_SESSION['page']['themes']) or $_SESSION['page']['themes'] === '') {
			SG_Navigation::composerThemesDefaut(SG_Rien::ThemeEnCours());
		}
		$page.= SG_Navigation::LeHaut();
		//}
		return $page;
	}
	/** 1.3.2 déplacé de socle.php
	* benchmark permet de mesurer des temps d'exécution et un nombre de passage pour faire du benchmark
	* 
	* @param string nom du compteur
	* @param boolean true : commencer la mesure et compter +1 ; false arrêter la mesure.
	*/
	static function Benchmark($pCompteur = 'cpt1', $pFlag = true) {
		if($pFlag) {
			if(!isset($_SESSION['benchmark'][$pCompteur])) {
				$_SESSION['benchmark'][$pCompteur]=array(microtime(true),0,1,-1,-1);
			} else {
				$tmp=$_SESSION['benchmark'][$pCompteur];
				// si on est déjà en train de compter sur ce compteur, on ne fait rien
				if ($tmp[0] == 0) {
					$tmp[0] = microtime(true);
					$tmp[2] += 1;
					$_SESSION['benchmark'][$pCompteur]=$tmp;
				}
			}
		} else {
			if(isset($_SESSION['benchmark'][$pCompteur])) {
				$tmp=$_SESSION['benchmark'][$pCompteur];
				// si on est déjà arrêté sur ce compteur, on ne fait rien
				if ($tmp[0] !== 0) {
					$delta = microtime(true) - $tmp[0];
					$tmp[1] += $delta;
					$tmp[0] = 0;
					if ($tmp[3] < 0) {
						$tmp[3] = $delta;
					} elseif ($tmp[3] > $delta) {
						$tmp[3] = $delta;
					}
					if ($tmp[4] < 0) {
						$tmp[4] = $delta;
					} elseif ($tmp[4] < $delta) {
						$tmp[4] = $delta;
					}
					$_SESSION['benchmark'][$pCompteur]=$tmp;
				}
			}
		}
	}
	/** 1.3.2 ajout
	* Chrono permet de mesurer des temps de passage (attention de ne pas mettre dans une boucle trop importante !)
	* 
	* @param string nom du compteur
	*/
	static function Chrono($pEtape = null) {
		if(is_null($pEtape)) {
			$_SESSION['chrono'][] = microtime(true);
		} else {
			$_SESSION['chrono'][$pEtape]= microtime(true);
		}
	}
	/** 1.3.3 ajout ; 2.1 classe incomplète ; 2.3 ne s'occupe que de l'application en cours ; $_SESSION['operations']
	* nettoie $_SESSION des erreurs et des vieilles opérations
	**/
	static function nettoieSession() {
		$appli = SG_Connexion::Application();
		if (isset($_SESSION['operations'])) {
			foreach($_SESSION['operations'] as $key => $element) {
				$sup = true;
				if (getTypeSG($element) === '@Erreur') { // si est une erreur
					tracer($key, $element);
					unset($_SESSION['operations'][$key]);
				} elseif (get_class($element) === "__PHP_Incomplete_Class") { // ou la classe est incomplète
					unset($_SESSION['operations'][$key]);
				} elseif (!method_exists($element, 'estInactive')) { // ou ce n'est pas une opération
					unset($_SESSION['operations'][$key]);
				} else {
					if ($element -> appli === $appli and $element -> estInactive()) { // ou elle est inactive et concerne l'application
						unset($_SESSION['operations'][$key]);
					} else { // sinon ne pas supprimer
						$sup = false;
					}
				}
				// supprime également le principal
				if ($sup and isset($_SESSION['principal'][$key])) {
					unset($_SESSION['principal'][$key]);
				}
			}
		}
	}
}
?>
