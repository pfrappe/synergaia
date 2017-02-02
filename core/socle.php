<?php defined("SYNERGAIA_PATH_TO_ROOT") or die('403.14 - Directory listing denied.');
/** SynerGaia 2.3 (see AUTHORS file)
 * fonctions basiques communes à tous les programmes
 */

/** 1.0.7
* intercepte l'événement en erreur (défini dans core/ressources.php)
*/
function errorHandler($code = 0, $message= '', $file= '', $line = 0) {
	if (0 == error_reporting()) {
		return;
	}
	throw new ErrorException($message, 0, $code, $file, $line);
}
/** 1.0.7
* génère la trace de l'événement en erreur (défini dans core/ressources.php)
*/
function exceptionHandler($e) {  
	if ( !headers_sent() ) {
		header("Content-type: text/html");
		header( sprintf("HTTP/1.1 %d %s", 500, ' message 500') );
	} else {
		echo "<pre>"; // trop tard :-(
	}
	try {
		@ob_flush();
	}
	catch( Exception $ignored ) {}
	echo( '================= Stack Trace ===================');

	$trace = array_reverse($e->getTrace());
	$i=1;
	foreach( $trace AS $k => $v ) {
		echo( "<br>$i: " . (isset($v['file'])?'fichier : ' .$v['file']:'')  . (isset($v['line'])?', ligne ' . $v['line'] : '') . (isset($v['class'])?(', ' . $v['class']):'') . (isset($v['type'])?($v['type']):' ') . (isset($v['function'])?($v['function'] . '()'):'') );
		$i++;
	}
	echo( '<br>'.$e->getCode() . ' ' . $e -> getFile() . ', ligne ' . $e -> getLine() . ', '.$e -> getmessage()) ;
}
/** 2.1 test args, pile appels, return
* 1.2 shuntage possible test admin ; 1.3.1 supression fonctin synergaia pour éviter boucles dans SG_Formule ; 1.3.3 écrit dans $_SESSION['debug']['texte']
* 1.3.4 : test isset [debug]
* 1.1 class <pre>, test ->admin pour supprimer boucle possible
*/
function tracer() {
	$ok=false; // mettre true pour shunter test admin (ne pas oublier de remettre à false !)
	$texte = '';
	if (isset($_SESSION['@Moi'] -> admin) or $ok) {
		if ($ok or $_SESSION['@Moi'] -> admin -> valeur === SG_VraiFaux::VRAIFAUX_VRAI) {
			$appels = debug_backtrace();
			$i=0;
			foreach($appels as $f) {
				if ($i === 0) {
					$texte .= '<br><b> ' . @$f['file']  . '['.@$f['line'] . '].';
				} elseif ($i === 1) {
					$texte .=  @$f['function'] . '</b> : (';
					if(isset($f['args'])) {
						$prm = '';
						foreach($f['args'] as $key => $arg) {
							if(getTypeSG($arg) === SG_Formule::TYPESG) {
								$argument = $arg -> formule;
							} else {
								$argument = getTypeSG($arg);
							}
							if($prm === '') {
								$prm = $argument;
							} else {
								$prm.= ', ' . $argument;
							}
						}
					}
					$texte.=$prm . ')<br><i>' . @$f['file']  . '['.@$f['line'] . '].</i>';
				} else {
					$texte.='<br><i>' . @$f['file']  . '['.@$f['line'] . '].</i>';
				}
				$i++;
				if ($i >= 10) break;
			}
			$args = func_get_args();
			foreach ($args as $key => $arg) {
				if ($arg === null) {
					$texte .= '<br> valeur (' . $key . ') :<pre>null</pre>';
				} else {
					$texte .= '<br> valeur (' . $key . ') :<pre>' . print_r($arg, true) . '</pre>';
				}
			}
			if(isset($_SESSION['debug']['texte'])) {
				$_SESSION['debug']['texte'].= $texte . PHP_EOL;
			} else {
				$_SESSION['debug']['texte'] = $texte . PHP_EOL;
			}
		}
	}
	return $texte;
}

