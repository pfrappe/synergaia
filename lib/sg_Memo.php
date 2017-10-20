<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Memo */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Memo_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Memo_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Memo_trait{};
}

/**
 * Classe SynerGaia de gestion d'un mémo (mail)
 * Nécessite que l'envoi de mail soit activé (envoi via php mail() )
 * @version 2.3
 */
class SG_Memo extends SG_Objet {
	/** string Type SynerGaia '@Memo' */
	const TYPESG = '@Memo';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	/** string Dernière erreur rencontrée */
	public $Erreur = '';
	/** string Expéditeur du message */
	public $expediteur = '';
	/** array Destinataires */
	public $destinataires = array();
	/** @var array Copie à 
	 * @since 2.3 ajout : */
	public $copie = array();
	/** @var array : Copie cachée
	 * @since 2.3 ajout */
	public $bcc = array();
	/** string Objet du message */
	public $objet = '';
	/** string Contenu du message */
	public $contenu = '';

	/**
	 * Construction de l'objet
	 */
	function __construct() {
	}

	/**
	 * Ajouter un destinataire
	 * @version 2.0 err 106
	 * @param indéfini $pQuelquechose destinataire
	 * @return SG_Memo $this
	 */
	function AjouterDestinataire($pQuelquechose = null) {
		$destinataire = '';
		$typeSG = getTypeSG($pQuelquechose);
		// Si on a une formule on l'exécute
		if ($typeSG === '@Formule') {
			$pQuelquechose = $pQuelquechose -> calculer();
			$typeSG = getTypeSG($pQuelquechose);
		}
		// Si on a un @Utilisateur, on prend le mail
		if ($typeSG === '@Utilisateur') {
			$destinataire = $pQuelquechose -> getIdentiteMail();
		} elseif ($typeSG === '@Email') {
			$destinataire = $pQuelquechose -> toString();
		} elseif ($typeSG === '@Erreur') {
			journaliser('@Memo.@AjouterDestinataire : erreur ' . $pQuelquechose -> toString());
			$this -> Erreur .= SG_Libelle::getLibelle('0106', true, $pQuelquechose -> toString());
		} else {
			// Sinon on prend la valeur telle quelle
			$destinataire = SG_Texte::getTexte($pQuelquechose);
		}
		if ($destinataire !== '') {
			$this -> destinataires[] = $destinataire;
		}
		return $this;
	}

	/**
	 * Definir l'objet du message
	 * 
	 * @version 2.1 simplifier
	 * @param string|SG_Texte|SG_Formule $pQuelquechose objet du message
	 * @return SG_Memo $this
	 */
	function DefinirObjet($pQuelquechose = null) {
		$this -> objet = SG_Texte::getTexte($pQuelquechose);
		return $this;
	}

	/** 2.1 simplifier
	* Définir le contenu du message
	*
	* @param string|SG_Texte|SG_Formule $pQuelquechose contenu du message
	*/
	function DefinirContenu($pQuelquechose = null) {
		$this -> contenu = SG_Texte::getTexte($pQuelquechose);
		return $this;
	}

	/** 2.1 simplifier
	 * Ajouter au contenu du message
	 *
	 * @param string|SG_Texte|SG_Formule $pQuelquechose contenu de l'ajout
	 * @return SG_Memo $this
	 */
	function AjouterContenu($pQuelquechose = null) {
		$this -> contenu.= SG_Texte::getTexte($pQuelquechose);
		return $this;
	}

