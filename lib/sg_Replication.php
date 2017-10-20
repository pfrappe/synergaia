<?php
/** SynerGaïa ce fichier contient la classe SG_Replication */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

// 2.3 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Replication_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Replication_trait.php';
} else {
	/** trait vide par défaut */
	trait SG_Replication_trait{};
}

/**  
 * SynerGaia SG_Replication : Classe de gestion d'une demande de réplication
 * Attention : la base _replicator est commune à toutes les applications.
 * Il faut donc tester dans cet objet que l'on travaille avec des bases de l'application en cours
 * @since 2.5
 * @version 2.6
 */
class SG_Replication extends SG_Objet {
	/** string Type SynerGaia '@Replication' */
	const TYPESG = '@Replication';

	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** string code de la base couchdb contenant les demandes de réplication 
	 * ATTENTION : commun à toutes les applications
	 **/
	CONST CODEBASE = "_replicator";

	/**
	* Enregistrement d'un objet @Replication dans CouchDB
	* exemple
	{
	  "_id": "68040d9f1beecdc29d70e8215106864a",
	  "_rev": "6-325a859a777af54e49e2fde44c79368e",
	  "source": "test_synergaia_annuaire",
	  "target": "http://loginadmincouchdb:pswcouchdb@192.168.0.155:5984/test_synergaia_annuaire",
	  "continuous": true,
	  "create_target": true,
	  "owner": "synergaia",
	  "_replication_state": "error",
	  "_replication_state_time": "2017-04-14T12:33:40+02:00",
	  "_replication_id": "73272ed794d56b798e8182fd0c05ae49",
	  "_replication_state_reason": "timeout"
	}
	* @since 2.5
	* @version 2.6 erreur 0275
	* @return SG_Replication|SG_Erreur soit $this si tout va bien, sinon SG_Erreur
	*/
	function Enregistrer() {
		$ret = $this;
		$couchdb = $_SESSION['@SynerGaia'] -> sgbd;
		$url = $this -> urlCouchDB(true);
		// simplifie en ne gardant que le tableau de propriétés
		$proprietes = array();
		foreach($this -> proprietes as $key => $propriete) {
			if($propriete !== '' and $propriete !== array()) {
				$proprietes[$key] = $propriete;
			}
		}
		// complète les propriétés manquantes
		if (!isset($proprietes['continuous'])) {
			$proprietes['continuous'] = true;
		}
		if (!isset($proprietes['create_target'])) {
			$proprietes['create_target'] = true;
		}
		if (!isset($proprietes['owner'])) {
			$proprietes['owner'] = 'synergaia';
		}
		// Enregistre les propriétés
		$ok = false;
		$contenu = json_encode($proprietes);
		if ($contenu === false) {
			if ($contenu === false) {
				$ret = new SG_Erreur(self::jsonLastError('Enregistrer', $this));
			} else {
				$ok = true;
			}
		} else {
			$ok = true;
		}
		if ($ok === true) {
			$resultat = $couchdb -> requete($url, "PUT", $contenu);
			if (strlen($resultat) !== 0) {
				$infos = json_decode($resultat);
				$this -> revision = $infos -> rev;  // avant 1.3.0 _rev
				$this -> setValeur('_rev', $this -> revision);

				$tmpCodeDocument = $infos -> id; // avant 1.3.0 _id
				if (is_null($tmpCodeDocument)) {
					$tmpCodeDocument = '';
				}
				if ($infos -> ok !== true) {
					$ret = new SG_Erreur('0100',$tmpCodeDocument);
				}
			} else {
				$ret = new SG_Erreur('0275');
			}
		}
		return $ret;
	}

	/** 
	 * Met à jour la propriété 'source' avec une nouvelle valeur ou retourne la valeur actuelle
	 * @since 2.5
	 * @version 2.6
	 * @param string|SG_Texte|SG_Formule nouvelle valeur de la propriété 'source'
	 * @return si mise à jour de la propriété : $this, sinon valeur de la propriété
	 **/
	function Source ($pNom = '') {
		if (func_num_args() === 0) {
			if (isset($this -> proprietes['source'])) {
				$s = $this -> proprietes['source'];
			} else {
				$s = '';
			}
			$ret = new SG_Texte($s);
		} else {
			$s = SG_Texte::getTexte($pNom);
			$this -> proprietes['source'] = $s;
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * Met à jour la propriété 'target' avec une nouvelle valeur ou retourne la valeur actuelle
	 * @since 2.5
	 * @version 2.6
	 * @param string|SG_Texte|SG_Formule nouvelle valeur de la propriété 'target'
	 * @return si mise à jour de la propriété : $this, sinon valeur de la propriété
	 **/
	function Cible ($pNom = '') {
		if (func_num_args() === 0) {
			if (isset($this -> proprietes['target'])) {
				$s = $this -> proprietes['target'];
			} else {
				$s = '';
			}
			$ret = new SG_Texte($s);
		} else {
			$s = SG_Texte::getTexte($pNom);
			$this -> proprietes['target'] = $s;
			$ret = $this;
		}
		return $ret;
	}
	// 2.3 complément de classe créée par compilation
	use SG_Objet_trait;
}
?>
