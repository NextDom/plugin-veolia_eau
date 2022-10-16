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
//require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

if (!function_exists('mb_strtolower')) {
    function mb_strtolower ($string, $encoding) {
        return strtolower($string);
    }
}

if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding ($str, $to_encoding, $from_encoding = "auto") {
        return $str;
    }
}


class veolia_eau extends eqLogic {
    /******************************* Attributs *******************************/
    /* Ajouter ici toutes vos variables propre à votre classe */
    /***************************** Methode static ****************************/
    // Si mode debug, lancer le plugin toutes les minutes
    public static function cron() {
        if (log::getLogLevel('veolia_eau') == 100) {
             self::cronHourly();
        }
    }
	  // Fonction d'info des dependances
	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = '/tmp/dependancy_veolia_in_progress';
		$return['state'] = 'ok';
		if (exec('php -v | grep "PHP 7." | wc -l') === 1
            && exec('apt list --installed php7.0-mbstring | grep -E "mbstring"| wc -l') < 1) {
			$return['state'] = 'nok';
		}
		return $return;
	}
		  // Fonction d'install des dependances
	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('veolia') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

    // Fonction exécutée automatiquement toutes les heures par Jeedom
    public static function cronHourly() {
		foreach (eqLogic::byType('veolia_eau', true) as $veolia_eau) {
            $heure_releve = intval($veolia_eau->getConfiguration('heure'));
            if ($heure_releve > 23) $heure_releve = 6;

            log::add('veolia_eau', 'debug', 'heure de relève: '.$heure_releve);
            if (date('G') == $heure_releve) {
				if ($veolia_eau->getIsEnable() == 1) {
					if (!empty($veolia_eau->getConfiguration('login')) && !empty($veolia_eau->getConfiguration('password'))) {
                        $veolia_eau->getConso(0);
                        log::add('veolia_eau', 'debug', 'done... ');
					} else {
					    log::add('veolia_eau', 'error', 'Identifiants non saisis');
					}
				}
			}
		}
    }

    // Fonction exécutée automatiquement tous les jours par Jeedom
    //public static function cronDayly() {
    //}

    /**
      * @param $offsetVeoliaDate
     * @param $refDate --> time() or date in the past
     * @return $compteurEndPrevMonth
     */
    public function calculCompteurEndLastMonth($refDate)
    {
        $depart_compteur = $this->getConfiguration('depart');
        $eqLogicId = $this->getId();
        # Recuperation de l ID de index
        $cmdId = cmd::byEqLogicIdAndLogicalId($eqLogicId, 'index')->getId();
        log::add('veolia_eau', 'debug', '$cmdId:' . $cmdId);
        # calcul de la date de recuperation des données
        $currentdatenum = $refDate;
        # Calcul du dernier jour du mois d'avant
        $LastDayLastMonth = date('Y-m-d', strtotime('last day of last month', $currentdatenum));
        # Recuperation de l'historique
        $debut = date("Y-m-d H:i:s", strtotime($LastDayLastMonth));
        log::add('veolia_eau', 'debug', '$debut:' . $debut);
        $fin = date("Y-m-d H:i:s", strtotime($LastDayLastMonth));
        log::add('veolia_eau', 'debug', '$fin:' . $fin);
        $value = history::all($cmdId, $debut, $fin);

        // TODO faire une fonction qui efface l histo du mois en cours ou forcer historique pas moyenné
        // TODO Ou mettre Index en history max et pas moyenne
        // TODO Sinon des qu'il y a 2 valeurs <> l'index devient la moyenne des 2
        // $value_date_time = history::byCmdIdDatetime(  $cmdId, $debut);
        //log::add('veolia_eau', 'debug', 'value_date_time:' . $value_date_time->getValue());
        //log::add('veolia_eau', 'debug', 'value[0]:' . $value[0]->getValue().'count:'.count(value));


        if (count($value) == 1) {
            $item = $value[0];
            $dateval = $item->getDatetime();
            $compteurEndPrevMonth = $item->getValue();
            log::add('veolia_eau', 'debug', '$compteurEndPrevMonth=1:' . $compteurEndPrevMonth);

        } elseif (count($value) > 0) {
            $item = $value[0];
            $dateval = $item->getDatetime();
            $compteurEndPrevMonth = $item->getValue();
            foreach ($value as $item) {
                log::add('veolia_eau', 'debug', '$compteurEndPrevMonth>0:' . $compteurEndPrevMonth . "count" . count($value) . "itemval" . $item->getValue());
            }
        } else {
            // If prev month empty --> assumption plugin just installed
            // compteur = start
            $dateval = 0;
            $compteurEndPrevMonth = $depart_compteur;
            log::add('veolia_eau', 'debug', '$compteurEndPrevMonth=0: ' . $compteurEndPrevMonth);
        }
        return $compteurEndPrevMonth;
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
        if (is_object($params)) {
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
        $this->setIsEnable(1);
        $this->setIsVisible(1);
        if ($this->getConfiguration('depart') == "" ) {
            $this->setConfiguration('depart', '0');
        }

        if ($this->getConfiguration('last') == "" ) {
            $lastdatenum = time();
            $monthCur = date("F",$lastdatenum);
            $FirstDayMonth = strtotime("first day of ".$monthCur, $lastdatenum);
            $lastdate = date("Y-m-d",$FirstDayMonth);
            // $lastdate = "2017-09-10";
            $this->setConfiguration('last',$lastdate);
        }
        if ($this->getConfiguration('offsetVeoliaDate') == ""){
            $this->setConfiguration('offsetVeoliaDate',3);
        }

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

	public function getConso($mock_test) {
        // Add ability to mock and tests the process without Jeedom
        // $mock_test=0: Normal process
        // $mock_test=1: Run automated tests with direct call to veolia
        // $mock_test=2: Run automated tests with mocked files
        // $mock_test=3: Run automated tests with mocked files and change of month
        $cookie_file = sys_get_temp_dir().'/veolia_php_cookies_'.uniqid();
        // log::add('veolia_eau', 'debug',  $cookie_file );
		static::secure_touch($cookie_file);

        $offsetVeoliaDate=$this->getConfiguration('offsetVeoliaDate');
		$getConsoInHtmlFile = true;
        $website=intval($this->getConfiguration('website'));
        $url_token=0; // n etait pas initialisé dans tous les cas
        $releve=0; // Utilise par Veolia sudest et Lyon pour la date du releve, permet de recuperer l historique
        if ($website == 1) {
            $nom_fournisseur = 'Veolia';
            $url_site = 'www.service.eau.veolia.fr';
        } elseif ($website == 2) {
            $nom_fournisseur = 'Veolia Méditerranée';
            $url_site = 'www.eau-services.com';
        } elseif ($website == 3) {
            $nom_fournisseur = 'Service Eau du Grand Lyon';
            $url_site = 'agence.eaudugrandlyon.com';
        } elseif ($website == 4) {
            $nom_fournisseur = 'Tout sur mon eau / Eau en ligne';
            $url_site = 'www.toutsurmoneau.fr';
        } elseif ($website == 6) {
			// SEE
            $nom_fournisseur = 'Société des eaux de l\'Essonne';
            // $url_site = 'www.eauxdelessonne.com'; // Fermeture du site depuis le 1er juillet 2019.
            $url_site = 'www.toutsurmoneau.fr';
        } elseif ($website == 7) {
            $nom_fournisseur = 'VEND\'Ô - Tout sur mon eau';
            $url_site = 'vendo.toutsurmoneau.fr';
        } elseif ($website == 8) {
            $nom_fournisseur = 'Eau de Sénart';
            $url_site = 'www.eauxdesenart.com';
        } elseif ($website == 9) {
            $nom_fournisseur = 'Stéphanoise des Eaux';
            $url_site = 'www.stephanoise-eaux.fr';
        } elseif ($website == 10) {
            $nom_fournisseur = 'Seynoise des Eaux';
            $url_site = 'www.seynoisedeseaux.fr';
        } elseif ($website == 11) {
            $nom_fournisseur = 'Orléanaise des Eaux';
            $url_site = 'www.orleanaise-des-eaux.fr';
        }  elseif ($website == 12) {
            $nom_fournisseur = 'Société des Eaux de l\'Ouest Parisien (SEOP)';
            $url_site = 'www.seop.fr';
        }  elseif ($website == 13) {
            $nom_fournisseur = 'L\'eau du Dunkerquois';
            $url_site = 'www.eaux-dunkerque.fr';
        } else {
			$nom_fournisseur = '';
            $url_site = 'not defined';
        }
        switch ($website) {
            case 2:
            case 3:
            // Algo: Process HTML and CSV and compare results
            // It will allow a progressive migration to csv
            // index is not provided, it is calculated from the begining
            // of the month, last value of previous month is provided in input
                $url_login = 'https://'.$url_site.'/default.aspx';
                // on ne peux avoir le csv que de deux jours en arrière
                // le csv mensuel pas de données pour le dernier jour
                // le csv par heure pas de données pour 0-1H
                // ex=mm/YYYY
                // mm=mm/YYYY
                // d=dd moins deux/trois jours

                // Calcul du dernier jour du mois
                // Si $lastdate n'est pas au dernier jour du mois
                // et que la date calculé $releve est au mois suivant
                // il manque la fin du mois passé. il faut passer $releve
                // au dernier jour du mois passé (last)
                $lastdate = $this->getConfiguration('last');
                //log::add('veolia_eau', 'debug','$lastdate: '.$lastdate);
                $lastdatenum = strtotime($lastdate);
                $monthLast = date("F",$lastdatenum);
                $LastDayMonth = strtotime("last day of ".$monthLast, $lastdatenum);
                $EndMonth = $LastDayMonth-$lastdatenum;

                // log::add('veolia_eau', 'debug',  $LastDayMonth.' '.$monthLast.' '.$lastdate);

                if ($mock_test >= 1) {
                  $currentdate=$this->getConfiguration('mock_date');
                  // log::add('veolia_eau', 'debug',' $currentdate:'.$currentdate);
                  $currentdatenum=strtotime($currentdate);
                } else {
                    $currentdatenum=time();
                }

                $releve = mktime(0, 0, 0, date("m",$currentdatenum)  , date("d",$currentdatenum)-$offsetVeoliaDate, date("Y",$currentdatenum));
                $monthReleve = date('F',$releve);
                // log::add('veolia_eau', 'debug',' $monthReleve:'.$monthReleve);

                if ($EndMonth != 0 && $monthReleve != $monthLast) {
                    $releve = mktime(0, 0, 0, date("m",$lastdatenum)  , date("d",$lastdatenum), date("Y",$lastdatenum));
                    if ($currentdatenum - $lastdatenum > 5*24*3600) { # on attend la mesure 5 jours
                        log::add('veolia_eau', 'debug','Detection de retard de veolia en fin de mois, on attend la mesure: '.  $monthReleve.' '.$monthLast.' '.$EndMonth);
                    } else {
                        log::add('veolia_eau', 'error',  'Mesure du '.date('Y-m-d',$releve).' perdu, pas disponible chez veolia');
                    }
                } elseif ($EndMonth == 0 && $monthReleve != $monthLast){ // il manque plusieurs mois, on passe au mois apres last (+1 jour)
                    $releve = mktime(0, 0, 0, date("m",$lastdatenum+3600*24)  , date("d",$lastdatenum+3600*24), date("Y",$lastdatenum+3600*24));
                    log::add('veolia_eau', 'debug','Il manque 1 ou plusieurs mois:'.  $monthReleve.' '.$monthLast.' '.$EndMonth);
                }

                $month = date('m/Y',$releve);
                $day = date('d',$releve);
                log::add('veolia_eau', 'debug',  $month.' '.$day);
                $url_consommation = 'https://'.$url_site.'/mon-espace-suivi-personnalise.aspx?mm='.$month.'&d=';
                $url_releve_csv = 'https://'.$url_site.'/mon-espace-suivi-personnalise.aspx?ex='.$month.'&mm='.$month.'&d=';
                log::add('veolia_eau', 'debug',  $url_releve_csv);
                $datas = array(
                    'login='.urlencode($this->getConfiguration('login')),
                    'pass='.urlencode($this->getConfiguration('password')),
                    'connect=OK',
                );
                $extension='.csv';
                break;

			// Sites basés sur "Tout sur mon eau" du groupe SUEZ.
			case 4:
			case 6:
			case 7:
			case 8:
			case 9:
			case 10:
			case 11:
			case 12:
            case 13:
				$url_token = 'https://'.$url_site.'/mon-compte-en-ligne/je-me-connecte';
                $tokenFieldName = '_csrf_token';
                $url_login = 'https://'.$url_site.'/mon-compte-en-ligne/je-me-connecte';
                $url_consommation = 'https://'.$url_site.'/mon-compte-en-ligne/historique-de-consommation';
                $getConsoInHtmlFile = false;
                $datas = array(
                    'tsme_user_login[_username]='.urlencode($this->getConfiguration('login')),
                    'tsme_user_login[_password]='.urlencode($this->getConfiguration('password'))
                );
                $extension='.xls';
                break;

			case 5:
				$url_login = 'https://espaceclients.eaudemarseille-metropole.fr/webapi/Utilisateur/authentification';
				$url_consommation = 'https://www.toutsurmoneau.fr/mon-compte-en-ligne/historique-de-consommation';
				$getConsoInHtmlFile = false;
				$datas = array(
					'identifiant='.urlencode($this->getConfiguration('login')),
					'motDePasseMD5='.urlencode(md5($this->getConfiguration('password')))
				);
				$extension='.xls';
				break;

            case 1:
            default:
                $url_token = 'https://www.service.eau.veolia.fr/connexion-espace-client.html';
                $tokenFieldName = 'token';
                $url_login = 'https://www.service.eau.veolia.fr/home.loginAction.do';
                $url_consommation = 'https://www.service.eau.veolia.fr/home/espace-client/votre-consommation.html?vueConso=releves';
                $url_releve_csv = 'https://www.service.eau.veolia.fr/home/espace-client/votre-consommation.exportConsommationData.do?vueConso=releves';
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
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

		// récupérer le token CSRF généré en cas de besoin
		if ($url_token) {
          	log::add('veolia_eau', 'debug', '### GET CSRF TOKEN ON '.$url_token.' ###');
            curl_setopt($ch, CURLOPT_URL, $url_token);
            if ($mock_test >= 2) {
                $response = "tbd";
            } else {
                $response = curl_exec($ch);
            }

            log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));
            log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));

          	log::add('veolia_eau', 'debug', 'Extracting token');
            require_once dirname(__FILE__).'/../../3rparty/SimpleHtmlParser/simple_html_dom.php';
            $html = str_get_html($response);
            //// TODO: next line
            //< Notice: Trying to get property of non-object in /home/travis/build/[secure]/plugin-veolia_eau/core/class/veolia_eau_process.class.php on line 356
            // < Call Stack:
            //<     0.0001     243200   1. {main}() /home/travis/build/[secure]/plugin-veolia_eau/tests/testVeoliaEau.php:0
            //<     0.0020     565144   2. veolia_eau->getConso() /home/travis/build/[secure]/plugin-veolia_eau/tests/testVeoliaEau.php:16

            $token = $html->find('input[name='.$tokenFieldName.']', 0)->value;
            log::add('veolia_eau', 'debug', 'Token: '.$token);

            if ($token !== '') {
                array_push($datas, $tokenFieldName.'='.$token);
            }
        }

		log::add('veolia_eau', 'debug', '### LOGIN ON '.$url_login.' ###');
		curl_setopt($ch, CURLOPT_URL, $url_login);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $datas));

		if ($mock_test >= 2) {
            $response = "tbd";
        } else {
            $response = curl_exec($ch);
        }

        log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));
		log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));
		log::add('veolia_eau', 'debug', '### GO TO CONSOMMATION PAGE ###');

		if ($getConsoInHtmlFile) {
			$htm_file = sys_get_temp_dir().'/veolia_html_'.uniqid().'.htm';
			static::secure_touch($htm_file);

			$fp = fopen($htm_file, 'w');
			if ($fp) {
				curl_setopt($ch, CURLOPT_URL, $url_consommation);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FILE, $fp);
                
                $idAbt = $this->getConfiguration('idAbt', 0);
                if ($idAbt) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, 'idAbt='.$idAbt);
                }        

                if ($mock_test >= 2) {
                    $response = 1;
                    $htm_file=$this->getConfiguration('mock_file');
                } else {
                    $response = curl_exec($ch);
                }

				log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));
				log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));
				fclose($fp);
			} else {
				log::add('veolia_eau', 'error', 'error on creating htm file "'.$htm_file.'"');
			}
		} else {
			curl_setopt($ch, CURLOPT_URL, $url_consommation);
			curl_setopt($ch, CURLOPT_POST, FALSE);

            if ($mock_test >= 2) {
                $response = "tbd";
            } else {
                $response = curl_exec($ch);
            }

			log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));
			log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));

			// extraction du token de téléchargement pour ToutSurMonEau et autres sites basés sur celui de SUEZ (Vend'Ô, Eau de Sénart, etc.)
			if ($website == 4 || $website == 6 || $website == 7 || $website == 8 || $website == 9 || $website == 10 || $website == 11 || $website == 12 || $website == 13) {
                require_once dirname(__FILE__).'/../../3rparty/SimpleHtmlParser/simple_html_dom.php';
                $html = str_get_html($response);
                $monthlyReportUrl = $html->find('div[id=export] a', 0)->href;
                $downloadToken = substr($monthlyReportUrl, strrpos($monthlyReportUrl, '/') + 1);
                log::add('veolia_eau', 'debug', 'downloadToken : '.$downloadToken);
                $month = date('m');
                $year = date('Y');
                $url_releve_csv = 'https://'.$url_site.'/mon-compte-en-ligne/exporter-consommation/day/'.$downloadToken.'/'.$year.'/'.$month;
                log::add('veolia_eau', 'debug', 'url csv : '.$url_releve_csv);
			}
		}

        //if ($website != 2){
        // Inutile de recuperer le xls pour www.eau-services.com
    	  log::add('veolia_eau', 'debug', '### GET DATAFILE CSV ###');
          if($mock_test>=2){
              $data_file=$this->getConfiguration('csv_mock_file');
          } else {
              $data_file = sys_get_temp_dir().'/veolia_releve_'.uniqid().$extension;
              log::add('veolia_eau', 'debug', '### Create File '.$data_file);
    	      static::secure_touch($data_file);

              $fp = fopen($data_file, 'w');
		      if ($fp) {
                log::add('veolia_eau', 'debug', '### Curl call '.$url_releve_csv);
			    curl_setopt($ch, CURLOPT_URL, $url_releve_csv);
			    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			    curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_POST, TRUE);

                if($mock_test>=2){
                  $response = "tbd";
                }else{
                  $response = curl_exec($ch);
                }
                $error = curl_error($ch);

			    log::add('veolia_eau', 'debug', 'response : '.$response);
			    log::add('veolia_eau', 'debug', 'error : '.$error);
			    log::add('veolia_eau', 'debug', 'response length : '.strlen($response));
			    log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));

			    fclose($fp);

		     } else {
			   log::add('veolia_eau', 'error', 'error on creating file "'.$data_file.'"');
		     }
         }

		curl_close($ch);

        //traitement du xls

        $this->traiteConso($data_file, $htm_file, $mock_test, $offsetVeoliaDate, $currentdatenum, $releve, $nom_fournisseur, $url_site);
		@unlink($cookie_file);
	}

	public function traiteConso($file, $htm_file, $mock_test, $offsetVeoliaDate, $currentdatenum, $releve, $nom_fournisseur, $url_site) {
        $consomonth = [];
        $datasFetched = [];
        $htmlDatasFetched = [];
        $csvDataFetched = [];
        $conso = 0;
        $compteur = 0;
        $date = 0;

        $alert = str_replace('#','',$this->getConfiguration('alert'));
        log::add('veolia_eau', 'debug', 'alert: '. $alert);

        $website = intval($this->getConfiguration('website'));
        switch ($website) {
            case 2:
            case 3:
              if ($file!=""){
                $htmlDataFetched=static::processHtml($htm_file, $website, $compteur, $date, $offsetVeoliaDate, $mock_test, $lastdate, $currentdatenum, $nom_fournisseur, $url_site);
                //log::add('veolia_eau', 'debug', 'csvDataFetched:'.serialize($datasFetched));
                  if($htmlDataFetched==0){
                      log::add('veolia_eau', 'error',"Pas de données sur le site");
                      return -1;
                  }
                // Traitement du csv
                $csvDataFetched=static::processCSV($file,$website, $offsetVeoliaDate, $nom_fournisseur, $url_site);
                //log::add('veolia_eau', 'debug', 'csvDataFetched:'.serialize($csvDataFetched));

                // Comparaison csv html pour corriger les non mesuree du html
                 $i=0;
                 $j=0;
                 $keepI=-1;

                 if ($mock_test == 0) {
                     $compteurEndPrevMonth = self::calculCompteurEndLastMonth($releve); // recupere la valeur du dernier jour du mois passé
                     log::add('veolia_eau', 'debug', 'getConso-$compteurEndPrevMonth: ' . $compteurEndPrevMonth . " offsetVeoliaDate: " . $offsetVeoliaDate);
                 } else{
                     $compteurEndPrevMonth=$htmlDataFetched[0]["index"]-$htmlDataFetched[0]["conso"];
                 }
                 $previousIndex=$compteurEndPrevMonth;
                 foreach ($csvDataFetched as $dateCSV ) {

                   if ($i < count($htmlDataFetched) ){
                     $dataHtml = $htmlDataFetched[ $i ];
                    // log::add('veolia_eau', 'debug', '$i < count($htmlDataFetched) j'.$j." i:".$i." $datasFetched".serialize($datasFetched[$j]["index"]));

                    if ($dataHtml["date"] === $dateCSV["date"]){
                      if ($dataHtml["conso"] != $dateCSV["conso"]){
                          log::add('veolia_eau', 'error', '$dataHtml["date"]'.$dataHtml["date"].'$data<>'.$dataHtml["conso"].'$data<>'.$dateCSV["conso"]);
                      } else{
                          if ($website==3 && $i==0 ){ // Remove last day of month at the begining for Lyon
                          } else {
                          $dateCSV["index"]=($dateCSV["conso"]+$previousIndex);
                          $dateCSV["typeReleve"]="M";
                          $previousIndex=$dateCSV["index"];
                          $datasFetched[$j]=$dateCSV;
                         }
                      }
                  } else {
                        $newDateCSV = date("Y-m-d", strtotime(" +1 day",strtotime($oldDateCSV)));
                        //log::add('veolia_eau', 'error', '$newDateCSV'.$newDateCSV);

                        if($newDateCSV == $dataHtml["date"]) {
                            // add day with 0 conso
                            $datasFetched[$j]["date"]=$newDateCSV;
                            $datasFetched[$j]["index"]=$previousIndex;
                            $datasFetched[$j]["typeReleve"]="M";
                            $datasFetched[$j]["conso"]=0;
                            $j++;
                            // add next one skip into the previous if
                            $dateCSV["index"]=($dateCSV["conso"]+$previousIndex);
                            $dateCSV["typeReleve"]="M";
                            $previousIndex=$dateCSV["index"];
                            $datasFetched[$j]=$dateCSV;
                          log::add('veolia_eau','debug','Missing item detected in CSV for:'.$newDateCSV);
                          $i=$i+2;
                        } else {
                        // log::add('veolia_eau', 'error', 'date <> --- $dataHtml["date"]'.$dataHtml["date"].'$data<>'.$dataHtml["conso"].'$data<>'.$dateCSV["conso"]);

                              if ($dateCSV["conso"]<0){
                                $keepNegativeConso=$dateCSV["conso"];
                                $keepI=$i;
                              } elseif ($keepI==$i){ // Negatif a soustraire au suivant
                                $dateCSV["conso"]=($dateCSV["conso"]+$keepNegativeConso);
                                $dateCSV["index"]=($dateCSV["conso"]+$previousIndex);
                                $dateCSV["typeReleve"]="M";
                                $previousIndex=$dateCSV["index"];
                                $datasFetched[$j]=$dateCSV;
                              } else{
                                  log::add('veolia_eau', 'debug', 'html different du CSV - $dataHtml["date"]'.$dataHtml["date"].'$dateCSV["date"]'.$dateCSV["date"].'$data<>'.$dataHtml["conso"].'$data<>'.$dateCSV["conso"].'$i'.$i.'$keepI'.$keepI.'$j:'.$j);
                              }
                        }
                     $i--;
                     }

                     if (isset($datasFetched[$j])) { // fix travis undefined offset when CSV is negative
                       $compteur=$datasFetched[$j]["index"];
                       // log::add('veolia_eau', 'debug', '$compteur: '.$compteur );

                     }
                     $oldDateCSV=$dateCSV["date"];  // manage empty items into CSV
                     $i++; $j++;
                 } else{
                     log::add('veolia_eau', 'debug', 'html plus petit que le csv, csv:'.count($csvDataFetched)." html:".count($htmlDataFetched)." i:".$i);
                 }
                }

              } else{
                  $datasFetched=static::processHtml($htm_file, $website, $compteur, $date, $offsetVeoliaDate, $mock_test, $lastdate, $currentdatenum);
              }

              break;

			// Cas concernant les site de Suez, gardé séparé de Veolia (case 1) en cas de besoin de modification de code
            case 4:
			case 6:
			case 7:
			case 8:
			case 9:
			case 10:
			case 11:
			case 12:
            case 13:
                $datasFetched=static::processCSV($file, $website, $nom_fournisseur, $url_site);
                break;

			case 1:
			default:
                $datasFetched=static::processCSV($file, $website, $nom_fournisseur, $url_site);

        }
        if (is_array($datasFetched)){
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
            log::add('veolia_eau', 'debug', 'save compteur: ' . $compteur);
            $this->setConfiguration('compteur', $compteur);
            $this->save(true);
        }

        if (!empty($date)) {
             if ($date >=$lastdate){
                 log::add('veolia_eau', 'debug', 'save last: '. $date);
                 $this->setConfiguration('last', $date);
                 $this->save(true);
             }
        }

        if ($mock_test == 0) {
		    @unlink($file);
		    @unlink($htm_file);
        }
	}

	private static function secure_touch($fname) {
		if (file_exists($fname)) {
			return;
		}

		$temp = tempnam(sys_get_temp_dir(), 'VEOLIA');
		rename($temp, $fname);
	}

    private static function processCSV($csv_file, $website, $nom_fournisseur, $url_site) {
      $consomonth = [];
      $datasFetched = [];
      $conso = 0;

      log::add('veolia_eau', 'debug', '### TRAITE CONSO XLS '.$website.' ###');
      require_once dirname(__FILE__).'/../../3rparty/PHPExcel/Classes/PHPExcel/IOFactory.php';
      if ($website ==2 || $website == 3) {
          $objReader = PHPExcel_IOFactory::createReader("CSV");
          $objReader->setDelimiter(";");
          try {
            $objPHPExcel = $objReader->load( $csv_file );
          } catch(Exception $e) {
              log::add('veolia_eau', 'error',$e->getMessage());
            return 0;
          }
      } else {
          try{
            $objPHPExcel = PHPExcel_IOFactory::load($csv_file);
        } catch(Exception $e) {
            log::add('veolia_eau', 'error',$e->getMessage());
          return 0;
        }
      }

      $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

      if (is_array($sheetData) && count($sheetData)) {
          $entete = array_shift($sheetData);
          log::add('veolia_eau', 'debug', count($sheetData).' data lines');

          if (count($sheetData)) {
              log::add('veolia_eau', 'debug', count($sheetData).' data lines');

              foreach ($sheetData as $line) {
                  $dateTemp = explode('/', $line['A']);
                  if ($website ==2 || $website == 3) {
                      $date = $dateTemp[2].'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT);
                      $index = 0;
                      $conso = $line['B'];
                      $typeReleve = 0;
                  }
                  elseif($website == 4 || $website == 6 || $website == 7 || $website == 8 || $website == 9 || $website == 10 || $website == 11 || $website == 12 || $website == 13) {
                      $dateTemp = explode('-', $line['A']);
                      $date = $dateTemp[2].'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT);
                      $index = $line['C'];
                      $conso = $line['B'] * 1000;
                      $typeReleve = 0;
                  } else {
                      $date = $dateTemp[2].'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT);
                      $index = $line['B'];
                      $conso = $line['C'];
                      $typeReleve = $line['D'];
                  }
                  $consomonth[] = $conso;
                  $datasFetched[] = array(
                      'date' => $date,
                      'index' => $index,
                      'conso' => $conso,
                      'typeReleve' => $typeReleve
                  );
              }
          } else {
              log::add('veolia_eau', 'error', 'Aucune donnée, merci de vérifier que vos identifiants sont corrects et que vous avez accès au télérelevé de : '.$nom_fournisseur.' (https://'.$url_site.').');
          }
      } else {
          log::add('veolia_eau', 'debug', 'empty data');
      }
      return $datasFetched;
    }

    private function processHtml($htm_file, $website, &$compteur, &$date, $offsetVeoliaDate, $mock_test, &$lastdate, $currentdatenum, $nom_fournisseur, $url_site) {
        log::add('veolia_eau', 'debug', '### TRAITE CONSO HTML '.$website.' ###');
        $depart = $this->getConfiguration('depart');
        $compteur = $this->getConfiguration('compteur');
        $lastdate=$this->getConfiguration('last');
        log::add('veolia_eau', 'debug', 'last1: '. $lastdate);
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
        if (count($info) == 1) { //dataPoints pas dans le HTML
          log::add('veolia_eau', 'error', 'dataPoints: pas trouvé dans la reponse de : '.$nom_fournisseur.' (https://'.$url_site.').');
          $pos = strrpos($info[0], "Nous nous excusons pour la");
          if ($pos != false) { // note: three equal signs
              log::add('veolia_eau', 'error', 'Site de '.$nom_fournisseur.' (https://'.$url_site.'.) H.-S. : une erreur est survenue, veuillez réessayer ultérieurement, nous nous excusons pour la gêne occasionnée.');
          }
          $pos = strrpos($info[0], "Site en cours de maintenance");
          if ($pos != false){
              log::add('veolia_eau', 'error', 'Site de '.$nom_fournisseur.' (https://'.$url_site.'.) H.-S. : site en cours de maintenance.');
          }
          return 0;
        }

        $info = explode("]", $info[1], 2);
        $info = str_replace(" ", "", $info[0]);
        $info = str_replace("\t,", "", $info);
        $info = str_replace("\t", "", $info);
        $info = str_replace("\r\n", "", $info);
        $info = str_replace("\n", "", $info);
        $info = str_replace("},{", "|", $info);
        $info = str_replace("}{", "|", $info);
        $info = str_replace("}", "", $info);
        $info = str_replace("{", "", $info);
        $info = str_replace("y:", "", $info);
        $info = str_replace("label:", "", $info);
        $info = str_replace("color:\"#c0bebf\",", "", $info);
        $info = str_replace("color:\"#94dde7\"", "", $info);
        $info = str_replace("color:\"#2abccf\"", "", $info);
        $info = str_replace("\"", "", $info);
        $info = explode( "|", $info);
        //log::add('veolia_eau', 'debug', print_r($info, true));

        foreach ($info as $data) {
            log::add('veolia_eau', 'debug', print_r($data, true));
            $data = explode(",", $data);

            // gerer le cas  "Non mesurée"
            // {y: 0, color:"#c0bebf", label: "Non mesurée"}
            // l espace a ete enleve par le str_replace(" ", "", $info[0]);
            if ($data[1] == "Nonmesurée") {
              log::add('veolia_eau', 'debug', 'valeur non mesurée');
              // verification que la donnee non mesuree ne se produit pas le dernier jour du mois, dans ce cas elle est perdu et ne sera pas ajoute le lendemain
              if($mock_test==3){
                $nm_currentreleve = mktime(0, 0, 0, date("m",mktime(0, 0, 0, 3, 3, 2018))  , date("d",mktime(0, 0, 0, 3, 3, 2018))-$offsetVeoliaDate, date("Y",mktime(0, 0, 0, 3, 3, 2018)));
                $nm_nextreleve = mktime(0, 0, 0, date("m",mktime(0, 0, 0, 3, 3, 2018))  , date("d",mktime(0, 0, 0, 3, 3, 2018))-$offsetVeoliaDate+1, date("Y",mktime(0, 0, 0, 3, 3, 2018)));
              }
              else{
                  $nm_currentreleve = mktime(0, 0, 0, date("m",$currentdatenum)  , date("d",$currentdatenum)-$offsetVeoliaDate, date("Y",$currentdatenum));
                  $nm_nextreleve = mktime(0, 0, 0, date("m",$currentdatenum)  , date("d",$currentdatenum)-$offsetVeoliaDate+1, date("Y",$currentdatenum));
              }
              $nm_month = date('m/Y',$nm_currentreleve);
              $nm_nextmonth = date('m/Y',$nm_nextreleve);
              log::add('veolia_eau', 'debug', ' $nm_nextmonth:'.$nm_nextmonth.' $nm_month:'.$nm_month);
              if ($nm_month != $nm_nextmonth) {
                log::add('veolia_eau', 'error', 'valeur non mesurée en fin de mois');
              }
              if ($date>$lastdate) {
                # Ne pas mettre l'erreur plusieurs fois dans le mois
                log::add('veolia_eau', 'error', 'Valeur non mesurée, une mesure est perdu');
              }
            continue;
            }

            $dateTemp = explode('/', $data[1]);

            // Recuperation d autres cas potentiel ou ce champ ne serait pas une date pour eviter de fausser le compteur
            // verifie s il y a bien 2 slash
            if(count($dateTemp) != 3) {
                log::add('veolia_eau', 'error', 'date invalide - impossible de trouver 2 slash :'.$data[1]);
                return 0;
            }

            // verifie si la date est valide
            if(!checkdate($dateTemp[1], $dateTemp[0], $dateTemp[2])){
                log::add('veolia_eau', 'error', 'date invalide:'.$data[1]);
                return 0;
            }

            // transform d/m/yyyy to yyy-mm-dd with leading 0
            $date = $dateTemp[2].'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT);
            $conso = $data[0];
            $consomonth[] = $conso;
            $typeReleve = 'M';

            if ($date>$lastdate) {
              $compteur += $conso;
            }

            $index = $depart + $compteur;
            log::add('veolia_eau', 'debug', $date.' '.$conso.' '.$typeReleve.' '.$compteur.' '.$index);

            $datasFetched[] = array(
                'date' => $date,
                'index' => $index,
                'conso' => $conso,
                'typeReleve' => $typeReleve
            );
        }
        return $datasFetched;
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
