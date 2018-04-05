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
    It is built in plain php, thus any server configuration is required
  */
  class DefaultsApiController extends ApiController {

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
          if($file == 'config_orcae') {
            $this->configOrcae();
            return;
          }
          // request of species .yaml config file
          else if($file == 'config_species') {
            $this->configSpecies();
            return;
          }
        }
      }

      // 404 not found
      parent::index();
    }

    // returns species_config default yaml file
    protected function configSpecies() {
      // binding to species default .yaml config file
      $file = new File($this->configUrl('config_species.default.yaml'));
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
    protected function configOrcae() {
      // binding to orcae_bogas default .yaml config file
      $file = new File($this->configUrl('config_orcae.default.yaml'));
      // reads file (turns file into string)
      $read = $file->read();

      // returns read file
      $this->response->statusCode('200');
      $this->response->body($read);
    }

    // creates config url
    private function configUrl($fileName) {
      // defines path as array
      $path = array(ROOT, 'app', 'Config', 'Defaults');
      // adds filename as last position
      $path[] = $fileName;
      // creates url
      return implode(DS, $path);
    }

  }

?>
