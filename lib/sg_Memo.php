<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de gestion d'un mémo (mail)
*/
// 2.3 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Memo_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Memo_trait.php';
} else {
	trait SG_Memo_trait{};
}
class SG_Memo extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Memo';
	public $typeSG = self::TYPESG;
	// Dernière erreur rencontrée
	public $Erreur = '';
	// Expéditeur du message
	public $expediteur = '';
	// Destinataires
	public $destinataires = array();
	// 2.3 ajout : Copie
	public $copie = array();
	// 2.3 ajout : Copie cachée
	public $bcc = array();
	// Objet du message
	public $objet = '';
	// Contenu du message
	public $contenu = '';

	/**
	* Construction de l'objet
	*/
	function __construct() {
	}
	/** 2.0 err 106
	* Ajouter un destinataire
	* @param indéfini $pQuelquechose destinataire
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

	/** 2.1 simplifier
	* Definir l'objet du message
	* @param indéfini $pQuelquechose objet du message
	*/
	function DefinirObjet($pQuelquechose = null) {
		$this -> objet = SG_Texte::getTexte($pQuelquechose);
		return $this;
	}

	/** 2.1 simplifier
	* Définir le contenu du message
	*
	* @param indéfini $pQuelquechose contenu du message
	*/
	function DefinirContenu($pQuelquechose = null) {
		$this -> contenu = SG_Texte::getTexte($pQuelquechose);
		return $this;
	}

	/** 2.1 simplifier
	* Ajouter au contenu du message
	*
	* @param indéfini $pQuelquechose contenu de l'ajout
	*/
	function AjouterContenu($pQuelquechose = null) {
		$this -> contenu .= SG_Texte::getTexte($pQuelquechose);
		return $this;
	}

	/** 2.0 err 105 ; 2.3 copie et bcc, correction implode
	* Envoyer le message
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
	/** 2.3
	* Ajouter un destinataire en copie
	* @param indéfini $pQuelquechose destinataire
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
	/** 2.3
	* Ajouter un destinataire en copie cachée
	* @param indéfini $pQuelquechose destinataire
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
