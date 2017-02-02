<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1 (see AUTHORS file)
* SG_Vue : Classe de gestion d'une vue de base de données
*/
class SG_Vue extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Vue';
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;
	// Vue physique associée
	public $vue;
	// Document de définition de la vue
	public $doc = '';
	// Code de la vue
	public $code = '';
	// Code de la base associée
	public $codeBase = '';

	// Sélection (1.3.4 private)
	private $selection = '';
	// 1.3.4 json calculé de la sélection (via setSelection)
	private $jsonselection = '';
	// 1.3.4 sha1 de la selection (via setSelection)
	private $sha1selection = '';
	
	/** 0.1 ; 2.0 getTexte
	* Construction de l'objet
	*
	* @param indéfini $pCodeVue code de la vue (base / vue)
	* @param indéfini $pCodeBase code de la base
	* @param indéfini $pSelection sélection des documents
	* @param boolean $pFormule indique si on vient de la traduction d'une formule (autre) ou de la programmation en PHP (true)
	*/
	function __construct($pCodeVue = '', $pCodeBase = '', $pSelection = '', $pDirect = false) {
		$this -> code = SG_Texte::getTexte($pCodeVue);
		$this -> codeBase = SG_Texte::getTexte($pCodeBase);
		$this -> setSelection($pSelection);
		if ($this -> code !== '' and $pDirect !== true) {
			// ne pas employer SG_Rien::Chercher() sinon boucle et remplissage mémoire
			$collection = $_SESSION['@SynerGaia']->getChercherDocuments(SG_DictionnaireVue::CODEBASE, '@DictionnaireVue', $this -> code, '');
			$defvue = $collection -> Premier();
			if (is_object($defvue) and $defvue -> Existe() -> EstVrai()) {
				$nomobjet = $defvue -> getValeur('@Objet','');
				$objet = new SG_DictionnaireObjet($nomobjet);
				$base = $objet -> getValeur('@Base','');
				$this -> setBase($base);
				$selection = $defvue -> getValeur('@Filtre','');
				$this -> setSelection($selection);
			}
		}
	}

	/** 0.1 ; 2.0 si pas code base, l'ajouter
	* Définition de la base associée
	* @param indéfini $pCodeBase code de la base
	*/
	function setBase($pCodeBase = '') {
		$this -> codeBase = $pCodeBase;
		if (strpos($this -> code, '/') === false) {
			$this -> code = $this -> codeBase . '/' . $this -> code;
		}
	}

	/** 0.1
	* Définition de la formule de sélection
	* @param indéfini $pSelection formule de sélection
	*/
	function setSelection($pSelection = '') {
		if(is_array($pSelection)) {
			$this -> selection = $pSelection;
		} else {
			$this -> selection = array('all' => array('map' => $pSelection));
		}
		$this -> jsonselection = json_encode($this -> selection);
		$this -> sha1selection = sha1($this -> jsonselection);
	}
	/** 1.0.7 ; 1.3.4 json
	* getSelection : phrase de sélection de la vue
	*/
	function getSelection() {
		$ret = '';
		$docvue = $this -> getDocumentVue();
		if ($docvue) {
			$ret = $docvue -> getValeur('views');
		}
		return $ret;
	}

	/** 1.0.7
	* Calcule le code de la vue
	* @return string code de la vue
	*/
	function getCodeVue() {
		if ($this -> code === '') {
			$this -> code = $this -> codeBase . '/vue_' . $this -> sha1selection;
		}
		return $this -> code;
	}

	/**
	* Donne le document de définition de la vue
	* @return SG_Document document de définition physique de la vue
	*/
	function getDocumentVue() {
		if ($this -> doc === '') {
			$tmpInfos = explode('/', $this -> getCodeVue());
			$tmpCodeBase = $tmpInfos[0];
			if(isset($tmpInfos[1])) {
				$tmpCodeDocument = '_design/' . $tmpInfos[1];
				$this -> doc = new SG_Document($tmpCodeBase . '/' . $tmpCodeDocument);
			} else {
				$this -> doc = new SG_Erreur('0113', $tmpCodeBase . '/' . $this -> code);
			}
		}
		return $this -> doc;
	}

	/** 1.0.7 ; 1.3.4 estErreur()
	* Création de la vue
	* @return boolean OK ?
	*/
	function creerVue() {
		$ret = false;
		// Si on a un code de vue
		if ($this -> code !== '') {
			// Si la vue n'existe pas encore
			if ($this -> Existe() -> estVrai() === false) {
				$ret = ! ($this -> Enregistrer() -> estErreur());
			} elseif (json_encode($this -> getSelection()) !== $this -> jsonselection) {
				$ret = ! ($this -> Enregistrer() -> estErreur());
			} else {
				$ret = true;
			}
		} else {
			// Si on n'a pas de code de vue mais un code de base et une sélection
			if ($this -> codeBase !== '') {
				if ($this -> selection !== '') {
					// Si la vue n'existe pas encore
					if ( ! $this -> Existe() -> estVrai()) {
						$ret = ! ($this -> Enregistrer() -> estErreur());
					} else {
						$ret = true;
					}
				} else {
					// pas de sélection
				}
			} else {
				// pas de codeBase
			}
		}

		if ($ret === true) {
			$this -> vue = new SG_VueCouchDB($this -> code);
		}

		return $ret;
	}
	/** 1.0.6
	* Calcule le contenu de la vue
	* @param string $pCleRecherche clé de recherche
	* @return SG_Collection contenu
	*/
	function Contenu($pCleRecherche = '', $pFiltre = '', $pIncludeDocs = false, $pExactMatch = true) {
		if ($this -> creerVue() === true) {
			$ret = $this -> vue -> Contenu($pCleRecherche, $pFiltre, $pIncludeDocs, $pExactMatch);
		} else {
			$ret = new SG_Collection();
		}
		return $ret;
	}
	/** 1.0.6
	* Chercher à partir d'une clé
	* @param string $pCleRecherche clé de recherche
	* @return SG_Collection résultat de la recherche
	*/
	function ChercherElements($pCleRecherche = '', $pFiltre = '') {
		$result = $this -> Contenu($pCleRecherche, $pFiltre, true);
		return $result;
	}
	/**
	* Chercher la première valeur trouvée à partir d'une clé
	* @param string $pCleRecherche clé de recherche
	* @return string résultat de la recherche
	*/
	function ChercherValeur($pCleRecherche = '') {
		if ($this -> creerVue() === true) {
			return $this -> vue -> ChercherValeur($pCleRecherche);
		} else {
			return null;
		}
	}
	/**
	* Chercher toutes les valeurs à partir d'une clé
	* @param string $pCleRecherche clé de recherche
	* @return SG_Collection résultat de la recherche
	*/
	function ChercherValeurs($pCleRecherche = '') {
		if ($this -> creerVue() === true) {
			return $this -> vue -> ChercherValeurs($pCleRecherche);
		} else {
			return null;
		}
	}
	/** 2.0 test erreur
	* Vue existe ?
	* @return SG_VraiFaux selon que vue existe ou non
	*/
	function Existe() {
		$doc = $this -> getDocumentVue();
		if (getTypeSG($doc) === '@Erreur') {
			$ret = new SG_VraiFaux(false);
		} else {
			$ret = $doc -> Existe();
		}
		return $ret;
	}
	/** 1.3.2 enregistrer(false, FALSE) pour éviter boucle ; 2.3 retour objet
	* Enregistre la définition de la vue
	* @return SG_VraiFaux enregistrement ok
	*/
	function Enregistrer() {
		// On fabrique la définition de la vue
		$tmpDocVue = $this -> getDocumentVue();
		$tmpDocVue -> setValeur('language', 'javascript');

		if ($this -> selection !== '') {
			if (is_string($this -> selection)) {
				$tmpVues = array('all' => array('map' => $this -> selection));
			} else {
				$tmpVues = $this -> selection;
			}
			$tmpDocVue -> setValeur('views', $tmpVues);
		}
		$ret = $tmpDocVue -> Enregistrer(false, false);
		if (! is_object($ret)) {
			$ret = new SG_VraiFaux($ret);
		} 
		return $ret;
	}
	/** 1.3.4 ajout
	* Retourne le tableau de la vue 'categorie'
	*/
	function Categorie() {
		if ($this -> creerVue() === true) {
			$ret = $this -> vue -> contenuBrut('', '', false, 'categorie?group=true');
		} else {
			$ret = array();
		}
		return $ret;
	}
	/* 2.0 ajout
	* Obtenir le document de Définition de la vur (dans le dictionnaire)
	/* 2.0 ajout
	* Recherche dynamique sur le poste de l'utilisateur
	**/
	function Choisir($pLibelle = '') {
		$libelle = SG_Texte::getTexte($pLibelle);
		// créer l'html du champ
		$nom = 'vue_' . $this -> getCodeCourt();
		$ret = '<select id="' . $nom . '" type="text" name="' . $nom . '">' . $libelle . '</select>';
		$ret .= '&nbsp&nbsp<img src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/zoom.png"><input id="' . $nom . 'Recherche" type="text" size="30" value=""></input>';
		// ajouter le script de recherche
		$ret .= '<script>' . PHP_EOL;
		$ret .= '$("#' . $nom . 'Recherche").keyup(function() {var cle=$(this).val();SynerGaia.vuechoisir(cle,"' . $nom . '",';
		$valeurs = $this -> ChercherValeurs('');
		if(getTypeSG($valeurs) === '@Erreur') {
			$ret .= '["' . $valeurs -> getMessage() . '"]';
		} else {
			$ret .= json_encode($valeurs);
		}
		$ret .= ')});' . PHP_EOL;
		$ret .= '</script>' . PHP_EOL;
		return $ret;
	}
	/** 2.0 ajout
	* Conversion en chaine de caractères
	* @return string texte
	*/
	function toString() {
		$ret = $this -> code;
		return $ret;
	}
	/** 2.0 ajout
	* retourne le nom seul de la vue (sans la base)
	* @return string code vue
	*/
	function getCodeCourt() {
		$i = strpos($this -> code, '/');
		if ($i === false) {
			$ret = $this -> code;
		} else {
			$ret = substr($this -> code, $i + 1);
		}
		return $ret;
	}
}
?>
