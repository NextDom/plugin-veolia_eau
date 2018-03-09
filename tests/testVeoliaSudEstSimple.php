<?php
/**
 *
 */

require_once '../core/class/veolia_eau_process.class.php' ;

class eqLogic{
  private $website = 2;
  private $login = 1;
  private $password = PSW1;
  private $depart = 0;
  private $compteur = 0;
  private $last="2018-03-01";
  private $alert="#680";
  private $mock_file="veolia_sudest_data_src/veolia_html_3March.htm";
  private $mock_date="2018-03-07";
  public function getConfiguration($config){
    if ($config=="website"){
      return $this->website;
    } elseif ($config=="login"){
      return $this->login;
    } elseif ($config=="password"){
      return $this->password;
    }elseif ($config=="depart"){
      return $this->depart;
    }elseif ($config=="compteur"){
      return $this->compteur;
    }elseif ($config=="last"){
      return $this->last;
    }elseif ($config=="alert"){
      return $this->alert;
    }elseif ($config=="mock_file"){
      return $this->mock_file;
    }elseif ($config=="mock_date"){
      return $this->mock_date;
    }
    else {
      log::add('veolia_eau','debug','getConfiguration:'.$config);
      return "ko";
    }
  }
  public function setConfiguration($config, $value){
      if ($config=="website"){
        $this->website=$value;
      } elseif ($config=="login"){
        $this->login=$value;
      } elseif ($config=="password"){
        $this->password=$value;
      }elseif ($config=="depart"){
        $this->depart=$value;
      }elseif ($config=="compteur"){
        $this->compteur=$value;
      }elseif ($config=="last"){
        $this->last=$value;
      }elseif ($config=="alert"){
        $this->alert=$value;
      }elseif ($config=="mock_file"){
        $this->mock_file=$value;
      }elseif ($config=="mock_date"){
        $this->mock_date=$value;
      }
      else {
        log::add('veolia_eau','debug','setConfiguration:'.$config." ".$value);
        return "ko";
      }
  }
  public function displayConfig(){
         log::add('veolia_eau','debug','displayConfig:'.
         " website:".
          $this->website.
          " login:".
          $this->login.
          " password:".
          $this->password.
          " depart:".
          $this->depart.
          " compteur:".
          $this->compteur.
          " last:".
          $this->last.
          " alert:".
          $this->alert.
          " mock_date:".
          $this->mock_date
          );
  }
  function getCmd($a,$b)
  {
    return 0;
  }
  function save ($cmd){
    // log::add('veolia_eau','debug','save:'.$cmd);
  }
}
class cmd {
  public function byId($argument)
  {
    return new cmd;
  }
  public function execCmd($cmd)
  {
    log::add('veolia_eau','debug','execCmd:'.$cmd);
  }
}

class log {
  public function add($plugin,$level,$message)
  {
    echo "\n".$plugin." ".$level." ".$message."\n";
  }
}


// Unit tests //
//class veoliaTest extends PHPUnit_Framework_TestCase {
  //public function test_secure_touch(){
  //    $this->assertNull(veolia_eau::secure_touch("sdetgFSFD"));
  //    $this->assertEquals(1,file_exists( "sdetgFSFD" ));
      //log::add('veolia_eau', 'debug', 'PHPUnit_Framework_TestCase');

  //}
  //public function test_getConso(){
  //  $this->assertNull(veolia_eau::getConso2(TRUE));
//  }
//}
$instanceVeolia = new veolia_eau;
$instanceVeolia->setConfiguration('mock_date',"2018-03-07");
$instanceVeolia->setConfiguration('mock_file',"veolia_sudest_data/veolia_html_3March.htm");
$instanceVeolia->displayConfig();
$instanceVeolia->getConso(2);

// Test avec l access au site Veolia - Penser a MAJ les ID/password
//$instanceVeolia->setConfiguration('login',"xx");
//$instanceVeolia->setConfiguration('password',"xx");
//$instanceVeolia->getConso(1);
//$instanceVeolia->displayConfig();



/**
 *
 */
?>
