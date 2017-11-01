<?php
/** SYNERGAIA fichier pour le traitement de l'objet @Rythme
 * @since 2.6
 */
defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');

if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/var/SG_Rythme_trait.php')) {
	include_once SYNERGAIA_PATH_TO_APPLI . '/var/SG_Rythme_trait.php';
} else {
	/** Pour ajouter les méthodes et propriétés spécifiques de l'application créées par le compilateur */
	trait SG_Rythme_trait{};
}

/**
 * Classe SynerGaia de gestion d'un rythme temporel
 * @since 2.6
 */
class SG_Rythme extends SG_Objet {
	/** string Type SynerGaia '@Rythme' */
	const TYPESG = '@Rythme';

	/** string description du rythme */
	public $rythme = '';

	/** string code unité de temps */
	public $unite = '';

	/**
	 * Construction d'un nouveau rythme temporel
	 * Exemple new SG_Rythme("1110","s") créer une série de semaines actives ou non (3 puis un espace)
	 * @since 2.6
	 * @param string|SG_Texte|SG_Formule $pRythme description du rythme sous forme de nombre (
	 * @param string|SG_Texte|SG_Formule $pUnite
	 */
	function __construct($pRythme = '', $pUnite = '') { //, $pRepeter = false) {
		$this -> rythme = SG_Texte::getTexte($pRythme);
		$this -> unite = SG_Texte::getTexte($pUnite);
	}
	
	/**
	 * Appliquer le rythme à partir d'une date ou date-heure fournie
	 * @since 2.6
	 * @param SG_Date|SG_DateHeure|SG_Formule $pDebut date de démarrage du calcul
	 * @param SG_Nombre|SG_Date|SG_DateHeure|SG_Formule $pFin date de fin du calcul ou nombre de fois s'il faut appliquer plusieurs fois le rythme
	 * @return SG_Collection|SG_Erreur liste des dates calculées ou erreur
	 */
	function Appliquer($pDebut = null, $pFin = null) {
		$ret = new SG_Collection(); 
		// préparation premier paramètre : debut
		if (is_null ($pDebut)) {
			$debut = SG_Rien::Maintenant();
		} elseif ($pDebut instanceof SG_Formule) {
			$debut = $pDebut -> calculer();
		} else {
			$debut = $pDebut;
		}
		if ($debut instanceof SG_Erreur) {
			$ret = $debut;
		} elseif (! ($debut instanceof SG_Date or $debut instanceof SG_DateHeure or $debut instanceof SG_Heure)) {
			$ret = new SG_Erreur('0284', getTypeSG($pDebut));
		} else {
			// préparation second paramètre : limite
			$classe = get_class($debut);
			$fin = new SG_Nombre();
			if (!is_null ($pFin)) {
				if ($pFin instanceof SG_Formule) {
					$fin = $pFin -> calculer();
				} elseif ($pFin instanceof SG_Texte) {
					$fin = new $classe($pFin);
				} else {
					$fin = $pFin;
				}
			}
			if ($fin instanceof SG_Erreur) {
				$ret = $fin;
			} elseif (! ($fin instanceof SG_Date or $fin instanceof SG_DateHeure or $fin instanceof SG_Heure or $fin instanceof SG_Nombre)) {
				$ret = new SG_Erreur('0285', getTypeSG($pFin));
			} else {
				// type de limite ('' ou 't' temps ou 'n' nombre de fois
				$typelim = '';
				if ($fin instanceof SG_Date or $fin instanceof SG_DateHeure or $fin instanceof SG_Heure) {
					$typelim = 't';
				} elseif ($fin instanceof SG_Nombre) {
					$typelim = 'n';
				}
				// Traitement
				$liste = array();
				$n = 0;
				$encore = true;
				while ($encore === true) {
					for ($i = 0; $i < strlen($this -> rythme); $i++) {
						$c = $this -> rythme[$i];
						if ($c !== '' and $c !== '0' and $c !== '-') {
							$liste[] = new $classe($debut -> toString());
						}
						if ($this -> unite === 's') {
							$debut = $debut -> Ajouter(7,'j');
						} elseif ($this -> unite === 'j') {
							$debut = $debut -> Ajouter(1,'j');
						}
					}
					$n++;
					// faut-il continuer ?
					$encore = false;
					if ($typelim === 't' and $debut -> InferieurA($fin) -> estVrai()) {
						$encore = true;
					} elseif ($typelim === 'n' and $n < $fin -> valeur) {
						$encore = true;
					}
				}
				$ret -> elements = $liste;
			}
		}
		return $ret;
	}

	// complément éventuel de classe créée par compilation
	use SG_Rythme_trait;
}
?>
