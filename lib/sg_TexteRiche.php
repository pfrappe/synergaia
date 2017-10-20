<?php
/** SynerGaia fichier pour la gestion de l'objet @TYexteRiche */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_TexteRiche_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_TexteRiche_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_TexteRiche_trait{};
}

/**
 * Classe SynerGaia de gestion d'un texte riche
 * @version 2.1.1
 */
class SG_TexteRiche extends SG_Texte {
	/**
	 * string Type SynerGaia
	 */
	const TYPESG = '@TexteRiche';
	/**
	 * string Type SynerGaia de l'objet
	 */
	public $typeSG = self::TYPESG;
	/**
	 * string Contenu texte de l'objet
	 */
	public $texte = '';

	/**
	 * Construction de l'objet
	 *
	 * @param string|SG_Texte|SG_Formule $pQuelqueChose valeur à partir de laquelle le SG_Texte est créé
	 */
	function __construct($pQuelqueChose = null) {
		$tmpTexte = new SG_Texte($pQuelqueChose);
		$this -> texte = $tmpTexte -> toString();
	}

	/**
	 * Conversion en code HTML. 
	 * Pour le texte riche, il faut conserver la classe d'affichage pour les puces et autres.
	 * 
	 * @sonce 1.0.6
	 * @version 2.1 return SG_HTML
	 * @return string SG_HTML
	 */
	function toHTML() {
		$texte = $this -> traduireLesURLInternes();
		$ret = '<richtext class="sg-richtext">' . $texte . '</richtext>';
		return new SG_HTML($ret);
	}

	/**
	 * Affichage sur le navigateur
	 * 
	 * @since 1.0.6
	 * @version 2.6 parm
	 * @param string $pOption option CSS ou classe
	 * @return string code HTML
	 */
	function afficherChamp($pOption = '') {
		return $this -> Afficher($pOption);
	}

	/**
	 * Affichage sur le navigateur
	 * 
	 * @version 2.0 parm
	 * @param string|SG_Texte|SG_Formule $pOption option CSS ou classe
	 * @return string code HTML
	 */
	function Afficher($pOption = '') {
		return $this -> toHTML();
	}

	/**
	 * Modification
	 * 
	 * @since 0.1
	 * @version 2.0 parm
	 * @param string|SG_Texte|SG_Formule $pRefChamp référence du champ HTML
	 * @param string|SG_Texte|SG_Formule $pValeursPossibles inutilisé (compatibilité)
	 * @return string code HTML
	 */
	function modifierChamp($pRefChamp = '', $pValeursPossibles = NULL) {
		$ret = '';
		$id = SG_SynerGaia::idRandom();
		$ret.= '<textarea id="' . $id . '" class="sg-richtext" name="' . $pRefChamp . '">' . htmlspecialchars($this -> texte) . '</textarea>' . PHP_EOL;
		$ret.= '<script>tinymce.init({' . SG_TexteRiche::parametresTinyMCE($id) . '})</script>';
		// pour n'ajouter le script qu'une seule fois
		//$_SESSION['script']['texteriche'] = 'tinymce.init({' . SG_TexteRiche::parametresTinyMCE() . '})' . PHP_EOL;
		//$_SESSION['libs']['tinymce'] = true;
		return $ret;
	}

	/**
	 * enlève les balises pour récupérer le texte brut
	 * 
	 * @since 1.0.5
	 * @return string
	 */
	function Texte() {
		$texte = new SG_Texte(strip_tags($this -> texte));
		return $texte;
	}

	/**
	 * Retourne @Vrai si le texte est contenu dans le @TexteRiche
	 * 
	 * @since 1.0.4
	 * @param string|SG_Texte|SG_Formule  $pQuelqueChose texte à rechecher
	 * @param boolean|SG_VraiFaux $pMot du type booléen : vrai si le mot doit être entier (par défaut false)
	 * 
	 * @return SG_VraiFaux selon que le texte est ou non trouvé
	 */
	function Contient ($pQuelqueChose = '', $pMot = false) {
		$texte = $this -> Texte();
		if (is_object($texte)) {
			$ret = $texte -> Contient($pQuelqueChose, $pMot);
		} else {
			$ret = SG_Rien::Faux();
		}
		return $ret;
	}

