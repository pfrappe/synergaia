<?php
/** SYNERGAIA Fichier contenant la gestion de l'objet @Bouton */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Bouton_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Bouton_trait.php';
} else {
	/**
	 * trait pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur (vide par défaut)
	 * @since 2.4
	 */
	trait SG_Bouton_trait{};
}

/**
 * Classe de gestion d'un bouton à afficher
 * @since 1.1 permet aussi l'affichage d'une fenêtre modale
 * @version 2.6
 */
class SG_Bouton extends SG_Objet {
	/** string Type SynerGaia '@Bouton' */
	const TYPESG = '@Bouton';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** @var string Libellé du bouton */
	public $nom;

	/** string Formule à exécuter au clic (@Formule) */
	public $formule = '';
	
	/** Objet associé au bouton, sur lequel portera la formule
	 * @var SG_Objet 
	 */
	public $objet;
	
	/** @var string|null si "m" : indique qu'il s'agit d'un modèle d'opération à lancer
	* @since 2.3
	*/
	public $type;

	/** @var string Code de l'étape de l'opération correspondante */
	public $code = '';

	/** @var string référence du document principal concerné */
	public $refdocument = '';
	
	/** @var array paramètres
	* @since 2.1.1
	*/
	public $parms;
	
	/**
	 * @since 2.5
	 * @var string classe css en plus de sg-bouton */
	public $classe;
	/**
	 * @since 2.5
	 * @var string image de fond */
	public $image;
	/**
	 * @since 2.5
	 * @var string phrase de javascript à exécuter sur le navigateur */
	public $script;

	/**
	 * @since 2.6
	 * @var SG_VraiFaux::VALEURVRAI indique si le bouton est activé. Vaut vrai par défaut. Testé et mis à jour par ActiverSi()
	 */
	private $actif = SG_VraiFaux::VRAIFAUX_VRAI;
	
	/**
	 * @since 2.6
	 * @var string message en bouton conditionné
	 */
	private $message;

	/**
	 * Construction de l'objet au cours de l'éxécution de la formule de la branche
	 * la formule a été compilée au moment de l'enregistrement de la phrase complète.
	 * La création de l'objet bouton sert à récupérer l'identifiant de la branche à exécuter
	 * 
	 * @since 1.1
	 * @version 2.4 formule SG_Formule
	 * @version 2.6 $pCode
	 * @param string|SG_Texte|SG_Formule $pNom nom du bouton
	 * @param string|SG_Texte|SG_Formule $pFormule formule à exécuter ou code du modèle d'opération à lancer
	 * @param string|SG_Texte|SG_Formule $pCode code interne du bouton si on veut le forcer
	 * @param string|SG_Texte|SG_Formule $pType indique qu'on veut lancer un nouveau modèle d'opération. Par défaut null, sinon "m"
	 */
	function __construct($pNom = '', $pFormule = '', $pCode = '', $pType = null) {
		//préparation des paramètres
		$this -> nom = SG_Texte::getTexte($pNom);
		// la formule est juste stockée : elle sera exécutée en cliquant sur le bouton
		if ($pFormule instanceof SG_Formule) {
			$this -> formule = $pFormule;
			if ($pFormule -> texte !== NULL and $pFormule -> texte !== '') {
				$code = sha1($pFormule -> texte);
			} else {
				$code = sha1($pFormule -> fonction . '#' . $pFormule -> methode);
			}
		} else {
			$this -> formule = new SG_Formule(SG_Texte::getTexte($pFormule));
			$code = sha1($this -> formule -> texte);
		}
		$this -> code = $code;
		// si on a fourni un code, c'est sous ce code que sera stocké le bouton plutot que le sha1 de la formule
		if ($pCode !== '') {
			$code = SG_Texte::getTexte($pCode);
			if ($code !== '' and !$code instanceof SG_Erreur) {
				$this -> code = $code;
			}
		}
		$this -> formule -> titre = $this -> nom;
		// code de bouton et d'étape
		$operation = SG_Pilote::OperationEnCours();
		if (!isset($operation -> boutons[$code])) {
			$operation -> boutons[$code] = $this -> formule;
		}
		if (func_num_args() > 2) {
			$this -> parms = array();
			for ($i = 2;$i < func_num_args();$i++) {
				$p = func_get_arg($i);
				if (is_object($p)) {
					$p = $p -> toString();
				}
				$this -> parms['p' . ($i - 1)] = $p;
			}
		}
	}

	/** 
	 * Conversion du @Bouton en chaine de caractères
	 * @since 1.0
	 * @version 2.4 -> texte
	 * @return string texte de la formule
	 */
	function toString() {
		return $this -> formule -> texte;
	}

	/**
	 * Retourne l'url d'appel de l'exécution du bouton
	 * @param string : type d'appel (par défaut variable bouton b=code)
	 * @return string
	 **/
	function url($pTypeAppel = SG_Navigation::URL_VARIABLE_ETAPE) {
		$operation = SG_Pilote::OperationEnCours();
		$url = SG_Navigation::URL_VARIABLE_OPERATION . '=' . $operation -> reference;
		
		if ($this -> code !== '') {
			$url .= '&' . $pTypeAppel . '=' . $this -> code;
		}
		if ($this -> refdocument !== '') {
			$url .= '&' . SG_Navigation::URL_VARIABLE_DOCUMENT . '=' . $this -> refdocument;
		}
		if (is_array($this -> parms)) {
			foreach ($this -> parms as $key => $elt) {
				$url.= '&' . $elt;
			}
		}
		return $url;
	}

