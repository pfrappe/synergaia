<?php
/** SynerGaia fichier pour le traitement de l'objet @Formule */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_Formule : Classe de gestion et d'exécution d'une formule SynerGaia
 * @version 2.5
 */
class SG_Formule extends SG_Objet {
	/** string Type SynerGaia '@Formule' */
	const TYPESG = '@Formule';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Texte de la formule SynerGaia */
	public $texte = '';
	/** string 2.1 traduction php */
	public $php = '';
	/** string 2.1 titre du résultat de la formule (pour les affichages) */
	public $titre = null;
	/** string 2.1 méthode du dernier résultat */
	public $methode = '';
	/** string 2.2 fonction de l'opération à exécuter */
	public $fonction = '';
	/** array 2.3 contexte de la formule (tableau des paramètres de la formule de départ) */
	public $contexte = array();

	/** SG_Objet Objet sur lequel appliquer la formule */
	public $objet;
	/** SG_Objet Objet "principal", lié à une formule parente */
	public $objetPrincipal;
	/** SG_Formule complète appelante de plus haut niveau pour la gestion des variables temporaires */
	public $formuleparent;
	/** SG_Operation source de la formule (pour trouver le php des fonctions appelées) */
	public $operation = null;

	/** array Liste des variables (seulement si je suis la formule apelante) */
	public $variables = array();
	/** array Liste des erreurs relevées (inutilisé) */
	public $erreurs = array();
	/** boolean 1.0.7 (inutilisé) permet d'activer un debug pour une seule formule */
	public $debug = false;

	/**
	 * __construct : Construction de l'objet
	 * 
	 * @since 1.0.7
	 * @version 2.1 suppr id , setParent
	 * @param string $pFormule texte de la formule SynerGaia
	 * @param indéfini $pObjet objet sur lequel appliquer la formule
	 * @param indéfini $pObjetPrincipal objet sur lequel appliquer la formule
	 * @param indéfini $pParent formule (ou opération) portant le texte de la formule d'origine et où sont stockées les variables
	 * @param indéfini $pParametres tableau des id des valeurs de paramètres complémentaires
	 */
	function __construct($pFormule = null, $pObjet = null, $pObjetPrincipal = null, $pParent = null, $pParametres = null) {
		$this -> setFormule($pFormule);
		
		if(is_array($pObjet)) {
			$this -> proprietes = $pObjet;
		} else {
			$this -> objet = $pObjet;
		}					

		if ($pObjetPrincipal !== null) {
			$this -> objetPrincipal = $pObjetPrincipal;
		} else {
			$this -> objetPrincipal = $this -> objet;
		}
		
		if ($pParametres != null) {
			// s'il y a des paramètres, c'est qu'on est une formule parente de plus haut niveau.
			foreach($pParametres as $i => $parametre) {
				if (getTypeSG($parametre) === '@Formule') {
					$this -> proprietes['$' . ($i + 1)] = $parametre -> calculer();
				} else {
					$this -> proprietes['$' . ($i + 1)] = $parametre;
				}
			}
		}
		$this -> setParent($pParent);
	}

	/**
	 * initialise correctement la formule en calculant les blocs
	 * @since 1.0.7
	 * @version 2.6 getTexte, return
	 * @param string|SG_Texte|SG_Formule $pFormule
	 * @return $this
	 */
	function setFormule($pFormule) {
		if ($pFormule !== null) {
			$this -> texte = SG_Texte::getTexte($pFormule);
		}
		return $this;
	}

	/**
	 * toString : la formule en texte
	 * 
	 * @since 1.0.7
	 * @return string
	 */
	public function toString() {
		return $this -> texte;
	}

