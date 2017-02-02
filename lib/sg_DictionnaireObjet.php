<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1 (see AUTHORS file)
 * SG_DictionnaireObjet : Classe de gestion d'un objet du dictionnaire
 */
class SG_DictionnaireObjet extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@DictionnaireObjet';

	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Code de l'objet du dictionnaire
	public $code;

	/** 1.0.6
	* Construction de l'objet
	*
	* @param string $pCodeObjet code de l'objet demandé
	* @param array tableau éventuel des propriétés
	*/
	public function __construct($pCodeObjet = null, $pTableau = null) {
		$tmpCode = new SG_Texte($pCodeObjet);
		$base = SG_Dictionnaire::CODEBASE;
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code', ''); 
		$this -> setValeur('@Type', '@DictionnaireObjet');
	}

	/** 2.0 parm ; 2.1 '' si null
	* Conversion en chaine de caractères
	* @return string texte
	*/
	function toString($pDefaut = null) {
		if ($this -> code === null) {
			$ret = '';
		} else {
			$ret = $this -> code;
		}
		return $ret;
	}

	/** 2.0 parm
	* Conversion en code HTML
	* @return string code HTML
	*/
	function toHTML($pDefaut = null) {
		return $this -> toString();
	}

	/**
	* Affichage
	*
	* @return string code HTML
	*/
	function afficherChamp() {
		return '<span class="champ_DictionnaireObjet">' . $this -> toHTML() . '</span>';
	}

	/**
	* Comparaison à un autre modèle d'objet
	*
	* @param indéfini $pQuelqueChose modèle d'objet avec lequel comparer
	* @return SG_VraiFaux vrai si les deux modèles d'objets sont identiques
	*/
	function Egale($pQuelqueChose) {
		$autreObjet = new SG_DictionnaireObjet($pQuelqueChose);
		return new SG_VraiFaux($this -> code === $autreObjet -> code);
	}

	/** 1.3.1 param 2
	* Modification
	* @param $pRefChamp référence du champ HTML
	* @param $pListeElements (collection) : liste des valeurs possibles (par défaut toutes)
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '', $pListeElements = null) {
		$ret = '<select class="champ_DictionnaireObjet" type="text" name="' . $pRefChamp . '">';
		// Propose le choix par défaut (vide)
		$ret .= '<option value="">(aucun)</option>';
		// Calcule la liste des objets du dictionnaire
		$modele = getTypeSG($this);
		if (is_null($pListeElements)) {
			$listeObjets = SG_Rien::Chercher($modele);
		} else {
			if (getTypeSG($pListeElements) === '@Formule') {
				$listeObjets = $pListeElements -> calculer();
			} else {
				$listeObjets = $pListeElements;
			}
			if (getTypeSG($listeObjets) !== '@Collection') {
				$listeObjets = new SG_Collection();
			}
		}
		$nbObjets = $listeObjets -> Compter() -> toInteger();
		for ($i = 0; $i < $nbObjets; $i++) {
			$objet = $listeObjets -> elements[$i];
			$selected = '';
			if ($objet -> code === $this -> code) {
				$selected = ' selected="selected"';
			}
			$ret .= '<option value="' . $objet -> getUUID() . '"' . $selected . '>' . $objet -> toHTML() . '</option>';
		}
		$ret .= '</select>';
		return $ret;
	}
	//1.2 ajout
	function deriveDe($pModele = '', $pRefresh = false) {
		$ret = false;
		$codeCache = '@DictionnaireObjet.deriveDe(' . $this->code . '.' . $pModele . ')';
		if (SG_Cache::estEnCache($codeCache, false) === false or $pRefresh) {
			$n = 0;
			$ok = true;
			$objet = $this;
			while ($ok and $n < 5) {
				$type = $objet -> code;
				if($type === $pModele) {
					$ret = true;
					break;
				} elseif ($type === '@Rien' or $type === '') {
					break;
				}
				$objet = new SG_DictionnaireObjet($objet -> getValeur('@Modele',''));
				$n++;
			}		
			SG_Cache::mettreEnCache($codeCache, $ret, false);
		} else {
			$ret = SG_Cache::valeurEnCache($codeCache, false);
		}
		return $ret;
	}
	/** 2.1 ajout
	* Prépare la compilation de la formule et met à jout le fichier .php
	**/
	function preEnregistrer() {
		$ret = $this -> compiler();
		return $ret;
	}
	/** 2.1 ajout
	* Compile les méthodes et formules de l'objet en PHP
	**/
	function Compiler() {
		// compiler et calculer les formules vers php puis sauvegarder la classe correspondante
		$formule = $this -> getValeur('@Phrase', '');
		$this -> setValeur('@PHP', '');
		$ret = '';
		if ($this -> getValeur('@Original', '0') === '0') {
			$compil = new SG_Compilateur($formule);
			$tmp = $compil -> Traduire();
			if ($compil -> php !== '') {
				$this -> setValeur('@PHP', 'oui' );
				$ret = $this;
			} else {
				$this -> setValeur('@PHP', '' );
				$ret = $compil -> erreur;
			}
			$ret = $compil -> compilerObjet($this);
		}
		return $ret;
	}
	/** 2.1 ajout ; 2.2 param affichertableau = formule
	* @formula : .@Afficher(.@Titre, .@Code,.@Modele,.@Base,.@Description);"Propriétés".@AfficherTitre;.@Chercher("@DictionnairePropriete").@Afficher(.@Titre);"Méthodes".@AfficherTitre;.@Chercher("@DictionnaireMethode").@Afficher(.@Titre)
	**/
	function Afficher() {
		$args = func_get_args();
		if (sizeof($args) === 0) {
			$objet = parent::Afficher('@Titre','@Code','@Modele','@Base','@Description');
			$txt = new SG_Texte("Propriétés");
			$txt = $txt -> AfficherCommeTitre() -> texte;
			$p1 = new SG_Formule();
			$p1 -> php = '$ret = $objet -> getValeur(\'@Code\',\'\');';
			$p1 -> titre = 'Code';
			$p2 = new SG_Formule();
			$p2 -> php = '$ret = $objet -> getValeur(\'@Titre\',\'\');';
			$p2 -> titre = 'Titre';
			$tmp = $this -> Chercher('@DictionnairePropriete', '@Objet', 'e') -> AfficherTableau($p1,$p2);
			$proprietes = $txt . '<br>' . $tmp -> texte;
			$txt = new SG_Texte ("Méthodes");
			$txt = $txt -> AfficherCommeTitre() -> texte;
			$tmp = $this -> Chercher('@DictionnaireMethode','@Objet','e') -> AfficherTableau($p1, $p2);
			$methodes = $txt . '<br>' . $tmp -> texte;
			$ret = $objet -> texte . $proprietes . $methodes; 
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return $ret;
	}
}
?>