	/** 
	 * Conversion en code HTML
	 * @version 2.6 Test activation
	 * @param SG_Texte : nom de classe css
	 * @return SG_HTML code HTML
	 * @uses SynerGaia.submit()
	 */
	function toHTML($pClasse = '') {
		$classe = SG_Texte::getTexte($pClasse);
		if ($classe === '') {
			$classe = 'sg-bouton';
		}
		$classe.= ' ' . $this -> classe;
		if ($this -> actif === SG_VraiFaux::VRAIFAUX_FAUX) {
			$classe.= ' sg-bouton-inactif';
			$submit = '';
		} elseif ($this -> actif === SG_VraiFaux::VRAIFAUX_INDEF) {
			$classe.= ' sg-bouton-gris';
			if (is_null($this -> message)) {
				$message = SG_Libelle::getLibelle('0279');
			} else {
				$message = $this -> message;
			}
			$submit = 'SynerGaia.submit(event, null,\'index.php?' . $this -> url() . '\', \'' . htmlentities(addslashes($message)) . '\')';
		} else {
			$submit = 'SynerGaia.submit(event, null,\'index.php?' . $this -> url() . '\')';
		}
		$ret = '<button type="button" onClick="' . $submit . '" class="' . $classe . '">' . $this -> nom . '</button>';
		return new SG_HTML($ret);
	}

	/**
	 * Génère l'affichage du bouton
	 * @since 2.4 ajout du paramètre $pClasse
	 * @param SG_Texte $pClasse nom de classe css
	 * @return SG_HTML code HTML de l'affichage du bouton
	 */ 
	function Afficher($pClasse = '') {
		$ret = $this -> toHTML($pClasse);
		return $ret;
	}

	/** 
	 * Indique que le résultat doit s'ouvrir dans une nouvelle fenêtre modale
	 * @since 1.1
	 * @version 2.0 return SG_HTML
	 * @return SG_HTML
	 * @uses SynerGaia.adroite()
	 */
	function Fenetre() {
		$ret = '<div class="modal hide fade" id="' . $this -> code . '-window">';
		$ret .= '<div class="modal-header"> <a class="close" data-dismiss="modal"><img src="../' . SG_Navigation::URL_THEMES . 'default/img/icons/16x16/silkicons/cancel.png"</a>';
		$ret .= '<h3>' . $this -> nom . '</h3>';
		$ret .= '</div><div class="modal-body"></div><div class="modal-footer"> <a class="btn btn-info" data-dismiss="modal">Fermer</a></div></div>' . PHP_EOL;
		$ret .= '<span onclick="SynerGaia.adroite(' . $this -> url(SG_Navigation::URL_VARIABLE_CODE) . ')" data-target="#' . $this -> code . '-window">' . $this -> nom . '</a>';
		return new SG_HTML($ret);
	}

	/**
	 * Affiche le bouton dans le cadre 'droite' du navigateur SynerGaïa
	 * @since 1.1 added
	 * @version 2.0 returns SG_HTML
	 * @version 2.6 correction
	 * @return SG_HTML
	 */
	function ADroite() {
		$ret = $this -> Afficher() -> ADroite();
		return new SG_HTML($ret);
	}

	/**
	 * Place le bouton dans la fenêtre gauche du navigateur SynerGaïa
	 * @since 1.3.3 added
	 * @version 2.0 returns SG_HTML
	 * @version 2.6 correction
	 * @return SG_HTML
	 */
	function AGauche() {
		$ret = $this -> Afficher() -> AGauche();
		return $ret;
	}

	/** 
	 * Ajoute une classe pour un effet de décoration. L'action se fait sur l'objet @HTML lui-même
	 * @since 1.3.1 ajout de la méthode
	 * @param string|SG_Texte $pClasse classe d'effet à ajouter
	 * @param string|SG_Texte $pImage url de l'image de fond du bouton
	 * @return SG_Bouton l'objet après modification
	 **/
	function Effet($pClasse = '', $pImage = '') {
		$this -> classe = SG_Texte::getTexte($pClasse);
		$this -> image = SG_Texte::getTexte($pImage);
		return $this;
	}

	/**
	 * Active le bouton selon le paramètre :
	 * - @Vrai : activé nomalement (valeur par défaut)
	 * - @Faux : grisé inactif (pas d'action)
	 * - @Indefini : grisé orange (action contrôlé par une alerte au clic)
	 * Si pas de paramètre, retourne la valeur en cours
	 * @since 2.6
	 * @param null|SG_VraiFaux|SG_Formule $pCondition Condition de l'activation
	 * @param null|string|SG_Texte|SG_Formule $pMessage message à afficher si condition indéfinie
	 * @return SG_Bouton $this
	 */
	function ActiverSi($pCondition = null, $pMessage = null) {
		if (! is_null($pMessage)) {
			$this -> message = SG_Texte::getTexte($pMessage);
		}
		if (is_null($pCondition)) {
			$ret = new SG_VraiFaux($this -> actif);
		} else {
			$cond = new SG_VraiFaux($pCondition);
			$this -> actif = $cond -> valeur;
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * Force la continuation de l'opération en exécutant la formule du bouton
	 * C'est à dire l'exécution de la branche correspondante
	 * 
	 * @since 2.6
	 * @param 
	 */
	function Executer($pCode = '') {
		$op = SG_Pilote::OperationEnCours();
		$code = SG_Texte::getTexte($pCode);
		if ($code instanceof SG_Erreur) {
			$ret = $code;
		} else {
			if (!is_string($code) or $code === '') {
				$code = '';
				if (isset($op -> boutons[$this -> code])) {
					$code = $op -> boutons[$this -> code] -> code;
				}
			}
			if ($code == '') {
				$ret = new SG_Erreur('9999');
			} else {
				$ret = $op -> $code($op, $op -> Principal());
			}
		}
		return $ret;
	}

	/** complément de classe créée par compilation
	 * @since 2.4. 
	 */
	use SG_Bouton_trait;
}
?>
