<?php
/** SYNERGAIA fichier contenant les objets nécessaires pour gérer le navigateur, notamment la classe SG_Navigation */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Navigation : Classe de traitement des éléments du navigateur Internet
 * Les méthodes de cette classe sont presque toutes statiques et n'est pas un SG_Objet SynerGaïa
 * @version 2.6 : transfert executerCodeSGGet dans SG_Pilote
 **/
class SG_Navigation {
	/** string Type SynerGaia */
	const TYPESG = '@Navigation';
	
	/** string url principale de SynerGaia */
	const URL_PRINCIPALE = 'index.php';
	/** string url js 
	 * @since 2.1.1
	 **/
	const URL_JS = 'nav/js/';
	/** string url themes 
	 * @since 2.1.1
	 **/
	const URL_THEMES = 'nav/themes/';
	/** string url images 
	 * @since 2.1.1
	 **/
	const URL_IMG = 'nav/img/';
	/** string Code pour la page de connexion (login) */
	const URL_LOGIN = 'login';
	/** string Code de la page de déconnexion (logout) */
	const URL_LOGOUT = 'logout';
	/** string Code de l'application (pour opération transverse)*/
	const URL_VARIABLE_APPLI = 'a';
	/** string Code du bout de formule d'un bouton */
	const URL_VARIABLE_BOUTON = 'b';
	/** string Code du parametre de fonction sgGET() */
	const URL_VARIABLE_CODE = 'c';																																																																																																																																		
	/** string Code du parametre d'un document / liste de documents */
	const URL_VARIABLE_DOCUMENT = 'd';
	/** string Code du parametre de l'étape dans l'opération */
	const URL_VARIABLE_ETAPE = 'e';
	/** string Code du parametre d'une formule */
	const URL_VARIABLE_FORMULE = 'f';
	/** string Code activé pour instruction de secours (vide cache par exemple) */
	const URL_VARIABLE_HELP = 'h';
	/** string Index dans une collection (i=codeobjet:indice) si indice vide : tout l'objet */
	const URL_VARIABLE_INDEX = 'i';
	/** string Code du parametre d'un jeton d'identification */
	const URL_VARIABLE_JETON = 'k';
	/** string Code du parametre du modèle d'opération */
	const URL_VARIABLE_MODELEOPERATION = 'm';
	/** string Code du parametre GET de l'opération */
	const URL_VARIABLE_OPERATION = 'o';
	/** string parametre de traitement pour utilisation de préfixe dans des boucles */
	const URL_VARIABLE_PARM = 'p';
	/** string parametre 1 de traitement */
	const URL_VARIABLE_PARM1 = 'p1';
	/** string parametre 2 de traitement */
	const URL_VARIABLE_PARM2 = 'p2';
	/** string parametre 3 de traitement */
	const URL_VARIABLE_PARM3 = 'p3';
	/** string non vide pour forcer la recompilation préalable de la formule ou de l'opération (en cas de changement de version)
	 * @since 2.6 */
	const URL_VARIABLE_RECOMPIL = 'q';
	/** string Code pour forcer le recalcul d'un panneau sg_Get (on boucle sur l'étape en cours) */
	const URL_VARIABLE_RECALCUL = 'r';
	/** string Code du parametre type de device (screen) s=m : mobile sinon d=defaut ; 'o' = objet json (requete interapplicative) */
	const URL_VARIABLE_SCREEN = 's';
	/** string Code du parametre GET du thème */
	const URL_VARIABLE_THEME = 't';
	/** string Code du parametre GET d'un identifiant utilisateur */
	const URL_VARIABLE_IDENTIFIANT = 'u';
	/** string Code exécution d'une url par sg_get */
	const URL_VARIABLE_EXEC = 'x';
	/** string 2.0 Code de cible pour l'ouverture de la nouvelle fenêtre ('g' gauche, 'm'=main défaut, 'c' centre, 'd' droite, 'n' nouvelle fenêtre ou code box) */
	const URL_VARIABLE_WINDOW = 'w';
	/** string Code du parametre GET d'un id de téléchargement de fichier */
	const URL_VARIABLE_FICHIER = 'z';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;
	/** string Code de l'opération */
	public $codeOperation;
	/** string Code du modèle d'opération */
	public $codeModeleOperation;
	/** string Code du thème */
	public $codeTheme;
	/** array 2.3 ajout */
	public $proportions = [30, 60, 30];
	/** array 2.3 ajout */
	public $elements = array();

