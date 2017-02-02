<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.2 (see AUTHORS file)
* SG_Image : classe SynerGaia de gestion d'une image
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Image_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Image_trait.php';
} else {
	trait SG_Image_trait{};
}
class SG_Image extends SG_ObjetComposite {
	// Type SynerGaia
	const TYPESG = '@Image';
	public $typeSG = self::TYPESG;

	/** 1.1 ajout
	* Construction de l'objet
	*/
	function __construct($pQuelqueChose = null, $pUUId = '') {
		$this -> initObjetComposite($pQuelqueChose, $pUUId);
		$this -> champs = array('@Titre' => null, '@Fichier' => null);
	}
	/** 1.1 ajout ; 2.1 suppression de la boucle, getSrc ; 2.2 sup height et width ; title
	*/
	function toHTML() {
		$ret = '<img class="repertoire-photo" src="' . $this -> getSrc(false) .'" title="' . $this -> getValeur('@Titre').'">'; // width="100%" height="100%"
		return $ret;
	}
	/** 1.1 ajout ; 1.3.1 static ; 2.2 0182
	*/
	static function resizeTo($pMax = 0, $pData) {
		if (!function_exists('imagecreatefromstring')) {
			$ret = new SG_Erreur('0047');
		} else {
			$ret = new SG_Erreur('0046');
			$max = new SG_Nombre($pMax);
			$max = $max -> toInteger();	
			if ($max === 0) {
				$max = 100;
			}
			$source = @imagecreatefromstring(base64_decode($pData));
if($source === false) {
	return new SG_Erreur('0182'); // TODO mettre au propre
}
			$imx = imagesx($source);
			$imy = imagesy($source);
			$coef = max($imx, $imy) / $max;
			$newwidth = floor($imx / $coef);
			$newheight = floor($imy / $coef);
			// Redimensionnement
			$thumb = imagecreatetruecolor($newwidth, $newheight);
			$r = imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $imx, $imy);
			if ($r === false) {
				$ret = new SG_Erreur('0046');
			} else {
				$fic = uniqid() . '.jpeg';
				$r = imagejpeg($thumb, $fic);
				$handle = fopen($fic, "r");
				$contents = fread($handle, filesize($fic));
				$ret = base64_encode($contents);
				$r = unlink($fic);
				imagedestroy($thumb);
			}
			imagedestroy($source);
		}
		return $ret;
	}
	/** 1.1 ajout
	*/
	function Redimensionner($pMax = 0) {
		$fichier = $this -> getValeur('@Fichier','');
		foreach ($fichier as $key => $image) {
			$data = self::resizeTo($pMax, $image['data']);
			$ret = new SG_Image();
			$ret -> proprietes = array();
			$ret -> proprietes['@Titre'] = $this -> getValeur('@Titre');
			$im = array('content_type' => 'image/jpeg','data' => $data );
			$ret -> proprietes['@Fichier'] = array();
			$ret -> proprietes['@Fichier'][$key] = $im;
			break;
		}
		return $ret;
	}
	/** 1.1 ajout
	*/
	function Vignette() {
		$fic = current($this -> getValeur('@Fichier',''));
		$key = key($this -> getValeur('@Fichier',''));
		if (isset($fic['vignette'])) {
			$ret = new SG_Image();
			$ret -> proprietes = array();
			$ret -> proprietes['@Titre'] = $this -> getValeur('@Titre');
			$im = array('content_type' => 'image/jpeg','data' => $fic['vignette'], 'vignette' => $fic['vignette']);
			$ret -> proprietes['@Fichier'][$key] = $im;
			$ret -> index = $this -> index;
		} else {
			$ret = $this -> Redimensionner(100);
		}
		return $ret;
	}
	/** 1.1 ajout
	*/
	function AfficherClic($pMessage = '', $pFormule = '') {
		$ret = '';
		$fichier = $this -> getValeur('@Fichier');
		$popup = '#popup_window';
		foreach ($fichier as $key => $image) {
			$formule = $pFormule;
			if ($pFormule === '') {
				$formule = new SG_Formule('.@Afficher', $this);
			}
			$bouton = new SG_Bouton($pMessage, $formule);
			$bouton -> proprietes['objet'] = $this;
			$ret .= '<img id="'.$bouton -> code . '_zoom" src="data:'. $image['content_type'] . ';base64,' .  $image['vignette'] . '"';
			$ret .= ' onclick="sg_getModal(\'c='.$bouton -> code.'&d=' . $this -> index .'\', \''.$popup . '\');" style="cursor: pointer;">';
		}
		$ret = new SG_HTML($ret);
		return $ret;
	}
	/** 1.1 ajout
	*/
	function Afficher($pTaille = 0) {
		if ($pTaille === 0) {
			$image = $this;
		} else {
			$image = $this -> Redimensionner($pTaille);
		}
		$ret = '<div>' . $image -> toHTML() . PHP_EOL. '<p class="repertoire-legende">' . $image -> getValeur('@Titre') . '<p></div>';
		return $ret;
	}
	/** 2.1 ajout
	* récupère les données pour un champ src="..."
	**/
	function getSrc($pEncode = true) {
		$ret = '';
		$fichier = $this -> getValeur('@Fichier','');
		if ($fichier !== '') {
			if (is_string($fichier)) {
				$ret = $fichier;
			} elseif (getTypeSG($fichier) === '@Texte') {
				$ret = $fichier -> texte;
			} else {
				if (!isset($fichier['data'])) {// si plusieurs fichiers prendre le premier
					$fichier  = current($fichier);
				}
				if(isset($fichier['type'])) {
					$type = $fichier['type'];
				} else {
					$type = 'image/jpeg';
				}
				$data = $fichier['data'];
				if ($pEncode === true) {
					$data = base64_encode($data);
				}
				$ret = 'data:'. $type . ';base64,' . $data ;
			}
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Image_trait;
}
?>
