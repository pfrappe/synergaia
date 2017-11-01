<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Photo */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
  
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Photo_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Photo_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.1.1 */
	trait SG_Photo_trait{};
}

/**
 * SG_Photo : classe SynerGaia de gestion d'une photo
 * 
 * @since 2.1
 * @version 2.4
 * @version 2.7 @Code now is md5 of file so its possible to see double files in database
 * @todo dans toutes les classes normaliser les sorties de toHTML (string ou SG_HTML)
 */
class SG_Photo extends SG_Document {
	/** string Type SynerGaia '@Photo' */
	const TYPESG = '@Photo';

	/** string Code de la base */
	const CODEBASE = 'synergaia_photos';
	
	/** integer Taille par défaut de la vignette 100px */
	const VIGNETTE_MAX = 100;

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;
	
	/**
	 * Calcule le code html pour l'affichage d'une photo
	 * @since 2.1 ajout
	 * @return SG_HTML|SG_Erreur
	 * @uses SynerGaia.fullScreen()
	 */
	function AfficherPhoto() {
		$ret = '';
		$fichier = $this -> doc -> getFichier();
		if(getTypeSG($fichier) === '@Erreur') {
			$ret = $fichier;
		} else {
			$id = sha1($fichier['nom']);
			$ret.= '<div id="' . $id . '"  class="sg-photo-div" onclick="SynerGaia.fullScreen(event,\'' . $id . '\')" data-sg="0">';
			$ret.= '<img class="sg-photo-img" src="data:'. $fichier['type'] . ';base64,' .  base64_encode($fichier['data']) . '" width="95%" height="95%">';
			$ret.= '</div>';
		}
		return new SG_HTML($ret);
	}

