<?php
/** SynerGaia fichier de gestion de l'objet @DictionnaireVue */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_DictionnaireVue : Classe de gestion d'une vue d'une base de documents
 * @since 1.1
 */
class SG_DictionnaireVue extends SG_Document {
	/** string Type SynerGaia '@DictionnaireVue' */
	const TYPESG = '@DictionnaireVue';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	
	/** string code de la base de stockage */
	const CODEBASE = 'synergaia_vues';

	/** string Code de l'objet du dictionnaire */
	public $code;

	/**
	 * Construction de la vue
	 * 
	 * @since 1.1
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
