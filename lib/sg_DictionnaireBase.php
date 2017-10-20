<?php
/** SYNERGAIA fichier pour le traitement de l'objet @DictionnaireBase */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_DictionnaireBase : Classe de gestion d'une base de documents
 * @since 1.1
 */
class SG_DictionnaireBase extends SG_Document {
	/** string Type SynerGaia '@DictionnaireBase' */
	const TYPESG = '@DictionnaireBase';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Code de l'objet du dictionnaire */
	public $code;

	/**
	 * Construction de l'objet
	 * 
	 * @since 1.1
	 * @param string $pCode code de l'objet demandé
	 * @param array $pTableau tableau éventuel des propriétés
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

	/**
	 * Liste des accès possibles à proposer dans la création d'une nouvelle base
	 * @since 2.1 ajout
	 * @return string : "couchdb","domino","odbc"
	 */
	function Acces_possibles () {
		$ret = array("couchdb","domino","odbc");
		return $ret;
	}
}
?>
