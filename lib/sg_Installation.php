<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 1.3.4 (see AUTHORS file)
* SG_Installation : Classe de gestion de l'installation
*/
class SG_Installation extends SG_Objet {
	/**
	 * Type SynerGaia
	 */
	const TYPESG = '@Installation';

	// Fichier du contenu du dictionnaire par défaut
	const DICTIONNAIRE_REFERENCE_FICHIER = 'ressources/dictionnaire.json';

	// 1.1 Fichier du contenu du dictionnaire par défaut
	const LIBELLES_REFERENCE_FICHIER = 'ressources/libelles.json';

	// 1.1 Fichier du contenu du dictionnaire par défaut
	const VILLES_REFERENCE_FICHIER = 'ressources/villes_fr.csv';

	// Type SynerGaia de l'objet
	public $typeSG = self::TYPESG;

	/** 1.3.0 correction test dictionnaire
	* Procédure d'installation nécessaire ? oui si pas de config ou pas de couchdb ou pas d'anuaire ou pas de dictionnaire
	* @return boolean
	*/
	static function installationNecessaire() {
		$ret = true;

		// Cherche le fichier de configuration
		if (file_exists(SYNERGAIA_PATH_TO_APPLI . '/' . SG_Config::FICHIER)) {
			// Fait un test de connexion au serveur CouchDB
			$couchDB = new SG_CouchDB();
			$connexionOK = $couchDB -> testConnexion();
			if ($connexionOK === true) {
				// Cherche la base annuaire
				$baseAnnuaire = new SG_Base(SG_Annuaire::CODEBASE);
				$baseAnnuaireExiste = $baseAnnuaire -> Existe() -> estVrai();
				// Cherche la base dictionnaire
				if ($baseAnnuaireExiste === true) {
					$baseDictionnaire = new SG_Base(SG_Dictionnaire::CODEBASE);
					$baseDictionnaireExiste = $baseDictionnaire -> Existe() -> estVrai();
					if ($baseDictionnaireExiste === true) {
						if(SG_Config::getConfig('HashDictionnaireDernierImport','') !== '') {
							$ret = false;
						}
					}
				}
			}
		}
		return $ret;
	}
	
	/** 1.1 ajout villes ; 1.3.0 @HTML ; 1.3.1 libellés et getConfig ; recalcul1201 ; 1.3.2 param $pRecalcul
	* Méthode de migration d'une version précédente
	* Cette méthode peut être exécutée plusieurs fois sans risque
	* Elle vide les caches et supprime les vues à recalculer
	*/
	static function MettreAJour($pRecalcul = true) {
		$version = SG_SynerGaia::NOVERSION;
		$versionprec = SG_Config::getConfig('SynerGaia_version','0000');
		$nl = '<br>';
		$ret = '<h1>Mise à jour ' . SG_SynerGaia::VERSION . ' (' . $version . ')</h1>'. $nl;
		if ($pRecalcul == true) {
			if ($versionprec < 1007) {
				$ret .= SG_Installation::recalcul1007();
			}
			if ($versionprec < 1201) {
				$ret .= SG_Installation::recalcul1201();
			}
		}
		// Cache
		$ret .= '<h2>Cache</h2>' . $_SESSION['@SynerGaia']->ViderCache() -> toString(). $nl;
		// Vues allDocuments
		$ret .= '<h2>Recalcul des vues</h2>' . str_replace(PHP_EOL, $nl, SG_Update::updateVuesAllDocuments()). $nl;
		// Libellés
		if (SG_Update::updateLibellesNecessaire() === true) {
			$update = SG_Update::updateLibelles();
			if (getTypeSG($update) !== '@Erreur') {
				if ($update === true) {
					$ret .= '<b>' . SG_Libelle::getLibelle('0071', false) . '</b><br>';
				} else {
					$ret .= '<b><p style="color:#ff0000">' . SG_Libelle::getLibelle('0072', false) . '</p></b> ' . $nl;
					$updateTotal = false;
				}
			} else {
				$ret .= '<b><p style="color:#ff0000">' . $update -> toString() . '</p></b> ' . $nl;
				$updateTotal = false;
			}
		}
		// Dictionnaire
		$updateTotal = true;
		if (SG_Update::updateDictionnaireNecessaire() === true) {
			$update = SG_Update::updateDictionnaire();
			if (getTypeSG($update) !== '@Erreur') {
				if ($update === true) {
					$ret .= '<b>' . SG_Libelle::getLibelle('0069', false) . '</b>' . $nl;
				} else {
					$ret .= '<b><p style="color:#ff0000">' . SG_Libelle::getLibelle('0070', false) . '</p></b> ' . $nl;
					$updateTotal = false;
				}
			} else {
				$ret .= '<b><p style="color:#ff0000">' . $update -> toString() . '</p></b> ' . $nl;
				$updateTotal = false;
			}
		}
		// recompilation des modèles d'opération et des objets
		$ret .= SG_Installation::recalcul2100();
		// Villes
		if (SG_Update::updateVillesNecessaire() === true) {
			$update = SG_Update::updateVilles();
			if (getTypeSG($update) !== '@Erreur') {
				if ($update === true) {
					$ret .= '<b>' . SG_Libelle::getLibelle('0073', false) . '</b><br>';
				} else {
					$ret .= '<b><p style="color:#ff0000">' . SG_Libelle::getLibelle('0074', false) . '</p></b><br>';
					$updateTotal = false;
				}
			} else {
				$ret .= '<b><p style="color:#ff0000">' . $update -> toString() . '</p></b><br>';
				$updateTotal = false;
			}
		}
		// enlever la demande de mise à jour
		if ($updateTotal === true) {
			SG_Config::setConfig('SynerGaia_version', SG_SynerGaia::NOVERSION);
			unset($_SESSION['page']['banniere']);
			$ret .= '<br><i>' . SG_SynerGaia::VERSION . ' : ' . SG_Libelle::getLibelle('0075', false) . '</i>';
		} else {
			$ret .= '<br><b><p style="color:#ff0000"><i>' . SG_SynerGaia::VERSION . ' : ' . SG_Libelle::getLibelle('0076', false, SG_SynerGaia::NOVERSION) . '</i></p></b>';
		}

		$ret = new SG_HTML($ret);
		return $ret;
	}
	
