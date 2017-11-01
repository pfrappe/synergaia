<?php
/** SYNERGAIA fichizer pour le traitement de l'objet @Repertoire */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Repertoire_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Repertoire_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe */
	trait SG_Repertoire_trait{};
}

/**
 * SG_Repertoire : classe SynerGaia de gestion d'un album de photos
 * @version 2.4
 */
class SG_Repertoire extends SG_Document { //SG_DocumentCouchDB
	/** string Type SynerGaia '@Repertoire' */
	const TYPESG = '@Repertoire';
	/** string code du champ chemin */
	const CHAMPCHEMIN = 'chemin';
	
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Code de la base */
	const CODEBASE = 'synergaia_repertoires';

	/** string titre de l'entrée du répertoire */
	public $titre = '';
	
	/**
	 * string Le chemin est mis à jour dans la construction de l'objet et par SG_SynerGaia->getObjet avec les infos du navigateur
	 * @version 2.4 liste des codes des répertoires pour arriver à celui-ci (idxxx:nomx/idyyy:nomy/idzzz:nomz...)
	 */
	public $chemin = '';
	
	/**
	 * Termine la construction du document (base, id, typeSG, titre, chemin, parent)
	 * @param string $pCode
	 * @param string $pTitre
	 * @since 2.4 ajout
	 */ 
	function initDocument($pCode = '', $pTitre = '') {
		$code = SG_Texte::getTexte($pCode);
		if ($code !== '') {
			$this -> setValeur('@Code',$code);
			$titre = SG_Texte::getTexte($pTitre);
			if ($titre !== '') {
				$this -> setValeur('@Titre',$titre);
			} else {
				$this -> setValeur('@Titre',$code);
			}
		}
		// si pas de base, c'est un nouveau document et on initialise l'id
		if (is_null($this -> doc -> codeBase) or $this -> doc -> codeBase === '') {
			$this -> doc -> setBase(self::CODEBASE);
			$this -> doc -> codeDocument = $_SESSION['@SynerGaia'] -> sgbd -> getUUID();
		}
		$this -> doc -> proprietes['@Type'] = $this -> typeSG;
		$this -> titre = $this -> getValeur('@Titre','');
		// chemin par défaut. Sinon il faut le changer après cette méthode
		$this -> chemin = $this -> doc -> codeDocument . ':' . $this -> titre;
	}

