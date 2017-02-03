<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Navigation : Classe de traitement de la navigation entre les pages
*/
class SG_Navigation {
	// Type SynerGaia
	const TYPESG = '@Navigation';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;
	// url principale de SynerGaia
	const URL_PRINCIPALE = 'index.php';
	// 2.1.1 url js
	const URL_JS = 'nav/js/';
	// 2.1.1 url js
	const URL_THEMES = 'nav/themes/';
	// 2.1.1 url js
	const URL_IMG = 'nav/img/';
	// Code pour la page de connexion (login)
	const URL_LOGIN = 'login';
	// Code de la page de déconnexion (logout)
	const URL_LOGOUT = 'logout';
	// Code de l'application (pour opération transverse)
	const URL_VARIABLE_APPLI = 'a';
	// Code du bout de formule d'un bouton
	const URL_VARIABLE_BOUTON = 'b';
	// Code du parametre de fonction sgGET()
	const URL_VARIABLE_CODE = 'c';
	// Code du parametre d'un document / liste de documents
	const URL_VARIABLE_DOCUMENT = 'd';
	// Code du parametre de l'étape dans l'opération
	const URL_VARIABLE_ETAPE = 'e';
	// Code du parametre d'une formule
	const URL_VARIABLE_FORMULE = 'f';
	// Code activé pour instruction de secours (vide cache par exemple)
	const URL_VARIABLE_HELP = 'h';
	// Code du parametre d'un jeton d'identification
	const URL_VARIABLE_JETON = 'k';
	// Code du parametre du modèle d'opération
	const URL_VARIABLE_MODELEOPERATION = 'm';
	// Code du parametre GET de l'opération
	const URL_VARIABLE_OPERATION = 'o';
	// parametres de traitement
	const URL_VARIABLE_PARM = 'p'; // pour utilisation de préfixe dans des boucles
	const URL_VARIABLE_PARM1 = 'p1';
	const URL_VARIABLE_PARM2 = 'p2';
	const URL_VARIABLE_PARM3 = 'p3';
	// Code pour forcer le recalcul d'un panneau sg_Get (on boucle sur l'étape en cours)
	const URL_VARIABLE_RECALCUL = 'r';
	// Code du parametre type de device (screen) s=m : mobile sinon d=defaut ; 'o' = objet json (erquete interapplicatives)
	const URL_VARIABLE_SCREEN = 's';
	// Code du parametre GET du thème
	const URL_VARIABLE_THEME = 't';
	// Code du parametre GET d'un identifiant utilisateur
	const URL_VARIABLE_IDENTIFIANT = 'u';
	// Code exécution d'une url par sg_get
	const URL_VARIABLE_EXEC = 'x';
	// 2.0 Code de cible pour l'ouverture de la nouvelle fenêtre ('g' gauche, 'm'=main défaut, 'c' centre, 'd' droite, 'n' nouvelle fenêtre)
	const URL_VARIABLE_WINDOW = 'w';
	// Code du parametre GET d'un id de téléchargement de fichier
	const URL_VARIABLE_FICHIER = 'z';
	// Code de l'opération
	public $codeOperation;
	// Code du modèle d'opération
	public $codeModeleOperation;
	// Code du thème
	public $codeTheme;
	// 2.3 ajout
	public $proportions = [20, 60, 20];
	// 2.3 ajout
	public $elements = array();

	/** 2.3 init elements
	* Construction de l'objet
	*/
	function __construct() {
		$this -> elements['admin'] = new SG_Cadre('admin');
		$this -> elements['aide'] = new SG_Cadre('aide');
		$this -> elements['centre'] = new SG_Cadre('centre');
		$this -> elements['debug'] = new SG_Cadre('debug');
		$this -> elements['droite'] = new SG_Cadre('droite');
		$this -> elements['entete'] = new SG_Cadre('entete');
		$this -> elements['erreurs'] = new SG_Cadre('erreurs');
		$this -> elements['gauche'] = new SG_Cadre('gauche');
		$this -> elements['popup'] = new SG_Cadre('popup');
		$this -> elements['themes'] = new SG_Cadre('themes');
	}

	/**
	* Renvoie le code du paramètre demandé (via GET)
	*
	* @param string $pCodeParametre
	* @param string $pValeurParDefaut
	*
	* @return string valeur du paramètre
	*/
	static function getParametre($pCodeParametre, $pValeurParDefaut = '') {
		$valeur = $pValeurParDefaut;
		if (isset($_GET[$pCodeParametre])) {
			$valeur = $_GET[$pCodeParametre];
		}
		return $valeur;
	}