	/**
	 * Construction de l'objet
	 * @since 2.3 init elements
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
	 * @version 2.4 urldecode
	 * @param string $pCodeParametre
	 * @param string $pValeurParDefaut
	 *
	 * @return string valeur du paramètre
	 */
	static function getParametre($pCodeParametre, $pValeurParDefaut = '') {
		$valeur = $pValeurParDefaut;
		if (isset($_GET[$pCodeParametre])) {
			$valeur = urldecode($_GET[$pCodeParametre]);
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

	/**
	 * Determine si une opération est disponible pour un utilisateur
	 *
	 * @since 1.0.7
	 * @version 1.3.4 indéfini si non trouvé
	 * @version 2.6 test user erreur
	 * @param indefini $pModeleOperation
	 * @param indefini $pUtilisateur
	 *
	 * @return boolean
	 */
	static function ModeleOperationDisponible($pModeleOperation = '', $pUtilisateur = '') {
		$ret = new SG_VraiFaux(false);
		$modeleOperation = SG_Navigation::getModeleOperation($pModeleOperation);
		if ($modeleOperation !== false) {
			$utilisateur = SG_Annuaire::getUtilisateur($pUtilisateur);
			if ($utilisateur instanceof SG_Erreur) {
				$ret = $utilisateur;
			} elseif ($utilisateur !== false) {
				$listeModeles = $utilisateur -> ModelesOperations();
				$ret = $listeModeles -> Contient($modeleOperation);
			}
		} else {
			$ret -> valeur = SG_VraiFaux::VRAIFAUX_INDEF;
		}
		return $ret;
	}

	/**
	* getModeleOperation : fournit un objet ModeleOperation à partir du paramètre
	* 
	* @since 1.0.7
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

	/**
	 * Traitement des paramètres HTTP POST passés
	 * Ils peuvent concerner le document en cours, l'opération en cours, d'autres documents ou une collection de documents
	 * 
	 * @since 0.5
	 * @version 2.4 $docPOST, test contrôles
	 * @param string|SG_Operation $opEnCours opération ou code de l'opération
	 * @return SG_Operation|SG_Erreur
	 * @throws Exception si l'enregistrement récupère une SG_Erreur
	 * @todo la récup des données restent ici ; les controles et l'enregistrement vont dans SG_Pilote
	 */
	static function traitementParametres_HTTP_POST($opEnCours) {
		$objetEnCours = null;
		$indices = array();
		$estCollec = false;
		// si on est dans l'opération en cours, il y a peut-être un traitement d'enregistrement à faire ?
		if (SG_Operation::isOperation($opEnCours)) {
			// effacer les erreurs précédentes
			$opEnCours -> erreurs = array();
			// obtenir le @Principal
			$refDoc = $opEnCours -> getPrincipal();
			if ($refDoc !== '') {
				if (is_string($refDoc)) {
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
			// si l'objet en cours est une collection de documents,
			// on récupère le document d'origine si réduite
			// et on prépare une table code doc -> indice pour la mise à jour des champs
			if ($objetEnCours instanceof SG_Collection) {
				$estCollec = true;
				if ($objetEnCours -> reduit) {
					foreach ($objetEnCours -> elements as $key => $objet) {
						if (getTypeSG($objet) === '@IDDoc') {
							$doc = $objet -> Document();
							$doc -> proprietes = $objet -> proprietes;
							$objetEnCours -> elements[$key] = $doc;
						} else {
						//	$objetEnCours -> elements[$key] = $objet; // normalement c'est impossible...
						}
					}
					$objetEnCours -> reduit = false;
					$opEnCours -> setPrincipal($objetEnCours);
				}
				foreach ($objetEnCours -> elements as $key => $objet) {
					if ($objet -> DeriveDeDocument() -> estVrai()) {
						$indices[$objet -> getUUID()] = $key;
					}
				}
			}
			$modif = false ;
			// mise à jour des champs saisis
			foreach ($_POST as $nomZoneHTML => $valeurZoneHTML) {
				// si le champ POST a le bon préfixe (sg-field_)
				$prefixe = SG_Champ::PREFIXE_HTML;
				if (substr($nomZoneHTML, 0, strlen($prefixe)) === $prefixe) {
					// extraire et décoder le nom du champ
					$nomChamp = SG_Champ::nomChampDecode(substr($nomZoneHTML, strlen($prefixe)));
					$partiesNomChamp = explode('/', $nomChamp);
					$uidDoc = $partiesNomChamp[0] . '/' . $partiesNomChamp[1];
					$tmpChamp = null;
					// le champ vient-il du @Principal ?
					$isObjetEnCours = false;
					if($objetEnCours !== null and ! $objetEnCours instanceof SG_Erreur) {
						if (method_exists($objetEnCours, 'getUUID')) {
							if ($uidDoc === $objetEnCours -> getUUID()) {
								$isObjetEnCours = true;
							}
						}
					}
					/** ici on recherche où se trouve le document concerné par le champ
					 * il peut s'agir du document principal (existant, nouveau, ou provisoire), de l'opération en cours, 
					 * mais aussi d'un document d'une collection principale - dans ce cas il faudra peuit-être enregistrer à chaque fois
					 **/
					$isOpEnCours = false;
					if ($opEnCours -> getUUID() === $uidDoc) { // c'est l'opération en cours (variable saisie par @Demander)
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
							$opEnCours -> proprietes[$partiesNomChamp[2]] = $doc; // 2.1
							$tmpChamp -> contenu = new SG_Texte($valeurZoneHTML);
						} else {
							$tmpChamp -> document = $opEnCours;
							$tmpChamp -> initContenu();
							$tmpChamp -> contenu -> contenant = ''; // pour éviter les récursions dans le JSON
							$opEnCours -> proprietes[$partiesNomChamp[2]] = $tmpChamp -> contenu; // 2.1
						}
						$isOpEnCours = true;
					} elseif ($isObjetEnCours) { // c'est le @Principal
						$tmpChamp = new SG_Champ($nomChamp, $objetEnCours);
					} elseif ($estCollec and isset($indices[$uidDoc])) { // c'est un document de la collection principale
						// on teste s'il s'agit d'une propriété du dictionnaire
						$doc = &$objetEnCours -> elements[$indices[$uidDoc]];
						$codeChamp = $partiesNomChamp[2];
						if (SG_Dictionnaire::isProprieteExiste(getTypeSG($doc), $codeChamp)) {
							$doc -> setValeur($codeChamp, $valeurZoneHTML);
						} else {
							// sinon c'est une variable associée au document
							if (isset($doc -> proprietes['@Type_' . $codeChamp])) {
								$type = $doc -> proprietes['@Type_' . $codeChamp];
								$classe = SG_Dictionnaire::getClasseObjet($type);
								$doc -> proprietes[$codeChamp] = new $classe($valeurZoneHTML);
							} else  {
								$doc -> proprietes[$codeChamp] = new SG_Texte($valeurZoneHTML);
							}
						}
					} else {// c'est un autre document
						if($partiesNomChamp[0] === $opEnCours -> reference) { // 2.1
							$tmpChamp = new SG_Champ($partiesNomChamp[1], $opEnCours);
						} else {
							$tmpChamp = new SG_Champ($nomChamp);
						}
					}
					// on finit le traitement en mettant éventuellement à jour immédiatement les documents impactés
					if (substr($nomChamp, -4) === '_sup') {// suppression de fichier ou de champ caché
						// on ne fait rien
					} elseif (is_object($tmpChamp) and $tmpChamp -> contenu -> toString() !== $valeurZoneHTML) { // ne changer que si valeur différente
						if (! $isOpEnCours) { // ce n'est pas l'opération
							if ($isObjetEnCours) {
								// c'est l'objet en cours : on le met à jour sans écrire sur disque
								$modif = true;
								$tmpChamp -> Definir($valeurZoneHTML);
							} else { // ce n'est pas l'opération if (! $isOpEnCours)
								// on teste s'il s'agit d'une propriété du dictionnaire
								if (SG_Dictionnaire::isProprieteExiste(getTypeSG($tmpChamp -> document), $tmpChamp -> codeChamp)) {
									// propriété mais pas le @Principal : on sauve immédiatement (true)
									$tmpChamp -> Definir($valeurZoneHTML, true);
								} else {
									// sinon c'est une variable associée au document (2ème false)
									$tmpChamp -> Definir($valeurZoneHTML, false, false);
								}
							}
						}
					}
				} // fin si prefixe
			} // fin boucle sur les variables transmises
			// à la fin, effectuer les controles (dans étiquette)
			$enr = false;
			if ($objetEnCours !== null and $objetEnCours -> DeriveDeDocument() -> estVrai()) {
				if ($modif or (getTypeSG( $objetEnCours ) !== '@Erreur' and ! $objetEnCours -> Existe() -> estVrai())) {
					// 2.4 controler ce qui a été envoyé
					$ok = true;
					$etape = $opEnCours -> etape;
					if ($etape === '') {
						$etape = '1';
					}
					$fnctrl =  'ctrl_etape_' . $etape;
					if (method_exists($opEnCours, $fnctrl)) {
						$ctl = $opEnCours -> $fnctrl($objetEnCours);
						if ($ctl !== '') {
							$ctl -> gravite = SG_Erreur::ERREUR_CTRL;
							$opEncours -> erreurs[] = $ctl;
							$ok = false;
						}
					}
					// et sauver si nécessaire
					if ($ok) {
						if ($modif) {
							// enregistrer les données
							$enr = $objetEnCours -> Enregistrer();
						}
					} else {
						$objetEnCours = $ctl;
						$enr = $ctl;
					}
				}
			}
			// garder @Principal si nécessaire
			if (is_object($enr) and get_class($enr) === 'SG_Erreur') {
				$opEnCours = $enr;
				if ($enr -> gravite > SG_Erreur::ERREUR_CTRL) {
					$e = new Exception($enr -> code);
					$e -> erreur = $enr;
					throw $e;
				}
			} else {
				$opEnCours -> setPrincipal($objetEnCours);
			}
		}
		return $opEnCours;
	}

	/**
	 * Traitement des paramètres HTTP FILES passés
	 * 
	 * @version 2.2 err 0180 ; multifichiers
	 * @param string|SG_Operation : l'opération en cours
	 * @return SG_Operation|SG_Erreur
	 * @todo séparer la récup des données : restent ici, les controles et l'enregistrement : dans SG_Pilote
	 */
	static function traitementParametres_HTTP_FILES($opEnCours) {
		if (!is_object($opEnCours)) {
			$ret = new SG_Erreur('0112');
		} else {
			$objetEnCours = $opEnCours -> getPrincipal();
			$fichiers = $_FILES;
			$save = false;
			foreach ($fichiers as $nomZoneHTML => $valeurZoneHTML) {
				// Si le nom du champ POST commence par le bon prefixe
				if (substr($nomZoneHTML, 0, strlen(SG_Champ::PREFIXE_HTML)) === SG_Champ::PREFIXE_HTML) {
					//$save = true; // a priori on devra sauvegarder le document à la fin
					// Extrait la fin du nom du champ
					$nomChamp = SG_Champ::nomChampDecode(substr($nomZoneHTML, strlen(SG_Champ::PREFIXE_HTML)));
					$tmpChamp = explode('/', $nomChamp);
					$idsup = '_sup_' . $nomZoneHTML;
					if(isset($_POST[$idsup]) and $_POST[$idsup] !== '') {
						$tmp = explode('.', $tmpChamp[2]);
						switch (sizeof($tmp)) {// suppression (on n'a pas trouvé plus astucieux pour faire la boucle...!)
							case 1 :
								unset($objetEnCours -> doc -> proprietes[$tmpChamp[2]]);
								$save = true;
								break;
							case 2 :
								unset($objetEnCours -> doc -> proprietes[$tmp[0]][$tmp[1]]);
								$save = true;
								break;
							case 3 :
								unset($objetEnCours -> doc -> proprietes[$tmp[0]][$tmp[1]][$tmp[2]]);
								$save = true;
								break;
							case 4 :
								unset($objetEnCours -> doc -> proprietes[$tmp[0]][$tmp[1]][$tmp[2]][$tmp[3]]);
								$save = true;
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
							if ($objetEnCours instanceof SG_Document) { //-> DeriveDeDocument() -> estVrai() === true) {
								// document normal
								if ($tmpChamp[2] === '_attachments') {
									// stockage dans les fichiers attachés (2.4 modif test)
									if(is_array($valeurZoneHTML['name'])) {
										$nfic = $objetEnCours -> setFichier('', $valeurZoneHTML['tmp_name'], $valeurZoneHTML['name'], $valeurZoneHTML['type']);
										if ($nfic > 0) {
											$save = true;
										}
									}
								} else {
									$tmpChamp = new SG_Champ($nomChamp, $objetEnCours);
									$tmpChamp -> DefinirFichier($valeurZoneHTML);
									$save = true;
								}
							}// c'est une collection ?? une erreur ??
						}
					}
				}
			} // end foreach
			if ($save === true) {
				$objetEnCours -> Enregistrer();
			}
			$ret = $opEnCours;
		}
		return $opEnCours;
	}

	/**
	 * BANNIERE du haut de la page
	 * 
	 * @since 1.1 déplacé de theme.php
	 * @version 2.2 déplace icone boite admin ; mobile
	 * @version 2.6 classes css
	 * @param boolean|SG_VraiFaux $pRefresh recalculer la bannière
	 * @return string Le texte HTML de la bannière
	 * @uses JS SynerGaia.themes(), SynerGaia.launchOperation(
	 */
	static function Banniere($pRefresh = false) {
		$estMobile = self::estMobile();
		$refresh = SG_VraiFaux::getBooleen($pRefresh);
		$dir = self::repertoireIcones();
		if (isset($_SESSION['page']['banniere'])and $_SESSION['page']['banniere'] !== '' and !$refresh) {
			$ret = $_SESSION['page']['banniere'];
		} else {
			$ret = '<div id="banniere-container" class="sg-banniere"><div class="sg-banniere-menu">';
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
				$ret .= '<div class="sg-banniere-ligne"><a class="sg-banniere-icone sg-banniere-lien" href="http://docum.synergaia.eu" target="_blanck" title="Cliquez pour aller sur le site officiel de SynerGaïa">SynerGaïa ' . $_SESSION['@SynerGaia']->Version() . '</a></div>' . PHP_EOL;
				if(SG_Rien::Moi() -> EstAnonyme() -> estVrai() === true) {
					$ret .= '<div class="sg-banniere-ligne"><a class="sg-banniere-lien" href="' . self::URL_PRINCIPALE . '?' . self::URL_LOGIN . '"><img class="sg-banniere-icone" src="' . $dir . 'accept.png">Me connecter</a></div>' . PHP_EOL;
				} else {
					$ret.= '<div class="sg-banniere-ligne click-pointer" onclick="SynerGaia.launchOperation(event,\'AnnuaireGererMaFiche\', null, true)" title="Ouvrir ma fiche d\'annuaire">' . $informationsUtilisateur . '</div>' . PHP_EOL;

					$ret.= '<div class="sg-banniere-ligne"><a class="sg-banniere-lien" href="' . self::URL_PRINCIPALE . '?' . self::URL_LOGOUT . '" title="Cliquez pour vous déconnecter"><img class="sg-banniere-icone" src="' . $dir . 'cancel.png"></img>';
					$ret.= '<abbr title="' . SG_SynerGaia::IdentifiantConnexion() . ' (cliquer pour se déconnecter)">Déconnexion</abbr></a></div>' . PHP_EOL;
				}
			}
			$ret .= '</div></div>' . PHP_EOL;
			$_SESSION['page']['banniere'] = $ret;
		}
		return $ret;
	}

	/**
	 * Raccourcis ; calcule les raccourcis de l'utilisateur
	 * 
	 * @since 1.0.7
	 * @version 2.2 boite admin
	 * @version 2.6 classes sg-
	 * @param boolean ou @VraiFaux : forcer le recalcul
	 * @return @Collection collection des raccourcis avec un lien 
	 * @formula : @Moi.@Raccourcis.@PourChaque(@ModeleOperation(.@Code).@LienPourNouvelleOperation)
	 * @uses JS SynerGaia.print(), SynerGaia.elargir(), SynerGaia.deplacerVers(), SynerGaia.toggle()
	 * @todo mettre libellés en fichier
	 */
	static function Raccourcis($pRecalcul = true) {
		$estadmin = SG_Rien::Moi() -> EstAdministrateur() -> estVrai();
		$ret = '<div class="sg-raccourcis noprint">';
		$pimg = '<img class="sg-raccourci noprint" src="' . self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/';
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
	
	/**
	 * Met à jour en globales de Session les informations d'entête
	 * 
	 * @since 1.1 déplacé depuis theme.php
	 * @version 1.3.1 @SynerGaia.@Titre
	 */
	static function Entete() {		
		if (!isset($_SESSION['page']['entete'])) {
			$_SESSION['page']['entete'] = '<title>' . $_SESSION['@SynerGaia'] -> Titre() . '</title>' . PHP_EOL;
		}
	}

	/**
	 * Teste si une nouvelle version est à mettre à jour
	 * 
	 * @since 1.1 déplacé depuis theme.php
	 * @version 2.6 f=@SynerGaia.@MettreAJour pour éviter d'utiliser la version obsolète de MO_Update
	 * @return string HTML pour la boite admin
	 */
	static function updateNecessaire() {
		$ret ='';
		if (SG_SynerGaia::updateDictionnaireNecessaire() === true) {
			if (SG_SynerGaia::VERSION === '2.6.0') {
				$url = SG_Navigation::URL_PRINCIPALE . '?' . SG_Navigation::URL_VARIABLE_FORMULE . '=@SynerGaia.@MettreAJour';
				$resume = 'Mettre à jour la version';
				$href = '<a href="' . $url . '" title="' . $resume . '" >';
				$href.= '<span>' . $resume . '</span>';
				$href.= '</a>';
			} else {
				$operationUpdate = new SG_ModeleOperation('Update');
				$href = $operationUpdate -> LienPourNouvelleOperation(false) -> texte;
			}
			$libelle = SG_Libelle::getLibelle('0010');
			$ret .= '<span class="message">'. $libelle . ' : ' . $href . '</span>' . PHP_EOL;
		}
		return $ret;
	}

	/**
	 * Affiche la boite d'exécution d'une formule
	 * 
	 * @since 1.1 déplace depuis theme.php
	 * @version 1.3.1 SG_TexteFormule
	 * @return string HTML
	 */
	static function boiteExecuterFormule() {
		$ret = '<form id="adminForm" method="get" action=""><fieldset>' . PHP_EOL;
		$txt = new SG_TexteFormule();
		$ret.= $txt -> modifierChamp(SG_Navigation::URL_VARIABLE_FORMULE) . PHP_EOL;
		$ret.= '<input type="submit" class="sg-bouton"/>' . PHP_EOL;
		$ret.= '</fieldset></form>' . PHP_EOL;
		return $ret;
	}

	/**
	 * Collection des opérations en attente de l'utilisateur
	 * 
	 * @todo est-ce encore pertinent ??
	 * @since 1.0.7
	 * @formula : @Moi.@MesOperationsEnAttente.@PourChaque(.@Lien)
	 * @param boolean $recalcul
	 * @return SG_Collection
	 */
	static function OperationsEnAttente($recalcul = true) {
		if ($recalcul || !isset($_SESSION['panels']['opa'])) {
			$_SESSION['panels']['opa'] = SG_Formule::executer('@MesOperationsEnAttente.@PourChaque(.@Lien)', $_SESSION['@Moi']);
		}
		return $_SESSION['panels']['opa'];
	}

	/**
	 * Collection des opérations suspendues de l'utilisateur
	 * 
	 * @todo est-ce encore pertinent ??
	 * @since 1.0.7
	 * @formula : @Moi.@MesOperationsSuspendues.@PourChaque(.@Lien)
	 * @param boolean $recalcul
	 * @return SG_Collection
	 */
	static function OperationsSuspendues($recalcul = true) {
		if ($recalcul or !isset($_SESSION['panels']['ops'])) {
			$_SESSION['panels']['ops'] = SG_Formule::executer('@MesOperationsSuspendues.@PourChaque(.@Lien)', $_SESSION['@Moi']);
		}
		$ret = $_SESSION['panels']['ops'];
		return $ret;
	}

	/**
	 * MenuTheme : Retourne le menu html d'un theme (gardé en cache sauf si recalcul forcé)
	 * 
	 * @since 1.0.7
	 * @param any $pTheme code du theme ou theme ou formule donnant un theme ou un code de theme
	 * @param boolean $pRecalcul : force le recalcul (défaut false)
	 * @return string code HTML : le panneau html com
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

	/**
	 * le menu HTML du theme en cours
	 * 
	 * @param boolean $recalcul
	 * @return string code du menu en cours
	 **/
	static function MenuThemeEnCours($recalcul = false) {
		if ($recalcul || !isset($_SESSION['panels']['mec'])) {
			$_SESSION['panels']['mec'] = SG_Navigation::MenuTheme('');
		}
		return $_SESSION['panels']['mec'];
	}
	
	/** 
	 * retour le code HTML des thèmes de l'utilisateur
	 * formule SG : 'MesThemes.Afficher'
	 * 
	 * @version 1.3.3 foreach
	 * @todo vérifier si le true du paramètre est nécessaire ?
	 * @param boolean $recalcul force le recalcul de la valeur (defaut true)
	 * @return string html des themes de l'utilisateur en cours
	 **/
	static function Themes($recalcul = true) {
		if ($recalcul or !isset($_SESSION['panels']['thm'])) {
			$collection = SG_Rien::MesThemes();
			if (getTypeSG($collection) === '@Erreur') {
				$ret = $collection;
			} else {
				$ret = '<div class="sg-menu">';
				foreach ($collection -> elements as $theme) {
					$ret .= $theme -> toHtml();
				}
				$ret.='</div>';
				$_SESSION['panels']['thm'] = $ret;
			}
		} else {
			$ret = $_SESSION['panels']['thm'];
		}
		return $ret;
	}

	/**
	 * Calcule les informations du pied du navigateur
	 * 
	 * @since 1.1 : ajout
	 * @version 1.3.3 debug
	 * @param boolean $recalcul
	 * @return string code HTML
	 */
	static function Pied($recalcul = true) {
		$pied = '';
		if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
			$pied.= '<ul>';
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
				$pied .='</table></li>';
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
			$pied.= '</ul>';
		}
		return $pied;
	}

	/**
	 * Affriche des informations de débogage (actuellemnt ne fait rien...)
	 * 
	 * @since 1.0.7
	 * @todo à terminer ou supprimer ?
	 * @param boolean $recalcul
	 * @formula : @Si(@Moi.@EstAdministrateur,liste vide)
	 * @return string code HTML
	 */	
	static function Debogage($recalcul = true) {
		$ret = '';
		if ($_SESSION['@Moi'] -> EstAdministrateur() -> estVrai()) {
			$ret .= '<li>' . '</li>' . PHP_EOL;
		}
		return $ret;
	}

	/** 
	 * Affiche une demande de login SynerGaïa
	 * 
	 * @since 1.0.7 dans login.php qui est supprimé
	 * @version 2.4 fusion mobile defaut
	 * @version 2.6 ajout du bouton pour éviter celui de jQuery
	 * @param string $erreurLogin
	 * @param booelan $pBody
	 * @return string Code HTML de la page de login
	 */
	static function pageLogin($erreurLogin = '', $pBody = true) {
		// préparation de l'url de retour
		$url = $_SERVER["REQUEST_URI"];
		if (strpos($url, '?') === false) {
			$url.= '?login=u';
		} else {
			$url.='&login=u';
		}
		$url = str_replace('c=mop&', '', $url); // pour obliger à tout réafficher
		// formulaire de saisie du login
		$userid = SG_SynerGaia::IdentifiantConnexion();
		if ($userid === SG_Connexion::ANONYME) {
			$userid = '';
		}
		$ret = '<div id="login-logo" class="sg-login-logo"></div><div id="login-content" class="sg-login-content">';
		$ret.= '<form id="login-form" class="sg-login-form" method="post" action="' . $url . '"><span><label>Identifiant</label>
			<input value="' . $userid . '" name="username" class="text-input text-input-login" type="text" autofocus="autofocus" placeholder="votre identifiant"/></span>
			<span><label>Mot de passe</label><input name="password" class="text-input text-input-password" type="password" placeholder="votre mot de passe" /></span>';
		if ($erreurLogin !== '') { 
			$ret .= '<div class="sg-erreur">' . SG_Texte::getTexte($erreurLogin) . '</div>';
		}
		$ret.= '<input type="submit" class="sg-bouton" title="Connexion" value="Connexion"></input>';
		$ret.= '</form></div>';
		// éventuellement, insertion dans une page entière
		if ($pBody) {
			$ret = SG_Navigation::Header() . '<body class="sg-login-body">' . $ret . '</body></html>';
		}
		return $ret;
	}

	/** 
	 * panneau des raccourcis
	 * 
	 * @since 1.0.7
	 * @param string $back
	 * @param boolean $header
	 * @param boolean $pAide
	 * @param string $pBoutons
	 * @return string 
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

	/**
	 * page d'accueil sur les mobiles
	 * 
	 * @return string HTML
	 **/
	static function pageAccueil() {
		$ret = '<div data-role="content" ' . SG_ThemeGraphique::dataTheme() . '><ul data-role="listview" data-inset="true">' . SG_Navigation::Themes();
		$ret.= '</ul></div>';
		return $ret;
	}

	/**
	 * Fabrique le bandeau d'aide de l'opération en cours
	 * 
	 * @param string|SG_Objet $pObjet
	 * @return string code HTML
	 * @uses JS SynerGaia.toggle()
	 **/ 
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
			$page = '<div id="aide-toggle" class="sg-aide-toggle noprint" onclick="SynerGaia.toggle(\'aide-contenu\');">';
			$page.= '<i>' . SG_Libelle::getLibelle('0079') . '</i>';
			$page.= '<div id="aide-contenu" data-role="page" class="sg-aide-contenu" style="display:none;">' . $texte . '</div></div>' . PHP_EOL;
		}
		return $page;
	}