	/**
	 * affichage indenté du répertoire et des liens vers les documents
	 * @since 2.1 ajout
	 * @param SG_Texte|SG_Formule $pAction : formule à exécuter dans le bouton correspondant à chaque étape du chemin
	 * @return SG_HTML
	 */
	public function AfficherPlanche($pAction = null) {
		// div cachée pour les infos à transmettre dans les liens (chemin notamment)
		$idform = SG_SynerGaia::idRandom();
		$ret = '<div><form id="' . $idform . '" name="rep" style="display:none;">';
		$ret.= '<input type="text" name="chemin" value="' . $this -> chemin . '"/>';
		$ret.= '</form></div>';
		// Boutons de navigation et titre
		$btn = $this -> getBoutons($idform, $pAction);
		if (getTypeSG($btn) === '@Erreur') {
			$ret.= $btn -> toHTML() -> texte;
		} else {
			$ret.= $btn;
		}
		$ret.= '<div class="sg-rep-page">';
		// Répertoires
		$elements = $this -> getValeur('@Repertoires',array());
		if(! is_array($elements)) {
			$ret.= $elements -> toString();
		} else {
			if (sizeof($elements) > 0) {
				$ret.= '<div class="sg-rep-repertoires">';
				foreach ($elements as $key => $elt) {
					$ret.= '<span class="sg-rep-sstitre" ' . self::onClick($elt['uid'], $idform) . '>' . $key . '</span>';
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
				$ret.= '<div class="sg-rep-planche">';
				foreach ($elements as $key => $elt) {
					if (is_array($elt)) {
						if (isset($elt['typeSG']) and $elt['typeSG'] === '@Erreur') {
							$err = new SG_Erreur($elt['code'],$elt['infos']);
							$err -> trace = $elt['trace'];
							$ret.= $err -> toHTML() -> texte;
						} else {
							$image = $this -> getVignette($elt);
							if (getTypeSG($image['data']) !== '@Erreur') {
								$id = 'vig-' . $key;
								$t = $elt['titre'];
								$ret.= '<div class="sg-rep-vignette"' . SG_Collection::creerLienCliquable($elt['doc'], $t , "centre");
								$ret.= $this -> creerLienZoom($id, $elt['doc']) . '>';
								$ret.= '<div id="' . $id . '" class="sg-rep-zoom"></div>';
								$ret.= '<img class="" src="data:'. $image['type'] . ';base64,' .  $image['data'] . '" alt="' . $t . '" title="' .$t . '">';
								$ret.= '</div>';
							} else {
								$id = 'vig-' . $key;
								$t = $elt['titre'];
								$ret.= '<div class="sg-rep-vignette"' . SG_Collection::creerLienCliquable($elt['doc'], $t , "centre");
								$ret.= $this -> creerLienZoom($id, $elt['doc']) . '>';
								$ret.= '<div id="' . $id . '" class="sg-rep-zoom"></div>';
								$ret.= $t . ' : ' . $image['data'] -> getMessage();
								$ret.= '</div>';
							}
						}
					}
				}
				$ret.= '</div>';
			}
		}
		$ret.= '</div>';
		return new SG_HTML($ret);
	}

	/**
	 * Affichage indenté du répertoire et des liens vers les documents
	 * @since 2.1 ajout
	 * @param string $pOptions
	 * @param string $pAction
	 * @return SG_HTML
	 */
	public function toHTML($pOptions = null, $pAction = '') {
		$ret = '<ol class="sg-rep"><span class="titre" onclick="">' . $this -> getValeur('@Titre','') . '</span>';
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
						$ret.= '<div class="sg-rep-photo" ' . SG_Collection::creerLienCliquable($elt['doc'], $elt['titre'], "centre") . '>';
						$ret.= '<img class="" src="data:'. $image['type'] . ';base64,' .  $image['data'] . '">';
						$ret.= '</div>';
					} else {
						$ret.= '<li class="sg-rep-doc" ' . SG_Collection::creerLienCliquable($elt['doc'], $elt['titre'], "centre") . '>';
						$ret.=  $elt['titre'];
						$ret.= '</li>';
					}
				} else {
					$type = getTypeSG($elt);
					if ($type !== '@Repertoire') {
						$ret.= '<li class="sg-rep-doc">' . $elt -> toString() . '</li>';
					}
				}
			}
		}
		$ret.= '</ol>';
		return new SG_HTML($ret);
	}

	/**
	 * affichage indenté du répertoire et des liens vers les documents
	 * @since 2.1 ajout
	 * @param string $pOptions
	 * @return SG_HTML
	 */
	public function toListeHTML($pOptions = null) {
		$ret = '<li class="sg-rep"><span class="titre">' . $this -> getValeur('@Titre','') . '</span><ol>';
		$elements = $this -> getValeur('@Repertoires',array());
		foreach ($elements as $key => $elt) {
			$type = getTypeSG($elt);
			if ($type === '@Repertoire') {
				$ret.= $elt -> toListeHTML($pOptions);
			}
		}
		$elements = $this -> getValeur('@Elements',null);
		foreach ($elements as $key => $elt) {
			if (is_array($elt)) {				
				$ret.= '<li class="sg-rep-doc" ' . SG_Collection::creerLienCliquable($elt['doc'], $elt['titre'], "centre") . '>' .  $elt['titre'] . '</li>';
			} else {
				$type = getTypeSG($elt);
				if ($type !== '@Repertoire') {
					$ret.= '<li class="sg-rep-doc">' . $elt -> toString() . '</li>';
				}
			}
		}
		$ret.= '</ol></li>';
		return new SG_HTML($ret);
	}

	/**
	 * ajout d'un document ou d'une collection de document ou d'une entrée de répertoire ou d'un répertoire
	 * Si l'objet est une erreur, l'ajout ne se fait pas sauf si pForce est à true ou @Vrai
	 * 
	 * @since 2.1 ajout
	 * @param SG_Objet|SG_Formule $pQuelqueChose l'objet à ajouter
	 * @param boolean|SG_VraiFaux $pSansDouble : refus d'enregistrement d'entrée en double (test sur uid)
	 * @param boolean|SG_VraiFaux $pForce : forcer l'enregistrement d'une entrée ' @Erreur' (par défaut faux)
	 * @return SG_Repertoire|SG_Erreur ce répertoire ou une erreur
	 */
	public function Ajouter($pQuelqueChose = null, $pSansDouble = false, $pForce = false) {
		$ret = $this;
		$type = getTypeSG($pQuelqueChose);
		$force = SG_VraiFaux::getBooleen($pForce);
		$sansdouble = SG_VraiFaux::getBooleen($pSansDouble);
		if ($type === '@Formule') {
			$objet = $pQuelqueChose -> calculer();
			$type = getTypeSG($objet);
		} else {
			$objet = $pQuelqueChose;
		}
		if (getTypeSG($objet) === '@Erreur' and $force === false) {
			$ret = $objet;
		} else {
			if ($type === '@Texte') {
				$objet = SG_Texte::getTexte($objet);
			}
			// rangement (soit répertoire (string), soit doc, soit collection, soit objet)
			if (is_string($objet)) {
				if ( ($sansdouble === false) or ($sansdouble === true and !$this -> entreeExiste('r', $objet))) {
					$newrep = new SG_Repertoire($objet);
					$this -> doc -> proprietes['@Repertoires'][$newrep -> titre] = array('uid' => $newrep -> getUUID(), 'titre' => $newrep -> titre);
				}
			} elseif ($type === '@Repertoire') {
				// un répertoire autonome vers lequel pointer (déjà enregistré)
				if ( ($sansdouble === false) or ($sansdouble === true and !$this -> entreeExiste('r', $objet -> getUUID()))) {
					$titre = $objet -> titre;
					if ($titre === '') {
						$titre = $objet -> getValeur('@Titre', '');
					}
					if ($titre === '') {
						$this -> doc -> proprietes['@Repertoires'][] = array('uid' => $objet -> getUUID(), 'titre' => '?');
					} else {
						$this -> doc -> proprietes['@Repertoires'][$titre] = array('uid' => $objet -> getUUID(), 'titre' => $titre);
					}
				}
			} elseif (SG_Dictionnaire::isObjetDocument($objet)) {
				if ( ($sansdouble === false) or ($sansdouble === true and !$this -> entreeExiste('r', $objet -> getUUID()))) {
					$elements = $this -> getValeur('@Elements',array());
					$entree = array('titre' => $objet -> toString(), 'doc' => $objet -> getUUID(), 'type' => getTypeSG($objet));
					$v = $objet -> getValeur('@Vignette','');
					if ($v !== '') {
						$entree['vignette'] = $v;
					}
					$elements[] = $entree;
					$this -> setValeur('@Elements', $elements);
				}
			} elseif ($type === '@Collection') {
				// on ne garde que les pointeurs vers les documents ou les répertoires (qui doivent déjà avoir été enregistrés)
				$elements = $this -> getValeur('@Elements',array());
				foreach ($objet -> elements as $key => $elt) {
					if ($sansdouble === false or ($sansdouble === true and !$this -> entreeExiste('', $elt -> getUUID()))) {
						$typeelt = getTypeSG($elt);
						if ($typeelt === '@Repertoire') {
							// entrée répertoire
							$this -> doc ->proprietes['@Repertoires'][$elt] = array('titre' => $elt -> titre, 'uid' => $elt -> getUUID(), 'type' => $typeelt);
						} else {
							// entrée document
							$entree = array('titre' => $elt -> toString(), 'doc' => $elt -> getUUID(), 'type' => getTypeSG($elt));
							if (SG_Dictionnaire::deriveDe(getTypeSG($elt), '@Photo')) {
								$entree['vignette'] = $elt -> getValeur('@Vignette','vignette');
							}
							$elements[] = $entree;
						}
					}
				}
				$this -> setValeur('@Elements', $elements);
			} else {
				if (($sansdouble === false) or ($sansdouble === true and !$this -> entreeExiste('r', $objet -> getUUID()))) {
					$elements = $this -> getValeur('@Elements',array());
					$elements[] = $objet;
					$this -> setValeur('@Elements', $elements);
				}
			}
		}
		return $ret;
	}

	/**
	 * Se déplacer dans la hiérarchie des répertoires (sous la forme parm1, parm 2, etc)
	 * si on passe deux fois par le même répertoire, le chemin est simplifié
	 * 
	 * @since 2.1 ajout
	 * @param SG_Texte|SG_Formule sous-répertoires du répertoire actuel séparés par des /
	 * @return SG_Repertoire|SG_Erreur répertoire atteint ou erreur
	 */
	function AllerA ($pLieu = '') {
		$ret = $this;
		$txt = SG_Texte::getTexte($pLieu);
		$chemin = $this -> chemin;
		$repdejalu = array();
		if ($txt !== '') {
			$srep = explode('/',SG_Texte::getTexte($pLieu));
			foreach ($srep as $lieu) {
				if (! isset($ret -> doc -> proprietes['@Repertoires'])) {
					$ret = new SG_Erreur('0220', $ret -> getValeur('@Titre', ''));// pas de sous rép
				} else {
					$idnom = self::extractIDNom($lieu);
					$nom = $idnom['nom'];
					$repertoires = $ret -> getValeur('@Repertoires');
					if(! isset($repertoires[$nom])) {
						$ret = new SG_Erreur('0221', $nom);
					} else {
						if (! isset($repertoires[$nom]['uid'])) {
							$ret = new SG_Erreur('0237', $nom);// pas répertoire
						} else {
							$ret = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($repertoires[$nom]['uid']);
							$this -> ajouterChemin($ret -> doc -> codeDocument . ':' . $nom);
						}
					}
				}
				if (getTypeSG($ret) === '@Erreur') {
					break;
				}
			}
		}
		return $ret;
	}

	/**
	 * récupère le répertoire en cours
	 * @since 2.1 ajout
	 * @return SG_Repertoire|SG_Erreur
	 */
	function EnCours() {
		$ret = $this;
		foreach ($this -> pointeur as $rep) {
			$repertoires = $ret -> getValeur('@Repertoires', null);
			if ($repertoires === null) {
				$ret = new SG_Erreur('0222');
				break;
			} elseif (!isset($repertoires[$rep])) {
				$ret = new SG_Erreur('0223', $rep);
				break;
			}
			$ret = $repertoires[$rep];
		}
		return $ret;
	}

	/**
	 * Charge le contenu d'un répertoire
	 * 
	 * @since 2.1 ajout
	 * @version 2.2 err 0176
	 * @param string|SG_Texte|SG_Formule $pDir : répertoire à charger sur le serveur
	 * @param string|SG_Texte|SG_Formule $pTypeObjet : éventuellement Type d'objet à créer)
	 * @param boolean|SG_VraiFaux|SG_Formule $pSansDouble : refus des doublons
	 * @param string|SG_Texte|SG_Formule $pChamp : nom du champ de fichier si pas '_attachments"
	 * @return SG_Repertoire|SG_Erreur ce répertoire ou une erreur
	 */
	function Charger($pDir = '', $pTypeObjet = '', $pSansDouble = false, $pChamp = '') {
		$ret = $this;
		$dir = SG_Texte::getTexte($pDir);
		$type = SG_Texte::getTexte($pTypeObjet);
		$sansdouble = SG_VraiFaux::getBooleen($pSansDouble);
		if ($type === '') {
			$ret = new SG_Erreur('0176');// obligatoire
		} else {
			$champ = SG_Texte::getTexte($pChamp);
			$handle = @opendir($dir);
			if ($handle === false) {
				$ret = new SG_Erreur('0231', $dir);// inaccessible
			} else {
				while (false !== ($entry = readdir($handle))) {
					set_time_limit(600);
					if ($entry != "." and $entry != "..") {
						$path = $dir . '/' . $entry;
						if (is_dir($path)) { // répertoire
							// tester si existe déjà dans le répertoire en cours
							$tmp = $this -> AllerA($entry);
							if (getTypeSG($tmp) === '@Erreur') {
								$tmp = new SG_Repertoire();
								$tmp -> initDocument($entry);
								$this -> Ajouter($tmp, $sansdouble);
							}
							if (getTypeSG($tmp) === '@Erreur') {
								$ret = $tmp;
								break;
							}
							// descendre dans le répertoire du dessous
							$tmp -> Charger($path, $type, $champ, $sansdouble);
						} else { // document
							$doc = SG_Rien::Nouveau($type);
							if (get_class($doc) === 'SG_Photo' or is_subclass_of($doc, 'SG_Photo')) {
								$doc -> Charger($dir, $entry, '', true, $sansdouble);
								if (getTypeSG($doc) === '@Erreur' and $doc -> code === '0253') {
									if (getTypeSG($doc -> trace) === '@Collection') {
										$doc = $doc -> trace -> elements[0];
									} else {
										$doc = $doc -> trace;
									}
								}
							} else {
								$doc -> setFichier($champ, $path, $entry, "image/jpeg");
								$doc -> setValeur('@Titre', $entry);
								$doc -> setValeur('@Code', $entry);
								$doc -> Enregistrer();
							}
							$this -> Ajouter($doc, $sansdouble);
							$doc = null;
						}
					}
				}
			}
		}
		$ok = $this -> Enregistrer();
		return $ret;
	}

	/**
	 * Test si vide
	 * 
	 * @since 2.1 ajout
	 * @param inutilisé
	 * @return SG_VraiFaux
	 */
	function EstVide($pChamp = NULL) {
		$champ = SG_Texte::getTexte($pChamp);
		if ($champ === '@Elements') {
			$ret = (bool) sizeof($this -> getValeur('@Elements', array())) === 0;
		} elseif ($champ === '@Repertoires') {
			$ret = (bool) sizeof($this -> getValeur('@Repertoires', array())) === 0;
		} elseif ($champ === NULL) { 
			$ret = (bool) sizeof($this -> getValeur('@Elements', array())) === 0;
			$ret = $ret and (bool)(sizeof($this -> getValeur('@Repertoires', array())) == 0);
		} else {
			$ret = parent::EstVide($pChamp);
		}
		return new SG_VraiFaux($ret);
	}

	/**
	 * Crée un lien qui ouvre l'image ou le document si pas image
	 * @since 2.1 ajout
	 * @param string $pURL
	 * @return string le lien calculé
	 */
	function creerLienCliquable($pURL) {
		$couchdb = $_SESSION['@SynerGaia'] -> sgbd;
		return $couchdb -> url . $this -> doc -> codeBaseComplet . '/' . $this -> doc -> codeDocument;
	}

	/**
	 * Recherche la vignette dans la photo, sinon vide
	 * 
	 * @since 2.1 ajout
	 * @param array $pDocument données d'image ou de fichier
	 * @return array tableau des données de fichier
	 */
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

	/**
	 * Liste des titres du répertoires en cours
	 * @since 2.1 ajout
	 * @return array titre du répertoire
	 */
	function getTitresEnCours() {
		$ret = array();
		foreach ($this -> pointeur as $elt) {
			$ret[] = $elt -> getValeur('@Titre','?');
		}
		return $ret;
	}

	/**
	 * Chemin parcouru pour accéder au répertoire en cours
	 * 
	 * @since 2.1 ajout
	 * @param string|SG_Texte|SG_Formule $pID : id du <form> contenant le chemin complet
	 * @param any $pAction : non utilisé
	 * @todo $pAction : donner l'action à exécuter ?
	 * @return string html
	 * @uses JS SynerGaia.diaporama()
	 */
	function getBoutons($pID = '', $pAction = null) {
		$ret = '';
		$reps = explode('/', $this -> chemin);
		$i = 0;
		// se positionner sur le premier répertoire du chemin
		$idnom = self::extractIDNom($reps[0]);
		if ($this -> doc -> codeDocument === $idnom['id']) {
			$elt = $this;
		} else {
			$elt = new SG_Repertoire(self::CODEBASE . '/' . $idnom['id']);
		}
		$fin = sizeof($reps) - 1;
		// boucler sur les étapes du chemin et créer les boutons correspondant
		while ($i <= $fin) {
			if (getTypeSG($elt) === '@Erreur') {
				$ret = $elt;
				break;
			} else {
				$titre = $elt -> getValeur('@Titre','?');
				if ($i >= $fin) { // sauf le dernier
					$ret.= '<span class="sg-rep-titre" >' . $titre . '</span>';
					break;
				} else {
					$clic = SG_Collection::creerLienCliquable($elt -> getUUID(), $titre, "centre", $pID);
					$ret.= '<span class="sg-rep-bouton" ' . $clic . '>' . $titre . '</span>';
				}
				$i++;
				$elt = $elt -> AllerA($reps[$i]);
			}
		}
		// ajouter un icône bouton 'diaporama'
		if (getTypeSG($ret) !== '@Erreur') {
			$op = SG_Pilote::OperationEnCours() -> reference;
			$codebtn = new SG_Bouton('diaporama','.@AfficherPhoto');
			$ret.= '<div class="sg-rep-btn-diaporama" onclick="SynerGaia.diaporama(event,\'' . $op . '\',\'' . $codebtn -> code . '\',\'' . $this -> getUUID() .'\',0)"';
			$ret.= ' title="Lancer le diaporama"></div>';
		}
		return $ret;
	}

	/**
	 * Trier localement les elements (il n'y a pas d'enregistrement)
	 * @since 2.1 ajout
	 * @version 2.3 correctif comparaison
	 * @version 2.4 seulement trier éléments
	 * @param SG_Formule $pCritere critère pour le tri (défaut .@Titre)
	 * @param string|SG_Texte|SG_Formule $pOrdre : 'a' ascendant, 'd' descendant (défaut 'a')
	 */
	function Trier($pCritere = '', $pOrdre = '') {
		if ($pCritere instanceof SG_Formule) {
			$critere = $pCritere;
		} else {
			$critere = SG_Texte::getTexte($pCritere);
		}
		$_SESSION["critere"] = $critere;
		/**
		 * comparaison entre deux éléments
		 * @param any $a
		 * @param any $b
		 * @return integer 1, 0, -1
		 */ 
		function tri($a, $b) {
			$f = $_SESSION["critere"];
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
		if (isset($this -> doc -> proprietes['@Elements'])) {
			usort($this -> doc -> proprietes['@Elements'], 'tri');
		}
		unset($_SESSION["critere"]);
		return $this;
	}

	/**
	 * Collection des répertoires du répertoire
	 * 
	 * @since 2.1 ajout
	 * @return SG_Collection
	 */
	function Repertoires() {
		$ret = new SG_Collection();
		if (isset($this -> doc -> proprietes['@Repertoires'])) {
			foreach ($this -> doc -> proprietes['@Repertoires'] as $key => $elt) {
				$ret -> elements[$key] = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($elt['uid']);
				$ret -> elements[$key] -> proprietes['@Indice'] = new SG_Texte($key);
			}
		}
		return $ret;
	}

	/**
	 * Collection des éléments du répertoire
	 * @since 2.1 ajout
	 * @version 2.4 param
	 * @param string|SG_Texte|SG_Formule $pTitre
	 * @return SG_Collection
	 */
	function Elements($pTitre = '') {
		$titre = SG_Texte::getTexte($pTitre);
		$ret = new SG_Collection();
		if (isset($this -> doc -> proprietes['@Elements'])) {
			$elements = $this -> doc -> proprietes['@Elements'];
			foreach ($elements as $elt) {
				if ($titre !== '') {
					if ($elt['titre'] === $titre) {
						$ret = $this -> getDocument($elt);
						break; // trouvé !
					}
				} else {
					$ret -> elements[] = $this -> getDocument($elt);
				}
			}
		}
		return $ret;
	}

	/**
	 * Récupère un document
	 * @since 2.1 ajout
	 * @param string $pEntree code de l'entrée à récupérer
	 * @return SG_Objet|SG_Erreur
	 */
	static function getDocument($pEntree) {
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($pEntree['doc']);
		if ($ret === null) {
			$ret = new SG_Erreur('0156', $pEntree['doc']);
		}
		return $ret;
	}

	/**
	 * choix de photos ou documents dans un répertoire. Le résultat est une collection de photos
	 * 
	 * @since 2.2 ajout
	 * @param string|SG_Texte|SG_Formule $pOptions
	 * @param string|SG_Texte|SG_Formule $pAction
	 * @return SG_HTML
	 */
	public function Choisir($pOptions = null, $pAction = '') {
		$opEnCours = SG_Pilote::OperationEnCours();
		// Boutons de navigation
		$ret = $this -> getBoutons();
		$ret.= '<div class="sg-rep-page">'; //<span class="album-titre" onclick="">' . $this -> getValeur('@Titre','') . '</span>';
		// Répertoires
		$elements = $this -> getValeur('@Repertoires',array());
		if(! is_array($elements)) {
			$ret.= $elements -> toString();
		} else {
			if (sizeof($elements) > 0) {
				$ret.= '<div class="sg-rep-repertoires">';
				foreach ($elements as $key => $elt) {
					$titre = $key;
					$clic = $elt['uid'];
					$ret.= '<span class="sg-rep-sstitre" ' . SG_Collection::creerLienCliquable($clic, $titre, "centre") . '>' . $titre . '</span>';
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
				$ret.= '<div class="sg-rep-planche">';
				$codeChampHTML = SG_Champ::codeChampHTML($opEnCours -> reference . '/@Principal');;
				foreach ($elements as $key => $elt) {
					if (is_array($elt)) {
						$image = $this -> getVignette($elt);
						if ($image) {
							$ret.= '<div class="sg-rep-vignette" >';
							$ret.= '<img class="" src="data:'. $image['type'] . ';base64,' .  $image['data'] . '" alt="' . $elt['titre'] . '">';
							$ret.= '<div  class="sg-rep-checkbox">';
							$ret.= '<input type="checkbox" name="' .  $codeChampHTML . '[]" value="' . $elt['doc'] . '">';
							$ret.= $elt['titre'];
							$ret.= '</div></div>';
						}
					}
				}
				$ret.= '</div>';
			}
		}
		$ret.= '</div>';
		return new SG_HTML($ret);
	}

	/**
	 * Calcule le code html pour créer un lien sur une zone de photo
	 * @since 2.4 ajout
	 * @param string $id visé
	 * @param string $element texte à cliquer
	 * @return string texte html à insérer
	 * @uses JS SynerGaia.photoZoom()
	 */
	static function creerLienZoom($id, $element) {
		$ret = '';
		if (is_string($element)) {
			$ret = 'onmouseover="SynerGaia.photoZoom(event,\'' . htmlentities($element, ENT_QUOTES, 'UTF-8') . '\',\'' . $id . '\')"';
		}
		return $ret;
	}

	/**
	 * fonction utilitaire pour enlever les boucles du chemin (on prend le dernier parcouru)
	 * Le chemin doit être bien formé (idxxx:nomx/idyyy:nomy etc.)
	 * 
	 * @since 2.4 ajout
	 * @param string $pChemin
	 * @return string chemin simplifié
	 */
	function ajouterChemin($pChemin) {
		if (is_null($pChemin) or $pChemin === '') {
			$chemin = $this -> doc -> codeDocument . ':' . $this -> titre;
		} else {
			$chemin = $pChemin;
		}
		if ($this -> chemin === '') {
			$this -> chemin = $chemin;
		} else {
			$ipos = strpos($this -> chemin, $chemin);
			if ($ipos === false) {
				$this -> chemin = $this -> chemin . '/' . $chemin;
			} else {
				$this -> chemin = substr($this -> chemin, 0, $ipos + strlen($chemin));
			}
		}
		return $this;
	}

	/**
	 * Donne le chemin actuel du répertoire (par défaut : lui-même)
	 * @since 2.4 ajout
	 * @return string chemin
	 */
	function getChemin() {
		if ($this -> chemin === '') {
			$this -> chemin = $this -> doc -> codeDocument . ':' . $this -> titre;
		}
		return $this -> chemin;
	}

	/**
	 * Met à jour le chemin actuel du répertoire
	 * @since 2.4 ajout
	 * @param string $pChemin
	 * return string le chemin
	 */
	function setChemin($pChemin) {
		$this -> chemin = $pChemin;
		return $this -> chemin;
	}

	/**
	 * Calcul le code html pour l'affichage d'une photo
	 * 
	 * @since 2.4 ajout
	 * @param integer|SG_Nombre|SG_Formule $pNumero numéro de la photo
	 * @return SG_HTML html autour de l'image
	 * @uses JS SynerGaia.fermerdiaporama(), SynerGaia.diaporama()
	 */
	public function AfficherPhoto($pNumero = null) {
		// infos étape		
		$op = SG_Pilote::OperationEnCours();
		$no = $op -> proprietes['$1'] -> texte;
		$opcode = $op -> reference;
		$opbtn = $op -> boutonencours;
		// préparer les paramètres
		if (is_null($pNumero)) {
			if($no == '') {
				$no = 0;
			} else {
				$no = strval($no);
			}
		} else {
			$no = SG_Nombre::getNombre($pNumero);
		}
		// contrôler
		if (!isset($this -> doc -> proprietes['@Elements'])) {
			$ret = new SG_Erreur("Ce répertoire n'a pas de photos");
		} else {
			$elements = $this -> doc -> proprietes['@Elements'];
			if (! isset($elements[$no])) {
				$ret = new SG_Erreur("Ce répertoire n'a pas la photo numéro %s", $no);
			} else {
				// récupérer la photo
				$id = $elements[$no]['doc'];
				$photo = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($id);
				$fichier = $photo -> doc -> getFichier();
				if(getTypeSG($fichier) === '@Erreur') {
					$ret = $fichier;
				} else {
					$ret = '<div id="' . $id . '"  class="sg-diaporama" onclick="SynerGaia.fermerdiaporama()">';
					// bouton prev
					$ret.= '<div class="sg-btn-prev"';
					if ($no > 0) {
						$clic = 'SynerGaia.diaporama(event,\'' . $opcode . '\',\'' . $opbtn . '\',\'' . $this -> getUUID() .'\',' . ($no - 1) . ')';
						$ret.= ' title="Photo précédente" onclick="' . $clic . '">';
						$ret.= '<span style="position: absolute;top: 300px;">&lt;</span>';
					} else {
						$ret.= '><img class="sg-photo-prec" style="opacity:0;">';
					}
					$ret.= '</div>';
					// photo
					$ret.= '<img class="sg-photo-img" src="data:'. $fichier['type'] . ';base64,' .  base64_encode($fichier['data']) . '" width="auto" height="97%">';
					// bouton next
					$ret.= '<div class="sg-btn-next"';
					if ($no < sizeof($elements)) {
						$clic = 'SynerGaia.diaporama(event,\'' . $opcode . '\',\'' . $opbtn . '\',\'' . $this -> getUUID() .'\',' . ($no + 1) . ')';
						$ret.= ' title="Photo suivante" onclick="' . $clic . '" >';
						$ret.= '<span style="position: absolute;top: 300px;">&gt;</span>';
					} else {
						$ret.= '><img class="sg-photo-suiv" style="opacity:0;">';
					}
					$ret.= '</div>';
				}
			}
		}
		if (getTypeSG($ret) !== '@Erreur') {
			$ret = new SG_HTML($ret);
			$ret -> cadre = 'centre';
		}
		return $ret;
	}

	/**
	 * extrait une étape de chemin en deux parties (id, nom)
	 * 
	 * @since 2.4 ajout
	 * @param string $pIDNom étape du chemin
	 * @return array ('id' => codeDocument, 'nom' => titre);
	 */
	static function extractIDNom($pIDNom = '') {
		$ret = array('id' => '', 'nom' => '');
		$ipos = strpos($pIDNom, ':');
		if ($ipos !== false) {
			$ret['nom'] = substr($pIDNom, $ipos + 1);
			$ret['id'] = substr($pIDNom, 0, $ipos);
		} else {
			$ret['nom'] = $pIDNom;
		}
		return $ret;
	}

	/**
	 * onClick sur Répertoire sur place
	 * @since 2.4 ajout
	 * @param string $uirep
	 * @param string $idform
	 * @return string
	 * @uses JS SynerGaia.launchOperation(), SynerGaia.champs()
	 */
	static function onClick($uirep, $idform) {
		$op = SG_Pilote::OperationEnCours();
		$ret = 'onclick="SynerGaia.launchOperation(event,\'' . SG_Navigation::URL_VARIABLE_OPERATION . '=' . $op -> reference;
		$ret.= '&' . SG_Navigation::URL_VARIABLE_ETAPE . '=' . $op -> prochaineEtape;
		$ret.= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=' . $uirep . '\'';
		$ret.= ',SynerGaia.champs(\'' . $idform . '\'), false)"';
		return $ret;
	}

	/**
	 * supprime une entrée de la liste des répertoires
	 * 
	 * @since 2.4 ajout
	 * @param string|SG_Texte|SG_Formule $pType type d'entrée à supprimer
	 * @param string|SG_Texte|SG_Formule $pNom code de l'entrée de répertoire à supprimer
	 * @return SG_Repertoire $this
	 */
	function SupprimerEntree($pType = null, $pNom = '') {
		$type = strtolower(substr(SG_Texte::getTexte($pType), 0, 1));
		$nom = SG_Texte::getTexte($pNom);
		$ret = $this;
		if ($type === 'e') {
			unset($this -> doc -> proprietes['@Elements'][$nom]);
		} elseif ($type === 'r') {
			unset($this -> doc -> proprietes['@Repertoires'][$nom]);
		} else {
			$ret = new SG_Erreur('0241', $type);
		}
		return $ret;
	}

	/**
	 * Rattrape le ratage de l'import des vignettes dans un répertoire
	 * @return SG_Repertoire $this
	 */
	function ImporterVignettes() {
		foreach($this -> doc -> proprietes['@Elements'] as &$entree) {
			$objet = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($entree['doc']);
			if (SG_Dictionnaire::deriveDe(getTypeSG($objet), '@Photo')) {
				$entree['vignette'] = $objet -> getValeur('@Vignette','vignette'); //$objet -> getFichier();
			}
		}
		return $this;
	}

	/**
	 * Teste si une entrée existe (c'est à dire que l'uid est déjà cité dans les éléments ou les répertoires)
	 * 
	 * @since 2.4 ajout
	 * @param string|SG_Texte|SG_Formule $pType type d'élément à chercher ('e' élement, 'r' répertoire, sinon les deux (par défaut))
	 * @param string|SG_Texte|SG_Formule $pUID
	 * @return boolean false ou indice de l'entrée
	 */
	function entreeExiste($pType = '', $pUID = '') {
		if (is_string($pUID)) {
			$uid = $pUID;
		} else {
			$uid = $pUID -> getUUID();
		}
		$type = strtolower($pType);
		$ret = false;
		if ($type !== 'r') {
			if (isset($this -> doc -> proprietes['@Elements'])) {
				foreach ($this -> doc -> proprietes['@Elements'] as $elt) {
					if ($elt['doc'] === $uid) {
						$ret = true;
						break;
					}
				}
			}
		}
		if ($ret === false and $type !== 'e') {
			if (isset($this -> doc -> proprietes['@Repertoires'])) {
				foreach ($this -> doc -> proprietes['@Repertoires'] as $elt) {
					if ($elt['uid'] === $uid) {
						$ret = true;
						break;
					}
				}
			}
		}
	}

	/**
	 * Boucle d'une instruction sur chaque élément ou répertoire ou les deux.
	 * Les lectures et les traitements se font un par un (à la différence des @Collection)
	 * 
	 * @since 2.4 ajout
	 * @param string|SG_Texte|SG_Formule $pType : type d'élément ('e' élément - par défaut, 'r' répertoire)
	 * @param SG_Formule $pFormule : formule à exécuter
	 * @return SG_Collection des résultats
	 */
	function PourChaque ($pType = 'e', $pFormule = null) {
		$type = strtolower(substr(SG_Texte::getTexte($pType), 0, 1));
		if ($type !== 'e' and $type !== 'r') {
			$ret = new SG_Erreur('0254', $type);
		} else {
			$res = array();
			$formule = $pFormule;
			if ($type === 'e') {
				$collec = $this -> doc -> proprietes['@Elements'];
			} else {
				$collec = $this -> doc -> proprietes['@Repertoires'];
			}
			foreach ($collec as $elt) {
				if ($type === 'e') {
					$doc = $this -> getDocument($elt);
				} else {
					$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($elt['uid']);
				}
				$res[] = $formule -> calculerSur($doc);				
			}	
			$ret = new SG_Collection();
			$ret -> elements = $res;	
		}
		return $ret;
	}

	/**
	 * Exécuté avant l'enregistrement :
	 * - tri des répertoires et contrôle des clés
	 * @since 2.4 ajout
	 * @return boolean true
	 */
	function preEnregistrer() {
		$sgbd = $_SESSION['@SynerGaia'] -> sgbd;
		if (isset($this -> doc -> proprietes['@Repertoires'])) {
			// contrôle des clés
			foreach ($this -> doc -> proprietes['@Repertoires'] as $key => $rep) {
				$titre = $sgbd -> getTitre($rep['uid']);
				if (! is_null($titre) and $titre !== '') {
					if ($titre !== $key) {
						// si la clé n'est pas le titre du répertoire on la remplace sauf si elle existe
						if (!isset ($this -> doc -> proprietes['@Repertoires'][$titre])) {
							$this -> doc -> proprietes['@Repertoires'][$titre] = $rep;
							unset($this -> doc -> proprietes['@Repertoires'][$key]);
						}
					}
				}
			}
			// tri
			ksort($this -> doc -> proprietes['@Repertoires']);
		}
		return true;
	}

	/**
	 * Remplace une entrée de document par une autre
	 * 
	 * @since 2.7
	 * @param string|SG_Texte|SG_IDDoc|SG_Document|SG_Formule $pOriginal ancien document ou son id
	 * @param string|SG_Texte|SG_IDDoc|SG_Document|SG_Formule $pNouveau document ou id du remplaçant
	 * @return SG_Repertoire
	 */
	function RemplacerEntree($pOriginal = null, $pNouveau = null) {
	}

	// Complément de classe spécifique à l'application (créé par la compilation)
	use SG_Repertoire_trait;
}
?>
