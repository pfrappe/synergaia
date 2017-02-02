<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_DocumentCouchDB : Classe SynerGaia de gestion d'un document CouchDB
*/
// 2.3 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_DocumentCouchDB_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_DocumentCouchDB_trait.php';
} else {
	trait SG_DocumentCouchDB_trait{};
}
class SG_DocumentCouchDB extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@DocumentCouchDB';
	public $typeSG = self::TYPESG;

	// Code de la base
	public $codeBase;

	// Code complet de la base avec prefixe
	public $codeBaseComplet = '';

	// Code du document (en fait l'id physique)
	public $codeDocument;

	// Révision (version) du document
	public $revision = '';

	// Tableau des propriétés du document et de leurs valeurs
	public $proprietes;

	/** 1.0.7 ; 2.1 setTableau simplifié
	* Construction de l'objet
	*
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
			ini_set('memory_limit', '512M'); // 2.1 pour répertoire //TODO Supprimer ?
					$tableau = json_decode($json, true);
			ini_restore('memory_limit');
					$this -> setTableau($tableau);
				}
			}
		}
		if (method_exists($this,"initSpecifique")) {
			$this -> initSpecifique();
		}
	}
	/** 1.0.6 ; 2.3 getCodeBaseComplet
	* setBase : Construction des identifiants de type base du document
	*
	* @param string $pCodeBase code de la base
	*/
	function setBase ($pCodeBase = null) {	
		// Si pas de base définie => base par défaut
		$codeBase = $pCodeBase;
		if (!$codeBase) {
			$codeBase = SG_Base::CODEBASE;
		}
		$this -> codeBase = $codeBase;
		$this -> codeBaseComplet = SG_Config::getCodeBaseComplet($codeBase);
	}

	/** 1.0.6 ; 2.1 simplifié
	* setTableau : contenu du document à partir d'un json
	*
	* @param string $pCodeBase code de la base
	* @param string $pCodeDocument code du document
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
	*
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

	/** 1.1 traitement des composites (.)
	* Définition de la valeur d'un champ du document
	*
	* @param string $pChamp code du champ
	* @param indéfini $pValeur valeur du champ
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
		$i = $valeur;
		return $valeur;
	}

	/** 1.1 : $pType ; traitement des composites (.) ; 2.2 fichiers multiples
	* Insertion d'un fichier dans un champ d'un document
	*
	* @param string $pChamp code du champ de stockage
	* @param string $pEmplacement emplacement actuel du fichier (chemin complet)
	* @param string $pNom nom du fichier dans le document
	* @param strint $pType type du fichier (seuls utilisés : image/jpeg, image/png, image/gif pour créer vignette)
	*/
	public function setFichier($pChamp = '', $pEmplacement = '', $pNom = '', $pType = '') {
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
		return true;
	}

	/** 1.1 _attachments ; 1.3.0 SYNERGAIA_PATH_TO_APPLI
	*  Acquisition du contenu d'un champ fichier et stockage dans une destination du serveur
	* 
	*  @param string $pChamp nom du champ dans lequel se trouve le fichier
	*  @param string $pFichier nom du fichier à récupérer
	*  @param string $pDestination répertoire de destination (par défaut ./tmp)
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

	/** 1.1 traitement des composites (.) ; 2.3 msg 0195
	* Lecture de la valeur d'un champ du document
	*
	* @param string $pChamp code du champ
	* @param indéfini $pValeurDefaut valeur si le champ n'existe pas
	*
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

	/** 2.1 creer si null et force
	* Lecture du code du document
	* @param (boolean) $pForce : (true) créer l'ID si vide, (false defaut) rendre même si vide
	* @return string code du document
	*/
	public function getCodeDocument($pForce = false) {
		if ($pForce and ($this -> codeDocument === null or $this -> codeDocument === '')) {
			$this -> codeDocument = $sgbd -> getUUID();
		}
		return $this -> codeDocument;
	}

	/** 1.0.5 ; 1.3.0 chang $infos ; 1.3.1 suppression champs vides ; 1.3.4 retour doc ou SG_Erreur
	* Enregistrement du document
	*
	* @return SG_VraiFaux résultat de l'enregistrement
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
			if (strlen($resultat) !== 0) {
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
			}
		}
		return $ret;
	}

	/**
	* Suppression du document
	*
	* @return SG_VraiFaux résultat de la suppression
	*/
	public function Supprimer() {
		$retBool = false;
		$couchdb = $_SESSION['@SynerGaia'] -> sgbd;
		$url = $couchdb -> url . $this -> codeBaseComplet . '/' . $this -> codeDocument . '?rev=' . $this -> revision;
		$resultat = $couchdb -> requete($url, "DELETE");
		if (strlen($resultat) !== 0) {
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
		}

		$ret = new SG_VraiFaux($retBool);
		return $ret;
	}

	// 1.1 ajout _attachments ; 1.3.3 getTexte() ; 1.3.4 urlencode
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

	/** 1.0.5 ; 2.1 return, static
	* Journaliser la dernière erreur json rencontrée
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
		return $texte;
	}

	/** 1.0.7 
	* remplacerID (nouvel id, champ ancien id)
	*/
	function remplacerID($pNouvelID = '', $pChampSave = '') {
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

	/** 1.1 ajout
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

	/** 1.3.4 ajout
	**/
	function getUUID() {
		return $this -> codeBase . '/' . $this -> codeDocument;
	}
	// 2.3 complément de classe créée par compilation
	use SG_DocumentCouchDB_trait;
}
?>
