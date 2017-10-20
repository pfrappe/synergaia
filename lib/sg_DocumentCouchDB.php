<?php
/** fichier contenant les classes pour gérer un @DocumentCouchDB */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

// 2.3 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_DocumentCouchDB_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_DocumentCouchDB_trait.php';
} else {
	/** trait vide par défaut */
	trait SG_DocumentCouchDB_trait{};
}

/**
 * SG_DocumentCouchDB : Classe SynerGaia de gestion d'un document CouchDB
 * @since 0.0
 * @version 2.3
 */
class SG_DocumentCouchDB extends SG_Objet {
	/** string Type SynerGaia '@DocumentCouchDB' */
	const TYPESG = '@DocumentCouchDB';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Code de la base */
	public $codeBase;

	/** string Code complet de la base avec prefixe */
	public $codeBaseComplet = '';

	/** string Code du document (en fait l'id physique) */
	public $codeDocument;

	/** string Révision (version) du document */
	public $revision = '';

	/** array Tableau des propriétés du document et de leurs valeurs */
	public $proprietes;

	/**
	 * Construction de l'objet
	 * 
	 * @since 2.1 setTableau simplifié
	 * @version 2.6 test json sg_erreur
	 * @param string $pCodeBase code de la base (ou codebase/codedocument)
	 * @param string $pCodeDocument code du document
	 * @param array $pTableau tableau des propriétés à créer
	 */
	public function __construct($pCodeBase = null, $pCodeDocument = null, $pTableau = null) {
		$this -> proprietes = array();
		if ($pTableau) {
			$this -> setBase($pCodeBase);
			$this -> setTableau($pTableau);
		} else {
			if ($pCodeBase !== '') { // 2.1 création sans id - gain de performance
				$islash = strpos($pCodeBase, '/');
				if ($islash === false) {
					$codeBase = $pCodeBase;
					$codeDocument = $pCodeDocument;
				} else {
					$codeBase = substr($pCodeBase,0,$islash);
					$codeDocument = substr($pCodeBase,$islash + 1);
				}
				$this -> setBase($codeBase);
				//On sait qu'on est dans un environnement CouchDB
				$sgbd = $_SESSION['@SynerGaia'] -> sgbd;
				// Si on n'a pas de CodeDocument => on fabrique un UUID (nouveau document vierge
				if ($codeDocument === null or $codeDocument === '') {
					$this -> codeDocument = $sgbd -> getUUID();
				} else {
					// Sinon on charge le document existant
					$this -> codeDocument = $codeDocument;
					$url = $this -> urlCouchDB(true);
					$json = $sgbd -> requete($url, "GET");
					if ($json instanceof SG_Erreur) {
						$tableau = array();
					} else {
				ini_set('memory_limit', '512M'); // 2.1 pour répertoire //TODO Supprimer ?
						$tableau = json_decode($json, true);
						$this -> setTableau($tableau);
				ini_restore('memory_limit');
					}
				}
			}
		}
		if (method_exists($this,'initSpecifique')) {
			$this -> initSpecifique();
		}
	}

	/**
	 * setBase : Construction des identifiants de type base du document
	 * @since 1.0.6
	 * @version 2.3 getCodeBaseComplet
	 * @param string $pCodeBase code de la base
	 */
	function setBase ($pCodeBase = null) {	
		// Si pas de base définie => base par défaut
		$codeBase = $pCodeBase;
		if (is_null($codeBase) or $codeBase === '') {
			if (isset($this -> proprietes['@TypeSG'])) {
				$codeBase = SG_Dictionnaire::getCodeBase($this -> proprietes['@TypeSG']);
			} else {
				$codeBase = SG_Base::CODEBASE;
			}
		}
		$this -> codeBase = $codeBase;
		$this -> codeBaseComplet = SG_Config::getCodeBaseComplet($codeBase);
	}

	/**
	 * setTableau : contenu du document à partir d'un json
	 * @since 1.0.6
	 * @version 2.1 simplifié
	 * @param array $pTableau tableau de propriétés
	 */
	function setTableau($pTableau) {
		// Si on a bien un document existant
		if (is_array($pTableau)) {
			$this -> proprietes = $pTableau;
			if (isset($this -> proprietes['_id'])) {
				$this -> codeDocument = $this -> proprietes['_id'];
			}
			unset($this -> proprietes['_id']);
			if (isset($this -> proprietes['_rev'])) {
				$this -> revision = $this -> proprietes['_rev'];
			}
		}
	}

