<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/******************************* Includes *******************************/
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

if (!function_exists('mb_strtolower')) {
  function mb_strtolower ($string, $encoding) {
	return strtolower($string);
  }
}


class veolia_eau extends eqLogic {
    /******************************* Attributs *******************************/
    /* Ajouter ici toutes vos variables propre à votre classe */

    /***************************** Methode static ****************************/


    // Fonction exécutée automatiquement toutes les minutes par Jeedom
    // public static function cron() {
    //     foreach (eqLogic::byType('veolia_eau', true) as $veolia_eau) {
	// 			if ($veolia_eau->getIsEnable() == 1) {
	// 				if (!empty($veolia_eau->getConfiguration('login')) && !empty($veolia_eau->getConfiguration('password'))) {
	// 					$veolia_eau->getConso();
    //                     log::add('veolia_eau', 'debug', 'done... ');
	// 				} else {
	// 					log::add('veolia_eau', 'error', 'Identifiants non saisis');
	// 				}
	// 			}
	// 	}
    // }

    // Fonction exécutée automatiquement toutes les heures par Jeedom
    public static function cronHourly() {
		foreach (eqLogic::byType('veolia_eau', true) as $veolia_eau) {
            $heure_releve = intval($veolia_eau->getConfiguration('heure'));
            if ($heure_releve > 23) $heure_releve = 6;
            log::add('veolia_eau', 'debug', 'heure de relève: '.$heure_releve);
            if (date('G') == $heure_releve) {
				if ($veolia_eau->getIsEnable() == 1) {
					if (!empty($veolia_eau->getConfiguration('login')) && !empty($veolia_eau->getConfiguration('password'))) {
						$veolia_eau->getConso();
                        log::add('veolia_eau', 'debug', 'done... ');
					} else {
						log::add('veolia_eau', 'error', 'Identifiants non saisis');
					}
				}
			}
		}
    }

    // Fonction exécutée automatiquement tous les jours par Jeedom
    public static function cronDayly() {
    }

    /*************************** Methode d'instance **************************/


    /************************** Pile de mise à jour **************************/

    /* fonction permettant d'initialiser la pile
     * plugin: le nom de votre plugin
     * action: l'action qui sera utilisé dans le fichier ajax du pulgin
     * callback: fonction appelé coté client(JS) pour mettre à jour l'affichage
     */
    public function initStackData() {
        nodejs::pushUpdate('veolia_eau::initStackDataEqLogic', array('plugin' => 'veolia_eau', 'action' => 'saveStack', 'callback' => 'displayEqLogic'));
    }

    /* fonnction permettant d'envoyer un nouvel équipement pour sauvegarde et affichage,
     * les données sont envoyé au client(JS) pour être traité de manière asynchrone
     * Entrée:
     *      - $params: variable contenant les paramètres eqLogic
     */
    public function stackData($params) {
        if(is_object($params)) {
            $paramsArray = utils::o2a($params);
        }
        nodejs::pushUpdate('veolia_eau::stackDataEqLogic', $paramsArray);
    }

    /* fonction appelé pour la sauvegarde asynchrone
     * Entrée:
     *      - $params: variable contenant les paramètres eqLogic
     */
    public function saveStack($params) {
        // inserer ici le traitement pour sauvegarde de vos données en asynchrone
    }

    /* fonction appelé avant le début de la séquence de sauvegarde */
    public function preSave() {
    }

    /* fonction appelé pendant la séquence de sauvegarde avant l'insertion
     * dans la base de données pour une mise à jour d'une entrée */
    public function preUpdate() {
		if (empty($this->getConfiguration('login'))) {
			throw new Exception(__('L\'identifiant ne peut pas être vide',__FILE__));
		}

		if (empty($this->getConfiguration('password'))) {
			throw new Exception(__('Le mot de passe ne peut etre vide',__FILE__));
		}
    }