	/** 1.3.2 repris de install.php qui disparait ; 1.3.4 reprise de install_i.php qui disparaissent ; 2.1.1 init @Moi
	* Installation de SynerGaia
	*
	* @return SG_VraiFaux
	*/
	static function Installer() {
		$sg_install = array();
		$numPageInstallRecue = '';

		// Cherche les paramètres passés en POST pour savoir à quelle page de l'installation on est
		if (isset($_POST['sg_install_etape'])) {
			$numPageInstallRecue = (string)$_POST['sg_install_etape'];
		}
		$numPageInstallDemandee = $numPageInstallRecue;

		// Valeurs par défaut :
		$sg_install['db_type'] = SG_Config::getConfig('DB_Type', 'CouchDB');
		$sg_install['db_host'] = SG_Config::getConfig('CouchDB_host', '127.0.0.1');
		$sg_install['db_login'] = SG_Config::getConfig('CouchDB_login', 'synergaia');
		$sg_install['db_password'] = SG_Config::getConfig('CouchDB_password', '');
		// préfixe couchdb par défaut
		$ipos = strripos(SYNERGAIA_PATH_TO_APPLI,'/');
		$prefixe = substr(SYNERGAIA_PATH_TO_APPLI, $ipos + 1);
		$sg_install['db_prefix'] = SG_Config::getConfig('CouchDB_prefix', $prefixe);

		$sg_install['admin_login'] = '';
		$sg_install['admin_password'] = '';
		$sg_install['admin_password2'] = '';

		$sg_install['modules'] = '';

		// Erreurs des formulaires
		$sg_install['erreurs'] = array();
		$sg_install['erreurs']['sg_install'] = '';

		$sg_install['erreurs']['db_type'] = '';
		$sg_install['erreurs']['db_host'] = '';
		$sg_install['erreurs']['db_login'] = '';
		$sg_install['erreurs']['db_password'] = '';
		$sg_install['erreurs']['db_prefix'] = '';

		$sg_install['erreurs']['admin_login'] = '';
		$sg_install['erreurs']['admin_password'] = '';
		$sg_install['erreurs']['admin_password2'] = '';

		// Page 0 validée (base de donnée) => cherche les données envoyées
		if ($numPageInstallRecue === '0') {

			if (isset($_POST['sg_install_db_type'])) {
				$sg_install['db_type'] = (string)$_POST['sg_install_db_type'];
			}
			if (isset($_POST['sg_install_db_host'])) {
				$sg_install['db_host'] = (string)$_POST['sg_install_db_host'];
			}
			if (isset($_POST['sg_install_db_login'])) {
				$sg_install['db_login'] = (string)$_POST['sg_install_db_login'];
			}
			if (isset($_POST['sg_install_db_password'])) {
				$sg_install['db_password'] = (string)$_POST['sg_install_db_password'];
			}
			if (isset($_POST['sg_install_db_prefix'])) {
				$sg_install['db_prefix'] = (string)$_POST['sg_install_db_prefix'] . '_';
			}

			// Vérifie la validité des données reçues
			$okPasserEtapeSuivante = true;

			if ($sg_install['db_type'] === '') {
				$sg_install['erreurs']['db_type'] = 'Le type de base de données est obligatoire.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['db_host'] === '') {
				$sg_install['erreurs']['db_host'] = 'Le nom d\'hôte est obligatoire.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['db_login'] === '') {
				$tmpHost = ($sg_install['db_host'] === '') ? 'localhost' : $sg_install['db_host'];
				$sg_install['erreurs']['db_login'] = 'L\'identifiant de connexion est obligatoire. Utilisez <a href="http://' . $tmpHost . ':5984/_utils/" onclick="window.open(this.href);return false;">Futon/CouchDB</a> pour créer un utilisateur si besoin.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['db_password'] === '') {
				$sg_install['erreurs']['db_password'] = 'Le mot de passe de connexion est obligatoire.';
				$okPasserEtapeSuivante = false;
			}

			// Vérifier que le préfixe est correct (caractères autorisés)
			if ($sg_install['db_prefix'] !== SG_CouchDB::NormaliserNomBase($sg_install['db_prefix'])) {
				$sg_install['erreurs']['db_prefix'] = 'Le préfixe n\'est pas valide. Il doit commencer par une lettre et ne peut contenir que des lettres en minuscule, des chiffres, et le caractère "_".';
				$okPasserEtapeSuivante = false;
			}

			if ($okPasserEtapeSuivante === true) {

				// Enregistrer dans le fichier config/config.php
				$saveOK = true;
				// essai d'ouverture de config.php
				$saveOK = $saveOK and SG_Config::setConfig('CouchDB_host', $sg_install['db_host']);
				$saveOK = $saveOK and SG_Config::setConfig('CouchDB_login', $sg_install['db_login']);
				$saveOK = $saveOK and SG_Config::setConfig('CouchDB_password', $sg_install['db_password']);
				$saveOK = $saveOK and SG_Config::setConfig('CouchDB_prefix', $sg_install['db_prefix']);
				$saveOK = $saveOK and SG_Config::setConfig('SynerGaia_path_to_root',  SYNERGAIA_PATH_TO_ROOT);
				if ($saveOK === false) {
					// Erreur à la sauvegarde des parametres
					$sg_install['erreurs']['sg_install'] = 'Le fichier ' . SG_Config::FICHIER . ' n\'a pas pu être créé ou modifié.';
				} else {
					// Faire un test de connexion
					$couchDB = new SG_CouchDB();
					$connexionOK = $couchDB -> testConnexion();

					if ($connexionOK === false) {
						// Erreur au test de connexion
						$tmpHost = ($sg_install['db_host'] === '') ? 'localhost' : $sg_install['db_host'];
						$sg_install['erreurs']['sg_install'] = 'La connexion à CouchDB est impossible. Vérifiez les paramètres saisis et utilisez <a href="http://' . $tmpHost . ':5984/_utils/" onclick="window.open(this.href);return false;">Futon/CouchDB</a> pour créer un utilisateur si besoin.';
					} else {
						// Proposer l'étape suivante
						$numPageInstallDemandee = '1';
					}
				}

			}

		}

		// Page 1 validée (adminitrateur) => cherche les données envoyées
		if ($numPageInstallRecue === '1') {

			if (isset($_POST['sg_install_admin_login'])) {
				$sg_install['admin_login'] = (string)$_POST['sg_install_admin_login'];
			}
			if (isset($_POST['sg_install_admin_password'])) {
				$sg_install['admin_password'] = (string)$_POST['sg_install_admin_password'];
			}
			if (isset($_POST['sg_install_admin_password2'])) {
				$sg_install['admin_password2'] = (string)$_POST['sg_install_admin_password2'];
			}

			// Vérifie la validité des données reçues
			$okPasserEtapeSuivante = true;

			if ($sg_install['admin_login'] === '') {
				$sg_install['erreurs']['admin_login'] = 'L\'identifiant administrateur est obligatoire.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['admin_password'] === '') {
				$sg_install['erreurs']['admin_password'] = 'Le mot de passe est obligatoire.';
				$okPasserEtapeSuivante = false;
			}
			if ($sg_install['admin_password2'] !== $sg_install['admin_password']) {
				if ($sg_install['admin_password'] !== '') {
					$sg_install['erreurs']['admin_password2'] = 'Les mots de passe doivent être identiques.';
				}
				$okPasserEtapeSuivante = false;
			}

			if ($okPasserEtapeSuivante === true) {

				// "Connexion" de l'utilisateur
				$_SESSION['user_id'] = $sg_install['admin_login'];

				// Installation du dictionnaire
				$update = new SG_Update();
				$update -> updateDictionnaire();

				// Installation des l'application de base
				$install = new SG_Installation();
				$install -> MettreAJour(false);

				// Création du document de la personne dans l'annuaire
				$utilisateur = new SG_Utilisateur($sg_install['admin_login'], null, true);
				$utilisateur -> DefinirMotDePasse($sg_install['admin_password']);
				$utilisateur -> Enregistrer(false, false); // 1.3.2
				$_SESSION['@Moi'] = $utilisateur; // 2.1.1

				// Ajout de l'utilisateur aux profils par défaut
				$profils = array('ProfilUtilisateur', 'ProfilAdministrateur');
				$nbProfils = sizeof($profils);
				for ($i = 0; $i < $nbProfils; $i++) {
					$profil = new SG_Profil($profils[$i]);
					$profil -> AjouterUtilisateur($utilisateur);
				}
				// Proposer l'étape suivante
				$numPageInstallDemandee = '2';
			}
		}

		// Page 2 validée (modules complémentaires) => cherche les données envoyées
		if ($numPageInstallRecue === '2') {
			if (isset($_POST['sg_install_modules'])) {
				$sg_install['modules'] = $_POST['sg_install_modules'];
			}
			// Vérifie la validité des données reçues
			$okPasserEtapeSuivante = true;

			if ($okPasserEtapeSuivante === true) {
				// Installation des modules complémentaires demandés
				if ($sg_install['modules'] !== '') {
					$nbInstallModules = sizeof($sg_install['modules']);
					for ($i = 0; $i < $nbInstallModules; $i++) {
						$module = $sg_install['modules'][$i];
						$import = new SG_Import('ressources/packs/' . $module . '.json');
						$import -> Importer(SG_Dictionnaire::CODEBASE);
					}
				}

				// Proposer l'étape suivante
				$numPageInstallDemandee = '3';
			}
		}

		// Aucune page en cours => page initiale
		if ($numPageInstallRecue === '') {
			$numPageInstallDemandee = '0';
		}

		// Charge la page correspondante (1.3.2 vidercache seulement en fin étape 3)
		$ret = '';
		if ($numPageInstallDemandee === '0') {
			$ret = self::install_couchdb($sg_install);
		} elseif ($numPageInstallDemandee === '1') {
			$ret = self::install_admin($sg_install);
		}
		if ($numPageInstallDemandee === '2') {
			$ret = self::install_modules($sg_install);
		}
		if ($numPageInstallDemandee === '3') {
			$ret = self::install_activation($sg_install);
		}
		if ($ret !== '') {
			$debut = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" ><title>SynerGaïa - Installation</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="' . SG_Navigation::URL_THEMES . 'defaut/css/install.css"/><body><div class="entete"><h1>Installation SynerGaïa</h1>';
			$fin = '</fieldset></form></div><div class="pied">SynerGaïa - documentation : <a href="http://www.synergaia.eu">http://www.synergaia.eu</a></div></body></html>';
			$ret = $debut . $ret . $fin;
		}
		return $ret;
	}
	
