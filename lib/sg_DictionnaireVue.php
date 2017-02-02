<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.1 (see AUTHORS file)
 * SG_DictionnaireBase : Classe de gestion d'une base de documents
 */
class SG_DictionnaireVue extends SG_Document {
	// Type SynerGaia
	const TYPESG = '@DictionnaireVue';
	public $typeSG = self::TYPESG;
	
	//code de la base
	const CODEBASE = 'synergaia_vues';

	// Code de l'objet du dictionnaire
	public $code;

	/** 1.1
	* Construction de la vue
	*
	* @param string $pCodeVue code de l'objet demandé
	* @param array tableau éventuel des propriétés
	*/
	public function __construct($pCodeVue = '', $pTableau = '') {
		$tmpCode = new SG_Texte($pCodeVue);
		$base = self::CODEBASE;
		$code = $tmpCode -> texte;
		if (! $tmpCode -> CommencePar($base) -> estVrai()) {
			$code = $base . '/' . $code;
		}
		$this -> initDocumentCouchDB($code, $pTableau);
		$this -> code = $this -> getValeur('@Code'); 
		$this -> setValeur('@Type', self::TYPESG);
	}
}
?>
