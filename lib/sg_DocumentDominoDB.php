<?php
/** SYNERGAIA fichier pour le traitement de l'objet @DocumentDominoDB */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * SG_DocumentDominoDB : Classe de gestion d'un document Domino
 * @since 1.1
 */
class SG_DocumentDominoDB extends SG_Objet {
	/** string Type SynerGaia */
	const TYPESG = '@DocumentDominoDB';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string code de la base */
	public $codeBase;

	/** string code du document */
	public $codeDocument;

	/** array tableau des propriétés du document */
	public $proprietes;
	
	/**
	 * Construction d'un nouveau document Domino
	 * @since 1.1 ajout
	 * @param string|SG_Texte|SG_Formule $pBase : chemin ou replicaID
	 * @param string|SG_Texte|SG_Formule $pDocumentUNID UNID Lotus Notes id du document
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

	/**
	 * Retourne l'id SynerGaïa du document
	 * @since 1.1 ajout
	 * @return string
	 */
	function getUUID() {
		return $this -> codeBase . '/' . $this -> codeDocument;
	}
}
?>
