<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de gestion d'un texte riche
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_TexteRiche_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_TexteRiche_trait.php';
} else {
	trait SG_TexteRiche_trait{};
}
class SG_TexteRiche extends SG_Texte {
	/**
	 * Type SynerGaia
	 */
	const TYPESG = '@TexteRiche';
	/**
	 * Type SynerGaia de l'objet
	 */
	public $typeSG = self::TYPESG;
	/**
	 * Contenu texte de l'objet
	 */
	public $texte = '';

	/**
	 * Construction de l'objet
	 *
	 * @param indéfini $pQuelqueChose valeur à partir de laquelle le SG_Texte est créé
	 */
	function __construct($pQuelqueChose = null) {
		$tmpTexte = new SG_Texte($pQuelqueChose);
		$this -> texte = $tmpTexte -> toString();
	}
	/** 1.0.6 ; 2.1 SG_HTML
	* Conversion en code HTML. 
	* Pour le texte riche, il faut conserver la classe d'affichage pour les puces et autres.
	*
	* @return string SG_HTML
	*/
	function toHTML() {
		$texte = $this -> traduireLesURLInternes();
		$ret = '<richtext class="champ_TexteRiche">' . $texte . '</richtext>';
		return new SG_HTML($ret);
	}

	/** 1.0.6 ; 2.0 parm
	* Affichage
	*
	* @return string code HTML
	*/
	function afficherChamp($pOption = '') {
		return $this -> Afficher();
	}

	/** 1.2 : traitement des liens internes ; 2.0 parm
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function Afficher($pOption = '') {
		return $this -> toHTML();
	}
	/** 0.1 ; 2.0 parm
	* Modification
	*
	* @param $pRefChamp référence du champ HTML
	*
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '', $pValeursPossibles = NULL) {
		$ret = '';
		$id = SG_Champ::idRandom();
		$ret.= '<textarea id="' . $id . '" class="champ_TexteRiche" name="' . $pRefChamp . '">' . htmlspecialchars($this -> texte) . '</textarea>' . PHP_EOL;
		$ret.= '<script>tinymce.init({' . SG_TexteRiche::parametresTinyMCE($id) . '})</script>';
		// pour n'ajouter le script qu'une seule fois
		//$_SESSION['script']['texteriche'] = 'tinymce.init({' . SG_TexteRiche::parametresTinyMCE() . '})' . PHP_EOL;
		//$_SESSION['libs']['tinymce'] = true;
		return $ret;
	}
	/** 1.0.5
	* enlève les balises pour récupérer le texte brut
	*/
	function Texte() {
		$texte = new SG_Texte(strip_tags($this -> texte));
		return $texte;
	}
	/** 1.0.4
	* Retourne @Vrai si le texte est contenu dans le @TexteRiche
	* @param indefini $pQuelqueChose texte à rechecher
	* @param indéfini $pMot du type booléen : vrai si le mot doit être entier (par défaut false)
	* 
	* @return @VraiFaux selon que le texte est ou non trouvé
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
	// 1.1 ajout, 1.3.0 correction pour fontsizeselect, fr, paste-data-image ; 1.3.4 'encadré'
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
	/** 1.2 ajout ; correction si @HTML
	* traduit toutes les URL internes (limite 100 pour éviter les boucles)
	* @param $pTexte
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
				if(getTypeSG($traduit) === '@HTML') {
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
	/** 1.2 ajout ; 1.3.4 @Fichiers ; 2.0 classe
	* transforme le texte [[typedoc/code | libellé affiché]] en URL pour un sget 
	* si pas trouvé sur code, essaie base/id
	*/
	function traduireUneURLInterne($pURL = '') {
		$ret = '';
		$urlinterne = self::getURLInterne($pURL);
		if (isset($urlinterne['doc'])) {
			$doc = $urlinterne['doc'];
			$cle = $urlinterne['cle'];
			$libelle = $urlinterne['libelle'];
			$ret = $pURL;
			if (method_exists($doc, 'Existe') and $doc -> Existe() -> estVrai() === true) {
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
	/** 1.2 ajout
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
	//1.2 ajout
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
	/** 1.2 ajout ; 1.3.4 [[champ=valeur|xxx]] ; [[@Fichiers=xxx.doc|libellé]] ; 2.0 libelle par défaut = doc.Titre()
	* calcule l'url permettant d'obtenir le renvoi vers le document. Si pas trouvé sur la clé d'accès on essaie sur le titre
	* @param (string) $pURL : valeur de l'url style wiki ([[accès|libellé]]
	* @return (string) : l'url permettant de joindre le document
	**/ 
	function getURLInterne($pURL = '') {		
		$url = $pURL;
		// suppression des crochets
		$url = str_replace('[[','',$url);
		$url = str_replace(']]','',$url);
		$url = explode('|',strip_tags($url));
		// extraction de la partie libellé (à droite de la barre '|' ), sinon tout le texte
		if(sizeof($url) === 1) {
			$libelle = $url[0];
			$url[1] = $libelle;
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
			// recherche du type de document (à gauche du '/') sinon type du contenant 
			$cle = explode('/',$val);
			if(sizeof($cle) === 1) {
				$type = getTypeSG($this -> contenant);
				if($type === '@Champ') {
					$type = getTypeSG($this -> contenant -> document);
				}
				$cle = array($type, $cle[0]);
			}
			$doc = $_SESSION['@SynerGaia'] -> getDocumentsFromTypeChamp($cle[0],$nomChamp,$cle[1]) -> Premier();
			if (method_exists($doc, 'Existe') and $doc -> Existe() -> estVrai() === false) {
				$doc = $_SESSION['@SynerGaia'] -> getDocumentsFromTypeChamp($cle[0],'Titre',$cle[1]) -> Premier();
				if (method_exists($doc, 'Existe') and $doc -> Existe() -> estVrai() === false) {
					$doc = $_SESSION['@SynerGaia'] -> getObjet($val);
				}
			}
			if ($libelle === '' and method_exists($doc, 'toString')) {
				$libelle = $doc -> toString();
			}
			$ret = array('cle'=>implode('/',$cle), 'libelle' => $libelle,'doc'=> $doc);
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_TexteRiche_trait;
}
?>