	/**
	 * Envoyer le message
	 * @version 2.0 err 105
	 * @version 2.3 copie et bcc, correction implode
	 * @return SG_VraiFaux|SG_Erreur résultat
	 */
	function Envoyer() {
		$ret = new SG_VraiFaux(false);
		//conditionnement de l'envoi par la configuration
		$envoi = true;
		$config = SG_Config::getConfig('Mail_Envoi', 'oui');
		if ($config === 'oui') {
			// Récupération des informations sur le message
			$strExpediteur = $_SESSION['@Moi'] -> getIdentiteMail();
			if (getTypeSG($strExpediteur) !== 'string') {
				$strExpediteur = '';
			}
			$strDestinataires = implode(', ', $this -> destinataires);
			$strObjet = $this -> objet;
			$strContenu = wordwrap('<html>' . PHP_EOL . '<body>' . PHP_EOL . nl2br($this -> contenu) . PHP_EOL . '</body>' . PHP_EOL . '</html>', 70);

			// Définition des entetes
			$strHeaders = 'MIME-Version: 1.0' . "\r\n";
			$strHeaders .= 'Content-type: text/html; charset="utf-8"' . "\r\n";
			$strHeaders .= 'From: ' . $strExpediteur . "\r\n";
			$strHeaders .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
			// destinataires en copie
			$tmp = implode($this -> copie, ', ');
			if ($tmp !== '') {
				$strHeaders .= 'To: ' . $tmp . "\r\n";
			}
			// destinataires en copie cachée
			$tmp = implode($this -> bcc, ', ');
			if ($tmp !== '') {
				$strHeaders .= 'Bcc: ' . $tmp . "\r\n";
			}
			
			// Envoi du message
			$retBool = mail($strDestinataires, $strObjet, $strContenu, $strHeaders);
			$ret = new SG_VraiFaux($retBool);
		} else {			
			$this -> Erreur .= SG_Libelle::getLibelle('0105');
			journaliser($this -> Erreur);
		}
		return $ret;
	}

	/**
	 * Ajouter un destinataire en copie
	 * @since 2.3
	 * @param string|SG_Texte|SG_Formule $pQuelquechose destinataire
	 * @return SG_Memo $this
	 */
	function CopieA($pQuelquechose = null) {
		$copie = '';
		$typeSG = getTypeSG($pQuelquechose);
		// Si on a une formule on l'exécute
		if ($typeSG === '@Formule') {
			$pQuelquechose = $pQuelquechose -> calculer();
			$typeSG = getTypeSG($pQuelquechose);
		}
		// Si on a un @Utilisateur, on prend le mail
		if ($typeSG === '@Utilisateur') {
			$copie = $pQuelquechose -> getIdentiteMail();
		} elseif ($typeSG === '@Email') {
			$copie = $pQuelquechose -> toString();
		} elseif ($typeSG === '@Erreur') {
			journaliser('@Memo.@AjouterCopie : erreur ' . $pQuelquechose -> toString());
			$this -> Erreur .= SG_Libelle::getLibelle('0191', true, $pQuelquechose -> toString());
		} else {
			// Sinon on prend la valeur telle quelle
			$copie = SG_Texte::getTexte($pQuelquechose);
		}
		if ($copie !== '') {
			$this -> copie[] = $copie;
		}
		return $this;
	}

	/**
	 * Ajouter un destinataire en copie cachée
	 * 
	 * @since 2.3
	 * @param string|SG_Texte|SG_Formule $pQuelquechose destinataire
	 * @return SG_Memo $this
	 */
	function CopieCacheeA($pQuelquechose = null) {
		$copie = '';
		$typeSG = getTypeSG($pQuelquechose);
		// Si on a une formule on l'exécute
		if ($typeSG === '@Formule') {
			$pQuelquechose = $pQuelquechose -> calculer();
			$typeSG = getTypeSG($pQuelquechose);
		}
		// Si on a un @Utilisateur, on prend le mail
		if ($typeSG === '@Utilisateur') {
			$copie = $pQuelquechose -> getIdentiteMail();
		} elseif ($typeSG === '@Email') {
			$copie = $pQuelquechose -> toString();
		} elseif ($typeSG === '@Erreur') {
			journaliser('@Memo.@AjouterCopie : erreur ' . $pQuelquechose -> toString());
			$this -> Erreur .= SG_Libelle::getLibelle('0191', true, $pQuelquechose -> toString());
		} else {
			// Sinon on prend la valeur telle quelle
			$copie = SG_Texte::getTexte($pQuelquechose);
		}
		if ($copie !== '') {
			$this -> bcc[] = $copie;
		}
		return $this;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Memo_trait;
}
?>
