<?php
/** SYNERGAIA fichier pour le traitement de l'objet @VueDominoDB */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_VueDominoDB : Classe de gestion des vues Domino
 * @since 1.1
 */
class SG_VueDominoDB extends SG_Objet {
	/** string Type SynerGaia */
	const TYPESG = '@VueDominoDB';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string Document "vue" associé */
	public $vue;

	/** string Code de la vue */
	public $code;

	/** string Code de la base */
	public $codeBase;

	/** string Code complet de la base avec prefixe */
	public $codeBaseComplet = '';

	/**
	* Construction de l'objet
	* 
	* @since  1.1
	* @param string|SG_Texte|SG_Formule $pBase
	* @param string|SG_Texte|SG_Formule $pCodeVue code de la vue
	*/
	public function __construct($pBase = '', $pCodeVue = '') {
		if (!isset($_SESSION['@SynerGaïa'] -> domino)) {
			$_SESSION['@SynerGaïa'] -> domino = new SG_DominoDB();
			$_SESSION['@SynerGaïa'] -> domino -> Connecter();
		}
		$this -> codeBase = strtolower(SG_Texte::getTexte($pBase));
		// Si j'ai un code de vue
		if ($pCodeVue !== '') {
			$this -> code = SG_Texte::getTexte($pCodeVue);
		}
	}

	/**
	 * Pas opérationnel
	 * @since 1.1
	 * @param string|SG_Texte|SG_Formule $parser
	 * @param string|SG_Texte|SG_Formule $tag
	 * @param string|SG_Texte|SG_Formule $attributes
	 * @return boolean false
	 */
	function tagDebut($parser, $tag, $attributes) {
		return false;
	}

	/**
	 * Pas opérationnel
	 * @since 1.1
	 * @param string|SG_Texte|SG_Formule $parser
	 * @param string|SG_Texte|SG_Formule $cdata
	 * @return boolean false
	 */
	function cdata($parser, $cdata) {
		return false;
	}

	/**
	 * Pas opérationnel
	 * @since 1.1
	 * @param string|SG_Texte|SG_Formule $parser
	 * @param string|SG_Texte|SG_Formule $tag
	 * @return boolean false
	 */
	function tagFin($parser, $tag) {
		return false;
	}

