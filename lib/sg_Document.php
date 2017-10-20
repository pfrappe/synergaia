<?php
/** fichier contenant la gestion d'un @Document */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Document_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Document_trait.php';
} else {
	/**
	 * Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * trait vide par défaut 
	 * @since 2.1.1
	 */
	trait SG_Document_trait{};
}

/**
 * SG_Document : classe SynerGaia de gestion d'un document en base de données
 * @since 0.0
 * @version 2.6 traitement de la saisie d'un champ @Collection
 */
class SG_Document extends SG_Objet {
	/** string Type SynerGaia '@Document' */
	const TYPESG = '@Document';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/** SG_ DocumentCouchDB Document physique associé */
	public $doc;

	/**
	 * Construction de l'objet
	 * @version 1.1 ajout Domino
	 * @param string $pQuelqueChose référence du document (si '' le SG_DocumentCouchDB est créé sans id - gain de performance)
	 * @param array|SG_DocumentCouchDB $pTableau si couchdb : tableau éventuel des propriétés du document CouchDB ou SG_DocumentCouchDB ; si domino, doc base ou code base
	 * 		si document mais type d'objet différent, initialise à partir des propriétés
	 */
	public function __construct($pQuelqueChose = '', $pTableau = null) {
		$code = SG_Texte::getTexte($pQuelqueChose);
		if (strpos($code, '.nsf/') !== false or getTypeSG($pTableau) === '@DictionnaireBase') {
			$this -> initDocumentDominoDB($code, $pTableau);
		} else {
			$this -> initDocumentCouchDB($code, $pTableau);
		}
		if(method_exists($this, 'initDocument')) {
			$this -> initDocument();
		}
	}

	/**
	 * initDocumentCouchDB : crée ou recherche le document CouchDB
	 * @version 2.2 cade base d'après @Type
	 * @version 2.6 force le calcul du @Type
	 * @param string $pRefDocument référence du document
	 * @param array|SG_DocumentCouchDB|SG_Document $pTableau si on fourni directement du JSON on le construit à partir de là
	 */
	function initDocumentCouchDB($pRefDocument = '', $pTableau = null) {
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
		$this -> setValeur('@Type', getTypeSG($this));
	}

	/** 
	 * Initialisation d'un document basé sur Domino
	 * @since 1.1 ajout
	 * @param string $pReferenceDocument
	 * @param string $pBase
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

	/**
	 * Conversion en chaine de caractères
	 * @version 2.1 correction si Titre rempli
	 * @param null|string $pDefaut valeur par defaut
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

	/**
	 * Conversion en code HTML
	 * @version 2.0 correction
	 * @param null|string $pDefaut
	 * @return string code HTML
	 */
	function toHTML($pDefaut = null) {
		return $this -> toString('');
	}

