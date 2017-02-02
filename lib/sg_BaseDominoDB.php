<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.4 (see AUTHORS file)
* 
* SG_BaseDominoDB : Classe de gestion d'une base de documents Domino
*
*/
class SG_BaseDominoDB extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@BaseDominoDB';
	public $typeSG = self::TYPESG;
	
	public $cookie = '';
	public $user = '';
	public $psw = '';
	public $serveur = '';
	
	public function __construct($pServeur = '', $pUser = '', $pPassword = '') {
	}
}
?>
