<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.1 (see AUTHORS file)
 * SG_DictionnaireBase : Classe de gestion d'une base de documents
 */
class SG_DictionnaireBase extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@DictionnaireBase';
	public $typeSG = self::TYPESG;

	// Code de l'objet du dictionnaire
	public $code;

	/** 1.1
	* Construction de l'objet
	*
	* @param string $pCodeObjet code de l'objet demandé
	* @param array tableau éventuel des propriétés
	*/
	public function __construct($pCode = null, $pTableau = null) {
		$tmpCode = new SG_Texte($pCode);
		$base = SG_Dictionnaire::getCodeBase($this -> typeSG);
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code', '');
		$this -> setValeur('@Type', '@DictionnaireBase');
	}
	/** 2.1 ajout
	* @formula : "couchdb","domino","odbc"
	**/
	function Acces_possibles () {
		$ret = array("couchdb","domino","odbc");
		return $ret;
	}
}
?>