	/**
	 * Document existe ?
	 * @return SG_VraiFaux document existe
	 */
	public function Existe() {
		$retBool = false;
		if ($this -> revision !== '') {
			$retBool = true;
		}
		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}

	/**
	 * Définition de la valeur d'un champ du document
	 * @version 1.1 traitement des composites (.)
	 * @todo vior si param forceFormule encore utile ??
	 * @param string $pChamp code du champ
	 * @param indéfini $pValeur valeur du champ
	 * @param boolean $forceFormule vrai s'il faut forcer l'enregistrement d'une formule
	 * @return any valeur du champ
	 */
	public function setValeur($pChamp = '', $pValeur = null, $forceFormule = false) {
		// Convertit les types SynerGaia en type "simples" pour l'enregistrement
		$valeur = $pValeur;
		$tmpTypeValeur = getTypeSG($pValeur);
		if (substr($tmpTypeValeur, 0, 1) === '@' and $forceFormule === false) {
			$valeur = $pValeur -> toString();
		}
		$champs = explode('.', $pChamp);		
		for($i = &$this -> proprietes; $key = array_shift($champs); $i = &$i[$key]) {
			if (!isset($i[$key])) {
				$i[$key] = array();
			}
		}
		$i = $valeur; // attention : $i est $this -> proprietes (voir 5 lignes ci-dessus au 'for' )
		return $valeur;
	}

	/**
	 * Insertion d'un fichier dans un champ d'un document
	 * @version 2.2 fichiers multiples
	 * @version 26 return nb fichiers
	 * @param string $pChamp code du champ de stockage
	 * @param string $pEmplacement emplacement actuel du fichier (chemin complet)
	 * @param string $pNom nom du fichier dans le document
	 * @param strint $pType type du fichier (seuls utilisés : image/jpeg, image/png, image/gif pour créer vignette)
	 * @return integer nbre de fichiers traités
	 */
	public function setFichier($pChamp = '', $pEmplacement = '', $pNom = '', $pType = '') {
		$ret = 0;
		if ($pEmplacement !== '') {
			// traitement du nom du champ
			$fichier_champ = SG_Texte::getTexte($pChamp);
			if ($fichier_champ === '') {
				$fichier_champ = '_attachments';
			}
			// On met dans le champ demandé le nom du fichier (peut être adresse.ville.plan par exemple)
			$champs = explode('.', $fichier_champ);		
			for($n = &$this -> proprietes; $key = array_shift($champs); $n = &$n[$key]) {
				if (!isset($n[$key])) {
					$n[$key] = array();
				}
			}// à la fin, $n pointe sur une propriété
			// recherche de la propriété correspondante
			$fichier = array();
			if ($fichier_champ === '_attachments') {
				if (isset($this -> proprietes['_attachments'])) {
					$fichier = $this -> proprietes['_attachments'];
				}
			}
			// si un seul fichier on met dans un tableau pour la boucle après
			if (is_array($pEmplacement)) {
				$fichier_emplacement = $pEmplacement;
				$fichier_nom = $pNom;
				$fichier_type = $pType;
			} else {
				$fichier_emplacement = array($pEmplacement);
				$fichier_nom = array($pNom);
				$fichier_type = array($pType);
			}
			// Charge le contenu du ou des fichiers
			for ($i = 0; $i < sizeof($fichier_emplacement); $i++) {
				if ($fichier_emplacement[$i] !== '') {
					$ret++;
					$fichier_contenu = base64_encode(file_get_contents($fichier_emplacement[$i]));
					$fichier[$fichier_nom[$i]] = array('content_type' => $fichier_type[$i], 'data' => $fichier_contenu);
					switch ($fichier_type[$i]) {
						case 'image/jpeg' :
						case 'image/png' :
						case 'image/gif' :
							$tmp =  SG_Image::resizeTo(100, $fichier_contenu);
							$fichier[$fichier_nom[$i]]['vignette'] = $tmp;
							// lecture des données exif
							$exif = json_encode(@exif_read_data($fichier_emplacement[$i]));
							if ($exif) {
								$fichier[$fichier_nom[$i]]['exif'] = $exif;
							}
							break;
						default :
							break;
					}
				}
			}
			$n = $fichier;// affectation dans la propriété
		}
		return $ret;
	}

