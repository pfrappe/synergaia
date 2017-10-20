<?php
/** SYNERGAIA fichier pour le traotement de l'objet @Application */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Application_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Application_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Application_trait{};
}

/**
* SG_Application : Classe décrivant une application SynerGaïa
* @since 2.3
* @version 2.4
*/
class SG_Application extends SG_Objet {
	/** string Type SynerGaia '@Application' */
	const TYPESG = '@Application';
	
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/**
	 * Construction de l'objet
	 * @since 2.3 ajout
	 * @param string $pCode code de l'application
	 */
	function __construct($pCode = null) {
		$this -> code = SG_Texte::getTexte($pCode);
	}
	
	/**
	 * Création d'une nouvellme application à partir d'une existante
	 * @todo à terminer
	static function Creer($pCode = '', $pPassword = '') {
		// le code de l'appli est-il déjà utilisé (répertoire, code de base - prefixe_synergaia_dictionnaire) ?
		// l'utilisateur en cours est-il administrateur ?
		// le mot de passe fourni est-il celui de l'administrateur en cours ?
		// on est bon pour la création :
		// recopier les fichiers et répertoires de l'application en cours vers la nouvelle
		// autoriser www-data:www-data sur les fichiers
		// modifier config.php pour enlever les références à l'appli actuelle
		// simuler par Internet la création de l'application
		// retourner le résultat sou forme d'objet @Application nouvelle
	}
	*/
	
	/**
	 * Pour exécuter un modèle d'opération dans une autre application et récolter le résultat dans la nôtre
	 * on suppose qu'il s'agit du même serveur (appel par localhost)
	 * 
	 * @since 2.3 ajout
	 * @param string|SG_Texte|SG_Formule $pOperation nom du modèle d'opération à exécuter
	 * @param string|SG_Texte|SG_Formule $pParm1 texte ou formule donnant un SG_Texte ou string : paramètre p1 facultatif pour le modèle d'opération
	 * @param string|SG_Texte|SG_Formule $pParm2 texte ou formule donnant un SG_Texte ou string : paramètre p2 facultatif pour le modèle d'opération
	 * @param string|SG_Texte|SG_Formule $pParm3 texte ou formule donnant un SG_Texte ou string : paramètre p3 facultatif pour le modèle d'opération
	 * @return SG_Object|SG_Erreur l'objet créé par la formule. Il doit exister dans l'application appelante
	 */
	function Executer ($pOperation = '', $pParm1 = '', $pParm2 = '', $pParm3 = '') {
		// url d'appel
		$operation = SG_Texte::getTexte($pOperation);
		$url = 'http://localhost/' . $this -> code . '/index.php?c=mop&m=' . $operation . '&s=o';//
		// préparation des paramètres
		$p = SG_Texte::getTexte($pParm1);
		if ($p !== '') {
			$url.= '&p1=' . urlencode($p);
		}
		$p = SG_Texte::getTexte($pParm2);
		if ($p !== '') {
			$url.= '&p2=' . urlencode($p);
		}
		$p = SG_Texte::getTexte($pParm3);
		if ($p !== '') {
			$url.= '&p3=' . urlencode($p);
		}
		// entête
		$sessionid = session_id();
		$header = "Accept: */*;\r\n";
		$header.= "Content-Type: application/x-www-form-urlencoded\r\n";
		// soit même session, soit par jeton
		if (isset($_COOKIE['AuthSession'])) {
			$header.= "Cookie:PHPSESSID=" . session_id();
			$header.= ';AuthSession='.$_COOKIE['AuthSession'] . ";\r\n";
		} else {
			$identifiant = $_SESSION['@Moi'] -> identifiant;
			$jeton = $_SESSION['@Moi'] -> Jeton();
			$url.= '&k=' . $jeton -> texte . '&u=' . $identifiant;
		}
		$options = array('http' => array('method' => 'POST', 'header' => $header . "Content-Length: 0\r\n", 'content' => ''));
		$contexte = stream_context_create($options);
		// exécution
		try {
			$ret = file_get_contents($url, false, $contexte);
			ini_set('memory_limit', '512M'); // TODO Supprimer ?
			$ret = unserialize($ret);
			ini_restore('memory_limit');
		} catch (Exception $e) {
			$msg = 'SG_Application [' . $this -> code . '] ligne ' . $e -> getLine() . ' : ' . $e -> getMessage();
			$ret = new SG_Erreur ('0204', $msg);
		}
		return $ret;
	}

