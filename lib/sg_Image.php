<?php
/** SYNERGAIA fichier pour le traitemet de l'objet @Image */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Image_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Image_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Image_trait{};
}

/**
 * SG_Image : classe SynerGaia de gestion d'une image
 * @version 2.2
 */
class SG_Image extends SG_ObjetComposite {
	/** string Type SynerGaia '@Image' */
	const TYPESG = '@Image';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/**
	 * Construction de l'objet
	 * 
	 * @since 1.1 ajout
	 * @param any $pQuelqueChose
	 * @param string|SG_Texte|SG_Formule $pUUId = ''
	 */
	function __construct($pQuelqueChose = null, $pUUId = '') {
		$this -> initObjetComposite($pQuelqueChose, $pUUId);
		$this -> champs = array('@Titre' => null, '@Fichier' => null);
	}

	/**
	 * Calcul le code html pour l'affichage de l'objet
	 * 
	 * @since 1.1 ajout
	 * @version 2.1 suppression de la boucle, getSrc
	 * @version 2.2 sup height et width ; title
	 * @return string code html
	 */
	function toHTML() {
		$ret = '<img class="sg-rep-photo" src="' . $this -> getSrc(false) .'" title="' . $this -> getValeur('@Titre').'">'; // width="100%" height="100%"
		return $ret;
	}

	/**
	 * Retaille l'image selon une nouvelle dimension
	 * 
	 * @since 1.1 ajout
	 * @version 1.3.1 static
	 * @version 2.2 erreur 0182
	 * @param integer $pMax nouvelle taille
	 * @param string $pData texte de l'image
	 * @TODO mettre au propre l'erreur 0182 (provisoire)
	 * @return string|SG_Erreur
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

	/**
	 * Crée une image redimensionnée selon la taille passée en paramètre
	 * @since 1.1 ajout
	 * @param integer $pMax nouvelle taille
	 * @return SG_Image
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

	/**
	 * Crée une nouvelle image sous forme de vignette (taille 100px)
	 * 
	 * @since 1.1 ajout
	 * @return SG_IMage
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

	/**
	 * Calcule le texte html à afficher avec une possibilité de clic
	 * 
	 * @since 1.1 ajout
	 * @param string|SG_Texte|SG_Formule $pMessage message à afficher sur le bouton
	 * @param SG_Formule $pFormule formule à exécuter au clic
	 * @return SG_HTML
	 */
	function AfficherClic($pMessage = '', $pFormule = '') {
		$ret = '';
		$fichier = $this -> getValeur('@Fichier');
		$popup = '#popup';
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

	/**
	 * Calcule le code html pour l'affichage de l'objet
	 * 
	 * @since 1.1
	 * @version 2.6 return SG_HTML
	 * @param integer $pTaille taille de l'image affichée
	 * @return SG_HTML
	 */
	function Afficher($pTaille = 0) {
		if ($pTaille === 0) {
			$image = $this;
		} else {
			$image = $this -> Redimensionner($pTaille);
		}
		$ret = '<div>' . $image -> toHTML() . PHP_EOL. '<p class="sg-rep-legende">' . $image -> getValeur('@Titre') . '<p></div>';
		return new SG_HTML($ret);
	}

	/**
	 * Récupère les données pour un champ src="..."
	 * 
	 * @since 2.1
	 * @param integer $pEncode
	 * @return string
	 */
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