	/**
	 * Affiche une page de logout
	 * 
	 * @since 1.3.2 repris de logout.php qui est abandonné
	 * @param string $pTitre
	 * @return strng code HTML
	 */
	static function pageLogout($pTitre = '') {
		$page = '';
		$page .= self::Header($pTitre);
		$page .= '<body id="logout"><div id="login-wrapper" class="png_bg"><div id="login-top"></div><div id="logout-content">
				<div>Vous êtes maintenant déconnecté.<br /><br /><a href="' . self::URL_PRINCIPALE . '">SynerGaïa</a>
				</div></div></div>';
		$page .= PHP_EOL . '</body></html>';
		return $page;
	}

	/**
	* Met à jour le principal de l'étape de l'opération passée en paramètre
	* Le principal provient, dans l'ordre, de l'url d=, des champs modifiés du masque (doc ou collec), du champ @Principal de l'opération
	* Si on ne trouve pas, on reste sur le principal de l'étape précédente dans la variable globale de session
	* 
	* @since 1.1 AJout (remplace SG_Navigation.MettreAJourLeContexte)
	* @version 1.3.0 $collec via .reduire pour Choisir
	* @param string|SG_Operation $pOperation opération en cours
	* @return boolean on a modifié le principal
	*/
	static function setPrincipal($pOperation) {
		$modif = false;
		$principal = null;
		// Si j'ai un document en paramètre	: je le prends en priorité car c'est avec lui que je vais travailler			
		$paramDoc = SG_Navigation::getParametre(SG_Navigation::URL_VARIABLE_DOCUMENT);
		if ($paramDoc !== '') {
			$doc = $_SESSION['@SynerGaia'] -> getObjet($paramDoc);
			if(is_object($pOperation)) {
				$principal = $doc;
			}
			$modif = true;
		} elseif ( ! SG_Operation::isOperation($pOperation)) {
			// je n'ai pas d'opération concernée : ce n'est pas possible
			$modif = new SG_Erreur('0042', getTypeSG($pOperation));
		} else {
			$champDocPrincipal = SG_Champ::codeChampHTML($pOperation -> reference . '/@Principal');
			if (isset($_POST[$champDocPrincipal])) {
				// cas du retour de @Choisir
				$docprincipal = $_POST[$champDocPrincipal];
				if(is_array($docprincipal)) {
					$principal = $pOperation -> getPrincipal();
					// c'est une collection de données choisies dans une liste
					if (getTypeSG($principal) === '@Collection' and sizeof($principal -> elements) > 0) {
						// si la collection est vide, c'est qu'on a la collection d'origine et on la réduit
						$collec = $principal -> reduire($docprincipal);
					} else {
						// sinon on la crée (ce qui pose problème si les doc sont nouveaux non enregistrés ou ont été modifiées)
						$collec = new SG_Collection();
						foreach ($docprincipal as $ref) {
							$objet = $_SESSION['@SynerGaia'] -> getObjet($ref);
							$collec -> Ajouter($objet);
						}
					}
					$principal = $collec;
					$modif = true;
				} else {
					// c'est une référence de document unique qui doit exister
					$doc = $_SESSION['@SynerGaia'] -> getObjet($docprincipal);
					if(is_object($doc)) {						
						$principal = $doc;
						$modif = true;
					}
				}
			}
		}
		if ($principal !== null) {
			$pOperation -> setPrincipal($principal);
		}
		return $modif;
	}