	/**
	 * Pour exécuter une formule dans une autre application et récolter le résultat dans la nôtre
	 * on suppose qu'il s'agit du même serveur (appel par localhost)
	 * 
	 * @since 2.4 ajout
	 * @param  string|SG_Texte|SG_Formule $pOperation nom du modèle d'opération à exécuter
	 * @return SG_Objet|SG_Erreur l'objet créé par la formule. Il doit exister dans l'application appelante
	 **/
	function ExecuterJSON ($pOperation = '') {
		$operation = SG_Texte::getTexte($pOperation);
		$identifiant = $_SESSION['@Moi'] -> identifiant;
		$jeton = $_SESSION['@Moi'] -> Jeton();
		$URL = 'http://localhost/' . $this -> code . '/index.php?c=sub&m=' . $operation . '&s=o&k=' . $jeton -> texte . '&u=' . $identifiant;
		$sessionid = session_id();
		$header = "Accept: */*;\r\n";
		$header.= "Content-Type: application/x-www-form-urlencoded\r\n";
		if (false and isset($_COOKIE['AuthSession'])) {
			$header.= "Cookie:PHPSESSID=" . session_id();
			$header.= ';AuthSession='.$_COOKIE['AuthSession'] . ";\r\n";
		}
		$options = array('http' => array('method' => 'POST', 'header' => $header . "Content-Length: 0\r\n", 'content' => ''));
		$contexte = stream_context_create($options);
		try {
			$ret = file_get_contents($URL, false, $contexte);
			ini_set('memory_limit', '512M'); // TODO Supprimer ?
			$ret = unserialize($ret);
			ini_restore('memory_limit');
		} catch (Exception $e) {
			$msg = 'SG_Application [' . $this -> code . '] ligne ' . $e -> getLine() . ' : ' . $e -> getMessage();
			$ret = new SG_Erreur ('0205', $msg);
		}
		return $ret;
	}

	/**
	 * Importe un objet non système avec ses propriétés et/ou ses méthodes
	 * Rien n'est fait si une erreur est détectée
	 * 
	 * @since 2.3 ajout
	 * @param SG_Texte|SG_Formule $pNom : nom de l'objet
	 * @param SG_VraiFaux $pAvecProprietes : avec ses propriétés (vrai par défaut)
	 * @param SG_VraiFaux $pAvecMethodes : avec ses méthodes (vrai par défaut)
	 * @return SG_DictionnaireObjet : l'ojet créé ou SG_Erreur
	 */
	function ImporterObjet($pNom= '', $pAvecProprietes = true, $pAvecMethodes = true) {
		// todo vérifier que l'utilisateur est administrateur dans l'application visée
		$ret = new SG_VraiFaux(false);
		$nom = SG_Texte::getTexte($pNom);
		if ($nom !== '') {
			if (substr($nom,0,1) === '@') {
				$ret = new SG_Erreur('pas objets systèmes');
			} else {
				if (is_bool($pAvecProprietes)) {
					$avecp = $pAvecProprietes;
				} else {
					$avecp = SG_VraiFaux::getBooleen($pAvecProprietes);
				}
				if (is_bool($pAvecMethodes)) {
					$avecm = $pAvecMethodes;
				} else {
					$avecm = SG_VraiFaux::getBooleen($pAvecMethodes);
				}
			}
		} else {
			$ret = new SG_Erreur('pas de nom fourni');
		}
		if ($ret === false) {
			$ret = new SG_Erreur('rien créé');
		}
		return new SG_VraiFaux($ret);
	}

	/**
	 * pour exécuter une formule dans une autre application et récolter le résultat dans la nôtre
	 * on suppose qu'il s'agit du même serveur (appel par localhost) 
	 * Si on passe une @Formule, c'est le résultat @Texte de l'exécution de cette formule qui est passé pour être interprété et exécuté.
	 * Dans ce cas il peut être plus performant de créer un modèle dans l'application visée et d'en demander l'exécution avec un paramètre
	 * 
	 * @since 2.4 ajout
	 * @param string|SG_Texte|SG_Formule $pFormule : le texte de la formule à exécuter.
	 * @return SG_Objet l'objet créé par la formule. Il doit exister dans l'application appelante
	 */
	function ExecuterFormule ($pFormule = '') {
		$formule = SG_Texte::getTexte($pFormule);
		$identifiant = $_SESSION['@Moi'] -> identifiant;
		$jeton = $_SESSION['@Moi'] -> Jeton();
		$url = 'http://localhost/' . $this -> code . '/index.php?f=' . $formule . '&s=o&k=' . $jeton -> texte . '&u=' . $identifiant;
		$sessionid = session_id();
		$header = "Accept: */*;\r\n";
		$header.= "Content-Type: application/x-www-form-urlencoded\r\n";
		if (false and isset($_COOKIE['AuthSession'])) {
			$header.= "Cookie:PHPSESSID=" . session_id();
			$header.= ';AuthSession='.$_COOKIE['AuthSession'] . ";\r\n";
		}
		$options = array('http' => array('method' => 'POST', 'header' => $header . "Content-Length: 0\r\n", 'content' => ''));
		$contexte = stream_context_create($options);
		try {
			$ret = file_get_contents($url, false, $contexte);
			ini_set('memory_limit', '512M'); // TODO Supprimer ?
			$ret = unserialize($ret);
			ini_restore('memory_limit');
		} catch (Exception $e) {
			$msg = 'SG_Application [' . $this -> code . '] ligne ' . $e -> getLine() . ' : ' . $e -> getMessage();
			$ret = new SG_Erreur ($msg);
		}
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Application_trait;
}
?>
