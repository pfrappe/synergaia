<?php
/** SynerGaia fichier contenant le traitement de l'objet @DictionnaireObjet */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_DictionnaireObjet_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_DictionnaireObjet_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
	 * @since 2.4
	 */
	trait SG_DictionnaireObjet_trait{};
}

/**
 * SG_DictionnaireObjet : Classe de gestion d'un objet du dictionnaire
 * @version 2.4 ajout de Methodes(), Proprietes()
 * @version 2.6 ajout de AjouterPropriete(), AjouterMethode()
 */
class SG_DictionnaireObjet extends SG_Document {
	/** string Type SynerGaia '@DictionnaireObjet' */
	const TYPESG = '@DictionnaireObjet';

	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/** string Code de l'objet du dictionnaire */
	public $code;

	/**
	 * Construction de l'objet
	 *
	 * @since 1.0.6
	 * @param string $pCodeObjet code de l'objet demandé
	 * @param array $pTableau tableau éventuel des propriétés
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

	/**
	 * Conversion en chaine de caractères
	 * @version 2.1 return '' si null
	 * @param any $pDefaut inutilisé
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

	/**
	 * Conversion en code HTML
	 * @version 2.0 parm pour compatibilité
	 * @param any $pDefaut inutilisé
	 * @return string code HTML
	 */
	function toHTML($pDefaut = null) {
		return $this -> toString();
	}

	/**
	 * Calcule le code html pour l'affichage en saisie dans un champ
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_DictionnaireObjet">' . $this -> toHTML() . '</span>';
	}

	/**
	 * Comparaison à un autre modèle d'objet
	 * Retourne vrai si les objets ont le même code
	 *
	 * @param SG_Objet $pQuelqueChose modèle d'objet avec lequel comparer
	 * @return SG_VraiFaux vrai si les deux modèles d'objets sont identiques
	 */
	function Egale($pQuelqueChose) {
		$autreObjet = new SG_DictionnaireObjet($pQuelqueChose);
		return new SG_VraiFaux($this -> code === $autreObjet -> code);
	}

	/**
	 * Calcule le code html pour la modification dans un champ
	 * @version 1.3.1 param 2
	 *
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

	/**
	 * Teste si cet objet dérive d'un autre modèle passé en paramètre
	 * 
	 * @since 1.2 ajout
	 * @param string $pModele code du modèle à vérifier
	 * @param boolean $pRefresh force le rafriachissement des valeurs en cache
	 * @return boolean
	 */
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

	/**
	 * Prépare la compilation de la formule et met à jout le fichier .php
	 * 
	 * @since  2.1 ajout
	 * @return boolean|SG_Erreur
	 */
	function preEnregistrer() {
		$ret = $this -> Compiler();
		return $ret;
	}

	/**
	 * Compile les méthodes et formules de l'objet en PHP et stocke le résultat dans le répertoire des objets compilés
	 * 
	 * @since 2.1 ajout
	 * @return boolean|SG_Erreur
	 */
	function Compiler() {
		// compiler et calculer les formules vers php puis sauvegarder la classe correspondante
		$formule = $this -> getValeur('@Phrase', '');
		$this -> setValeur('@PHP', '');
		$ret = '';
		if ($this -> getValeur('@Original', '0') === '0') {
			// calculer le PHP
			$compil = new SG_Compilateur($formule);
			$compil -> titre = 'Objet : ' . $this -> toString();
			$tmp = $compil -> Traduire();
			if (getTypeSG($compil -> erreur) === '@Erreur') {
				SG_Pilote::OperationEnCours() -> erreurs[] = $compil -> erreur;
				$ret = $compil -> erreur;
			} else {
				if ($compil -> php !== '') {
					$this -> setValeur('@PHP', 'oui' );
					$ret = $this;
				} else {
					$this -> setValeur('@PHP', '' );
					$ret = $compil -> erreur;
				}
				// créer et enregistrer la classe .php correspondante
				$ret = $compil -> compilerObjet($this);
				if (getTypeSG($compil -> erreur) === '@Erreur') {
					SG_Pilote::OperationEnCours() -> erreurs[] = $compil -> erreur;
				}
			}
		}
		return $ret;
	}

