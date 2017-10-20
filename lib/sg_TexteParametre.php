<?php
/** SynerGaia fichier pour la gestion de l'objet @TexteParametre */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_TexteParametre_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_TexteParametre_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_TexteParametre_trait{};
}

/**
* Classe SynerGaia de gestion d'un texte riche paramétrable
* @version 2.6 
*/
class SG_TexteParametre extends SG_TexteRiche {
	/** string Type SynerGaia '@TexteParametre' */
	const TYPESG = '@TexteParametre';
	/** string Délimiteur de contenu actif (début) */
	const DELIMITEUR_CONTENU_ACTIF_DEBUT = '{#';

	/** string Délimiteur de contenu actif (fin) */
	const DELIMITEUR_CONTENU_ACTIF_FIN = '#}';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;
	

	/**
	 * Conversion en code HTML pour l'affichage sur le navigateur
	 * C'est un peu plus que la traduction du SG_TexteRiche car, au passage,
	 * on effectue la traduction des clauses entre délimiteurs {#...#}
	 * 
	 * @version 2.6 corrections : erreur si $calculer != true ; test objet résultat
	 * @param boolean|SG_VraiFaux $pCalculer : Calculer les formules intégrées (par défaut : oui)
	 * @param SG_Document $pDocument document sur lequel s'appliqueront les formules
	 * @return string code HTML
	 */
	function Afficher($pCalculer = true, $pDocument = null) {
		$calculer = SG_VraiFaux::getBooleen($pCalculer);
		//$calculer = $calculer -> estVrai();
		$texte = $this -> texte;
		// afficher aussi comme texte riche
		$texte = $this -> traduireLesURLInternes($texte);
		$newtexte = $texte;
		// traiter les formules
		if ($calculer === true) {
			if ($pDocument !== null) {
				if(getTypeSG($pDocument) === '@Formule') {
					$docencours = $pDocument -> calculer();
				} else {
					$docencours = $pDocument;
				}
			} elseif (isset($this -> contenant -> document)) {
				$docencours = $this -> contenant -> document;
			} else {
				$docencours = $this -> contenant;
			}
			$formule = new SG_Formule('', $docencours );
			$ipos = strpos($texte, self::DELIMITEUR_CONTENU_ACTIF_DEBUT, 0);
			if($ipos !== false) {
				$ifin = 0;
				$newtexte = substr($texte,0,$ipos);
				//boucle sur les balises de formules SynerGaia
				while ($ipos !== false) {
					$ifin = strpos($texte, self::DELIMITEUR_CONTENU_ACTIF_FIN, $ipos);
					if ($ifin !== false) {
						$formuleHTML = strip_tags(substr($texte, $ipos +2, $ifin - $ipos - 2));
						$formule -> setFormule(html_entity_decode($formuleHTML,ENT_QUOTES,"UTF-8")); // codage compatible avec codage json couchdb...
						$resultat = $formule -> calculerSur($docencours);
						if (is_array($resultat)) { // 2.1
							$resultat = $resultat[0];
						}
						if (is_object($resultat)) {
							$resultat = $resultat -> toHTML();
							if(is_object($resultat)) {
								$resultat = $resultat -> texte;
							}
						}
						// ajuste la position de la fin
						$newtexte.= $resultat;
						$ipos = strpos($texte, self::DELIMITEUR_CONTENU_ACTIF_DEBUT, $ifin + 2);
						if ($ipos === false) {
							$newtexte.= substr($texte, $ifin + 2); // récupère la fin du texte
							break;
						} else {
							$newtexte.= substr($texte, $ifin + 2, $ipos - $ifin - 2); // texte entre deux balises
						}
					}
				}
			}
		}
		$ret = new SG_HTML('<richtext class="sg-richtext">' . $newtexte . '</richtext>');
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_TexteParametre_trait;
}
?>
