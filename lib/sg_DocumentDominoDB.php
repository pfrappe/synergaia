<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.1 (see AUTHORS file)
 * SG_DocumentDominoDB : Classe de gestion d'un document Domino
 */
class SG_DocumentDominoDB extends SG_Objet {
	// Type SynerGaia
	const TYPESG = '@DocumentDominoDB';
	public $typeSG = self::TYPESG;
	
	public $codeBase;
	
	public $codeDocument;
	
	public $proprietes;
	
	/** 1.1 ajout
	* @param $pBase : chemin ou replicaID
	* @param $UniversalID du document
	*/
	public function __construct($pBase = '', $pDocumentUNID = '') {
		// objet domino
		if (!isset($_SESSION['@SynerGaïa'] -> domino)) {
			$_SESSION['@SynerGaïa'] -> domino = new SG_DominoDB();
		}
		$domino = $_SESSION['@SynerGaïa'] -> domino;
		//serveur et base
		if(getTypeSG($pBase) === '@DictionnaireBase') {
			$base = $pBase -> getValeur('@Chemin');
			$serveur = $pBase -> getValeur('@AdresseIP');
		} else {
			$serveur = $domino -> serveur;
			$base = SG_Texte::getTexte($pBase);
		}
		$this -> codeBase = $base;
		//code document
		$unid = SG_Texte::getTexte($pDocumentUNID);

		$this -> codeDocument = $unid;
		$this -> proprietes = array();
		$docbase = new SG_DictionnaireBase($base);
		
		if ($base !== '') {
			$json = $domino -> getURL($pBase, $base . '/SynerGaiaDocument?OpenAgent&unid=' . $unid); //&fields=form,nom,prenom');
			if(is_object($json)) {
				$this -> erreurs[] = $json;
				if(isset($json -> trace)) {
					journaliser('new DocumentCouchDB (json): ' . $json -> trace);
				}
			} else {
				$json = substr($json, strpos($json, '{'));
				$json = substr($json, 0, strrpos($json, '}') + 1);
				$this -> proprietes = json_decode($json, true);
				if (!is_array($this -> proprietes)) {
					$this -> erreurs[] = new SG_Erreur('0028', json_last_error_msg());
				} elseif (isset($this -> proprietes['_attachments'])) {
					$fichiers = array();
					foreach($this -> proprietes['_attachments'] as $fic) {
						$url = $base . '/0/' . $unid . '/$FILE/' . urlencode($fic);
						$data = $domino -> getURL($pBase, $url);
						if(isset($domino -> lastheaders['content-type'])) {
							$ct = $domino -> lastheaders['content-type'];
						} else {
							$ct = '';
						}
						if(getTypeSG($data) === '@Erreur') {
							$fichiers[$fic] = array('content_type' => $ct, 'data' => $data -> toString());
						} else {
							$fichiers[$fic] = array('content_type' => $ct, 'data' => base64_encode($data));
						}
					}
					$this -> proprietes['_attachments'] = $fichiers;
				}
			}
		}
	}
	// 1.1 ajout
	function getUUID() {
		return $this -> codeBase . '/' . $this -> codeDocument;
	}
}
?>