	/**
	 * Acquisition du contenu d'un champ fichier et stockage dans une destination du serveur
	 * @version 1.3.0 SYNERGAIA_PATH_TO_APPLI
	 * @param string $pChamp nom du champ dans lequel se trouve le fichier
	 * @param string $pFichier nom du fichier à récupérer
	 * @param string $pDestination répertoire de destination (par défaut ./tmp)
	 * @return boolean|SG_Erreur
	 */
	public function DetacherFichier($pChamp = null, $pFichier = '', $pDestination = 'tmp') {
		$ret = false;
		if (substr($pDestination, 0, 1) === '/') {
			$dest = SYNERGAIA_PATH_TO_APPLI . $pDestination;
		} else {
			$dest = SYNERGAIA_PATH_TO_APPLI . '/' . $pDestination;
		}
		$nom = SG_Texte::getTexte($pChamp);
		if ($nom === '') {
			$nom = '_attachments';
		}
		// le champ existe-t-il ?
		if (array_key_exists($nom, $this -> proprietes)) {
			$champ = $this -> proprietes[$nom];
			// si la destination n'existe pas on la crée
			if (!is_dir($dest)) {
				mkdir ($dest, 0777, true);
			}
			if (is_dir($dest)) {
				// calcul du nom du fichier
				if (getTypeSG($pFichier) === '@Formule') {
					$nomfic = $pFichier -> calculer();
				} else {
					$nomfic = new SG_Texte($pFichier);
				}
				$nomfic = $nomfic -> toString();
				if ($nomfic !== '') {
					if (array_key_exists($nomfic, $champ)) {
						$fichiers = array($champ[$nomfic]);
					} else {
						$ret = new SG_Erreur('0039', $nomfic . ' : ' . $nom);
					}
				} else {
					$fichiers = $champ;
				}
				if (getTypeSG($ret) !== '@Erreur') {
					$ret = true;
					foreach ($fichiers as $nom => $fichier) {
						if (isset($fichier['stub'])) {
							$url = $this -> urlCouchDB() . '/' . $nom;
							$contenu = $_SESSION['@SynerGaia'] -> sgbd -> requete($url);
						} else {
							$contenu = $fichier['data'];
						}
						if (file_put_contents($dest . '/' . str_replace(' ', '_', $nom), base64_decode($contenu)) === false) {
							$ret = new SG_Erreur('0038', $dest . '/' . $nom);
						}
					}
				}
			} else {
				$ret = new SG_Erreur('0040', $dest);
			}
		} else {
			$ret = new SG_Erreur('0041', $nom);
		}

		return $ret;
	}

	/**
	 * Lecture de la valeur d'un champ du document
	 * @version 2.3 msg 0195
	 * @param string $pChamp code du champ
	 * @param indéfini $pValeurDefaut valeur si le champ n'existe pas
	 * @return indéfini valeur du champ
	 */
	public function getValeur($pChamp = null, $pValeurDefaut = null) {
		$ret = $pValeurDefaut;
		$champs = explode('.', $pChamp);
		if (sizeof($champs) === 1) {
			if (!is_array($this -> proprietes)) {
				$ret = new SG_Erreur('0195');
			} elseif (array_key_exists($pChamp, $this -> proprietes)) {
				$ret = $this -> proprietes[$pChamp];
			} else {
				if ($pChamp === '_id') {
					$ret = $this -> codeDocument;
				}
			}
		} else {
			$valeurs = array();
			if (array_key_exists($champs[0], $this -> proprietes)) {
				$valeurs = $this -> proprietes[$champs[0]];
			}
			for ($i = 1; $i < sizeof($champs); $i++) {
				if (array_key_exists($champs[$i], $valeurs)) {
					$valeurs = $valeurs[$champs[$i]];
				} else {
					$valeurs = $pValeurDefaut;
					break;
				}
			}
			$ret = $valeurs;
		}
		return $ret;
	}

	/**
	 * Lecture du code du document
	 * @version 2.1 creer si null et force
	 * @param boolean $pForce : (true) créer l'ID si vide, (false defaut) rendre même si vide
	 * @return string code du document
	 */
	public function getCodeDocument($pForce = false) {
		if ($pForce and ($this -> codeDocument === null or $this -> codeDocument === '')) {
			$this -> codeDocument = $sgbd -> getUUID();
		}
		return $this -> codeDocument;
	}

