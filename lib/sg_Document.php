<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Document : classe SynerGaia de gestion d'un document en base de données
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Document_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Document_trait.php';
} else {
	trait SG_Document_trait{};
}
class SG_Document extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Document';

	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Document physique associé (@DocumentCouchDB)
	public $doc;
	
	/** 1.1 ajout Domino
	* Construction de l'objet
	*
	* @param (string) $pRefenceDocument référence du document (si '' le SG_DocumentCouchDB est créé sans id - gain de performance)
	* @param indefini $pTableau : si couchdb : tableau éventuel des propriétés du document CouchDB ou SG_DocumentCouchDB ; si domino, doc base ou code base
	* 		si document mais type d'objet différent, initialise à partir des propriétés
	*/
	public function __construct($pRefDocument = null, $pTableau = null) {
		$code = SG_Texte::getTexte($pRefDocument);
		if (strpos($code, '.nsf/') !== false or getTypeSG($pTableau) === '@DictionnaireBase') {
			$this -> initDocumentDominoDB($code, $pTableau);
		} else {
			$this -> initDocumentCouchDB($code, $pTableau);
		}
	}
		
	/** 1.0.6 ; 1.3.4 copie d'un autre objet via $pTableau ; 2.1 création sans id ; 2.2 cade base d'après @Type
	* initDocumentCouchDB : crée ou recherche le document CouchDB
	*
	* @param string $pRefenceDocument référence du document
	* @param string $pJson si on fourni directement du JSON on le construit à partir de là
	*/
	function initDocumentCouchDB($pRefDocument = null, $pTableau = null) {
		$typeTab = getTypeSG($pTableau);
		if ($typeTab === '@DocumentCouchDB') {
			$this -> doc = $pTableau;
		} else {
			if ($typeTab !== getTypeSG($this) and method_exists($pTableau, 'DeriveDeDocument') and $pTableau -> DeriveDeDocument() -> EstVrai()) {
				$tableau = $pTableau -> doc -> proprietes;
				$type = getTypeSG($this);
				$tableau['@Type'] = $type;
			} else {
				$tableau = $pTableau;
			}
			$codeBase = '';
			$codeDocument = '';
			if ($pRefDocument === '') {// 2.1 création sans id - gain de performance
				$this -> doc = new SG_DocumentCouchDB('');
			} else {
				$referenceDocument = SG_Texte::getTexte($pRefDocument);
				if ($referenceDocument !== '') {
					if (strpos($referenceDocument, '/') === false) {
						if (is_array($pTableau) and isset($pTableau['@Type'])) {
							$codeBase = SG_Dictionnaire::getCodeBase($pTableau['@Type']);
						} else {
							$codeBase = $referenceDocument;
						}
					} else {
						$elements = explode('/', $referenceDocument);
						$codeBase = $elements[0];
						if (sizeof($elements) > 1) {
							$codeDocument = $elements[1];
							if (sizeof($elements) > 2) {
								$codeDocument .= '/' . $elements[2];
							}
						}
					}
					// Si on a un doublon dans le code de base (répété au début du code du document)
					if (substr($codeDocument, 0, strlen($codeBase) + 1) === ($codeBase . '/')) {
						$codeDocument = substr($codeDocument, strlen($codeBase) + 1);
					}
				}
				$this -> doc = new SG_DocumentCouchDB($codeBase, $codeDocument, $tableau);
			}
		}
	}
	/** 1.1 ajout
	*/
	function initDocumentDominoDB ($pReferenceDocument, $pBase) {
		if (getTypeSG($pBase) === '@DictionnaireBase:') {
			$this -> doc = new SG_DocumentDominoDB($pBase, $pReferenceDocument);
		} else {
			$ref = SG_Texte::getTexte($pReferenceDocument);
			$i = strpos($ref, '.nsf/');
			$codeBase = substr($ref, 0, $i + 4);
			$unid = substr($ref, $i + 5);
			$this -> doc = new SG_DocumentDominoDB($codeBase, $unid);
		}
	}
	/** 1.1 : paramètre ; 2.1 correction si Titre rempli
	* Conversion en chaine de caractères
	*
	* @return string texte
	*/
	function toString($pDefaut = null) {
		$ret = $this -> getValeur('Titre', null);
		if($ret === null) {
			if(method_exists($this, 'Titre')) {
				$ret = $this -> Titre();
			} else {
				$ret = '';
			}
		}
		$ret = SG_Texte::getTexte($ret);
		if ($ret === '') {
			$ret = $this -> getValeur('@Titre', '');
			if ($ret === '') {
				if ($pDefaut !== null) {
					$ret = SG_Texte::getTexte($pDefaut);
				} else {
					$ret = $this -> getValeur('Code', '');
					if ($ret === '') {
						$ret = $this -> getValeur('@Code', '');
						if ($ret === '') {
							$type = $this -> getValeur('@Type', '');
							$ret = (($type === '') ? 'Document' : $type) . ' sans titre';
						}
					}
				}
			}
		}
		return $ret;
	}

	/** 1.1 : paramètre ; 2.0 correction
	 * Conversion en code HTML
	 *
	 * @return string code HTML
	 */
	function toHTML($pDefaut = null) {
		return $this -> toString('');
	}

	/** 1.0.5 ; 2.0 traitement propriétés locales
	 * Définition de la valeur d'un champ du document. Si une valeur locale existe, c'est celle-là qui est mise à jour.
	 *
	 * @param string $pChamp code du champ
	 * @param indéfini $pValeur valeur du champ
	 * @param boolean $forceFormule pour éviter de calculer une formule si c'est une formule qu'on veut stocker
	 */
	public function setValeur($pChamp = '', $pValeur = null, $forceFormule = false) {
		$champ = SG_Texte::getTexte($pChamp);
		$valeur = $pValeur;
		$tmpTypeValeur = getTypeSG($pValeur);
		if ($tmpTypeValeur === '@Formule') {
			if ($forceFormule === false) {
				$valeur = $pValeur -> calculer();
			} else {
				// pour éviter les récursion au json_encode (car si c'est une opération, elle contient la formule...)
				$valeur -> objet = null;
				$valeur -> objetPrincipal = null;
				$valeur -> formuleparent = null;
			}
		}
		$ret = null;
		if ($champ !== '') {
			if (getTypeSG($valeur) !== '@Erreur') {
				if(isset($this -> proprietes[$champ])) {
					$this -> proprietes[$champ] = $valeur;
					$ret = $valeur;
				} else {
					$ret = $this -> doc -> setValeur($champ, $valeur, $forceFormule);
				}
			} else {
				if ($champ === '@Erreur') {
					$ret = $this -> doc -> setValeur($champ, $valeur, $forceFormule);
				} else {
					$ret = $this -> doc -> setValeur('@Erreur', $valeur -> toString());
					$ret = $this -> doc -> setValeur($champ, '');
				}
			}
		}
		return $ret;
	}

	/** 1.1 $pType
	* Définition du contenu de type "Fichier" d'un champ du document
	* @param string $pChamp code du champ
	* @param string $pEmplacement emplacement du fichier
	* @param string $pNom nom du fichier
	* @param strint $pType type du fichier (seuls utilisés : image/jpeg, image/png, image/gif pour créer vignette)
	**/
	public function setFichier($pChamp = null, $pEmplacement = '', $pNom = '', $pType = '') {
		$ret = null;
		if ($pEmplacement !== '') {
			$ret = $this -> doc -> setFichier($pChamp, $pEmplacement, $pNom, $pType);
		}
		return $ret;
	}

	/** 1.0.7
	 * Lecture de la valeur d'un champ du document, c'est à dire son contenu brut
	 * on commence par les propriétés à la volée du @Document puis on va dans le document physique
	 * @param string $pChamp code du champ
	 * @param indéfini $pValeurDefaut valeur si le champ ou le document physique n'existe pas
	 *
	 * @return indéfini valeur du champ
	 */
	public function getValeur($pChamp = null, $pValeurDefaut = null) {
		$ret = $pValeurDefaut;
		if(isset($this->proprietes[$pChamp])) { // propriétés temporaires
			$ret = $this -> proprietes[$pChamp];
		} elseif (is_object($this -> doc)) { //propriétés stockées
			$ret = $this -> doc -> getValeur($pChamp, $pValeurDefaut);
		}
		return $ret;
	}
	
	/**
	 *  Acquisition du contenu d'un champ fichier et stockage dans une destination
	 * 
	 *  @param string $pChamp nom du champ dans lequel se trouve le fichier
	 *  @param string $pFichier nom du fichier à récupérer
	 *  @param string $pDestination répertoire de destination (par défaut ./tmp)
	 */
	public function DetacherFichier($pChamp = null, $pFichier = '', $pDestination = '/tmp') {
		return $this -> doc -> DetacherFichier($pChamp, $pFichier, $pDestination);
	}
	
	/** 1.1 simplification ; 1.3.0 accepte modele null) ; 2.2 categorie ; 2.3 cas texte multiple
	 * Lecture de la valeur d'une propriété du document : retourne un objet SynerGaïa
	 *
	 * @param string $pChamp code de la propriété (défaut null)
	 * @param indéfini $pValeurDefaut valeur de la propriété si le champ n'existe pas (défaut null)
	 * @param string $pModele modele imposé pour la propriété recherchée (défaut null)
	 *
	 * @return indéfini valeur de la propriete sous forme d'objet SynerGaïa
	 */
	public function getValeurPropriete($pChamp = null, $pValeurDefaut = null, $pModele = '') {
		// simplifie la valeur par defaut et la met dans $valeurPropriete
		$defaut = $pValeurDefaut;
		if(is_string($pValeurDefaut)) {
			$defaut = new SG_Texte($pValeurDefaut);
		} elseif (is_array($pValeurDefaut) and count($pValeurDefaut) <= 1) {
			$defaut = new SG_Texte(current($pValeurDefaut));
		}		
		$ret = $defaut;
		$valeurPropriete = $this -> getValeur($pChamp, $defaut);
		// Cherche le modèle de la propriété
		$typeObjet = getTypeSG($this);
		$modelePropriete = $pModele;
		if ($modelePropriete === null or $modelePropriete === '') {
			// Cherche si un champ "@Type_?" existe, contenant le type de propriété (uniquement sur les "@Operation")
			$proprieteSurchargee = false;
			if (SG_Operation::isOperation($this)) {
				$proprieteSurchargeeType = $this -> getValeur('@Type_' . $pChamp, '');
				if ($proprieteSurchargeeType !== '') {
					$proprieteSurchargee = true;
				}
			}

			if ($proprieteSurchargee === true) {
				$modelePropriete = $proprieteSurchargeeType;
			} else {
				$modelePropriete = SG_Dictionnaire::getCodeModele($typeObjet . '.' . $pChamp, false);
			}
		}
		$proprieteMultiple = SG_Dictionnaire::isMultiple($typeObjet . '.' . $pChamp);
		if ($proprieteMultiple === true) {
			if ($modelePropriete === '@Categorie') {
				$ret = new SG_Categorie($valeurPropriete);
				$ret -> contenant = $this;
				$ret -> index = $this -> getUUID() . '/' . $pChamp;
			} else {
				// On a une propriété multiple
				$ret = new SG_Collection();
				// Si on a une valeur unique, on en fait un tableau
				if (getTypeSG($valeurPropriete) != 'array') {
					// Sauf si on a une valeur null
					if (gettype($valeurPropriete) === 'NULL') {
						$valeurPropriete = array();
					} else {
						$valeurPropriete = array($valeurPropriete);
					}
				}

				$nbValeurs = sizeof($valeurPropriete);
				if ($nbValeurs === 0) {
					// Aucune valeur enregistrée
				} else {
					// Boucle sur les valeurs enregistrées
					for ($numValeur = 0; $numValeur < $nbValeurs; $numValeur++) {
						$element = SG_Rien::creerObjet($modelePropriete, $valeurPropriete[$numValeur]);
						$element -> contenant = $this;
						$element -> index = $this -> getUUID() . '/' . $pChamp . '(' . $numValeur . ')';
						$ret -> Ajouter($element);
					}
				}
			}
			$ret -> titre = $pChamp;
		} else {
			if ($valeurPropriete === null) {
				$ret = new SG_Rien();
			} else {
				// On n'a pas une propriété multiple
				$ret = SG_Rien::creerObjet($modelePropriete, $valeurPropriete);
				$ret -> contenant = $this;
				$ret -> index = $this -> getUUID() . '/' . $pChamp;
			}
		}
		return $ret;
	}

	/** 2.1 creer si null et force
	* Lecture du code du document
	* @param (boolean) $pForce : (true) créer l'ID si vide, (false defaut) rendre même si vide
	* @return string code du document
	*/
	public function getCodeDocument($pForce = false) {
		return $this -> doc -> getCodeDocument($pForce);
	}

	/** 1.0.7 ; 2.3 @param 1 et 2
	 * Document existe ?
	 *
	 * @return SG_VraiFaux document existe
	 */
	public function Existe($pChamp = null, $pValeur = null) {
		$ret = new SG_VraiFaux(false);
		if ($pChamp === null) {
			if (isset($this -> doc)) {
				$ret = $this -> doc -> Existe();
			}
		} else {
			$champ = SG_Texte::getTexte($pChamp);
			$valeur = SG_Texte::getTexte($pValeur);
			$collec = $_SESSION['@SynerGaia'] -> sgbd -> getDocumentsParChamp(getTypeSG($this), $champ, $valeur);
			if (getTypeSG($collec) === '@Collection') {
				if (sizeof($collec -> elements) > 0) {
					$ret = new SG_VraiFaux(true);
				}
			} else {
				$ret = $collec; // erreur
			}
		}
		return $ret;
	}

	/** 1.1 traite ce qui revient de ChercherVue
	 * Lecture de l'UUID du document (peut venir des propriétés provisoires)
	 *
	 * @return string UUID du document
	 */
	public function getUUID() {
		$ret = '';
		if (isset($this -> doc)) {
			if (getTypeSG($this -> doc) === '@HTML') {
				$ret = $this -> site -> getValeur('@Code', '') . ':' . $this -> url;
			} else {
				$ret = $this -> doc -> codeBase . '/' . $this -> doc -> codeDocument;
			}
		} elseif (isset($element -> proprietes['_id'])) {
			$ret = $element -> proprietes['_id'];
		}
		return $ret;
	}

	/** 1.3.2 : si valeur numérique, n° de colonne des propriétés du tableau
	 * Champ du document
	 *
	 * @param $pCodeChamp indefini code du champ
	 *
	 * @return SG_Champ contenu associé
	 */
	public function Champ($pCodeChamp = '') {
		$ret = null;
		$tmpCodeChamp = $pCodeChamp;
		if(getTypeSG($pCodeChamp) === '@Formule') {
			$tmpCodeChamp = $pCodeChamp -> calculer();
		}
		if(getTypeSG($tmpCodeChamp) === '@Nombre') {
			$ret = $this -> getValeur('' . ($tmpCodeChamp -> valeur + 1),'');
		} else {
			$codeChamp = SG_Texte::getTexte($pCodeChamp);
			if ($codeChamp !== '') {
				$ret = new SG_Champ($this -> getUUID() . '/' . $codeChamp, $this);
			}
		}
		return $ret;
	}

	/** 2.1 supp test sur doc provisoire ; sup appelmethodesenregistrer (traité dans les méthodes spécifiques des objets) ; php titre
	* 1.3.1 précalcul de @Titre si méthode Titre ; 1.3.2 $pCalculTitre : 1.3.4 retour $this ou SG_Erreur ; 
	* Enregistrement du document
	*
	* @param $pAppelMethodesEnregistrer boolean appel des méthodes Enregistrer et @Enregistrer
	* @return SG_VraiFaux résultat de l'enregistrement
	*/
	public function Enregistrer($pAppelMethodesEnregistrer = true, $pCalculTitre = true) {
		$ret = $this;
		$ok = null;
		// exécution de la méthode des classes dérivées
		if (method_exists($this, 'FN_preEnregistrer')) {
			$ok = $this -> FN_preEnregistrer();
		} elseif (method_exists($this, 'preEnregistrer')) {
			$ok = $this -> preEnregistrer();
		}
		if (getTypeSG($ok) === '@Erreur') {
			$ret = $ok;
		} else {
			$typeObjet = $this -> getValeur('@Type', '');
			// si l'objet n'a pas de propriété Titre mais une méthode Titre, on la calcule (pour les vues stockées)
			if ($pCalculTitre === true) {
				if (!(SG_Dictionnaire::isProprieteExiste($typeObjet,'Titre') or SG_Dictionnaire::isProprieteExiste($typeObjet,'@Titre'))) {
					$titre = '';
					if(method_exists($this, 'FN_Titre')) {
						$titre = $this -> FN_Titre();
					} elseif (method_exists($this, 'Titre')) {
						$titre = $this -> Titre();
					}
					$this -> setValeur('@Titre', $titre);
				}
			}
			// Définit les propriétés @DateCreation, @AuteurCreation, @DateModification et @AuteurModification
			$maintenant = new SG_DateHeure(time());
			$utilisateur = SG_SynerGaia::IdentifiantConnexion();
			$this -> setValeur('@DateModification', $maintenant);
			if ($this -> getValeur('@DateCreation', '') === '') {
				$this -> setValeur('@DateCreation', $maintenant);
			}
			$this -> setValeur('@AuteurModification', $utilisateur);
			if ($this -> getValeur('@AuteurCreation', '') === '') {
				$this -> setValeur('@AuteurCreation', $utilisateur);
			}
		}
		$err = '';
		if (! $ret -> estErreur()) {
			$retenr = $this -> doc -> Enregistrer();
			if ($retenr -> estErreur()) {
				$ret = $retenr;
			} else {
				// exécution de la méthode des classes dérivées sinon classe SynerGaia
				if (method_exists($this, 'FN_postEnregistrer')) {
					$ret = $this -> FN_postEnregistrer();
				} elseif (method_exists($this, 'postEnregistrer')) {
					$ret = $this -> postEnregistrer();
				}
			}
		}
		if (is_object($ret) and (! $ret -> estErreur())) {
			$ret = $ok;
		}
		return $ret;
	}
	/** 1.0.6
	* preEnregistrer : fonction éventuellement créée et exécutée dans les classes dérivées au début de la méthode SG_Document -> Enregistrer()
	function preEnregistrer() {
	}
	*/

	/** 2.3 ajout @param
	* Suppression du document
	* @param $pBase : permet de forcer le code de la base dans les cas où ce code n'aurait pas été correct
	* @return SG_VraiFaux résultat de la suppression
	*/
	public function Supprimer($pBase = '') {
		$base = SG_Texte::getTexte($pBase);
		if ($base !== '' and $this -> doc -> codeBase !== $base) {
			$this -> doc -> setBase($base);
		}
		return $this -> doc -> Supprimer();
	}

	/** 2.1 si parametre est string (donc on est en interne), les fichiers visibles
	* 1.1 : titre non affiché si vide, <h2> toujours ; 
	* 1.3.1 isEmpty ; $valeur est object ; 1.3.2 toggle $infosdoc, correction ligne 500 calculerSUR() ; 1.3.3 seulement champs non vides
	* 1.3.4 test $codedocument existe, condensé $infos ; supp lignes vides
	* Affichage du document
	*
	* @return string contenu HTML affichable
	*/
	public function Afficher() {
		$ret = '';
		// Traite les parametres passés
		$formule = '';
		$formuleorigine = null;
		$nbParametres = func_num_args();

		if ($nbParametres === 0) {
			// Aucun paramètre fourni => affiche tous les champs
			$titre = $this -> toHTML('');
			if($titre !== '') {
				$ret .= '<h1>' . $titre . '</h1>';
			}
			$modele = getTypeSG($this);
			$listeChamps = SG_Dictionnaire::getListeChamps($modele);
			// 1.3.1 si pas de doc physique, 
			$ret .= '<ul data-role="listview">';
			foreach ($listeChamps as $codeChamp => $modeleChamp) {
				if ($codeChamp !== '_rev') {
					if($this -> getValeur($codeChamp,'') !== '') {
						$tmpChamp = new SG_Champ($this -> getUUID() . '/' . $codeChamp, $this);
						if ($tmpChamp -> isEmpty() === false) {
							$tmpChamp = $tmpChamp -> Afficher();
							if ($tmpChamp !== '') {
								$ret .= '<li class="sg-lignechamp">' . $tmpChamp . '</li>';
							}
						}
					}
				}
			};
			
			// traitement des champs qui ne sont pas dans le dictionnaire (on ne prend pas les propriétés temporaires) (infos)
			$autres = '';
			$infos = '';
			if (is_array($this -> doc -> proprietes)) {
				foreach ($this -> doc -> proprietes as $key => $valeur) {
					$libelle = $key;
					$texte = '';
					if (! array_key_exists($key, $listeChamps)) {
						if($key === '_attachments') {
							// 1.3.4 attachement des fichiers ; 2.1 dans $ret
							$libelle = SG_Libelle::getLibelle('0095', false);
							$objetfichiers = new SG_Fichiers($this -> doc);
							$ret.= '<li class="sg-lignechamp"><span class="sg-titrechamp">'. $libelle . '</span> :'. $objetfichiers -> afficherChamp() . '</li>';
						} elseif (is_object($valeur)) {
							$texte = $valeur -> toString();
						} elseif (is_array($valeur)) {
							// objet composite
							if(sizeof($valeur) <= 1) {
								$texte = '';
								$avant = '';
								$apres = '';
							} else {
								$texte = '<ul class="adresse">';
								$avant = '<li class="sg-lignechamp">';
								$apres = '</li>';
							}
							foreach($valeur as $val) {
								if(is_object($val)) {
									$texte.= $avant . $val -> toString() . $apres;
								} elseif (is_array($val)) {
									try {
										$texte.= $avant . implode($val, ', ') . $apres;
									} catch (Exception $e){
										$texte.= $avant . json_encode($val) . $apres;
									}
								} else {
									$texte.= $avant . $val . $apres;
								}
							}
							if($avant !== '') {
								$texte .= '</ul>';
							}
						} else {
							$texte = $valeur;
						}
						if($texte !== '') {
							$autres.= '<li><span class="sg-titrechamp">' . $libelle . '</span> : ' . $texte . '</li>';
						}
					}
				}
			}
			if ($autres !== '') {
				$infos = '<div id="infosdoc" class="autreschamps noprint" style="display:none"><ul data-role="listview">';
				/* // id du document
				if (isset($this -> doc -> codeDocument)) {
					$infos.= '<li class="sg-lignechamp"><span class="sg-titrechamp">_id</span> : ' . $this -> doc -> codeDocument . '</li>';
				}*/
				$infos.= $autres . '</ul></div>';
			}
			if($infos !== '') {
				$ret.= '<li class="sg-lignechamp"><a class="sg-titrechamp noprint" title="Affiche les informations administratives du document" onclick="SynerGaia.montrercacher(\'infosdoc\', \'infosdoc_triangle\');">';
				$ret.= 'Administration du document <img id="infosdoc_triangle" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/nav/next.png"></img></a></li>';
				$ret.= $infos;
			}
			$ret .= '</ul>';
		} else {
			// on a une liste de paramètres
			$ret .= '<ul data-role="listview">';
			$parametres = func_get_args();
			$resultats = array();
			foreach($parametres as $parametre) {
				// calcule la valeur de chaque paramètre
				if (is_string($parametre)) {
					$element = $this -> get($parametre);
				} elseif (getTypeSG($parametre) === '@Formule') {
					$element = $parametre -> calculerSur($this);
				} else {
					$element = $parametre;
				}
				$texte = '';
				if ($element) {
					if (isset($element -> contenant)) {
						$tmpChamp = new SG_Champ($element -> index, $element -> contenant);
						if ($tmpChamp -> isEmpty() === false) {
							$texte = $tmpChamp -> Afficher();
						}
					} elseif(getTypeSG($element) === '@Collection') {
						$tmpChamp = new SG_Champ();
						$tmpChamp -> contenu = $element;
						$tmpChamp -> libelle = $element -> titre;
						
						if ($tmpChamp -> isEmpty() === false) {
							$texte = $tmpChamp -> Afficher();
						}
					} else {
						$texte = $element -> Afficher();
						if(is_object($texte)) {
							$texte = $texte -> texte;
						}
					}
					if ($texte !== '') {
						$ret .= '<li class="sg-lignechamp">' . $texte . '</li>';
					}
				}
			}
			$ret .= '</ul>';
		}
		SG_Navigation::OperationEnCours() -> setValeur('@Principal', $this -> getUUID());
		if (getTypeSG($ret) !== '@HTML') {
			$ret = new SG_HTML($ret);
		}
		return $ret;
	}
	/** 1.1 : afficher titre si non vide, h2 toujours ; 1.3.1 annulation modif 1.1 (suite à création @Formulaire) ; 1.3.4 @Fichiers _attachments
	 * Affichage du document
	 *
	 * @return string contenu HTML affichable
	 */
	public function AfficherChamps() {
		$ret = '';
		// Traite les parametres passés
		$formule = '';
		$formuleorigine = null;
		$nbParametres = func_num_args();
		if ($nbParametres === 0 ) {
			// Aucun paramètre fourni => affiche tous les champs
			$champs = SG_Dictionnaire::getListeChamps(getTypeSG($this));
			// Transforme la liste des champs en proprietes
			$listeChamps = array();
			foreach($champs as $key => $modele) {
				$listeChamps[] = $key;
			}
		} else {
			$listeChamps = array();
			for ($i = 0; $i < $nbParametres; $i++) {
				$parametre = func_get_arg($i);
				if (getTypeSG($parametre) === '@Formule') {
					$formule = $parametre;					
					$formuleorigine = $parametre;
				} else {
					$formule = new SG_Formule(SG_Texte::getTexte($parametre), $this, null, $formuleorigine);
				}
				$champ = $formule -> calculer();
				if (getTypeSG($champ) !== 'string') {
					$champ = $champ -> toString();
				}
				$listeChamps[] = $champ;
			}
		}
		if (SG_ThemeGraphique::ThemeGraphique() === 'mobilex') {
			$ret .= '<ul data-role="listview">';
		} else {
			$ret .= '<ul>';
		}
		// 1.3.4 attachement des fichiers
		if (isset($this -> doc -> proprietes['_attachments'])) {
			if(!in_array('@Fichiers', $listechamps)) {
				$listechamps[] = '@Fichiers';
			}
		}
		foreach ($listeChamps as $champs) {
			$champs = explode(',', $champs);
			foreach($champs as $codeChamp) {
				if ($codeChamp !== '_rev') {
					$tmpChamp = new SG_Champ($this -> getUUID() . '/' . $codeChamp);
					$valeur = $tmpChamp -> toString();
					if ($valeur !== null and $valeur !== '') {
						$texte = $tmpChamp -> Afficher();
						$ret .= '<li>' . $tmpChamp -> Afficher() . '</li>';
					}
				}
			}
		}
		$ret .= '</ul>';
		return $ret;
	}
	/** 1.1 natcasesort ; 1.3.1 valeurs possibles param 2 ; 2.0 affichage des valeurs sélectées
	* Modification d'un champ de type @Document (lien vers un ou plusieurs documents)
	* @param $codeChampHTML (string) : code du champ
	* @param $pListeElements (@Collection) : liste des valeurs possibles (par défaut toutes)
	* @return string contenu HTML affichable / modifiable
	*/
	function modifierChamp($codeChampHTML = '', $pListeElements = null) {
		$modele = getTypeSG($this);
		$codeBase = SG_Dictionnaire::getCodeBase($modele);
		// Calcule la liste des documents du modèle et trie le tableau
		if (is_null($pListeElements)) {
			$listeElements = SG_Rien::Chercher($modele);
		} else {
			if (getTypeSG($pListeElements) === '@Formule') {
				$listeElements = $pListeElements -> calculer();
			} else {
				$listeElements = $pListeElements;
			}
			if (getTypeSG($listeElements) !== '@Collection') {
				$listeElements = new SG_Collection();
			}
		}
		$idChamp = SG_Champ::idRandom();
		$choix = array();
		foreach($listeElements -> elements as $element) {
			$choix[] = $element -> toString() . '|' . $element -> doc -> codeDocument;
		}
		// Genere la liste des documents proposés
		$listeSelection = '<select class="champ_Lien" type="text" name="' . $codeChampHTML . '" onchange="SynerGaia.changeSelected(event, \'' . $idChamp . '\', \'s\');">';

		// Propose le choix par défaut (vide)
		$listeSelection .= '<option value="">(aucun)</option>';
		
		// Met tout en liste déroulante
		natcasesort($choix);
		$code = $this -> doc -> codeDocument;
		$selected = array();
		foreach ($choix as $elt) {
			$element = explode('|', $elt);
			$texte = $element[0];
			$refDocument = $element[sizeof($element) - 1];
			$listeSelection .= '<option value="' . $codeBase . '/' . $refDocument . '"';
			if (SG_Champ::idIdentiques($refDocument, $code)) {
				$listeSelection .= ' selected="selected"';
				$selected[] = $texte;
			}
			$listeSelection .= '>' . $texte . '</option>';
		}
		$listeSelection .= '</select>';
		// prépare le champ final
		$ret = '<div id="' . $idChamp .'"><span id="' . $idChamp . '"_val" class="selectedvalues">';
		$ret.= implode(',', $selected);
		$ret.= '</span>' .$listeSelection;
		$ret.= '</div>';
		$_SESSION['saisie'] = true;
		return $ret;
	}

	/** 1.3.1 rien si n'existe pas
	 * Affichage d'un champ de type @Document
	 *
	 * @return string contenu HTML affichable
	 */
	function afficherChamp() {
		if($this -> Existe() -> estVrai()) {
			$ret = $this-> LienVers($this -> toString(), 'DocumentConsulter');
		} else {
			$ret = '';
		}
		return $ret;
	}

	/** 2.1 Champ::txtModifier, parametre->methode , paramètres string ; 2.3 traitement propriétés éloignées ; titre seult si ss parm
	* 1.1 : titre non affiché si vide, <h2> toujours ; 1.3.1 trim($propriete) ; formule de valeurs possibles ; 1.3.4 @Fichiers
	* Modification du document
	* @param (any) liste facultative de paramètres donnant les propriétés ou formules à afficher
	* @return string contenu HTML affichable / modifiable
	*/
	public function Modifier() {
		$ret = $this -> getChampEnregistrer(); // ajouter un champ pour indiquer qu'on enregistrera la saisie
		// Traite les parametres passés
		$listeProprietes = array();
		$nbParametres = func_num_args();
		if ($nbParametres !== 0) {
			// on a une liste de paramètres
			for ($i = 0; $i < $nbParametres; $i++) {
				$parametre = func_get_arg($i);
				if (is_string($parametre)) {
					$listeProprietes[] = $parametre;
				} elseif (getTypeSG($parametre) === '@Formule') {// vient d'une formule
					if ($parametre -> fonction !== '' or $parametre -> php !== '') {
						$listeProprietes[] = $parametre;// -> methode; // 2.1
					} else {
						$listeProprietes[] = $parametre -> formule;
					}
				} else {
					$listeProprietes[] = SG_Texte::getTexte($parametre);
				}
			}
		} else {
			// titre du document
			$titre = $this -> toHTML('');
			if($titre !== '') {
				$ret .= '<h2>' . $titre . '</h2>';
			}
			// si aucun : recupere la liste complete des champs du document
			$listeChamps = SG_Dictionnaire::getListeChamps(getTypeSG($this));
			// Transforme la liste des champs en formules de propriete
			foreach($listeChamps as $key => $modele) {
				$listeProprietes[] = '.' . $key;
			}
			$listeProprietes[] = '.@Fichiers';
		}
		$ret .= '<ul data-role="listview">';
		// affichage des autres champs
		$proprietesNonModifiables = array('.@Type', '.@Erreur', '.@DateCreation', '.@DateModification', '.@AuteurCreation', '.@AuteurModification');
		foreach ($listeProprietes as $propriete) {
			$index = '';
			$doc = null;
			if (getTypeSG($propriete) === '@Formule') {
				$tmp = $propriete -> calculerSur($this);
				$propriete = $propriete -> methode;
			//	$index = $propriete -> index;
				$doc = $tmp -> contenant;
			}
			// extraction éventuelle du libellé (derrière le ':')
			$libelle = null;
			if (strpos($propriete, ':') !== false) {
				list($propriete, $libelle) = explode(':', $propriete);
			}
			$propriete = trim($propriete);
			if (!in_array($propriete, $proprietesNonModifiables)) {
				// Supprime le '.' au début de la propriété (pirouette)
				if(substr($propriete, 0, 1) === '.') { // 2.1
					$propriete = substr($propriete, 1);
				}
				if ($propriete === '@Fichiers') {
					// cas des fichiers rattachés
					$tmpChamp = new SG_Fichiers($this -> doc);
				} else {
					// cas d'une formule de valeurs possibles derrière la propriété
					$parenth = strpos($propriete, '(');
					if($parenth !== false) {
						$valeurspossibles = new SG_Formule(substr($propriete, $parenth + 1, -1), $this);
						$propriete = substr($propriete, 0, $parenth);
					} else {
						$valeurspossibles = null;
					}
					// préparation du champ à modifier
					if ($index === '') { // c'est le document en cours
						$index = $this -> getUUID() . '/' . $propriete;
						$doc = $this;
					}
					$tmpChamp = new SG_Champ($index, $doc);
					if (!is_null($libelle)) {
						$tmpChamp -> libelle = $libelle;
					}
				}
				$ret .= '<li class="sg-lignechamp">' . $tmpChamp -> txtModifier($valeurspossibles) . '</li>';
			}
		}
		$ret .= '</ul>';
		$opEnCours = SG_Navigation::OperationEnCours();
		if ($this -> Existe() -> estVrai()) {
			$opEnCours -> setValeur('@Principal', $this -> getUUID());
		} else {
			$opEnCours -> doc -> proprietes['@Principal'] = $this;
		}
		$_SESSION['saisie'] = true;
		return new SG_HTML($ret);
	}

	/** 1.0.7
	 * Duplication du document
	 * @param indefini $pIdDocument liste des champs à reprendre
	 * @return SG_Document nouveau document
	 */
	public function Dupliquer() {
		$codeBase = $this -> doc -> codeBase;
		$codeDocument = $codeBase . '/';

		$ret = new SG_Document($codeDocument);

		$type = $this -> getValeur('@Type');

		// Définition systématique du type du nouvel objet
		$ret -> setValeur('@Type', $type);

		// Traite les parametres passés
		$listeProprietes = array();
		$nbParametres = func_num_args();
		if ($nbParametres !== 0) {
			for ($i = 0; $i < $nbParametres; $i++) {
				$parametre = func_get_arg($i);
				if (getTypeSG($parametre) === '@Formule') {
					$listeProprietes[] = $parametre -> formule;
				} else {
					$listeProprietes[] = SG_Texte::getTexte($parametre);
				}
			}
		} else {
			// Recupere la liste complete des champs du document
			$listeChamps = SG_Dictionnaire::getListeChamps($type);
			// Liste des champs non dupliqués automatiquement
			$champsNonDupliques = array('@Erreur', '@DateCreation', '@DateModification', '@AuteurCreation', '@AuteurModification');
			// Transforme la liste des champs en proprietes
			foreach($listeChamps as $key => $modele) {
				if (! array_key_exists($key, $champsNonDupliques)) {
					$listeProprietes[] = $key;
				}
			}
		}

		$nbProprietes = sizeof($listeProprietes);
		for ($i = 0; $i < $nbProprietes; $i++) {
			// Supprime le '.' au début de la propriété (pirouette)
			$codeChamp = substr($listeProprietes[$i], 1);

			$valeurChamp = $this -> getValeur($codeChamp, null);
			if ($valeurChamp !== null) {
				$ret -> setValeur($codeChamp, $valeurChamp);
			}
		}
		$ret -> Enregistrer();
		return $ret;
	}

	/**
	 * Comparaison à un autre document
	 *
	 * @param indéfini $pQuelqueChose objet avec lequel comparer
	 * @return SG_VraiFaux vrai si les deux documents sont identiques
	 */
	function Egale ($pQuelqueChose) {
		$type = getTypeSG($pQuelqueChose);
		$doc = $pQuelqueChose;
		if ($type === '@Formule') {
			$doc = $pQuelqueChose -> calculer();
			$type = $type = getTypeSG($doc);
		}
		if ($doc -> typeSG !== '@Document') {
			$doc = new SG_Document($doc);
		}
		$ret = new SG_VraiFaux($this -> getUUID() === $doc -> getUUID());
		return $ret;
	}
	/*
	 * Faire suivre l'affichage d'un document dans un memo
	 * @param indéfini $pDestinataires
	 * @param indéfini $pObjet objet du message
	 * @param indéfini $pModele modèle d'opération à utiliser pour le lien vers le document
	 * @param indéfini $pImmediat : envoi immédiat (vrai) ou non. par défaut non
	 */
	function FaireSuivre ($pDestinataires = '', $pObjet = '', $pModele = '', $pImmediat = false, $pMethode = '') {
		$memo = new SG_Memo();
		$memo -> AjouterDestinataire($pDestinataires);
		$memo -> DefinirObjet (SG_Texte::getTexte($pObjet));
		$modele = SG_Texte::getTexte($pModele);
		$html = '<h2>Veuillez prendre connaissance du document ci-dessous</h2>';
		
		if ($modele !== '' ) {
			$html .= '<br><i>(vous pouvez l\'afficher via le lien ci-contre :';
			$html .= '<a href="http://' . SG_Navigation::AdresseIP() . '/' . SG_Connexion::Application() . '/' . SG_Navigation::URL_PRINCIPALE . '?';
			$html .= 'd=' . $this -> getUUID() . '&m=' . $modele . '">Clicquez ici</a></i><br>';
		}
		$html .= '<br><div id="contenu"><table style="bgcolor: #eeeeee"><tr><td>';
		if ($pMethode === '') {
			$html .= $this -> Afficher();
		} elseif (getTypeSG($pMethode) === '@Formule') {			
			$pMethode -> objet = $this;
			$pMethode -> objetPrincipal = $this;
			$texte = SG_Formule::executer($pMethode -> formule, $this, $this, $pMethode);
			if (getTypeSG($texte) !== 'string') {
				$texte = $texte -> toHTML();
			}
			$html .= htmlspecialchars($texte);
		} else {
			journaliser('fairesuivre : $pMéthode = ' . getTypeSG($pMethode));
		}
		$html .= '</td></tr></table></div>';
		$memo -> DefinirContenu($html);
		
		$immediat = $pImmediat;
		if (getTypeSG($pImmediat) === '@Formule') {
			$pImmediat ->  objet = $this;
			$pImmediat ->  objetprincipal = $this;
			$immediat = $pImmediat -> calculer();
		}
		if (getTypeSG($immediat) === '@VraiFaux') {
			$immediat = $immediat -> estVrai();
		} elseif (getTypeSG($immediat) === '@Texte') {
			$immediat = false;
		}
		if ($immediat === true) {
			$memo -> Envoyer();
		}
		return $memo;	
	}
	/** 1.1 ajout (= @ObjetsLies) ; 1.2 param $pSens
	 * Cette fonction retourne la collection de tous les objets de type Document lié au document en cours.
	 * La liaison se fait à base des propriétés et non des résultats de méthode.
	 * La liste est constituée en deux étapes : 
	 * 	'e' les documents qui pointent vers l'objet.
	 * 	's' les documents que le document en cours accède
	 * @param any $pModele : sélection éventuelle sur un seul modèle d'objet
	 * @param any $pChamp : sélection éventuelle sur un seul champ
	 * @param any $pSens : 'e' = entrants seulement, 's' sortant seulement, 'r' = textes riches aussi
	 */
	function Chercher($pModele = '', $pChamp = '', $pSens = 'e') {
		$ret = new SG_Collection();
		$sens = strtolower(SG_Texte::getTexte($pSens));
		$type = getTypeSG($this);
		if (strpos($sens, 'e') !== false) {
			if ($pModele === '') {
				$liens = SG_Dictionnaire::getLiensEntrants($type);
				foreach($liens as $modele) {
					$ret-> Concatener($_SESSION['@SynerGaia']->getObjetsLies($this, $modele, $pChamp));
				}
			} else {
				// si sélection sur un seul modèle ($pModele fourni)
				$modele = SG_Texte::getTexte($pModele);
				$ret = $_SESSION['@SynerGaia']->getObjetsLies($this, $modele, $pChamp);
			}
		}
		if (strpos($sens, 's') !== false) {
			$champsliens = SG_Dictionnaire::getLiens($type);
			foreach($champsliens as $lien) {
				$objet = $this -> getValeurPropriete($lien, '');
				if (getTypeSG($objet) === '@Collection') {
					foreach($objet -> elements as $doc) {
						if ($doc->Existe() -> estVrai()) {
							if($pModele === '' or ($pModele !== '' and getTypeSG($doc) === $pModele)) {
								$ret -> elements[] = $doc;
							}
						}
					}
				} elseif ($objet->Existe() -> estVrai()) {
					if($pModele === '' or ($pModele !== '' and getTypeSG($objet) === $pModele)) {
						$ret -> elements[] = $objet;
					}
				}
			}
		}
		if (strpos($sens, 'r') !== false) {
			$textesriches = SG_Dictionnaire::getTextesRiches($type);
			foreach ($textesriches as $item) {
				$tr = $this -> getValeurPropriete($item);
				$collec = $tr -> LiensInternes();
				if ($collec -> Compter() -> toInteger() > 0) {
					$ret -> Concatener($collec);
				}
			}
		}
		return $ret;
	}
	/** 1.0.7
	 * LienVers : crée un lien hyperttexte vers ce document via l'opération passée en paramètre
	 */
	function LienVers($pTexte = '', $pModele= '') {
		$texte = SG_Texte::getTexte($pTexte);
		if ($texte === '') {
			$texte = $this -> toString();
		}
		$html = '<a href="' . $this -> URL($pModele) . '">' . $texte  . '</a>';
		$html = new SG_HTML($html); // pour la prise en compte correcte dans les vues
		return $html;
	}
	
	function Bouton ($pNom = '', $pFormule = '') {
		$bouton = new SG_Bouton($pNom, $pFormule);
		$bouton -> refdocument = $this -> getUUID();
		return $bouton;
	}
	/** 1.1 gestion du type ; 1.3.4 '0094' ; 2.1.1 exit... ; 2.2 pas err 0094
	* récupération d'un fichier
	*/ 
	function TelechargerFichier($pChamp = null, $pFichier = null) {
		$fichier = SG_Texte::getTexte($pFichier);
		$ret = $this -> doc -> getFichier($pChamp, $fichier);
		if (getTypeSG($ret) !== SG_Erreur::TYPESG) {
			header('Content-Type: ' . $ret['type'] . '; name="' . $fichier.'"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . strlen($ret['data']));
			header('Content-Disposition: attachment; filename="'.$fichier.'"');
			header('Expires: 0');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			echo $ret['data'];
			exit; //2.1.1 quelque chose ne marche plus si on ne s'arrête pas TODO réparer...
			$ret = new SG_Texte(strlen($ret['data']));
		} else {
			//$ret = new SG_Erreur('0094', $fichier);
		}
		return $ret;
	}
	/** 1.1 ajout
	*/
	function getChampEnregistrer() {
		$ret = '';
		// coder un champ @Enregistrer pour le retour de la saisie (doc principal)
		$opEnCours = SG_Navigation::OperationEnCours();
		if (SG_Operation::isOperation($opEnCours)) {
			$codeChamp = SG_Champ::codeChampHTML($opEnCours -> reference . '/@Enregistrer');
			$ret .= '<input  type="hidden" name="' . $codeChamp . '" value="' . $this -> getUUID() . '"/>';
		}
		return $ret;
	}
	/** 1.2 ajout
	* Parcourt l'ensemble des Document.Consulter puis @Document.@Consulter pour chercher la formule à exécuter
	*/
	function Consulter() {
		$code = '';
		$n = 0;
		$typeObjet = getTypeSG($this);
		$action = 'Consulter';
		while ($typeObjet !== '@Document' and $n < 20) {
			if (SG_Dictionnaire::isMethodeExiste($typeObjet, $action) === true) {
				$code = $typeObjet . '.' . $action;
				break;
			}
			$typeObjet = SG_Dictionnaire::getCodeModele($typeObjet);
			$n++;
		}
		if ($code === '') { // pas trouvé
			$typeObjet = getTypeSG($this);
			$action = '@Consulter';			
			while ($typeObjet !== '@Document' and $n < 20) {
				if (SG_Dictionnaire::isMethodeExiste($typeObjet, $action) === true) {
					$code = $typeObjet . '.' . $action;
					break;
				}
				$typeObjet = SG_Dictionnaire::getCodeModele($typeObjet);
				$n++;
			}
		}
		if ($code === '' or $code === '@Document' . '.@Consulter') {// pour éviter de boucler...
			$ret = $this -> Afficher();
		} else {
			$ret = SG_Formule::executer('.' . $action, $this);
		}
		return $ret;
	}
	/** 1.2 ajout ; 1.3.2 n'entoure plus le résultat avec les accolades (permet d'en enchainer plusieurs)
	*/
	function JSON() {
		if(func_num_args() == 0) {
			$ret = '"' . $this -> toString() . '"';
		} else {
			$ret = '';
			// TODO utiliser getValeurs() 1.3.2
			// si liste de paramètres passée dans le 1er argument :
			$args = func_get_args();
			if (func_num_args() == 1 and is_array($args[0])) {
				$args = $args[0];
			}
			// traitement des paramètres
			foreach ($args as $parametre) {
				if (getTypeSG($parametre) === '@Formule') {
					$texte = $parametre -> formule;
				} else {
					$texte = SG_Texte::getTexte($parametre);
				}
				//traiter le titre
				$i = strpos($texte, ':');
				if ($i !== false) {
					$titre = substr($texte, $i + 1);
					$formule = substr($texte, 0, $i);
				} else {
					$titre = '';
					$formule = $texte;
				}
				//traiter la valeur
				$valeur = SG_Formule::executer($formule, $this, null, isset($parametre -> formuleparent)?$parametre -> formuleparent:null);
				$txtjson = '';
				if ( ! is_null($valeur) and $valeur !== '') {
					if(getTypeSG($valeur) === '@Collection') {
						$valeur = $valeur -> elements;
					}
					if(is_array($valeur)) {
						$txtjson = '[';
						foreach($valeur as $val) {
							if ($txtjson !== '[') {
								$txtjson .= ',';
							}
							$txtjson .= $val -> JSON($args) -> texte;
						}
						$txtjson .= ']';
						if ($txtjson === '[]') {
							$txtjson = '';
						}
					} else {
						if (! is_object($valeur)) {
							$valeur = new SG_Texte($valeur);
						}
						$txtjson .= $valeur -> JSON($args) -> texte;
					}
					if ($txtjson !== '') {
						if ($titre !== '') {
							$txtjson = '"' . $titre . '":' . $txtjson;
						}
					}
				}
				if($txtjson !== '') {
					if ($ret !== '') {
						$ret .= ',';
					}
					$ret .= $txtjson;
				}
			}
			//$ret .= '}';
		}
		if ($ret === '{}') {
			$ret = '';
		}
		return new SG_Texte($ret);
	}
	/** 1.2 ajout
	*/
	function Voisinage($pNiveau = 1, $pModeles = null) {
		$ret = new SG_Collection();
		if (!is_numeric($pNiveau)) {
			$niveau = new SG_Nombre($pNiveau);
			$niveau = $niveau -> toInteger();
		} else {
			$niveau = $pNiveau;
		}
		if ($niveau > 0) {
			if($pModeles !== null) {
				
			}
			$collec = $this -> Chercher('e');
			$niveau -= 1;
			foreach($collec -> elements as $element) {
				$voisins = $element -> Voisinage($niveau, $pModeles);
				$element -> proprietes['voisinage'] = $voisins;
				$ret -> elements[] = $element;				
			}
		}
		return $ret;
	}
	// 1.2 ajout
	function URL($pModele= '') {
		$modele = SG_Texte::getTexte($pModele);
		if ($modele === '') {
			$modele = 'DocumentConsulter';
		}
		$ret = SG_Navigation::URL_PRINCIPALE . '?d=' . $this -> getUUID() . '&m=' . $modele;
		return $ret;
	}
	/** 1.2 ajout ; 1.3.3 pris en compte niveau ; 2.1.1 titre toString
	* Afficher les liens de type @Reponses d'un document, de manière récursive
	*/
	function AfficherForum($pChampTexte = 'Texte', $pChampReponse = 'ReponseA', $pNiveau = 10) {
		$ret = '';
		$niveau = new SG_Nombre($pNiveau);
		$niveau = $niveau -> toInteger() - 1;
		$champ = SG_Texte::getTexte($pChampTexte);
		$reponse = SG_Texte::getTexte($pChampReponse);
		$titre = $this -> toString();
		if ($this -> EstVide($reponse) -> estVrai()) {// début du forum
			$theme = true;
			$ret.= '<div class="forum">';
			$ret.= '<div class="forum-theme">';
		} else {
			$theme = false;
			$ret.= '<div class="forum-reponse">';
		}
		// ligne du document
		$ret.= '<div class="forum_colgauche">';
		$auteur = $this -> getValeurPropriete('@AuteurCreation') ;
		$ret.= '<span class="forum-auteur">' . $this -> getValeurPropriete('@AuteurCreation') -> LienVers() -> texte . '</span><br>';
		$ret.= '<span class="forum-infos">' . $this -> getValeur('@DateCreation') . '</span>';
		$bouton = new SG_Bouton('Répondre','@Nouveau("' . getTypeSG($this) . '").@MettreValeur("'.$reponse.'",@OperationEnCours.@Parametre).@Modifier(.'.$champ .')', 'p1=' . $this -> getUUID());
		$ret.= '<p>' . $bouton -> toHTML() -> texte . '<p/>';
		$ret.= '</div>'; // fin col-gauche
		$ret.= '<div class="forum-page">';
		// titre
		if($theme) {
			$ret.= '<div class="forum-titre">' . $titre . '</div>'; 
		}
		// texte
		$p = $this -> getValeurPropriete($champ) -> Afficher();
		if (! is_string($p)) {
			$p = $p -> toString();
		}
		$ret.= '<div class="forum-texte">' . $p . '</div>';
		$reponses = $this -> Chercher(getTypeSG($this), $reponse);
		$ret.= '</div>'; // fin de page
		if ($niveau > 0) {
			foreach($reponses -> elements as $docReponse) {
				$ret.= $docReponse -> AfficherForum($pChampTexte, $pChampReponse, $niveau) -> texte;
			}
		}
		$ret.= '</div>'; // fin de ligne
		if ($theme) {
			$ret.= '</div>'; // fin de forum
		}
		return new SG_HTML($ret);
	}
	/**1.3.1 ajout
	* Indique que l'objet dérive de @Document
	*/
	function DeriveDeDocument () {
		return new SG_VraiFaux(true);
	}
	/** 1.3.1 ajout ; 1.3.2 correction sur $titre ; 1.3.4 correction $titre et $texte
	* Crée une vignette pour les liste de style apple
	**/
	function Vignette($pTitre = '.Titre', $pTexte = '.Texte.@Texte.@Jusqua(".")', $pImage = '', $pLien ='') {
		$lien = SG_Texte::getTexte($pLien);
		if($lien === '') {
			$lien = 'index.php?m=DocumentConsulter&d=' . $this->getUUID();
		}
		$ret = '<div class="document_vignette"><a href="' . $lien . '">';
		// image
		if(getTypeSG($pImage) !== 'string') {
			$image = $pImage -> calculer();
			$ret = '<img class="document_vignette_image">' . $pImage -> calculer() . '</img>';
		}
		$ret.= '<div>';
		// titre
		$type = getTypeSG($pTitre);
		$titre = $pTitre;
		if($type !== '@Formule' and $type !== '@Texte') {
			$titre = new SG_Formule($pTitre);
		}
		$ret.= '<span class="document_vignette_titre">' . $titre -> calculerSur($this) -> toHTML() -> texte . '</span>';
		// texte
		$type = getTypeSG($pTexte);
		$texte = $pTexte;
		if($type !== '@Formule' and $type !== '@Texte') {
			$texte = new SG_Formule($pTexte);
		}
		$texte = $texte -> calculerSur($this);
		if (is_object($texte)) {
			$texte = $texte -> toHTML();
		}
		if (is_object($texte) ) {
			$texte = $texte -> texte;
		}
		$ret.= '<span class="document_vignette_texte">' . $texte . '</span>';
		$ret.= '</div>';
		$ret.= '</a></div>';
		return new SG_HTML($ret);
	}
	/** 1.3.2 ajout
	* Affichage d'un arbre horizontal
	* @param (@Formule) Formule donnant la collection des voisins
	* @param (@Nombre) profondeur maximum de l'arbre
	* @param (@Formule) formules pemettant de créer la vignette à afficher dans chaque feuille sous forme json
	**/
	function AfficherArbre($pParents = null,$pProfondeur = 1) {
		$profondeurInitiale = new SG_Nombre($pProfondeur);
		$profondeurInitiale = $profondeurInitiale -> toInteger();
		// récupération des paramètres de la vignette
		$vignette = func_get_args();
		$r = array_shift($vignette);
		$r = array_shift($vignette);
		if(sizeof($vignette) === 1 and is_array($vignette[0])) {
			$vignette = $vignette[0];
		}
		// création du JSON du document en cours
		$json = '{' . $this -> JSON($vignette) -> texte;
		$json = html_entity_decode(str_replace(array(chr(10), chr(13)), '<br>', $json));
		// si possible continuer dans la profondeur
		$parents = array();
		if (abs($profondeurInitiale) > 0) {
			$collecvoisins = $pParents -> calculerSur($this);
			if (getTypeSG($collecvoisins) === '@Collection') {
				if (sizeof($collecvoisins -> elements) > 0) {
					foreach($collecvoisins -> elements as $voisin) {
						$parents[]= $voisin -> AfficherArbre($pParents, - abs($profondeurInitiale) + 1, $vignette);
					}
				}
			}
		}
		if ($parents !== array()) {
			$json.= ',"parents" : [' . implode(',', $parents) . ']';
		}
		$json.= '}';
		if ($profondeurInitiale > 0) {
			// profondeur initiale positive = niveau de départ => on sort
			$idGraphique = 'arbre_' . substr(sha1(mt_rand()), 0, 8); // Identifiant unique du graphique
			$ret = '<div id="' . $idGraphique . '" class="arbre"></div>' . PHP_EOL;
			$_SESSION['script'][]= 'var data_' . $idGraphique . ' = ' . $json . ';' . PHP_EOL . ' afficherArbre("#' . $idGraphique . '",data_' . $idGraphique . ');' . PHP_EOL;
		} else {
			$ret = $json;
		}
		return $ret;
	}
	/** 1.3.2 ajout
	* Récupère une série de valeurs du documents dans un tableau
	**/
	function getValeurs() {
		$ret = array();
		// traitement des paramètres
		$args = func_get_args();
		// si liste de paramètres passée dans le 1er argument :
		if (func_num_args() == 1 and is_array($args[0])) {
			$args = $args[0];
		}
		foreach ($args as $parametre) {
			if (getTypeSG($parametre) === '@Formule') {
				$texte = $parametre -> formule;
			} else {
				$texte = SG_Texte::getTexte($parametre);
			}
			//traiter le titre
			$i = strpos($texte, ':');
			if ($i !== false) {
				$titre = substr($texte, $i + 1);
				$formule = substr($texte, 0, $i);
			} else {
				$titre = '';
				$formule = $texte;
			}
			//traiter la valeur
			$valeur = SG_Formule::executer($formule, $this, null, isset($parametre -> formuleparent)?$parametre -> formuleparent:null);
			if ($titre === '') {
				$ret[] = $valeur;
			} else {
				$ret[$titre] = $valeur;
			}
		}
		return $ret;
	}
	/** 1.3.3 ajout ; 2.1 param 1
	* Un document est vide s'il n'a aucune propriete (Nouveau) ou seulement son type (@Type) et n'a jamais été enregistré 
	* @return @VraiFaux : le document est vide
	**/
	function EstVide($pChamp = null) {
		$ret = new SG_VraiFaux(true);
		if ($pChamp === null) {
			if (isset($this -> doc) and isset($this-> doc -> proprietes) and ($this-> doc -> proprietes !== array()) 
			and (sizeof($this-> doc -> proprietes) > 1 or (sizeof($this-> doc -> proprietes) === 1 and !isset($this-> doc -> proprietes['@Type'])))){
				$ret -> valeur = SG_VraiFaux::VRAIFAUX_FAUX;
			}
		} else {
			$champ = SG_Texte::getTexte($pChamp);
			if (isset($this-> doc -> proprietes[$champ]) and $this-> doc -> proprietes[$champ] !== '') {
				$ret -> valeur = SG_VraiFaux::VRAIFAUX_FAUX;
			}
		}
		return $ret;		
	}
	/** 1.3.4 AJout
	* @param $pChamp (@Texte) nom du champ à effacer dans le document
	* @return @Document le document lui-même (modifié et non enregistré)
	**/
	function Effacer($pChamp = '') {
		$champ = SG_Texte::getTexte($pChamp);
		if ($champ !== '') {
			if (SG_Dictionnaire::isLien(getTypeSG($this) . '.' . $champ)) {
				if (method_exists($pValeur, 'getUUID')) {
					$this -> setValeur($champ, '');
				} else {
					$this -> setValeur($champ, '');
				}
			} else {
				unset($this -> doc -> proprietes[$champ]);
			}
		}
		return $this;
	}
	/** 2.1 ajout ; 2.2 sup width et height 100% si pas de style
	* Donne l'HTML de l'image passée en paramètre (elle se trouve dans les attachements
	* @param : nom du fichier de l'image
	**/
	function AfficherImage($pNom = '', $pTaille = 0, $pStyle = '') {
		$ret = '';
		$nom = SG_Texte::getTexte($pNom);
		$style = SG_Texte::getTexte($pStyle);
		$taille = new SG_Nombre($pTaille);
		if ($nom === '') {
			$image = new SG_Image();
			$ret = $image -> toHTML();
		} elseif (array_key_exists($nom, $this -> doc -> proprietes['_attachments'])) {
			$image = new SG_Image();
			$image -> proprietes['@Fichier'] = $this -> doc -> getFichier('', $nom);
			if ($taille -> valeur != 0) {
				$image = $image -> Redimensionner($pTaille);
			}
			$ret .= '<img class="repertoire-photo" src="' . $image -> getSrc() .'" alt="' . $nom . '" style="' . $style . '">';
		} else {
			$ret .= '<img src="" alt="Image inconnue : ' . $nom . '">';
		}
		return new SG_HTML($ret);
	}
	/** 2.1 ajout
	* 
	**/
	function Fichiers($pNom = '') {
		$ret = '';
		$nom = SG_Texte::getTexte($pNom);
		if(isset($this -> doc -> proprietes['_attachments'])) {
			$libelle = SG_Libelle::getLibelle('0095', false);
			$objetfichiers = new SG_Fichiers($this -> doc);
			if($nom === '') {
				$ret.= '<li class="sg-lignechamp"><span class="sg-titrechamp">Fichiers</span> :'. $objetfichiers -> afficherChamp($nom) . '</li>';
			} else {
				$ret.= $objetfichiers -> afficherChamp($nom);
			}
		}
		return new SG_HTML($ret);
	}
	/** 2.2 ajout
	* Joindre un fichier aux attachements du document
	* @param (@Fichier) : objet à joindre. Si tableau : $nomfichier => [type, data]
	* @return (@Document) : le document ou erreur
	**/
	function AjouterFichiers($pObjet = null) {
		$ret = $this;
		if ($pObjet === null) {
			$ret = new SG_Erreur('Un paramètre de type fichier est obligatoire');
		} else {
			if (getTypeSG($pObjet) === '@Formule') {
				$objet = $pObjet -> calculer();
			} else {
				$objet = $pObjet;
			}
			if (getTypeSG($objet) === '@Fichier') {
				$this -> doc -> proprietes['_attachments'] [$objet -> reference] = $objet -> contenu;
			} elseif (is_array($objet)) {
				foreach ($objet as $key => $element) {
					$this -> doc -> proprietes['_attachments'] [$key] = $element;
				}
			} elseif (getTypeSG($objet) === '@Fichiers') {
				foreach ($objet -> elements as $key => $fic) {
					$this -> doc -> proprietes['_attachments'] [$key] = $element;
					break;
				}
			} else {
				$ret = new SG_Erreur('Le paramètre ne contient pas de fichier');
			}
		}
		return $ret;
	}
	/** 2.2 ajout
	* On regarde d'abord sur l'objet puis éventuellement dans propriété de l'opération en cours
	* @param (string) $pNom : le nom de la propriété ou de la méthode
	* @param (string) $pNomMethode : nom de la méthode terminant l'expression pour rechercher son titre dans le dictionnaire
	* @param (objet) : inutilisé (voir SG_Operation)
	* @return : la valeur ou @Erreur(166) ou @Erreur(175)
	**/
	function getProprieteOuMethode($pNom, $pNomMethode, $pObjet=null) {
		if (isset($this -> proprietes[$pNom])) {// propriété locale
			$ret = $this -> propriete($pNom);
		} elseif (method_exists($this, $pNomMethode)){// méthode
			$ret = $this -> $pNomMethode();
		} else {
			//$err = new SG_Erreur('0175',getTypeSG($this) . '.' . $pNom); // si pas trouvé
			$ret = $this -> getValeurPropriete($pNom, null);
		}
		return	$ret;
	}
	/** 2.3 ajout
	* créer un double du document sauf les champs système. 
	* Si paramètre = @True, on garde les champ système sauf :
	* - code base 
	* - code base complet si pas corrects
	* - _rev
	* @param (@Vraifaux) : gadrer les infos système
	* @return : le nouveau document non enregistré
	**/
	function Cloner ($pParmSyst = false) {
		$ret = new SG_Document();
		$ret -> typeSG = $this -> typeSG;
		$ret -> doc = $this -> doc;
		$ret -> doc -> codeBase = SG_Dictionnaire::getCodeBase($this -> typeSG);
		$ret -> doc -> setBase($ret -> doc -> codeBase);
		return $ret;
	}
	/** 2.3 ajout
	* teste si un document ne possède que les champs passés en paramètres (sauf champs système). Seules les propriétés permanentes sont testées
	* @param (@Texte) : liste des noms de champs qui doivent être présents uniquement
	* @return : (@VraiFaux) selon le résultat (ou @Erreur)
	**/
	function NAQue () {
		$ret = new SG_VraiFaux (true);
		$args = func_get_args();
		$noms = array('_id', '_rev', '@Type', '@DateCreation', '@AuteurCreation', '@DateModification', '@AuteurModification');
		if (func_num_args() != 0) {
			foreach($args as $arg) {
				$txt = SG_Texte::getTexte($arg);
				if (is_string($txt)) {
					$noms[] = $txt;
				}
			}
		}
		foreach ($this -> doc -> proprietes as $key => $champ) {
			if (! in_array($key, $noms)) {
				$ret = new SG_VraiFaux (false);
				break;
			}				
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Document_trait;
}
?>
