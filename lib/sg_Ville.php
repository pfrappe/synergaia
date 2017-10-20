<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Ville */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Ville_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Ville_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Ville_trait{};
}

/** SynerGaia 2.1.1 (see AUTHORS file)
 * Classe SynerGaia de gestion d'une ville
 * @since 1.0
 * @version 2.1.1
 * @version 2.6 lien géographique
 */
class SG_Ville extends SG_Document {
	/** string Type SynerGaia '@Ville' */
	const TYPESG = '@Ville';

	/** string code de la base */
	const CODEBASE = 'synergaia_villes_fr';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Code nom de la ville (pays / ville ou ville) */
	public $code = '';

	/**
	 * Construction de l'objet
	 * 
	 * @since 1.0
	 * @version 1.1 latitude, longitude
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

	/**
	 * Calcul le code html poure l'affichage dans un champ
	 * 
	 * @since 1.1 ajout
	 * @return string code html
	 */
	function afficherChamp() {
		$ret = '';
		if ($this -> code !== '') {
			$titre = $this -> getValeur('@Titre','');
			$ret = $this -> LienGeographique($titre,$titre,$this -> getValeur('@Latitude','') ,$this -> getValeur('@Longitude',''));
		}
		return $ret;
	}

	/**
	 * Calcule le code html pour la modification dans un champ
	 * 
	 * @since 1.1 ajout
	 * @since 2.0 parm
	 * @since 2.1 adaptation à php7
	 * @param string $codeChampHTML référence du champ HTML
	 * @param any $pListeElements : inutilisé
	 * @return string code html
	 * @uses SynerGaia.villes()
	 */
	function modifierChamp($codeChampHTML = '', $pListeElements = NULL) {
		// trouver la valeur actuelle
		$ville = '';
		if ($this -> code !== '') {
			$ville = $_SESSION['@SynerGaia'] -> sgbd -> getVillesAjax($this -> code,$this -> getUUID());
		}
		// créer l'html du champ
		$ret = '';
		$ret.= '<input id="champ_VilleRecherche" class="sg-ville-srch" type="text" size="30" value="" title="Recherche..."></input>';
		$ret.= '<select id="champ_Ville" class="sg-ville-choix" type="text" name="' . $codeChampHTML . '">' . $ville . '</select>';
		// ajouter le script de recherche ajax
		$ret.= '<script>' . PHP_EOL;
		$ret.= '$("#champ_VilleRecherche").keyup(function() {var cle=$(this).val();SynerGaia.villes(cle,"champ_Ville")});' . PHP_EOL;
		$ret.= '</script>' . PHP_EOL;
		return $ret;
	}

	/**
	 * Calcule le code html pour créer un lien vers une base géographique externe
	 * 
	 * @since 1.2
	 * @version 2.6 retour DSG_HTML
	 * @param string|SG_Texte|SG_Formule $pRequete
	 * @param string|SG_Texte|SG_Formule $pTitre
	 * @param string|SG_Texte|SG_Formule $pLatitude
	 * @param string|SG_Texte|SG_Formule $pLongitude
	 * @return SG_HTML
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
			<a class="sg-lien-map" href="' . $href . '" style="white-space: normal;">
			<img class="sg-lien-map-btn noprint" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/world.png" title="Montrer la localisation sur une carte interactive" 
			alt="" style="padding: 0px 3px 0px 0px; cursor: pointer;"></img>' . $titre . '</a></span>';
		}
		return new SG_HTML($ret);
	}

	/**
	 * calcule le code html de l'affichage dans un champ
	 * @since 2.1
	 * @param any $pDefaut inutilisé
	 * @return string code HTML
	 */
	function toHTML($pDefaut = null) {
		return $this -> afficherChamp();
	}

	/**
	 * Conversion de l'objet en chaine de caractères
	 * @version 2.1 : paramètre pour compatibilité avec SG_Objet
	 * @param any $pDefaut inutilisé
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

	/**
	 * Permet la recherche de l'égalité sur une chaine de caractères
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule|SG_Ville $pVille nom ou document de la ville
	 * @return SG_VraiFaux|SG_Erreur
	 **/
	function Egale($pVille) {
		$ret = new SG_VraiFaux();
		if ($pVille instanceof SG_Ville) {
			$ville = $pVille;
		} else {
			$ville = $_SESSION['@SynerGaia'] -> sgbd -> getDocumentsFromTypeChamp('@Ville', '@Code', SG_Texte::getTexte($pVille));
			if ($ville instanceof SG_Collection) {
				$ville = $ville -> Premier();
			}
		}
		$ret = new SG_VraiFaux($this -> getUUID() === $ville -> getUUID());
		return $ret;
	}
	
	// 2.1.1. complément de classe créée par compilation
	use SG_Ville_trait;
}
?>
