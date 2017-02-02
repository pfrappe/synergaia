<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.1 (see AUTHORS file)
 * SG_XMLParser : Parser XML standardisé pour SynerGaïa
 */
class SG_XMLParser {
	// Type SynerGaia
	const TYPESG = '@XMLParser';
	public $typeSG = self::TYPESG;
	
	public $parser;

	/** 1.1
	 * Construction de l'objet
	 */
	function __construct() {
		$this -> initParser();
    }
    // 1.1
    function parse($data) {
        xml_parse($this -> parser, $data);
    }

    function tagDebut($parser, $tag, $attributes) {
        return false;
    }

    function cdata($parser, $cdata) {
        return false;
    }

    function tagFin($parser, $tag) {
        return false;
    }
	
	//1.1 initialisation du parser
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