	/**
	 * Met la référence du document principal dans l'opération
	 * Si cette référece n'est pas identique à document principal de la navigation, met à jour la navigation
	 * @since 1.1 AJout (déplacé de SG_Navigation.MettreAJourLeContexte)
	 * @version 2.2 css spécifiques
	 * @param string|SG_Formule $pTitre titre de l'application 
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
		//$ret.= '<script src="' . self::URL_JS . 'moment/moment.min.js"></script>'; seulement si fullcalendar 3.4.0
		$v = 'fullcalendar';//-3.4.0';
		$ret.= '<link rel="stylesheet" type="text/css" href="' . self::URL_JS . $v . '/fullcalendar.css" />';
		$ret.= '<script src="' . self::URL_JS . $v .'/fullcalendar.min.js"></script>';
		
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
		if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/css/mobile.css')) {
			$ret.= '<link rel="stylesheet" href="var/css/mobile.css"></link>';
		}
		$ret.= '</head>';
		return $ret;
	}

	/**
	 * URL de lancement sur une autre page du navigateur
	 * @param string $url
	 * @return string code html
	 */
	static function url($url='') {
		$ret = '';
		if($url !== '') {
			$ret .= '<script type="text/javascript">document.location.href="' .$url . '";</script>';
		}
		return $ret;
	}

