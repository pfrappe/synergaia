<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* SG_Theme : Classe de gestion d'un theme (onglet)
**/// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Theme_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Theme_trait.php';
} else {
	trait SG_Theme_trait{};
}
class SG_Theme extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Theme';
	public $typeSG = self::TYPESG;

	// Code du theme
	public $code;

	/** 1.0.7 ; correction pour recherche par code
	* Construction de l'objet
	*
	* @param string $pCode code du theme
	* @param array $pTableau tableau éventuel des propriétés
	*/
	public function __construct($pCodeTheme = '', $pTableau = null) {
		$tmpCode = new SG_Texte($pCodeTheme);
		$base = SG_Dictionnaire::getCodeBase(self::TYPESG);
		$code = $tmpCode -> texte;
		$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode($base,self::TYPESG,$code);
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		if(! is_array($doc)) {
			$doc = $pTableau;
		}
		$this -> initDocumentCouchDB($code, $doc);
		$this -> code = $this -> getValeur('@Code');
		$this -> setValeur('@Type', self::TYPESG);
	}

	/** 1.0.6
	* Conversion en code HTML
	*
	* @return string code HTML
	*/
	function toHTML($pThemeGraphique = '') {
		$ret = '';
		$themeIcone = '';
		$themeTitre = $this -> toString();
		if ($pThemeGraphique == '') {
			$themegr = SG_ThemeGraphique::ThemeGraphique();
		} else {
			$themegr = $pThemeGraphique;
		}
		if ($this -> doc !== null) {
			$themeIcone = $this -> getValeur('@IconeTheme', '');
		}
		$style = 'padding-left:18px;';
		$ret = '<li onclick="SynerGaia.getMenu(event,\'t=' .  $this -> getUUID() . '\')">';
		if ($themeIcone !== '') { $ret .= ' <img src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/' . $themeIcone . '" alt="" class="ui-li-icon"/>';}
		$ret .=  '<span >' . $themeTitre  . '</span></li>' . PHP_EOL;
		return $ret;
	}

	/** 1.0.6
	 * Génère la page d'accueil du thème
	 *
	 * @return string code HTML
	 */
	function Accueil() {
		return $this -> getValeurPropriete('@Description', '') -> toHTML();
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_Theme">' . $this -> toHTML() . '</span>';
	}

	/** 1.0.6 ; 1.3.1 param 2
	* Modification
	* @param $pRefChamp référence du champ HTML
	* @param $pListeElements (@Collection) : liste des valeurs possibles (par défaut toutes)
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '', $pListeElements = null) {
		$ret = '<select class="champ_Theme" type="text" name="' . $pRefChamp . '">' . PHP_EOL;

		// Propose le choix par défaut (vide)
		$ret .= ' <option value="">(aucun)</option>' . PHP_EOL;

		// Calcule la liste des thèmes
		$modele = getTypeSG($this);
		if (is_null($pListeElements)) {
			$listeThemes = SG_Rien::Chercher($modele) -> Trier('.@Titre');
		} else {
			if (getTypeSG($pListeElements) === '@Formule') {
				$listeThemes = $pListeElements -> calculer();
			} else {
				$listeThemes = $pListeElements;
			}
			if (getTypeSG($listeThemes) !== '@Collection') {
				$listeThemes = new SG_Collection();
			}
		}
		$nbThemes = $listeThemes -> Compter() -> toInteger();
		$uid = $this -> getUUID();
		for ($i = 0; $i < $nbThemes; $i++) {
			$theme = $listeThemes -> elements[$i];
			$selected = '';
			if ($uid === $theme -> getUUID()) {
				$selected = ' selected="selected"';
			}

			$urlIcone = '';
			$themeIcone = $theme -> getValeur('@IconeTheme', '');
			if ($themeIcone !== '') {
				$urlIcone = '' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/' . $themeIcone;
			}

			$ret .= ' <option value="' . $theme -> getUUID() . '"' . $selected . ' style="padding-left:20px; background:url(\'' . $urlIcone . '\') 2px 50% no-repeat;">' . $theme -> toHTML() . '</option>' . PHP_EOL;
		}

		$ret .= '</select>' . PHP_EOL;

		return $ret;
	}
	/** 1.0.6 ; 2.1 supp formule
	*  Retourne le tableau des modèles d'opération du thème auquel j'ai droit
	* 
	* @return @Collection collection des modèles d'opération
	* @formula : @Moi.@ModelesOperations.@Filtrer(.@Theme.@Code.@Egale(code)).@Trier(.@Position)
	*/
	function MesModelesOperation() {
		$mmo = $_SESSION['@Moi'] -> ModelesOperations();
		$id = $this -> getUUID();
		$mmot = array();
		foreach($mmo -> elements as $elt) {
			if ( isset($elt -> doc -> proprietes['@Theme']) and $elt -> doc -> proprietes['@Theme'] === $id) {
				if( isset($elt -> doc -> proprietes['@Position'])) {
					$no = $elt -> doc -> proprietes['@Position'];
				} else {
					$no = 0;
				}
				if(!isset($mmot[$no])) {
					$mmot[$no] = array($elt);
				} else {
					$mmot[$no][] = $elt;
				}
			}
		}
		ksort($mmot);
		$ret = new SG_Collection();
		foreach($mmot as $mot) {
			foreach($mot as $elt) {
				$ret -> elements[] = $elt;
			}
		}
		return $ret;
	}
	
	function Titre() {
		return $this -> getValeur('@Titre');
	}
	
	/** 1.3.1 ajout param ; 1.3.3 ajout id ; perfomance (supp appel @formule); parm event ; titre direct ; 2.0 effacer = true ; 2.2 classes
	* Affiche un menu de theme (si vide cela dépend du paramètre)
	* @param (@VraiFaux ou boolean) afficher si vide
	* @return (@HTML) menu ou vide
	* @formula : .@MesModelesOperation.@PourChaque(.@LienPourNouvelleOperation)
	**/
	function Menu($pMemeSiVide = true) {
		$mobile = (SG_ThemeGraphique::ThemeGraphique() === 'mobile');
		$memeSiVide = new SG_VraiFaux($pMemeSiVide);
		$memeSiVide = $memeSiVide -> estVrai();
		$cible = '';
		if ($mobile) {
			$cible = '\'menuetcorps\'';
			$classe = 'menu';
		} else {
			$cible = '\'centre\'';
			$classe = 'sous-menu';
		}
		$collection = $this -> MesModelesOperation();
		$ret = '';
		if (getTypeSG($collection) === '@Collection') {
			if (sizeof($collection -> elements) === 0) {
				if ($memeSiVide === true) {
					$ret = new SG_Texte(SG_Libelle::getLibelle('0043'));// rien trouvé
				}
			} else {
				$ret.= '<ul class="' . $classe . '">';
				$cible = ''; // ne fonctionne pas
				foreach ($collection -> elements as $ope) {
					$ret.= '<li onclick="SynerGaia.launchOperation(event,\'' . $ope -> code . '\',null,true' .$cible.')">' . $ope -> getValeur('@Titre') . '</li>';
				}
				$ret.= '</ul>';
			}
		} else {
			if (getTypeSG($collection) === '@Erreur') {
				$ret = $collection;
			} else {
				if ($memeSiVide === true) {
					$ret = new SG_Texte(SG_Libelle::getLibelle('0044'));// rien trouvé
				}
			}
		}
		return new SG_HTML($ret);
	}
	
	function Aide() {
		return $this -> getValeurPropriete('@Description', '') -> Afficher();
	}
	
	/** 1.0.3
	 * retourne le code du theme
	 * @return string code du thème
	 */
	function code() {
		return $this -> getValeurPropriete('@Code','');
	}
	
	/** 1.0.3
	 * retourne l'id du document du theme
	 * @return string id du thème
	 */	
	function Id() {
		return $this -> doc -> codeDocument;
	}
	/** 1.1 ajout
	*/
	function postEnregistrer() {
		// remettre à jour les menus
		$ret = $_SESSION['@SynerGaia'] -> ViderCache('n');
	}
	
	/** 2.1. ajout
	* @formula : .@Afficher(.@Titre,.@Code,.@IconeTheme,.@Position,.@Description)
	**/
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Afficher('@Titre','@Code','@IconeTheme','@Position','@Description');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}
	/** 2.1 ajout
	* @formula : .@Modifier(.@Titre,.@Code,.@IconeTheme,.@Position,.@Description)
	**/
	function Modifier() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Modifier('@Titre','@Code','@IconeTheme','@Position','@Description');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Modifier'), $args);
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Theme_trait;	
}
?>