    /* fonction appelé pendant la séquence de sauvegarde après l'insertion
     * dans la base de données pour une mise à jour d'une entrée */
    public function postUpdate() {
		$cmdlogic = veolia_eauCmd::byEqLogicIdAndLogicalId($this->getId(), 'index');
		if (!is_object($cmdlogic)) {
			$veolia_eauCmd = new veolia_eauCmd();
			$veolia_eauCmd->setName(__('Index', __FILE__));
			$veolia_eauCmd->setEqLogic_id($this->id);
			$veolia_eauCmd->setLogicalId('index');
			$veolia_eauCmd->setConfiguration('data', 'index');
			$veolia_eauCmd->setType('info');
			$veolia_eauCmd->setSubType('numeric');
			$veolia_eauCmd->setUnite('L');
			$veolia_eauCmd->setIsHistorized(1);
			$veolia_eauCmd->save();
		}

		$cmdlogic = veolia_eauCmd::byEqLogicIdAndLogicalId($this->getId(), 'conso');
		if (!is_object($cmdlogic)) {
			$veolia_eauCmd = new veolia_eauCmd();
			$veolia_eauCmd->setName(__('Consommation', __FILE__));
			$veolia_eauCmd->setEqLogic_id($this->id);
			$veolia_eauCmd->setLogicalId('conso');
			$veolia_eauCmd->setConfiguration('data', 'conso');
			$veolia_eauCmd->setType('info');
			$veolia_eauCmd->setSubType('numeric');
			$veolia_eauCmd->setUnite('L');
			$veolia_eauCmd->setIsHistorized(1);
			$veolia_eauCmd->save();
		}

		$cmdlogic = veolia_eauCmd::byEqLogicIdAndLogicalId($this->getId(), 'typeReleve');
		if (!is_object($cmdlogic)) {
			$veolia_eauCmd = new veolia_eauCmd();
			$veolia_eauCmd->setName(__('Mesuré / Estimé', __FILE__));
			$veolia_eauCmd->setEqLogic_id($this->id);
			$veolia_eauCmd->setLogicalId('typeReleve');
			$veolia_eauCmd->setConfiguration('data', 'typeReleve');
			$veolia_eauCmd->setType('info');
			$veolia_eauCmd->setSubType('string');
			$veolia_eauCmd->setIsHistorized(0);
			$veolia_eauCmd->save();
		}

		$cmdlogic = veolia_eauCmd::byEqLogicIdAndLogicalId($this->getId(), 'dateReleve');
		if (!is_object($cmdlogic)) {
			$veolia_eauCmd = new veolia_eauCmd();
			$veolia_eauCmd->setName(__('Date', __FILE__));
			$veolia_eauCmd->setEqLogic_id($this->id);
			$veolia_eauCmd->setLogicalId('dateReleve');
			$veolia_eauCmd->setConfiguration('data', 'dateReleve');
			$veolia_eauCmd->setType('info');
			$veolia_eauCmd->setSubType('string');
			$veolia_eauCmd->setIsHistorized(0);
			$veolia_eauCmd->save();
		}
    }

    /* fonction appelé pendant la séquence de sauvegarde avant l'insertion
     * dans la base de données pour une nouvelle entrée */
    public function preInsert() {
    }

    /* fonction appelé pendant la séquence de sauvegarde après l'insertion
     * dans la base de données pour une nouvelle entrée */
    public function postInsert() {
    }

    /* fonction appelé après la fin de la séquence de sauvegarde */
    public function postSave() {
    }

    /* fonction appelé avant l'effacement d'une entrée */
    public function preRemove() {
    }

