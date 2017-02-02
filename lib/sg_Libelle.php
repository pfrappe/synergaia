<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1 (see AUTHORS file)
* SG_Libelle : classe SynerGaia de gestion des libellés standards
**/
class SG_Libelle extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@Libelle';
	public $typeSG = self::TYPESG;
	
	const CODEBASE = 'synergaia_libelles';
	/** 1.1 ajout ; 1.3.2 parm 3
	* Recherche un libellé sur la base et le prépare
	**/
	static function getLibelle($pCode = '', $pAvecNo = true, $pInfos = '') {
		$ret = '';
		if ($pCode !== '') {
			$code = new SG_Texte($pCode);
			$code = $code -> texte;
			if ($pAvecNo === true) {
				$ret = '(' . $code . ') ';
			}
			$ret.= $_SESSION['@SynerGaia'] -> getLibelle($pCode);
		}
		// Remplace les éventuels paramètres de la chaine avec les infos complémentaires
		$ret = str_replace('%s', (string) $pInfos, $ret);

		return $ret;
	}
}
?>
