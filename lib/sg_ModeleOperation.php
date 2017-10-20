<?php
/** QYNERGAIA fichier pour le traitement de l'objet @ModeleOperation */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_ModeleOperation : Classe de gestion d'un modele d'opération
 * @version 2.3
 */
class SG_ModeleOperation extends SG_Document {
	/** string Type SynerGaia '@ModeleOperation' */
	const TYPESG = '@ModeleOperation';
	
	/** string Type SynerGaia de l'objet */
	public $typeSG = self::TYPESG;

	/** string Code du modèle d'opération */
	public $code = '';

	/**
	 * Construction de l'objet
	 * @since 0.0
	 * @version 1.2 recherche code base
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

	/**
	 * initDocument : Termine l'initialisation spécifique du document
	 * @since 2.1 ajout
	 */
	function initDocument() {
		$this -> setValeur('@Type', '@ModeleOperation');
		$this -> code = $this -> getValeur('@Code');
	}

	/**
	 * Conversion en chaine de caractères
	 * @version 2.0 parm
	 * @param string $pDefaut
	 * @return string texte
	 */
	function toString($pDefaut = NULL) {
		return $this -> getValeur('@Titre', $this -> code);
	}

	/**
	 * Conversion en code HTML
	 * @version 2.0 parm
	 * @param string $pDefaut
	 * @return string code HTML
	 */
	function toHTML($pDefaut = NULL) {
		return $this -> toString();
	}

	/**
	 * Fabrication du lien vers une nouvelle opération avec ce modèle
	 * @version 1.3.0 supp 'htmlentities'
	 * @version 1.3.1 resume
	 * @version 1.3.2 parametre pour exécution en sg_get
	 * @version 1.3.3 parm event
	 * @version 2.0 effacer = true
	 * @version 2.6 retour SG_HTML
	 * @param boolean|SG_VraiFaux|SG_Formule  $pSGGet est-ce un lien en sgget ?
	 * @param string|SG_Texte|SG_Formule $pCible cible du lien
	 * @return SG_HTML code HTML du lien
	 * @uses SynerGaia.launchOperation()
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
		return new SG_HTML($ret);
	}

	/**
	 * Calcul du code html pour l'affichage comme champ
	 *
	 * @return string code HTML
	 */
	function afficherChamp() {
		return '<span class="champ_ModeleOperation">' . $this -> toHTML() . '</span>';
	}

	/**
	 * Calcul du code html pour la modification comme champ
	 * @since 1.0.6
	 * @version 1.3.1 param 2
	 * @version 2.1 correction code document
	 * @param string $pRefChamp référence du champ HTML
 	 * @param null|SG_Collection|SG_Formule $pListeElements liste des valeurs possibles (par défaut toutes)
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

	/**
	 * Affecter à un utilisateur une nouvelle opération de mon modèle
	 * Si pas de paramètre, m'affecter moi
	 * ATTENTION L'enregistrement de la nouvelle opération est fait
	 * 
	 * @since 1.0.7
	 * @version 2.6 $ret, n° erreur 0282
	 * @param string|SG_Texte|SG_Formule $pUtilisateur utilisateur destinataire de l'opération (par défaut, moi)
	 * @return SG_Operation|SG_Erreur opération affectée ou erreur
	 */
	function Affecter($pUtilisateur = '') {
		if ($pUtilisateur === '') {
			$pUtilisateur = SG_SynerGaia::IdentifiantConnexion();
		}

		$utilisateur = SG_Annuaire::getUtilisateur($pUtilisateur);
		if ($utilisateur instanceof SG_Utilisateur) {
			// Fabrique une nouvelle opération
			$operation = SG_Operation::CreerDuModele($this -> code);
			$operation -> setValeur('@Responsable', $utilisateur -> identifiant);
			$operation -> Enregistrer();
			$ret = $operation;
		} elseif ($utilisateur === false) {
			// inconnu
			$ret = new SG_Erreur('0282', SG_Texte::getTexte($pUtilisateur));
		} else {
			$ret = $utilisateur;
		}
		return $ret;
	}

	/**
	 * Commence une nouvelle opération de mon modèle
	 *
	 * @return string code HTML pour la redirection
	 */
	function Commencer() {
		$url = SG_Navigation::URL_PRINCIPALE . '?' . SG_Navigation::URL_VARIABLE_MODELEOPERATION . '=' . $this -> code;
		return '<script>document.location.href="' . $url . '";</script>';
	}

	/**
	 * Texte de l'aide associée au modèle
	 * 
	 * @version 2.1 getValeurPropriete
	 * @version 2.1.1 vide si Texteriche vide
	 * @return string|SG_HTML
	 */
	public function Aide() {
		$ret = $this -> getValeurPropriete('@Description', '');
		if (is_object($ret) and $ret -> texte !== '' and $ret -> texte !== null) {
			$ret = $ret -> Afficher();
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * Texte de la formule
	 * 
	 * @since 1.1 : ajout
	 * @return string la phrase de la formule
	 */
	function Formule() {
		return $this -> getValeur('@Phrase');
	}

	/**
	 * Traitement après enregistrement : vide le cache navigation pour forcer sa mise à jour
	 * @since 1.1 ajout
	 */
	function postEnregistrer() {
		// remettre à jour les menus
		$ret = $_SESSION['@SynerGaia'] -> ViderCache('n');
	}

	/**
	 * Prépare la compilation de la formule et met à jout le fichier .php
	 * @since 2.1 ajout
	 * @version 2.2 -> fonction
	 * @version 2.3 return compil
	 * @return boolean Compilation ok ?
	 */
	function preEnregistrer() {
		// terminer l'initialisation
		if (is_null($this -> code) or $this -> code === '') {
			$this -> code = $this -> getValeur('@Code','');
		}
		// compiler avant de sauvegarder
		$formule = $this -> getValeur('@Phrase', '');
		$compil = new SG_Compilateur($formule);
		$compil -> titre = 'Modèle d\'opération : ' . $this -> toString();
		$tmp = $compil -> Traduire();
		// si pas d'erreur, créer la classe du modèle d'opération
		if ($compil -> erreur !== '') {
			$ret = $compil -> erreur;
			$ret -> gravite = SG_Erreur::ERREUR_CTRL;
			SG_Pilote::OperationEnCours() -> erreurs[] = $compil -> erreur;
		} else {
			if ($compil -> php !== '') {
				$this -> setValeur('@PHP', 'oui' );
			} else {
				$this -> setValeur('@PHP', '' );
			}
			$ret = $compil -> compilerOperation($this -> code, $this -> Formule(), $compil -> php);
			if (getTypeSG($ret) === '@Erreur') {
				SG_Pilote::OperationEnCours() -> erreurs[] = $ret;
			} elseif ($compil -> erreur !== '') {
				SG_Pilote::OperationEnCours() -> erreurs[] = $compil -> erreur;
			}
		}
		return $ret;
	}

	/**
	 * Afficher les propriétés dans un bon ordre
	 * @since 2.1 ajout
	 * @param SG_Formule la liste des champs de l'affichage
	 * @return SG_HTML le code html calculé
	 */
	function Afficher() {
		$ret = call_user_func_array (array('SG_Document', 'Afficher'), func_get_args());
		return $ret;
	}

	/**
	 * Calcule le code html pour modifier les propriétés dans un bon ordre
	 * @since 2.1 ajout
	 * @param SG_Formule la liste des champs de l'affichage
	 * @return SG_HTML le code html calculé
	 */
	function Modifier() {
		$ret = call_user_func_array (array('SG_Document', 'Modifier'), func_get_args());
		return $ret;
	}
}
?>