	/**
	 * Extrait le contenu de la vue
	 * @since 1.1
	 * @param string $pCleRecherche clé de recherche
	 * @param string $pFiltre formule à exécuter immédiatement
	 * @param boolean $pIncludeDocs permet d'inclure les documents dans la recherche
	 * @param integer|SG_Nombre|SG_Formule $pStart
	 * @param integer|SG_Nombre|SG_Formule $pCount
	 * @return SG_Collection des objets SynerGaia lus
	 */
	function Contenu($pCleRecherche = '', $pFiltre = '', $pIncludeDocs = false, $pStart = 1, $pCount = 10000) {
		$start = new SG_Nombre($pStart);
		$start = $start -> valeur;
		if ($start < 1) $start = 1;
		$count = new SG_Nombre($pCount);
		$count = $count -> valeur;
		if ($count < 1) $count = 10000;
		$includedocs = new SG_VraiFaux($pIncludeDocs);
		$includedocs = $includedocs -> estVrai();
		
		$ret = new SG_Collection();
		if ($pCleRecherche === '') {
			$url = $this -> codeBase . '/' . $this -> code . '?ReadViewEntries&outputformat=JSON&start='. $start . '&count=' . $count;
			$json = $_SESSION['@SynerGaïa'] -> domino -> getURL('', $url);
			if (getTypeSG($json) === '@Erreur') {
				$ret = $json;
			} else {
				// le json de Domino n'est pas accepté tel quel par php : on le modifie
				$json = str_replace(array("\>","\!"), array(">","!"), $json);
				$result = json_decode($json, true);
				if (! isset($result['viewentry'])) {
					$ret = new SG_Erreur('0026', $result);
				} else {
					$result = $result['viewentry'];
					foreach ($result as $lignelue) {
						$element = new SG_Document();
						$ligne = array();
						if (isset($lignelue['entrydata'])) {
							foreach($lignelue['entrydata'] as $key => $value) {
								if (isset($value['@columnnumber'])) {
									$c = '@Col' . $value['@columnnumber'];
								} else {
									$c = '@Col' . $key;
								}
								if (isset($value['text'])) {
									$val = $value['text'];
									if (is_array($val)) {
										$ligne[$c] = new SG_Texte($val[0]);
									} else {
										$ligne[$c] = new SG_Texte($val);
									}
								} elseif (isset($value['textlist'])) {
									if (sizeof($value['textlist']) === 1) {
										$ligne[$c] = new SG_Texte($value['textlist']['text'][0][0]);
									} else {
										$textes = array();
										foreach ($value['textlist'] as $key => $val) {
											if (is_array($val)) {
												$textes[] = new SG_Texte($val['text'][0][0]);
											} else {
												$textes[] = new SG_Texte($val['text'][0]);
											}
										}
										$ligne[$c] = $textes;
									}
								} elseif (isset($value['number'])) {
									$val = $value['number'];
									if (is_array($val)) {
										$ligne[$c] = new SG_Nombre($val[0]);
									} else {
										$ligne[$c] = new SG_Nombre($val);
									}
								} elseif (isset($value['numberlist'])) {
									if (sizeof($value['numberlist']) === 1) {
										$ligne[$c] = new SG_Nombre($value['numberlist']['number'][0][0]);
									} else {
										$nombres = array();
										foreach ($value['numberlist'] as $key => $val) {
											if (is_array($val)) {
												$nombres[] = new SG_Nombre($val['number'][0][0]);
											} else {
												$nombres[] = new SG_Nombre($val['number'][0]);
											}
										}
										$ligne[$c] = $nombres;
									}
								} elseif (isset($value['datetime'])) {
									$dt = new SG_DateHeure();
									$val = $value['datetime'];
									if (is_array($val)) {
										$dt -> setDateTimeDomino($val[0]);
									} else {
										$dt -> setDateTimeDomino($val);
									}
									$ligne[$c] = $dt;
								} else {
									$ligne[$c] = new SG_Texte(implode(',', $value));
								}
							}
							if (isset($lignelue['@unid'])) {
								if ($includedocs) {
									$json = $_SESSION['@SynerGaïa'] -> domino -> getURL('', $this -> codeBase . '/SynerGaiaDocument?unid=' . $lignelue['@unid'] . '&OpenAgent');
									$tableau = json_decode($json);
									$typeElement = 'SG_Document';
									if (isset($row['doc']['@Type'])) {
										$type = $row['doc']['@Type'];
										if (substr($type, 0, 1) === '@') {
											$typeElement = SG_Dictionnaire::getClasseObjet($type);
										} else {
											$codeElement = $this -> codeBase . '/' . $codeElement;
										}
									}
									$element = new $typeElement($this -> codeBase, $tableau);
								} else {
									$ligne['@unid'] = new SG_Texte($lignelue['@unid']);						
									$element -> doc = new SG_DocumentDominoDB ();
									$element -> doc -> codeDocument = $lignelue['@unid'];
									$element -> doc -> codeBase = $this -> codeBase;
								}
							}
							$element -> proprietes = $ligne;
							$ret -> elements[] = $element;
						}
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Cherche les éléments correpondants à une clé dans la vue
	 * 
	 * @since 1.1
	 * @param string|SG_Texte|SG_Formule $pCleRecherche clé de recherche
	 * @return SG_Collection
	 */
	function ChercherElements($pCleRecherche = '') {
		return $this -> Contenu($pCleRecherche);
	}

	/**
	 * parse l'xml de la vue dans un tableau associatif
	 * source php manual article xml_parse
	 * 
	 * @since 1.1 
	 * @param string $xml texte à parser
	 * @return SG_Collection
	 */
	function parseToArray($xml) {
		$xml_array = array();
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($xml), $xml_values);
		xml_parser_free($parser);
		$get_attributes = 1;
		$priority = 'tag';
		if ($xml_values) {
			$xml_array = array ();
			$parents = array ();
			$opened_tags = array ();
			$arr = array ();
			$current = & $xml_array;
			$repeated_tag_index = array ();
			foreach ($xml_values as $data) {
				unset ($attributes, $value);
				extract($data);
				$result = array ();
				$attributes_data = array ();
				if (isset ($value)) {
					if ($priority == 'tag') {
						$result = $value;
					} else {
						$result['value'] = $value;
					}
				}
				if (isset ($attributes) and $get_attributes) {
					foreach ($attributes as $attr => $val) {
						if ($priority == 'tag') {
							$attributes_data[$attr] = $val;
						} else {
							$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
						}
					}
				}
				if ($type == "open") {
					$parent[$level -1] = & $current;
					if (!is_array($current) or (!in_array($tag, array_keys($current)))) {
						$current[$tag] = $result;
						if ($attributes_data) {
							$current[$tag . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag . '_' . $level] = 1;
						$current = & $current[$tag];
					} else {
						if (isset ($current[$tag][0])) {
							$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
							$repeated_tag_index[$tag . '_' . $level]++;
						} else {
							$current[$tag] = array ($current[$tag], $result);
							$repeated_tag_index[$tag . '_' . $level] = 2;
							if (isset ($current[$tag . '_attr'])) {
								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset ($current[$tag . '_attr']);
							}
						}
						$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
						$current = & $current[$tag][$last_item_index];
					}
				} elseif ($type == "complete") {
					if (!isset ($current[$tag])) {
						$current[$tag] = $result;
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ($priority == 'tag' and $attributes_data)
							$current[$tag . '_attr'] = $attributes_data;
					} else {
						if (isset ($current[$tag][0]) and is_array($current[$tag])) {
							$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
							if ($priority == 'tag' and $get_attributes and $attributes_data) {
								$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
							$repeated_tag_index[$tag . '_' . $level]++;
						} else {
							$current[$tag] = array ( $current[$tag], $result );
							$repeated_tag_index[$tag . '_' . $level] = 1;
							if ($priority == 'tag' and $get_attributes) {
								if (isset ($current[$tag . '_attr'])) {
									$current[$tag]['0_attr'] = $current[$tag . '_attr'];
									unset ($current[$tag . '_attr']);
								}
								if ($attributes_data) {
									$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
								}
							}
							$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
						}
					}
				} elseif ($type == 'close') {
					$current = & $parent[$level -1];
				}
			}
		}
		return ($xml_array);
	}
}
?>
