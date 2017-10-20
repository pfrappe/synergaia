<?php
/** SynerGaia fichier pour la gestion de l'objet @Log */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

/**
 * Classe SynerGaia de gestion des logs
 * @version 2.1
 */
class SG_Log {
	/**
	* string Type SynerGaia
	*/
	const TYPESG = '@Log';
	/**
	* integer Valeur pour le niveau "FATAL"
	*/
	const LOG_NIVEAU_FATAL = 0;
	/**
	* integer Valeur pour le niveau "ERREUR"
	*/
	const LOG_NIVEAU_ERREUR = 1;
	/**
	* integer Valeur pour le niveau "WARNING"
	*/
	const LOG_NIVEAU_WARNING = 2;
	/**
	* integer Valeur pour le niveau "INFO"
	*/
	const LOG_NIVEAU_INFO = 3;
	/**
	* integer Valeur pour le niveau "DEBUG"
	*/
	const LOG_NIVEAU_DEBUG = 4;

	/**
	* string Valeur pour la sortie en console
	*/
	const LOG_SORTIE_CONSOLE = "Console";

	/**
	* string Type SynerGaia de l'objet
	*/
	public $typeSG = self::TYPESG;
	/**
	* string Valeur interne pour le type de sortie
	*/
	public $type;
	/**
	* integer Valeur interne pour le niveau
	*/
	public $niveau;

	/**
	* Construction de l'objet de log
	*
	* @param string $pType type de sortie
	* @param integer $pNiveau niveau de log minimal
	*/
	function __construct($pType = self::LOG_SORTIE_CONSOLE, $pNiveau = self::LOG_NIVEAU_ERREUR) {
		$this -> type = $pType;
		$this -> niveau = $pNiveau;
	}

	/**
	 * Ajout d'un message de log
	 *
	 * @param string $pMessage message
	 * @param integer $pNiveau niveau du message
	 */
	function log($pMessage = "", $pNiveau = self::LOG_NIVEAU_INFO) {
		static $indent;

		if ($this -> niveau >= $pNiveau) {
			$debut = substr($pMessage, 0, 3);
			if ($debut === 'OUT') {
				$indent--;
			}
			$message = substr("                                      ", 0, $indent) . $pMessage;
			if ($debut === 'IN ') {
				$indent++;
			}
			error_log($message, 0);
		}
	}

	/**
	 * Afficher : affiche le dernier fichier log d'Apache (per défaut) ou de CouchDB
	 * 
	 * @since 1.3.3 ajout
	 * @version 2.1 getMessage
	 * @param SG_Texte $pFichier code du log à afficher
	 * @return SG_HTML fichier log demandé
	 */
	static function Afficher($pFichier = "a") {
		$ret = ''; 
		$fichier = strtolower(SG_Texte::getTexte($pFichier));
		if ($fichier === "a") {
			$nom = '/var/log/apache2/error.log';
			if (!is_readable($nom)) {
				$ret = new SG_Erreur('0088');
			} else {
				try {
					$ret = file_get_contents($nom);
				} catch (Exception $e) {
					$ret = new SG_Erreur('0090', $e -> getMessage());
				}
			}
		} else {
			$ret = new SG_Erreur('0089', $fichier);
		}
		return new SG_HTML($ret);
	}
}
?>
