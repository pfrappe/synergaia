<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Photo : classe SynerGaia de gestion d'une photo
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Photo_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Photo_trait.php';
} else {
	trait SG_Photo_trait{};
}
class SG_Photo extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Photo';

	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Code de la base
	const CODEBASE = 'synergaia_photos';
	
	// Taille par défaut de la vignette
	const VIGNETTE_MAX = 100;
	
	/** 2.1 ajout
	* 
	**/
	function AfficherPhoto() {
		$ret = '';
		$fichier = $this -> doc -> getFichier();
		if(getTypeSG($fichier) === '@Erreur') {
			$ret.= $fichier -> toString();
		} else {
			$id = sha1($fichier['nom']);
			$ret.= '<div id="' . $id . '"  class="photo-div" onclick="SynerGaia.fullScreen(event,\'' . $id . '\')" data-sg="0">';
			$ret.= '<img class="photo-img" src="data:'. $fichier['type'] . ';base64,' .  base64_encode($fichier['data']) . '" width="95%" height="95%">';
			$ret.= '</div>';
		}
		return new SG_HTML($ret);
	}
	/** 2.1 ajout
	* Charge le contenu d'un répertoire
	* @param (string ou formule) $pDir : répertoire à charger sur le serveur
	* @param (string ou formule) $pEntry : nom du fichier à lire)
	**/
	function Charger($pDir = '', $pEntry = '', $pTitre = '') {
		$ret = $this;
		$dir = SG_Texte::getTexte($pDir);
		$entry = SG_Texte::getTexte($pEntry);
		$titre = SG_Texte::getTexte($pTitre);
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
					$test = getimagesize($path);
					if ($test === false) {
						$ret = new SG_Erreur('0155', $path);
					} else {
						$this -> setFichier('_attachments', $path, $entry, "image/jpeg");
						$this -> setValeur('@Titre', $titre);
						$this -> setValeur('@Code', $titre);
						$exif = json_encode(@exif_read_data($path));
						if ($exif) {
							$this -> setValeur('@Exif', $exif);
						}
						$image = $this -> doc -> proprietes['_attachments'][$entry]['data'];
						$this -> setValeur('@Vignette', SG_Image::resizeto(80,$image));
						$ok = $this -> Enregistrer();
						if (getTypeSG($ok) === '@Erreur') {
							$ret = $ok;
						}
					}
				}
			}
		}
		return $ret;
	}
	/** 2.1 ajout
	* Récupère une donnée Exif si elle existe
	**/
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
	/** 2.2 ajout
	* Ajoute un fichier photo à la propriété @Photo (via _attachments) ou restitue un @Fichier contenant la photo
	* @param (@Fichier) $pObjet : fichier contenant la photo
	* @return @this si affectation ou @Fichier contenant la photo si sans paramètre
	**/
	function Photo($pObjet = null) {
		$ret = $this;
		if ($pObjet === null) {
			$ret = new Fichier();
			$ret -> reference = key(current($this -> doc -> proprietes['_attachments']));
			$ret -> contenu = current($this -> doc -> proprietes['_attachments']);
		} else {
			if (getTypeSG($pObjet) === '@Formule') {
				$objet = $pObjet -> calculer();
			} else {
				$objet = $pObjet;
			}
			if (getTypeSG($objet) === '@Fichier') {
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
			} elseif (getTypeSG($objet) === '@Fichiers') {
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
		if (getTypeSG($ret) !== '@Erreur') {
			$img = current($this -> doc -> proprietes['_attachments']);
			$this -> doc -> proprietes['@Vignette'] = SG_Image::resizeTo(80, $img['data']);
		}
		return $ret;
	}
	/** 2.2 ajout
	* Crée et retourne une @Image vignette si le fichier existe
	* @param $pMax (@Nombre) : taille maximale de la vignette si elle n'existe pas (défaut 100)
	* @param 2, 3 et 4 inutilisés : compatibilité avec SG_Document.Vignette()
	* @return @Image
	**/
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
	/** 2.2 ajout
	* @param inutilisé (compatibilité avec SG_Document::toHTML())
	*/
	function toHTML($pDefaut = NULL) {
		if (isset($this -> doc -> proprietes['@Vignette'])) {
			$img = 'data:image/jpeg;base64,' . $this -> getValeur('@Vignette','');
			$ret = '<img class="repertoire-photo" src="' . $img .'">';
		} else {
			$ret = '<img class="repertoire-photo" src="' . $this -> getSrc(true) .'">';
		}
		$ret.= '<span> '. $this -> getValeur('@Titre','') . '</span>';
		return $ret;
	}
	/** 2.2 ajout
	* récupère les données pour un champ src="..."
	* @return string de type html
	**/
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
	/** 2.2 ajout
	* Calculer les champs @Titre (si vide), @Vignette, @DatePriseDeVue
	**/
	function preEnregistrer() {
		$fic = current($this -> getValeur('_attachments',''));
		$key = key($this -> getValeur('_attachments',''));
		// recalcul d'une vignette si elle n'existe pas (systématique au cas où la photo a changé
		if (isset($fic['data'])) {
			$this -> setValeur('@Vignette', SG_Image::resizeTo(self::VIGNETTE_MAX, $fic['data']));
		}
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
					$date = $date -> getTimestamp();
				} elseif (isset($exif['DateTime'])) { 
					$date = DateTime::createFromFormat('Y:m:d h:i:s',$exif['DateTime']);
					$date = $date -> getTimestamp();
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
		
	/** 2.3 ajout
	* Affiche la photo parmi un diaporama et ajoute des boutons "suivant" et "précédent"
	* @param $pCollection : collection d'où provient la photo
	* @param $pIndex : index de la photo actuelle
	**/
	function AfficherDiaporama($pCollection = null, $pIndex = null) {
		$ret = '';
		$fichier = $this -> doc -> getFichier();
		if(getTypeSG($fichier) === '@Erreur') {
			$ret.= $fichier -> toString();
		} else {
			$id = sha1($fichier['nom']);
			$ret.= '<div id="' . $id . '"  class="photo-div" onclick="SynerGaia.fullScreen(event,\'' . $id . '\')" data-sg="0">';
			$ret.= '<img class="photo-prec" src="" alt="Photo précédénte" onclick="SynerGaia.photoPrec(event,\'' . $id . '\')" >';
			$ret.= '<img class="photo-img" src="data:'. $fichier['type'] . ';base64,' .  base64_encode($fichier['data']) . '" width="95%" height="95%">';
			$ret.= '<img class="photo-suiv" src="" alt="Photo suivante" onclick="SynerGaia.photoSuiv(event,\'' . $id . '\')" >';
			$ret.= '</div>';
		}
		return new SG_HTML($ret);
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Photo_trait;
}
?>