	/**
	 * Liste des paramètres pour tinyMce
	 * 
	 * @since 1.1 ajout
	 * @version 1.3.4 'encadré'
	 * @param string $pID id du texte riche pour la sélection de jQuery
	 * @return string
	 */
	static function parametresTinyMCE($pID) {
		$ret = 'selector: "#' . $pID . '", height : 500,';
		$ret.= 'theme: "modern",';
		$ret.= 'language : "fr_FR",';
		$ret.= 'fontsize_formats: "8pt 10pt 11pt 12pt 14pt 18pt 24pt 36pt 40pt 48pt",';
		$ret.= 'convert_fonts_to_spans: true,';
		$ret.= 'plugins: [
        "advlist autolink lists link image charmap print preview hr anchor pagebreak",
        "searchreplace wordcount visualblocks visualchars code fullscreen",
        "insertdatetime media nonbreaking save table contextmenu directionality",
        "emoticons template paste textcolor camera"],';
		$ret.= 'toolbar1: "insertfile undo redo | styleselect formatselect | fontselect fontsizeselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent",';
		$ret.= 'toolbar2: "print preview media | forecolor backcolor emoticons | link image | search replace | camera",';
		$ret.= 'image_advtab: true,';
		$ret.= 'entity_encoding:"named",';
		$ret.= 'paste_data_images: true,';
		$ret.= 'style_formats: [
        {title: "Rouge", inline: "span", styles: {color: "#ff0000"}},
        {title: "Vert", inline: "span", styles: {color: "#00ff00"}},
        {title: "Bleu", inline: "span", styles: {color: "#0000ff"}},
        {title: "Bleu délavé", inline: "span", styles: {color: "#337ab7"}},
        {title: "Encadré", inline: "span", styles: {border: "solid 1px #ccc", "box-shadow": "0px 3px 6px #ede", padding: "3px"}}]';
		return $ret;
	}

	/**
	 * traduit toutes les URL internes (limite 100 pour éviter les boucles)
	 * 
	 * @since 1.2 ajout
	 * @since 2.6 test $traduit erreur
	 * @param string $pTexte
	 * @return string le texte traduit
	 */
	function traduireLesURLInternes($pTexte = null) {
		if ($pTexte === null) {
			$texte = $this -> texte;
		} else {
			if (is_object($pTexte)) {
				$texte = $pTexte -> toString();
			} else {
				$texte = $pTexte;
			}
		}
		$ideb = 0;
		$urlinterne = strpos($texte, '[[',$ideb);
		$n = 0;
		while ($urlinterne !== false) {
			$ideb = $urlinterne;
			$ifin = strpos($texte, ']]',$ideb);
			if ($ifin !== false) {
				$traduit = $this -> traduireUneURLInterne(substr($texte, $ideb, $ifin - $ideb + 2));
				if ($traduit instanceof SG_Erreur) {
					$traduit = $traduit -> toHTML();
				}
				if ($traduit instanceof SG_HTML) {
					$traduit = $traduit -> texte;
				}
				$texte = substr($texte, 0, $ideb) . $traduit . substr($texte, $ifin + 2);
			}
			$urlinterne = strpos($texte, '[[', $ideb);
			$n++;
			if($n > 100) {break;}
		}
		return $texte;
	}

	/**
	 * transforme le texte [[typedoc/code | libellé affiché]] en URL pour un sget 
	 * si pas trouvé sur code, essaie base/id
	 * 
	 * @since 1.2 ajout
	 * @version 2.0 classe
	 * @version 2.6 test erreur
	 * @param string $pURL l'url à traduire
	 */
	function traduireUneURLInterne($pURL = '') {
		$ret = '';
		$urlinterne = self::getURLInterne($pURL);
		if ($urlinterne instanceof SG_Erreur) {
			$ret = $urlinterne;
		} elseif (isset($urlinterne['doc'])) {
			$doc = $urlinterne['doc'];
			$cle = $urlinterne['cle'];
			$libelle = $urlinterne['libelle'];
			$ret = $pURL;
			if (method_exists($doc, 'Existe') and $doc -> Existe() -> estVrai() === true) {
				if ($libelle === '') {
					$libelle = $doc -> toString();
				}
				// on a bien trouvé un document
				$cle = explode('/', $cle);
				if(! isset($cle[2])) {
					$ret = '<a href="' . SG_Navigation::URL_PRINCIPALE . '?m=DocumentConsulter&d=' . $doc -> getUUID() . '">' . $libelle . '</a>';
				} else {
					// un seul champ visé = import du texte
					$formule = new SG_Formule('.' . $cle[2] . '.@Afficher', $doc);
					$ret = SG_Formule::executer('.' . $cle[2] . '.@Afficher', $doc);
				}
			} else {
				$ret = '<span class="sg-url-inconnue" >' . $libelle . '</span>';
			}
		} elseif (isset($urlinterne['fic'])) {
			$ret = $urlinterne['fic'];
		} else {
			$ret = '<span class="sg-url-inconnue" >' . $urlinterne['libelle'] . '</span>';
		}
		return $ret;
	}

	/**
	 * Calcul le code HTML d'un sommaire du texte riche en se basant sur les balises <h>
	 * @since 1.2 ajout
	 * @param string $pTitre titre du sommaire (défaut : 'Sommaire')
	 * @param integer|SG_Nombre|SG_Formule $pProfondeur profondeur du sommaire (défaut 999)
	 */
	function Sommaire($pTitre = 'Sommaire', $pProfondeur = 999) {
		$profondeur = new SG_Nombre($pProfondeur);
		$profondeur = $profondeur -> toInteger();
		if($profondeur <= 0 or $profondeur >= 10) {
			$profondeur = 9;
		}	
		$titre = new SG_Texte($pTitre);
		$titre = $titre -> texte;
		//extraire les lignes de titre
		$modele = '/<h[1-' . $profondeur.']*[^>]*>.*?<\/h[1-' . $profondeur . ']>/';
		$nb = preg_match_all($modele, $this -> texte, $lignes);
		//boucler pour créer l'indentation
		$ret = '';
		$nprec = 0;
		foreach ($lignes[0] as $ligne) {
			$n = intval(substr($ligne, 2, 1));
			while ($n > $nprec) { // descendre
				$ret .= '<ol>';
				$nprec++;
			}			
			while ($n < $nprec) { // remonter
				$ret .= '</ol>';
				$nprec--;
			}
			$ret .= '<li>' . strip_tags($ligne) . '</li>';
		}
		//ajouter l'entourage
		$ret = '<div id="toc"><p id="toc-header">' . $titre . '</p><ol>' . $ret . '</ol></div>';
		return $ret;
	}

	/**
	 * Recherche les liens internet dans le texte
	 * 
	 * @since 1.2 ajout
	 * @return SG_Collection liste des liens trouvés
	 */
	function LiensInternes() {
		$liste = array();
		if ($pTexte === null) {
			$texte = $this -> texte;
		} else {
			$texte = $pTexte;
		}
		$ideb = 0;
		$urlinterne = strpos($texte, '[[',$ideb);
		$n = 0;
		while ($urlinterne !== false) {
			$ideb = $urlinterne;
			$ifin = strpos($texte, ']]',$ideb);
			if ($ifin !== false) {
				$url = self::getURLInterne(substr($texte, $ideb, $ifin - $ideb + 2));
				$cle = $url['cle'];
				if (!isset($liste[$cle])) {
					$doc = $url['doc'];
					if (method_exists($doc, 'Existe') and $doc -> Existe() -> estVrai()) {
						$liste[$cle] = $doc;
					} else {
						$liste[$cle] = new SG_Erreur('0049',$cle);
					}
				}
			}
			$urlinterne = strpos($texte, '[[', $ideb + 2);
			$n++;
			if($n > 100) {break;}
		}
		$ret = new SG_Collection();
		$ret -> elements = $liste;
		return $ret;
	}

	/**
	 * Calcule les données de l'url permettant d'obtenir le renvoi vers le document.
	 * Si pas trouvé sur la clé d'accès on essaie sur le titre
	 * Le lien est sous la forme [[acces|libellé]] ou accès est obj:code ou obj/id ou titre
	 * Pour compatibilité avec les vesrions antérieures à la 2.6, on cherche aussi obj/code
	 * 
	 * @since 1.2 ajout
	 * @version 2.5 test erreur
	 * @version 2.6 possibilité de obj:code ou obj/id
	 * @param string $pURL valeur de l'url style wiki ([[accès|libellé]]
	 * @return string code html de l'url permettant de joindre le document
	 */ 
	function getURLInterne($pURL = '') {		
		$url = $pURL;
		// suppression des crochets
		$url = str_replace('[[','',$url);
		$url = str_replace(']]','',$url);
		$url = explode('|',strip_tags($url));
		// extraction de la partie libellé (à droite de la barre '|' ), sinon tout le texte
		if(sizeof($url) === 1) {
			$libelle = '';
			$url[1] = $url[0];
		} else {
			$libelle = $url[1];
		}
		// recherche du nom du champ de recherche (à gauche du '=') sinon 'Code'
		$nomChamp = 'Code';
		$val = $url[0];
		$champs = explode('=', $val);
		if(sizeof($champs) === 2) {
			$val = $champs[1];
			$nomChamp = $champs[0];
		}
		// cas d'un fichier (file=nomdufichier)
		if ($nomChamp === '@Fichiers') {
			if (sizeof($champs) === 2) {
				$objetfichiers = new SG_Fichier($champs[1]);
				$ret = array('fic' => $objetfichiers -> afficherChamp());
			} else {
				$objetfichiers = new SG_Fichiers($this -> doc);
				$ret = array('fic' => $objetfichiers -> afficherChamp());
			}
		} else {
			$doc = null;
			$cle = array();
			if (strpos($val, '/') !== false) {
				// recherche document par id (en premier)
				$doc =  $_SESSION['@SynerGaia'] -> sgbd -> getObjetByID($val);
			}
			if (is_null($doc) or $doc instanceof SG_Erreur) {
				// recherche sur le code
				if (strpos($val, '/') !== false) {
					// recherche du type de document (à gauche du '/') sinon type du contenant 
					$cle = explode('/',$val);
				} else {
					// recherche du type de document (à gauche du '/') sinon type du contenant 
					$cle = explode(':',$val);
				}
				if(sizeof($cle) === 1) {
					$type = getTypeSG($this -> contenant);
					if($type === '@Champ') {
						$type = getTypeSG($this -> contenant -> document);
					}
					$cle = array($type, $cle[0]);
				}
				$doc = $_SESSION['@SynerGaia'] -> getDocumentsFromTypeChamp($cle[0],$nomChamp,$cle[1]);
				if ($doc instanceof SG_Erreur) {
					$ret = $doc;
				} else {
					$doc = $doc -> Premier();
					if (method_exists($doc, 'Existe') and $doc -> Existe() -> estVrai() === false) {
						$doc = $_SESSION['@SynerGaia'] -> getDocumentsFromTypeChamp($cle[0],'Titre',$cle[1]) -> Premier();
						if (method_exists($doc, 'Existe') and $doc -> Existe() -> estVrai() === false) {
							$doc = $_SESSION['@SynerGaia'] -> getObjet($val);
						}
					}
				}
			}
			$ret = array('cle'=>implode('/',$cle), 'libelle' => $libelle,'doc'=> $doc);
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_TexteRiche_trait;
}
?>
