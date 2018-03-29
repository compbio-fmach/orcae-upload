<?php

  // API controller required
  App::uses('ApiController', 'Controller');

  // Files utilities
  App::uses('Folder', 'Utility');
  App::uses('File', 'Utility');

  /*
    Imports Spyc library for yaml parsing
    NOTE: in order to rely as less as possible on server configuration,
    Spyc has been used for yaml-php parsing and vice-versa
    It is a php-only library, which is slower than php_yaml.dll module
    but increases portability
  */
  App::import('Vendor', 'Spyc', array('file' => 'spyc/Spyc.php'));

  /*
    This controller returns default values.
    NOTE: due to yaml configuration, it needs PECl enabled in php.ini
  */
  class DefaultsController extends ApiController {

    // this API requires authentication
    public function beforeFilter() {
      // callback to parent beforeFilter to configure API response
      parent::beforeFilter();
      // callback to parent authentication check
      parent::authRequired();
    }

    public function index() {
      // checks if GET
      if($this->request->is('get')) {
        if(isset($this->params->query['file'])) {
          $file = $this->params->query['file'];
          // request of orcae_bogas .yaml configuration file
          if($file == 'orcae_bogas') {
            $this->orcaeConfig();
            return;
          }
          // request of species .yaml config file
          else if($file == 'species_config') {
            $this->speciesConfig();
            return;
          }
        }
      }

      // 404 not found
      parent::index();
    }

    // returns species_config default yaml file
    protected function speciesConfig() {
      // binding to species default .yaml config file
      $file = new File('../Config/Defaults/species_config.default.yaml');
      // reads file (turns file into string)
      $read = $file->read();

      // if there was an error while parsing the file
      // returns read, non-parsed, string
      if(!$read) {
        $this->response->statusCode('500');
        return;
      }

      //print_r($yaml);
      //return;

      // writes out user and data fileds
      // same syntax as Trpee_conf.default.yaml
      $count = 1;
      $read = str_replace('<username>', '\''.$this->user['username'].'\'', $read, $count);
      $read = str_replace('<today>', '\''.date('d/m/y').'\'', $read, $count);

      // outputs custom yaml file
      $this->response->statusCode('200');
      $this->response->body($read);
    }

    // returns orcae_bogas default yaml file
    protected function orcaeConfig() {
      // binding to orcae_bogas default .yaml config file
      $file = new File('../Config/Defaults/orcae_bogas.default.yaml');
      // reads file (turns file into string)
      $read = $file->read();

      // returns read file
      $this->response->statusCode('200');
      $this->response->body($read);
    }

  }

?>
