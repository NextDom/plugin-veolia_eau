<?php
/**
 *
 */

require_once './veolia_eauMockJeedom.class.php';
require_once '../core/class/veolia_eau_process.class.php' ;
$instanceVeolia = new veolia_eau;
$instanceVeolia->setConfiguration('last',"2018-03-09");
$instanceVeolia->setConfiguration('website',3);

$instanceVeolia->setConfiguration('mock_date',"2018-03-12");
$instanceVeolia->setConfiguration('mock_file',"Veolia-Lyon-Apr/veolia_html_Lyon_Apr18.htm");
$instanceVeolia->setConfiguration('csv_mock_file',"Veolia-Lyon-Apr/veolia_releve_Lyon_Apr18.csv");
$instanceVeolia->displayConfig();
$instanceVeolia->getConso(2);

/**
 *
 */
?>
