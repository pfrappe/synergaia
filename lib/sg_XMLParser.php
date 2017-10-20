<?php
/** SYNERGAIA fichier pour le traitement de l'objet @XMLParser */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/** (see AUTHORS file)
 * SG_XMLParser : Parser XML standardisé pour SynerGaïa
 * @since 1.1
 * @todo à terminer
 */
class SG_XMLParser {
	/** string Type SynerGaia '@XMLParser' */
	const TYPESG = '@XMLParser';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** xml_paser */
	public $parser;

	/**
	 * Construction de l'objet
	 * @since 1.1
	 */
	function __construct() {
		$this -> initParser();
	}

	/**
	 * Parse le texte XML
	 * @since 1.1
	 * @param string $data
	 */
	function parse($data) {
		xml_parse($this -> parser, $data);
	}

	/**
	 * Parse le texte XML
	 * @since 1.1
	 * @param xml_parser $parser
	 * @param string $tag
	 * @param string $attributes
	 * @return boolean
	 */
	function tagDebut($parser, $tag, $attributes) {
		return false;
	}

	/**
	 * Parse le texte XML
	 * @since 1.1
	 * @param xml_parser $parser
	 * @param string $cdata
	 * @return boolean
	 */
	function cdata($parser, $cdata) {
		return false;
	}

	/**
	 * Parse le texte XML
	 * @since 1.1
	 * @param xml_parser $parser
	 * @param string $tag
	 * @return boolean
	 */
	function tagFin($parser, $tag) {
		return false;
	}
	
	/**
	 * Initialisation du parser
	 * @since 1.1
	 */
	function initParser() {
		if (! isset($this -> parser)) {
			$this -> parser = xml_parser_create();
			xml_set_object($this -> parser, $this);
			xml_set_element_handler($this -> parser, 'tagDebut', 'tagFin'); 
			xml_set_character_data_handler($this -> parser, 'cdata');
			xml_parser_set_option($this -> parser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($this -> parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
			xml_parser_set_option($this -> parser, XML_OPTION_SKIP_WHITE, 1);
		}
	}
}
?>
