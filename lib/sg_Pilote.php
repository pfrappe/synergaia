<?php
/** SYNERGAIA ce fichier contient le pilote général du fonctionnement de SynerGaia */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Pilote : Classe de pilotage global du traitement.
 * Cette classe est entièrement statique
 * @since 1.3.1
 * @version 2.6 : prise en compte de la réintégration de SG_Installation dans SG_SynerGaia
 */
class SG_Pilote {
	/** string Type SynerGaia de l'objet '@Pilote' */
	const TYPESG = '@Pilote';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/**
	 * Ligne principale du traitement entre l'envoi de l'url par l'utilisateur et le retour sur son navigateur ($page)
	 * @since 1.3.1
	 * @version 2.6 : réintégration de SG_Installation dans SG_SynerGaia
	 **/
	static function Traiter() {
		$opencours = null;
		$ret = array();
		self::controleSession();
		if (isset($_GET[SG_Navigation::URL_LOGOUT])) {
			// logout : sortie immédiate de toutes les applications SynerGaïa ouvertes sur le navigateur
			SG_Connexion::Deconnexion();
			$page = SG_Navigation::pageLogout();
		} else {
			// test si changement d'application et l'initialise
			$ret = SG_Connexion::initApplication();
			$page = '';
			// Test si l'installation a déjà été faite
			if (SG_Synergaia::installationNecessaire()) {
				$page = SG_Synergaia::Installer();
			} else {
				// initialise les composantes du thème graphique (standard ou mobile)	
				SG_ThemeGraphique::initThemeGraphique(SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_SCREEN));
				// test de l'utilisateur
				$erreur = self::verifierConnexion();
				if($erreur !== '') {
					// si erreur de connexion, envoyer une page de login
					$paramCode = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_CODE);
					// selon le mode d'accès
					if ($paramCode === '') {
						// navigation standard (rien que la form)
						$page = SG_Navigation::pageLogin($erreur);
					} else {
						// exécution code sgget après logout
						$ret['operation'] = SG_Navigation::pageLogin($erreur, false);
						$ret['erreurs'] = $erreur;
						$page = json_encode($ret);
					}
				} else {
					// prise en compte des infos d'une page précédente
					$operation = self::assumerPagePrecedente();
					// y a-t-il eu des erreurs dans l'étape précédente ?
					if ($operation instanceof SG_Erreur) {
						$page = json_encode(array('erreurs' => $operation -> toHTML() -> texte), true);
					} elseif (is_object($operation) and sizeof($operation -> erreurs) > 0) {
						$page = json_encode(SG_Navigation::erreursOperation($operation), false);
						$operation -> erreurs = array();
					} else {
						// A PARTIR D'ICI, ON TRAITE LA DEMANDE (CODE SG_GET, NOUVELLE OPÉRATION OU NOUVELLE ÉTAPE)
						self::initialiserNouvelleEtape();
						// est-ce une fonction sg_get ? Dans ce cas $page contient le seul résultat de l'opération demandée sous forme json
						$page = self::traiterAppelSGGet($operation);
						if (is_null($page)) { // pas sg_Get : on continue : le retour sera une page complète HTML
							self::nettoieSession($operation);
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
										if (sizeof($operation -> erreurs) === 0) {
											// On l'exécute si elle est correcte
											$res = self::traiterOperationDemandee($operation);
										} else {
											if (isset($operation -> erreurs[0])) {
												$res = $operation -> erreurs[0];
											} else {
												$res = new SG_Erreur('0190');
											}
										}
									}
								}
								// mise en forme du résultat
								$page.= SG_Navigation::Body($res, $operation);
								$page.= SG_Navigation::finBody();
							} // fin !estMobile
						} // fin page nulle (pas sg-get)
						$opencours = SG_Pilote::OperationEnCours(); // 2.4
						if ($opencours !== null) {
							if ($opencours instanceof SG_Erreur) {
								$page = $opencours -> getMessage();
							} else {
								$r = $opencours -> Enregistrer();
							}
						}
					} // fin test erreur
				} // fin logging
			}// fin install
		}
		echo $page;
		if (is_object($opencours) and !$opencours instanceof SG_Erreur) {
			$opencours -> reduirePrincipal();
		}
	}

	/**
	 * Retourne le document d'opération dont le code est passé
	 * On cherche dans les opérations actives puis sur disque
	 * 
	 * @version 2.5 repris de SG_Navigation
	 * @param $pOperation string : référence de l'opération
	 * @formula : retour=@Operation(code);	@Si(retour.@EstUn("@Erreur");@ModeleOperation(code);retour)
	 **/
	static function obtenirOperation($pOperation = null) {
		$operation = null;
		if ($pOperation !== null) {
			$type = getTypeSG($pOperation);
			if ($pOperation instanceof SG_Operation) {
				$operation = $pOperation;
			} elseif (is_string($pOperation)) {
				if (isset($_SESSION['operations'][$pOperation])) {
					// opération active ?
					$operation = $_SESSION['operations'][$pOperation];
				} else {
					// Cherche l'opération en cours sauvegardée
					$operation = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(SG_Operation::CODEBASE, '@Operation', $pOperation);
					if (!$operation instanceof SG_Operation) {
						$operation = new SG_Operation();
						$operation -> mettreErreur('0168', $pOperation);
					}
					SG_Pilote::declarerOperationActive($operation);
				}
			} elseif ($type === '@ModeleOperation') {
				if (SG_Navigation::ModeleOperationDisponible($pOperation) -> estVrai()) {
					$operation = SG_Operation::CreerDuModele($pOperation);
					SG_Pilote::declarerOperationActive($operation);
				} else {
					// Cette opération n'est pas autorisée ou n'existe pas
					$operation = new SG_Operation();
					SG_Operation::STOP('0014', $pOperation . ' : ' . $_Session['@Moi'] -> identifiant);
				}
			}
		}
		return $operation;
	}

	/**
	 * Cas du traitement depuis un mobile
	 * @since 1.3.3 ajout
	 * @version 2.2 gauche et droite "page"
	 * @param string|SG_Operation code ou opération en cours
	 * @return string HTML de la page calculée
	 */
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
		$page.= '<div id="gauche" class="sg-box"></div>';
		$page.= '<div id="operation" class="sg-box sg-ope-contenu" >' . SG_Navigation::Body($res, $operation) . '</div>';
		$page.= '<div id="droite" class="sg-box"></div>';
		$page.= '<div id="aide" class="sg-box sg-aide noprint"></div>';
		$page.= SG_Navigation::finBody();
		return $page;
	}

	/**
	 * Vérifier si identification de l'utilisateur est faite ou faisable
	 * @return string|SG_Erreur "" si ok, sinon erreur
	 */
	static function verifierConnexion() {
		$ret = '';
		if (isset($_GET[SG_Navigation::URL_LOGIN])) { // demande de login
			SG_Connexion::Deconnexion();
			if ($_GET[SG_Navigation::URL_LOGIN] === '') {
				$ret = SG_Libelle::getLibelle('0083'); // 'présentez-vous'
			} elseif ($_GET[SG_Navigation::URL_LOGIN] === 'u') {
				$username = '';
				$psw = '';
				if (isset($_POST['username'])) {
					$ret = SG_Libelle::getLibelle('0082'); // 'id ou psw incorrect'
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
							$ret = '';
						}
					}
				}
			} else {
				$ret = SG_Libelle::getLibelle('0085'); // code != u
			}
		} else {
			// on est déjà dans une session
			$id = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_IDENTIFIANT);
			if ($id === '') {
				$id = SG_SynerGaia::IdentifiantConnexion();
			}
			$utilisateur = SG_Connexion::Utilisateur($id);
			if (!(SG_Connexion::EstConnecte($utilisateur) -> estVrai())) {
				if (!SG_Connexion::Connexion($utilisateur) -> estVrai()) {
					$ret = SG_Libelle::getLibelle('0086'); // 'user déconnecté'
				}
			} 
		}
		// dans certains cas on a perdu des bouts de session
		if ($ret === '' and ! isset($_SESSION['@Moi'])){
			$ret = new SG_Erreur('0252');
		}
		return $ret;
	}

	/**
	 * Renvoie l'opération en cours ou en crée une vide si elle n'est pas répertoriée
	 * 
	 * @since 2.5 dépacé de SG_Navigation
	 * @version 2.6 cherche dans opérations actives ; erreur 0292
	 * @return SG_Operation opération en cours ou nouvelle
	 */
	static function OperationEnCours() {
		if (!isset($GLOBALS['operationencours'])) {
			$ret = SG_Operation::Creer('');
			self::declarerOperationEnCours($ret);
		} elseif (isset($_SESSION['operations'][$GLOBALS['operationencours']])) {
			$ret = $_SESSION['operations'][$GLOBALS['operationencours']];
		} else {
			$ret = new SG_Erreur('0292');
		}
		return $ret;
	}

	/**
	 * prépare une opération à partir de l'url
	 * ordre de priorité : f=formule, m=modèle, o=opération
	 * 
	 * @since 1.1 Ajout (vient de index.php puis déplacée de SG_Navigation)
	 * @version 2.4 récup p1,p2, p3
	 * @version 2.7 màj @Titre au bon endroit...
	 * @param string|SG_Operation $pOperation une opération actuelle
	 * @return null|SG_Operation
	 */
	static function preparerOperationDemandee($pOperation) {
		$operation = null;
		// est-ce une formule immédiate ?	
		$paramFormule = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_FORMULE);
		if ($paramFormule !== '') {
			if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
				unset($_SESSION['parms']);
				$nom = sha1($paramFormule);
				$classe = 'OP_' . $nom;
				if (class_exists($classe)) {
					$operation = new $classe();
					$operation -> php = 'oui';
				} else {
					$compil = new SG_Compilateur();
					$compil -> titre = 'Formule immédiate : ';
					$trad = $compil -> Traduire($paramFormule);
					if (!is_object($trad) or ! $trad instanceof SG_Erreur) {
						$operation = $compil -> compilerOperation($nom, $paramFormule, $compil -> php, 'OP_');
						if (!is_object($operation) or get_class($operation) !== 'SG_Erreur') {
							$classe = 'OP_' . $nom;
							$operation = new $classe();
							$operation -> php = 'oui';
							$operation -> MettreValeur('@Titre', $paramFormule);
						}
					} else {
						$operation = new SG_Operation();
						$operation -> erreurs[] = $compil -> erreur;
					}
				}
				$operation -> MettreValeur('@Titre', $paramFormule);
			} else {
				$operation = new SG_Operation();
				$operation -> mettreErreur('0017', $_SESSION['@Moi'] -> identifiant);
			}
		} else {
			// est-ce le lancement d'un modèle ?
			$paramModele = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_MODELEOPERATION);
			if ($paramModele !== '') {
				$connu = SG_Navigation::ModeleOperationDisponible($paramModele);
				if ($connu -> estVrai()) {
					unset($_SESSION['parms']);
					$operation = SG_Operation::CreerDuModele($paramModele);
					SG_Pilote::declarerOperationActive($operation);
				} elseif ($connu -> valeur === SG_VraiFaux::VRAIFAUX_INDEF) {
					$operation = new SG_Operation();
					$operation -> mettreErreur('0091', $paramModele);
				} else {
					$operation = new SG_Operation();
					$operation -> mettreErreur('0015', $paramModele . ' : ' . $_SESSION['@Moi'] -> identifiant);
				}
			} else {
				// est-ce une opération existante ?
				$paramOperation = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_OPERATION);
				if ($paramOperation !== '') {
					// est-ce qu'on en a bien une en paramètre ?
					if (SG_Operation::isOperation($pOperation)) {
						// est-ce la bonne ?
						if ($pOperation -> reference === $paramOperation) {
							$operation = $pOperation;
						} else {
							// sinon on la recherche
							$operation = self::obtenirOperation($paramOperation);
							if ($operation === null) {
								$operation = new SG_Operation();
								$operation -> mettreErreur('0016', $paramOperation);
							} else {
								$paramEtape = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_ETAPE);
								$operation -> setValeur('@Etape', $paramEtape);
							}
						}
						$operation -> erreurs = array();
					}
				}
			}
		}
		if(! is_null($operation)) {
			// récupération des paramètres d'url (p1, p2, p3)
			for ($i = 1; $i <= 3; $i++) {
				$val = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM . $i);
				if ($val !== '') {
					$operation -> proprietes['$' . $i] = new SG_Texte($val);
				}
			}
		}
		return $operation;
	}

	/**
	* Traitement de la fonction sg_get demandée
	* @since 1.3.1
	* @version 2.4 prise en compte de SG_Operation::mettreErreur
	* @version 2.6 sup paramRecalcul ; $pCode ; self::executerCodeSGGet
	* @param SG_Operation $pOperation opération en cours
	* @param string $pCode forçage du paramètre
	* @return null si pas sg_get ; sinon résultat à renvoyer tel quel dans le navigateur
	*/
	static function traiterAppelSGGet($pOperation, $pCode = '') {
		$ret = null;
		if ($pCode === '') { 
			$paramCode = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_CODE);
		} else {
			$paramCode = $pCode;
		}
		if ($paramCode !== '') {
			try {
				$contenu = self::executerCodeSGGet($paramCode, true, $pOperation);
			} catch (Exception $e) {
				$contenu = 'erreur ' . $e -> getMessage();
			}
			if (getTypeSG($contenu) === '@Erreur') {
				if (getTypeSG($pOperation) === '@Operation') {
					$pOperation -> mettreErreur($contenu);
				} else {
					$contenu = $contenu -> toHTML();
				}
			}
			$ret = $contenu;
		}
		return $ret;
	}

	/**
	 * ASSUMER OU TERMINER LA PAGE PRÉCÉDENTE REÇUE
	 * @version 2.3 test erreur
	 * @return SG_Operation|SG_Erreur
	 **/
	static function assumerPagePrecedente() {
		$operation = null;
		$paramOperation = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_OPERATION);
		if ($paramOperation !== '') {
			$operation = self::obtenirOperation($paramOperation);
			// Traitement des paramètres POST (mise à jour des données des documents)
			$operation = SG_Navigation::traitementParametres_HTTP_POST($operation);
			if (! $operation instanceof SG_Erreur) {
				// Traitement des paramètres FILES (fichiers joints)
				$operation = SG_Navigation::traitementParametres_HTTP_FILES($operation);
			}
		}
		return $operation;
	}

	/**
	 * initialisations au début du traitement d'une nouvelle étape
	 * @version 2.1 sup ['nopage']
	 **/
	static function initialiserNouvelleEtape() {
		//raz des contenus de la page
		$_SESSION['libs'] = array(); // bibliothèques nécessaires dans le header
		$_SESSION['page']['aide'] = '';
		$_SESSION['page']['entete'] = '';
		$_SESSION['script'] = array();
		if(!isset($_SESSION['page']['theme'])) {
			$_SESSION['page']['theme'] = '';
		}
		
	}

	/**
	 * c'est un démarrage de thème
	 * @version 2.1 -> code
	 * @param string|SG_Theme $paramTheme code ou theme à démarrer
	 * @return string HTML du theme
	 */
	static function demarrerUnNouveauTheme($paramTheme) {
		if (is_string($paramTheme)) {
			$theme = new SG_Theme($paramTheme);
			$_SESSION['page']['theme'] = $paramTheme;
		} else {
			$theme = $paramTheme;
			$_SESSION['page']['theme'] = $theme -> Code() -> texte;
		}
		SG_Navigation::composerThemesDefaut($theme);
		self::declarerOperationEnCours(null);
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

	/**
	 * Traiter une opération
	 * @version 2.4 pi=new SG_Texte
	 * @param string SG_Operation|SG_Erreur $operation code ou opération en cours à traiter ou erreur venant d'avant
	 **/
	static function traiterOperationDemandee($operation) {
		if (getTypeSG($operation) === '@Erreur') {
			$operation -> gravite = SG_Erreur::ERREUR_STOP;
			$ret = $operation -> toHTML();
		} else {
			$r = self::declarerOperationEnCours($operation, true);
			// calcul de l'étape et des boutons associés
			$paramEtape = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_ETAPE);
			$paramBouton = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_BOUTON);
			// on regarde s'il faut remplacer le principal par un objet précis passé en index (i=...) de l'url
			$index = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_INDEX);
			if ($index !== '') {
				// chercher l'id de l'objet puis l'objet (si c'est un @IDDoc ou cherche le document
				$ipos = strpos($index, ':');
				if ($ipos === false) {
					$id = $index;
					$key = '';
				} else {
					$id = substr($index, 0, $ipos);
					$key = substr($index, $ipos +1);
				}
				if (! isset($operation -> proprietes[$id])) {
					$o = new SG_Erreur('le principal 1 est introuvable...', $index);
				} else {
					$o = $operation -> proprietes[$id];
					if ($key !== '') {
						if (is_array($o)) {
							if (! isset($o[$key])) {
								$o = new SG_Erreur('le principal 2 est introuvable...', $index);
							} else {
								$o = $o[$key];
							}
						} elseif (getTypeSG($o) === '@Collection') {
							if (! isset($o -> elements[$key])) {
								$o = new SG_Erreur('le principal 3 est introuvable...', $index);
							} else {
								$o = $o -> elements[$key];
								if (getTypeSG($o) === '@IDDoc') {
									$o = $o -> Document();
								}
							}
						} else {
							$o = new SG_Erreur('le principal n\'est pas un conteneur...', $index);
						}
					}
				}
				$operation -> setPrincipal($o);
			}
			// exécution (1.3.0 boucle while sur étapes vides (étiquette |> ))
			if (get_class($operation) === 'stdClass') {
				$ret = new SG_Erreur('0167');
			} else {
				$ret = $operation -> Traiter($paramEtape, $paramBouton);
				$n = 0;
				// 1.3.0 boucle while sur étapes vides (étiquette |> )
				while ($ret === '' and $operation -> prochaineEtape !== '' and $operation -> prochaineEtape !== $paramEtape) {
					$n++;
					if ($n > 5) break; // pour éviter les boucles... TODO réécrire pour enlever ce test
					$ret = $operation -> Traiter($operation -> prochaineEtape, '');
				}
				// MISE EN FORMULAIRE entête, aide, url
				$_SESSION['page']['entete'] = $operation -> genererEntete();
				$_SESSION['page']['aide'] = SG_Navigation::pageAide($operation);
				$_SESSION['page']['url'] = $operation -> url();
			}
		}
		return $ret;
	}

	/**
	 * Affiche la page d'accueil s'il y en a une prévue
	 * @return array HTML de la page d'accueil
	 */
	static function afficherAccueil() {
		// on a aucun paramètre : on présente les menus des thèmes. 
		//Si un thème 'Accueil' existe, on présente celui-là
		self::declarerOperationEnCours(null);
		$entete = $_SESSION['page']['application'] ;
		if (SG_ThemeGraphique::ThemeGraphique() === 'mobile') {
			$contenuPrincipal = SG_Navigation::pageAccueil();
		} else {
			$theme = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(SG_Dictionnaire::CODEBASE, '@Theme','Accueil');
			if (getTypeSG($theme) !== '@Erreur') {
				self::demarrerUnNouveauTheme($theme);
				$contenuPrincipal = $theme -> Aide();
			} else {
				$contenuPrincipal = '<h1>Bienvenue sur SynerGaïa !</h1><br><br><br>Cliquez sur un des thèmes ci-dessus pour commencer.';
			}
		}
		return array($contenuPrincipal);
	}

	/**
	 * afficher le haut de la page
	 * @version 2.3 retirer test mobile
	 * @param SG_Operation|null $operation opération en cours
	 * @return string HTML calculé
	 */
	static function afficherLeHaut($operation = null) {
		// header : css et script
		$page = SG_Navigation::Header($_SESSION['page']['application']);
		// forçage du thème qui a pu changer dans $_SESSION en passant par ailleurs dans d'autres fenêtres
		if(! is_null($operation)) {
			$theme = $operation -> getValeurPropriete('@Theme','');
			if(getTypeSG($theme) === '@Theme' and $theme -> Code() -> texte !== $_SESSION['page']['theme']) {
				SG_Navigation::composerThemesDefaut($theme);
			//1.3.3	SG_Navigation::composerMenuDefaut($theme);
			}
		}
		if (!isset($_SESSION['page']['themes']) or $_SESSION['page']['themes'] === '') {
			SG_Navigation::composerThemesDefaut(SG_Rien::ThemeEnCours());
		}
		$page.= SG_Navigation::LeHaut();
		return $page;
	}

	/**
	 * Benchmark permet de mesurer des temps d'exécution et un nombre de passage pour faire du benchmark
	 * @since 1.3.2 déplacé de socle.php
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

	/**
	* Chrono permet de mesurer des temps de passage (attention de ne pas mettre dans une boucle trop importante !)
	* @since 1.3.2 ajout
	* @param string $pEtape nom du compteur pour l'étape
	*/
	static function Chrono($pEtape = null) {
		if(is_null($pEtape)) {
			$_SESSION['chrono'][] = microtime(true);
		} else {
			$_SESSION['chrono'][$pEtape]= microtime(true);
		}
	}

	/**
	 * nettoie $_SESSION des erreurs et des vieilles opérations
	 * @since 1.3.3 ajout
	 * @version 2.4 parm op
	 * @param SG_Operation $pOperation operation en cours
	 **/
	static function nettoieSession($pOperation) {
		$appli = SG_Connexion::Application();
		if (SG_Operation::isOperation($pOperation)) {
			$opCode = $pOperation -> reference;
		} else {
			$opCode = $pOperation;
		}
		if (isset($_SESSION['operations'])) {
			foreach($_SESSION['operations'] as $key => $element) {
				$sup = false;// par défaut, ne pas supprimer
				if (getTypeSG($element) === SG_Erreur::TYPESG) {
					// si est une erreur
					$sup = true;
				} elseif (! $element instanceof SG_Operation) {
					// ou ce n'est pas une opération
					$sup = true;
				} elseif ($element -> appli === $appli and $element -> estInactive() and $element -> reference !== $opCode) {
					// si elle concerne l'application, est inactive, et n'est pas celle en cours
					$sup = true;
				}
				// supprime opération des opérations actives
				if ($sup) {
					unset($_SESSION['operations'][$key]);
				}
			}
		}
	}

	/**
	 * Nettoie $_SESSION des valeurs non correctes (nécessaire à la suite de certains crashes)
	 * @since 2.5 ajout
	 **/
	static function controleSession() {
		$ret = true; // tout est bon
		if(! is_array($_SESSION['page'])) {
			unset($_SESSION['page']);
			$ret = false;
		}
		if(isset($_SESSION['@Moi']) and ! is_object($_SESSION['@Moi'])) {
			unset($_SESSION['@Moi']);
			$ret = false;
		}
		if(isset($_SESSION['operations']) and ! is_array($_SESSION['operations'])) {
			unset($_SESSION['operations']);
			$ret = false;
		}
		if(isset($_SESSION['bases']) and ! is_array($_SESSION['bases'])) {
			unset($_SESSION['bases']);
			$ret = false;
		}
		return $ret;
	}

	/**
	 * Exécute une fonction connu standard accessible à tous ou une formule contenu dans un bouton pour les seuls administrateurs ou un code de bouton.
	 * 
	 * @since 1.0 dans SG_Navigation
	 * @version 2.4 sub + "o"
	 * @version 2.6 reprise depuis SG_Navigation ; retour sub vers popup
	 * @param (string) $paramQuery : code de la fonction à exécuter
	 * @param (boolean) $recalcul : indicateur du forçage de recalcul pour certaines fonctions
	 * @param (string ou SG_Operation) $pOperation : opération en cours
	 * @return (string) : json des différentes parties à afficher
	 */
	static function executerCodeSGGet($paramQuery = '', $recalcul = false, $pOperation = null) {
		$contenu = '';
		$code = $paramQuery;
		switch ($code) {
			// rechercher dans la collection (@AfficherChercher) // TODO Terminer...
			case 'che' : 
				$objet = SG_Pilote::OperationEnCours() -> Principal();
				if(!getTypeSG($objet) === '@Collection') {
					$contenu = new SG_Erreur('0087', getTypeSG($objet));
				} else {
					$contenu = SG_Pilote::OperationEnCours() -> Principal();
				}
				break;
			//accès aux mots du dictionnaire
			case 'dic' :
				$mot1 = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM1);
				//$contenu = SG_Dictionnaire::ajaxMots($mot1);
				$contenu = json_encode(SG_Dictionnaire::Vocabulaire($mot1) -> elements);
				break;
			// au secours ! permet de lancer un code exécutable de secours exécuté via url : synergaia/index.php?c=hlp
			case 'hlp' :
				$contenu = SG_Cache::viderCache(); // par défaut
				break;
			// menu thème en cours
			case 'mec' :
				$contenu = SG_Navigation::MenuThemeEnCours($recalcul);
				break;
			// menu thème
			case 'men' :
				$contenu = SG_Navigation::MenuTheme(SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_THEME), $recalcul);
				break;
			// modele opération (uniquement première étape) (voir aussi 'sub')
			case 'mop' :
				$operation = self::preparerOperationDemandee($pOperation);
				if (SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_SCREEN) === 'o') {
					$r = self::declarerOperationEnCours($operation, true);
					//2.5 $r = SG_Navigation::setPrincipal($operation);
					if (getTypeSG($operation) === '@Erreur') {
						$contenu = serialize($operation);
					} else {
						$res = $operation -> Traiter('', '', '', 'f');
						ini_set('memory_limit', '512M'); // TODO Supprimer ?
						$contenu = serialize($res);
						ini_restore('memory_limit');
					}
				} else {
					$res = self::traiterOperationDemandee($operation);
					$res = SG_Navigation::elementsDuBody($res, $operation);
					$contenu = json_encode($res);
					if ($contenu === false) {
						$contenu = '{"erreurs":"<div id=\"erreurs\" class=\"sg-erreurs\"><div class="sg-erreur sg-erreur-grav-5">Erreur json : '. SG_DocumentCouchDB::jsonLastError('mop') .'</div></div>"}';
					}
				}
				break;
			// vider le cache navigation
			case 'nav' :
				$contenu = SG_Cache::viderCache("n");
				break;
			// nouvelle zone de saisie d'un fichier dans @Fichiers
			case 'nfi' :
			case 'nfs' :
				$objet = SG_Pilote::OperationEnCours() -> Principal();
				if ($code === 'nfs') {
					$fic = new SG_Fichiers($objet);
					$contenu = $fic -> getNouveauFichier($objet);
				} else {
					$fic = new SG_Fichier($objet);
					$contenu = $fic -> modifierChamp($objet -> getUUID() .'/_attachments/');
				}
				break;
			// opérations en attente
			case 'opa' :
				$contenu = SG_Navigation::OperationsEnAttente($recalcul) -> toHTML();
				break;
			// opérations suspendues
			case 'ops' :
				$contenu = SG_Navigation::OperationsSuspendues($recalcul) -> toHTML();
				break;
			case 'out' :
				$r = SG_Connexion::Deconnexion();
				$contenu = SG_Navigation::pageLogout();
				break;
			// raccourcis
			case 'rac' :
				$contenu = SG_Navigation::Raccourcis($recalcul) -> toHTML() ;
				break;
			// 2.0 submit via Ajax (voir aussi 'mop')
			case 'sub':
				$operation = self::preparerOperationDemandee($pOperation);
				$res = self::traiterOperationDemandee($operation);
				if (SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_SCREEN) === 'o') {
					ini_set('memory_limit', '512M'); // TODO Supprimer ?
					$contenu = serialize($res);
					ini_restore('memory_limit');
				} else {
					$res = SG_Navigation::elementsDuBody($res, $operation);
					// si on vient d'u popup, le retour du centre se fait dans le popup
					$cible = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_WINDOW);
					if ($cible == 'formpopup' and isset($res['operation'])) {
						$res['popup'] = $res['operation'];
						unset($res['operation']);
					}
					$contenu = json_encode($res);
				}
				break;
			// 1.3.3 : aide du thème fourni
			case 'thh' :
				$operation = SG_Pilote::OperationEnCours();
				SG_Pilote::declarerOperationActive($operation);
				$thm = new SG_Theme(SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_THEME));
				$contenu = $thm -> Aide() -> texte;
				break;
			// menu thèmes
			case 'thm' :
				$contenu = SG_Navigation::Themes($recalcul);
				break;
			// test de développement
			case 'tst' :
				$contenu = new SG_Erreur('0048');
				break;
			// upload de fichiers multiples (en association avec paramètres p1, p2, p3)
			case 'upl' :
				$param = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM1);
				if ($param === '1') {
					$contenu = '[umf:'. ini_get('upload_max_filesize').',mfu:'.ini_get('max_file_uploads').',pms:'.ini_get('post_max_size').']';
				} elseif ($param === '2') {
					$champ = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM2);
					//$dir = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM3);
					$contenu = json_encode(SG_Navigation::upload($champ, 'var/uploads'), true);
				}
				break;
			// recherche de la liste des villes de CouchDB
			case 'vil' :
				$param = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM1);
				$actuel = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM2);
				$contenu = $_SESSION['@SynerGaia'] -> sgbd -> getVillesAjax($param,$actuel);
				break;
			case 'xdi' :
				$contenu = SG_Dictionnaire::ExporterJSON();
				$contenu = '{"children":[' . $contenu . ']}';
				break;
			default :
				$operation = SG_Pilote::OperationEnCours();
				$boutons = $operation -> getValeur('@Boutons', '');
				if (isset($boutons[$code])) {
					$formule = new SG_Formule($boutons[$code], $operation);
					// Si j'ai un document en paramètre	: je le prends en priorité car c'est avec lui que je vais travailler			
					$paramDoc = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_DOCUMENT);
					if ($paramDoc !== '') {
						$formule -> objet = $_SESSION['@SynerGaia'] -> getObjet($paramDoc);
					}		
					// passage des paramètres d'url (p1, p2, p3)
					for ($i = 1; $i <= 3; $i++) {
						$formule -> proprietes['$' . $i] = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_PARM . $i);
					}
					// calcul
					$contenu = $formule -> calculer();
				} else {
					$contenu = new SG_Erreur('0011', $paramQuery);
				}
				break;
		}
		return $contenu;
	}

	/**
	 * Met à jour l'opération en cours dans les globales. Si null en paramètre, efface l'indicateur d'opération en cours
	 * La référence de l'opération est placée dans $GLOBALS['operationencours']
	 * 
	 * @since 1.1 Ajout dans SG_Navigation
	 * @version 2.6 mis ici dans SG_Pilote
	 * @param SG_Operation $pOperation opération à déclarer
	 * @param boolean $pPrincipal mettre l'opération comme principal de l'étape
	 * @return boolean true
	 **/
	static function declarerOperationEnCours($pOperation, $pPrincipal = false) {
		if ($pOperation === null) {
			unset($GLOBALS['operationencours']);
		} else {
			SG_Pilote::declarerOperationActive($pOperation);
			$GLOBALS['operationencours'] = $pOperation -> reference;
			// Passe l'opération au statut "en cours"
			$pOperation -> setValeur('@Statut', SG_Operation::STATUT_ENCOURS);
			// thème en cours
			$_SESSION['page']['theme'] = $pOperation -> getValeur('@Theme', '');
			if ($pPrincipal) {
				SG_Navigation::setPrincipal($pOperation);
			}
		}
		return true;
	}

	/**
	 * Declarer l'opération comme active
	 * Elle est placée dans la liste des opérations indexées par leur référence
	 * 
	 * @since 2.6
	 * @param SG_Operation $pOperation operation à déclarer
	 * @return SG_Operation l'opération active
	 **/
	static function declarerOperationActive($pOperation) {
		if ($pOperation instanceof SG_Operation) {
			// mettre à jour les timestamp de l'opération
			if (is_null($pOperation -> start)) {
				$pOperation -> start = time();
			}
			$pOperation -> lastuse = time();
			$_SESSION['operations'][$pOperation -> reference] = $pOperation;
			$ret = $pOperation;
		} elseif ($pOperation instanceof SG_Erreur) {
			$ret = $pOperation;
		} else {
			$ret = new SG_Erreur('0174',getTypeSG($pOperation));
		}
		return $ret;
	}

}
?>
