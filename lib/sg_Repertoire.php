<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Repertoire : classe SynerGaia de gestion d'un album de photos
*/
// Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Repertoire_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Repertoire_trait.php';
} else {
	trait SG_Repertoire_trait{};
}
class SG_Repertoire extends SG_DocumentCouchDB {
	// Type SynerGaia
	const TYPESG = '@Repertoire';
	public $typeSG = self::TYPESG;

	// Code de la base
	const CODEBASE = 'synergaia_repertoires';

	// titre de l'entrée
	public $titre = '';
	
	// titre depuis l'entrée principale
	//public $titrecomplet = ''; TODO fonction

	// pointeur du répertoire parent local
	public $repparent = null;
	
	/** 2.1 ajout
	* Construction de l'objet. Elle est spécifique car on se base sur DocumentCouchDB mais on ne peut pas utiliser le __Construct standard à cause des paramètres
	* L'identifiant du répertoire est construit à partir de son titre.
	* Il faut donc faire attention si on change le titre.
	* codeDocument : soit titre si répertoire initial, soit idparent / titre si le répertoire est rattaché à un parent
	* @param indéfini $pCodeBase : code du document à partir de laquelle le SG_Repertoire est créé
	* @param indéfini $pCodeDocument : soit codeDocument, soit objet parent
	* @param (boolean) $pTableau : tableau des propriétés (utile pour les vues)
	***/
	public function __construct($pCodeBase = null, $pCodeDocument = null, $pTableau = null) {
		// traitement du premier paramètre : il peut contenir la base si on vient de @Nouveau, ou le code du parent
		$codeBase = SG_Texte::getTexte($pCodeBase);
		$codeDocument = $codeBase; // cas simple d'un répertoire de plus haut niveau
		// si on vient d'un @Nouveau, tout est dans $pCodeBase et le code de la base précède le titre : on l'enlève
		$i = strpos($codeBase, '/');
		if ($i !== false) {
			if (substr($codeBase, 0, $i) === self::CODEBASE) {
				$codeDocument = substr($codeDocument, $i + 1);
				//$codeBase = substr($codeBase, $ipos);
				$codeBase = self::CODEBASE; // TODO chercher le vrai code base de l'objet
			}
		} else {
			$codeDocument = $codeBase;
		}
		// titre du répertoire
		$titre = $codeDocument;
		$i = strpos($codeDocument, '/');
		if ($i !== false) {
			$titre = substr($codeDocument, $i + 1);
		}
		$this -> titre = $titre;
		$this -> codeBase = self::CODEBASE;
		if (is_array($pCodeDocument)) {
			// cas d'une construction dans la lecture d'une vue : on reçoit les propriétés dans un tableau dans $pCodeDocument
			$this -> proprietes = $pCodeDocument;
			$this -> setBase(self::CODEBASE);
			$this -> setTableau($pCodeDocument);
			if (isset($this -> proprietes['@Parent'])) {
				$this -> setParent($this -> proprietes['@Parent']);
			}
		} else {
			// peut-être objet existant ? ($pCodeBase contient alors le code du document (soit titre seul, soit codeDocument idparent/titre)
			$objet = null;
			// calculer le code à partir de la hiérarchie
			if ($pCodeBase !== null and $pCodeBase !== '') {
				// essayer de charger un répertoire existant sur le code
				ini_set('memory_limit', '512M');
				$tableau = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(self::CODEBASE, self::TYPESG, $codeDocument);
				$this -> setTableau($tableau);
				$this -> setBase(self::CODEBASE);
				// rattacher à un parent
				if (is_object($pCodeDocument)) {
					$this -> setParent($pCodeDocument);
				} elseif (isset($this -> proprietes['@Parent'])) {
					$this -> repparent = $this -> proprietes['@Parent'];
				}
				ini_restore('memory_limit');
			}
			if (is_array($objet)) {
				// on a trouvé un objet existant (retourné sous forme de document)
				$this -> setBase();
				$this -> setTableau($objet);
			} else {
				// c'est un nouveau répertoire
				$this -> setValeur('@Type', self::TYPESG);
				if (! isset($this -> proprietes['@Elements'])) {
					$this -> setValeur('@Elements', array());
				}
				if (! isset($this -> proprietes['@Titre'])) {
					$this -> setValeur('@Titre', $titre);
				}
				$parent = null;
				if (is_object($pCodeDocument)) {
					$parent = $pCodeDocument;
					$this -> setValeur('@Parent', $parent -> getUUID());
					$this -> setValeur('@Code', $parent -> codeDocument . '/' . $titre);
				} else {
					$this -> setValeur('@Code', $titre);
				}
				if (!isset($this -> proprietes['_id'])) {
					$this -> codeDocument = $_SESSION['@SynerGaia'] -> sgbd -> getUUID();
					$this -> proprietes['_id'] = $this -> codeDocument;
				} else {
					$this -> codeDocument = $this -> proprietes['_id'];
				}
				$this -> setBase(self::CODEBASE);
			}
		}
	}
	/** 2.1 ajout
	* spécifique en plus du SG_DocumentCouchDB::__construct()
	**/
	function initSpecifique() {
		if (isset($this -> proprietes['@Parent'])) {
			$this -> repparent = $this -> proprietes['@Parent'];
		}
	}
	/** 2.1 ajout
	* rattache le répertoire à un parent
	**/
	function setParent($pParent) {
		if (is_object($pParent)) {
			$this -> repparent = $pParent -> getUUID();
		} else {
			$this -> repparent = $pParent;
		}
		$this -> proprietes['@Parent'] = $this -> repparent;
		$ipos = strpos('/', $this -> repparent);
		if ($ipos) {
			$this -> codeDocument = substr($this -> repparent, $ipos + 1) . '/' . $this -> titre;
			$this -> proprietes['@Code'] = $this -> codeDocument;
		}
	}
	/** 2.1 ajout
	* affichage indenté du répertoire et des liens vers les documents
	**/
	public function Afficher($pOptions = '', $pAction = '') {
		$ret = new SG_HTML($this -> toHTML($pOptions, $pAction));
		return $ret;
	}
	/** 2.1 ajout
	* affichage indenté du répertoire et des liens vers les documents
	**/
	public function AfficherPlanche($pOptions = null, $pAction = '') {
		// Boutons de navigation
		$ret = $this -> getBoutons();
		$ret.= '<div class="repertoire-page">'; //<span class="album-titre" onclick="">' . $this -> getValeur('@Titre','') . '</span>';
		// Répertoires
		$elements = $this -> getValeur('@Repertoires',array());
		if(! is_array($elements)) {
			$ret.= $elements -> toString();
		} else {
			if (sizeof($elements) > 0) {
				$ret.= '<div class="repertoire-repertoires">';
				foreach ($elements as $key => $elt) {
					$titre = $elt['titre'];
					$clic = $elt['uid'];
					$ret.= '<span class="repertoire-sstitre" ' . SG_Collection::creerLienCliquable($clic, $titre, "centre") . '>' . $titre . '</span>';
				}
				$ret.= '</div>';
			}
		}
		// Vignettes
		$elements = $this -> getValeur('@Elements',array());
		if(! is_array($elements)) {
			$ret.= $elements -> toString();
		} else {
			if (sizeof($elements) > 0) {
				$ret.= '<div class="repertoire-planche">';
				foreach ($elements as $key => $elt) {
					if (is_array($elt)) {
						$image = $this -> getVignette($elt);
						if ($image) {
							$t = $elt['titre'];
							$ret.= '<div class="repertoire-vignette" ' . SG_Collection::creerLienCliquable($elt['doc'], $t , "centre") . '>';
							$ret.= '<img class="" src="data:'. $image['type'] . ';base64,' .  $image['data'] . '" alt="' . $t . '" title="' .$t . '">';
							$ret.= '</div>';
						}
					}
				}
				$ret.= '</div>';
			}
		}
		$ret.= '</div>';
		$opEnCours -> doc -> proprietes['@Principal'] = $this;
		return new SG_HTML($ret);
	}
	/** 2.1 ajout
	* affichage indenté du répertoire et des liens vers les documents
	**/
	public function toHTML($pOptions = null, $pAction = '') {
		$ret = '<ol class="repertoire"><span class="titre" onclick="">' . $this -> getValeur('@Titre','') . '</span>';
		$elements = $this -> getValeur('@Repertoires',array());
		foreach ($elements as $key => $elt) {
			$type = getTypeSG($elt);
			if ($type === '@Repertoire') {
				$ret.= $elt -> toHTML($pOptions);
			}
		}
		$elements = $this -> getValeur('@Elements',array());
		if(! is_array($elements)) {
			$ret.= $elements -> toString();
		} else {
			foreach ($elements as $key => $elt) {
				if (is_array($elt)) {
					$image = $this -> getVignette($elt);
					if ($image) {
						$ret.= '<div class="repertoire-photo" ' . SG_Collection::creerLienCliquable($elt['doc'], $elt['titre'], "centre") . '>';
						$ret.= '<img class="" src="data:'. $image['type'] . ';base64,' .  $image['data'] . '">';
						$ret.= '</div>';
					} else {
						$ret.= '<li class="doc" ' . SG_Collection::creerLienCliquable($elt['doc'], $elt['titre'], "centre") . '>';
						$ret.=  $elt['titre'];
						$ret.= '</li>';
					}
				} else {
					$type = getTypeSG($elt);
					if ($type !== '@Repertoire') {
						$ret.= '<li class="doc">' . $elt -> toString() . '</li>';
					}
				}
			}
		}
		$ret.= '</ol>';
		return $ret;
	}
	/** 2.1 ajout
	* affichage indenté du répertoire et des liens vers les documents
	**/
	public function toListeHTML($pOptions = null) {
		$ret = '<li class="rep"><span class="titre">' . $this -> getValeur('@Titre','') . '</span><ol>';
		$elements = $this -> getValeur('@Repertoires',array());
		foreach ($elements as $key => $elt) {
			$type = getTypeSG($elt);
			if ($type === '@Repertoire') {
				$ret.= $elt -> toHTML($pOptions);
			}
		}
		$elements = $this -> getValeur('@Elements',null);
		foreach ($elements as $key => $elt) {
			if (is_array($elt)) {				
				$ret.= '<li class="doc" ' . SG_Collection::creerLienCliquable($elt['doc'], $elt['titre'], "centre") . '>' .  $elt['titre'] . '</li>';
			} else {
				$type = getTypeSG($elt);
				if ($type !== '@Repertoire') {
					$ret.= '<li class="doc">' . $elt -> toString() . '</li>';
				}
			}
		}
		$ret.= '</ol></li>';
		return $ret;
	}
	/** 2.1 ajout
	* ajout d'un document ou d'un collection de document ou d'une entrée de répertoire ou d'un répertoire
	**/
	public function Ajouter($pQuelqueChose = null) {
		$type = getTypeSG($pQuelqueChose);
		if ($type === '@Formule') {
			$objet = $pQuelqueChose -> calculer();
			$type = getTypeSG($objet);
		} else {
			$objet = $pQuelqueChose;
		}
		if ($type === '@Texte') {
			$objet = SG_Texte::getTexte($objet);
		}
		// rangement (soit répertoire (string), soit doc, soit collection, soit objet)
		if (is_string($objet)) {
			$newrep = new SG_Repertoire($objet);
			$newrep -> setParent($this);
			$this -> proprietes['@Repertoires'][$newrep -> titre] = array('uid' => $newrep -> getUUID(), 'titre' => $newrep -> titre);
		} elseif ($type === '@Repertoire') {
			// un répertoire autonome vers lequel pointer (déjà enregistré)
			$this -> proprietes['@Repertoires'][$objet -> titre] = array('uid' => $objet -> getUUID(), 'titre' => $objet -> titre);
		} elseif (SG_Dictionnaire::isObjetDocument($objet)) {
			$elements = $this -> getValeur('@Elements',array());
			$entree = array('titre' => $objet -> toString(), 'doc' => $objet -> getUUID(), 'type' => getTypeSG($objet));
			if (SG_Dictionnaire::deriveDe(getTypeSG($objet), '@Photo')) {
				$entree['vignette'] = $objet -> getValeur('@Vignette','vignette'); //$objet -> getFichier();
			}
			$elements[] = $entree;
			$this -> setValeur('@Elements', $elements);
		} elseif ($type === '@Collection') {
			// on ne garde que les pointeurs vers les documents (qui doivent déjà avoir été enregistrés)
			$elements = $this -> getValeur('@Elements',array());
			foreach ($objet -> elements as $key => $elt) {
				$elements[] = array('titre' => $elt -> toString(), 'doc' => $elt -> getUUID(), 'type' => getTypeSG($elt));
			}
			$this -> setValeur('@Elements', $elements);
		} else {
			$elements = $this -> getValeur('@Elements',array());
			$elements[] = $objet;
			$this -> setValeur('@Elements', $elements);
		}
		return $this;
	}
	/** 2.1 ajout
	* se déplacer dans la hiérarchie des répertoires (sous la forme parm1, parm 2, etc)
	* @param (SG_Texte ou calculable) sous-répertoires du répertoire actuel séparés par des /
	* @return (SG_Repertoire ou SG_Erreur) : répertoire atteint
	**/
	function AllerA ($pLieu = '') {
		$ret = $this;
		$txt = SG_Texte::getTexte($pLieu);
		if ($txt !== '') {
			$srep = explode('/',SG_Texte::getTexte($pLieu));
			foreach ($srep as $lieu) {
				if (! isset($ret -> proprietes['@Repertoires'])) {
					$ret = new SG_Erreur('Ce répertoire n\'a pas de sous-répertoires : ', $ret -> proprietes['@Titre']);
					break;
				} else {
					$repertoires = $ret -> proprietes['@Repertoires'];
					if(! isset($repertoires[$lieu])) {
						$ret = new SG_Erreur('Sous-répertoire inexistant : ', $lieu);
						break;
					} else {
						$ret = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($repertoires[$lieu]['uid']);
					}
				}
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* récupère le répertoire en cours
	**/
	function EnCours() {
		$ret = $this;
		foreach ($this -> pointeur as $rep) {
			$repertoires = $ret -> getValeur('@Repertoires', null);
			if ($repertoires === null) {
				$ret = new SG_Erreur('pas de sous-répertoires');
				break;
			} elseif (!isset($repertoires[$rep])) {
				$ret = new SG_Erreur('sous-répertoire inexistant', $rep);
				break;
			}
			$ret = $repertoires[$rep];
		}
		return $ret;
	}
	/** 2.1 ajout ; 2.2 err 0176
	* Charge le contenu d'un répertoire
	* @param (string ou formule) $pDir : répertoire à charger sur le serveur
	* @param (string ou formule) $pTypeObjet : éventuellement Type d'objet à créer)
	**/
	function Charger($pDir = '', $pTypeObjet = '', $pChamp = '') {
		$ret = $this;
		$dir = SG_Texte::getTexte($pDir);
		$type = SG_Texte::getTexte($pTypeObjet);
		if ($type === '') {
			$ret = new SG_Erreur('0176');// obligatoire
		} else {
			$champ = SG_Texte::getTexte($pChamp);
			$handle = opendir($dir);
			if ($handle) {
				while (false !== ($entry = readdir($handle))) {
					set_time_limit(600);
					if ($entry != "." and $entry != "..") {
						$path = $dir . '/' . $entry;
						if (is_dir($path)) { // répertoire
							$tmp = new SG_Repertoire($entry, $this);
							if (getTypeSG($tmp) === '@Erreur') {
								$ret = $tmp;
								break;
							}
							$tmp -> Charger($path, $type, $champ);
							$this -> Ajouter($tmp);
						} else { // document
							$doc = SG_Rien::Nouveau($type);
							if (get_class($doc) === 'SG_Photo' or is_subclass_of($doc, 'SG_Photo')) {
								$doc -> Charger($dir, $entry);
							} else {
								$doc -> setFichier($champ, $path, $entry, "image/jpeg");
								$doc -> setValeur('@Titre', $entry);
								$doc -> setValeur('@Code', $entry);
								$doc -> Enregistrer();
							}
							$this -> Ajouter($doc);
							$doc = null;
						}
					}
				}
			}
		}
		$ok = $this -> Enregistrer();
		return $ret;
	}
	/** 2.1 ajout
	* Test si vide
	**/
	function EstVide() {
		$ret = (bool) sizeof($this -> getValeur('@Elements', array())) === 0;
		$ret = $ret and (bool)(sizeof($this -> getValeur('@Repertoires', array())) == 0);
		return new SG_VraiFaux($ret);
	}
	/** 2.1 ajout
	* Crée un lien qui ouvre l'image ou le document si pas image
	**/
	function creerLienCliquable($pURL) {
		$couchdb = $_SESSION['@SynerGaia'] -> sgbd;
		return $couchdb -> url . $this -> codeBaseComplet . '/' . $this -> codeDocument;
	}
	/** 2.1 ajout
	* recherche la vignette dans la photo, sinon vide
	**/
	function getVignette($pDocument) {
		if (isset($pDocument['vignette'])) {
			$ret = array ('type' => 'image/jpeg', 'data' => $pDocument['vignette']);
		} else {
			if (is_array($pDocument)) {
				$doc = $pDocument['doc'];
			} elseif(is_string($pDocument)) {
				$doc = $pDocument;
			}
			$fic = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($doc . '/_attachments'); // chercher les fichiers attachés du documents
			if (getTypeSG($fic) === '@Erreur' or $fic === null) {
				$ret = false;
			} else {
				$ret = $fic;
				$ret['data'] = SG_Image::resizeto(80,base64_encode($fic['data']));
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* Liste des titres du répertoires en cours
	**/
	function getTitresEnCours() {
		$ret = array();
		foreach ($this -> pointeur as $elt) {
			$ret[] = $elt -> getValeur('@Titre','?');
		}
		return $ret;
	}
	/** 2.1 ajout
	* Liste des titres du répertoires en cours
	**/
	function getURL($pURL = '') {
		$ret = $this -> titre;
		if ($this -> repparent !== null) {
			$ret = $this -> repparent -> getURL($pURL) . '/' . $ret;
		} else {
			$ret = $this -> codeBase . '/' . $this -> codeDocument;
		}
		return $ret;
	}
	/** 2.1 ajout
	* Liste des titres du répertoires en cours
	* @return : string html
	**/
	function getBoutons() {
		$ret = '';
		$parent = $this -> repparent;
		$elt = $this;
		while ($elt !== null) {
			$titre = $elt -> getValeur('@Titre','?');
			$clic = $elt -> getURL();
			$div = '<div style="padding-left: 10 px">';
			$div.= '<span class="repertoire-titre" ' . SG_Collection::creerLienCliquable($clic, $titre, "centre") . '>' . $titre . '</span>';
			$ret.= $div . $ret . '</div>';
			$elt = $parent;
			if ($elt !== null) {
				$parent = $elt -> repparent;
			}
		}
		return $ret;
	}
	/** 2.1 ajout ; 2.3 correctif comparaison
	* Trier localement les répertoires et les elements (il n'y a pas d'enregistrement)
	* @param (texte) $pQuoi : 'r' répertoires, 'e' éléments, 't' les deux (défaut 'e')
	* @param (formule) $pFiltre : critère pour le tri (défaut .@Titre)
	* @param (texte) $pOrdre : 'a' ascendant, 'd' descendant (défaut 'a')
	**/
	function Trier($pQuoi = 'e', $pFiltre = '', $pOrdre = '') {
		$quoi = strtolower(SG_Texte::getTexte($pQuoi));
		if ($quoi === '') {
			$quoi = 'e';
		}
		if (getTypeSG($pFiltre) === '@Formule') {
			$filtre = $pFiltre;
		} else {
			$filtre = SG_Texte::getTexte($pFiltre);
		}
		$_SESSION["filtre"] = $filtre;
		function tri($a, $b) {
			$f = $_SESSION["filtre"];
			if ($f === '') {
				$reta = $a['titre'];
				$retb = $b['titre'];
			} else {
				$doca = SG_Repertoire::getDocument($a);
				$docb = SG_Repertoire::getDocument($b);
				$reta = SG_Texte::getTexte($f -> calculerSur($doca));
				$retb = SG_Texte::getTexte($f -> calculerSur($docb));
			}
			if ($reta === $retb) {
				$ret = 0;
			} elseif ($reta < $retb) {
				$ret = -1;
			} else {
				$ret = 1;
			}
			return $ret;
		}
		$ordre = strtolower(SG_Texte::getTexte($pOrdre));
		if ($ordre === '') {
			$ordre = 'a';
		}
		if ($quoi === 'r' or $quoi === 't') {
			if (isset($this -> proprietes['@Repertoires'])) {
				ksort($this -> proprietes['@Repertoires']);
			}
		}
		if ($quoi === 'e' or $quoi === 't') {
			if (isset($this -> proprietes['@Elements'])) {
				usort($this -> proprietes['@Elements'], 'tri');
			}
		}
		unset($GLOBALS["filtre"]);
		return $this;
	}
	/** 2.1 ajout
	* Collection des répertoires
	**/
	function Repertoires() {
		$ret = new SG_Collection();
		if (isset($this -> proprietes['@Repertoires'])) {
			$ret -> elements = $this -> proprietes['@Repertoires'];
		}
		return $ret;
	}
	/** 2.1 ajout
	* Collection des éléments
	**/
	function Elements() {
		$ret = new SG_Collection();
		if (isset($this -> proprietes['@Elements'])) {
			$elements = $this -> proprietes['@Elements'];
			foreach ($elements as $elt) {
				$ret -> elements[] = $this -> getDocument($elt);
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* récupère un document
	**/
	static function getDocument($pEntree) {
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($pEntree['doc']);
		if ($ret === null) {
			$ret = new SG_Erreur('0156', $pEntree['doc']);
		}
		return $ret;
	}
	/** 2.1 ajout
	* éclate un répertoire unique version 2.0 en répertoires 2.1
	**/
	function Eclater() {
		if (isset($this -> proprietes['@Repertoires'])) {
			$repertoires = array() ;
			foreach($this -> proprietes['@Repertoires'] as $key => $rep) {
				// créer un nouveau répertoire (seul le titre et le parent sont récupérés)
				$newrep = new SG_Repertoire($key,$rep['proprietes']);
				$newrep -> setParent($this);
				// recopie les éléments et les répertoires de ce répertoire
				if (isset($rep['proprietes']['@Elements'])) {
					$newrep -> proprietes['@Elements'] = $rep['proprietes']['@Elements'];
				}
				if (isset($rep['proprietes']['@Repertoires'])) {
					$newrep -> proprietes['@Repertoires'] = $rep['proprietes']['@Repertoires'];
				}
				$newrep -> proprietes['@Parent'] = $this -> getUUID();
				$newrep -> proprietes['@Code'] = $this -> codeDocument . '/' . $key;
				// l'éclater
				$newrep -> Eclater(); // éclate et enregistre
				// mettre à jour l'id ici sur l'appelant
				$repertoires[$key] = array('uuid' => $newrep -> getUUID(), 'titre' => $key);
			}
			$this -> proprietes['@Repertoires'] = $repertoires;
		}
		$this -> Enregistrer();
		return $this;
	}
	/** 2.2 ajout
	* choix de photos ou documents dans un répertoire. Le résultat est une collection de photos
	**/
	public function Choisir($pOptions = null, $pAction = '') {
		// Boutons de navigation
		$ret = $this -> getBoutons();
		$ret.= '<div class="repertoire-page">'; //<span class="album-titre" onclick="">' . $this -> getValeur('@Titre','') . '</span>';
		// Répertoires
		$elements = $this -> getValeur('@Repertoires',array());
		if(! is_array($elements)) {
			$ret.= $elements -> toString();
		} else {
			if (sizeof($elements) > 0) {
				$ret.= '<div class="repertoire-repertoires">';
				foreach ($elements as $key => $elt) {
					$titre = $elt['titre'];
					$clic = $elt['uid'];
					$ret.= '<span class="repertoire-sstitre" ' . SG_Collection::creerLienCliquable($clic, $titre, "centre") . '>' . $titre . '</span>';
				}
				$ret.= '</div>';
			}
		}
		// Vignettes
		$elements = $this -> getValeur('@Elements',array());
		if(! is_array($elements)) {
			$ret.= $elements -> toString();
		} else {
			if (sizeof($elements) > 0) {
				$ret.= '<div class="repertoire-planche">';
				foreach ($elements as $key => $elt) {
					if (is_array($elt)) {
						$image = $this -> getVignette($elt);
						if ($image) {
							$ret.= '<div class="repertoire-vignette" ' . SG_Collection::creerLienCliquable($elt['doc'], $elt['titre'], "centre") . '>';
							$ret.= '<img class="" src="data:'. $image['type'] . ';base64,' .  $image['data'] . '" alt="' . $elt['titre'] . '">';
							$ret.= '</div>';
						}
					}
				}
				$ret.= '</div>';
			}
		}
		$ret.= '</div>';
		$opEnCours -> doc -> proprietes['@Principal'] = $this;
		return new SG_HTML($ret);
	}
	// Complément de classe spécifique à l'application (créé par la compilation)
	use SG_Repertoire_trait;
}
?>