	/** 
	 * Définition de la valeur d'un champ du document. Si une valeur locale existe, c'est celle-là qui est mise à jour.
	 * @since 1.0.5
	 * @version 2.0 traitement propriétés locales
	 * @param string $pChamp code du champ
	 * @param indéfini $pValeur valeur du champ
	 * @param boolean $forceFormule pour éviter de calculer une formule si c'est une formule qu'on veut stocker
	 * @return any valeur 
	 */
	public function setValeur($pChamp = '', $pValeur = null, $forceFormule = false) {
		$champ = SG_Texte::getTexte($pChamp);
		$valeur = $pValeur;
		$tmpTypeValeur = getTypeSG($pValeur);
		if ($tmpTypeValeur === '@Formule') {
			if ($forceFormule === false) {
				$valeur = $pValeur -> calculer();
			} else {
				// pour éviter les récursions au json_encode (car si c'est une opération, elle contient la formule...)
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

	/**
	 * Définition du contenu de type SG_Fichier (@Fichier) d'un champ du document
	 * @version 1.1 ajout du parm $pType
	 * @param string $pChamp code du champ
	 * @param string $pEmplacement emplacement du fichier
	 * @param string $pNom nom du fichier
	 * @param string $pType type du fichier (seuls utilisés : image/jpeg, image/png, image/gif pour créer vignette)
	 * @return null|boolean retour du setFichier de SG_DocumentCouchDB
	 **/
	public function setFichier($pChamp = null, $pEmplacement = '', $pNom = '', $pType = '') {
		$ret = null;
		if ($pEmplacement !== '') {
			$ret = $this -> doc -> setFichier($pChamp, $pEmplacement, $pNom, $pType);
		}
		return $ret;
	}

	/**
	 * Lecture de la valeur d'un champ du document, c'est à dire son contenu brut
	 * on commence par les propriétés à la volée du @Document puis on va dans le document physique
	 * @since 1.0.7
	 * @param string $pChamp code du champ
	 * @param indéfini $pValeurDefaut valeur si le champ ou le document physique n'existe pas
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
	 * Acquisition du contenu d'un champ fichier et stockage dans une destination
	 * @since 1.0.7
	 * @param string $pChamp nom du champ dans lequel se trouve le fichier
	 * @param string $pFichier nom du fichier à récupérer
	 * @param string $pDestination répertoire de destination (par défaut ./tmp)
	 * @return boolean
	 */
	public function DetacherFichier($pChamp = null, $pFichier = '', $pDestination = '/tmp') {
		return $this -> doc -> DetacherFichier($pChamp, $pFichier, $pDestination);
	}
	
	/**
	 * Lecture de la valeur d'une propriété du document : retourne un objet SynerGaïa
	 * @since 1.0.7
	 * @version 2.3 cas texte multiple
	 * @param string $pChamp code de la propriété (défaut null)
	 * @param indéfini $pValeurDefaut valeur de la propriété si le champ n'existe pas (défaut null)
	 * @param string $pModele modele imposé pour la propriété recherchée (défaut null)
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

	/**
	 * Lecture du code du document
	 * @since 1.0.7
	 * @version 2.1 creer si null et force
	 * @param boolean $pForce : (true) créer l'ID si vide, (false defaut) rendre même si vide
	 * @return string code du document
	 */
	public function getCodeDocument($pForce = false) {
		return $this -> doc -> getCodeDocument($pForce);
	}

	/**
	 * Document existe ? soit ce document, soit un document du même type et dont un champ a cette velaur si les paramètres sont fournis
	 * @since 1.0.7
	 * @version 2.3 @param 1 et 2
	 * @param null|string|SG_Texte|SG_Formule $pChamp nom du champ que doit contenir le document recherché, 
	 * @param null|any $pValeur valeur que le champ doit contenir
	 * @return SG_VraiFaux|SG_Erreur document existe
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

	/** 
	 * Lecture de l'UUID du document (peut venir des propriétés provisoires)
	 * @version 1.1 traite ce qui revient de ChercherVue
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

	/** 
	 * Champ du document
	 * @version 1.3.2 : si valeur numérique, n° de colonne des propriétés du tableau
	 * @param $pCodeChamp indefini code du champ
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

	/**
	 * Enregistrement du document
	 * @since 0.0
	 * @version 2.4 $ret = $this si pas de problème
	 * @version 2.6 init @Type si pas déjà fait
	 * @param boolean $pAppelMethodesEnregistrer appel des méthodes Enregistrer et @Enregistrer
	 * @param boolean $pCalculTitre
	 * @return SG_Dcoument|SG_Erreur résultat de l'enregistrement (@Ceci si ok sinon erreur retournée)
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
		if ($ok instanceof SG_Erreur) {
			$ret = $ok;
		} else {
			// mettre type objet si pas fait
			$typeObjet = $this -> getValeur('@Type', '');
			if ($typeObjet === '') {
				$typeObjet = getTypeSG($this);
				$this -> setValeur('@Type', $typeObjet);
			}
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
			if (getTypeSG($ret) !== '@Erreur') {
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
				if (getTypeSG($ret) !== '@Erreur') {
					if(is_null($ok) or $ok === true) {
						$ret = $this;
					} else {
						$ret = $ok;
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Suppression du document
	 * @version 2.3 ajout @param
	 * @param string|SG_Texte|SG_Formule $pBase permet de forcer le code de la base dans les cas où ce code n'aurait pas été correct
	 * @return SG_VraiFaux résultat de la suppression
	 */
	public function Supprimer($pBase = '') {
		$base = SG_Texte::getTexte($pBase);
		if ($base !== '' and $this -> doc -> codeBase !== $base) {
			$this -> doc -> setBase($base);
		}
		return $this -> doc -> Supprimer();
	}

	/**
	 * Affichage du document
	 * @since 0.0
	 * @version 2.1 si parametre est string (donc on est en interne), les fichiers visibles : 
	 * @version 2.4 getTexte $titre
	 * @version 1.1 : titre non affiché si vide, <h2> toujours ; 
	 * @version 1.3.1 isEmpty ; $valeur est object ; 1.3.2 toggle $infosdoc, correction ligne 500 calculerSUR() ; 1.3.3 seulement champs non vides
	 * @version 1.3.4 test $codedocument existe, condensé $infos ; supp lignes vides
	 * @param SG_Formule noms de champs ou résultats à afficher (autant que nécessaires ou aucun pour tout afficher)
	 * @return string contenu HTML affichable
	 * @uses SynerGaia.montrercacher()
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
				$ret .= '<h1>' . SG_Texte::getTexte($titre) . ' ' . $this -> AfficherLien() -> texte . '</h1>';
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
								$texte = '<ul class="sg-composite">';
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
				$infos = '<div id="infosdoc" class="sg-infosdoc noprint" style="display:none"><ul data-role="listview">';
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
					} elseif (getTypeSG($element) === '@Collection') {
						$tmpChamp = new SG_Champ();
						$tmpChamp -> contenu = $element;
						$tmpChamp -> libelle = $element -> titre;
						
						if ($tmpChamp -> isEmpty() === false) {
							$texte = $tmpChamp -> Afficher();
						}
					} else {
						if(is_object($element) and !($element -> EstVide() -> estVrai())) {
							$texte = $element -> Afficher();
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
		SG_Pilote::OperationEnCours() -> setPrincipal($this);
		if (getTypeSG($ret) !== '@HTML') {
			$ret = new SG_HTML($ret);
		}
		return $ret;
	}
	/**
	 * Affichage du document
	 * @version 1.3.4 @Fichiers _attachments
	 * @param any liste des champs à afficher
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

	/**
	 * Modification d'un champ de type @Document (lien vers un ou plusieurs documents)
	 * @since 0.0
	 * @version 2.0 affichage des valeurs sélectées
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
		$idChamp = SG_SynerGaia::idRandom();
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

	/**
	 * Affichage d'un champ de type @Document
	 * @version 1.3.1 rien si n'existe pas
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

	/**
	 * Modification du document : calcul du html permettat la saisie en modification
	 * @since 0.0
	 * @version 2.6 traitement des champs de type @Collection
	 * @param any liste facultative de paramètres donnant les propriétés ou formules à afficher
	 * @return SG_HTML contenu HTML affichable / modifiable
	 * @todo supprimer la pirouette du '.' devant les propriétés
	 * @todo if ($tmp -> DeriveDeDocument() -> estVrai()) {etc. pour .Contact.@ValeursPossibles(etc) pas au point
	 */
	public function Modifier() {
		$ret = ''; // 2.5
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
						$listeProprietes[] = $parametre -> texte;
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
			// si aucun : récupère la liste complète des champs du document
			$listeChamps = SG_Dictionnaire::getListeChamps(getTypeSG($this));
			// Transforme la liste des champs en formules de propriete
			foreach($listeChamps as $key => $modele) {
				$listeProprietes[] = '.' . $key;
			}
			$listeProprietes[] = '.@Fichiers';
		}
		$ret .= '<ul data-role="listview">';
		// affichage des autres champs
		$proprietesNonModifiables = self::nomsChampsSysteme();
		foreach ($listeProprietes as $propriete) {
			$valeurspossibles = null;
			$index = '';
			$doc = null;
			$libelle = null;
			if (getTypeSG($propriete) === '@Formule') {
				$libelle = $propriete -> titre;
				$nom = $propriete -> methode;
				// calcul de l'objet du champ à modifier
				$tmp = $propriete -> calculerSur($this);
			//	if ($tmp -> DeriveDeDocument() -> estVrai()) { // 2.5 pour .Contact.@ValeursPossibles(etc) pas au point
			//		$index = $tmp -> index;
			//		$doc = $tmp;
			//	} else {
					$doc = $tmp -> contenant;
			//	}
				// cas d'une formule de valeurs possibles de la propriété
				if(isset($tmp -> proprietes['@vp'])) {
					$valeurspossibles = $tmp -> proprietes['@vp'];
				}
			} else {
				$nom = $propriete;
			}
			// extraction éventuelle du libellé (derrière le ':')
			if (strpos($nom, ':') !== false) {
				list($nom, $libelle) = explode(':', $nom);
			}
			$nom = trim($nom);
			if (!in_array($nom, $proprietesNonModifiables)) {
				// Supprime le '.' au début de la propriété (pirouette)
				if(substr($nom, 0, 1) === '.') { // 2.1
					$nom = substr($nom, 1);
				}
				if ($nom === '@Fichiers') {
					// cas des fichiers rattachés
					$tmpChamp = new SG_Fichiers($this -> doc);
				} else {
					// préparation du champ à modifier
					if ($index === '') { // c'est le document en cours
						$index = $this -> getUUID() . '/' . $nom;
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
		$ret = new SG_HTML($ret);
		$ret -> saisie = true;
		$this -> setPrincipal(true);
		return $ret;
	}

	/**
	 * Duplication du document
	 * @since 1.0.7
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
					$listeProprietes[] = $parametre -> texte;
				} else {
					$listeProprietes[] = SG_Texte::getTexte($parametre);
				}
			}
		} else {
			// Recupere la liste complete des champs du document
			$listeChamps = SG_Dictionnaire::getListeChamps($type);
			// Liste des champs non dupliqués automatiquement
			$champsNonDupliques = self::nomsChampsSysteme();
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

	/**
	 * Faire suivre l'affichage d'un document dans un memo
	 * @since 1.1
	 * @param indéfini $pDestinataires
	 * @param indéfini $pObjet objet du message
	 * @param indéfini $pModele modèle d'opération à utiliser pour le lien vers le document
	 * @param indéfini $pImmediat : envoi immédiat (vrai) ou non. par défaut non
	 * @param string|SG_Texte|SG_Formule $pMethode éventuellement, une méthode spécifique pour l'envoi du document
	 * @return SG_Memo le message préparé
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
			$texte = $pMethode -> calculerSur($this);
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
			$immediat = $pImmediat -> calculerSur($this);
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

	/**
	 * Cette fonction retourne la collection de tous les objets de type Document lié au document en cours.
	 * La liaison se fait à base des propriétés et non des résultats de méthode.
	 * La liste est constituée en deux étapes : 
	 * 	'e' les documents qui pointent vers l'objet.
	 * 	's' les documents que le document en cours accède
	 * @since 1.1
	 * @version 1.2 param $pSens
	 * @param any $pModele : sélection éventuelle sur un seul modèle d'objet
	 * @param any $pChamp : sélection éventuelle sur un seul champ
	 * @param any $pSens : 'e' = entrants seulement, 's' sortant seulement, 'r' = textes riches aussi
	 * @return SG_Collection
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

	/**
	 * LienVers : crée un lien hyperttexte vers ce document via l'opération passée en paramètre
	 * @since 1.0.7
	 * @param string|SG_Texte|SG_Formule $pTexte
	 * @param string|SG_Texte|SG_Formule $pModele
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

	/**
	 * Crée un bouton SG_Bouton donnant la référence du document
	 * @param string $pNom
	 * @param string $pFormule
	 * @return SG_Bouton
	 */
	function Bouton ($pNom = '', $pFormule = '') {
		$bouton = new SG_Bouton($pNom, $pFormule);
		$bouton -> refdocument = $this -> getUUID();
		return $bouton;
	}
	/**
	 * Récupération d'un fichier
	 * @version 2.2 pas err 0094
	 * @param string|SG_Texte|SG_Formule $pChamp
	 * @param string|SG_Texte|SG_Formule $pFichier
	 * @return string HTML 
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

	/**
	* Parcourt l'ensemble des Document.Consulter puis @Document.@Consulter pour chercher la formule à exécuter
	* @since 1.2 ajout
	* @return SG_HTML contenant le texte HTML à aficher
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

	/**
	 * Extrait le document en JSON
	 * @since 1.2 ajout
	 * @version 1.3.2 n'entoure plus le résultat avec les accolades (permet d'en enchainer plusieurs)
	 * @return string json
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
					$texte = $parametre -> texte;
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

	/**
	 * Calcule le voisinage d'un document à partir des liens et des champs liens
	 * @since 1.2 ajout
	 * @param integer|SG_Nombre|SG_Formule $pNiveau niveau de profondeur du voidinage (par défaut 1)
	 * @param string|SG_Texte|SG_Formule $pModeles liste des modèles de documents à retenir
	 * @return SG_Collection
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

	/**
	 * url pour consulter le document
	 * @since 1.2 ajout
	 * @param string $pModele
	 * return string
	 */
	function URL($pModele= '') {
		$modele = SG_Texte::getTexte($pModele);
		if ($modele === '') {
			$modele = 'DocumentConsulter';
		}
		$ret = SG_Navigation::URL_PRINCIPALE . '?d=' . $this -> getUUID() . '&m=' . $modele;
		return $ret;
	}

	/**
	 * Afficher les liens de type @Reponses d'un document, de manière récursive
	 * @since 1.2 ajout
	 * @version 2.6 modifier pour l'auteur
	 * @param SG_Texte|SG_Formule $pChampTexte par défaut Texte
	 * @param SG_Texte|SG_Formule $pChampReponse par défaut 'ReponseA'
	 * @param SG_Nombre|SG_Formule $pNiveau (par défaut 10)
	 * @return SG_HTML
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
		$ret.= '<span class="forum-auteur">' . $auteur -> LienVers() -> texte . '</span><br>';
		$ret.= '<span class="forum-infos">' . $this -> getValeur('@DateCreation') . '</span>';
		$bouton = new SG_Bouton('Répondre','@Nouveau("' . getTypeSG($this) . '").@MettreValeur("'.$reponse.'",@OperationEnCours.@Parametre).@Modifier(.'.$champ .')', 'p1=' . $this -> getUUID());
		$ret.= '<p>' . $bouton -> toHTML() -> texte . '<p/>';
		// si auteur, bouton modifier
		if ($auteur -> Egale(SG_Rien::Moi()) -> estVrai()) {
			$bouton = new SG_Bouton('Modifier','.@Modifier', 'p1=' . $this -> getUUID());
			$ret.= '<p>' . $bouton -> toHTML() -> texte . '<p/>';
		}
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
	
	/**
	* Indique que l'objet dérive de SG_Document
	* @since 1.3.1 ajout
	* @return SG_VraiFaux
	*/
	function DeriveDeDocument () {
		return new SG_VraiFaux(true);
	}

	/**
	 * Crée une vignette pour les liste de style apple
	 * @since 1.3.1 ajout
	 * @version 1.3.4 correction $titre et $texte
	 * @param SG_Texte|SG_Formule $pTitre par défaut '.Titre'
	 * @param SG_Texte|SG_Formule $pTexte par défaut '.Texte.@Texte.@Jusqua(".")'
	 * @param SG_Texte|SG_Formule $pImage par défaut ''
	 * @param SG_Texte|SG_Formule $pLien lien utl à activer quand on clique sur la vignette
	 * @return SG_HTML
	 */
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

	/**
	* Affichage d'un arbre horizontal
	* @since 1.3.2 ajout
	* @param (@Formule) Formule donnant la collection des voisins
	* @param (@Nombre) profondeur maximum de l'arbre
	* @param (@Formule) formules pemettant de créer la vignette à afficher dans chaque feuille sous forme json
	* @return string json résultant
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
			$idGraphique = 'arbre_' . SG_SynerGaia::idRandom(); // Identifiant unique du graphique
			$ret = '<div id="' . $idGraphique . '" class="arbre"></div>' . PHP_EOL;
			$_SESSION['script'][]= 'var data_' . $idGraphique . ' = ' . $json . ';' . PHP_EOL . ' afficherArbre("#' . $idGraphique . '",data_' . $idGraphique . ');' . PHP_EOL;
		} else {
			$ret = $json;
		}
		return $ret;
	}

	/**
	 * Récupère une série de valeurs du documents dans un tableau 
	 * @since 1.3.2 ajout
	 * @return array
	 */
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
				$texte = $parametre -> texte;
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

	/**
	* Un document est vide s'il n'a aucune propriete (Nouveau) ou seulement son type (@Type) et n'a jamais été enregistré 
	* Si un nom de champ est fourni en paramètre, c'est ce champ dont on teste la vacuité
	* @since 1.3.3 ajout
	* @version 2.1 param 1
	* @param string|SG_Texte|SG_Formule $pChamp nom d'un champ spécifique
	* @return SG_VraiFaux : le document est vide
	*/
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

	/**
	 * Efface un champ du document
	 * @since 1.3.4 AJout
	 * @param SG_Texte $pChamp nom du champ à effacer dans le document
	 * @return SG_Document le document lui-même (modifié et non enregistré)
	 */
	function Effacer() {
		$args = func_get_args();
		foreach ($args as $pChamp) {
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
		}
		return $this;
	}

	/**
	* Donne l'HTML de l'image passée en paramètre (elle se trouve dans les attachements
	* Si pas de nom fourni, donne une image vide
	* @since 2.1 ajout
	* @version 2.2 sup width et height 100% si pas de style
	* @param string|SG_Texte|SG_Formule $pNom nom du fichier de l'image
	* @param integer|SG_Nombree|SG_Formule $pTaille taille maximale en pixels de l'image, 
	* @param string|SG_Texte|SG_Formule $pStyle clause de style CSS (pas défaut '')
	* @return SG_HTML
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
			$ret .= '<img class="sg-rep-photo" src="' . $image -> getSrc() .'" alt="' . $nom . '" style="' . $style . '">';
		} else {
			$ret .= '<img src="" alt="Image inconnue : ' . $nom . '">';
		}
		return new SG_HTML($ret);
	}

	/**
	 * Affiche les fichiers du document
	 * @since 2.1 ajout
	 * @param string|SG_Texte|SG_Formule $pNom nom du fichier si un seul fichier voulu
	 * @return SG_HTML
	 */
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

	/**
	 * Joindre un fichier aux attachements du document
	 * @since 2.2 ajout
	 * @version 2.6 err 0310, 0311
	 * @param SG_Fichier $pObjet : objet à joindre. Si tableau : $nomfichier => [type, data]
	 * @return SG_Document|SG_Erreur ce document ou erreur
	 */
	function AjouterFichiers($pObjet = null) {
		$ret = $this;
		if ($pObjet === null) {
			$ret = new SG_Erreur('0310');
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
				$ret = new SG_Erreur('0311');
			}
		}
		return $ret;
	}

	/**
	 * On regarde d'abord sur l'objet puis éventuellement dans propriété de l'opération en cours
	 * @since 2.2 ajout
	 * @param string $pNom : le nom de la propriété ou de la méthode
	 * @param string $pNomMethode : nom de la méthode terminant l'expression pour rechercher son titre dans le dictionnaire
	 * @param SG_Objet : inutilisé (voir SG_Operation)
	 * @return : la valeur ou @Erreur(166) ou @Erreur(175)
	 */
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

	/**
	 * créer un double du document sauf les champs système. 
	 * Si paramètre = @True, on garde les champ système sauf :
	 * - code base 
	 * - code base complet si pas corrects
	 * - _rev
	 * @since 2.3 ajout
	 * @param SG_Vraifaux $pParmSyst : gadrer les infos système
	 * @return : le nouveau document non enregistré
	 */
	function Cloner ($pParmSyst = false) {
		$syst = SG_VraiFaux::getBooleen($pParmSyst);
		$ret = new SG_Document();
		$ret -> typeSG = $this -> typeSG;
		$ret -> doc = $this -> doc;
		$ret -> doc -> codeBase = SG_Dictionnaire::getCodeBase($this -> typeSG);
		$ret -> doc -> setBase($ret -> doc -> codeBase);
		$ret -> doc -> proprietes = $this -> doc -> proprietes;
		unset($ret -> doc -> revision);
		unset($ret -> doc -> proprietes['_rev']);
		if ($syst === false) {
			foreach (self::nomsChampsSysteme() as $elt) {
				unset($ret -> doc -> proprietes[$elt]);
			}
		}
		return $ret;
	}

	/**
	 * teste si un document ne possède que les champs passés en paramètres (sauf champs système). Seules les propriétés permanentes sont testées
	 * @since 2.3 ajout
	 * @param SG_Texte|SG_Formule : liste des noms de champs qui doivent être présents uniquement
	 * @return SG_VraiFaux|SG_Erreur selon le résultat (ou @Erreur)
	 */
	function NAQue () {
		$ret = new SG_VraiFaux (true);
		$args = func_get_args();
		$noms = self::nomsChampsSysteme();
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

	/** 
	 * Liste des champs système
	 * @since 2.4 ajout
	 * @return array
	 **/
	static function nomsChampsSysteme() {
		return array('_id', '_rev', '@Type', '@Erreur', '@DateCreation', '@AuteurCreation', '@DateModification', '@AuteurModification');
	}

	/**
	* Met ce document comme principal de l'opération, éventuellement en modif
	* 
	* @since 2.4
	* @version 2.5 setPrincipal()
	* @version 2.6 abandon parm $pModif
	* @return SG_Document $this
	**/
	function setPrincipal() {
		$opEnCours = SG_Pilote::OperationEnCours();
		if ($this -> Existe() -> estVrai()) {
			// document déjà sur disque => ne mettre que l'UID
			// @todo vérifier que c'est malin plutôt que tout le document. Vient sans doute de l'ancienne façon de conserver le principal
			// ou alors c'est pour forcer le rechargement après un enregistrement ?
			$opEnCours -> setPrincipal($this -> getUUID());
		} else {
			$opEnCours -> setPrincipal($this);
		}
	}

	/** 
	* est ou dérive d'un objet dérivant de @Document
	* @since 2.5
	* @param SG_Texte $pType le type de l'objet à comparer
	* @return SG_VraiFaux vrai ou faux
	**/
	function DeriveDe($pType = '') {
		$type = SG_Texte::getTexte($pType);
		if (substr($type, 0, 1) === '@') {
			$classe = 'SG_' . substr($type, 1);
		} else {
			$classe = $type;
		}
		$ret = (get_class($this) === $classe or is_subclass_of($this, $classe));
		return new SG_VraiFaux($ret);
	}

	/**
	 * Affiche le document dans un format deux colonnes (titres de champs, valeurs de champs)
	 * @param SG_Formule noms de champs ou résultats à afficher (autant que nécessaires ou aucun pour tout afficher)
	 * @return SG_HTML|SG_Erreur contenu HTML affichable
	 */
	public function AfficherEnColonnes() {
		$ret = '';
		// Traite les parametres passés
		$formule = '';
		$formuleorigine = null;
		$nbParametres = func_num_args();

		if ($nbParametres === 0) {
			// Aucun paramètre fourni => affiche tous les champs
			$titre = $this -> toHTML('');
			if($titre !== '') {
				$ret .= '<h1>' . SG_Texte::getTexte($titre) . '</h1>';
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
								$texte = '<ul class="sg-composite">';
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
				$infos = '<div id="infosdoc" class="sg-infosdoc noprint" style="display:none"><ul data-role="listview">';
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
						if(is_object($texte)) {
							$texte = $element -> Afficher();
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
		SG_Pilote::OperationEnCours() -> setPrincipal($this);
		if (getTypeSG($ret) !== '@HTML') {
			$ret = new SG_HTML($ret);
		}
		return $ret;
	}

	/**
	 * Récupère ou met à jour la valeur brute du champ telle que stockée dans la base physique
	 * Elle est retournée sous forme de SG_Texte
	 * Evidemment, cette façon de travailler est dangereuse...
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pCode code du champ
	 * @param string|SG_Texte|SG_Formule $pValeur valeur éventuelle à mettre
	 * @return SG_Texte|SG_Document|SG_Erreur document si mise à jour, valeur si recherche, erreur sinon
	 */
	function ChampBrut($pCode = null, $pValeur = null) {
		$ret = new SG_Erreur('0282');
		$code = SG_Texte::getTexte($pCode);
		$val = SG_Texte::getTexte($pValeur);
		if ($code !== '') {
			if (isset($this -> doc -> proprietes[$code])) {
				$ret = new SG_Texte($this -> doc -> proprietes[$code]);
			}
		}
		return $ret;
	}

	/**
	 * Change le type du document. A manipuler avec précaution car peut complètement modifier le contenu d'une base...
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pModele modèle cible pour le document
	 * @return SG_Objet le nouvel objet
	 */
	function Devient($pModele = '') {
		$modele = SG_Texte::getTexte($pModele);
		if ($modele === '') {
			$ret = $this;
		} else {
			$ret = new $modele();
			$ret -> doc = $this -> doc;
			$ret -> doc -> proprietes['@Type'] = $modele;
		}
		return $ret;
	}

	/**
	 * Permet de forcer l'id d'un document (ne modifie pas le _rev s'il existe)
	 * Ne fait rien si l'uid actuel existe
	 * Utile dans l'interprétation des paquets pour le chargement du dictionnaire
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pUID le nouvel uid
	 * @return SG_Document|SG_Erreur
	 */
	function setUID($pUID = '') {
		$uid = SG_Texte::getTexte($pUID);
		if (is_string($uid) and $uid !== '' and $this -> doc instanceof SG_DocumentCouchDB
		and !isset($this -> doc -> proprietes['_id'])) {
			$this -> doc -> codeDocument = $uid;
			$this -> doc -> proprietes['_id'] = $uid;
			$ret = $this;
		} else {
			$ret = new SG_Erreur('0290');
		}
		return $ret;
	}

	/**
	 * Permet de mettre une formule dans la propriété indiquée
	 * A la différence de MettreValeur, la formule est stockée telle quelle sans interprétation
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pChamp champ dans leqel doit être stockée la formule
	 * @param string|SG_Texte|SG_Formule $pFormule formule à stocker
	 * @return SG_Document|SG_Erreur ce document ou une erreur
	 */
	function MettreFormule ($pChamp = '', $pFormule = '') {
		$ret = $this;
		$champ = SG_Texte::getTexte($pChamp);
		if ($champ instanceof SG_Erreur) {
			$ret = $champ;
		} elseif ($champ !== '') {
			if ($pFormule instanceof SG_Formule) {
				$formule = $pFormule -> phrase;
			} else {
				$formule = SG_Texte::getTexte($pFormule);
			}
			$this -> MettreValeur($champ, $formule);
		}
		return $ret;
	}

	/**
	 * Crée un lien pour une recherche par id sur le document.
	 * (JS) En cliquant, le lien sera copié dans le presse papier pour insertion dans un texte riche.
	 * 
	 * @since 2.6
	 * @param SG_Document $pDocument le document vers lequel pointera le lien
	 * @return string html de l'icone à cliquer et de l'appel pour le clic
	 * @uses SynerGaia.copy()
	 */
	function AfficherLien($pDocument = null) {
		$ret = '';
		$doc = null;
		if ($pDocument instanceof SG_Document) {
			$doc = $pDocument;
		} elseif (is_null($pDocument)) {
			$doc = $this;
		}
		if (is_null($doc)) {
			$ret = new SG_Erreur('0310');
		} else {
			$ret = '<span class="sg-doc-lien"><img class="sg-raccourci" src="nav/themes/defaut/img/icons/16x16/silkicons/link.png" ';
			$ret.= 'onclick="SynerGaia.copy(event,\'[[' . $doc -> getUUID() . ']]\')"';
			$lib = SG_Libelle::getLibelle('0312', false);
			$ret.= ' title="' . $lib . '"/></span>';
		}
		return new SG_HTML($ret);
	}

	/**
	 * Fournit l'ID interne d'un document. Cette méthode ne permet pas sa modification.
	 * 
	 * @since 2.6
	 * @return SG_Texte
	 */
	function ID() {
		$ret = new SG_Texte($this -> getUUID());
		return $ret;
	}

	/** 2.1.1. complément de classe créée par compilation */
	use SG_Document_trait;
}
?>