	/**
	 * Enregistrement du document
	 * @version 1.3.4 retour doc ou SG_Erreur
	 * @version 2.6 test $resultat SG_Erreur
	 * @return SG_DocumentCouchDB|SG_Erreur résultat de l'enregistrement
	 */
	public function Enregistrer() {
		$ret = $this;
		$couchdb = $_SESSION['@SynerGaia'] -> sgbd;
		$url = $this -> urlCouchDB(true);
		// simplifie en ne gardant que le tableau de propriétés
		$proprietes = array();
		foreach($this -> proprietes as $key => $propriete) {
			if($propriete !== '' and $propriete !== array()) {
				$proprietes[$key] = $propriete;
			}
		}
		// Enregistre les propriétés
		$ok = false;
		$contenu = json_encode($proprietes);
		if ($contenu === false) {
			if(isset($proprietes['@Exif'])) { // 2.1 cas des fichiers photos qui peuvent poser problème...
				unset($proprietes['@Exif']);				
				$contenu = json_encode($proprietes);
			}
			if ($contenu === false) {
				$ret = new SG_Erreur(self::jsonLastError('Enregistrer', $this));
			} else {
				$ok = true;
			}
		} else {
			$ok = true;
		}
		if ($ok === true) {
			$resultat = $couchdb -> requete($url, "PUT", $contenu);
			if ($resultat instanceof SG_Erreur) {
				$ret = $resultat;
			} elseif (strlen($resultat) !== 0) {
				$infos = json_decode($resultat);
				$this -> revision = $infos -> rev;  // avant 1.3.0 _rev
				$this -> setValeur('_rev', $this -> revision);

				$tmpCodeDocument = $infos -> id; // avant 1.3.0 _id
				if (is_null($tmpCodeDocument)) {
					$tmpCodeDocument = '';
				}
				if ($infos -> ok !== true) {
					$ret = new SG_Erreur('0100',$tmpCodeDocument);
				}
			} else {
				$ret = new SG_Erreur('0219');
			}
		}
		return $ret;
	}

	/**
	 * Suppression du document
	 * @version 2.6 test SG_Erreur
	 * @return SG_VraiFaux résultat de la suppression
	 */
	public function Supprimer() {
		$retBool = false;
		$couchdb = $_SESSION['@SynerGaia'] -> sgbd;
		$url = $couchdb -> url . $this -> codeBaseComplet . '/' . $this -> codeDocument . '?rev=' . $this -> revision;
		$resultat = $couchdb -> requete($url, "DELETE");
		if($resultat instanceof SG_Erreur) {
			$ret = $resultat;
		} elseif (strlen($resultat) !== 0) {
			$infos = json_decode($resultat);
			$tmpOk = $infos -> ok;
			if (is_null($tmpOk)) {
				$tmpOk = '';
			}
			$retBool = ($tmpOk === true);

			$tmpCodeDocument = $infos -> id;
			if (is_null($tmpCodeDocument)) {
				$tmpCodeDocument = '';
			}
			$retBool = $retBool and ($tmpCodeDocument === $this -> codeDocument);
			$ret = new SG_VraiFaux($retBool);
		}
		return $ret;
	}

	/** 
	 * récupère un fichier dans le document
	 * @since 1.1 ajout
	 * @version 1.3.4 urlencode
	 * @param string $pChamp nom de champ
	 * @param string $pFichier nom de fichier
	 * @return string|SG_Erreur conteniu du fichier pour injection dans un code HTML ou erreur
	 */
	function getFichier ($pChamp = null, $pFichier = null) {
		$ret = false;
		$nom = SG_Texte::getTexte($pChamp);
		if ($pChamp === null or $nom === '') {
			$nom = '_attachments';
		}
		// le champ existe-t-il ?
		if (array_key_exists($nom, $this -> proprietes)) {
			$champ = $this -> proprietes[$nom];
			// calcul du nom du fichier
			if ($pFichier === null) {
				reset($champ);
				$fic = key($champ);
			} else {
				$fic = SG_Texte::getTexte($pFichier);
			}
			if (array_key_exists($fic, $champ)) {
				if (isset($champ[$fic]['stub'])) {
					$url = $this -> urlCouchDB() . '/' . urlencode($fic);
					$contenu = $_SESSION['@SynerGaia'] -> sgbd -> requete($url);
				} else {
					$contenu = base64_decode($champ[$fic]['data']);
				}
				$ret = array('data' => $contenu, 'type' => $champ[$fic]['content_type'], 'nom' => $fic);
			} else {
				$ret = new SG_Erreur('0036', $fic . ' : ' . $nom);
			}
		} else {
			$ret = new SG_Erreur('0037', $nom);
		}
		return $ret;
	}

