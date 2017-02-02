<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
* Classe SynerGaia de gestion des tables
*/
// 2.1.1 Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur
if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Calendrier_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Calendrier_trait.php';
} else {
	trait SG_Calendrier_trait{};
}
class SG_Calendrier extends SG_Collection {
	// Type SynerGaia
	const TYPESG = '@Calendrier';
	public $typeSG = self::TYPESG;
	
	public $formuleTitre; // formule qui appliquée sur les documents donne leur titre
	public $formuleDebut; // formule qui appliquée sur les documents donne leur date de début
	public $formuleFin; // formule qui appliquée sur les documents donne leur date de fin
	public $formuleClasse; // 2.1 formule de la class CSS
	
	/* Termine la construction de l'objet comme calendrier (après SG_Collection->__construct()
	* @param tableau de 4 éléments 
	*   [0] indéfini $pQuelqueChose valeur à partir de laquelle le SG_Collection est créée (inutilisé ici)
	* 	[1] .Titre : formule qui appliquée sur les documents donne leur titre
	* 	[2] .Debut : formule qui appliquée sur les documents donne leur date de début
	* 	[3] .Fin : formule qui appliquée sur les documents donne leur date de fin
	* 	[4] .Classe : formule qui appliquée sur les documents donne le nom de la classe spécifique de la case
	*/
	public function initClasseDerive() {		
		$args = func_get_arg(0);
		if (sizeof($args) > 1) {
			$this -> formuleTitre = $args[1];
			if (sizeof($args) > 2) {
				$this -> formuleDebut = $args[2];
				if (sizeof($args) > 3) {
					$this -> formuleFin = $args[3];
					if (sizeof($args) > 4) {
						$this -> formuleClasse = $args[4];
					}
				}
			}
		}
	}
	// 1.2 ajout ; 2.0 parm ; 2.1 si es paramètres sont fournis ici, formuleClasse
	function Afficher($pParametres = NULL) {
		if(func_num_args() > 0) {
			$args = func_get_args();
			if (sizeof($args) >= 1) {
				$this -> formuleTitre = $args[0];
				if (sizeof($args) >= 2) {
					$this -> formuleDebut = $args[1];
					if (sizeof($args) >= 3) {
						$this -> formuleFin = $args[2];
						if (sizeof($args) >= 4) {
							$this -> formuleClasse = $args[3];
						}
					}
				}
			}
		}
		$html = $this -> AfficherCalendrier($this->formuleTitre, $this->formuleDebut, $this->formuleFin, $this -> formuleClasse);
		return $html;
	}
	//1.2 ajout
	function Titre($pTitre = null){
		if($pTitre === null) {
			$this->formuleTitre = $pTitre;
			$ret = $this;
		} else {
			$ret = $this -> formuleTitre;
		}
		return $ret;
	}
	//1.2 ajout
	// prépare la formule à exécuter en cas de clic dans une zone libre de l'agenda
	// par défaut : new=@Nouveau("document");new.Debut=@DateHeure($1);new.Fin=new.Debut.@Ajouter(1,"h");new.@Modifier
	// est repris dans @Collection.@AfficherCalendrier (voir url si clic dans initcalendar)
	function Clic($pAction = null) {
		if($pAction === null) {
			$typeDoc = getTypeSG($this -> Premier());
			$formule = 'new=@Nouveau("' . $typeDoc . '");new.Debut=@DateHeure($1);new.Fin=new.Debut.@Ajouter(1,"h");new.@Modifier';
		} else {
			$formule = $pAction;
		}
		$this -> clic = new SG_Bouton('clic', $formule);
		return $this;
	}
	//1.2 ajout : pas opérationnel
	function NouvelleEntree($pDebut = null, $pFin = null) {
		$typeDoc = getTypeSG($this -> Premier());
		$doc = SG_Rien::Nouveau($typeDoc);
		$debut = new SG_DateHeure($pDebut);
		$doc -> setValeur('Debut', $debut);
		$doc -> setValeur('Fin', $debut -> Ajouter(1, 'h'));
		$ret = $doc -> Modifier();
		return $ret;
	}
	/** 2.3 ajout
	* si paramètre, met à jour la formule de classe CSS des cases, sinon renvoi la classe actuelle
	* La formule sera exécutée sur chaque ligne du calendrier
	* @param $pClasse @Formule ou @Texte : texte ou formule donnant un texte de classe ou de style.
	* @return : la formule de classe enregistrée
	**/
	function Classe($pClasse = null){
		if($pClasse === null) {
			$this->formuleClasse = $pClasse;
			$ret = $this;
		} else {
			$ret = $this -> formuleClasse;
		}
		return $ret;
	}
	// 2.1.1. complément de classe créée par compilation
	use SG_Calendrier_trait;
}
?>
