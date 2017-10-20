<?php
/** SynerGaia fichier poure la gestion de l'objet @Erreur */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Erreur_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Erreur_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Erreur_trait{};
}

/**
* Classe SynerGaia de traitement des erreurs
* @version 2.4
*/
class SG_Erreur extends SG_Objet {
	/** string Type SynerGaia '@Erreur' */
	const TYPESG = '@Erreur';
	/** string niveau de gravité */
	const ERREUR_INFO = '1';
	/** string niveau de gravité */
	const ERREUR_CTRL = '2';
	/** string niveau de gravité */
	const ERREUR_EXEC = '3';
	/** string niveau de gravité */
	const ERREUR_STOP = '4';
	/** string niveau de gravité */
	const ERREUR_CRSH = '5';
	/** string Message d'erreur "erreur inconnue" */
	const MESSAGE_ERREUR_INCONNUE = '0000';
	/** string Code d'erreur : méthode non trouvée */
	const ERR_DICO_ELEMENT_INTROUVABLE = '0002';
	/** string Code d'erreur : le fichier n'est pas un JSON valide (import) */
	const ERR_FICHIER_JSON_INVALIDE = '0005';
	
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Code de l'erreur */
	public $code = '0000';
	/** string Informations sur l'erreur */
	public $infos;
	/** string Informations de trace */
	public $trace;
	/** string Gravité de l'erreur (0 pas d'erreur, 1 warning, 2 erreur dans un contrôle, 3 : erreur dans l'étape, 4 : erreur grave arrêt, 5 erreur générale)
	 * @since 2.5 ajout 
	 */
	public $gravite = '0';
	


	/**
	 * Construction de l'objet
	 * 
	 * @version 1.1 code erreur string
	 * @param integer $pCodeErreur code de l'erreur
	 * @param string $pInfos informations complémentaires
	 * @param string $pGravite niveau de gravité de l'erreur
	 *	  */
	public function __construct($pCodeErreur = '0000', $pInfos = '', $pGravite = '') {
		$this -> code = SG_Texte::getTexte($pCodeErreur);
		$this -> infos = SG_Texte::getTexte($pInfos);
		$this -> gravite = SG_Texte::getTexte($pGravite);
	}

	/**
	 * Conversion en chaine de caractères
	 *
	 * @return string texte
	 */
	function toString() {
		return $this -> getMessage();
	}

	/**
	 * Calcule le code HTML de l'affichage (avec une url vers la documentation)
	 * 
	 * @since 1.1
	 * @version 2.4 SG_HTML + url vers la documentation
	 * @return SG_HTML html texte rouge
	 */
	function toHTML() {
		$msg = $this -> getMessage(true);
		if (!is_null($this -> trace) and $this -> trace !== '') {
			$msg.= '<br><span class="sg-erreur-trace">' . $this -> trace . '</span>';
		}
		$css = 'sg-erreur-grav-' . $this -> gravite;
		return new SG_HTML('<div class="sg-erreur sg-erreur-grav-' . $this -> gravite . '">' . $msg . '</div>');
	}

	/**
	 * Message d'erreur
	 * 
	 * @version 2.4 url et classes
	 * @param string $pURLDocum url vers la documentation si pas standard
	 * @return string html message de l'erreur
	 */
	public function getMessage($pURLDocum = false) {
		if ($this -> code === '') {
			$msg = $this -> infos;
		} else {
			$msg = SG_Libelle::getLibelle($this -> code, false, $this -> infos);
		}
		if ($pURLDocum === true and $this -> code !== '') {
			$url = 'http://docum.synergaia.eu/index.php?m=DocumentConsulter&p1=Document&p2=erreur'. $this -> code;
			$ret = ' <a class="sg-erreur-url" href="'. $url . ' " target="blank">' . $this -> code . '</a> ' . $msg;
		} else {
			$ret = $msg;
		}
		return $ret;
	}

	/**
	 * Retourne le titre de l'erreur
	 * 
	 * @since 1.2 ajout
	 * @return SG_Texte
	 */
	function Titre() {
		return new SG_Texte($this -> getMessage());
	}

	/**
	 * Affiche l'erreur sur le navigateur
	 * 
	 * @since 1.3.1 ajout
	 * @return SG_HTML code html
	 */
	function afficherChamp() {
		return $this -> toHTML();
	}

	/**
	 * pour tous les objets : false sauf SG_Erreur et dérivés
	 * 
	 * @since 1.3.4 ajout
	 * @version 2.4 retourne true
	 * @return boolean true
	 **/
	function estErreur() {
		return true;
	}

	/**
	 * Retourne la valeur du code de l'erreur
	 * 
	 * @since 1.2 ajout
	 * @return SG_Texte
	 */
	function Code() {
		return new SG_Texte($this -> code);
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Erreur_trait;
}
?>