	/**
	* Journaliser la dernière erreur json rencontrée
	* @since 1.0.5
	* @version 2.1 return, static
	* @param string $ou
	* @param SG_Document $pDoc
	* @return string texte de l'erreur json
	*/
	static function jsonLastError($ou = '', $pDoc = null) {
		$texte = '@DocumentCouchDB.' . $ou . ' ERREUR (';
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$texte = 'Aucune erreur';
				break;
			case JSON_ERROR_DEPTH:
				$texte = 'Profondeur maximale atteinte';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$texte = 'Inadéquation des modes ou underflow';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$texte = 'Erreur lors du contrôle des caractères';
				break;
			case JSON_ERROR_SYNTAX:
				$texte = 'Erreur de syntaxe ; JSON malformé';
				break;
			case JSON_ERROR_UTF8:
				$texte = 'Caractères UTF-8 malformés, probablement une erreur d\'encodage';
				break;
				// nécessite php 5.5
			case JSON_ERROR_RECURSION :
				$texte = 'Récursion dans l\'élément';
				break;
			case JSON_ERROR_INF_OR_NAN :
				$texte = 'La valeur passée inclut soit NAN soit INF';
				break;
			case JSON_ERROR_UNSUPPORTED_TYPE;
				$texte = 'Une valeur non supportée a été fournie';
				break;
			default:
				$texte .= 'Erreur inconnue : ' . json_last_error();
				break;
		}
		$texte .= ') à l encodage json !';
		if ($pDoc !== null) {
			$texte .= ' doc = ' . $pDoc -> codeBaseComplet . '/' . $pDoc -> codeDocument;
		}
journaliser($texte);
journaliser($pDoc);
		return $texte;
	}

	/**
	 * remplacerID (nouvel id, champ ancien id)
	 * @since 1.0.7 
	 * @todo voir si utilisée ??
	 * @param string $pNouvelID
	 * @return boolean
	 */
	function remplacerID($pNouvelID = '') {
		$ret = false;
		$newDoc = new SG_DocumentCouchDB($this -> codeBase, $pNouvelID);
		if ($newDoc -> Existe() -> estVrai()) {
			$ret = new SG_Erreur('0102', $pNouvelID);
		} else {
			$newDoc -> proprietes = $this -> proprietes;
			unset($newDoc -> proprietes['_rev']);
			$newDoc -> revision = '';
			$ret = $this -> Enregistrer() -> estVrai();
			if (! $ret -> estErreur()) {
				if ($this -> Supprimer() -> estVrai()) {
					$ret = $newDoc;
				} else {
					$ret = new SG_Erreur('0101', $pNouvelID);
				}
			}
		}
		return $ret;
	}

	/**
	 * retourne l'url du document dans couchdb
	 * @since 1.1 ajout
	 * @param boolean $creerBase vrai s'il faut créer la base si elle n'existe pas (par défaut false)
	 * @return string
	 */
	function urlCouchDB($creerBase = false) {
		$couchdb = $_SESSION['@SynerGaia'] -> sgbd;
		if (!$couchdb -> BaseExiste($this -> codeBaseComplet)) {
			if ($creerBase === true) {
				$couchdb -> AjouterBase($this -> codeBaseComplet);
			}
		}
		return $couchdb -> url . $this -> codeBaseComplet . '/' . $this -> codeDocument;
	}

	/**
	 * retourne UUID du document
	 * @since 1.3.4 ajout
	 * @return string
	 **/
	function getUUID() {
		return $this -> codeBase . '/' . $this -> codeDocument;
	}

	// 2.3 complément de classe créée par compilation
	use SG_DocumentCouchDB_trait;
}
?>