	/**
	 * Calcule / Interprète une formule complete
	 * 
	 * @since 1.1
	 * @version 1.3.1 utilisation de separerCollection, test sur nom de propriété affectée
	 * @version 2.3 tient compte de fonction et contexte ; test @erreur, err 0198
	 * @param string $pEtape code de l'étape à traiter
	 * @return SG_Objet|SG_Erreur résultat du calcul ou SG_Erreur
	 */
	function calculer($pEtape = '') {
		if ($this -> fonction !== '') {
			// 2.2 à partir de fonction
			$objet = $this -> operation;
			if (! method_exists($objet, $this -> fonction)) {
				$ret = new SG_Erreur('0198', get_class($objet) . '.' . $this -> fonction);
			} else {
				$ret = call_user_func_array(array($objet, $this -> fonction) , array($this -> objet, $this -> contexte));
			}
		} elseif (! is_null($this -> php) and $this -> php !== '') {
			// 2.1 calcul à partir de php
			$ret = false;
			$objet = $this -> objet;
			if (is_string($objet)) {
				$objet = new SG_Texte($objet);
			}
			$rien = new SG_Rien();
			$operation = $this -> operation;
			$etape = $pEtape;
			$resultat = array();
			try {
				eval($this -> php);
			} catch (Exception $e) {
				$ret = new SG_Erreur('0255', $this -> php . ' : ' . $e -> getMessage());
			}
		} else {
			// faire la traduction à la volée
			$nom = sha1($this -> texte);
			$classe = 'FO_' . $nom;
			$ok = true;
			if (SG_Autoloader::verifier($classe) === false) {
				$ok = false;
				$compil = new SG_Compilateur();
				$compil -> titre = 'Formule : ' . $this -> toString();
				$ret = $compil -> Traduire($this -> texte);
				if (! $compil -> erreur instanceof SG_Erreur) {
					$ret = $compil -> compilerOperation($nom, $this -> texte, $compil -> php, 'FO_');
					if (! ($ret === false or $ret instanceof SG_Erreur)) {
						$ok = true;
					}
				} else {					
					SG_Pilote::OperationEnCours() -> erreurs[] = $compil -> erreur;
				}
			}
			if ($ok === true ) {
				$formule = new $classe();
				$formule -> php = 'oui';
				$formule -> objet = $this -> objet;
				$ret = $formule -> traiterSpecifique($pEtape, 'f');
			}
		}
		return $ret;
	}
	
	/**
	 * Teste si une propriété existe
	 * 
	 * @since 1.0.4
	 * @param string $pNom
	 * return boolean
	 */
	function isProprieteExiste($pNom = '') {
		$nom = SG_Texte::getTexte($pNom);
		$ret = false;
		if (isset($this -> getFormuleOrigine() -> proprietes[$nom])) {
			$ret = true;
		}
		return $ret;
	}

	/**
	 * Récupère la valeur d'une propriété de la formule
	 * 
	 * @since 1.0.4
	 * @version 2.0 parm2
	 * @param string|SG_Texte|SG_Formule $pNom
	 * @param SG_Objet $pValeurDefaut valeur par défaut
	 */
	function getValeur($pNom = '', $pValeurDefaut = null) {
		$nom = SG_Texte::getTexte($pNom);
		$ret = null;
		if ($this -> isProprieteExiste($nom)) {
			$ret = $this -> getFormuleOrigine() -> proprietes[$nom];
		}
		return $ret;
	}

	/**
	 * Met une valeur de propriété
	 * 
	 * @since 1.0.4
	 * @version 2.6 SG_Formule $this
	 * @param string|SG_Texte|SG_Formule $pNom
	 * @param SG_Objet $pValeur valeur à mettre
	 * @return SG_Formule $this
	 */
	function setValeur($pNom = '', $pValeur) {
		$nom = SG_Texte::getTexte($pNom);
		$this -> getFormuleOrigine() -> proprietes[$nom] = $pValeur;
		return $this;
	}

	/**
	 * Recherche la formule a plus haute ayant généré l'appel à la formule actuelle
	 * 
	 * @since 1.0.4
	 * return SG_Formule
	 */
	function getFormuleOrigine() {
		if ($this -> formuleparent === null) {
			$ret = $this;
		} else {
			$ret = $this -> formuleparent -> getFormuleOrigine();
		}
		return $ret;
	}

