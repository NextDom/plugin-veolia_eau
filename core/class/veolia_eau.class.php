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

class veolia_eau extends eqLogic {
    /******************************* Attributs *******************************/ 
    /* Ajouter ici toutes vos variables propre à votre classe */
	const URL_LOGIN = 'https://www.service-client.veoliaeau.fr/home.loginAction.do';
	const URL_CONSOMMATION = 'https://www.service-client.veoliaeau.fr/home/espace-client/votre-consommation.html?vueConso=releves';
	const URL_RELEVE_CSV = 'https://www.service-client.veoliaeau.fr/home/espace-client/votre-consommation.exportConsommationData.do?vueConso=releves';
	
    /***************************** Methode static ****************************/ 

    
    // Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function cron() {
		/*foreach (eqLogic::byType('veolia_eau', true) as $veolia_eau) {
			if ($veolia_eau->getIsEnable() == 1) {
				if (!empty($veolia_eau->getConfiguration('login')) && !empty($veolia_eau->getConfiguration('password'))) {
					log::add('veolia_eau', 'debug', '----------------------------------');
					$veolia_eau->getConso();
				} else {
					log::add('veolia_eau', 'error', 'Identifiants non saisis');
				}
			}
		}*/
    }
    
    // Fonction exécutée automatiquement toutes les heures par Jeedom
    public static function cronHourly() {
		if (date('G') == 6) {
			foreach (eqLogic::byType('veolia_eau', true) as $veolia_eau) {
				if ($veolia_eau->getIsEnable() == 1) {
					if (!empty($veolia_eau->getConfiguration('login')) && !empty($veolia_eau->getConfiguration('password'))) {
						$veolia_eau->getConso();
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
		
		$datas = array(
			'veolia_username='.urlencode($this->getConfiguration('login')),
			'veolia_password='.urlencode($this->getConfiguration('password')),
			'login=OK',
		);
		
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

		curl_setopt($ch, CURLOPT_URL, self::URL_LOGIN);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $datas));		
		
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		log::add('veolia_eau', 'debug', '### LOGIN ###');				
		log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));	
		log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));		
				
		curl_setopt($ch, CURLOPT_URL, self::URL_CONSOMMATION);
		curl_setopt($ch, CURLOPT_POST, FALSE);
		
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		log::add('veolia_eau', 'debug', '### GO TO CONSOMMATION PAGE ###');				
		log::add('veolia_eau', 'debug', 'cURL response : '.urlencode($response));	
		log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));
		
		
		$xls_file = sys_get_temp_dir().'/veolia_releve_'.uniqid().'.xls';
		static::secure_touch($xls_file);
		
		$fp = fopen($xls_file, 'w');
		
		if ($fp) {
			curl_setopt($ch, CURLOPT_URL, self::URL_RELEVE_CSV);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			
			$response = curl_exec($ch);
			$info = curl_getinfo($ch);		
			
			log::add('veolia_eau', 'debug', '### GET XLS ###');
			log::add('veolia_eau', 'debug', 'response length : '.strlen($response));		
			log::add('veolia_eau', 'debug', 'cURL errno : '.curl_errno($ch));			
					
			@unlink($cookie_file);
			curl_close($ch);
			fclose($fp);
			
			//traitement du xls
			$this->traiteConso($xls_file);			
		} else {
			log::add('veolia_eau', 'error', 'error on creating file "'.$xls_file.'"');
		}	
	}
	
	public function traiteConso($file) {		
		log::add('veolia_eau', 'debug', '### TRAITE CONSO ###');
		$lastdate=$this->getConfiguration('last')

		require_once dirname(__FILE__).'/../../3rparty/PHPExcel/Classes/PHPExcel/IOFactory.php';

		$objPHPExcel = PHPExcel_IOFactory::load($file);

		$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

		if (is_array($sheetData) && count($sheetData)) {
			$entete = array_shift($sheetData);

			if (count($sheetData)) {
				log::add('veolia_eau', 'debug', count($sheetData).' data lines');
				$row=0;
				foreach ($sheetData as $line) {
					$dateTemp = explode('/', $line['A']);
					$date = $dateTemp[2].'-'.str_pad($dateTemp[0], 2, '0', STR_PAD_LEFT).'-'.str_pad($dateTemp[1], 2, '0', STR_PAD_LEFT);
					$index = $line['B'];
					$conso = $line['C'];
					$typeReleve = $line['D'];

					if ($date>$lastdate) {
                        $cmd = $this->getCmd(null, 'index');

                        if (is_object($cmd)) {
                            $cmd->setCollectDate($date);
                            $cmd->event($index);
                        }
					
                        $cmd = $this->getCmd(null, 'conso');
					
                        if (is_object($cmd)) {
                            $cmd->setCollectDate($date);
                            $cmd->event($conso);
                        }
					
                        $cmd = $this->getCmd(null, 'typeReleve');
			
                        if (is_object($cmd)) {
                            $cmd->setCollectDate($date);
                            $cmd->event($typeReleve);
                        }
                        $row++;
                    }
				}
                log::add('veolia_eau', 'debug', $row.' new data lines');
			} else {
				log::add('veolia_eau', 'error', 'Aucune donnée, merci de vérifier que vos identifiants sont corrects et que vous avez accès au télérelevé Veolia');	
			}
		} else {
			log::add('veolia_eau', 'debug', 'empty data');	
		}
		if (! empty($date)) {
            $this->setConfiguration('last', $date);
            $this->save(true);
        }
		@unlink($file);
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