	/**
	 * adresse IP du serveur vu de l'extérieur
	 * @return string
	 * 
	 */
	static function AdresseIP() {
		return $_SERVER['SERVER_ADDR'];
	}

	/**
	* BODY = mise en place des éléments html pour le theme defaut
	* 
	* @version 1.1 repris de template.php périmé
	* @version 1.3.1 <admin> déplacé au dessus du <corps> ; section 'adroite'
	* @version 1.3.2 => lehaut()
	* @version 1.3.3 proportions ; réorganisation pour droite et gauche et mobile ; context menu
	* @version 1.3.4 elementsDuBody() ;
	* @version 2.0 sup menupied, abandon drag drop
	* @version 2.1 raccourcis, sup menu contextuel
	* @version 2.1.1 favori
	* @version 2.3 récup erreur ; correct form droite
	* @version 2.4 classe corps
	* @version 2.6 supp proportions en pixels : géré par des classes spécifiques
	* @param array $resultat liste des résultats calculés de l'étape
	* @param SG_Operation $operation opération en cours
	* @return string code HTML 
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
		//== PARTIE GAUCHE
		$ret.= '<div id="gauche" tabindex="-1" data-role="page" class="sg-box sg-gauche noprint">';
		// résultat d'opération dans <form> si SG_HTML à gauche
		if (isset($elements['gauche'])) {
			$ret.= '<form id="formgauche">' . $elements['gauche'] . '</form>'; //2.0 onmouseup="SynerGaia.lacher(event)
		}
		$ret.= '</div>'; // fin gauche
		//== PARTIE CENTRALE
		$ml = ((100 - $proportions[1])/2);
		$pct = ''; //'style="margin-left: ' . $ml . '%;width:' . $proportions[1] . '%;"';
		$ret.= '<div id="centre" ' . $pct . ' class="sg-box sg-centre">';
		// --- boutons raccourcis ---
		if(!$estMobile) {
			$ret.= self::Raccourcis();
			// Boite Admin
			$ret.= self::boiteAdmin($elements['pied']);
		}
		// Corps
		$ret.= '<div id="corps" class="corps" data-role="page">';
		// entête 
		if (isset($elements['op-entete'])) {
			$ret.= '<div id="op-entete" class="sg-ope-entete noprint">' . $elements['op-entete']. '</div>' . PHP_EOL;
		}
		// Aide
		$aide = '';
		if (!$estMobile and isset($elements['aide'])) {
			$aide = $elements['aide'];
		}
		$ret.= '<div id="aide" class="sg-aide noprint">' . $aide . '</div>';
		// erreurs
		if (isset($elements['erreurs'])) {
			$ret.= '<div id="erreurs" class="sg-erreurs noprint" >' . $elements['erreurs'] . '</div>' . PHP_EOL;
		}
		// contenu principal
		$ret.= '<div id="operation" class="sg-ope-contenu">' . PHP_EOL;
		if (isset($elements['operation'])) {
			if (getTypeSG($elements['operation']) === '@Erreur') {
				$ret.= $elements['operation'] -> toString();
			} else {
				$ret.= $elements['operation'];
			}
		}
		$ret.= '</div>'; // operation
		$ret.= '</div>'; // corps
		$ret.='</div>'; // fin centre

		//== A DROITE
		$ml = 100 - $proportions[2];
		$pct = 'style="margin-left: ' . $ml . '%;width:' . $proportions[2] . '%;"';
		$ret.= '<div id="droite" ' . $pct . ' class="sg-box noprint sg-droite" draggable="" data-role="page" tabindex="-1">';
		if (isset($elements['droite'])) {
			$ret.= $elements['droite'];
			//$ret.= '<form id="formdroite" >' . $elements['droite'] . '</form>';
		}
		$ret.= '</div>';
		// entourer de sg-grandcorps
		$ret = '<div id="menuetcorps" class="sg-grandcorps">' . $ret . '</div>';
		return $ret;
	}

	/**
	 * Construction d'un body en tableau associatif qui sera envoyé en JSON.
	 * Cette fonction est utilisée dès que la page complète a été affichée, pour mettre à jour uniquement les parties recalculées
	 * C'est le cas notamment pour les appels sg_get
	 * 
	 * @since 1.3.3 ajout
	 * @version 2.3 supp <br> ; pas effacer si centre vide
	 * @version 2.6 rassembler traitement gauche droite popup ; mutualiser $btntxt au début
	 * @version 2.7 correct erreur sur test $tmp->saisie
	 * @param array $resultat Résultats calculés de l'étape en cours
	 * @param SG_Operation $operation Opération courante
	 * @return array tableau des résultats pour chaque cadre du navigateur
	 */
	static function elementsDuBody($resultat, $operation) {
		$ret = array();
		if (!is_array($resultat)) {
			$resultat = array('centre' => $resultat);
		}
		// texte d'un éventuel bouton de soumission
		$btntxt = 'Enregistrer';
		if (isset($resultat['submit'])) {
			$btntxt = $resultat['submit'];
		}
		//=== ENTETE de l'opération ===  
		$ret['op-entete'] = $_SESSION['page']['entete'] . self::favori($operation);
		$ret['aide'] = $_SESSION['page']['aide'];
		//=== ERREURS ===
		$e = '';
		if ($resultat instanceof SG_Erreur) {
			$e.= $resultat -> toHTML() -> texte ;
		} elseif (isset($resultat['erreurs']) and $resultat['erreurs'] !== '') {
			if (is_string($resultat['erreurs'])) {
				$e.= '<div class="sg-erreur1">'.$resultat['erreurs'].'</div>';
			} else {
				$e.= $resultat['erreurs'] -> toString();
			}
		}
		unset($resultat['erreurs']);
		$ret['erreurs'] = $e;
		// === CENTRE ===
		$centre = '';
		$centrevide = true;
		$submit = false;
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
									if ($res -> saisie === true) {
										$submit = true;
									}
								}
							} elseif ($res instanceof SG_Collection) {
								foreach ($res -> elements as $e) {
									$tmp = $e -> toHTML();
									if ($tmp instanceof SG_HTML) {
										$tmp = $tmp -> texte;
										if ($tmp -> saisie === true) {
											$submit = true;
										}
									}
									if ($tmp !== '') {
										$centrevide = false;
										$centre .= $tmp;
									}
								}
							} else {
								$centrevide = false;
								$tmp = $res -> toHTML();
								if ($tmp instanceof SG_HTML) {
									if ($tmp -> saisie === true) {
										$submit = true;
									}
									$tmp = $tmp -> texte;
								}
								$centre .= $tmp;
							}
						} elseif (is_array($res)) {
							$centrevide = false;
							$centre.= implode('', $res);
						} else { // sinon texte au centre
							$centrevide = false;
							$centre.= $res;
						}
					}
				}
			}
		} else {
			$centrevide = false;
			$centre = $resultat;
		}
		// centre
		//  - operation
		$idForm = 'formcentre';
		$texte = '';
		if(is_null($operation)) {
			$texte = $centre;
		} elseif ($operation instanceof SG_Erreur) {
			$texte = $operation -> toHTML();
			if (is_object($texte) and $texte -> estHTML()) {
				$texte = $texte -> texte;
			}
		} else {
			$texte = self::genererBaliseForm($operation, $idForm);
			// bouton submit haut et bas, seulement si quelque chose au centre
			if(is_array($resultat)) {
				$bouton = '';
				if( ($submit or isset($resultat['submit'])) and !$centrevide) {
					$bouton = '<br>' . self::genererBoutonSubmit($btntxt, $idForm);
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
		//========== GAUCHE, DROITE, POPUP =====
		$boites = array('gauche','droite', 'popup');
		foreach ($boites as $boite) {
			$idForm = 'form' . $boite;
			$html = '';
			$submit = false; // 2.6 isset($resultat['submit']) and $centrevide;
			if(is_array($resultat)) {
				foreach($resultat as $texte) {
					if ($texte instanceof SG_HTML and $texte -> cadre === $boite) {
						$html.= $texte -> toHTML();
						if ($texte -> saisie === true) {
							$submit = true;
						}
					}
				}
				if ($html !== '' and $html !== '<ul></ul>') {
					if($submit) {
						$ret[$boite] = self::genererBaliseForm($operation, $idForm) . '<div id="f-' . $boite . '" class="sg-boite noprint">' . $html;
						$ret[$boite].= '<br>' . self::genererBoutonSubmit($btntxt, $idForm);
						$ret[$boite].= '</div></form>';
					} else {
						$ret[$boite] = $html;
					}
				}
			}
		}
		// pied avec les infos de calcul
		$ret['pied'] = self::Pied(); // benchmarck
		// et le debug
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

	/**
	 * Scripts de fin de body
	 * 
	 * @since 1.3.3 ajout
	 * @version 2.2 SynerGaia.initOnLoad
	 * @return string code html
	 * @uses SynerGaia.initOnLoad()
	 */
	static function finBody() {
		$ret = '<script>SynerGaia.initOnLoad()</script>' . PHP_EOL . '<script>';
		foreach ($_SESSION['script'] as $code => $script) {
			$ret .= $script;
		}
		$ret .= '</script></body>';
		return $ret;
	}

	/**
	 * Renvoie le chemin complet des icones 16x16 sur le serveur
	 * 
	 * @since 1.1 ajout
	 * @return string
	 */
	static function repertoireIcones() {
		return self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/';
	}

	/**
	 * Code HTML fixe pour demander l'upload de fichier
	 * @since 1.1 ajout (d'apres source originale : http://www.inserthtml.com/2012/08/file-uploader/) 
	 * @return string code HTML
	 **/
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

	/**
	 * Code HTML pour demander l'upload de fichier
	 * @since 1.1 ajout
	 */
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

	/**
	 * Affiche un bouton pour le retour à l'accueil
	 * @since 1.3.0 ajout
	 * @return string code HTML
	 */
	static function btnAccueil() {
		$ret = '<li><a href="' . SG_Navigation::URL_PRINCIPALE . '" data-icon="home" data-iconpos="notext" data-direction="reverse" data-transition="slide">Accueil</a></li>';
		return $ret;
	}

	/**
	* retourne la phrase de Logo du navigateur
	* @since 1.3.1 ajout
	* @param string $pLogo chemin du fichier pour le logo de l'application
	* @return string HTML
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

	/**
	* cadre pour les suggestions (menus déroulants)
	* @since 1.3.1 ajout
	* @return html
	**/
	static function autosuggestions() {
		$ret = '<div id="autosuggestions" class="sg-suggestions">';
		$ret.= '<div id="autosuggestions-liste" class="sg-suggestions-liste"></div>';
		$ret.= '<img class="sg-suggestions-img" onclick="$(\'#autosuggestions\').hide()"></img>';
//		$ret.= '<img src="' . self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/cancel.png" onclick="$(\'#autosuggestions\').hide()"></img>';
		$ret.= '</div>'.PHP_EOL;
		return $ret;
	}

	/** 1.3.1 ajout ; 1.3.2 rien si null ; 1.3.3 'd'
	* Permet de remplir la partie 'adroite' à droite. Le cadre est 'droite'
	* @param SG_Formule $pFormule formule donnant ce qu'il faut placer à droite
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

	/**
	* Titre de l'application
	* @since 1.3.1 ajout
	**/
	static function Titre() {
		$ret ='<div class="sg-banniere-logo">' . self::Logo() . '</div>';
		$ret.= '<div class="sg-banniere-titre">' . $_SESSION['@SynerGaia'] -> Titre() . '</div>';
		return $ret;
	}

	/**
	* calcule les onglets des themes standards
	* 
	* @since 1.3.1 déplacé de themes.php
	* @version 1.3.3 montremenu
	* @version 2.1.1 test @Erreur
	* @version 2.3 correction boucle
	* @param string $themeEnCours code du thème en cours
	* @return string code HTML
	* @uses SynerGaia.getMenu()
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

			$ongletsHTML .= '<div id="themes-liste" class="sg-menu">' . PHP_EOL;
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
					$ligneTheme .= '<div class="sg-menu-ligne" onclick="SynerGaia.getMenu(event,\'c=men&' . SG_Navigation::URL_VARIABLE_THEME . '=' . $themeCode . '\')">' . PHP_EOL;
				} else {
					// afficher la page de présentation
					$ligneTheme .= '<div class="sg-menu-ligne" onclick="SynerGaia.getMenu(event,\'c=thh&' . SG_Navigation::URL_VARIABLE_THEME . '=' . $themeCode . '\')">' . PHP_EOL;
				}
				// TODO : icone du thème : gérer le thème graphique
				if ($themeIcone !== '') {
					$ligneTheme .= ' <img class="sg-menu-ligne-img" src="' . self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/' . $themeIcone . '" alt="' . htmlentities($themeTitre, ENT_QUOTES, 'UTF-8') . '"/>' . PHP_EOL;
				}
				$ligneTheme.= $themeTitre;
				$ligneTheme.= self::MenuTheme($themeCode);
				$ligneTheme.= '</div>' . PHP_EOL;

				$ongletsHTML.= $ligneTheme . PHP_EOL;
			}
			$ongletsHTML .= ' </div>' . PHP_EOL;
		}
		$_SESSION['page']['themes'] = $ongletsHTML;
	}

	/**
	 * Calcule l'HTML du pied de navigateur (temps de calculs et autres infos techniques)
	 * @since 1.3.1 déplacé
	 * @param string $pied texte à mettre en pied
	 **/
	static function boiteAdmin($pied) {
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
			$ret.= '</ul>' . PHP_EOL;
			$ret.= '<div id="pied" class="sg-pied noprint">' . $pied . '</div>';// benchmarch et temps d'exécution
			$ret.= '<div id="debug" class="debug noprint">';
			if (isset($_SESSION['debug']['texte']) and $_SESSION['debug']['texte'] !== '') {
				$ret.= $_SESSION['debug']['texte'];
			}
			$ret.= '</div></div>';// debug, admin
		}
		return $ret;
	}

	/** 
	 * affiche Bannière et Thèmes
	 * 
	 * @since 0.0 dans fichier theme.php
	 * @version 1.0 déplacé dans Body()
	 * @version 1.3.2 déplace de Body()
	 * @version 2.3 loader
	 * @uses SynerGaia.popup()
	 **/
	static function LeHaut() {
		$ret = '<body>';
		$ret.='<div id="media" style="display:none;" class="noprint">' . SG_ThemeGraphique::ThemeGraphique() . '</div>'; // pour tester dans js;
		// Fenêtres cachées au chargement
		$ret.= self::autosuggestions() . PHP_EOL;
		// popup (fond et popup sont séparés à cause du problème d'opacité
		$ret.= '<div class="sg-popup">';
		$ret.= '<div id="popup-fond" class="sg-popup-fond noprint" onclick="SynerGaia.popup(event,\'popup\',false)"></div>
			<div id="popup" class="sg-popup-block noprint"></div>'.PHP_EOL;
		$ret.= '</div>';
		// image loader
		$ret.= '<div id="loader" class="sg-loader noprint" style="display:none"></div>'.PHP_EOL;
		// menu contextuel pour les formules
		$ret.= self::contextMenu() . PHP_EOL;
		//#### HAUT
		// Bannière
		$ret.= '<div id="banniere" class="noprint">' . self::Banniere() . '</div>';
		// Themes (préparé dans self::composerThemesDefaut())
		$ret.= '<div id="themes" class="sg-themes noprint">' . $_SESSION['page']['themes'] . '</div>';
		// pour les menus et sous-menus quand mobile
		$ret.= '<div id="menu" class="sg-menu noprint"></div>';
		$ret.= '<div id="sous-menu" class="sg-sous-menu noprint"></div>';
		$ret.= '<div id="points" class="sg-points"><img id="ptmenu"/><img id="ptsous-menu"/><img id="ptgauche"/><img id="ptcentre"/><img id="ptoperation"/><img id="ptdroite"/></div>';
		return $ret;
	}

	/** ajout 1.3.3 ; 1.3.4 $pMode ; 2.1 $element peut être string
	* @param string|SG_Document $element element visé
	* @param boolean $pMode : lien html ou lien Ajax
	* @param string $pIndex
	* @return html : lien
	**/
	static function getUrlEtape($element, $pMode = true, $pIndex = '') {
		$uid = '';
		if (is_string($element)) {
			$uid = $element;
		} elseif (method_exists($element,'getUUID')) {
			$uid = $element -> getUUID();
		}
		$op = SG_Pilote::OperationEnCours();
		$ret = self::URL_VARIABLE_OPERATION . '=' . $op -> reference;
		$ret .= '&' . self::URL_VARIABLE_ETAPE . '=' . $op -> prochaineEtape;
		// si lien réel
		if ($uid !== '') {
			$ret.= '&' . self::URL_VARIABLE_DOCUMENT . '=' . $uid;
		} elseif ($pIndex !== '') {
			$ret.= '&' . SG_Navigation::URL_VARIABLE_INDEX . '=' . $pIndex;
		}
		if ($pMode) {
			$ret = self::calculerURL($ret);
		}
		return $ret;
	}

	/** 1.3.3 ajout
	 * Etablit les largeurs en pourcentage des trois parties du corps du navigateur (par défaut 20% 60% 20%)
	 * Ceci est valable pour l'opération en cours
	 * @param integer|SG_Nombre|SG_Formule $pGauche défaut 0
	 * @param integer|SG_Nombre|SG_Formule $pCentre défaut 0
	 * @param integer|SG_Nombre|SG_Formule $pDroite défaut 0
	 * @return SG_Navigation
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

	/** 
	 * Permet de remplir la partie 'gauche' à droite. Le cadre est 'd'
	 * 
	 * @since 1.3.3
	 * @param SG_Formule $pFormule formule donnant ce qu'il faut placer à droite
	 * @return SG_HTML HTML du résultat
	 */
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

	/**
	* Pour compatibilité. Toujours @Vrai
	* @since 1.3.3 ajout
	* @return SG_VraiFaux true
	**/
	static function EstVide () {
		return new SG_VraiFaux(true);
	}

	/**
	 * Détermine si on est dans le cadre d'un thème mobile ou non
	 * @since 1.3.3 ajout
	 * @return boolean oui ou non
	 */ 
	static function estMobile() {
		return SG_ThemeGraphique::ThemeGraphique() === 'mobile';
	}

	/**
	 * calcul une url à lancer ou cliquer selon le thème graphique
	 * @since 1.3.3
	 * @version 2.0 effacer = true
	 * @param string $pURL url à lancer
	 * @param boolean $pMode true (défaut) : par http:// ; false : par sg_getLanchOperation()
	 * @return string html
	 * @uses SynerGaia.launchOperation()
	 */
	static function calculerURL($pURL = '', $pMode = true) {
		if ($pMode) {
			$ret = self::URL_PRINCIPALE . '?' . $pURL;
		} else {
			$ret = 'javascript:SynerGaia.launchOperation(event,"' . $pURL . '",null,true)';
		}
		return $ret;
	}

	/**
	 * Prépare un context menu masqué (clic droit)
	 * @since 1.3.3 ajout
	 * @return string code html
	 * @uses SynerGaia.contextMenu, SynerGaia.print(), SynerGaia.elargir(), SynerGaia.retrecir()
	 * @todo libellés en fichier
	 */
	static function contextMenu() {
		$ret = '<div id="contextMenuCorps" style="display:none" class="noprint"><ul>';
		$ret.= '<li onclick="SynerGaia.contextMenu.hide(\'contextMenuCorps\');SynerGaia.print();" id="ctxmenu_print">Imprimer</li>';
		$ret.= '<li onclick="SynerGaia.contextMenu.hide(\'contextMenuCorps\');SynerGaia.elargir();" id="ctxmenu_elargir">Élargir</li>';
		$ret.= '<li onclick="SynerGaia.contextMenu.hide(\'contextMenuCorps\');SynerGaia.retrecir();" id="ctxmenu_retrecir">Rétrécir</li>';
		$ret.= '<li onclick="SynerGaia.contextMenu.hide(\'contextMenuCorps\')" id="ctxmenu_fermer">Fermer</li>';
		$ret.= '</ul></div>';
		return $ret;
	}

	/**
	 * pour tous les objets : false sauf SG_Erreur et dérivés
	 * @since 1.3.4 ajout
	 * @return false
	 **/
	function estErreur() {
		return false;
	}

	/**
	 * génère une balise <form>
	 * @since 2.0 
	 * @version 2.1 getValeurPropriete au lieu de getValeur
	 * @param (SG_Operation) : operation encours
	 * @param (string) : id de la form
	 * @return (string) texte html
	 **/
	static private function genererBaliseForm($pOperation, $pID) {		
		$urlProchaineEtape = htmlentities($pOperation -> urlProchaineEtape(), ENT_QUOTES, 'UTF-8'); // index.php?o=operation&e=derniereetape
		$ret = '<form method="post" action="' . $urlProchaineEtape . '" enctype="multipart/form-data" id="'. $pID . '">' . PHP_EOL;
		// si affichage d'un document, mettre champ /@Principal pour identifier au retour
		// (si checkbox sur collection, est déjà présent dans la collection)
		$principal = $pOperation -> getPrincipal();
		if(getTypeSG($principal) === 'string' and $principal !== '') {
			$tmpChamp = SG_Champ::codeChampHTML($pOperation -> reference . '/@Principal');
			$ret.= '<input type="hidden" name="' . $tmpChamp . '" value="' . $principal . '"/>';
		}
		return $ret;
	}

	/**
	 * génère un bouton dans une <form>
	 * @since 2.0
	 * @version 2.3 suppr <br>
	 * @param texte ou html : texte du bouton
	 * @param id de la <form> à soumettre
	 * @return texte html du bouton
	 * @uses SynerGaia.submit()
	 */
	static private function genererBoutonSubmit ($pTexte, $pID) {
		$ret = '<button type="button" class="sg-bouton" onclick="SynerGaia.submit(event, \'' . $pID . '\')">' . $pTexte . '</button>';
		return $ret;
	}

	/**
	 * crée l'étoile de mise en favori de l'opération en cours sauf si elle est erronée ou ne correspond pas à un modèle
	 * 
	 * @since 2.1 ajout
	 * @version 2.3 htmlentities
	 * @version 2.6 classe sg-
	 * @param SG_Operation $pOperation
	 * @return string html : le texte
	 * @uses SynerGaia.favori()
	 */
	static function favori($pOperation) {
		$ret = '';
		if ($pOperation !== null) {
			$m = $pOperation -> getValeurPropriete('@ModeleOperation', '');
			if(getTypeSG($m) === '@ModeleOperation') {
				$pimg = '<img class="sg-raccourci noprint" src="' . self::URL_THEMES . 'defaut/img/icons/16x16/silkicons/';
				$mop = 'm=' . $m -> getValeur('@Code');
				$jeton = 'k=' . $_SESSION['@Moi'] -> Jeton() -> texte;
				$userid = 'u=' . $_SESSION['@Moi'] -> identifiant;
				$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . '?' . $mop . '&' . $jeton . '&' . $userid;
				$titre = $_SESSION['@SynerGaia'] -> Titre() . ' : ' . $pOperation -> Titre();
				$ret = $pimg . 'star.png" onclick="SynerGaia.favori(event,\''. $url . '\', \'' . htmlentities($titre) . '\');" title="Mettre en favori :' . htmlentities($pOperation -> Titre()) . '">' . PHP_EOL;
			}
		}
		return $ret;
	}

	/**
	 * uploader des fichiers vers un répertoire temporaire
	 * 
	 * @since 2.2 ajout
	 * @param string $pChamp : nom du champ dans lequel les images sont stockées
	 * @param string $pDir : nom du répertoire temporaire
	 * @return
	 */
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

	/** 2.4 ajout
	 * met en forme les erreurs de l'opération
	 * @param $pOperation
	 * @return : array du texte des erreurs
	 **/
	static function erreursOperation($pOperation) {
		$ret = '';
		if (!is_null($pOperation) and getTypeSG($pOperation) !== '@Erreur' and is_array($pOperation -> erreurs)) {
			foreach($pOperation -> erreurs as $key => $erreur) {
				if (getTypeSG($erreur) === '@Erreur') {
					$ret.= $erreur -> toHTML() -> texte;
				}
			}
		}
		return $ret;
	}

	/**
	 * Met le résultat des formules dans des onglets
	 * Le titre de l'onglet est recherché succéssivement dans le texte de l'argument ou dans le titre du html de l'onglet
	 * 
	 * @since 2.5
	 * @param SG_Formule premier onglet : formule donnant un SG_HTML affecté ou non d'un titre
	 * @param SG_Formule onglets suivants
	 * @return SG_HTML le texte entouré d'un cadre et affecté d'un titre
	 * @uses SynerGaia.onglet()
	 */
	static function Onglets () {
		$ret = new SG_HTML();
		if (func_num_args() > 0) {
			$id = SG_SynerGaia::idRandom();
			$args = func_get_args();
			$no = 1;
			$titres = '';
			$pages = '';
			$select = ' data-selecte="1"';
			foreach ($args as $arg) {
				// calculer l'argument
				$res = $arg;
				if (getTypeSG($arg) === SG_Formule::TYPESG) {
					$res = $arg -> calculer();
				}
				// titre de l'onglet (priorité à l'argument, puis calculé, puis fixe)
				// 1. valeur du titre
				$titre = 'Onglet ' . $no;
				if (isset($arg -> titre)) { // arg
					$titre = SG_Texte::getTexte($arg -> titre);
				} elseif (isset($arg -> proprietes['titre'])) {
					$titre = SG_Texte::getTexte($arg -> proprietes['titre']);
				} elseif (isset($res -> titre)) { // res
					$titre = SG_Texte::getTexte($res -> titre);
				} elseif (getTypeSG($res) === '@HTML' and isset($res -> proprietes['titre'])) { // res
					$titre = SG_Texte::getTexte($res -> proprietes['titre']);
				}
				// 2. remplissage de la div
				$titres.= '<div id="' . $id . '-titre-' . $no . '" class="sg-onglets-titre"' . $select;
				$titres.= ' onClick="SynerGaia.onglet(event,\'' . $id . '\',\'' . $no . '\')"';
				$titres.= ' data-no="'.$no.'">' . $titre . '</div>';
				// contenu de l'onglet
				$pages.= '<div id="' . $id . '-page-' . $no . '" class="sg-onglets-page" data-no="' . $no . '"' . $select . '>';
				if (is_array($res)) {
					foreach ($res as $r) {
						$pages.= $r -> toString();
					}
				} elseif (! is_null($res)) {
					$pages.= $res -> toString();
				}
				$pages.= '</div>';
				// onglet suivant
				$no++;
				$select = '';
			}
			$txt = '<div id="' . $id . '" class="sg-onglets">';
			$txt.= '<div class="sg-onglets-titres">' . $titres . '</div>';
			$txt.= '<div class="sg-onglets-pages">' . $pages . '</div>';
			$txt.= '</div>';
			$ret -> texte = $txt;
		}
		return $ret;
	}

	/**
	 * Transforme le résultat de l'opération en un tableau de SG_HTML trié par cadre du navigateur
	 * Tout ce qui n'est pas une instance de SG_HTML ou une SG_Collection de SG_HTML est abandonné
	 * 
	 * @since 2.6
	 * @param array $pResultat résultat de l'étape de l'opération en cours
	 * @return array tableau des HTML
	 */
	static function classerResultat($pResultat) {
		$ret = array();
		if (!is_array($pResultat)) {
			$resultat = array($resultat);
		}
		foreach($resultat as $key => $elt) {
			
		}
		return $ret;
	}

	/**
	 * Efface le contenu d'un cadre
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pCadre code du cadre à effacer
	 * @return SG_HTML cadre vide
	 */
	static function Effacer($pCadre = '') {
		$cadre = SG_Texte::getTexte($pCadre);
		$ret = new SG_HTML();
		$ret -> cadre = $cadre;
		return $ret;
	}
}
?>