	/** 1.0.7
	* recalcul des id @Utilisateurs
	*/
	static function recalcul1007() {
		$ret = '<h2>(1.0.7) Recalcul des ID des utilisateurs</h2><br>';
		$collec = SG_Annuaire::Utilisateurs();
		foreach($collec -> elements as $utilisateur) {
			$idAvant107 = $utilisateur -> getValeur('@IdAvant107', '');
			if ($idAvant107 === '' or $utilisateur -> doc -> codeDocument !== $utilisateur->identifiant) {
				$newUser = SG_Annuaire::getUtilisateur($utilisateur->identifiant);
				if ($newUser === false) {
					$newUserdoc  = new SG_DocumentCouchDB();
					$newUserdoc -> proprietes = $utilisateur -> doc -> proprietes;
					$newUserdoc -> codeBase = $utilisateur -> doc -> codeBase;
					$newUserdoc -> codeBaseComplet = $utilisateur -> doc -> codeBaseComplet;
					$newUserdoc -> proprietes['_id'] = $utilisateur->getValeur('@Identifiant');
					$newUserdoc -> codeDocument = $utilisateur->getValeur('@Identifiant');
					$newUserdoc -> revision = '';
					unset($newUserdoc -> proprietes['_rev']);
					$newUserdoc -> proprietes['@IdAvant107'] = $utilisateur -> doc -> codeDocument;
					$ret .= $newUserdoc -> getValeur('@IdAvant107') . ' => ' . $newUserdoc -> codeDocument;
					$ret .= $newUserdoc -> Enregistrer() -> estErreur() ? ' ERREUR problème':' : ok';
					$ret .= '<br>';
				}
			}
		}
		// suppression des anciens utilisateurs
		$collec = SG_Annuaire::Utilisateurs();
		foreach($collec -> elements as $utilisateur) {
			if ($utilisateur -> doc ->  codeDocument  !== $utilisateur->getValeur('@Identifiant')) {
				$code = $utilisateur -> doc ->  codeDocument;
				if ($utilisateur -> Supprimer() -> estVrai()) {
					$ret .= '<br> ' . $code . ' supprimé';
				} else {
					$ret .= '<br> ' . $code . ' : ERREUR problème à la suppression !';
				}
			}
		}
		// construction de la vue des utilisateurs
		$users = SG_Rien::Chercher('@Utilisateur');
		$oldusers = array();
		foreach($users -> elements as $user) {
			$oldusers[SG_Annuaire::CODEBASE . '/' .$user -> getValeur('@IdAvant107','?')] = $user-> getUUID(); //SG_Annuaire::CODEBASE . '/' .$user -> identifiant;
		}
		// parcours de tous les objets pour rechercher les champs @Utilisateur et changer l'identifiant
		$objets = SG_Dictionnaire::ObjetsDocument() -> elements;
		foreach($objets as $objet) {
			$champs = SG_Dictionnaire::getListeChamps($objet -> code,"@Utilisateur");
			if ($champs !== array()) {
				$docs = SG_Rien::Chercher($objet->code);
				foreach($docs -> elements as $element) {
					$modif = false;
					foreach($champs as $key => $champ) {
						if(isset($element -> doc -> proprietes[$key])) {
							$c = $element -> doc -> proprietes[$key];
							if(is_array($c)) {                                
								foreach($c as $keyc => $u) {
									if(isset($oldusers[$u])) {
										$c[$keyc] = $oldusers[$u];
										$modif = true;
									} elseif (isset($oldusers[SG_Annuaire::CODEBASE . '/' . $u])) {
										$c[$keyc] = $oldusers[SG_Annuaire::CODEBASE . '/' . $u];
										$modif = true;
									}
								}
							} else {
								if(isset($oldusers[$c])) {
									$c = $oldusers[$c];
									$modif = true;
								} elseif(isset($oldusers[SG_Annuaire::CODEBASE . '/' . $c])) {
									$c = $oldusers[SG_Annuaire::CODEBASE . '/' . $c];
									$modif = true;
								}
							}
							$element -> doc -> proprietes[$key]= $c;
						}
					}
					if($modif) {
						$element -> Enregistrer();
					}
				}
			}
		}
		$ret .= '<br>';
		return $ret;
	}
	/** 1.3.1 : ajout
	* suppression de @DocumentPrincipal et .@Principal des formules (sauf @Operation)
	**/
	static function recalcul1201() {
		$ret = '<h2>(1.2.1) Simplification des formules (.@DocumentPrincipal et .@Principal)</h2><br>';
		$ret.= '<p>Attention : les formules incluses dans des textes paramétrés NE SONT PAS TRADUITES !</p>';
		$formule = '@Chercher("@DictionnairePropriete","",.@ValeursPossibles.@EstVide.@Non)';
		$formule.= '.@Concatener(@Chercher("@DictionnaireMethode","",.@Action.@EstVide.@Non))';
		$formule.= '.@Concatener(@Chercher("@ModeleOperation","",.@Phrase.@EstVide.@Non))';
		$collec = SG_Formule::executer($formule);
		$n = 0;
		foreach($collec -> elements as $element) {
			$texte = '';
			switch (getTypeSG($element)) {
				case '@DictionnairePropriete' :
					$nomPropriete = '@ValeursPossibles';
					break;
				case '@DictionnaireMethode' :
					$nomPropriete = '@Action';
					break;
				case '@ModeleOperation' :
					$nomPropriete = '@Phrase';
					break;
			}
			// changement
			$modif = false;
			$texte = $element -> getValeurPropriete($nomPropriete, '');
			if ($texte -> Contient('.@DocumentPrincipal.') -> estVrai()) {
				$element -> setValeur($nomPropriete, $texte -> Remplacer('.@DocumentPrincipal', '.'));
				$modif = true;
			}
			if ($texte -> Contient('.@Principal.') -> estVrai()) {
				$element -> setValeur($nomPropriete, $texte -> Remplacer('.@Principal', '.'));
				$modif = true;
			}
			if($modif) {
				$n++;
				$element -> Enregistrer();
				$ret.= '<br><b>"' . $element -> toString() . '"</b> modifié';
			}
		}
		$ret.='<p>Modification 1.2.1 terminée !</p>';
		return $ret;
	}
	/** 1.3.4 déplacé de install_0.php
	* 
	**/
	static function install_couchdb($sg_install) {
		$ret = '<h2>Connexion à la base de données</h2></div>
<div class="contenu"><form action="" method="post">
<fieldset><input type="hidden" name="sg_install_etape" value="0"/>
<p><label for="sg_install_db_type">Type de base de données <abbr title="obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>SynerGaïa peut stocker ses données dans différents systèmes de base de données. Choisissez celui que vous souhaitez utiliser.</span></p>
<select name="sg_install_db_type"><option value=""';
		if ($sg_install['db_type'] === '') {$ret.= 'selected="selected" ';}
		$ret.= '>sélectionnez :</option><option value="CouchDB"';
		if ($sg_install['db_type'] === 'CouchDB') {$ret.= 'selected="selected" ';}
		$ret.= '>CouchDB</option></select>';
		if ($sg_install['erreurs']['db_type'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_type'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_db_host">Nom d\'hôte <abbr title="Saisie obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Saisissez le nom d\'hôte de la base de données. La valeur "localhost" est généralement utilisée.</span></p></p>
<input type="text" name="sg_install_db_host" value="' . $sg_install['db_host'] . '"/>';
		if ($sg_install['erreurs']['db_host'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_host'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_db_login">Nom d\'utilisateur <abbr title="Saisie obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Saisissez le nom d\'utilisateur SynerGaïa connu par le système de base de données.</span></p></p>
<input type="text" name="sg_install_db_login" value=""/>';
		if ($sg_install['erreurs']['db_login'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_login'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_db_password">Mot de passe <abbr title="Saisie obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Saisissez le mot de passe associé à l\'utilisateur de la base de données.</span></p></p>
<input type="password" name="sg_install_db_password" autocomplete="off" value=""/>';
		if ($sg_install['erreurs']['db_password'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_password'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_db_prefix">Préfixe des noms des bases :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Saisissez le préfixe à utiliser pour nommmer les bases. Utile si plusieurs environnements SynerGaïa cohabitent sur le même serveur.</span>
</p></p><input type="text" name="sg_install_db_prefix" value="' . $sg_install['db_prefix'] . '"/>';
		if ($sg_install['erreurs']['db_prefix'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['db_prefix'] . '</span>';
		}
		$ret.= '</p><p><input type="submit" class="btn" value="Enregistrer les paramètres de connexion"/>';
		if ($sg_install['erreurs']['sg_install'] !== '') {$ret.= '<span class="erreur">' . $sg_install['erreurs']['sg_install'] . '</span>';}
		$ret.= '</p>';
		return $ret;
	}
	/** 1.3.4 reprise de install_1.php
	**/
	static function install_admin($sg_install) {
		$ret = '<h2>Compte administrateur</h2></div><div class="contenu"><form action="" method="post">
<fieldset><input type="hidden" name="sg_install_etape" value="1"/>
<p><label for="sg_install_admin_login">Votre identifiant <abbr title="Saisie obligatoire">*</abbr> :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Identifiant de l\'administrateur de l\'environnement SynerGaïa.</span></p>
<input type="text" name="sg_install_admin_login" value="' . $sg_install['admin_login'] . '"/>';
		if ($sg_install['erreurs']['admin_login'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['admin_login'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_admin_password">Votre mot de passe <abbr title="Saisie obligatoire">*</abbr> :</label>
<input type="password" name="sg_install_admin_password" autocomplete="off" value="' . $sg_install['admin_password'] . '"/>';
		if ($sg_install['erreurs']['admin_password'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['admin_password'] . '</span>';
		}
		$ret.= '</p><p><label for="sg_install_admin_password2">Répétez votre mot de passe <abbr title="Saisie obligatoire">*</abbr> :</label>
<input type="password" name="sg_install_admin_password2" autocomplete="off" value=""/>';
		if ($sg_install['erreurs']['admin_password2'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['admin_password2'] . '</span>';
		}
		$ret.= '</p><p><input type="submit" class="btn" value="Créer le compte administrateur"/>';
		if ($sg_install['erreurs']['sg_install'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['sg_install'] . '</span>';
		}
		$ret.= '</p>';
		return $ret;
	}
	/** 1.3.4 repris de install_2.php
	**/
	static function install_modules($sg_install) {
		$ret = '<h2>Modules complémentaires</h2></div><div class="contenu"><form action="" method="post">
<fieldset><input type="hidden" name="sg_install_etape" value="2"/>
<p><label for="sg_install_modules">Modules complémentaires :</label>
<p class="tooltip"><img class="tooltip" src="' . SG_Navigation::URL_THEMES . 'defaut/img/icons/16x16/silkicons/help.png"/>
<span>Sélectionnez ici les modules à installer. Vous pourrez toujours ajouter des modules par la suite.</span></p>
<ul><li><input type="checkbox" name="sg_install_modules[]" value="socle" id="sg_install_module_socle" checked="checked" disabled="disabled" />
<label class="sg-checkbox" for="sg_install_module_socle">Socle minimal SynerGaïa</label></li>';
		// Cherche les modules complémentaires disponibles
		$cheminPacks = SYNERGAIA_PATH_TO_ROOT.'/ressources/packs';

		// Liste les noms des fichiers JSON du dossier
		$nomsFichiers = array();
		$dir = opendir($cheminPacks);
		while ($file = readdir($dir)) {
			// On cherche les fichiers "normaux"
			if ($file != '.' && $file != '..' && !is_dir($cheminPacks . '/' . $file)) {
				// On cherche les fichiers ".json"
				if (substr($file, -5) === '.json') {
					$nomsFichiers[] = $file;
				}
			}
		}
		closedir($dir);

		// Tri de la liste des fichiers
		sort($nomsFichiers);

		// Fabrique la liste des packs à partir des fichiers
		$packs = array();
		$nbFichiers = sizeof($nomsFichiers);
		for ($i = 0; $i < $nbFichiers; $i++) {
			$file = $nomsFichiers[$i];

			$pack = array();

			// Code du pack = nom de fichier
			$pack['code'] = substr($file, 0, -5);

			// Cherche le nom du pack
			$contenuTexte = file_get_contents($cheminPacks . '/' . $file);
			$contenuJSON = json_decode($contenuTexte, true);
			if (sizeof($contenuJSON) !== 0) {
				foreach ($contenuJSON as $key => $val) {
					$pack['nom'] = $key;
				}
				if ($pack['nom'] === '') {
					$pack['nom'] = $pack['code'];
				}
				$packs[] = $pack;
			}
		}

		// Génère la liste des cases à cocher pour les packs disponibles
		$nbPacks = sizeof($packs);
		for ($i = 0; $i < $nbPacks; $i++) {
			$idHTML = 'sg_install_module_' . $i;
			$html = '<li>' . PHP_EOL;
			$html .= ' <input type="checkbox" name="sg_install_modules[]" id="' . $idHTML . '" value="' . $packs[$i]['code'] . '" />' . PHP_EOL;
			$html .= ' <label class="sg-checkbox" for="' . $idHTML . '"/>' . $packs[$i]['nom'] . '</label>' . PHP_EOL;
			$html .= '</li>';
			$ret.= $html . PHP_EOL;
		}
		$ret.= '</ul></p><p><input type="submit" class="btn" value="Terminer l\'installation"/>';
		if ($sg_install['erreurs']['sg_install'] !== '') {
			$ret.= '<span class="erreur">' . $sg_install['erreurs']['sg_install'] . '</span>';
		}
		$ret.= '</p>';
		return $ret;
	}
	/** 1.3.4 repris de install_3.php
	**/
	static function install_activation($sg_install) {
		$ret = '<h2>Récapitulatif</h2></div>
		<div class="contenu"><form action="" method="post"><fieldset><h2>L\'installation a été réalisée avec succès.</h2>
		<a class="lien_synergaia" href="' . SG_Navigation::URL_PRINCIPALE . '"> Accéder à ' . SG_Config::getConfig('SynerGaia_titre', 'SynerGaïa') . '</a>';
		$r = SG_Cache::viderCache();
		return $ret;
	}
	/** 1.3.4 ajout
	* Crée les nouveaux répertoires (appli/, appli/config), les redirections (appli/img, appli/js, appli/themes), et les fichiers (config.php et index.php)
	* @param $pDir (string) : nom du répertoire de l'application
	* @param $pTitre : titre de l'applicatoin (sinon "SynerGaïa (incomplet))")
	**/ 
	static function install_repertoires($pDir = '', $pTitre = '') {
		$ret = true;
		$newappli = substr(SYNERGAIA_PATH_TO_APPLI, 0, strrpos(SYNERGAIA_PATH_TO_APPLI, '/')) . '/' . $pDir;
		
		if(!file_exists($newappli)) {
			$res = mkdir($newappli);
		}
		if(!file_exists($newappli . '/index.php')) {
			$res = copy(SYNERGAIA_PATH_TO_APPLI . '/index.php', $newappli . '/index.php');
		}
		// écriture du fichier de config par défaut
		if(!file_exists($newappli . '/config')) {
			$res = mkdir($newappli . '/config/');
		}
		$configfic = $newappli . '/' . SG_Config::FICHIER;
		if(!file_exists($configfic)) {
			$file = fopen($configfic, 'w');
			if ($file !== false) {
				$r= fwrite($file, '<?php defined("SYNERGAIA_PATH_TO_APPLI") or die("403.14 - Directory listing denied.");' . PHP_EOL);
				fwrite($file, PHP_EOL);
				fwrite($file, '$SG_Config[\'SynerGaia_path_to_root\'] = \'' . SYNERGAIA_PATH_TO_ROOT . '\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'SynerGaia_titre\'] = \'SynerGaïa (incomplet)\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'SynerGaia_url\'] = \'' . SG_Config::getConfig('SynerGaia_url') . '\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'SynerGaia_theme\'] = \'defaut\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'CouchDB_port\'] = ' . SG_Config::getConfig('CouchDB_port') . ';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'CouchDB_host\'] = \'' . SG_Config::getConfig('CouchDB_host') . '\';' . PHP_EOL);
				fwrite($file, '$SG_Config[\'CouchDB_prefix\'] = \'' . $pDir . '_\';' . PHP_EOL);
				fwrite($file, 'ini_set(\'memory_limit\', \'128M\');' . PHP_EOL);
				fwrite($file, PHP_EOL);
				fwrite($file, '?>' . PHP_EOL);
				$res = fclose($file);
			}
		}
		if(!file_exists($newappli . '/nav/')) {
			$res = symlink(SYNERGAIA_PATH_TO_ROOT . '/nav', $newappli . '/nav');
		}
		header('Location: http://' . $_SERVER["HTTP_HOST"] . '/' . $pDir . '/index.php');
		return $ret;
	}
	/** 2.1 ajout TODO : compléter Operation passées (?), 
	* gère l'arrivée du traitement php
	* Operation : ajout de typegeneralSG dans Operation, remplacement du nom de classe
	* ModeleOperation : traduction php et création des classes
	* DictionnaireObjet : traduction objet et création des classes
	**/
	static function recalcul2100() {
		$ret = '<h2>Compilation PHP des Modèles d\'opération</h2><br>';
		$ret.= self::compilationModelesOperation();
		$ret.= '<h2>Compilation PHP des Objets</h2><br>';
		$ret.= self::compilationObjets();
		return $ret;
	}
	/** 2.1 ajout
	* Compilation des modèles d'opération
	**/
	static function compilationModelesOperation() {
		$collec = SG_Rien::Chercher('@ModeleOperation');
		foreach ($collec -> elements as $elt) {
			$elt -> Enregistrer();
		}
		$ret = 'Terminé !';
		return $ret;
	}
	/** 2.1 ajout
	* Compilation des objets non-système (ne commencent pas par @)
	**/
	static function compilationObjets() {
		$collec = SG_Rien::Chercher('@DictionnaireObjet');
		foreach ($collec -> elements as $elt) {
			$code = $elt -> getValeur('@Code','');
			if (substr($code, 0, 1) !== '@') {
				$elt -> Enregistrer();
			}
		}
		$ret = 'Terminé !';
		return $ret;
	}
}