	/**
	 * Calcule le code html pour l'affichage de l'objet
	 * Sans paramètres : ajoute la liste des popriétés et méthodes)
	 * 
	 * @since 2.1 ajout
	 * @version 2.2 param affichertableau = formule
	 * @version 2.6 propriétés ajout @Modele
	 * @return SG_HTML
	 * @formula :
	 *	.@Afficher(.@Titre, .@Code,.@Modele,.@Base,.@Description);
	 *	"Propriétés".@AfficherTitre;
	 *	.@Chercher("@DictionnairePropriete").@Afficher(.@Titre);
	 *	"Méthodes".@AfficherTitre;
	 *	.@Chercher("@DictionnaireMethode").@Afficher(.@Titre)
	 */
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
			$p3 = new SG_Formule();
			$p3 -> php = '$ret = $objet -> getValeurPropriete(\'@Modele\',\'\');';
			$p3 -> titre = 'Modèle';
			$tmp = $this -> Chercher('@DictionnairePropriete', '@Objet', 'e') -> AfficherTableau($p1,$p2,$p3);
			$proprietes = $txt . '<br>' . $tmp -> texte;
			$txt = new SG_Texte ("Méthodes");
			$txt = $txt -> AfficherCommeTitre() -> texte;
			$tmp = $this -> Chercher('@DictionnaireMethode','@Objet','e') -> AfficherTableau($p1, $p2);
			$methodes = $txt . '<br>' . $tmp -> texte;
			$ret = $objet -> texte . $proprietes . $methodes; 
		} else {
			$ret = call_user_func_array(array('SG_Document', 'Afficher'), $args);
		}
		return new SG_HTML($ret);
	}

	/**
	 * Retourne la collection des propriétés d'un objet
	 * 
	 * @since 2.4 ajout
	 * @param string|SG_Texte|SG_Formule $pOriginal : seules les propriétés ajoutées ("a") ou SynerGaia ("s") ou tout ("") défaut
	 * @param boolean|SG_VraiFaux|SG_Formule $pRecursif : donne aussi les propriétés des classes parentes
	 * @return SG_Collection : collection des propriétés
	 */
	function Proprietes($pOriginal = '', $pRecursif = true) {
		$ret = new SG_Collection();
		$p = SG_Dictionnaire::getProprietesObjet($this -> code);
		foreach ($p as $key => $id) {
			$ret -> elements[] = SG_Dictionnaire::getPropriete($this -> code, $key);
		}
		return $ret;
	}

	/**
	 * retourne la collection des propriétés d'un objet
	 * 
	 * @since 2.4 ajout
	 * @param string|SG_Texte|SG_Formule $pOriginal : seules les propriétés ajoutées ("a") ou SynerGaia ("s") ou tout ("") défaut
	 * @param boolean|SG_VraiFaux|SG_Formule $pRecursif @VraiFaux : donne aussi les propriétés des classes parentes
	 * @return SG_Collection : collection des propriétés
	 */
	function Methodes($pOriginal = '', $pRecursif = true) {
		$ret = new SG_Collection();
		$p = SG_Dictionnaire::getMethodesObjet($this -> code);
		foreach ($p as $key => $id) {
			$ret -> elements[] = SG_Dictionnaire::getMethode($this -> code, $key);
		}
		return $ret;
	}

	/**
	 * Ajoute ou met à jour une propriété (SG_DictionnairePropriete) d'un objet existant
	 * Si elle n'existe pas, son id sera objet.code
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pCode code de la propriété
	 * @param string|SG_Texte|SG_Formule $pTitre (défaut = code)
	 * @param string|SG_Texte|SG_Formule $pModele (par défaut @Texte)
	 * @return SG_DicionnairePropriete|SG_Erreur la proprieté créée ou une erreur
	 */
	function AjouterPropriete($pCode = '', $pTitre = '', $pModele = '') {
		$codeObjet = $this -> getValeur('@Code','');
		$code = SG_Texte::getTexte($pCode);
		$codedoc = $codeObjet . '.' . $code;
		// voir si la propriété existe déjà
		$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(SG_Dictionnaire::CODEBASE, SG_DictionnairePropriete::TYPESG, $codedoc);
		if ($doc instanceof SG_Erreur) {
			if ($doc -> code === '0234') {
				$doc = new SG_DictionnairePropriete();
				$tab = array();
				$tab['_id'] = $codedoc;
			} else {
				$ret = $doc;
			}
		} else {
			$tab = $doc -> doc -> proprietes;
		}
		if (! $doc instanceof SG_Erreur) {
			$ret = $doc;
			$tab['@Code'] =  $codedoc;
			$tab['@Propriete'] = $code;
			$tab['@Objet'] = $this -> getUUID();
			if ($pModele instanceof SG_DictionnaireObjet) {
				$modele = $pModele;
			} else {
				$modele = SG_Texte::getTexte($pModele);
				if ($modele === '') {
					$modele = SG_Texte::TYPESG;
				}
				$modele = SG_Dictionnaire::getDictionnaireObjet($modele);
			}
			$tab['@Modele'] = $modele -> getUUID();
			$titre =  SG_Texte::getTexte($pTitre);
			if ($titre === '') {
				$titre = $code;
			}
			$tab['@Titre'] = $titre;
			$doc -> doc -> proprietes = $tab;
			$doc -> Enregistrer();
		}
		return $ret;
	}

	/**
	 * Ajoute ou met à jour une méthode (SG_DictionnaireMethode) d'un objet existant
	 * Si elle n'existe pas, son id sera objet.code
	 * 
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pCode code de la propriété
	 * @param string|SG_Texte|SG_Formule $pTitre (défaut = code)
	 * @param string|SG_Texte|SG_Formule $pModele (par défaut @Texte)
	 * @param string|SG_Texte|SG_Formule $pFormule la formule de la méthode
	 * @return SG_DicionnaireMethode|SG_Erreur la méthode créée ou une erreur
	 */
	function AjouterMethode($pCode = '', $pTitre = '', $pModele = '', $pFormule = '') {
		$codeObjet = $this -> getValeur('@Code','');
		$code = SG_Texte::getTexte($pCode);
		$codedoc = $codeObjet . '.' . $code;
		// voir si la propriété existe déjà
		$doc = $_SESSION['@SynerGaia'] -> sgbd -> getObjetParCode(SG_Dictionnaire::CODEBASE, SG_DictionnaireMethode::TYPESG, $codedoc);
		if ($doc instanceof SG_Erreur) {
			if ($doc -> code === '0234') {
				$doc = new SG_DictionnaireMethode();
				$tab = array();
				$tab['_id'] = $codedoc;
			} else {
				$ret = $doc;
			}
		} else {
			$tab = $doc -> doc -> proprietes;
		}
		if (! $doc instanceof SG_Erreur) {
			$ret = $doc;
			$tab = array();
			$tab['_id'] = $codedoc;
			$tab['@Code'] =  $codedoc;
			$tab['@Methode'] = $code;
			$tab['@Objet'] = $this -> getUUID();
			if ($pModele instanceof SG_DictionnaireObjet) {
				$modele = $pModele;
			} else {
				$modele = SG_Texte::getTexte($pModele);
				if ($modele === '') {
					$modele = SG_Texte::TYPESG;
				}
				$modele = SG_Dictionnaire::getDictionnaireObjet($modele);
			}
			$tab['@Modele'] = $modele -> getUUID();
			$titre =  SG_Texte::getTexte($pTitre);
			if ($titre === '') {
				$titre = $code;
			}
			$tab['@Titre'] = $titre;
			$doc -> doc -> proprietes = $tab;
			$doc -> setValeur('@Action', $pFormule);
			$doc -> Enregistrer();
		}
		return $ret;
	}

	// 2.4 complément de classe créée par compilation
	use SG_DictionnaireObjet_trait;
}
?>