	/**
	 * Execute une formule, sans contexte particulier
/// ATTENTION ne pas mettre de tracer() dans cette fonction : plantage
	 * 
	 * @since 1.1 ajout (anciennement fonction formule dans socle.php)
	 * @param string $pFormule formule à exécuter
	 * @param indéfini $pObjet objet sur lequel appliquer la formule
	 * @param indefini $pObjetPrincipal : principal de la formule si différent de l'objer
	 * @param indefini $pParent formule parente si nécessaire
	 * @param array $pParametres liste de paramètres
	 *
	 * @return indéfini retour de la formule
	 */
	static function executer($pFormule = '', $pObjet = null, $pObjetPrincipal = null, $pParent = null, $pParametres = null) {
		$tmpFormule = new SG_Formule($pFormule, $pObjet, $pObjetPrincipal, $pParent, $pParametres);
		$ret = $tmpFormule -> calculer();
		return $ret;
	}

	/**
	 * Calcule la formule en l'appliquant sur l'objet passé en paramètre
	 * 
	 * @since 1.3 ajout pour amélioration des performances
	 * @version 2.5 param3 pour les boutons
	 * @param SG_Objet $pObjet objet sur lequel s'appliquera la formule
	 * @param SG_Objet $pObjetPrincipal objet principal si différent de $pObjet
	 * @param 
	 */
	function calculerSur($pObjet = null, $pObjetPrincipal = null) {
		if(is_array($pObjet)) {
			$this -> proprietes = $pObjet;
		} else {
			$this -> objet = $pObjet;
		}
		if ($pObjetPrincipal !== null) {
			$this -> objetPrincipal = $pObjetPrincipal;
		} else {
			$this -> objetPrincipal = $this -> objet;
		}
		$ret = $this -> calculer();
		return $ret;
	}
	
	/**
	 * Calcul le code html pour l'affichage de la formule (sans l'exécuter)
	 * 
	 * @since 2.1 ajout
	 * @param any SG_Texte|SG_Formule liste de paramètres à afficher
	 * @return SG_HTML : le texte de la formule et éventuellement du php
	 */
	function Afficher() {
		$ret = '<p><i>' . $this -> texte . '</i>';
		if ($this -> php !== '') {
			$ret.= '<br><pre>' . $this -> php . '</pre>'; 
		} elseif ($this -> fonction !== '') {
			$ret.= '<br><pre>' . $this -> fonction . '</pre>'; 
		}
		$ret.= '</p>';
		return new SG_HTML($ret);
	}

	/**
	 * Initialise la formule parente
	 *
	 * @since 2.1 ajout
	 * @param SG_Formule $pParent : sg_formule parente
	 */
	function setParent($pParent) {
		if (getTypeSG($pParent) === '@Formule') {
			$this -> formuleparent = $pParent;
			$this -> operation = $pParent -> operation;
		}
	}

	/**
	 * Appelle et exécute la fonction passée en paramètre au niveau de la formule parente
	 * 
	 * @since 2.1 ajout
	 * @param string $pFonction fonction appelée
	 * @param array $pArgs tableau des arguments
	 * @return SG_Objet le résultat de l'exécution
	 */
	function callParent ($pFonction, $pArgs) {
		$ret = call_user_func_array(array(parent, $pFonction), $pArgs);
		return $ret;
	}

	/**
	 * Préparation d'une formule avant son traitement, généralement comme paramètre d'une étape compilée
	 * 
	 * @since 2.6
	 * @return SG_Formule mise à jour
	 * @param string $pNo
	 * @param string $pMethode
	 * @param SG_Operation|SG_Formule formule, objet ou opération appelante
	 * @param SG_Objet $pObjet
	 * @param array $pContexte paramètres de la formule appelante
	 * @return SG_Formule
	 */
	static function preparer($pNo, $pMethode, $pAppelant, $pObjet = null, $pContexte = null) {
		$ret = new SG_Formule();
		$ret -> fonction = 'fn' . $pNo;
		$ret -> methode = '.' . $pMethode;
		if ($pObjet !== null) {
			$ret -> objetPrincipal = $pObjet;
			$ret -> objet = $pObjet;
		}
		$ret -> operation = $pAppelant;
		$ret -> setParent($pAppelant);
		$ret -> contexte = $pContexte;
		return $ret;
	}
}
?>