// 2.0 init $_SESSION['debug']['texte']
function tracerAppels($pMessage = '', $maxAppels = 0) {
	if (is_object($_SESSION['@Moi'] -> admin) and $_SESSION['@Moi'] -> admin -> estVrai() === true) {
		if(!isset($_SESSION['tracerAppels'])) {// pour éviter les boucles
			$message = $pMessage;
			if(is_object($pMessage)) {
				$message = $message -> toString();
			}
			$texte = "\n<h2>==== Trace d'appels : $message</h2>";
			$appels = debug_backtrace();
			$n = 0;
			$call = false;
			foreach ($appels as $key => $appel) {
				if($maxAppels > 0 and $n > $maxAppels) {
					break;
				} else {
					if (!$call) {
						$debtexte = '[' . $key . '] ';
					} else {
						$n++;
						$call = false;
					}
					if (isset($appel['file'])) {
						$debtexte.= '<strong>' . $appel['file'] . '</strong> ligne ' . $appel['line'];
						if (isset($appel['object'])) {
							$debtexte.= ' (<i>' . getTypeSG($appel['object']) . '</i>)';
						}
					} else {
						$call = true;
					}
					$fintexte = ' : ';
					if (isset($appel['class'])) {
						$fintexte.= $appel['class'];
					}
					if (isset($appel['type'])) {
						$fintexte.= $appel['type'];
					}
					if (isset($appel['function'])) {
						if ($appel['function'] !== 'call_user_func_array') {
							$fintexte.= $appel['function'];
						}
					}
					if ($call !== true) {
						$texte.= $debtexte . $fintexte . '<br>';
						$debtexte = '';
						$fintexte = '';
						$call = false;
					}
				}
			}
			$texte.= '==== FIN ====<br><br>';
			if (isset($_SESSION['debug']['texte'])) {
				$_SESSION['debug']['texte'].= $texte;
			} else {
				$_SESSION['debug']['texte'] = $texte;
			}
		}
		unset($_SESSION['tracerAppels']);
	}
}

/** 1.0.7
 * Détermine le type de l'objet SynerGaia passé, ou à défaut le type PHP
 * L'objet peut aussi avoir sa propre méthode getTypeSG() (voir SG_Notation, SG_Operation et SG_Formule)
 *
 * @param indéfini $pQuelqueChose objet
 * @return string type de l'objet
 */
function getTypeSG($pQuelqueChose = null) {
	$type = 'NULL';
	if (isset($pQuelqueChose)) {
		// Cherche le type "normal" :
		$type = gettype($pQuelqueChose);
		if (is_object($pQuelqueChose)) {
			if (method_exists($pQuelqueChose, 'getTypeSG')) {
				$type = $pQuelqueChose -> getTypeSG();
			} else {
				// on a un objet SynerGaia
				if (property_exists($pQuelqueChose, 'typeSG')) {
					$type = $pQuelqueChose -> typeSG;
				}
				if ($type === SG_Document::TYPESG) {
					// on a un @Document
					$type = $pQuelqueChose -> getValeur('@Type', SG_Document::TYPESG);
				}
			}
		}
	}
	// parfois enlever le code de base
	$i = strpos($type,'/');
	if ( $i !== false) {
		$type = substr($type, $i+1);
	}
	return $type;
}
/** 1.3.2 trace appel ; 2.3 trace appel minimum
 * Ajoute un message à la log et au debug si activé
 *
 * @param string : $pMessage Message à ajouter
 * @param boolean : Trace : ajouter la trace complète de l'appel à journaliser
 * @param integer : $pNiveau Niveau du message
 * @level 0
 */
