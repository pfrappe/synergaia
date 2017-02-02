<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.1 (see AUTHORS file)
 * 
 * SG_CanalODBC : Classe de gestion des bases de données ODBC
 *
 */
class SG_CanalODBC extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@CanalODBC';
	public $typeSG = self::TYPESG;
	
	public $user = '';
	public $psw = '';
	public $source = '';
	public $connexion;
	
	/** 1.1
	* Construction d'un objet @CanalODBC
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
			$r = $this -> Connecter();
		}
	}
	
	/** 1.1
	* établir la connexion ODBC (connexion persistante)
	*/
	public function Connecter() {
		$this -> connexion = odbc_connect ($this -> source , $this -> user, $this -> psw, SQL_CUR_USE_ODBC);
		if ($this -> connexion === false) {
			$this -> connexion = new SG_Erreur('0034', $this -> source . ': ' . odbc_errormsg());
		}
		return $this;
	}
	
	/** 1.1
	* obtenir le résultat d'une requête ODBC
	*/
	public function Requete($pRequete = '') {
		if (! isset($this -> connection)) {
			$this -> Connecter();
		}
		if (getTypeSG($this -> connexion) === '@Erreur') {
			$ret = new SG_Erreur('0035', $this -> connexion -> getMessage());
		} else {
			$resultat = $this -> connection -> execute(pRequete);
			$rs_fld0 = $rs->Fields(0);
			$rs_fld1 = $rs->Fields(1);
			while (!$rs->EOF) {
				$empNameLoc    = $rs_fld0->value;
				$empWPPos    = $rs_fld1->value;
				$rs->MoveNext();
			}

			$rs->Close();
		}
		return $ret;
	}
	/** 1.1 new
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
	/** 1.1 new
	*/
	public function Fermer() {
		$this -> connexion -> Close(); 
	}
}
?>