    /* fonnction appelé après l'effacement d'une entrée */
    public function postRemove() {
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {
      }
     */

    /*     * **********************Getteur Setteur*************************** */

	public function getConso() {
		$cookie_file = sys_get_temp_dir().'/veolia_php_cookies_'.uniqid();
		static::secure_touch($cookie_file);

        switch (intval($this->getConfiguration('website'))) {
            case 2:
                $url_login = 'https://www.eau-services.com/default.aspx';
                // on ne peux avoir le csv que de deux jours en arrière
                // le csv mensuel pas de données pour le dernier jour
                // le csv par heure pas de données pour 0-1H
                // ex=mm/YYYY
                // mm=mm/YYYY
                // d=dd moins deux jours
                $releve = mktime(0, 0, 0, date("m")  , date("d")-3, date("Y"));
                $month = date('m/Y',$releve);
                $day = date('d',$releve);
                log::add('veolia_eau', 'debug',  $month.' '.$day);
                $url_consommation = 'https://www.eau-services.com/mon-espace-suivi-personnalise.aspx?mm='.$month.'&d=';
                $url_releve_csv = 'https://www.eau-services.com/mon-espace-suivi-personnalise.aspx?ex='.$month.'&mm='.$month.'&d=';
                log::add('veolia_eau', 'debug',  $url_releve_csv);
                $datas = array(
                    'login='.urlencode($this->getConfiguration('login')),
                    'pass='.urlencode($this->getConfiguration('password')),
                    'connect=OK',
                );
                $extension='.csv';
                break;

            case 3:
                $url_login = 'https://agence.eaudugrandlyon.com/default.aspx';
                $url_consommation = 'https://agence.eaudugrandlyon.com/mon-espace-suivi-personnalise.aspx';
                $url_releve_csv = 'https://agence.eaudugrandlyon.com/mon-espace-suivi-personnalise.aspx?ex=9/2016&mm=9/2016&d=';
                $datas = array(
                    'login='.urlencode($this->getConfiguration('login')),
                    'pass='.urlencode($this->getConfiguration('password')),
                    'connect=OK',
                );
                $extension='.csv';
                break;

           case 4:
                $url_login = "https://www.eau-en-ligne.com/security/signin";
                $url_consommation = "https://www.eau-en-ligne.com/ma-consommation/DetailConsoExcel";
                $url_releve_csv = "https://www.eau-en-ligne.com/ma-consommation/DetailConsoExcel";
                $datas = array(
                    'signin[username]='.urlencode($this->getConfiguration('login')),
                    'signin[password]='.urlencode($this->getConfiguration('password')),
                    'x=47&y=19',
                );
                $extension='.xls';
                break;

			case 1:
			default:
                $url_login = 'https://www.service-client.veoliaeau.fr/home.loginAction.do';
                $url_consommation = 'https://www.service-client.veoliaeau.fr/home/espace-client/votre-consommation.html?vueConso=releves';
                $url_releve_csv = 'https://www.service-client.veoliaeau.fr/home/espace-client/votre-consommation.exportConsommationData.do?vueConso=releves';
                $datas = array(
                    'veolia_username='.urlencode($this->getConfiguration('login')),
                    'veolia_password='.urlencode($this->getConfiguration('password')),
                    'login=OK',
                );
                $extension='.xls';
        }

		$headers = array(
			"Accept: */*",
			"Connection: Keep-Alive",
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_NOBODY, FALSE);
		curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

		curl_setopt($ch, CURLOPT_URL, $url_login);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $datas));

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);

		log::add('veolia_eau', 'debug', '### LOGIN ###');
		log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));
		log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));

		$htm_file = sys_get_temp_dir().'/veolia_html_'.uniqid().'.htm';
		static::secure_touch($htm_file);

        $fp = fopen($htm_file, 'w');
        if ($fp) {
            curl_setopt($ch, CURLOPT_URL, $url_consommation);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            //curl_setopt($ch, CURLOPT_POST, FALSE);

            $response = curl_exec($ch);
            $info = curl_getinfo($ch);

            log::add('veolia_eau', 'debug', '### GO TO CONSOMMATION PAGE ###');
            log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));
            log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));
            fclose($fp);
        } else {
            log::add('veolia_eau', 'error', 'error on creating htm file "'.$htm_file.'"');
        }

		$data_file = sys_get_temp_dir().'/veolia_releve_'.uniqid().$extension;
		static::secure_touch($data_file);

        $fp = fopen($data_file, 'w');

		if ($fp) {
			curl_setopt($ch, CURLOPT_URL, $url_releve_csv);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FILE, $fp);

			$response = curl_exec($ch);
			$info = curl_getinfo($ch);

			log::add('veolia_eau', 'debug', '### GET DATAFILE ###');
			log::add('veolia_eau', 'debug', 'response length : '.strlen($response));
			log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));

			fclose($fp);

			//traitement du xls
			$this->traiteConso($data_file, $htm_file);
		} else {
			log::add('veolia_eau', 'error', 'error on creating file "'.$data_file.'"');
		}

		curl_close($ch);
		@unlink($cookie_file);
	}

	public function traiteConso($file, $htm_file) {

        $consomonth = [];
        $datasFetched = [];

        $alert = str_replace('#','',$this->getConfiguration('alert'));
        log::add('veolia_eau', 'debug', 'alert: '. $alert);

        switch (intval($this->getConfiguration('website'))) {
            case 2:
                log::add('veolia_eau', 'debug', '### TRAITE CONSO CSV '.$website.' ###');
                $depart = $this->getConfiguration('depart');
                $compteur = $this->getConfiguration('compteur');
				// -- format des data a decoder (y en litres)
					// dataPoints: [
				//  {y: 306, label: "01/10/2016"}
				//  ,
				//  {y: 602, label: "02/10/2016"}
				//  ]
						// -- Exception a gerer:
				// dataPoints: [
				//  {y: 0, color:"#c0bebf", label: "Non mesurée"},
				//  {y: 0, color:"#c0bebf", label: "Non mesurée"},
				//  {y: 0, color:"#c0bebf", label: "Non mesurée"}
				// ]
				// --
				// String cible: "306,01/10/2016,602,02/10/2016"
				// --
				// String en cas de non mesuree: "0,Nonmesurée,0,Nonmesurée,0,Nonmesurée"
				// --
                $html = file_get_contents($htm_file);
                $info = explode("dataPoints: [", $html,2);
                $info = explode("]", $info[1], 2);
                $info = str_replace(" ", "", $info[0]);
                $info = str_replace("\t,", "", $info);
                $info = str_replace("\t", "", $info);
                $info = str_replace("\r\n", "", $info);
                $info = str_replace("\n", "", $info);
                $info = str_replace("}{", "|", $info);
                $info = str_replace("}", "", $info);
                $info = str_replace("{", "", $info);
                $info = str_replace("y:", "", $info);
                $info = str_replace("label:", "", $info);
                $info = str_replace("color:\"#c0bebf\",", "", $info);
                $info = str_replace("\"", "", $info);
                $info = explode( "|", $info);
                //log::add('veolia_eau', 'debug', print_r($data, true));

                foreach ($info as $data) {
                    //log::add('veolia_eau', 'debug', print_r($data, true));
                    $data = explode(",", $data);

					// gerer le cas  "Non mesurée"
					// {y: 0, color:"#c0bebf", label: "Non mesurée"}
					// l espace a ete enleve par le str_replace(" ", "", $info[0]);
					if ($data[1]=="Nonmesurée") {
					  log::add('veolia_eau', 'debug', 'valeur non mesurée');
					  // verification que la donnee non mesuree ne se produit pas le dernier jour du mois, dans ce cas elle est perdu et ne sera pas ajoute le lendemain
					  $nm_currentreleve = mktime(0, 0, 0, date("m")  , date("d")-3, date("Y"));
					  $nm_month = date('m/Y',$nm_currentreleve);
					  $nm_nextreleve = mktime(0, 0, 0, date("m")  , date("d")-2, date("Y"));
					  $nm_nextmonth = date('m/Y',$nm_nextreleve);
					  if ($nm_month != $nm_nextmonth) {
					    log::add('veolia_eau', 'error', 'valeur non mesurée en fin de mois: la mesure sera perdu demain, il faut la recuperer avant minuit ou ensuite a la main et corriger la valeur dans history ainsi que la valeur compteur dans eqLogic');
					    // TODO: gerer ce cas automatiquement
					  }
					  break;
					}


					$dateTemp = explode('/', $data[1]);

					// Recuperation d autres cas potentiel ou ce champ ne serait pas une date pour eviter de fausser le compteur
					// verifie s il y a bien 2 slash
					if(count($dateTemp)!=3) {
						log::add('veolia_eau', 'error', 'date invalide - impossible de trouver 2 slash :'.$data[1]);
						break;
					}

					// verifie si la date est valide
					if(!checkdate($dateTemp[1],$dateTemp[0],$dateTemp[2])){
						log::add('veolia_eau', 'error', 'date invalide:'.$data[1]);
						break;
					}

					// transform d/m/yyyy to yyy-mm-dd with leading 0
					$date = $dateTemp[2].'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT);
					//$index = 0;
					$conso = $data[0];
					$consomonth[] = $conso;
					$typeReleve = 'M';

					$compteur += $conso;
					$index = $depart + $compteur;
					log::add('veolia_eau', 'debug', $date.' '.$conso.' '.$typeReleve.' '.$compteur.' '.$index);

                    $datasFetched[] = array(
                        'date' => $date,
                        'index' => $index,
                        'conso' => $conso,
                        'typeReleve' => $typeReleve
                    );
				}

                break;

            case 3:
                log::add('veolia_eau', 'debug', '### TRAITE CONSO CSV '.$website.' ###');
                break;

            case 4:
                require_once dirname(__FILE__).'/../../3rparty/PHPExcel/Classes/PHPExcel/IOFactory.php';

                $objPHPExcel = PHPExcel_IOFactory::load($file);

                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

                log::add('veolia_eau', 'debug', '### TRAITE CONSO XLS TOUT SUR MON EAU '.$website.' ### '.$lastdate);

                if (is_array($sheetData) && count($sheetData)) {
                    $entete = array_shift($sheetData);

                    if (count($sheetData)) {
                        log::add('veolia_eau', 'debug', count($sheetData).' data lines');

                        foreach ($sheetData as $line) {
                            $conso = $line['B']*1000;

                            if ($conso == 0) continue;

                            $dateTemp = explode('-', $line['A']);
                            $date = $dateTemp[2].'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT);
                            $index = $line['C'] * 1000;
			    			$consomonth[] = $conso;
                            $typeReleve = '';

                            $datasFetched[] = array(
                                'date' => $date,
                                'index' => $index,
                                'conso' => $conso,
                                'typeReleve' => $typeReleve
                            );
                        }
                    } else {
                        log::add('veolia_eau', 'error', 'Aucune donnée, merci de vérifier que vos identifiants sont corrects et que vous avez accès au télérelevé Veolia');
                    }
                } else {
                    log::add('veolia_eau', 'debug', 'empty data');
                }

            break;

			case 1:
			default:
                log::add('veolia_eau', 'debug', '### TRAITE CONSO XLS '.$website.' ###');

                require_once dirname(__FILE__).'/../../3rparty/PHPExcel/Classes/PHPExcel/IOFactory.php';

                $objPHPExcel = PHPExcel_IOFactory::load($file);

                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

                if (is_array($sheetData) && count($sheetData)) {
                    $entete = array_shift($sheetData);

                    if (count($sheetData)) {
                        log::add('veolia_eau', 'debug', count($sheetData).' data lines');

                        foreach ($sheetData as $line) {
                            $dateTemp = explode('/', $line['A']);
                            $date = $dateTemp[2].'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT);
                            $index = $line['B'];
                            $conso = $line['C'];
			    			$consomonth[] = $conso;
                            $typeReleve = $line['D'];

                            $datasFetched[] = array(
                                'date' => $date,
                                'index' => $index,
                                'conso' => $conso,
                                'typeReleve' => $typeReleve
                            );
                        }
                    } else {
                        log::add('veolia_eau', 'error', 'Aucune donnée, merci de vérifier que vos identifiants sont corrects et que vous avez accès au télérelevé Veolia');
                    }
                } else {
                    log::add('veolia_eau', 'debug', 'empty data');
                }
        }

        foreach ($datasFetched as $data) {
            log::add('veolia_eau', 'debug', 'Date: '.$data['date'].' / Index: '.$data['index'].' / Conso: '.$data['conso'].' / Type de relevé: '.$data['typeReleve']);

            if ($data['index'] > 0 ) {
                $cmd = $this->getCmd(null, 'index');

                if (is_object($cmd)) {
                    $cmd->event($data['index'], $data['date']);
                }

                $cmd = $this->getCmd(null, 'conso');

                if (is_object($cmd)) {
                    $cmd->event($data['conso'], $data['date']);
                }

                $cmd = $this->getCmd(null, 'typeReleve');

                if (is_object($cmd)) {
                    $cmd->event($data['typeReleve'], $data['date']);
                }

                $cmd = $this->getCmd(null, 'dateReleve');

                if (is_object($cmd)) {
                    $cmd->event($data['date'], $data['date']);
                }
            }
        }

        $maxday = $this->getConfiguration('maxday');
        $maxmonth = $this->getConfiguration('maxmonth');

        if (!empty($maxday) && $conso >= $maxday && $alert != '') {
            $cmdalerte = cmd::byId($alert);
            $options['title'] = "Alerte Conso Eau";
            $options['message'] = "Conso journalière du ".$date. ": ".$conso." litres";
            log::add('veolia_eau', 'debug', $options['message']);
            $cmdalerte->execCmd($options);
        }

        $consomonth = array_sum(array_slice($consomonth, -30));
        if (!empty($maxmonth) && $consomonth >= $maxmonth && $alert != '') {
            $cmdalerte = cmd::byId($alert);
            $options['title'] = "Alerte Conso Eau";
            $options['message'] = "Conso mensuelle: ".$consomonth." litres";
            log::add('veolia_eau', 'debug', $options['message']);
            $cmdalerte->execCmd($options);
        }

        if (!empty($compteur)) {
            $this->setConfiguration('compteur', $compteur);
            $this->save(true);
        }

		@unlink($file);
		@unlink($htm_file);
	}

	private static function secure_touch($fname) {
		if (file_exists($fname)) {
			return;
		}
		$temp = tempnam(sys_get_temp_dir(), 'VEOLIA');
		rename($temp, $fname);
	}
}

class veolia_eauCmd extends cmd {
    /******************************* Attributs *******************************/
    /* Ajouter ici toutes vos variables propre à votre classe */

    /***************************** Methode static ****************************/

    /*************************** Methode d'instance **************************/

    /* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
    public function dontRemoveCmd() {
        return true;
    }
    */

    public function execute($_options = array()) {

    }

    /***************************** Getteur/Setteur ***************************/


}

?>
