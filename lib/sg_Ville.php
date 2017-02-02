<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de gestion d'une ville
*/// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Ville_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Ville_trait.php';
} else {
	trait SG_Ville_trait{};
}
class SG_Ville extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Ville';
	public $typeSG = self::TYPESG;
	
	const CODEBASE = 'synergaia_villes_fr';

	// Code nom de la ville (pays / ville ou ville)
	public $code = '';

	// Document physique
	public $doc;

	/** 1.1 latitude, longitude
	* Construction de l'objet
	*
	* @param string $pCode nom de la ville
	* @param indefini $pTableau tableau éventuel des propriétés du document CouchDB ou SG_DocumentCouchDB
	* @param string $pPays code du pays 
	*/
	public function __construct($pCode = '', $pTableau= null, $pPays = '') {
		$base = SG_Dictionnaire::getCodeBase($this -> typeSG);
		$tmpCode = new SG_Texte($pCode);
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code','');
	}
	/** 1.1 ajout
	*/
	function afficherChamp() {
		$ret = '';
		if ($this -> code !== '') {
			$titre = $this -> getValeur('@Titre','');
			$ret = $this -> LienGeographique($titre,$titre,$this -> getValeur('@Latitude','') ,$this -> getValeur('@Longitude',''));
		}
		return $ret;
	}
	/** 1.1 ajout ; 2.0 parm ; 2.1 php7
	* Modification
	* @param $pRefChamp référence du champ HTML
	* @param $pListeElements (@Collection) : liste des valeurs possibles (par défaut toutes)
	*/
	function modifierChamp($codeChampHTML = '', $pListeElements = NULL) {
		// trouver la valeur actuelle
		$ville = '';
		if ($this -> code !== '') {
			$ville = $_SESSION['@SynerGaia'] -> sgbd -> getVillesAjax($this -> code,$this -> getUUID());
		}
		// créer l'html du champ
		$ret = '<select id="champ_Ville" type="text" name="' . $codeChampHTML . '">' . $ville . '</select>';
		$ret .= '&nbsp&nbsp<img src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/zoom.png"><input id="champ_VilleRecherche" type="text" size="30" value=""></input>';
		// ajouter le script de recherche ajax
		$ret .= '<script>' . PHP_EOL;
		$ret .= '$("#champ_VilleRecherche").keyup(function() {var cle=$(this).val();SynerGaia.villes(cle,"champ_Ville")});' . PHP_EOL;
		$ret .= '</script>' . PHP_EOL;
		return $ret;
	}
	/**1.2 lien géographique
	*/
	function LienGeographique($pRequete = '', $pTitre = '', $pLatitude = '', $pLongitude = '') {
		$requete = SG_Texte::getTexte($pRequete);
		$titre = SG_Texte::getTexte($pTitre);
		$lat = str_replace(',', '.', SG_Texte::getTexte($pLatitude));
		$long = str_replace(',', '.', SG_Texte::getTexte($pLongitude));
		$typeLien = 'googlemaps';
		//$typeLien = 'mapquest';
		$href = '';
		switch ($typeLien) {
			case 'googlemaps' :
				if ($lat !== '' and $long !== '') {
					$href = 'https://maps.google.fr/maps?channel=fs&q=' . $lat . ',' . $long . '&oe=utf-8&ie=UTF-8';
				} else {
					$href = 'https://maps.google.fr/maps?channel=fs&q=' . $requete . '&oe=utf-8&ie=UTF-8';
				}
				break;
			case 'mapquest' :
				if ($lat !== '' and $long !== '') {
					$href = '//www.mapquest.com/?q=' . $lat . ',' . $long . '&zoom=13';
				} else {
					$href = '//www.mapquest.com/?q=' . $requete;
				}
				break;
			case 'wmflabs' :
				if ($lat !== '' and $long !== '') {
					$href = '//tools.wmflabs.org/geohack/geohack.php?pagename=' . $titre . '&language=fr&params=' . $lat . '_N_' . $long . '_E_type:city_region:fr_globe:earth&title=';
				} else {
					$href = '//tools.wmflabs.org/geohack/geohack.php?pagename=' . $titre . '&language=fr&params=' . $lat . '_N_' . $long . '_E_type:city_region:fr_globe:earth&title=';
				}
				break;
			default :
		}
		
		$ret = '';
		if ($href !== '') {
			$ret = '<span style="white-space: nowrap;">
			<a class="lienGeographique" href="' . $href . '" style="white-space: normal;">
			<img class="wmamapbutton noprint" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/world.png" title="Montrer la localisation sur une carte interactive" 
			alt="" style="padding: 0px 3px 0px 0px; cursor: pointer;"></img>' . $titre . '</a></span>';
		}
		return $ret;
	}
	/** 2.1 ajout
	* 
	* @param
	* @return
	**/
	function toHTML($pDefaut = null) {
		return $this -> afficherChamp();
	}
	/** 2.1 : paramètre
	* Conversion en chaine de caractères
	*
	* @return string texte
	*/
	function toString($pDefaut = null) {
		$ret = $this -> getValeur('Titre', null);
		if($ret === null and method_exists($this, 'Titre')) {
			$ret = $this -> Titre();
		} else {
			$ret = '';
		}
		$ret = SG_Texte::getTexte($ret);
		if ($ret === '') {
			$ret = $this -> getValeur('@Titre', '');
		}
		return $ret;
	}
	
	// 2.1.1. complément de classe créée par compilation
	use SG_Ville_trait;
}
?>
