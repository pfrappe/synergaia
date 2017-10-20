<?php
/** SYNERGAIA fichier pour le traitement de l'obje @Theme */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Theme_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Theme_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.1.1 
	 */
	trait SG_Theme_trait{};
}

/**
 * SG_Theme : Classe de gestion d'un theme et de ses menus de
 * @version 2.6
 */
class SG_Theme extends SG_Document {
	/** string Type SynerGaia '@Theme' */
	const TYPESG = '@Theme';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Code du theme */
	public $code;

	/**
	 * Construction de l'objet
	 * 
	 * @version 1.0.7 correction pour recherche par code
	 * @param string|SG_Texte|SG_Formule $pCodeTheme code du theme
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

	/**
	* Conversion en code HTML
	* @since 1.0.6
	* @version 2.6 class img
	* @param string $pThemeGraphique (inutilisé)
	* @return string code HTML
	* @uses JS SynerGaia.getMenu()
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
		$ret = '<li class="sg-menu-ligne" onclick="SynerGaia.getMenu(event,\'t=' .  $this -> getUUID() . '\')">';
		if ($themeIcone !== '') { $ret .= ' <img class="sg-menu-ligne-img" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/' . $themeIcone . '" alt="" class="ui-li-icon"/>';}
		$ret .=  '<span >' . $themeTitre  . '</span></li>' . PHP_EOL;
		return $ret;
	}

	/**
	 * Génère la page d'accueil du thème
	 * 
	 * @since 1.0.6
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

	/**
	* Calcul du code html pour la modification comme champ
	* 
	* @since 1.0.6
	* @version 1.3.1 param 2
	* @param string $pRefChamp référence du champ HTML
	* @param SG_Collection $pListeElements liste des valeurs possibles (par défaut toutes)
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

	/**
	 *  Retourne le tableau des modèles d'opération du thème auquel j'ai droit
	 * @since 1.0.6
	 * @version 2.1 supp formule
	 * @return SG_Collection collection des modèles d'opération
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

	/**
	 * Retourne le titre du thème (sinon le code)
	 * @since 1.3.1
	 * @return SG_Texte
	 */
	function Titre() {
		$ret = $this -> getValeur('@Titre','');
		if($ret === '') {
			$ret = $this -> getValeur('@Code','');
		}
		return $ret;
	}

	/**
	 * Affiche un menu de theme (si vide cela dépend du paramètre)
	 * @since 1.0
	 * @version 1.3.1 ajout param
	 * @version 1.3.3 ajout id ; perfomance (supp appel @formule); parm event ; titre direct
	 * @version 2.0 effacer = true
	 * @version 2.2 classes
	 * @version 2.6 traitement menu à deux niveaux (xxx//yyy)
	 * @param boolean|SG_VraiFaux|SG_Formule $pMemeSiVide afficher quand même si vide
	 * @return SG_HTML menu ou vide
	 * @formula : .@MesModelesOperation.@PourChaque(.@LienPourNouvelleOperation)
	 * @uses JS SynerGaia.launchOperation()
	 */
	function Menu($pMemeSiVide = true) {
		$mobile = (SG_ThemeGraphique::ThemeGraphique() === 'mobile');
		$memeSiVide = new SG_VraiFaux($pMemeSiVide);
		$memeSiVide = $memeSiVide -> estVrai();
		if ($mobile) {
			$cible = '\'menuetcorps\'';
			$classe = 'sg-menu';
		} else {
			$cible = '\'centre\'';
			$classe = 'sg-sous-menu';
		}
		$lignes = array();
		$sousmenu = array();
		$collection = $this -> MesModelesOperation();
		$ret = '';
		if (getTypeSG($collection) === '@Collection') {
			if (sizeof($collection -> elements) === 0) {
				if ($memeSiVide === true) {
					$ret = new SG_Texte(SG_Libelle::getLibelle('0043'));// rien trouvé
				}
			} else {
				foreach ($collection -> elements as $ope) {
					$titre = $ope -> getValeur('@Titre', $ope -> code);
					$ipos = strpos($titre, '//');
					$ligne = '<li class="sg-menu-ligne" onclick="SynerGaia.launchOperation(event,\'' . $ope -> code . '\',null,true,' .$cible.')">';
					// si de la forme code//titre on crée un tableau
					if ($ipos !== false) {
						$codemenu = substr($titre, 0, $ipos);
						$ligne.= substr($titre, $ipos + 2) . '</li>';
						if (isset($lignes[$codemenu]) and is_string($lignes[$codemenu])) {
							$lignes[$codemenu] = array($lignes[$codemenu], $ligne);
						} else {
							$lignes[$codemenu][] = $ligne;
						}
					} else {
						if (isset($lignes[$titre])) {
							if (! is_array($lignes[$titre])) {
								$lignes[$titre] = array($lignes[$titre]);
							}
							$lignes[$titre][] = $ligne . $titre . '</li>';
						} else {
							$lignes[$titre] = $ligne . $titre . '</li>'; 
						}
					}
				}
				$ret.= '<ul class="sg-sous-menu">';
				foreach ($lignes as $key => $ligne) {
					if (is_array($ligne)) {
						$ret.= '<li class="sg-menu-ligne">' . $key;
						$ret.= '<ul class="sg-menu-menu">';
						foreach ($ligne as $sl) {
							$ret.= $sl;
						}
						$ret.= '</ul></li>';
					} else {
						$ret.= $ligne;
					}
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

	/**
	 * Retourne la valeur du champ description à titre d'aide pour le thème
	 * @since 1.0.3
	 * @return SG_HTML
	 */
	function Aide() {
		return $this -> getValeurPropriete('@Description', '') -> Afficher();
	}

	/**
	 * retourne le code du theme
	 * @since 1.0.3
	 * @return string code du thème
	 */
	function code() {
		return $this -> getValeurPropriete('@Code','');
	}

	/**
	 * retourne l'id du document du theme
	 * @since 1.0.3
	 * @return string id du thème
	 */	
	function Id() {
		return $this -> doc -> codeDocument;
	}

	/**
	 * Traitement après l'enregistrement d'un thème :
	 * - vider le cache de la navigation pour forcer le recalcul des menus
	 * @since 1.1 ajout
	 * @return boolean true
	 */
	function postEnregistrer() {
		// remettre à jour les menus
		$ret = $_SESSION['@SynerGaia'] -> ViderCache('n');
		return true;
	}

	/**
	 * Calcule le code html pour l'affichage d'un thème comme document
	 * Par défaut : champs @Titre,@Code,@IconeTheme,@Position,@Description
	 * @since 2.1. ajout
	 * @return SG_HTML
	 * @formula : .@Afficher(.@Titre,.@Code,.@IconeTheme,.@Position,.@Description)
	 */
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$ret = parent::Afficher('@Titre','@Code','@IconeTheme','@Position','@Description');
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}

	/**
	 * Calcul le code html pour la modification du documentTHème
	 * @since 2.1 ajout
	 * @return SG_HTML
	 * @formula : .@Modifier(.@Titre,.@Code,.@IconeTheme,.@Position,.@Description)
	 */
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
