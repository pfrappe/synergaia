<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
 * SG_ModeleOperation : Classe de gestion d'un modele d'opération
 */
class SG_ModeleOperation extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@ModeleOperation';
	
	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	// Code du modèle d'opération
	public $code = '';

	/** 1.2 recherche code base
	 * Construction de l'objet
	 *
	 * @param indéfini $pCodeModeleOperation code du modèle d'opération
	 * @param array $pTableau tableau éventuel des propriétés du document pkhysique
	 */
	function __construct($pCodeModeleOperation = '', $pTableau = null) {
		$tmpCode = new SG_Texte($pCodeModeleOperation);
		$base = SG_Dictionnaire::getCodeBase('@ModeleOperation');
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> initDocument();
	}
	/** 2.1 ajout
	* initDocument : Termine l'initialisation spécifique du document
	**/
	function initDocument() {
		$this -> setValeur('@Type', '@ModeleOperation');
		$this -> code = $this -> getValeur('@Code');
	}

	/** 2.0 parm
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString($pDefaut = NULL) {
		return $this -> getValeur('@Titre', $this -> code);
	}

	/** 2.0 parm
	* Conversion en code HTML
	*
	* @return string code HTML
	*/
	function toHTML($pDefaut = NULL) {
		return $this -> toString();
	}

	/** 1.3.0 supp 'htmlentities' ; 1.3.1 resume ; 1.3.2 parametre pour exécution en sg_get ; 1.3.3 parm event ; 2.0 effacer = true
	 * Fabrication du lien vers une nouvelle opération avec ce modèle
	 *
	 * @return string code HTML du lien
	 */
	function LienPourNouvelleOperation($pSGGet = true, $pCible = '') {
		if(is_object($pSGGet)) {
			$sgget = SG_VraiFaux::getBooleen($pSGGet);
		} else {
			$sgget = $pSGGet;
		}
		$cible = SG_Texte::getTexte($pCible);
		$ret = '';
		if ($this -> doc) {
			$titre = $this -> getValeurPropriete('@Titre', $this -> code) -> toHTML() -> texte;
			$icone = $this -> getValeur('@IconeOperation', '');
			$resume = $this -> getValeurPropriete('@Description', '') -> Texte() -> Debut(150) -> texte;
			if ($sgget) {
			/*	if ($cible !== '' and $cible !== 'centre') { // ne fonctionne pas
					$cible = ',{centre:\'' . $cible . '\'}';
				} */
				$cible = '';
				$ret.='<span onclick="SynerGaia.launchOperation(event,\'' . $this -> code . '\',null, true' . $cible . ')">' . $titre . '</span>';
			} else {
				$url = SG_Navigation::URL_PRINCIPALE . '?' . SG_Navigation::URL_VARIABLE_MODELEOPERATION . '=' . $this -> code;
				if($resume === '') {
					$resume = $titre;
				}
				$ret .= '<a href="' . $url . '" title="' . $resume . '" >';
				if ($icone !== '') {
					$ret .= '<img src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/' . $icone . '" alt="' . $titre . '" class="ui-li-icon"/>';
				}
				$ret .= '<span>' . $titre . '</span>';
				$ret .= '</a>';
			}
		}
		return $ret;
	}

	/**
	 * Affichage
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_ModeleOperation">' . $this -> toHTML() . '</span>';
	}

	/** 1.0.6 ; 1.3.1 param 2 ; 2.1 correction code document
	* Modification
	* @param $pRefChamp référence du champ HTML
	* @param $pListeElements (collection) : liste des valeurs possibles (par défaut toutes)
	* @return string code HTML
	*/
	function modifierChamp($pRefChamp = '', $pListeElements = null) {
		$ret = '<select class="champ_ModeleOperation" type="text" name="' . $pRefChamp . '">';

		// Propose le choix par défaut (vide)
		$ret .= '<option value="">(aucun)</option>';

		// Calcule la liste des modèles
		$modele = getTypeSG($this);
		if (is_null($pListeElements)) {
			$listeElements = SG_Rien::Chercher($modele);
		} else {
			if (getTypeSG($pListeElements) === '@Formule') {
				$listeElements = $pListeElements -> calculer();
			} else {
				$listeElements = $pListeElements;
			}
			if (getTypeSG($listeElements) !== '@Collection') {
				$listeElements = new SG_Collection();
			}
		}
		$nbModelesOperations = $listeElements -> Compter() -> toInteger();
		for ($i = 0; $i < $nbModelesOperations; $i++) {
			$modeleOperation = $listeElements -> elements[$i];
			$selected = '';

			if ($modeleOperation -> code === $this -> code) {
				$selected = ' selected="selected"';
			}
			$ret .= '<option value="' . $modeleOperation -> doc -> codeDocument . '"' . $selected . '>' . $modeleOperation -> getValeur('@Theme', '') . ' / ' . $modeleOperation -> toHTML() . '</option>';
		}

		$ret .= '</select>';

		return $ret;
	}

	/**1.0.7
	* Affecter un modèle d'opération à un utilisateur
	* @param $pUtilisateur utilisateur destinataire de l'opération
	* @return SG_Operation opération affectée
	*/
	function Affecter($pUtilisateur = '') {
		if ($pUtilisateur === '') {
			$pUtilisateur = SG_SynerGaia::IdentifiantConnexion();
		}

		$utilisateur = SG_Annuaire::getUtilisateur($pUtilisateur);
		if (!$utilisateur === false) { //	   $utilisateur = new SG_Utilisateur($pUtilisateur);

			// Fabrique une nouvelle opération
			$operation = SG_Operation::CreerDuModele($this -> code);
			$operation -> setValeur('@Responsable', $utilisateur -> identifiant);
			$operation -> Enregistrer();

			return $operation;
		} else {
			return new SG_Erreur('Cet utilisateur n\existe pas !');
		}
	}

	/**
	* Commence une nouvelle opération
	*
	* @return string code HTML pour la redirection
	*/
	function Commencer() {
		$url = SG_Navigation::URL_PRINCIPALE . '?' . SG_Navigation::URL_VARIABLE_MODELEOPERATION . '=' . $this -> code;
		return '<script>document.location.href="' . $url . '";</script>';
	}
	// 2.1 getValeurPropriete ; 2.1.1 vide si Texteriche vide
	public function Aide() {
		$ret = $this -> getValeurPropriete('@Description', '');
		if (is_object($ret) and $ret -> texte !== '' and $ret -> texte !== null) {
			$ret = $ret -> toHTML();
		} else {
			$ret = '';
		}
		return $ret;
	}
	/** 1.1 : ajout
	* texte de la formule
	*/
	function Formule() {
		return $this -> getValeur('@Phrase');
	}
	/** 1.1 ajout
	*/
	function postEnregistrer() {
		// remettre à jour les menus
		$ret = $_SESSION['@SynerGaia'] -> ViderCache('n');
	}
	/** 2.1 ajout ; 2.2 -> fonction ; 2.3 return compil
	* Prépare la compiation de la formule et met à jout le fichier .php
	*/
	function preEnregistrer() {
		// compiler avant de sauvegarder
		$formule = $this -> getValeur('@Phrase', '');
		$compil = new SG_Compilateur($formule);
		$tmp = $compil -> Traduire();
		if ($compil -> erreur !== '') {
			$ret = new SG_Erreur('0161', $this -> getValeur('@Code') . ' : ' . $compil -> erreur);
		} else {
			if ($compil -> php !== '' or $compil -> fonction !== '') {
				$this -> setValeur('@PHP', 'oui' );
			} else {
				$this -> setValeur('@PHP', '' );
			}
			$ret = $compil -> compilerOperation($this -> code, $this -> Formule(), $compil -> php);
		}
		return $ret;
	}
	/** 2.1 ajout
	* Afficher les propriétés dans un bon ordre
	**/
	function Afficher() {
		$ret = call_user_func_array (array('SG_Document', 'Afficher'), func_get_args());
		return $ret;
	}
	/** 2.1 ajout
	* Modifier les propriétés dans un bon ordre
	**/
	function Modifier() {
		$ret = call_user_func_array (array('SG_Document', 'Modifier'), func_get_args());
		return $ret;
	}
}
?>
