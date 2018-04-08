<?php
/**
 *
 */

class eqLogic{
  private $website = 2;
  private $login = 1;
  private $password = "PSW1";
  private $depart = 0;
  private $compteur = 0;
  private $offsetVeoliaDate = 3;
  private $last="";
  private $alert="#680";
  private $mock_file="veolia_sudest_data_src/veolia_html_3March.htm";
  private $csv_mock_file="";
  private $mock_date="";
  private $maxday=1000;
  private $maxmonth=10000;
  public function __construct()
  {
     $this->last      =  (new \DateTime())->format('Y-m-d');
     $this->mock_date =  (new \DateTime())->format('Y-m-d');
   }

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
    }elseif ($config=="offsetVeoliaDate"){
      return $this->offsetVeoliaDate;
    }elseif ($config=="last"){
      return $this->last;
    }elseif ($config=="alert"){
      return $this->alert;
    }elseif ($config=="mock_file"){
      return $this->mock_file;
    }elseif ($config=="csv_mock_file"){
      return $this->csv_mock_file;
    }elseif ($config=="mock_date"){
      return $this->mock_date;
    }elseif ($config=="maxday"){
      return $this->maxday;
    }elseif ($config=="maxmonth"){
      return $this->maxmonth;
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
      }elseif ($config=="offsetVeoliaDate"){
        $this->offsetVeoliaDate=$value;
      }elseif ($config=="last"){
        $this->last=$value;
      }elseif ($config=="alert"){
        $this->alert=$value;
      }elseif ($config=="mock_file"){
        $this->mock_file=$value;
      }elseif ($config=="csv_mock_file"){
        $this->csv_mock_file=$value;
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
  static public function byId($argument)
  {
    return new self();
  }
  public function execCmd($cmd)
  {
    log::add('veolia_eau','debug','execCmd:'.serialize($cmd));
  }
}

class log {
  static public function add($plugin,$level,$message)
  {
    echo "\n".$plugin." ".$level." ".$message."\n";
  }
}

/**
 *
 */
?>
