<?php
/** SYNERGAIA fichier pour le tratement de l'objet @CanalODBC */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_CanalODBC : Classe de gestion des bases de données ODBC
 * @since 1.1
 */
class SG_CanalODBC extends SG_Objet {
	/** string Type SynerGaia  '@CanalODBC' */
	const TYPESG = '@CanalODBC';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	/** string Code user du canal */
	public $user = '';
	/** string mot de passe du canal */
	public $psw = '';
	/** string source ODBC du canal */
	public $source = '';
	/** ODBCConnexion objet connexion */
	public $connexion;
	
	/**
	 * Construction d'un objet @CanalODBC
	 * 
	 * @since 1.1
	 * @param string|SG_Texte|SG_Formule $pSource
	 * @param string|SG_Texte|SG_Formule $pUser
	 * @param string|SG_Texte|SG_Formule $pPassword
	 */
	public function __construct($pSource = '', $pUser = '', $pPassword = '') {
		$this -> source = SG_Texte::getTexte($pSource);		
		if ($this -> source === '') {
			$this -> source = SG_Config::getConfig('CanalODBC_host', '');
		}
		$port = SG_Config::getConfig('CanalODBC_port', '');
		
		$this -> user = SG_Texte::getTexte($pUser);
		if ($this -> user === '') {
			$this -> user = SG_Config::getConfig('CanalODBC_login', '');
		}

		$this -> psw = SG_Texte::getTexte($pPassword);
		if ($this -> psw === '') {
			$this -> psw = SG_Config::getConfig('CanalODBC_password', '');
		}
		if ($this -> source !== '') {
			if (!function_exists("odbc_connect")) {
				$ret = SG_Operation::STOP('0270');
			}
			$r = $this -> Connecter();
		}
	}
	
	/**
	 * établir la connexion ODBC (connexion persistante)
	 * @since 1.1
	 * @return SG_CanalODBC
	 */
	public function Connecter() {
		$this -> connexion = odbc_connect ('Driver={FreeTDS};dbname="' . $this -> source . '";' , $this -> user, $this -> psw, SQL_CUR_USE_ODBC);
		if ($this -> connexion === false) {
			$this -> connexion = new SG_Erreur('0034', $this -> source . ': ' . odbc_errormsg());
		}
		return $this;
	}
	
	/**
	 * obtenir le résultat d'une requête ODBC
	 * 
	 * @since 1.1
	 * @param string|SG_Texte|SG_Formule $pRequete requete ODBC
	 * @return any résultat de bla requête
	 */
	public function Requete($pRequete = '') {
		$requete = SG_Texte::getTexte($pRequete);
		if (! isset($this -> connection)) {
			$this -> Connecter();
		}
		if (getTypeSG($this -> connexion) === '@Erreur') {
			$ret = new SG_Erreur('0035', $this -> connexion -> getMessage());
		} else {
			$res = $this -> connection -> execute($requete);
			$res_fld0 = $res -> Fields(0);
			$res_fld1 = $res -> Fields(1);
			while (!$res -> EOF) {
				$empNameLoc    = $res_fld0 -> value;
				$empWPPos    = $res_fld1 -> value;
				$res -> MoveNext();
			}

			$res -> Close();
		}
		return $ret;
	}

	/**
	 * Etablit le type de source et retourne le texte ODBC à envoyer
	 * 
	 * @since 1.1 new
	 * @param string|SG_Texte|SG_Formule $pType
	 * @param string|SG_Texte|SG_Formule $pServeur
	 * @param string|SG_Texte|SG_Formule $pDatabase
	 * @param string|SG_Texte|SG_Formule $pHost
	 * @param string|SG_Texte|SG_Formule $pFile
	 * @param string|SG_Texte|SG_Formule $pCnxName
	 * @return string|SG_Erreur
	 */
	public function SourceDeType($pType = '', $pServeur = '', $pDatabase = '', $pHost = '', $pFile = '', $pCnxName = '') {
		$type = strtolower(SG_Texte::getTexte($pType));
		$serveur = SG_Texte::getTexte($pServeur);
		$database = SG_Texte::getTexte($pDatabase);
		$host = SG_Texte::getTexte($pHost);
		$file = SG_Texte::getTexte($pFile);
		$cnxname = SG_Texte::getTexte($pCnxName);
		
		switch ($type) {
			case 'access' :
				$ret = 'Driver={Microsoft Access Driver (*.mdb)};Dbq=' . $database;
				break;
			case 'excel' :
				$ret = 'Driver={Microsoft Access Driver (*.mdb)};Dbq=' . $database;
			case 'foxpro' :
				$ret = 'Driver={Microsoft Visual FoxPro Driver};SourceType=DBF;SourceDB=' . $database . ';Exclusive=NO;collate=Machine;NULL=NO;DELETED=NO;BACKGROUNDFETCH=NO;';
				break;
			case 'mysql' :
				$ret = 'DRIVER={MySQL ODBC 3.51 Driver};CommLinks=tcpip(Host=' . $serveur . ');DatabaseName=' . $database . ';uid=' . $this -> user . '; pwd=' . $this -> psw . ';';
				break;
			case 'sqllite' :
				$ret = 'Driver=SQLite3;Database=' . $database;
				break;
			case 'sqlserver' :
				$ret = 'Driver={SQL Server Native Client 10.0};Server=' . $serveur . ';Database=' . $database . ';';
				break;
			case 'sybase' :
				$ret = 'Driver={Adaptive Server Anywhere 8.0};'.
						'CommLinks=tcpip(Host=' . $host . ');' .
						'ServerName=' . $serveur . ';' .
						'DatabaseName=' . $database . ';' .
						'DatabaseFile=' . $file . ';' .
						'ConnectionName=' . $cnxname . ';' .
						'uid=' . $this -> user . ';pwd=' . $this -> psw;
				break;
			default :
				$ret = new SG_Erreur('0033', $type);
				break;
		}
		return $ret;
	}

	/**
	 * Ferme le canal
	 * @since 1.1 new
	 */
	public function Fermer() {
		$this -> connexion -> Close(); 
	}
}
?>