	/**
	* Renvoie le préfixe des url de l'environnement SynerGaïa
	*
	* @return string prefixe des url
	*/
	static function getUrlBase() {
		$ret = '';

		// Cherche en cache
		$codeCache = '@Navigation.@getUrlBase';
		if (SG_Cache::estEnCache($codeCache, false) === true) {
			// Lit en cache
			$ret = SG_Cache::valeurEnCache($codeCache, false);
		} else {
			// Pas en cache : calcule la valeur
			if (isset($_SERVER['SERVER_PROTOCOL'])) {
				$protocol = (strstr('https', $_SERVER['SERVER_PROTOCOL']) === false) ? 'http' : 'https';
				$tempPath1 = explode('/', str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])));
				$tempPath2 = explode('/', str_replace('\\', '/', dirname(__FILE__)));
				$tempPath3 = explode('/', str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])));

				$nb = count($tempPath1);
				for ($i = count($tempPath2); $i < $nb; $i++) {
					array_pop($tempPath3);
				}

				if (isset($_SERVER['HTTP_HOST'])) {
					$ret = $protocol . '://' . $_SERVER['HTTP_HOST'] . implode('/', $tempPath3) . '/';
				}
			}
			// Enregistre en cache
			SG_Cache::mettreEnCache($codeCache, $ret, false);
		}

		return $ret;
	}

	/** 1.0.7 ; 1.3.4 indéfini si non trouvé
	* Determine si une opération est disponible pour un utilisateur
	*
	* @param indefini $pCodeModeleOperation
	* @param indefini $pIdentifiantUtilisateur
	*
	* @return boolean
	*/
	static function ModeleOperationDisponible($pModeleOperation = '', $pUtilisateur = '') {
		$ret = new SG_VraiFaux(false);
		$modeleOperation = SG_Navigation::getModeleOperation($pModeleOperation);
		if ($modeleOperation !== false) {
			$utilisateur = SG_Annuaire::getUtilisateur($pUtilisateur);
			if($utilisateur !== false) {
				$listeModeles = $utilisateur -> ModelesOperations();
				$ret = $listeModeles -> Contient($modeleOperation);
			}
		} else {
			$ret -> valeur = SG_VraiFaux::VRAIFAUX_INDEF;
		}
		return $ret;
	}
	/** 1.0.7
	* getModeleOperation : fournit un objet ModeleOperation à partir du paramètre
	* @param any $pModeleOperation code ou formule donnant un code d'opération
	* @return SG_ModeleOperation trouvé ou false
	*/
	static function getModeleOperation ($pModeleOperation = '') {
		$ret = false;
		$typeSG = getTypeSG($pModeleOperation);
		if( $typeSG === '@ModeleOperation') {
			$ret = $pModeleOperation;
		} else {
			if($typeSG !== 'string') {
				$typeSG = new SG_Texte($pModeleOperation);
				$codeModele = $typeSG -> texte;
			} else {
				$codeModele = $pModeleOperation;
			}
			if(getTypeSG($codeModele) === 'string') {
				$collec = $_SESSION['@SynerGaia']->getDocumentsFromTypeChamp('@ModeleOperation','@Code',$codeModele);
				if ($collec -> EstVide() -> estVrai()) {
					$ret = new SG_ModeleOperation($codeModele);
				} else {
					$ret = $collec -> Premier();
				}
			}
		}
		if ($ret !== false) {
			if (! $ret -> Existe() -> estVrai()) {
				$ret = false;
			}
		}
		return $ret;
	}
	/** 2.1 php, retour de demander ; 2.3 test erreur
	* 1.3.0 variables demandées sont dans opération en cours , retour  ; 1.3.4 test retour enregistrer ; 2.0 améliorer 'demander objet'
	* 1.1 passage de $refdoc dans DocumentPrincipal ; 1.3 récup $refDoc['proprietes']
	* 1.1 : avant save, si le type n'est pas bon on change de type(cas de retour de @Nouveau.@Modifier
	* Traitement des paramètres HTTP POST passés
	* @param : opération ou code de l'opération
	* @return SG_Operation ou SG_Erreur
	*/
	static function traitementParametres_HTTP_POST($paramOperation) {
		// voir si on a déjà une opération en cours pour terminer éventuellement l'étape précédente
		$opEnCours = self::obtenirOperation($paramOperation);
		// si opération en cours, peut-être traitement d'enregistrement à faire ?
		if (SG_Operation::isOperation($opEnCours)) {
			// y a-t-il un @Enregistrer sur le navigateur ?
			$codeChampEnreg = SG_Champ::codeChampHTML($opEnCours -> reference . '/@Enregistrer');
			if (isset($_POST[$codeChampEnreg])) {
				// obtenir le @Principal
				$objetEnCours = null;
				$refDoc = $opEnCours -> getValeur('@Principal', '');
				if ($refDoc !== '') {
					if (gettype($refDoc) === 'string') {
						// si seulement une référence, chercher le document dans la base
						$objetEnCours = $_SESSION['@SynerGaia'] -> getObjet($refDoc);
						if (!isset($objetEnCours)) { // pas trouvé
							$ret = new SG_Erreur('0045');
							$objetEnCours = null;
						}
					} else {
						$objetEnCours = $refDoc;
					}
				}
				$modif = false ;
				$modifOpe = false ;
				// mise à jour des champs
				foreach ($_POST as $nomZoneHTML => $valeurZoneHTML) {
					if ($nomZoneHTML !== $codeChampEnreg) { // ne pas traiter les champs cachés
						// si le champ POST a le bon préfixe
						$prefixe = SG_Champ::PREFIXE_HTML;
						if (substr($nomZoneHTML, 0, strlen($prefixe)) === $prefixe) {
							// extraire et décoder le nom du champ
							$nomChamp = SG_Champ::nomChampDecode(substr($nomZoneHTML, strlen($prefixe)));
//tracer($nomChamp);
							$partiesNomChamp = explode('/', $nomChamp);
							$uidDoc = $partiesNomChamp[0] . '/' . $partiesNomChamp[1];
							// le champ vient-il du @Principal ?
							$isObjetEnCours = false;
							if($objetEnCours !== null and getTypeSG($objetEnCours) !== '@Erreur') {
								if (method_exists($objetEnCours, 'getUUID')) {
									if ($uidDoc === $objetEnCours -> getUUID()) {
										$isObjetEnCours = true;
									}
								}
							}
							// s'agit-il de l'opération en cours ?
							$isOpEnCours = false;
							if ($isObjetEnCours) { // c'est le @Principal
								$tmpChamp = new SG_Champ($nomChamp, $objetEnCours);
							} elseif ($opEnCours -> getUUID() === $uidDoc) { // c'est l'opération en cours (variable saisie par @Demander)
								$tmpChamp = new SG_Champ();
								$tmpChamp -> codeChamp = $partiesNomChamp[2];
								$tmpChamp -> typeObjet = $opEnCours -> proprietes['@Type_' . $tmpChamp -> codeChamp];
								$tmpChamp -> valeur = $valeurZoneHTML;
								if(SG_Dictionnaire::isObjetDocument($tmpChamp -> typeObjet)) {
									$classe = SG_Dictionnaire::getClasseObjet($tmpChamp -> typeObjet);
									if ($classe !== '') {
										$doc = new $classe($valeurZoneHTML);
									} else {
										$doc = new SG_Erreur('0115',$classe);
									}
									$tmpChamp -> document = $doc;
									if (is_object($opEnCours -> formule)) {
									//	$opEnCours -> formule -> setValeur($partiesNomChamp[2], $doc); // TODO voir si supprimer avec php ?
									}
									$opEnCours -> proprietes[$partiesNomChamp[2]] = $doc; // 2.1
									$tmpChamp -> contenu = new SG_Texte($valeurZoneHTML);
								} else {
									$tmpChamp -> document = $opEnCours;
									$tmpChamp -> initContenu();
									$tmpChamp -> contenu -> contenant = ''; // pour éviter les récursions dans le JSON
									/*TODO voir si supprimer avec php ?
									if (is_object($opEnCours -> formule)) {
									$opEnCours -> formule -> proprietes[$partiesNomChamp[2]] = $tmpChamp -> contenu; // 
									}*/
									$opEnCours -> proprietes[$partiesNomChamp[2]] = $tmpChamp -> contenu; // 2.1
								}
								$isOpEnCours = true;
							} else{ // c'est un autre document
								if($partiesNomChamp[0] === $opEnCours -> reference) { // 2.1
									$tmpChamp = new SG_Champ($partiesNomChamp[1], $opEnCours);
								} else {
									$tmpChamp = new SG_Champ($nomChamp);
								}
							}
							if (substr($nomChamp, -4) === '_sup') {// suppression de fichier ou de champ caché
								// on ne fait rien
							} elseif ($tmpChamp -> contenu -> toString() !== $valeurZoneHTML) {
								// ne changer que si valeur différente
								if ($isObjetEnCours) {
									$modif = true;
									$tmpChamp -> Definir($valeurZoneHTML);
								} elseif (! $isOpEnCours) {
									$tmpChamp -> Definir($valeurZoneHTML, true); // si ce n'est pas le @Principal on sauve immédiatement
								}
							}
						}
					}
				}
				// à la fin, sauver @Principal si nécessaire
				$enr = false;
				if ($modifOpe) {
					$enr = $opEnCours -> Enregistrer();
				}
				if ($objetEnCours !== null) {
					if ($modif or (getTypeSG( $objetEnCours ) !== '@Erreur' and ! $objetEnCours -> Existe() -> estVrai())) {
						$enr = $objetEnCours -> Enregistrer();
					}
				}
				if (is_object($enr) and get_class($enr) === 'SG_Erreur') {
					$opEnCours = $enr;
				} else {
					$_SESSION['principal'][$opEnCours -> reference] = $objetEnCours;
					if ($enr === false) {
						$_POST[$codeChampEnreg] = '';
					}
				}
			} else {				
				// 2.1 par défaut, le principal est peut-être dans @Principal de l'opération ?
				$objetEnCours = $opEnCours -> Principal();
			}
		}
		return $opEnCours;
	}
	/** 1.3.0 : @param et retour ;1.3.4 @Enregistrer ; suppression fichier ; test $opEnCours is object ; 2.2 err 0180
	 * Traitement des paramètres HTTP FILES passés
	 * @param string ou @Operation : l'opération en cours
	 * @return boolean
	 */
	static function traitementParametres_HTTP_FILES($pOperation) {
		if(SG_Operation::isOperation($pOperation)) {
			$opEnCours = $pOperation;
		} else {
			$opEnCours = self::obtenirOperation($pOperation);
		}
		if ( ! is_object($opEnCours)) {
			$ret = new SG_Erreur('0112');
		} elseif (! array_key_exists($opEnCours -> reference, $_SESSION['principal'])) {
			$ret = new SG_Erreur('0180');
		} else {
			$objetEnCours = $_SESSION['principal'][$opEnCours -> reference];
			$fichiers = $_FILES;
			$save = false;
			foreach ($fichiers as $nomZoneHTML => $valeurZoneHTML) {
				// Si le nom du champ POST commence par le bon prefixe
				if (substr($nomZoneHTML, 0, strlen(SG_Champ::PREFIXE_HTML)) === SG_Champ::PREFIXE_HTML) {
					$save = true; // a priori on devra sauvegarder le document à la fin
					// Extrait la fin du nom du champ
					$nomChamp = SG_Champ::nomChampDecode(substr($nomZoneHTML, strlen(SG_Champ::PREFIXE_HTML)));
					$tmpChamp = explode('/', $nomChamp);
					$idsup = '_sup_' . $nomZoneHTML;
					if(isset($_POST[$idsup]) and $_POST[$idsup] !== '') {
						$tmp = explode('.', $tmpChamp[2]);
						switch (sizeof($tmp)) {// suppression (on n'a pas trouvé plus astucieux pour faire la boucle...!)
							case 1 :
								unset($objetEnCours -> doc -> proprietes[$tmpChamp[2]]);
								break;
							case 2 :
								unset($objetEnCours -> doc -> proprietes[$tmp[0]][$tmp[1]]);
								break;
							case 3 :
								unset($objetEnCours -> doc -> proprietes[$tmp[0]][$tmp[1]][$tmp[2]]);
								break;
							case 4 :
								unset($objetEnCours -> doc -> proprietes[$tmp[0]][$tmp[1]][$tmp[2]][$tmp[3]]);
								break;
							default :
								break;
						}
					} else {
						if (is_null($objetEnCours)) { // cas de @Demander.@Fichiers
							$tmpDoc = new SG_Document(); // temporaire
							$tmpChamp = new SG_Champ($nomChamp, $tmpDoc);
							$tmpChamp -> DefinirFichier($valeurZoneHTML);
							$nom = $tmpChamp -> codeChamp;
							$fic = new SG_Fichiers();
							if( isset($tmpDoc -> doc -> proprietes[$nom])) {
								$fic -> elements = $tmpDoc -> doc -> proprietes[$nom];
							}
							$opEnCours -> proprietes[$nom] = $fic;
							$save = false;
						} else {
							// document normal
							if ($tmpChamp[2] === '_attachments') {
								// stockage dans les fichiers attachés
								if($valeurZoneHTML['name'] !== '' and (isset($valeurZoneHTML['name'][0]) and $valeurZoneHTML['name'][0] !== '')) {
									$objetEnCours -> setFichier('', $valeurZoneHTML['tmp_name'], $valeurZoneHTML['name'], $valeurZoneHTML['type']);
								}
							} else {
								$tmpChamp = new SG_Champ($nomChamp, $objetEnCours);
								$tmpChamp -> DefinirFichier($valeurZoneHTML);
							}
						}
					}
				}
			}
			if ($save) {
				$objetEnCours -> Enregistrer();
			}
			$ret = $opEnCours;
		}
		return $opEnCours;
	}
	/** 2.1.1 mobile plus simple ; 2.2 déplace icone boite admin ; mobile
	* 1.1 déplacé de theme.php ; doc s'ouvre dans nouvel onglet ; 1.3.1 enlevé <admin> (voir @Navigation.@Body), +toggle <admin> ; 1.3.3 parm event
	* BANNIERE en haut de la page
	* @param (SG_VraiFaux ou boolean) recalculer la bannière
	* @return (string) Le texte HTML de la bannière
	*/
	static function Banniere($pRefresh = false) {
		$estMobile = self::estMobile();
		$refresh = SG_VraiFaux::getBooleen($pRefresh);
		$dir = self::repertoireIcones();
		if (isset($_SESSION['page']['banniere'])and $_SESSION['page']['banniere'] !== '' and !$refresh) {
			$ret = $_SESSION['page']['banniere'];
		} else {
			$ret = '<div id="banniere-container" class="sg-banniere"><ul>';
			// Logo et titre de l'application
			$titre = self::Titre();
			// cas des mobiles : simple bandeau avec le titre
			if($estMobile){
				$ret.= '<span class="click-pointer sg-banniere-titre">' . $titre . '</span>';
				$ret.= '<img class="sg-banniere-icone-menu" src="' . self::URL_THEMES . 'defaut/img/menu-alt-512.png" onclick="SynerGaia.themes();"></img>';
			} else {
				$estadmin = SG_Rien::Moi() -> EstAdministrateur() -> estVrai();
				$informationsUtilisateur = SG_Rien::Moi() -> toHTML();
				$informationsSynerGaia = ', version ' . $_SESSION['@SynerGaia']->Version();
				if ($estadmin) {
					$informationsSynerGaia .= ', cache : ' . SG_Cache::getTypeCache();
				}
				$ret.= $titre;
				// Lien vers le site SynerGaïa
				$ret .= '<li class="banniere-menu"><a href="http://docum.synergaia.eu" target="_blanck" title="Cliquez pour aller sur le site officiel de SynerGaïa">SynerGaïa ' . $_SESSION['@SynerGaia']->Version() . '</a></li>' . PHP_EOL;
				if(SG_Rien::Moi() -> EstAnonyme() -> estVrai() === true) {
					$ret .= '<li class="banniere-menu"><a href="' . self::URL_PRINCIPALE . '?' . self::URL_LOGIN . '"><img src="' . $dir . 'accept.png">Me connecter</a></li>' . PHP_EOL;
				} else {
					$ret.= '<li class="click-pointer banniere-menu" onclick="SynerGaia.launchOperation(event,\'AnnuaireGererMaFiche\', null, true)" title="Ouvrir ma fiche d\'annuaire">' . $informationsUtilisateur . '</li>' . PHP_EOL;
					$ret.= '<li class="banniere-menu"><a href="' . self::URL_PRINCIPALE . '?' . self::URL_LOGOUT . '" title="Cliquez pour vous déconnecter"><img src="' . $dir . 'cancel.png"></img>';
					$ret.= '<abbr title="' . SG_SynerGaia::IdentifiantConnexion() . ' (cliquer pour se déconnecter)">Déconnexion</abbr></a></li>' . PHP_EOL;
				}
			}
			$ret .= '</ul></div>' . PHP_EOL;
			$_SESSION['page']['banniere'] = $ret;
		}
		return $ret;
	}
	/** 1.0.7 ; 2.1.1 simplifiés, url ; 2.2 boite admin
	* Raccourcis ; calcule les raccourcis de l'utilisateur
	* @param boolean ou @VraiFaux : forcer le recalcul
	* @return @Collection collection des raccourcis avec un lien 
	* @formula : @Moi.@Raccourcis.@PourChaque(@ModeleOperation(.@Code).@LienPourNouvelleOperation)
	*/
	static function Raccourcis($pRecalcul = true) {
		$estadmin = SG_Rien::Moi() -> EstAdministrateur() -> estVrai();
		$ret = '<div class="raccourcis noprint">';
		$pimg = '<img class="raccourci noprint" src="' . self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/';
		$ret.= $pimg . 'printer.png" onclick="SynerGaia.print();" title="Imprimer">' . PHP_EOL;
		$ret.= $pimg . 'application_put.png" onclick="SynerGaia.elargir(98);" title="Pleine largeur">';
		$ret.= $pimg . 'application_side_contract.png" onclick="SynerGaia.deplacerVers(event,\'gauche\');" title="Mettre à gauche">';
		$ret.= $pimg . 'application_side_expand.png" onclick="SynerGaia.deplacerVers(event,\'droite\');" title="Mettre à droite">';
		if ($estadmin) {
			$ret.= $pimg . 'comment_edit.png" onclick="SynerGaia.toggle(\'admin\');" title="Affiche ou masque le formulaire d\'exécution de formule">';
		}
		$ret.= '</div>';
		return $ret;
	}
	
	// 1.1 déplacé depuis theme.php ; 1.3.1 @SynerGaia.@Titre
	static function Entete() {		
		if (!isset($_SESSION['page']['entete'])) {
			$_SESSION['page']['entete'] = '<title>' . $_SESSION['@SynerGaia'] -> Titre() . '</title>' . PHP_EOL;
		}
	}
	// 1.1 déplacé depuis theme.php
	static function updateNecessaire() {
		$ret ='';
		if (SG_Update::updateDictionnaireNecessaire() === true) {
			$operationUpdate = new SG_ModeleOperation('Update');
			$lienOperationUpdate = $operationUpdate -> LienPourNouvelleOperation(false);
			$libelle = SG_Libelle::getLibelle('0010');
			$ret .= '<span class="message">'. $libelle . ' : ' . $lienOperationUpdate . '</span>' . PHP_EOL;
		}
		return $ret;
	}
	// 1.1 déplace depuis theme.php ; 1.3.1 SG_TexteFormule
	static function boiteExecuterFormule() {
		$ret = '<form id="adminForm" method="get" action=""><fieldset>' . PHP_EOL;
		$txt = new SG_TexteFormule();
		$ret.= $txt -> modifierChamp(SG_Navigation::URL_VARIABLE_FORMULE) . PHP_EOL;
		$ret.= '<input type="submit" class="sg-bouton"/>' . PHP_EOL;
		$ret.= '</fieldset></form>' . PHP_EOL;
		return $ret;
	}
	/** 1.0.7
	* @return : @Collection
	* @formula : @Moi.@MesOperationsEnAttente.@PourChaque(.@Lien)
	*/
	static function OperationsEnAttente($recalcul = true) {
		if ($recalcul || !isset($_SESSION['panels']['opa'])) {
			$_SESSION['panels']['opa'] = SG_Formule::executer('@MesOperationsEnAttente.@PourChaque(.@Lien)', $_SESSION['@Moi']);
		}
		return $_SESSION['panels']['opa'];
	}
	/** 1.0.7
	// retour : @Collection
	* @formula : @Moi.@MesOperationsSuspendues.@PourChaque(.@Lien)
	*/
	static function OperationsSuspendues($recalcul = true) {
		if ($recalcul or !isset($_SESSION['panels']['ops'])) {
			$_SESSION['panels']['ops'] = SG_Formule::executer('@MesOperationsSuspendues.@PourChaque(.@Lien)', $_SESSION['@Moi']);
		}
		$ret = $_SESSION['panels']['ops'];
		return $ret;
	}
	/** 1.0.7
	 * MenuTheme : Retourne le menu html d'un theme (gardé en cache sauf si recalcul forcé)
	 * @param any $pTheme code du theme ou theme ou formule donnant un theme ou un code de theme
	 * @param boolean $pRecalcul : force le recalcul (défaut false)
	 * @return HTML : le panneau html com
	 */
	static function MenuTheme($pTheme = '', $pRecalcul = false) {
		$theme = $pTheme;
		$type = getTypeSG($theme);
		$ret = '';
		if (getTypeSG($theme) === '@Formule') {
			$theme = $theme -> calculer();
			$type = getTypeSG($theme);
		}
		if ($type === 'string') {
			$theme = new SG_Theme($theme);
			$type = getTypeSG($theme);
		}
		if ($type === '@Theme') {
			$recalcul = SG_VraiFaux::getBooleen($pRecalcul);
			$codeCache = 'MenuTheme(' .$theme -> Id() . ')';
			if (SG_Cache::estEnCache($codeCache, true) and $recalcul === false) {
				$ret = SG_Cache::valeurEnCache($codeCache, true);
			} else {
				$menu = $theme -> Menu(false); // pas de menu si vide
				$type = getTypeSG($menu);
				if ($type === 'string') {
					$ret = $menu;
				} elseif ($type === '@Texte' or $type === '@HTML') {
					$ret = $menu -> toHTML();
				} elseif ($type === '@Collection') {
					$ret = $menu -> toListeHTML('menu','menu');
				} else {
					$ret = $menu -> Afficher();
				}
				SG_Cache::mettreEnCache($codeCache, $ret, true);
			}
		}
		return $ret;
	}
	
	static function MenuThemeEnCours($recalcul = false) {
		if ($recalcul || !isset($_SESSION['panels']['mec'])) {
			$_SESSION['panels']['mec'] = SG_Navigation::MenuTheme('');
		}
		return $_SESSION['panels']['mec'];
	}
	
	/** retour HTML ; 1.3.3 foreach
	* @formula : @MesThemes.@Afficher
	**/
	static function Themes($recalcul = true) {
		if ($recalcul or !isset($_SESSION['panels']['thm'])) {
			$collection = SG_Rien::MesThemes();
			if (getTypeSG($collection) === '@Erreur') {
				$ret = $collection;
			} else {
				$ret = '<ul class="menu">';
				foreach ($collection -> elements as $theme) {
					$ret .= $theme -> toHtml();
				}
				$ret.='</ul>';
				$_SESSION['panels']['thm'] = $ret;
			}
		} else {
			$ret = $_SESSION['panels']['thm'];
		}
		return $ret;
	}
	/** 1.1 : ajout ; 1.3.3 debug
	* retour HTML
	*/
	static function Pied($recalcul = true) {
		$pied = '';
		if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
			$pied .= '<ul>';
			$chrono_total = round(microtime(true) - $_SESSION['timestamp_init'], 3);
			$pied.= '<li>Page calculée en ' . $chrono_total . 's</li>' . PHP_EOL;
			if (isset($_SESSION['benchmark'])) {
				$pied.= '<li>Mémoire PHP : ' . round(memory_get_usage() / 1024 / 1024) . ' Mo </li>';
				if (function_exists('mb_strlen')) {
					$size = mb_strlen(serialize($_SESSION), '8bit');
				} else {
					$size = strlen(serialize($_SESSION));
				}
				$pied.= '<li>$_SESSION : ' . round($size / 1024) . ' Ko </li>';
				foreach($_SESSION as $key=>$element) {
					if (substr($key, 0, 10) === 'operation_') {
						if (function_exists('mb_strlen')) {
							$size = mb_strlen(serialize($element), '8bit');
						} else {
							$size = strlen(serialize($element));
						}
						$pied.= '<li> ['.$key.'] : ' . round($size / 1024) . ' Ko </li>';
					}
				}
				$pied .= '<li><table style="padding:5px;"><tr><td style="padding:2px;">Étape</td><td style="padding:2px;">Durée totale</td>
					<td style="padding:2px;">Nombre</td><td style="padding:2px;">Moyenne</td><td style="padding:2px;">Minimum</td><td style="padding:2px;">Maximum</td></tr>';
				foreach ($_SESSION['benchmark'] as $cpt => $v) {
					if ($v[2]==0) {
						$pied .= '<tr><td>' . $cpt . ' : pas tourné !</td></tr>' . PHP_EOL;
					} else {
						$pied .= '<tr><td>' . $cpt . '</td><td>' . round($v[1], 4) . '</td><td>' . $v[2] . '</td>
							<td>' . round($v[1]/$v[2],4) . '</td><td>' . round($v[3], 4) . '</td><td>' . round($v[4], 4) . '</td></tr>' . PHP_EOL;
					}
				}
				$pied .='<table></li>';
				unset($_SESSION['benchmark']);
			}
			if (isset($_SESSION['chrono'])) {
				$prec = $_SESSION['timestamp_init'];
				$pied .= '<li>Durées d\'étape</li>' . PHP_EOL . '<ul>';
				foreach ($_SESSION['chrono'] as $etape => $temps) {
					$pied .= '<li>' . $etape . ' : ' . round($temps - $prec, 4) . '</li>' . PHP_EOL;
					$prec = $temps;
				}
				$pied .= '</ul>';
				unset($_SESSION['chrono']);
			}
			$pied .= '</ul>';
		}
		return $pied;
	}
	/** 1.0.7
	* retour HTML
	* @formula : @Si(@Moi.@EstAdministrateur,liste vide)
	*/	
	static function Debogage($recalcul = true) {
		$ret = '';
		if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
			$ret .= '<li>' . '</li>' . PHP_EOL;
		}
		return $ret;
	}
	/** 1.3.2 che, mop ; $pOperation ; 2.0 sub ; 2.3 mop interappli
	* Exécute une fonction connu standard accessible à tous ou une formule contenu dans un bouton pour les seuls administrateurs ou un code de bouton.
	* @param (string) $paramQuery : code de la fonction à exécuter
	* @param (boolean) $recalcul : indicateur du forçage de recalcul pour certaines fonctions
	* @param (string ou SG_Operation) $pOperation : opération en cours
	* @return (string) : json des différentes parties à afficher
	**/
	static function executerCodeSGGet($paramQuery = '', $recalcul = false, $pOperation = null) {
		$contenu = '';
		$code = $paramQuery;
		switch ($code) {
			// rechercher dans la collection (@AfficherChercher) // TODO Terminer...
			case 'che' : 
				$objet = SG_Navigation::OperationEnCours() -> Principal();
				if(!getTypeSG($objet) === '@Collection') {
					$contenu = new SG_Erreur('0087', getTypeSG($objet));
				} else {
					$contenu = SG_Navigation::OperationEnCours() -> Principal();
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
			// modele opération (uniquement première étape)
			case 'mop' :
				$operation = SG_Pilote::preparerOperationDemandee($pOperation);
				if (SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_SCREEN) === 'o') {
					$r = SG_Navigation::declarerOperationEnCours($operation);
					$r = SG_Navigation::setPrincipal($operation);
					if (getTypeSG($operation) === '@Erreur') {
						$contenu = serialize($operation);
					} else {
						$res = $operation -> Traiter('', '', '', 'f');
						ini_set('memory_limit', '512M'); // TODO Supprimer ?
						$contenu = serialize($res);
						ini_restore('memory_limit');
					}
				} else {
					$res = SG_Pilote::traiterOperationDemandee($operation);
					$contenu = json_encode(self::elementsDuBody($res, $operation));
					if ($contenu === false) {
						$contenu = '{"erreurs":"<ul><li>Erreur json : '. SG_DocumentCouchDB::jsonLastError('mop') .'</li></ul>"}';
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
				$objet = self::OperationEnCours() -> Principal();
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
			// 2.0 submit via Ajax
			case 'sub':
				SG_Pilote::initialiserNouvelleEtape();
				$operation = SG_Pilote::preparerOperationDemandee($pOperation);
				$res = SG_Pilote::traiterOperationDemandee($operation);
				$contenu = json_encode(self::elementsDuBody($res, $operation));
				break;
			// 1.3.3 : aide du thème fourni
			case 'thh' :
				$operation = SG_Navigation::OperationEnCours();
				$thm = new SG_Theme(SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_THEME));
				$contenu = $thm -> Aide() -> texte;
				$_SESSION['operations'][$operation -> reference] = $operation;
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
					$contenu = json_encode(self::upload($champ, 'var/uploads'), true);
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
				$operation = self::OperationEnCours();
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
	/** 1.0.7 ; 1.3.2 repris de login.php qui est supprimé
	*/
	static function pageLogin($erreurLogin = '') {
		$userid = SG_SynerGaia::IdentifiantConnexion();
		if ($userid === SG_Connexion::ANONYME) {
			$userid = '';
		}
		$page = '';
		$page .= SG_Navigation::Header();
		$page .= '<body>';
		if (SG_ThemeGraphique::ThemeGraphique() === 'mobilex'){
			$page .= '<div id="login" data-role="page" ' . SG_ThemeGraphique::dataTheme() . '>
				<header data-role="header"><h1>SynerGaia Mobile</h1></header>
				<div data-role="content">';
		} else {
			$page.= '<body id="login-body"><div id="login-wrapper" class="png_bg">
				<div id="login-top"></div>
				<div id="login-content">';
		}
		$page.= '<form id="login-form" method="post" action="' . self::URL_PRINCIPALE . '?' . self::URL_LOGIN . '=u"><p><label>Identifiant</label>
			<input value="' . $userid . '" name="username" class="text-input text-input-login" type="text" autofocus="autofocus" placeholder="votre identifiant"/></p>
			<p><label>Mot de passe</label><input name="password" class="text-input text-input-password" type="password" placeholder="votre mot de passe" /></p>
			<p> <input class="sg-bouton" type="submit" value="Connexion" /></p>';
		if ($erreurLogin !== '') { 
			$page .= '<div class="error">' . $erreurLogin . '</div>';
		}
		$page .= '</form></div></div></body></html>';
		return $page;
	}
	/** 1.0.7
	 * panneau des raccourcis
	 */
	static function panelRaccourcis($back = '', $header = true, $pAide = false, $pBoutons = '') {
		$panel = '';
		$panel .= '<div data-role="header"><div data-role="collapsible"><h3>Raccourcis</h3><ul data-role="listview">';
		$panel .= $this -> btnAccueil();
		$ops = SG_Navigation::OperationsEnAttente();
		if (getTypeSG($ops) === '@Collection') {
			$cpt =  $ops -> Compter() -> toInteger();
			if ($cpt > 0) {
				$panel .= '<li data-theme="e"><a href="#encours">En attente <span class="ui-li-count">(' . $cpt . ')</span></a></li>';
			}
		}
		$ops = SG_Navigation::OperationsSuspendues();
		if (getTypeSG($ops) === '@Collection') {
			$cpt =  $ops -> Compter() -> toInteger();
			if ($cpt > 0) {
				$panel .= '<li data-theme="e"><a href="#encours">Suspendues <span class="ui-li-count">(' . $cpt . ')</span></a></li>';
			}
		}
		$panel .=  SG_Navigation::Raccourcis(false) -> toListeHTML() ;
		if (isset($_SESSION['@Moi'])) {
			$panel .= '<li><a href="' . SG_Navigation::URL_PRINCIPALE . '?m=AnnuaireGererMaFiche">' . $_SESSION['@Moi'] -> identifiant . '</a></li>';
		}
		if($pAide === true) {
			$panel .=  '<li><a href="#aide">Aide</a></li>';
		}
		$panel .= '<li><a href="#pied">Infos sur le traitement</a></li>';
		if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
			$panel .= '<li><a href="#recherche" data-icon="search" data-iconpos="notext">Rechercher</a></li>';
		}
		$panel .= $pBoutons;
		$panel .= '</ul></div></div>';
		return $panel;
	}
	// page d'accueil sur les mobiles
	static function pageAccueil() {
		$ret = '<div data-role="content" ' . SG_ThemeGraphique::dataTheme() . '><ul data-role="listview" data-inset="true">' . SG_Navigation::Themes();
		$ret.= '</ul></div>';
		return $ret;
	}
	// Fabrique le bandeau d'aide de l'opération en cours
	static function pageAide($pObjet = null) {
		$page = '';
		$texte = '';
		$objet = $pObjet;
		if (getTypeSG($objet) === 'string') {
			$texte = $pObjet;
		} elseif ($objet !== null) {
			$texte = $objet -> Aide();
		}
		if (!is_string($texte)) {
			$texte = $texte -> toString();
		}
		if ($texte !== '') {
			$page = '<div id="aide-toggle" class="noprint" onclick="SynerGaia.toggle(\'aide-contenu\');">';
			$page.= '<i>' . SG_Libelle::getLibelle('0079') . '</i>';
			$page.= '<div id="aide-contenu" data-role="page" style="display:none;">' . $texte . '</div></div>' . PHP_EOL;
		}
		return $page;
	}
	// 1.3.2 repris de logout.php qui est abandonné
	static function pageLogout($pTitre = '') {
		$page = '';
		$page .= self::Header($pTitre);
		$page .= '<body id="logout"><div id="login-wrapper" class="png_bg"><div id="login-top"></div><div id="logout-content">
				<div>Vous êtes maintenant déconnecté.<br /><br /><a href="' . self::URL_PRINCIPALE . '">SynerGaïa</a>
				</div></div></div>';
		$page .= PHP_EOL . '</body></html>';
		return $page;
	}
	/** 1.0.5 ; 1.3.0 regarde dans $_SESSION ; 2.1 cherche operation dans $_SESSION
	 * retourne le document d'opération dont le code est passé
	 * @formula : retour=@Operation(code);	@Si(retour.@EstUn("@Erreur");@ModeleOperation(code);retour)
	 */
	static function obtenirOperation($pOperation = null) {
		$operation = null;
		if ($pOperation !== null) {
			$type = getTypeSG($pOperation);
			if ($type === '@Operation') {
				$operation = $pOperation;
			} elseif ($type === 'string') {
				if (isset($_SESSION['operations'][$pOperation])) {
					// opération active ?
					$operation = $_SESSION['operations'][$pOperation];
				} elseif (isset($GLOBALS['operationencours'])){
					if (is_object($GLOBALS['operationencours']) and $GLOBALS['operationencours'] -> reference === $pOperation) {
						$operation = $GLOBALS['operationencours'];
					} else {
						$operation = new SG_Erreur('0168', $pOperation); // TODO
					}
				} else {
					// Cherche les éléments de l'opération en cours
	// TODO traiter les opérations dérivées => chercher directement une opération par son code
					$collecDocOperation = SG_Rien::Chercher('@Operation', $pOperation);
					// Vérifie qu'on a bien trouvé une et une seule opération
					$n = $collecDocOperation -> Compter() -> toInteger();
					if ($n === 1) {
						$operation = $collecDocOperation -> Premier();
					} elseif ($n === 0) { // opération non trouvée
						$operation = new SG_Erreur('0018', $pOperation);
					} else { // ou non unique...
						$operation = new SG_Erreur('0019', $pOperation . ' ' . $n);
					}
				}
			} elseif ($type === '@ModeleOperation') {
				if (SG_Navigation::ModeleOperationDisponible($pOperation) -> estVrai()) {
					$operation = SG_Operation::CreerDuModele($pOperation);
					$_SESSION['operations'][$operation -> reference] = $operation;
				} else {
					// TODO Mettre un message clair pour l'utilisateur : "Cette opération n'est pas autorisée"
					$operation = new SG_Erreur('0014', $pOperation . ' : ' . $_Session['@Moi'] -> identifiant);
				}
			}
		}
		return $operation;
	}
	/** 1.1 AJout (remplace SG_Navigation.MettreAJourLeContexte) ; 1.3.0 $collec via .reduire pour Choisir
	* Met à jour le principal de l'étape de l'opération passée en paramètre
	* Le principal provient, dans l'ordre, de l'url d=, des champs modifiés du masque (doc ou collec), du champ @Principal de l'opération
	* Si on ne trouve pas, on reste sur le principal de l'étape précédente dans la variable globale de session
	* @param $pDocument string ou @Document : opération en cours
	* @return boolean on a modifié le principal
	*/
	static function setPrincipal($pOperation) {
		$modif = false;
		// Si j'ai un document en paramètre	: je le prends en priorité car c'est avec lui que je vais travailler			
		$paramDoc = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_DOCUMENT);
		if ($paramDoc !== '') {
			$doc = $_SESSION['@SynerGaia'] -> getObjet($paramDoc);
			if(is_object($pOperation)) {
				$_SESSION['principal'][$pOperation -> reference] = $doc;
				$pOperation -> setValeur('@Principal', $doc);
			}
			$modif = true;
		} elseif ( ! SG_Operation::isOperation($pOperation)) {
			// je n'ai pas d'opération concernée : ce n'est pas possible
			$modif = new SG_Erreur('0042', getTypeSG($pOperation));
		} else {
			// j'ai une opération en cours
			$codeChamp = SG_Champ::codeChampHTML($pOperation -> reference . '/@Enregistrer');
			// on a enregistré un document ?
			if (isset($_POST[$codeChamp])) {
				// récupérer le document enregistré : il est dans le principal de l'opération
				$refDoc = $_POST[$codeChamp];
				// si la référence est vide, c'est qu'on n'a pas eu d'enregistrement physique : on reste sur le principal en cours
				if ($refDoc !== '') {
					$doc = $_SESSION['@SynerGaia'] -> getObjet($refDoc);
					$_SESSION['principal'][$pOperation -> reference] = $doc;
					$pOperation -> setValeur('@Principal', $doc);
					$modif = true;
				}
			} else {
				$champDocPrincipal = SG_Champ::codeChampHTML($pOperation -> reference . '/@Principal');
				if (isset($_POST[$champDocPrincipal])) {
					$docprincipal = $_POST[$champDocPrincipal];
					if(is_array($docprincipal)) {
						// c'est une collection de données choisies dans une liste
						if (isset($_SESSION['principal'][$pOperation -> reference]) 
						and getTypeSG($_SESSION['principal'][$pOperation -> reference]) === '@Collection') {
							// on a la collection d'origine et on la réduit
							$collec = $_SESSION['principal'][$pOperation -> reference] -> reduire($docprincipal);
						} else {
							// sinon on la crée (ce qui pose problème si les doc sont nouveaux non enregistrés ou ont été modifiées)
							$collec = new SG_Collection();
							foreach ($docprincipal as $ref) {
								$objet = $_SESSION['@SynerGaia'] -> getObjet($ref);
								$collec -> Ajouter($objet);
							}
						}
						$pOperation -> doc -> proprietes['@Principal'] = $collec;
						$_SESSION['principal'][$pOperation -> reference] = $collec;
						$modif = true;
					} else {
						// c'est une référence de document unique qui doit exister
						$doc = $_SESSION['@SynerGaia'] -> getObjet($docprincipal);
						if(is_object($doc)) {						
							$_SESSION['principal'][$pOperation -> reference] = $doc;
							$pOperation -> setValeur('@Principal', $doc);
							$modif = true;
						}
					}
				}
			}
		}
		return $modif;
	}	
	/** 2.1.1 /nav ; 2.2 css spécifiques
	* 1.1 AJout (déplacé de SG_Navigation.MettreAJourLeContexte) ; 1.3.2 test sur les bibliothèques js ; 1.3.4 var/defaut.css ; multidatepicker
	* Met la référence du document principal dans l'opération
	* Si cette référece n'est pas identique à document principal de la navigation, met à jour la navigation
	* @param $pDocument string ou @Document
	*/
	static function Header($pTitre = '') {
		//$lib = $_SESSION['libs'];
		$ret = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<title>' . $_SESSION['@SynerGaia'] -> Titre() . '</title>';
		//jquery 1.11.1 from CDN Google ou serveur
		if (file_exists(SYNERGAIA_PATH_TO_ROOT . '/' . self::URL_JS . 'jquery/jquery-1.11.1.min.js')) {
			$ret.= '<script src="' . self::URL_JS . 'jquery/jquery-1.11.1.min.js"></script>';
		} else {
			$ret.= '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>';
		}
		//jQueryUI
		$ret .= '<link rel="stylesheet" media="all" type="text/css" href="' . self::URL_JS . 'jqueryui/1.10.3/themes/smoothness/jquery-ui.min.css" />
		<script type="text/javascript" src="' . self::URL_JS . 'jqueryui/1.10.3/jquery-ui.min.js"></script>';

		$ret .= '<script src="' . self::URL_JS . 'jqueryui/addons/uitablefilter.js"></script>';

		//datepicker
		$ret .= '<link rel="stylesheet" media="screen" type="text/css" href="' . self::URL_JS . 'datepicker/css/datepicker.css" />
		<script type="text/javascript" src="' . self::URL_JS . 'datepicker/js/datepicker.js"></script>';

		//multi dates picker
		$ret .= '<script type="text/javascript" src="' . self::URL_JS . 'multidatepicker/jquery-ui.multidatespicker.js"></script>';

		//jQuery mobile 1.3.2 sur code.jquery.com ou serveur
		if (SG_ThemeGraphique::ThemeGraphique() === 'mobile') {
			if (file_exists(SYNERGAIA_PATH_TO_ROOT . '/' . self::URL_JS . 'jquerymobile/jquery.mobile-1.3.2.min.js')) {
				$ret .= '<script type="text/javascript" src="' . self::URL_JS . 'jquerymobile/jquery.mobile-1.3.2.min.js"></script>';
			} else {
				$ret .= '<script src="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.js"></script>';
			}
		}
		// D3
		$ret .= '<script src="' . self::URL_JS . 'jquery.svg.1.4.5/jquery.svg.js"></script>
		<link rel="stylesheet" href="' . self::URL_JS . 'jquery.svg.1.4.5/jquery.svg.css"></link>';
		$ret.= '<script type="text/javascript" src="' . self::URL_JS . 'd3/d3.min.js"></script>';

		$ret .= '<script src="' . self::URL_JS . 'jqueryui/addons/jquery-ui-datetimepicker-addon.js"></script>
		<script src="' . self::URL_JS . 'jqueryui/addons/jquery-ui-datetimepicker-addon-fr.js"></script>
		<script src="' . self::URL_JS . 'jqueryui/addons/jquery-ui-sliderAccess.js"></script>';

		$ret .= '<script type="text/javascript" src="' . self::URL_JS . 'editablegrid-2.0.1/editablegrid-2.0.1.js"></script>
		<script type="text/javascript" src="' . self::URL_JS . 'editablegrid.js"></script>';

		$ret.= '<script src="' . self::URL_JS . 'tinymce/tinymce.min.js"></script>';//tinymce 4.1.0	
		$ret.= '<link rel="stylesheet" type="text/css" href="' . self::URL_JS . 'fullcalendar/fullcalendar.css" />';
		$ret.= '<script src="' . self::URL_JS . 'fullcalendar/fullcalendar.min.js"></script>';
		
		// scripts SynerGaïa
		$ret.= '<script src="' . self::URL_JS . 'synergaia.js"></script>';
		$ret.= '<script src="' . self::URL_JS . 'synergaia-dtpicker.js"></script>';
		$ret.= '<script src="' . self::URL_JS . 'synergaia-graphiques.js"></script>';
		$ret.= '<script src="' . self::URL_JS . 'synergaia-proximites.js"></script>';
		// styles synergaia selon le mode graphique
		$ret .= '<link rel="icon" type="image/png" href="' . self::URL_THEMES . 'defaut/img/favicon.png" />';
		$tg = SG_ThemeGraphique::ThemeGraphique();
		$ret.= '<link rel="stylesheet" type="text/css" href="' . self::URL_THEMES . 'defaut/css/' . $tg . '.css"/>';
		// styles spécifiques de l'application
		if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/css/defaut.css')) {
			$ret.= '<link rel="stylesheet" href="var/css/defaut.css"></link>';
		}
		$ret.= '</head>';
		return $ret;
	}

	static function url($url='') {
		$ret = '';
		if($url !== '') {
			$ret .= '<script type="text/javascript">document.location.href="' .$url . '";</script>';
		}
		return $ret;
	}

	static function AdresseIP() {
		return $_SERVER['SERVER_ADDR'];
	}
	/** 1.1 AJout
	* Met à jour l'opération en cours pour @Navigation.@OperationEnCours
	*/
	static function declarerOperationEnCours($pOperation) {
		if ($pOperation === null) {
			unset($GLOBALS['operationencours']);
		} else {
			$GLOBALS['operationencours'] = $pOperation;
			// Passe l'opération au statut "en cours"
			$pOperation -> setValeur('@Statut', SG_Operation::STATUT_ENCOURS);
			// thème en cours
			$_SESSION['page']['theme'] = $pOperation -> getValeur('@Theme', '');
		}
	}
	/** 1.1 Déplacé (était avant dans SG_Rien) ; 1.2 création si n'existe pas
	* Renvoie l'opération en cours
	* @return SG_Operation opération en cours
	*/
	static function OperationEnCours() {
		if (!isset($GLOBALS['operationencours'])) {
			$GLOBALS['operationencours'] = SG_Operation::Creer('');
		}
		return $GLOBALS['operationencours'];
	}
	/**  2.0 sup menupied, abandon drag drop ; 2.1 raccourcis, sup menu contextuel, 2.1.1 favori ; 2.3 récup erreur ; correct form droite
	* 1.3.3 proportions ; réorganisation pour droite et gauche et mobile ; context menu ; 1.3.4 elementsDuBody() ;
	* 1.1 repris de template.php périmé ; 1.3.1 <admin> déplacé au dessus du <corps> ; section 'adroite' ; 1.3.2 => lehaut()
	* BODY = theme defaut
	*/
	static function Body($resultat, $operation) {
		$estMobile = self::estMobile();
		$elements = self::elementsDuBody($resultat, $operation);
		$ret = '';
		if (self::estMobile()) {
			$proportions = [0, 100, 0];
		} else {
			$proportions = [30, 60, 30];
		}
		// corpsligne
		//$ret = '<div id="corpsligne">';
		//== PARTIE GAUCHE
		$ret.= '<div id="gauche" data-role="page" class="noprint box" style="width:' . $proportions[0] . '%;" '; //2.1 2.3 onclick="SynerGaia.devantderriere(event)" 
		$ret.= 'onmouseover="SynerGaia.devantderriere(event,true)" onmouseout="SynerGaia.devantderriere(event,false)" draggable="true">';
		// résultat d'opération dans <form> si SG_HTML à gauche
		if (isset($elements['gauche'])) {
			$ret.= '<form id="formgauche">' . $elements['gauche'] . '</form>'; //2.0 onmouseup="SynerGaia.lacher(event)
		}
		$ret.= '</div>'; // fin gauche
		//== PARTIE CENTRALE
		$ml = ((100 - $proportions[1])/2);
		$pct = 'style="margin-left: ' . $ml . '%;width:' . $proportions[1] . '%;"';
		$ret.= '<div id="centre" ' . $pct . ' class="box">';
		// --- boutons raccourcis ---
		if(!$estMobile) {
			$ret.= self::Raccourcis();
			// Boite Admin
			$ret.= self::boiteAdmin();
		}
		// Corps
		$ret.= '<div id="corps" data-role="page">';
		// entête 
		if (isset($elements['op-entete'])) {
			$ret.= '<div id="op-entete" class="operationEntete noprint">' . $elements['op-entete']. '</div>' . PHP_EOL;
		}
		if (!$estMobile and isset($elements['aide'])) {
			$ret.= $elements['aide'];
		}
		// erreurs
		if (isset($elements['erreurs'])) {
			$ret.= '<div id="erreurs" class="erreurs noprint" >' . $elements['erreurs'] . '</div>' . PHP_EOL;
		}
		// contenu principal
		$ret.= '<div id="operation" class="sg-operation-contenu" data-role="content">' . PHP_EOL;
		if (isset($elements['operation'])) {
			if (getTypeSG($elements['operation']) === '@Erreur') {
				$ret.= $elements['operation'] -> toString();
			} else {
				$ret.= $elements['operation'];
			}
		}
		$ret .= '</div>'; // operation
		$ret .= '</div>'; // corps
		$ret.='</div>'; // fin centre
		//== A DROITE
		$ml = 100 - $proportions[2];
		$pct = 'style="margin-left: ' . $ml . '%;width:' . $proportions[2] . '%;"';
		$ret.='<div id="droite" ' . $pct . ' class="noprint" draggable="true">';
		if (isset($elements['droite'])) {
			$ret.= '<div id="adroite" data-role="page" class="adroite noprint" onclick="SynerGaia.devantderriere(event)">' . $elements['droite'] . '</div>';
		}
		$ret.= '</div>';
		return $ret;
	}
	/** 2.2 si $resligne array ; 2.3 supp <br> ; pas effacer si centre vide
	* 2.1 correction refresh, debug , test html, correction centre, retour ligne, test resultat array
	* 1.3.3 ajout ; 1.3.4 'debug' ; 2.0 prise en compte de refresh ;
	* construction d'un body en tableau associatif.
	* Cette fonction est utilisée dès que la page complète a été affichée, pour mettre à jour uniquement les parties recalculées
	* C'est le cas notamment pour les appels sg_get
	**/
	static function elementsDuBody($resultat, $operation) {
		$ret = array();
		// entête  
		$ret['op-entete'] = $_SESSION['page']['entete'] . self::favori($operation);
		$ret['aide'] = '<div id="aide" class="noprint">' . $_SESSION['page']['aide'] . '</div>';
		// erreurs
		$e = '<ul>';
		if (getTypeSG($resultat) === '@Erreur') {
			$e.= '<li>' . $resultat -> getMessage() . '</li>';
		} elseif (isset($resultat['erreurs']) and $resultat['erreurs'] !== '') {
			if (is_string($resultat['erreurs'])) {
				$e.= '<li>'.$resultat['erreurs'].'</li>';
			} else {
				$e.= '<li>'.$resultat['erreurs'] -> toString().'</li>';
			}
			unset($resultat['erreurs']);
		}
		if ($_SESSION['page']['erreurs'] !== '') {
			$e.= '<li>' . $_SESSION['page']['erreurs'] . '</li>';
		}
		$ret['erreurs'] = $e . '</ul>';
		// y aura-t-il quelque chose au centre ?
		$centre = '';
		$centrevide = true;
		if(is_array($resultat)) { // examiner ['n° operation']['submit']
            foreach($resultat as $key => $resligne) {
                if (!is_array($resligne)) {
                    $resligne = array($resligne);
                }
                foreach($resligne as $res) {
					if($key !== 'submit') {
						if(is_object($res)) { // SG_HTML
							if ($res -> estHTML()) {
								if ($res -> cadre === 'centre' or $res -> cadre === '') {
									$centrevide = false;
									if ($res -> rupture === null) {
										$centre .= $res -> texte . PHP_EOL;
									} else {
										$centre .= $res -> texte;
									}
								}
							} else {
								$centrevide = false;
								$centre .= $res -> toHTML();
							}
						} else { // sinon texte au centre
							$centrevide = false;
							$centre .= $res;
						}
					}
				}
			}
		} else {
			$centrevide = false;
			$centre = $resultat;
		}
		// cadre de gauche
		$idForm = 'formgauche';
		$agauche = '';
		if(is_array($resultat)) {
			foreach($resultat as $texte) {
				if (is_object($texte) and $texte -> estHTML() and $texte -> cadre === 'gauche') {
					$agauche.= $texte -> toHTML();
				}
			}
			if ($agauche !== '' and $agauche !== '<ul></ul>') {
				if(isset($resultat['submit']) and $centrevide) {
					$ret['gauche'] = self::genererBaliseForm($operation, $idForm) . '<div id="agauche" class="agauche noprint">' . $agauche;
					$ret['gauche'].= '<br>' . self::genererBoutonSubmit($resultat['submit'], $idForm);
					$ret['gauche'].= '</div></form>';
				} else {
					$ret['gauche'] = $agauche;
				}
			}
		}
		// centre
		//  - operation
		$idForm = 'formcentre';
		$texte = '';
		if(is_null($operation)) {
			$texte = $centre;
		} elseif (getTypeSG($operation) === '@Erreur') {
			$texte = $operation -> toHTML();
			if (is_object($texte) and $texte -> estHTML()) {
				$texte = $texte -> texte;
			}
		} else {
			$texte = self::genererBaliseForm($operation, $idForm);
			// bouton submit haut et bas, seulement si quelque chose au centre
			if(is_array($resultat)) {
				$bouton = '';
				if(isset($resultat['submit']) and !$centrevide) {
					$bouton = '<br>' . self::genererBoutonSubmit($resultat['submit'], $idForm). '<br>';
				}
				if ($centre !== '' or $bouton !== '') {
					$texte .= $bouton . $centre . $bouton;
				}
			} else {
				$texte.= 'Aucun résultat';
			}
			$texte .= '</form>';
		}
		if ($centre !== '') {
			$ret['operation'] = $texte;
		}
		//======== droite
		$adroite = '';
		$idForm = 'formdroite';
		// résultat d'opération si SG_HTML à droite
		if(is_array($resultat)) {
			foreach($resultat as $key => $texte) {
				if (is_object($texte) and $texte -> estHTML() and $texte -> cadre === 'droite') {
					$adroite.= $texte -> toHTML();
				}
			}
			if ($adroite !== '' and $adroite !== '<ul></ul>') {
				if(isset($resultat['submit']) and $centrevide) {
					$ret['droite'] = self::genererBaliseForm($operation, $idForm) . '<div id="adroite" class="adroite noprint">' . $adroite;
					$ret['droite'].= '<br>' . self::genererBoutonSubmit($resultat['submit'], $idForm);
					$ret['droite'].= '</div></form>';
				} else {
					$ret['droite'] = $adroite;
				}
			}

		}
		if (isset($_SESSION['debug']['texte'])) {
			$ret['debug'] = $_SESSION['debug']['texte'];
		}
		if (isset($_SESSION['refresh'])) {
			foreach($_SESSION['refresh'] as $key => $texte) {
				if (is_object($texte)) {
					$ret[$key] = $texte -> texte;
				} else {
					$ret[$key] = $texte;
				}
			}
			unset($_SESSION['refresh']);
		}
		return $ret;
	}
	/** 1.3.3 ajout ; 2.2 SynerGaia.initOnLoad
	* Scripts de fin de body
	**/
	static function finBody() {
		$ret = '<script>SynerGaia.initOnLoad()</script>' . PHP_EOL . '<script>';
		foreach ($_SESSION['script'] as $code => $script) {
			$ret .= $script;
		}
		$ret .= '</script></body>';
		return $ret;
	}
	/** 1.1 ajout
	*/
	static function repertoireIcones() {
		return self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/';
	}
	// 1.1 ajout (source originale : http://www.inserthtml.com/2012/08/file-uploader/)
	static function fileUploader() {
		$ret = '';
		$ret .= '<div id="drop-files" ondragover="return false">Lâchez les fichiers ici !</div>';
		$ret .= '<div id="uploaded-holder">';
		$ret .= '<div id="dropped-files"><div id="upload-button"><a href="#" class="upload"><i class="ss-upload"> </i> Upload!</a>';
        $ret .= '    <a href="#" class="delete"><i class="ss-delete"> </i></a><span>0 Files</span></div></div>';
		$ret .= '<div id="extra-files"><div class="number">0</div><div id="file-list"><ul></ul></div></div></div>';
		$ret .= '<div id="loading"><div id="loading-bar"><div class="loading-color"></div></div><div id="loading-content">Uploading file.jpg</div></div>';
		$ret .= '<div id="file-name-holder"><ul id="uploaded-files"><h1>Uploaded Files</h1></ul></div>';
		return $ret;
	}
	// 1.1 ajout
	static function UploaderFichiers() {
		// We're putting all our files in a directory called images.
		$uploaddir = 'images/';
		 
		// The posted data, for reference
		$file = $_POST['value'];
		$name = $_POST['name'];
		 
		// Get the mime
		$getMime = explode('.', $name);
		$mime = end($getMime);
		 
		// Separate out the data
		$data = explode(',', $file);
		 
		// Encode it correctly
		$encodedData = str_replace(' ','+',$data[1]);
		$decodedData = base64_decode($encodedData);
		 
		// You can use the name given, or create a random name.
		// We will create a random name!
		$randomName = substr_replace(sha1(microtime(true)), '', 12).'.'.$mime;
		 
		if(file_put_contents($uploaddir.$randomName, $decodedData)) {
			$ret = $randomName.":uploaded successfully";
		} else {
			// Show an error message should something go wrong.
			$ret = "Something went wrong. Check that the file isn't corrupted";
		}
		return $ret;
	}
	/** 1.3.0 ajout
	*/
	static function btnAccueil() {
		$ret = '<li><a href="' . SG_Navigation::URL_PRINCIPALE . '" data-icon="home" data-iconpos="notext" data-direction="reverse" data-transition="slide">Accueil</a></li>';
		return $ret;
	}
	/** 1.3.1 ajout
	* retourne la phrase de Logo du navigateur
	*/
	static function Logo($pLogo = '') {
		$logo = $_SESSION['@SynerGaia'] -> Logo($pLogo);
		if($logo !== '') {
			$ret='<img src="' . $logo . '"></img>';
		} else {
			$ret = '';
		}
		return $ret;
	}
	/** 1.3.1 ajout
	* cadre pour les suggestions (menus déroulants)
	* @return html
	**/
	static function autosuggestions() {
		$ret = '<div id="autosuggestions" class="autosuggestions">';
		$ret.= '<div id="autosuggestions-liste"></div>';
		$ret.= '<img src="' . self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/cancel.png" onclick="$(\'#autosuggestions\').hide()"></img>';
		$ret.= '</div>'.PHP_EOL;
		return $ret;
	}
	/** 1.3.1 ajout ; 1.3.2 rien si null ; 1.3.3 'd'
	* Permet de remplir la partie 'adroite' à droite. Le cadre est 'droite'
	* @param (SG_Formule) formule donnant ce qu'il faut placer à droite
	**/
	static function ADroite ($pFormule = '') {
		$ret = new SG_Rien();
		if ($pFormule !== '' and getTypeSG($pFormule) === '@Formule') {
			$ret = $pFormule -> calculer();
			$type = getTypeSG($ret);
			if ($type === '@Texte' or $type === 'string') {
				$ret = new SG_HTML($ret);
			}
			if($type === '@HTML') {
				$ret -> cadre = 'droite';
			}
		}
		return $ret;
	}
	/** 1.3.1 ajout
	* Titre de l'application
	*/
	static function Titre() {
		$ret ='<div class="sg-banniere-logo">' . self::Logo() . '</div>';
		$ret.= '<div class="sg-banniere-titre">' . $_SESSION['@SynerGaia'] -> Titre() . '</div>';
		return $ret;
	}
	/** (1.3.1) déplacé de themes.php ; 1.3.3 montremenu ; 2.1.1 test @Erreur ; 2.3 correction boucle
	* calcule les onglets des themes standards
	**/
	static function composerThemesDefaut($themeEnCours) {
		$ret = '';
		$mobile = self::estMobile();
		$ongletsHTML = '';
		if (!isset($_SESSION['page']['themes']) or $_SESSION['page']['themes'] === '') {
			// Cherche les themes de l'utilisateur en cours, selon ses profils
			$collectionThemes = SG_Rien::MesThemes();
			$themes = array();
			if (getTypeSG($collectionThemes) === '@Collection') {
				if (sizeof($collectionThemes -> elements) === 0) {
					$ongletsHTML .= 'Aucun thème trouvé.' . PHP_EOL;
				} else {
					foreach ($collectionThemes -> elements as $theme) {
						$type = getTypeSG($theme);
						if ($type !== '@Rien' and $type !== '@Erreur') {
							$themes[] = array($theme -> getCodeDocument(), $theme -> Titre(), $theme -> getValeur('@IconeTheme'));
						}
					}
				}
			} else {
				if (getTypeSG($collectionThemes) === '@Erreur') {
					$message = $collectionThemes -> toString();
				} else {
					$message = 'Aucun thème trouvé.';
				}
				$ongletsHTML .= $message . PHP_EOL;
			}

			$ongletsHTML .= '<ul id="themes-liste" class="menu">' . PHP_EOL;
			$nbOnglets = sizeof($themes);
			for ($i = 0; $i < $nbOnglets; $i++) {
				$themeCode = $themes[$i][0];
				$themeTitre = $themes[$i][1];
				$themeIcone = $themes[$i][2];
				$themeStyleCSS = '';
				if ($themeEnCours !== null) {
					if ($themeCode === $themeEnCours -> getCodeDocument()) {
						$themeStyleCSS = 'class="select" ';
					}
				}

				$ligneTheme = '';
				// activation des sous-menus
				$id = 'menu_' . $themeCode;
				if ($mobile) {
					// afficher le menu
					$ligneTheme .= '<li onclick="SynerGaia.getMenu(event,\'c=men&' . SG_Navigation::URL_VARIABLE_THEME . '=' . $themeCode . '\')">' . PHP_EOL;
				} else {
					// afficher la page de présentation
					$ligneTheme .= '<li onclick="SynerGaia.getMenu(event,\'c=thh&' . SG_Navigation::URL_VARIABLE_THEME . '=' . $themeCode . '\')">' . PHP_EOL;
				}
				// TODO : icone du thème : gérer le thème graphique
				if ($themeIcone !== '') {
					$ligneTheme .= ' <img src="' . self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/' . $themeIcone . '" alt="' . htmlentities($themeTitre, ENT_QUOTES, 'UTF-8') . '"/>' . PHP_EOL;
				}
				$ligneTheme.= $themeTitre;
				$ligneTheme.= self::MenuTheme($themeCode);
				$ligneTheme.= '</li>' . PHP_EOL;

				$ongletsHTML.= $ligneTheme . PHP_EOL;
			}
			$ongletsHTML .= ' </ul>' . PHP_EOL;
		}
		$_SESSION['page']['themes'] = $ongletsHTML;
	}
	/** 1.3.1 déplacé
	*
	**/
	static function boiteAdmin() {
		$ret = '';
		// si administrateur, div admin
		if (SG_Rien::Moi() -> EstAdministrateur() -> estVrai() === true) {
			// Affiche la boite de saisie d'une formule
			$ret.= '<div id="admin" class="admin noprint" style="display:none"><ul style="list-style-type:none;">';
			// update nécessaire ?
			$upd = self::updateNecessaire();
			if($upd !== '') {
				$ret .= '<li>' . $upd . '</li>';
			}
			$ret.= '<li>' . self::boiteExecuterFormule() . '</li>' . PHP_EOL;
			$ret .= '</ul>' . PHP_EOL;			
			$ret.= '<div id="debug" class="debug noprint" style="margin-left: 0%;width:100%;">';
			if (isset($_SESSION['debug']['texte']) and $_SESSION['debug']['texte'] !== '') {
				$ret.= $_SESSION['debug']['texte'];
			}
			$ret .= '</div></div>';// debug, admin
		}
		return $ret;
	}
	/** 1.3.2 déplace de Body() ; 2.3 loader
	* affiche Bannière et Thèmes
	*/
	static function LeHaut() {
		$ret = '<body>';
		$ret.='<div id="media" style="display:none;" class="noprint">' . SG_ThemeGraphique::ThemeGraphique() . '</div>'; // pour tester dans js
		// Fenêtres cachées au chargement
		$ret.= self::autosuggestions() . PHP_EOL;
		$ret.= '<div id="popup_window" class="popup_block noprint" onclick="SynerGaia.popupClick(event,\'popup_window\')"></div>'.PHP_EOL;
		$ret.= '<div id="loader" class="loader-div noprint" style="display:none"></div>'.PHP_EOL;
		$ret.= self::contextMenu() . PHP_EOL;
		//#### HAUT
		// Bannière
		$ret.= '<div id="banniere" class="noprint">' . self::Banniere() . '</div>';
		// Themes (préparé dans self::composerThemesDefaut())
		$ret.= '<div id="themes" class="noprint">' . $_SESSION['page']['themes'] . '</div>';
		return $ret;
	}
	/** ajout 1.3.3 ; 1.3.4 $pMode ; 2.1 $element peut être string
	* @param (string ou @Document) : element visé
	* @param (boolean) $pMode : lien html ou lien Ajax
	* @return html : lien
	**/
	static function getUrlEtape($element, $pMode = true) {
		$uid = '';
		if (is_string($element)) {
			$uid = $element;
		} elseif (method_exists($element,'getUUID')) {
			$uid = $element -> getUUID();
		}
		$ret = self::URL_VARIABLE_OPERATION . '=' . self::OperationEnCours() -> reference;
		$ret .= '&' . self::URL_VARIABLE_ETAPE . '=' . $_SESSION['page']['etape_prochaine'];
		// si lien réel
		if ($uid !== '') {
			$ret .= '&' . self::URL_VARIABLE_DOCUMENT . '=' . $uid;
		}
		if ($pMode) {
			$ret = self::calculerURL($ret);
		}
		return $ret;
	}
	/** 1.3.3 ajout
	* Etablit les largeurs en pourcentage des trois parties du corps du navigateur (par défaut 20% 60% 20%)
	* Ceci est valable pour l'opération en cours
	* @param $pGauche (@Nombre) défaut 0
	* @param $pCentre (@Nombre) défaut 0
	* @param $pDroite (@Nombre) défaut 0
	* @return @Navigation
	**/
	function Diviser($pGauche = 0, $pCentre = 0, $pDroite = 0) {
		$g = new SG_Nombre($pGauche);
		$g = $g -> toFloat();
		$c = new SG_Nombre($pCentre);
		$c = $c -> toFloat();
		$d = new SG_Nombre($pDroite);
		$d = $d -> toFloat();
		if($g === 0 and $c === 0 and $d === 0) {
			$this -> proportions = [20, 60, 20];
		} else {
			$this -> proportions = [$g, $c, $d];
		}
		return $this;
	}
	/** 1.3.3 ajout
	* Permet de remplir la partie 'gauche' à droite. Le cadre est 'd'
	* @param (SG_Formule) formule donnant ce qu'il faut placer à droite
	* @return le résultat
	**/
	static function AGauche ($pFormule = '') {
		$ret = new SG_Rien();
		if ($pFormule !== '' and getTypeSG($pFormule) === '@Formule') {
			$ret = $pFormule -> calculer();
			$type = getTypeSG($ret);
			if ($type === SG_Texte or $type === 'string') {
				$ret = new SG_HTML($ret);
			}
			if($type === '@HTML') {
				$ret -> cadre = 'gauche';
			}
		}
		return $ret;
	}
	/** 1.3.3 ajout
	* Pour compatibilité. Toujours @Vrai
	**/
	static function EstVide () {
		return new SG_VraiFaux(true);
	}
	/** 1.3.3 ajout
	**/ 
	static function estMobile() {
		return SG_ThemeGraphique::ThemeGraphique() === 'mobile';
	}
	/** 1.3.3 ajout ; 2.0 effacer = true
	* calcul une url à lancer ou cliquer selon le thème graphique
	* @param $pURL (string) url à lancer
	* @param $pMode (boolean) true (défaut) : par http:// ; false : par sg_getLanchOperation()
	**/
	static function calculerURL($pURL = '', $pMode = true) {
		if ($pMode) {
			$ret = self::URL_PRINCIPALE . '?' . $pURL;
		} else {
			$ret = 'javascript:SynerGaia.launchOperation(event,"' . $pURL . '",null,true)';
		}
		return $ret;
	}
	/** 1.3.3 ajout
	* prépare un context menu masqué (clic droit)
	**/
	static function contextMenu() {
		$ret = '<div id="contextMenuCorps" style="display:none" class="noprint"><ul>';
		$ret.= '<li onclick="SynerGaia.contextMenu.hide(\'contextMenuCorps\');SynerGaia.print();" id="ctxmenu_print">Imprimer</li>';
		$ret.= '<li onclick="SynerGaia.contextMenu.hide(\'contextMenuCorps\');SynerGaia.elargir();" id="ctxmenu_elargir">Élargir</li>';
		$ret.= '<li onclick="SynerGaia.contextMenu.hide(\'contextMenuCorps\');SynerGaia.retrecir();" id="ctxmenu_retrecir">Rétrécir</li>';
		$ret.= '<li onclick="SynerGaia.contextMenu.hide(\'contextMenuCorps\')" id="ctxmenu_fermer">Fermer</li>';
		$ret.= '</ul></div>';
		return $ret;
	}
	/** 1.3.4 ajout
	* pour tous les objets : false sauf SG_Erreur et dérivés
	**/
	function estErreur() {
		return false;
	}
	/** 2.0 ajout ; 2.1 getValeurPropriete au lieu de getValeur
	* génère une balise <form>
	* @param (SG_Operation) : operation encours
	* @param (string) : id de la form
	* @return (string) texte html
	**/
	static private function genererBaliseForm($pOperation, $pID) {		
		$urlProchaineEtape = htmlentities($pOperation -> urlProchaineEtape(), ENT_QUOTES, 'UTF-8'); // index.php?o=operation&e=derniereetape
		$ret = '<form method="post" action="' . $urlProchaineEtape . '" enctype="multipart/form-data" id="'. $pID . '">' . PHP_EOL;
		// si affichage d'un document, mettre champ /@Principal pour identifier au retour
		// (si checkbox sur collection, est déjà présent dans la collection)
		$principal = $pOperation -> Principal();
		if(getTypeSG($principal) === 'string' and $principal !== '') {
			$tmpChamp = SG_Champ::codeChampHTML($pOperation -> reference . '/@Principal');
			$ret.= '<input type="hidden" name="' . $tmpChamp . '" value="' . $principal . '"/>';
		}
		return $ret;
	}
	/** 2.0 ajout ; 2.3 suppr <br>
	* génère un bouton dans une <form>
	* @param texte ou html : texte du bouton
	* @param id de la <form> à soumettre
	* @return texte html du bouton
	*/
	static private function genererBoutonSubmit ($pTexte, $pID) {
		$ret = '<span class="sg-bouton" onclick="SynerGaia.submit(event, \'' . $pID . '\')">' . $pTexte . '</span>';
		return $ret;
	}
	/** 2.1 ajout ; 2.3 htmlentities
	* crée l'étoile de mise en favori de l'opération en cours
	**/
	static function favori($pOperation) {
		if ($pOperation === null) {
			$ret = '';
		} else {
			$pimg = '<img class="raccourci noprint" src="' . self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/';
			$favoriURL = 'm=' . $pOperation -> getValeur('@Code');
			$favoriJeton = 'k=' . $_SESSION['@Moi'] -> Jeton() -> texte;
			$favoriID = 'u=' . $_SESSION['@Moi'] -> identifiant;
			$key = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . '?' . $favoriURL . '&' . $favoriJeton . '&' . $favoriID;
			$titre = $_SESSION['@SynerGaia'] -> Titre() . ' : ' . $pOperation -> Titre();
			$ret = $pimg . 'star.png" onclick="SynerGaia.favori(event,\''. $key . '\', \'' . htmlentities($titre) . '\');" title="Mettre en favori :' . htmlentities($pOperation -> Titre()) . '">' . PHP_EOL;
		}
		return $ret;
	}
	/** 2.2 ajout
	* uploader des fichiers vers un répertoire temporaire
	* @param string $pChamp : nom du champ dans lequel les images sont stockées
	* @param string $pDir : nom du répertoire temporaire
	* @return
	**/
	static function upload($pChamp, $pDir) {
		$ret = array();
		if (!is_dir($pDir)) {
			mkdir($pDir, 0777, true);
		}
		foreach ($_FILES[$pChamp]['error'] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$name = $_FILES[$pChamp]['name'][$key];
				move_uploaded_file( $_FILES[$pChamp]['tmp_name'][$key], $pDir . '/' . $name);
				$ret[] = $name;
			} else {
				$ret[] = $name . ' error';
			}
		}
		return $ret;
	}
	/** 2.3 ajout
	* ceci n'est pas un SG_HTML
	**/
	function estHTML() {
		return false;
	}
}
?>