	/**
	 * Charge le contenu d'un répertoire
	 * 
	 * @since 2.1 ajout
	 * @param string|SG_Texte|SG_Formule $pDir : répertoire à charger sur le serveur
	 * @param string|SG_Texte|SG_Formule $pEntry : nom du fichier à lire)
	 * @param string|SG_Texte|SG_Formule $pTitre : titre et code de la photo
	 * @param boolean|SG_VraiFaux|SG_Formule $pSansDouble ne charge qu'une fois le même code photo
	 * @return SG_Photo|SG_Erreur Cette photo ou une erreur
	 */
	function Charger($pDir = '', $pEntry = '', $pTitre = '', $pSansDouble = false) {
		$ret = $this;
		$dir = SG_Texte::getTexte($pDir);
		$entry = SG_Texte::getTexte($pEntry);
		$titre = SG_Texte::getTexte($pTitre);
		$sansdouble = SG_VraiFaux::getBooleen($pSansDouble);
		$deja = ''; // pour récupérer une photo en double
		if ($titre === '') {
			$titre = $entry;
		}
		if ($dir === '' or $entry === '') {
			$ret = new SG_Erreur('0152');
		} else {
			$handle = opendir($dir);
			if (!$handle) {
				$ret = new SG_Erreur('0153');
			} else {
				$path = $dir . '/' . $entry;
				if (is_dir($path)) {
					$ret = new SG_Erreur('0154', $path);
				} else {
					$test = @getimagesize($path);
					if ($test === false) {
						$ret = new SG_Erreur('0155', $path);
					} else {
						$this -> setFichier('_attachments', $path, $entry, "image/jpeg");
						$md5 = $this -> calculeMD5();
						$ok = true;
						// si sans double, test via sha si déjà dans la base pour ne pas enregistrer
						if ($sansdouble and self::testMD5($md5, $deja)) {
							$ret = new SG_Erreur('0253', $titre, $deja);
						} else {
							$this -> setMD5();
							$this -> setValeur('@Titre', $titre);
							$this -> setValeur('@Code', $titre);
							$exif = json_encode(@exif_read_data($path));
							if ($exif) {
								$this -> setValeur('@Exif', $exif);
							}
							$image = current($this -> getValeur('_attachments',''))['data'];
							$this -> setValeur('@Vignette', SG_Image::resizeto(80,$image));
							$ok = $this -> Enregistrer();
							if (getTypeSG($ok) === '@Erreur') {
								$ret = $ok;
							}
						}
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Récupère une donnée Exif si elle existe
	 * 
	 * @since 2.1 ajout
	 * @param string|SG_Texte|SG_Formule $pParm nom de la donnée à récupérer
	 * @return SG_Texte
	 */
	function Exif($pParm = '') {
		$ret = new SG_Texte('');
		$parm = SG_Texte::getTexte($pParm);
		if ($parm !== '') {
			$exif = $this -> getValeur('@Exif', '');
			if (is_array($exif)) {
				$parm = explode('.', $parm);
				$ret = $exif;
				foreach ($parm as $p) {
					if (isset($ret[$p])) {
						$ret = $ret[$p];
					} else {
						$ret = implode($p);
						break;
					}
				}
				$ret = new SG_Texte($ret);
			}
		}
		return $ret;
	}

	/**
	 * Ajoute un fichier photo à la propriété @Photo (via _attachments) ou restitue un @Fichier contenant la photo
	 * Par défaut le @Titre et le @Code sont égaux au nom du fichier
	 * 
	 * @since 2.2 ajout
	 * @version 2.6 instanceof
	 * @param string|array|SG_Texte|SG_Formule|SG_Fichier $pObjet : fichier contenant la photo
	 * @return SG_Photo|SG_Fichier si affectation ou @Fichier contenant la photo si sans paramètre
	 */
	function Photo($pObjet = null) {
		$ret = $this;
		if ($pObjet === null) {
			$ret = new Fichier();
			$ret -> reference = key(current($this -> doc -> proprietes['_attachments']));
			$ret -> contenu = current($this -> doc -> proprietes['_attachments']);
		} else {
			if ($pObjet instanceof SG_Formule) {
				$objet = $pObjet -> calculer();
			} else {
				$objet = $pObjet;
			}
			if ($objet instanceof SG_Fichier) {
				$this -> doc -> proprietes['_attachments'] [$objet -> reference] = $objet -> contenu;
				$this -> doc -> proprietes['@Titre'] = $objet -> reference;
				$this -> doc -> proprietes['@Code'] = $objet -> reference;
			} elseif (is_array($objet)) {
				foreach ($objet as $key => $element) {
					$this -> doc -> proprietes['_attachments'] [$key] = $element;
					$this -> doc -> proprietes['@Titre'] = $key;
					$this -> doc -> proprietes['@Code'] = $key;
					break;
				}
			} elseif ($objet instanceof SG_Fichiers) {
				foreach ($objet -> elements as $key => $fic) {
					$this -> doc -> proprietes['_attachments'] [$key] = $element;
					$this -> doc -> proprietes['@Titre'] = $key;
					$this -> doc -> proprietes['@Code'] = $key;
					break;
				}
			} else {
				$ret = new SG_Erreur('0183');
			}
		}
		// calcul de la vignette
		if (! $ret instanceof SG_Erreur) {
			$img = current($this -> doc -> proprietes['_attachments']);
			$this -> doc -> proprietes['@Vignette'] = SG_Image::resizeTo(80, $img['data']);
		}
		return $ret;
	}

	/**
	 * Crée et retourne une @Image vignette si le fichier existe
	 * 
	 * @since 2.2 ajout
	 * @param integer|SG_Nombre|SG_Formule $pMax : taille maximale de la vignette si elle n'existe pas (défaut 100)
	 * @param any $p1 inutilisé : compatibilité avec SG_Document.Vignette()
	 * @param any $p2 inutilisé : compatibilité avec SG_Document.Vignette()
	 * @param any $p3 inutilisé : compatibilité avec SG_Document.Vignette()
	 * @return SG_Image|SG_Erreur
	 */
	function Vignette($pMax = self::VIGNETTE_MAX, $p1=null, $p2=null, $p3=null) {
		$fic = current($this -> getValeur('_attachments',''));
		$key = key($this -> getValeur('_attachments',''));
		$ret = new SG_Image();
		$ret -> proprietes = array();
		$ret -> proprietes['@Titre'] = $this -> getValeur('@Titre','');
		$ret -> proprietes['@Fichier'][$key] = $fic;
		if (isset($this -> doc -> proprietes['@Vignette'])) {
			$img = $this -> doc -> proprietes['@Vignette'];
			$ret -> proprietes['@Fichier'][$key]['vignette'] = $img;
			$ret -> proprietes['@Fichier'][$key]['data'] = $img;
		} elseif (isset($fic['vignette'])) {
			$ret -> proprietes['@Fichier'][$key]['data'] = $fic['vignette'];
		} else {
			if (isset($fic['data'])) {
				$max = SG_Nombre::getNombre($pMax);
				$img = SG_Image::resizeTo($max, $fic['data']);
				$ret -> proprietes['@Fichier'][$key]['vignette'] = $img;
				$ret -> proprietes['@Fichier'][$key]['data'] = $img;
			} else {
				$ret = new SG_Erreur('0181');
			}
		}
		return $ret;
	}

	/**
	 * Calcul le code html pour l'affichage de la photo
	 * 
	 * @since 2.2 ajout
	 * @param any $pDefaut inutilisé (compatibilité avec SG_Document::toHTML())
	 * @return SG_HTML
	 */
	function toHTML($pDefaut = NULL) {
		if (isset($this -> doc -> proprietes['@Vignette'])) {
			$img = 'data:image/jpeg;base64,' . $this -> getValeur('@Vignette','');
			$ret = '<img class="sg-rep-photo" src="' . $img .'">';
		} else {
			$ret = '<img class="sg-rep-photo" src="' . $this -> getSrc(true) .'">';
		}
		$ret.= '<span> '. $this -> getValeur('@Titre','') . '</span>';
		return new SG_HTML($ret);
	}

	/**
	 * Récupère les données pour un champ src="..."
	 * 
	 * @since 2.2 ajout
	 * @param boolean $pEncode
	 * @return string de type html
	 */
	function getSrc($pEncode = true) {
		$ret = '';
		$fichier = $this -> getValeur('_attachments','');
		if ($fichier !== '') {
			if (is_string($fichier)) {
				$ret = $fichier;
			} elseif (getTypeSG($fichier) === '@Texte') {
				$ret = $fichier -> texte;
			} else {
				$key = key($fichier);
				$fichier  = current($fichier);
				if(isset($fichier['type'])) {
					$type = $fichier['type'];
				} else {
					$type = 'image/jpeg';
				}
				if (!isset($fichier['data']) and isset($fichier['stub']) and $fichier['stub'] == '1') {
					$url = $this -> doc -> urlCouchDB() . '/' . urlencode($key);
					$data = $_SESSION['@SynerGaia'] -> sgbd -> requete($url);
					if ($pEncode === true) {
						$data = base64_encode($data);
					}
				} elseif (isset($fichier['data'])) {
					$data = $fichier['data'];
					if ($pEncode === true) {
						$data = base64_encode($data);
					}
				}
				$ret = 'data:'. $type . ';base64,' . $data ;
			}
		}
		return $ret;
	}

	/**
	 * Calculate fields @Code @Titre (if empty), @Exif, @Vignette, @DatePriseDeVue
	 * 
	 * @since 2.2
	 * @version 2.7 .@Code contains the filename of the photo
	 * @return boolean|SG_Erreur
	 */
	function preEnregistrer() {
		$fic = current($this -> getValeur('_attachments',''));
		$key = key($this -> getValeur('_attachments',''));
		// recalcul d'une vignette si elle n'existe pas et du md5 (systématique pour le cas où la photo a changé)
		if (isset($fic['data'])) {
			$this -> setValeur('@Vignette', SG_Image::resizeTo(self::VIGNETTE_MAX, $fic['data']));
			$this -> setMD5();
		}
		$this -> setValeur('@Code',$key);
		// calcul d'un titre si non fourni, à partir du nom du fichier photo
		if ($this -> getValeur('@Titre','') === '') {
			$this -> setValeur('@Titre',$key);
		}
		// calcul d'un champ @Exif s'il a été inclu dans l'attachement (voir SG_Navigation::traitementParametres_HTTP_FILES
		if (isset($fic['exif'])) {
			$exif = json_decode($fic['exif'], true);
			if(is_array($exif)) {
				$this -> setValeur('@Exif', $fic['exif']);
				// extraction de la date de prise de vue
				$date = null;
				if (isset($exif['DateTimeOriginal'])) { 
					$date = DateTime::createFromFormat('Y:m:d h:i:s',$exif['DateTimeOriginal']);
					if ($date) {
						$date = $date -> getTimestamp();
					}
				} elseif (isset($exif['DateTime'])) { 
					$date = DateTime::createFromFormat('Y:m:d h:i:s',$exif['DateTime']);
					if($date) {
						$date = $date -> getTimestamp();
					}
				} elseif (isset($exif['FileDateTime'])) { 
					$date = $exif['FileDateTime'];
				}
				if(! is_null($date)) {
					$this -> setValeur('@DatePriseDeVue',$date);
				}
			} else {
				$ret = new SG_Erreur('Données exif non valides');
			}
		}
		return true;
	}
		
	/**
	 * Affiche la photo parmi un diaporama et ajoute des boutons "suivant" et "précédent"
	 * 
	 * @since 2.3 ajout
	 * @param SG_Collection $pCollection : inutilisé collection d'où provient la photo
	 * @param any $pIndex : inutilisé index de la photo actuelle
	 * @return SG_HTML|SG_Erreur
	 * @uses SynerGaia.fullScreen(), SynerGaia.photoPrec(), SynerGaia.photoSuiv()
	 * @todo libellés en fichier
	 */
	function AfficherDiaporama($pCollection = null, $pIndex = null) {
		$ret = '';
		$fichier = $this -> doc -> getFichier();
		if(getTypeSG($fichier) === '@Erreur') {
			$ret = $fichier;
		} else {
			$id = sha1($fichier['nom']);
			$ret.= '<div id="' . $id . '"  class="sg-photo-div" onclick="SynerGaia.fullScreen(event,\'' . $id . '\')" data-sg="0">';
			$ret.= '<img class="sg-photo-prec" src="" alt="Photo précédénte" onclick="SynerGaia.photoPrec(event,\'' . $id . '\')" >';
			$ret.= '<img class="sg-photo-img" src="data:'. $fichier['type'] . ';base64,' .  base64_encode($fichier['data']) . '" width="95%" height="95%">';
			$ret.= '<img class="sg-photo-suiv" src="" alt="Photo suivante" onclick="SynerGaia.photoSuiv(event,\'' . $id . '\')" >';
			$ret.= '</div>';
		}
		return new SG_HTML($ret);
	}

	/**
	 * Récupérer le code html de la photo (balise img)
	 * 
	 * @since 2.4 ajout
	 * @return SG_HTML
	 */
	function getPhotoToHTML() {
		$fichier = $this -> doc -> getFichier();
		$ret = '<img class="sg-photo-img" src="data:'. $fichier['type'] . ';base64,' .  base64_encode($fichier['data']) . '" width="95%" height="95%">';
		return new SG_HTML($ret);
	}

	/**
	 * Calcule le md5 de l'image
	 * 
	 * @since 2.4 ajout
	 * @return string
	 */
	function setMD5() {
		$this -> setValeur('@MD5',$this -> calculeMD5());
		return $this;
	}

	/**
	 * Teste s'il existe déjà une image dans la base avec le même MD5 
	 * (la photo en cours est supposée ne pas y être déjà)
	 * 
	 * @since 2.4 ajout
	 * @param string $pMD5
	 * @param SG_Document $doc en retour : le document atteint par la recherche
	 * @return boolean false si n'existe pas ou erreur, true si existe
	 */
	function testMD5($pMD5, &$doc = null) {
		$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetsParMD5(getTypeSG($this), $pMD5);
		$ret = !($doc instanceof SG_Erreur  or ($doc instanceof SG_Collection and sizeof($doc -> elements) === 0));
		return $ret;
	}

	/**
	 * Calcule le md5 du fichier data (on va le lire si on ne l'a pas déjà) 
	 * 
	 * @since 2.4 ajout
	 * @return boolean false si n'existe pas ou erreur, true si existe
	 */
	function calculeMD5() {
		$ret = '';
		if (isset($this -> doc -> proprietes['_attachments'])) {
			$ret = md5($this -> getData());
		}
		return $ret;
	}

	/**
	 * Test s'il existe déjà une image dans la base avec le même md5
	 * Attention cette fonction répond différemment de la fonction Document.Existe qui teste seulement
	 * 
	 * @since 2.4 ajout
	 * @param any $pChamp inutilisé
	 * @param any $pValeur inutilisé
	 * @return SG_VraiFaux
	 */
	function Existe($pChamp = NULL, $pValeur = NULL) {
		$deja = false;
		$md5 = $this -> calculeMD5();
		if ($md5 !== '') {
			$doc = '';
			$deja = $this -> testMD5($md5, $doc);
		}
		return new SG_VraiFaux($deja);
	}

	/**
	 * Test combien il existe déjà d'image dans la base avec le même md5
	 * @since 2.4 ajout
	 * @return SG_Nombre
	 */
	function Nombre() {
		$ret = 0;
		$md5 = $this -> getMD5();
		if ($md5 === '') {
			$md5 = $this -> calculeMD5();
		}
		if ($md5 !== '') {
			$doc = '';
			$deja = $this -> testMD5($md5, $doc);
			if(getTypeSG($doc) === '@Collection') {
				$ret = sizeof($doc -> elements);
			}
		}
		return new SG_Nombre($ret);
	}

	/**
	 * récupère la chaine data du premier fichier photo (pas en base64)
	 * @since 2.4 ajout
	 * @return string
	 */
	function getData() {
		$data = '';
		if (isset($this -> doc -> proprietes['_attachments'])) {
			$fic = current($this -> getValeur('_attachments',''));
			if (!isset($fic['data']) and isset($fic['stub']) and $fic['stub'] == '1') {
				$fichier = $this -> doc -> getFichier();
				$data = $fichier['data'];
			} elseif (isset($fic['data'])) {
				$data = $fic['data'];
			}
		}
		return $data;
	}

	/**
	 * récupère la valeur du champ @MD5 ou '' si pas calculée
	 * @since 2.4 ajout
	 * @return string
	 */
	function getMD5() {
		return $this -> getValeur('@MD5', '');
	}

	/**
	 * Recherche les doubles d'une photos dans la base, c'est à dire celles qui ont le même md5 mais ne sont pas celle-ci
	 * 
	 * @since 2.7
	 * @return SG_Collection
	 */
	function Doubles() {
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> getObjetsParMD5(getTypeSG($this), $this -> getMD5());
		if ($ret instanceof SG_Collection) {
			$uid = $this -> getUUID();
			for ($i = 0; $i < sizeof($ret -> elements); $i++) {
				if ($ret -> elements[$i] -> getUUID() === $uid) {
					unset($ret -> elements[$i]);
				}
			}
		}				
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Photo_trait;
}
?>
