<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* SG_Application : Classe décrivant une application SynerGaïa
*/
// Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Application_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Application_trait.php';
} else {
	trait SG_Application_trait{};
}
class SG_Application extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@Application';
	public $typeSG = self::TYPESG;
	
	// code de l'application (sans le tiret bas)

	/** 2.3 ajout
	* Construction de l'objet
	* @param string $pCode code de l'application
	*/
	function __construct($pCode = null) {
		$this -> code = SG_Texte::getTexte($pCode);
	}
	// TODO
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
	/** 2.3 ajout
	* pour exécuter une formule dans une autre application et récolter le résultat dans la nôtre
	* on suppose qu'il s'agit du même serveur (appel par localhost)
	* @param $pOperation SG_Texte ou SG_Formule : le nom du modèle d'opération à exécuter
	* @return : l'objet créé par la formule. Il doit exister dans l'application appelante
	**/
	function Executer ($pOperation = '') {
		$operation = SG_Texte::getTexte($pOperation);
		$identifiant = $_SESSION['@Moi'] -> identifiant;
		$jeton = $_SESSION['@Moi'] -> Jeton();
		$pURL = 'http://localhost/' . $this -> code . '/index.php?c=mop&m=' . $operation . '&s=o&k=' . $jeton -> texte . '&u=' . $identifiant;
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
			$ret = file_get_contents($pURL, false, $contexte);
			ini_set('memory_limit', '512M'); // TODO Supprimer ?
			$ret = unserialize($ret);
			ini_restore('memory_limit');
		} catch (Exception $e) {
			$msg = 'SG_Application [' . $this -> code . '] ligne ' . $e -> getLine() . ' : ' . $e -> getMessage();
			$ret = new SG_Erreur ('0202', $msg);
		}
		return $ret;
	}
	/** 2.3 ajout
	* Importe un objet non système avec ses propriétés et/ou ses méthodes
	* Rien n'est fait si une erreur est détectée
	* @param SG_Texte : nom de l'objet
	* @param SG_VraiFaux : avec ses propriétés (vrai par défaut)
	* @param SG_VraiFaux : avec ses méthodes (vrai par défaut)
	* @return SG_DictionnaireObjet : l'ojet créé ou SG_Erreur
	**/
	function ImporterObjet($pNom= '', $pAvecProprietes = true, $pAvecMethodes = true) {
		// vérifier que l'utilisateur est administrateur dans l'application visée
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
	// 2.1.1. complément de classe créée par compilation
	use SG_Application_trait;
}
?>
