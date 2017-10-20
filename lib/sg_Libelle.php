<?php
/** 
 * Fichier contenant les traitements de la classe SG_Libelle
 * @since 1.1
 * @version 1.3.2
 */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Libelle_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Libelle_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur, via un trait à la fin de la classe */
	trait SG_Libelle_trait{};
}

/**
 * SG_Libelle : classe SynerGaia de gestion des libellés standards
 * @since 1.1
 * @version 1.3.2
 */
class SG_Libelle extends SG_Document {
	/** string Type SynerGaia '@Libelle' */
	const TYPESG = '@Libelle';

	/** string code de la base CouchDB */
	const CODEBASE = 'synergaia_libelles';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/**
	 * Recherche un libellé sur la base et le prépare
	 * Si le paramètre $pInfos est fourni, son texte vient remplacer le premier %s du libellé orginal du message
	 * @since 1.1 ajout
	 * @version 1.3.2 parm 3
	 * @param string	$pCode	Code du message
	 * @param boolean	$pAvecNo	Afficher ou non le code du message
	 * @param string	$pInfos	informations copmplémentaires éventuelles
	 * @return string le libellé correspondant au numéro
	 */
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

	// Complément de classe spécifique à l'application (créé par la compilation)
	use SG_Libelle_trait;
}
?>
