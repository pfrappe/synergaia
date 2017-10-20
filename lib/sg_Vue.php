<?php
/** SynerGaia fichier pour la gestion de l'objet @Vue */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Vue : Classe de gestion d'une vue logique sur une base de données
 * @since 2.1
 */
class SG_Vue extends SG_Objet {
	/** string Type SynerGaia '@Vue' */
	const TYPESG = '@Vue';
	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;
	/** SG_VueCouchDB|SG_VueDomino Vue physique associée */
	public $vue;
	/** SG_DocumentCouchDB Document de définition de la vue */
	public $doc = '';
	/** string Code de la vue */
	public $code = '';
	/** string Code de la base associée */
	public $codeBase = '';

	/** string phrase javascript de la sélection */
	private $selection = '';
	/** string json calculé de la sélection (via setSelection) */
	private $jsonselection = '';
	/** string sha1 de la selection (via setSelection) */
	private $sha1selection = '';
	
	/**
	 * Construction de l'objet
	 * 
	 * @since 0.1
	 * @version 2.0 getTexte
	 * @param indéfini $pCodeVue code de la vue (base / vue)
	 * @param indéfini $pCodeBase code de la base
	 * @param indéfini $pSelection sélection des documents
	 * @param boolean $pDirect indique si on vient de la traduction d'une formule (autre) ou de la programmation en PHP (true)
	 */
	function __construct($pCodeVue = '', $pCodeBase = '', $pSelection = '', $pDirect = false) {
		$this -> code = SG_Texte::getTexte($pCodeVue);
		$this -> codeBase = SG_Texte::getTexte($pCodeBase);
		$this -> setSelection($pSelection);
		if ($this -> code !== '' and $pDirect !== true) {
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

	/**
	 * Définition de la base associée
	 * 
	 * @since 0.1
	 * @version 2.0 si pas code base, l'ajouter
	 * @param indéfini $pCodeBase code de la base
	 */
	function setBase($pCodeBase = '') {
		$this -> codeBase = $pCodeBase;
		if (strpos($this -> code, '/') === false) {
			$this -> code = $this -> codeBase . '/' . $this -> code;
		}
	}

	/**
	 * Définition de la formule de sélection
	 * 
	 * @since 0.1
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

	/**
	 * getSelection : phrase de sélection de la vue
	 * 
	 * @since 1.0.7
	 * @version 1.3.4 json
	 * @return string
	 */
	function getSelection() {
		$ret = '';
		$docvue = $this -> getDocumentVue();
		if ($docvue) {
			$ret = $docvue -> getValeur('views');
		}
		return $ret;
	}

	/**
	 * Calcule le code de la vue
	 * 
	 * @since 1.0.7
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

	/**
	 * Création de la vue
	 * 
	 * @since 1.0.7
	 * @version 1.3.4 estErreur()
	 * @return boolean OK ?
	 * @todo : mettre des @Erreur sur les branches vides
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

	/**
	 * Calcule le contenu de la vue
	 * 
	 * @since 1.0.6
	 * @version 2.4 erreur 0249
	 * @param string $pCleRecherche clé de recherche
	 * @param string $pFiltre
	 * @param boolean $pIncludeDocs
	 * @param boolean $pExactMatch
	 * @return SG_Collection contenu
	 */
	function Contenu($pCleRecherche = '', $pFiltre = '', $pIncludeDocs = false, $pExactMatch = true) {
		if ($this -> creerVue() === true) {
			$ret = $this -> vue -> Contenu($pCleRecherche, $pFiltre, $pIncludeDocs, $pExactMatch);
		} else {
			$ret = new SG_Erreur('0249', $this -> sha1selection); // erreur sur vue
		}
		return $ret;
	}

	/**
	 * Chercher à partir d'une clé
	 * 
	 * @since 1.0.6
	 * @version 2.4 parm 3
	 * @param string $pCleRecherche clé de recherche
	 * @param string $pFiltre filtre de sélection
	 * @param boolean $pIncludeDocs inlure les documents (défaut true)
	 * @return SG_Collection résultat de la recherche
	 */
	function ChercherElements($pCleRecherche = '', $pFiltre = '', $pIncludeDocs = true) {
		$result = $this -> Contenu($pCleRecherche, $pFiltre, $pIncludeDocs);
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

	/**
	 * Vue existe ?
	 * 
	 * @version 2.0 test erreur
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

	/**
	 * Enregistre la définition de la vue
	 * @version 2.3 retour objet
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

	/**
	 * Retourne le tableau de la vue 'categorie'
	 * 
	 * @since 1.3.4 ajout
	 * @return array
	 */
	function Categorie() {
		if ($this -> creerVue() === true) {
			$ret = $this -> vue -> contenuBrut('', '', false, 'categorie?group=true');
		} else {
			$ret = array();
		}
		return $ret;
	}

	/**
	 * Recherche dynamique sur le poste de l'utilisateur
	 * 
	 * @since 2.0 ajout
	 * @param string|SG_Texte|SG_Formule $pLibelle
	 * @return string code HTML
	 * @uses SynerGaia.vuechoisir()
	 */
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

	/**
	 * Conversion en chaine de caractères
	 * 
	 * @since 2.0 ajout
	 * @return string texte
	 */
	function toString() {
		$ret = $this -> code;
		return $ret;
	}

	/**
	 * retourne le nom seul de la vue (sans la base)
	 * 
	 * @since 2.0 ajout
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