function journaliser($pMessage = "", $pTrace = true, $pNiveau = SG_LOG::LOG_NIVEAU_INFO) {
	$appeltxt = '';
	$fonction = '';
	$fonctionprec = '';
	$appels = debug_backtrace();
	foreach ($appels as $appel) {
		if (isset($appel['file'])) {
			if ($appeltxt !== '') {
				$appeltxt.= '<=';
			}
			$fonction = substr($appel['file'], strrpos($appel['file'], '/') + 1);
			if ($fonction !== $fonctionprec) {
				$appeltxt.= $fonction;
				$fonctionprec = $fonction;
			}
			$appeltxt.=  '(' . $appel['line'] . ')';
		}
		if ($pTrace !== true) {
			break;
		}
	}
	$texte = $appeltxt . ' : (' . getTypeSG($pMessage) . ') ' . print_r($pMessage, true);
	$GLOBALS['SG_LOG'] -> log($texte, $pNiveau);
}

if (!function_exists('mime_content_type')) {
	function mime_content_type($filename) {
		$ret = 'application/octet-stream';
		$mime_types = array(
		// divers
		'txt' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html', 'php' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json', 'xml' => 'application/xml', 'swf' => 'application/x-shockwave-flash', 'flv' => 'video/x-flv',
		// images
		'png' => 'image/png', 'jpe' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'bmp' => 'image/bmp', 'ico' => 'image/vnd.microsoft.icon', 'tiff' => 'image/tiff', 'tif' => 'image/tiff', 'svg' => 'image/svg+xml', 'svgz' => 'image/svg+xml',
		// archives
		'zip' => 'application/zip', 'rar' => 'application/x-rar-compressed', 'exe' => 'application/x-msdownload', 'msi' => 'application/x-msdownload', 'cab' => 'application/vnd.ms-cab-compressed',
		// audio/video
		'mp3' => 'audio/mpeg', 'qt' => 'video/quicktime', 'mov' => 'video/quicktime',
		// adobe
		'pdf' => 'application/pdf', 'psd' => 'image/vnd.adobe.photoshop', 'ai' => 'application/postscript', 'eps' => 'application/postscript', 'ps' => 'application/postscript',
		// ms office
		'doc' => 'application/msword', 'rtf' => 'application/rtf', 'xls' => 'application/vnd.ms-excel', 'ppt' => 'application/vnd.ms-powerpoint',
		// open office
		'odt' => 'application/vnd.oasis.opendocument.text', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet');

		$ext = strtolower(array_pop(explode('.', $filename)));
		if (array_key_exists($ext, $mime_types)) {
			$ret = $mime_types[$ext];
		} elseif (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			$ret = $mimetype;
		} else {
			$ret = 'application/octet-stream';
		}
		journaliser('socle.mime_content_type ' . $ret);
		return $ret;
	}
}
/*
*   se charge de générer la page pour forcer le téléchargement d'un fichier dans tmp.
*   sortie: true -> c'est bon
* 
*   @param string $pFilename : nom du fichier
*/
function generate_download_page($pFilename = null) {
	// on désactive la compression en sortie
	$x = ini_set('zlib.output_compression', 0);

	header("Content-Type: " . mime_content_type($pFilename) . "; name=\"".$pFilename."\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize(SYNERGAIA_PATH_TO_ROOT . '/tmp/' . $pFilename));
	header("Content-Disposition: attachment; filename=\"".$pFilename."\"");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$ret = @readfile(SYNERGAIA_PATH_TO_ROOT . '/tmp/' . $pFilename);

	return $ret;
}
/**
* benchmark permpet de mesurer des temps d'exécution et un nombre de passage pour faire du benchmark
* 
* @param string nom du compteur
* @param boolean true : commencer la mesure et compter +1 ; false arrêter la mesure.
*/
function benchmark($pCompteur = 'cpt1', $pFlag = true) {
	if($pFlag) {
		if(!isset($_SESSION['benchmark'][$pCompteur])) {
			$_SESSION['benchmark'][$pCompteur]=array(microtime(true),0,1,-1,-1);
		} else {
			$tmp=$_SESSION['benchmark'][$pCompteur];
			// si on est déjà en train de compter sur ce compteur, on ne fait rien
			if ($tmp[0] == 0) {
				$tmp[0] = microtime(true);
				$tmp[2] += 1;
				$_SESSION['benchmark'][$pCompteur]=$tmp;
			}
		}
	} else {
		if(!isset($_SESSION['benchmark'][$pCompteur])) {
			$_SESSION['benchmark'][$pCompteur]=array(0,0,1,-1,-1);
		} else {
			$tmp=$_SESSION['benchmark'][$pCompteur];
			// si on est déjà arrêté sur ce compteur, on ne fait rien
			if ($tmp[0] !== 0) {
				$delta = microtime(true) - $tmp[0];
				$tmp[1] += $delta;
				$tmp[0] = 0;
				if ($tmp[3] < 0) {
					$tmp[3] = $delta;
				} elseif ($tmp[3] > $delta) {
					$tmp[3] = $delta;
				}
				if ($tmp[4] < 0) {
					$tmp[4] = $delta;
				} elseif ($tmp[4] < $delta) {
					$tmp[4] = $delta;
				}
				$_SESSION['benchmark'][$pCompteur]=$tmp;
			}
		}
	}
}
/** 1.0.6
 */
function faireObjetSynerGaia($pQuelqueChose = '', $pType = '') {
	if (is_null($pQuelqueChose)) {
		$ret = new SG_Rien();
	} else {
		if (is_array($pQuelqueChose)) {
			$ret = new SG_Collection();
			foreach ($pQuelqueChose as $valeur) {
				$ret -> elements[] = faireObjetSynerGaia($valeur);
			}
		} else {
			$type = getTypeSG($pQuelqueChose);
			if ($type === SG_Formule::TYPESG) {
				$ret = $pQuelqueChose -> calculer();
			} else {
				if ($type === 'string') {
					$ret = new SG_Texte($pQuelqueChose);
				} elseif ($type === 'integer' || $type === 'float') {
					$ret = new SG_Nombre($pQuelqueChose);
				} elseif ($type === 'bool') {
					$ret = new SG_VraiFaux($pQuelqueChose);
				} else {
					// TODO distinguer le cas des dates et des heures
					$ret = $pQuelqueChose;
				}
			}
			if ($pType !== '') {
				if ($type !== $pType) {                 
					if (SG_Dictionnaire::isObjetSysteme($type)) {
						$codeFonction = SG_Dictionnaire::getClasseObjet($pType);
						// instancie un nouvel objet SynerGaia
						$ret = new $codeFonction($ret);
					}
				}
			}
		}
	}
	return $ret;
}

/** 1.0.6
 * transforme la pvaleur donnée en paramètre en valeur booléenne.
 * @param indefini $pQuelqueChose valeur à traduire
 * @return booleen True ou False (si erreur non reconnu ou indéfini, retourne null)
 */
function getBooleanValue($pQuelqueChose) {
	$ret = $pQuelqueChose;
	$type = getTypeSG($pQuelqueChose);
	if ($type !== 'boolean') {
		if ($type === SG_Formule::TYPESG) {
			$ret = $pQuelqueChose -> calculer();
			$type = getTypeSG($ret);
		}
		if ($type === SG_VraiFaux::TYPESG) {
			$ret = $ret -> EstVrai();
		} elseif ($type === 'string') {
			if ($ret === 'oui' || $ret === 'OUI' || $ret = 'Oui' || $ret = 'true' || $ret = 'vrai') {
				$ret = true;
			} elseif  ($ret === 'non' || $ret === 'NON' || $ret = 'Non' || $ret = 'false' || $ret = 'faux') {
				$ret = false;
			}
		} else {
			$ret = null;
		}
	}
	return $ret;
}
//1.1 ajout
if (!function_exists('json_last_error_msg')) {
	function json_last_error_msg() {
		switch (json_last_error()) {
			default:
				return;
			case JSON_ERROR_DEPTH:
				$error = 'Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				$error = 'Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
		}
		return new SG_Erreur($error);
	}
}
?>
