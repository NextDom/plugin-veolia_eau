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
    private const MAX_EMPTY_DATA_ATTEMPTS = 10;
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
		$return['log'] = log::getPathToLog(__CLASS__ . '_update');
		$return['progress_file'] = '/tmp/dependancy_veolia_in_progress';
		$return['state'] = 'ok';
		// Les dépendances PHP (PhpSpreadsheet) sont installées via composer.
		if (!file_exists(dirname(__FILE__) . '/../../vendor/autoload.php')) {
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

        $cmdlogic = veolia_eauCmd::byEqLogicIdAndLogicalId($this->getId(), 'refresh');
		if (!is_object($cmdlogic)) {
            $veolia_eauCmd = new veolia_eauCmd();
            $veolia_eauCmd->setName(__('Rafraichir', __FILE__));
            $veolia_eauCmd->setEqLogic_id($this->id);
            $veolia_eauCmd->setLogicalId('refresh');
            $veolia_eauCmd->setType('action');
            $veolia_eauCmd->setSubType('other');
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


    private static function isWebsiteToutSurMonEau($website){
	    if ($website == 4 || $website == 6 || $website == 7 || $website == 8 || $website == 9 || $website == 10 || $website == 11 ||$website == 12 || $website == 13 ||$website == 14 || $website == 15){
		    return true;}
	    return false;
    }

    /* Widget custom : carte "eau" (conso en hero, index, badge M/E, date).
     * Le template se trouve dans core/template/dashboard/veolia_eau.html.
     * Si l'utilisateur désactive le widget custom (option widgetTmpl), ou en
     * version mobile (pas de template dédié), template_replace/getTemplate
     * retombent automatiquement sur le rendu par défaut. */
    public function toHtml($_version = 'dashboard') {
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $version = jeedom::versionAlias($_version);

        // logicalId de la commande => préfixe de placeholder dans le template
        $map = array(
            'conso'      => 'conso',
            'index'      => 'index',
            'dateReleve' => 'date',
            'typeReleve' => 'type',
        );
        foreach ($map as $logicalId => $key) {
            $cmd = $this->getCmd(null, $logicalId);
            if (is_object($cmd)) {
                $replace['#' . $key . '_id#']    = $cmd->getId();
                $replace['#' . $key . '_value#'] = $cmd->execCmd();
                $replace['#' . $key . '_unite#'] = $cmd->getUnite();
            } else {
                $replace['#' . $key . '_id#']    = '';
                $replace['#' . $key . '_value#'] = '';
                $replace['#' . $key . '_unite#'] = '';
            }
        }
        // Badge ambre si relevé Estimé (E), teal si Mesuré (M)
        $replace['#type_badge_class#'] = (strtoupper(substr(trim($replace['#type_value#']), 0, 1)) === 'E') ? 'vw-badge--est' : '';

        // Libellés traduits côté serveur (les {{}} ne sont pas interprétés sur
        // le HTML d'un widget injecté après la passe i18n du dashboard).
        $replace['#l_conso#']   = __('Consommation du jour', __FILE__);
        $replace['#l_index#']   = __('Index compteur', __FILE__);
        $replace['#l_date#']    = __('Relevé du', __FILE__);
        $replace['#l_type#']    = __('Type de relevé', __FILE__);
        $replace['#l_refresh#'] = __('Rafraîchir', __FILE__);

        return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, __CLASS__, __CLASS__)));
    }

    /*     * **********************Getteur Setteur*************************** */

	public function getConso($mock_test) {
	    if ($this->getConsecutiveNoDataAttempts() >= self::MAX_EMPTY_DATA_ATTEMPTS) {
	        log::add('veolia_eau', 'warning', 'Arrêt des tentatives : aucune donnée reçue après '.self::MAX_EMPTY_DATA_ATTEMPTS.' essais.');
	        return;
	    }
		// Pour EGL (website=3) : bloquer si une relève a déjà réussi aujourd'hui
		// (évite les relances en boucle après un succès suivi de données vides)
		$website_check = intval($this->getConfiguration('website'));
		if ($website_check === 3 && $mock_test == 0) {
			$lastSuccess = intval($this->getConfiguration('lastSuccessTimestamp', 0));
			if ($lastSuccess > 0) {
				$todayStart = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
				if ($lastSuccess >= $todayStart) {
					log::add('veolia_eau', 'debug',
						'EGL : relève déjà réussie aujourd\'hui à '.date('H:i:s', $lastSuccess).', pas de nouvelle tentative.');
					return;
				}
			}
		}
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

        // Nouveau portail Veolia (eau.veolia.fr) : authentification AWS Cognito + API JSON/CSV
        // Flux totalement différent (token JWT Bearer), traité dans une méthode dédiée.
        if ($website == 16) {
            @unlink($cookie_file);
            return $this->getConsoVeoliaWeb($mock_test, $offsetVeoliaDate);
        }

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
        } elseif ($website == 14) {
            $nom_fournisseur = "Syndicat de Distribution d'Eau du Sud-Ouest Lyonnais (SIDESOL)";
            $url_site = 'sidesol.toutsurmoneau.fr';
        } elseif ($website == 15) {
            $nom_fournisseur = 'L\'eau du Valenciennois';
            $url_site = 'leauduvalenciennois.toutsurmoneau.fr';
         } else {
			$nom_fournisseur = '';
            $url_site = 'not defined';
        }
        switch ($website) {
            case 2:
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

            case 3:
            // Nouveau site EGL (depuis janvier 2025) : API REST OAuth2 PKCE
                $url_login = 'https://agence.eaudugrandlyon.com/application/auth/externe/authentification';
                $getConsoInHtmlFile = false;
                $currentdatenum = time();
                $datas = array(
                    'username='.urlencode($this->getConfiguration('login')),
                    'password='.urlencode($this->getConfiguration('password')),
                    'client_id=kwnOk0B_aqlOI6p_GVxrbf6',
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
            case 14:
            case 15:
				$url_token = 'https://'.$url_site.'/mon-compte-en-ligne/je-me-connecte';
                $tokenFieldName = '_csrf_token';
                $url_login = 'https://'.$url_site.'/mon-compte-en-ligne/je-me-connecte';
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
            $token = $html->find('input[name='.$tokenFieldName.']', 0)->value;
            // Ajout : Extraction token pour le nouveau site toutsurmoneau
			if ($website == 4 || $website == 6 || $website == 7 || $website == 8 || $website == 9 || $website == 10 || $website == 11 || $website == 12 || $website == 13 || $website == 14 || $website == 15) {
              preg_match("/csrfToken.*targetUrl/", $response, $matches);
              $token = implode($matches);
              $token = str_ireplace("\u002D","-",$token);
              $token = str_ireplace("csrfToken\u0022\u003A\u0022","",$token);
              $token = str_ireplace("\u0022,\u0022targetUrl","",$token);
              log::add('veolia_eau', 'debug', 'Token: '.$token);
            }
            // Fin Ajout toutsurmoneau
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

        // Flux OAuth2 PKCE pour le nouveau site EGL (website 3, depuis janvier 2025)
        if ($website == 3 && $mock_test < 2) {
            $login_json = json_decode($response, true);
            if (!$login_json || ($login_json['code'] ?? '') !== '0') {
                log::add('veolia_eau', 'error', 'Authentification EGL échouée. Vérifiez vos identifiants. Réponse : '.$response);
                curl_close($ch);
                @unlink($cookie_file);
                return;
            }
            // Étape 2 : Authorization PKCE
            $egl_client_id   = 'kwnOk0B_aqlOI6p_GVxrbf6';
            $egl_redirect    = 'https://agence.eaudugrandlyon.com/autorisation-callback.html';
            $egl_verifier    = '5';
            $egl_challenge   = rtrim(strtr(base64_encode(hash('sha256', $egl_verifier, true)), '+/', '-_'), '=');
            $authorize_url   = 'https://agence.eaudugrandlyon.com/application/auth/authorize-internet?'
                .http_build_query([
                    'redirect_uri'          => $egl_redirect,
                    'response_type'         => 'code',
                    'code_challenge'        => $egl_challenge,
                    'code_challenge_method' => 'S256',
                    'client_id'             => $egl_client_id,
                ]);
            log::add('veolia_eau', 'debug', '### EGL AUTHORIZE ###');
            curl_setopt($ch, CURLOPT_URL, $authorize_url);
            curl_setopt($ch, CURLOPT_POST, FALSE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_exec($ch);
            $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            log::add('veolia_eau', 'debug', 'EGL authorize final URL : '.$final_url);
            parse_str(parse_url($final_url, PHP_URL_QUERY), $auth_query);
            $auth_code = $auth_query['code'] ?? '';
            if (!$auth_code) {
                log::add('veolia_eau', 'error', 'EGL : code OAuth2 absent de la redirection : '.$final_url);
                curl_close($ch);
                @unlink($cookie_file);
                return;
            }
            // Étape 3 : Échange du code contre un access_token
            log::add('veolia_eau', 'debug', '### EGL TOKEN EXCHANGE ###');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, 'https://agence.eaudugrandlyon.com/application/auth/tokenUtilisateurInternet');
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $auth_code,
                'code_verifier' => $egl_verifier,
                'client_id'     => $egl_client_id,
                'redirect_uri'  => $egl_redirect,
            ]));
            $token_resp = curl_exec($ch);
            log::add('veolia_eau', 'debug', 'EGL token response : '.$token_resp);
            $token_json = json_decode($token_resp, true);
            $egl_access_token = $token_json['access_token'] ?? '';
            if (!$egl_access_token) {
                log::add('veolia_eau', 'error', 'EGL : access_token absent de la réponse : '.$token_resp);
                curl_close($ch);
                @unlink($cookie_file);
                return;
            }
            // Étape 4 : Récupération du contrat
            log::add('veolia_eau', 'debug', '### EGL GET CONTRACTS ###');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $egl_contracts_select = 'id,reference,statutExtrait,dateEffet,dateEcheance,'
                .'conditionPaiement(compteClient(solde),mensualise,modePaiement),'
                .'servicesSouscrits(statut,usage,calibreCompteur,nombreHabitants),'
                .'espaceDeLivraison(reference)';
            $egl_contracts_expand = 'conditionPaiement(compteClient),servicesSouscrits,espaceDeLivraison';
            $egl_headers = array_merge(
                $headers,
                [
                    'Authorization: Bearer '.$egl_access_token,
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'entreprise: EPGL',
                ]
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $egl_headers);
            curl_setopt(
                $ch,
                CURLOPT_URL,
                'https://agence.eaudugrandlyon.com/application/rest/interfaces/ael/contrats/rechercher?'
                .http_build_query([
                    'expand' => $egl_contracts_expand,
                    'select' => $egl_contracts_select,
                ])
            );
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
            $contracts_resp = curl_exec($ch);
            log::add('veolia_eau', 'debug', 'EGL contracts : '.substr($contracts_resp, 0, 300));
            $contracts_data = json_decode($contracts_resp, true);
            $egl_contract_id = null;
            if (is_array($contracts_data)) {
                if (isset($contracts_data[0]['id'])) {
                    $egl_contract_id = $contracts_data[0]['id'];
                } elseif (isset($contracts_data['content'][0]['id'])) {
                    $egl_contract_id = $contracts_data['content'][0]['id'];
                }
            }
            if (!$egl_contract_id) {
                log::add('veolia_eau', 'error', 'EGL : impossible de récupérer l\'ID du contrat : '.$contracts_resp);
                curl_close($ch);
                @unlink($cookie_file);
                return;
            }
            log::add('veolia_eau', 'debug', 'EGL contract ID : '.$egl_contract_id);
            // Étape 4b : Récupération des détails du contrat (passage en mode jour)
            log::add('veolia_eau', 'debug', '### EGL GET CONTRACT DETAIL ###');
            curl_setopt($ch, CURLOPT_POST, FALSE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $egl_headers);
            curl_setopt(
                $ch,
                CURLOPT_URL,
                'https://agence.eaudugrandlyon.com/application/rest/produits/contrats/'
                .$egl_contract_id.'?'
                .http_build_query(['select' => 'pointAccesServicesClient,dateEffet,dateFin'])
            );
            $contract_detail_resp = curl_exec($ch);
            log::add('veolia_eau', 'debug', 'EGL contract detail : '.$contract_detail_resp);
            // Étape 4c : Vérification de la communicabilité AMM (suivi conso journalier)
            log::add('veolia_eau', 'debug', '### EGL CHECK POINT DE SERVICE ###');
            curl_setopt(
                $ch,
                CURLOPT_URL,
                'https://agence.eaudugrandlyon.com/application/rest/produits/contrats/'
                .$egl_contract_id.'/pointDeService?'
                .http_build_query(['select' => 'communicabiliteAMM,modeReleve,niveauDeTension'])
            );
            $pds_resp = curl_exec($ch);
            log::add('veolia_eau', 'debug', 'EGL pointDeService : '.$pds_resp);
            $pds_data = json_decode($pds_resp, true);
            $egl_amm       = $pds_data['communicabiliteAMM'] ?? null;
            $egl_mode_releve = $pds_data['modeReleve'] ?? null;
            log::add('veolia_eau', 'debug', 'EGL communicabiliteAMM : '.($egl_amm ? 'true' : 'false').', modeReleve : '.$egl_mode_releve);
            if ($egl_amm === false) {
                log::add('veolia_eau', 'error', 'EGL : communicabiliteAMM désactivée, les données journalières ne sont pas disponibles pour ce compteur');
                curl_close($ch);
                @unlink($cookie_file);
                return;
            }
            // Étape 5 : Construction de l'URL de consommation journalière
            // Les bornes doivent correspondre à minuit et fin de journée heure locale Paris
            $egl_tz = new DateTimeZone('Europe/Paris');
            $egl_dt_fin = new DateTime('today 23:59:59', $egl_tz);
            $egl_date_fin = gmdate('Y-m-d\TH:i:s.999\Z', $egl_dt_fin->getTimestamp());
            $egl_lastdate = $this->getConfiguration('last');
            if ($egl_lastdate) {
                $egl_dt_debut = new DateTime($egl_lastdate.' +1 day midnight', $egl_tz);
            } else {
                $egl_dt_debut = new DateTime('-2 years midnight', $egl_tz);
            }
            $egl_date_debut = gmdate('Y-m-d\TH:i:s.000\Z', $egl_dt_debut->getTimestamp());
            $url_releve_csv = 'https://agence.eaudugrandlyon.com/application/rest/produits/contrats/'
                .$egl_contract_id.'/consommationsJournalieres?'
                .http_build_query(['dateDebut' => $egl_date_debut, 'dateFin' => $egl_date_fin]);
        }

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
			if ($website != 3) {
				curl_setopt($ch, CURLOPT_URL, $url_consommation);
				curl_setopt($ch, CURLOPT_POST, FALSE);

				if ($mock_test >= 2) {
					$response = "tbd";
				} else {
					if (static::isWebsiteToutSurMonEau($website)){
						$response = "";}
					else{
						$response = curl_exec($ch);}
				}

				log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));
				log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));
			}

			if (static::isWebsiteToutSurMonEau($website)){
				$url_pds = 'https://'.$url_site.'/public-api/cel-consumption/meters-list';
				log::add('veolia_eau', 'debug', 'url PDS : '.$url_pds);
				curl_setopt($ch, CURLOPT_URL, $url_pds);
				$response = curl_exec($ch);
				$json_obj = json_decode($response);
				if ($json_obj === null || !isset($json_obj->{'content'}->{'clientCompteursPro'}[0]->{'compteursPro'}[0]->{'idPDS'})) {
					log::add('veolia_eau', 'error', 'Impossible de récupérer la liste des compteurs (authentification échouée ou réponse inattendue) sur '.$nom_fournisseur.' (https://'.$url_site.').');
					curl_close($ch);
					@unlink($cookie_file);
					return;
				}
				$idPDS = $json_obj->{'content'}->{'clientCompteursPro'}[0]->{'compteursPro'}[0]->{'idPDS'};

                $dt = date_create();
                $end_year = date_format($dt,"Y");
                $end_month = date_format($dt,"m");
                $end_day = date_format($dt,"d");

                date_modify($dt, "-1 month");
                $start_year = date_format($dt,"Y");
                $start_month = date_format($dt,"m");
                $start_day = date_format($dt,"d");

                $url_releve_csv = 'https://'.$url_site.'/public-api/cel-consumption/telemetry?id_PDS='.$idPDS.'&mode=daily&start_date='.$start_year.'-'.$start_month.'-'.$start_day.'&end_date='.$end_year.'-'.$end_month.'-'.$end_day;
			}
		}

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
                if ($website == 3) {
                    // EGL REST API : GET avec le bearer token et le header entreprise
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $egl_headers);
                    curl_setopt($ch, CURLOPT_POST, FALSE);
                    $response = curl_exec($ch);
                    $error = curl_error($ch);
                    log::add('veolia_eau', 'debug', 'EGL response length : '.strlen($response));
                    log::add('veolia_eau', 'debug', 'error : '.$error);
                    log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));
                    log::add('veolia_eau', 'debug', 'EGL response preview : '.substr($response, 0, 300));
                    // Convertir la réponse JSON en CSV (format date;index;volume)
                    $json_data = json_decode($response, true);
                    $entries = [];
                    if (is_array($json_data)) {
                        if (isset($json_data['postes'])) {
                            foreach ($json_data['postes'] as $poste) {
                                foreach (($poste['data'] ?? []) as $e) { $entries[] = $e; }
                            }
                        } elseif (isset($json_data['data']) && is_array($json_data['data'])) {
                            $entries = $json_data['data'];
                        } elseif (isset($json_data['consommationsJournalieres'])) {
                            $entries = $json_data['consommationsJournalieres'];
                        } elseif (isset($json_data[0])) {
                            $entries = $json_data;
                        }
                        if (empty($entries)) {
                            log::add('veolia_eau', 'debug', 'EGL JSON top-level keys : '.implode(',', array_keys($json_data)));
                        }
                    } else {
                        log::add('veolia_eau', 'debug', 'EGL JSON decode failed or not an array, json_last_error : '.json_last_error());
                    }
                    log::add('veolia_eau', 'debug', 'EGL entries found : '.count($entries));
                    if (!empty($entries)) {
                        log::add('veolia_eau', 'debug', 'EGL first entry keys : '.implode(',', array_keys($entries[0])));
                    }
                    fwrite($fp, "date;index;volume\n");
                    $egl_written = 0;
                    $egl_skipped = 0;
                    foreach ($entries as $entry) {
                        // EGL encode les mois avec un décalage de -1 (janvier=0, février=1, etc.)
                        // On passe true à normalizeEglDate pour corriger ce décalage (website==3 uniquement)
                        $egl_date  = self::normalizeEglDate($entry, true);
                        $egl_index = $entry['index'] ?? $entry['indexCompteur'] ?? $entry['releve'] ?? 0;
                        $egl_conso = $entry['consommation'] ?? $entry['volume'] ?? $entry['quantite'] ?? 0;
                        if ($egl_date && ($egl_conso !== 0 || $egl_index !== 0)) {
                            fwrite($fp, $egl_date.';'.$egl_index.';'.$egl_conso."\n");
                            $egl_written++;
                        } else {
                            if ($egl_skipped < 3) {
                                log::add('veolia_eau', 'debug', 'EGL skipped entry : date='.($egl_date ?: '(empty)').' conso='.$egl_conso.' index='.$egl_index);
                            }
                            $egl_skipped++;
                        }
                    }
                    if ($egl_skipped > 0) {
                        log::add('veolia_eau', 'debug', 'EGL total skipped entries : '.$egl_skipped);
                    }
                    log::add('veolia_eau', 'debug', 'EGL entries written to CSV : '.$egl_written);
                } elseif (!static::isWebsiteToutSurMonEau($website)){
			        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    $response = curl_exec($ch);
                    $error = curl_error($ch);
                    log::add('veolia_eau', 'debug', 'response : '.$response);
                    log::add('veolia_eau', 'debug', 'error : '.$error);
                    log::add('veolia_eau', 'debug', 'response length : '.strlen($response));
                    log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));
                } else {
                    $response = curl_exec($ch);
                    $error = curl_error($ch);
                    log::add('veolia_eau', 'debug', 'response : '.$response);
                    log::add('veolia_eau', 'debug', 'error : '.$error);
                    log::add('veolia_eau', 'debug', 'response length : '.strlen($response));
                    log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));
                    $json_obj = json_decode($response);
                    fwrite($fp, "date;index;volume\n");
                    if ($json_obj === null || !isset($json_obj->{'content'}->{'measures'})) {
                        log::add('veolia_eau', 'error', 'Aucune mesure récupérée (réponse inattendue) sur '.$nom_fournisseur.' (https://'.$url_site.').');
                    } else
                    foreach ($json_obj->{'content'}->{'measures'} as $measure){
                        if ($measure->{'index'}){
                            fwrite($fp, $measure->{'date'}.';'.$measure->{'index'}.';'.$measure->{'volume'}."\n");}
                    }
                }
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

	/*
	 * Récupération de la consommation sur le nouveau portail Veolia (eau.veolia.fr).
	 * Authentification AWS Cognito (USER_PASSWORD_AUTH) puis appels à l'API backend
	 * istefr avec le token Bearer (AccessToken).
	 */
	public function getConsoVeoliaWeb($mock_test, $offsetVeoliaDate) {
		$nom_fournisseur = 'Veolia (portail eau.veolia.fr)';
		$url_site = 'www.eau.veolia.fr';
		$api = 'https://prd-ael-sirius-backend.istefr.fr';
		$cognito_url = 'https://cognito-idp.eu-west-3.amazonaws.com/';
		$cognito_client_id = '3kghade1fg54739kj8pkbova8j';

		// 1. Authentification AWS Cognito
		log::add('veolia_eau', 'debug', '### COGNITO AUTH ###');
		$auth_payload = json_encode(array(
			'AuthFlow' => 'USER_PASSWORD_AUTH',
			'AuthParameters' => array(
				'USERNAME' => $this->getConfiguration('login'),
				'PASSWORD' => $this->getConfiguration('password'),
			),
			'ClientId' => $cognito_client_id,
		));
		$response = static::veoliaWebHttp($cognito_url, array(
			'Content-Type: application/x-amz-json-1.1',
			'X-Amz-Target: AWSCognitoIdentityProviderService.InitiateAuth',
		), $auth_payload);
		$auth = json_decode($response);
		if ($auth === null || !isset($auth->AuthenticationResult->AccessToken)) {
			log::add('veolia_eau', 'error', 'Authentification échouée sur '.$nom_fournisseur.' ; vérifiez votre identifiant et votre mot de passe (https://'.$url_site.').');
			return;
		}
		$accessToken = $auth->AuthenticationResult->AccessToken;
		$bearer = array('Authorization: Bearer '.$accessToken);

		// 2. espace-client -> id_abonnement
		log::add('veolia_eau', 'debug', '### ESPACE-CLIENT ###');
		$response = static::veoliaWebHttp($api.'/espace-client?type-front=WEB_ORDINATEUR', $bearer);
		$ec = json_decode($response);
		$idAbo = null;
		// idAbt permet de choisir l'abonnement si plusieurs (0 par défaut)
		$wanted = intval($this->getConfiguration('idAbt', 0));
		$found = 0;
		if ($ec !== null && isset($ec->contacts)) {
			foreach ($ec->contacts as $contact) {
				if (!isset($contact->tiers)) { continue; }
				foreach ($contact->tiers as $tiers) {
					if (!isset($tiers->abonnements)) { continue; }
					foreach ($tiers->abonnements as $abo) {
						if (!isset($abo->id_abonnement)) { continue; }
						if ($found == $wanted) {
							$idAbo = $abo->id_abonnement;
							break 3;
						}
						$found++;
					}
				}
			}
		}
		if ($idAbo === null) {
			log::add('veolia_eau', 'error', 'Aucun abonnement trouvé (index '.$wanted.') sur '.$nom_fournisseur.' (https://'.$url_site.').');
			return;
		}
		log::add('veolia_eau', 'debug', 'id_abonnement: '.$idAbo);

		// 3. facturation -> numero_pds + date_debut_abonnement
		log::add('veolia_eau', 'debug', '### FACTURATION ###');
		$response = static::veoliaWebHttp($api.'/abonnements/'.$idAbo.'/facturation', $bearer);
		$fact = json_decode($response);
		if ($fact === null || !isset($fact->numero_pds)) {
			log::add('veolia_eau', 'error', 'Impossible de récupérer le numéro de PDS sur '.$nom_fournisseur.' (https://'.$url_site.').');
			return;
		}
		$pds = $fact->numero_pds;
		$dateDebut = isset($fact->date_debut_abonnement) ? substr($fact->date_debut_abonnement, 0, 10) : date('Y-m-d');
		log::add('veolia_eau', 'debug', 'numero_pds: '.$pds.' / date_debut: '.$dateDebut);

		// 4. Récupération du CSV journalier (mois courant + mois précédent pour couvrir les bords)
		$data_file = sys_get_temp_dir().'/veolia_releve_'.uniqid().'.csv';
		static::secure_touch($data_file);
		$fp = fopen($data_file, 'w');
		if (!$fp) {
			log::add('veolia_eau', 'error', 'error on creating file "'.$data_file.'"');
			return;
		}

		$headerWritten = false;
		$now = time();
		foreach (array(strtotime('first day of last month', $now), $now) as $monthTime) {
			$annee = date('Y', $monthTime);
			$mois = intval(date('m', $monthTime));
			$url_csv = $api.'/consommations/'.$idAbo.'/journalieres/export?annee='.$annee.'&mois='.$mois.'&numero-pds='.$pds.'&date-debut-abonnement='.$dateDebut;
			log::add('veolia_eau', 'debug', '### GET DAILY CSV '.$annee.'-'.$mois.' : '.$url_csv);
			$csv = static::veoliaWebHttp($url_csv, $bearer);
			if ($csv === false || $csv === '') {
				log::add('veolia_eau', 'debug', 'CSV vide pour '.$annee.'-'.$mois);
				continue;
			}
			// La 1re ligne est l'entête ; on ne l'écrit qu'une fois.
			$lines = preg_split('/\r\n|\r|\n/', $csv);
			foreach ($lines as $i => $line) {
				if ($line === '') { continue; }
				if ($i == 0) {
					if ($headerWritten) { continue; }
					$headerWritten = true;
				}
				fwrite($fp, $line."\n");
			}
		}
		fclose($fp);

		// 5. Traitement via processCSV (case 16) puis envoi des événements
		$this->traiteConso($data_file, '', $mock_test, $offsetVeoliaDate, $now, 0, $nom_fournisseur, $url_site);
		if ($mock_test == 0) {
			@unlink($data_file);
		}
	}

	/* Appel HTTP simple (cURL) pour le portail Veolia. POST si $postData fourni, sinon GET. */
	private static function veoliaWebHttp($url, $headers = array(), $postData = null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0");
		if ($postData !== null) {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		}
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		$response = curl_exec($ch);
		log::add('veolia_eau', 'debug', 'HTTP '.$url.' -> errno '.curl_errno($ch).', len '.strlen($response ?: ''));
		curl_close($ch);
		return $response;
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
              if ($file!=""){
                $htmlDataFetched=static::processHtml($htm_file, $website, $compteur, $date, $offsetVeoliaDate, $mock_test, $lastdate, $currentdatenum, $nom_fournisseur, $url_site);
                  if($htmlDataFetched==0){
                      $attempts = $this->incrementConsecutiveNoDataAttempts();
                      $this->logConsecutiveNoDataAttempt($attempts, $nom_fournisseur, $url_site);
                      log::add('veolia_eau', 'error',"Pas de données sur le site");
                      return -1;
                  }
                // Traitement du csv
                $csvDataFetched=static::processCSV($file,$website, $offsetVeoliaDate, $nom_fournisseur, $url_site);

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

                    if ($dataHtml["date"] === $dateCSV["date"]){
                      if ($dataHtml["conso"] != $dateCSV["conso"]){
                          log::add('veolia_eau', 'error', '$dataHtml["date"]'.$dataHtml["date"].'$data<>'.$dataHtml["conso"].'$data<>'.$dateCSV["conso"]);
                      } else{
                          $dateCSV["index"]=($dateCSV["conso"]+$previousIndex);
                          $dateCSV["typeReleve"]="M";
                          $previousIndex=$dateCSV["index"];
                          $datasFetched[$j]=$dateCSV;
                      }
                  } else {
                        $newDateCSV = date("Y-m-d", strtotime(" +1 day",strtotime($oldDateCSV)));

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

            case 3:
            // Nouveau site EGL (depuis janvier 2025) : traitement direct du CSV REST
                $datasFetched=static::processCSV($file, $website, $nom_fournisseur, $url_site);
                if (is_array($datasFetched) && count($datasFetched) > 0) {
                    $lastEntry = end($datasFetched);
                    $date      = $lastEntry['date'];
                    $compteur  = $lastEntry['index'];
                    $lastdate  = $this->getConfiguration('last');
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
          	case 14:
                $datasFetched=static::processCSV($file, $website, $nom_fournisseur, $url_site);
                break;

			case 1:
			default:
                $datasFetched=static::processCSV($file, $website, $nom_fournisseur, $url_site);

        }
		if (!is_array($datasFetched) || count($datasFetched) === 0) {
			// Pour EGL (website=3) : si last est récent (≤ 2 jours),
			// l'absence de nouvelles données est normale (délai de publication).
			// On ne pénalise pas le compteur dans ce cas.
			$website = intval($this->getConfiguration('website'));
			if ($website === 3) {
				$lastdate   = $this->getConfiguration('last');
				$lastdateTs = $lastdate ? strtotime($lastdate) : 0;
				if ($lastdateTs > 0 && (time() - $lastdateTs) <= 2 * 86400) {
					log::add('veolia_eau', 'info',
						'EGL : aucune nouvelle donnée mais last ('.$lastdate.') est récent (≤ 2 jours), pas de pénalité.');
					return;
				}
			}
			$attempts = $this->incrementConsecutiveNoDataAttempts();
			$this->logConsecutiveNoDataAttempt($attempts, $nom_fournisseur, $url_site);
			return;
		}
        $this->resetConsecutiveNoDataAttempts();
		// Mémoriser l'horodatage du dernier succès
		$this->setConfiguration('lastSuccessTimestamp', time());
		$this->save(true);
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

    private function getConsecutiveNoDataAttempts() {
        return intval($this->getConfiguration('noDataAttempts', 0));
    }

private function resetConsecutiveNoDataAttempts() {
    if ($this->getConsecutiveNoDataAttempts() === 0) {
        return;
    }
    $this->setConfiguration('noDataAttempts', 0);
    $this->setConfiguration('noDataAttemptsLastTime', 0); // reset timestamp aussi
    $this->save(true);
}

private function incrementConsecutiveNoDataAttempts() {
    $now = time();
    $lastFailTime = intval($this->getConfiguration('noDataAttemptsLastTime', 0));

    // Si le dernier échec date de plus de 24h, on repart de 0
    if ($lastFailTime > 0 && ($now - $lastFailTime) > 86400) {
        log::add('veolia_eau', 'info', 'Réinitialisation du compteur noDataAttempts (dernier échec > 24h).');
        $this->setConfiguration('noDataAttempts', 0);
    }

    $attempts = $this->getConsecutiveNoDataAttempts() + 1;
    $this->setConfiguration('noDataAttempts', $attempts);
    $this->setConfiguration('noDataAttemptsLastTime', $now); // horodatage du dernier échec
    $this->save(true);
    return $attempts;
}

    private function logConsecutiveNoDataAttempt($attempts, $nom_fournisseur, $url_site) {
        if ($attempts >= self::MAX_EMPTY_DATA_ATTEMPTS) {
            log::add('veolia_eau', 'error', 'Aucune donnée reçue pour : '.$nom_fournisseur.' (https://'.$url_site.'). Arrêt des tentatives après '.$attempts.' essais.');
            return;
        }
        log::add('veolia_eau', 'error', 'Aucune donnée reçue pour : '.$nom_fournisseur.' (https://'.$url_site.'). Tentative '.$attempts.'/'.self::MAX_EMPTY_DATA_ATTEMPTS.'.');
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

      // Portail eau.veolia.fr (website 16) : CSV simple séparé par des virgules,
      // généré par nos soins. On le parse en natif pour ne pas dépendre de PHPExcel
      // (la lib embarquée 1.8 n'est pas compatible PHP 8 et provoque une erreur fatale).
      // Format : Date de relevé,Consommation,Index relevé,Index mesuré/estimé,Écoulement
      if ($website == 16) {
          $lines = @file($csv_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
          if (!is_array($lines) || count($lines) < 2) {
              log::add('veolia_eau', 'error', 'Aucune donnée, merci de vérifier vos identifiants et l\'accès au télérelevé de : '.$nom_fournisseur.' (https://'.$url_site.').');
              return $datasFetched;
          }
          array_shift($lines); // entête
          log::add('veolia_eau', 'debug', count($lines).' data lines');
          foreach ($lines as $line) {
              $col = str_getcsv($line, ',');
              $dateTemp = explode('/', $col[0]);
              // On ignore les jours non encore relevés (index vide).
              if (count($dateTemp) != 3 || !isset($col[2]) || $col[2] === '') {
                  continue;
              }
              $date = $dateTemp[2].'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT);
              $datasFetched[] = array(
                  'date' => $date,
                  'index' => $col[2],
                  'conso' => isset($col[1]) ? $col[1] : 0,
                  'typeReleve' => isset($col[3]) ? $col[3] : ''
              );
          }
          return $datasFetched;
      }

      require_once dirname(__FILE__).'/../../vendor/autoload.php';
      // \Throwable (et pas seulement Exception) : on capture aussi les Error
      // fatales pour ne jamais tuer le script en silence.
      if ($website ==2 || $website == 3 || static::isWebsiteToutSurMonEau($website)) {
          $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
          $objReader->setDelimiter(";");
          try {
            $spreadsheet = $objReader->load( $csv_file );
          } catch(\Throwable $e) {
              log::add('veolia_eau', 'error',$e->getMessage());
            return 0;
          }
      } else {
          try{
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($csv_file);
        } catch(\Throwable $e) {
            log::add('veolia_eau', 'error',$e->getMessage());
          return 0;
        }
      }

      $sheetData = $spreadsheet->getActiveSheet()->toArray(null,true,true,true);

      if (is_array($sheetData) && count($sheetData)) {
          $entete = array_shift($sheetData);
          log::add('veolia_eau', 'debug', count($sheetData).' data lines');

          if (count($sheetData)) {
              log::add('veolia_eau', 'debug', count($sheetData).' data lines');

              foreach ($sheetData as $line) {
                  $dateTemp = explode('/', $line['A']);
                  if ($website == 2) {
                      $date = $dateTemp[2].'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT);
                      $index = 0;
                      $conso = $line['B'];
                      $typeReleve = 0;
                  }
                  elseif ($website == 3) {
                      // Nouveau site EGL : format date;index;volume (déjà en litres, pas de conversion)
                      // La date est déjà normalisée en YYYY-MM-DD par normalizeEglDate (avec correction mois +1)
                      $date = substr($line['A'], 0, 10);
                      $index = floatval($line['B']);
                      $conso = floatval($line['C']);
                      $typeReleve = 'M';
                  }
                  elseif (static::isWebsiteToutSurMonEau($website)){
                      $dateTemp = explode(' ', $line['A']);
                      $date = $dateTemp[0];
                      $conso = $line['C'] * 1000;
                      $index = $line['B'];
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

    /**
     * Normalise une date EGL vers le format YYYY-MM-DD.
     *
     * @param array $entry          Entrée JSON EGL
     * @param bool  $egl_month_offset  Si true, corrige le décalage de mois de l'API EGL
     *                                 (janvier=0, février=1, etc. → +1 pour obtenir le mois réel)
     *
     * @return string Date au format YYYY-MM-DD, ou chaîne vide si non déterminée
     */
    private static function normalizeEglDate(array $entry, bool $egl_month_offset = false) {
        $egl_date = $entry['date'] ?? $entry['dateDebut'] ?? $entry['dateJour'] ?? $entry['dateReleve'] ?? '';

        // Cas où la date est fournie sous forme de champs séparés jour/mois/annee
        if (!$egl_date && isset($entry['jour'], $entry['mois'], $entry['annee'])) {
            $day   = intval($entry['jour']);
            $month = intval($entry['mois']) + ($egl_month_offset ? 1 : 0);
            $year  = intval($entry['annee']);

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        if (!$egl_date) {
            return '';
        }

        // Cas format dd/mm/yyyy avec correction du décalage de mois EGL
        if ($egl_month_offset && strpos($egl_date, '/') !== false) {
            $parts = explode('/', $egl_date);
            if (count($parts) === 3) {
                $day   = intval($parts[0]);
                $month = intval($parts[1]) + 1; // janvier=0 → +1
                $year  = intval($parts[2]);
                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
        }

        // Cas format dd/mm/yyyy standard (sans décalage)
        if (strpos($egl_date, '/') !== false) {
            $parts = explode('/', $egl_date);
            if (count($parts) === 3 && checkdate(intval($parts[1]), intval($parts[0]), intval($parts[2]))) {
                return sprintf('%04d-%02d-%02d', intval($parts[2]), intval($parts[1]), intval($parts[0]));
            }
        }

        // Cas format YYYY-MM-DD ou ISO 8601 avec correction du décalage de mois EGL
        if ($egl_month_offset && preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $egl_date, $matches)) {
            $year  = intval($matches[1]);
            $month = intval($matches[2]) + 1; // janvier=0 → +1
            $day   = intval($matches[3]);
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // Cas format YYYY-MM-DD ou ISO 8601 standard (sans décalage)
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $egl_date, $matches)) {
            return sprintf('%04d-%02d-%02d', intval($matches[1]), intval($matches[2]), intval($matches[3]));
        }

        // Fallback : strtotime (ne peut pas corriger le décalage de mois)
        $timestamp = strtotime($egl_date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return '';
    }

    private function processHtml($htm_file, $website, &$compteur, &$date, $offsetVeoliaDate, $mock_test, &$lastdate, $currentdatenum, $nom_fournisseur, $url_site) {
      log::add('veolia_eau', 'debug', '### TRAITE CONSO HTML '.$website.' ###');
        $depart = $this->getConfiguration('depart');
        $compteur = $this->getConfiguration('compteur');
        $lastdate=$this->getConfiguration('last');
        log::add('veolia_eau', 'debug', 'last1: '. $lastdate);
        $html = file_get_contents($htm_file);
        $info = explode("dataPoints: [", $html,2);
        if (count($info) == 1) { //dataPoints pas dans le HTML
          log::add('veolia_eau', 'error', 'dataPoints: pas trouvé dans la reponse de : '.$nom_fournisseur.' (https://'.$url_site.').');
          $pos = strrpos($info[0], "Nous nous excusons pour la");
          if ($pos != false) {
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
        $info = preg_replace('/color:"#[a-f0-9]{6}",?/i', "", $info);
        $info = str_replace("\"", "", $info);
        $info = explode( "|", $info);

        foreach ($info as $data) {
            log::add('veolia_eau', 'debug', print_r($data, true));
            $data = explode(",", $data);

            if ($data[1] == "Nonmesurée") {
              log::add('veolia_eau', 'debug', 'valeur non mesurée');
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
                log::add('veolia_eau', 'error', 'Valeur non mesurée, une mesure est perdu');
              }
            continue;
            }

            $dateTemp = explode('/', $data[1]);

            if(count($dateTemp) != 3) {
                log::add('veolia_eau', 'error', 'date invalide - impossible de trouver 2 slash :'.$data[1]);
                return 0;
            }

            if(!checkdate($dateTemp[1], $dateTemp[0], $dateTemp[2])){
                log::add('veolia_eau', 'error', 'date invalide:'.$data[1]);
                return 0;
            }

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

    public function execute($_options = array()) {
        $veolia_eau = $this->getEqLogic(); //récupère l'éqlogic de la commande $this

        switch ($this->getLogicalId()) { //vérifie le logicalid de la commande
            case 'refresh': // LogicalId de la commande rafraîchir.
                log::add('veolia_eau', 'info', 'Relevé manuel');
                if ($veolia_eau->getIsEnable() == 1) {
                    if (!empty($veolia_eau->getConfiguration('login')) && !empty($veolia_eau->getConfiguration('password'))) {
                        $veolia_eau->getConso(0);
                        log::add('veolia_eau', 'debug', 'done... ');
                    } else {
                        log::add('veolia_eau', 'error', 'Identifiants non saisis');
                    }
                }
                break;
        }
    }

    /***************************** Getteur/Setteur ***************************/
}