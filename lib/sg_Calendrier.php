<?php
/** SynerGaia fichier contenant la définition et le traitemet de l'objet @Calendrier */
 defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Calendrier_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Calendrier_trait.php';
} else {
	/** trait pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Calendrier_trait{};
}

/**
 * Classe SynerGaia de gestion des tables
 * @since 1.2
 * @version 2.6
 */
class SG_Calendrier extends SG_Collection {
	/** string Type SynerGaia '@Calendrier' */
	const TYPESG = '@Calendrier';
	/** string Type SynerGaia */
	public $typeSG = self::TYPESG;

	/** SG_Formule formule qui appliquée sur les documents donne leur titre */ 
	public $formuleTitre;

	/** SG_Formule formule qui appliquée sur les documents donne leur date de début */ 
	public $formuleDebut;

	/** SG_Formule formule qui appliquée sur les documents donne leur date de fin */ 
	public $formuleFin;
	
	/** string SG_Formule bouton à exécuter si clic sur une zone vide (traité comme @Bouton)
	 * @since 2.6
	 */
	public $clicvide;

	/** string SG_Formule bouton à exécuter si doubleclic sur un document (traité comme @Bouton)
	 * @since 2.6
	 */
	public $doubleclic;

	/** SG_Formule formule de la class CSS
	 * @since 2.1
	 */
	public $formuleClasse;
	
	/**
	 * Termine la construction de l'objet comme calendrier (après SG_Collection->__construct()
	 * @param tableau de 5 éléments 
	 *  [0] indéfini $pQuelqueChose valeur à partir de laquelle le SG_Collection est créée (inutilisé ici)
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

	/**
	 * Calcule le code HTML pour l'affichage d'un calendrier
	 * 
	 * @since 1.2 ajout
	 * @version 2.1 si les paramètres sont fournis ici, formuleClasse
	 * @param array $pParametres 
	 *  [0] .Titre : formule qui appliquée sur les documents donne leur titre, 
	 *  [1] .Debut : formule qui appliquée sur les documents donne leur date de début
	 * 	[2] .Fin : formule qui appliquée sur les documents donne leur date de fin
	 * 	[3] .Classe : formule qui appliquée sur les documents donne le nom de la classe spécifique de la case
	 * @return SG_HTML
	 */
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
		$html = $this -> AfficherCalendrier($this -> formuleTitre, $this -> formuleDebut, $this -> formuleFin, $this -> formuleClasse);
		return $html;
	}

	/**
	 * Retourne ou met à jour le titre du calendrier
	 * @since 1.2
	 * @param string|G_Texte|SG_Formule $pTitre formule ou valeur du titre
	 * @return string code HTML du titre
	 */
	function Titre($pTitre = null){
		if($pTitre !== null) {
			$this -> formuleTitre = $pTitre;
			$ret = $this;
		} else {
			$ret = $this -> formuleTitre;
		}
		return $ret;
	}

	/**
	 * Retourne ou met à jour la valeur de la formule à exécuter lors d'un clic sur un document
	 * est repris dans Collection.AfficherCalendrier
	 * par défaut : .Afficher.Popup
	 * 
	 * @since 1.2 ajout
	 * @version 2.6 val par défaut $pAction ;valeur par défaut popup au lieu de modifier
	 * @param SG_Formule $pAction formule
	 * @return SG_Calendrier ceci
	 */
	function Clic($pAction = null) {
		if($pAction === null) {
			$formule = '.@Afficher.@Popup';
		} else {
			$formule = $pAction;
		}
		$this -> clic = new SG_Bouton('clic', $formule);
		return $this;
	}

	/**
	 * Retourne ou met à jour la valeur de la formule à exécuter lors d'un clic sur une zone vide
	 * prépare la formule à exécuter en cas de clic dans une zone libre de l'agenda
	 * par défaut : Nouveau("document").Debut=DateHeure($1).Fin=new.Debut.Ajouter(1,"h").Modifier
	 * est repris dans Collection.AfficherCalendrier (voir url si clic dans initcalendar)
	 * 
	 * @since 2.6 ajout
	 * @param SG_Formule $pAction formule
	 * @return SG_Calendrier ceci
	 */
	function ClicVide($pAction = null) {
		if($pAction === null) {
			$typeDoc = getTypeSG($this -> Premier());
			$formule = '@Nouveau("' . $typeDoc . '").Debut(@DateHeure($1)).Fin(.Debut.@Ajouter(1,"h")).@Modifier.@Popup';
		} else {
			$formule = $pAction;
		}
		$this -> clicvide = new SG_Bouton('clic', $formule);
		return $this;
	}

	/**
	 * Retourne ou met à jour la valeur de la formule à exécuter lors d'un double clic sur un document
	 * prépare la formule à exécuter en cas de clic dans une zone libre de l'agenda
	 * par défaut : .Modifier
	 * est repris dans Collection.AfficherCalendrier
	 * 
	 * @since 2.6 ajout
	 * @param SG_Formule $pAction formule
	 * @return SG_Calendrier ceci
	 */
	function DoubleClic($pAction = null) {
		if($pAction === null) {
			$formule = '.@Modifier';
		} else {
			$formule = $pAction;
		}
		$this -> doubleclic = new SG_Bouton('clic', $formule);
		return $this;
	}

/* 	//1.2 ajout : pas opérationnel
	function NouvelleEntree($pDebut = null, $pFin = null) {
		$typeDoc = getTypeSG($this -> Premier());
		$doc = SG_Rien::Nouveau($typeDoc);
		$debut = new SG_DateHeure($pDebut);
		$doc -> setValeur('Debut', $debut);
		$doc -> setValeur('Fin', $debut -> Ajouter(1, 'h'));
		$ret = $doc -> Modifier() -> Popup();
		return $ret;
	}
*/

	/**
	 * si paramètre, met à jour la formule de classe CSS des cases, sinon retourne la classe actuelle
	 * La formule sera exécutée sur chaque ligne du calendrier
	 * 
	 * @since 2.3 ajout
	 * @param string|G_Texte|SG_Formule $pClasse texte ou formule donnant un texte de classe ou de style.
	 * @return string|SG_Calendrier la formule de classe enregistrée ou le calendrier (si mise à jour)
	 */
	function Classe($pClasse = null){
		if($pClasse === null) {
			$this -> formuleClasse = $pClasse;
			$ret = $this;
		} else {
			$ret = $this -> formuleClasse;
		}
		return $ret;
	}

	/**
	 * Retourne la collection des événements autour d'un mois
	 * 
	 * @since 2.4 ajout
	 * @param string $pTypeObjet : type d'objet sur lequel se fait la recherche
	 * @param string $pChamp : nom du champ début sur lequel se fait la recherche
	 * @param string $pMois : année mois (aaaamm) demandé
	 * @return SG_Collection|SG_Erreur : la collection demandée
	 */
	function get3Mois($pTypeObjet = '', $pChamp = '', $pMois = '') {
		$ret = $_SESSION['@SynerGaia'] -> sgbd -> get3mois(SG_Texte::getTexte($pTypeObjet), SG_Texte::getTexte($pChamp), SG_Texte::getTexte($pMois));
		return $ret;
	}

	// 2.1.1. complément de classe créée par compilation
	use SG_Calendrier_trait;
}
?>
