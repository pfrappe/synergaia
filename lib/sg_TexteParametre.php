<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.1.1 (see AUTHORS file)
* Classe SynerGaia de gestion d'un texte riche paramétrable
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_TexteParametre_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_TexteParametre_trait.php';
} else {
	trait SG_TexteParametre_trait{};
}
class SG_TexteParametre extends SG_TexteRiche {
	// Type SynerGaia
	const TYPESG = '@TexteParametre';
	public $typeSG = self::TYPESG;
	
	// Délimiteur de contenu actif (début)
	const DELIMITEUR_CONTENU_ACTIF_DEBUT = '{#';
	
	// Délimiteur de contenu actif (fin)
	const DELIMITEUR_CONTENU_ACTIF_FIN = '#}';

	/** 1.3.1 @param, return @HTML, strip_tags ; 2.1 si tableau ou erreur, suppression récursivité, param 2
	* Conversion en code HTML
	* @param (boolean ou @VraiFaux) $pCalculer : Calculer les formules intégrées (par défaut : oui)
	* @param (SG_Document ou dérivé) $pDocument : document sur lequel s'appliqueront les formules
	* @return string code HTML
	*/
	function Afficher($pCalculer = true, $pDocument = null) {
		$calculer = new SG_VraiFaux($pCalculer);
		$calculer = $calculer -> estVrai();
		$texte = $this -> texte;
		// afficher aussi comme texte riche
		$texte = $this -> traduireLesURLInternes($texte);
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
			if($ipos === false) {
				$newtexte = $texte;
			} else {
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
							$resultat = $resultat -> toString();
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
		$ret = new SG_HTML('<richtext class="champ_TexteRiche">' . $newtexte . '</richtext>');
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_TexteParametre_trait;
}
?>
