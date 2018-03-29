<?php

  // API controller required
  App::uses('ApiController', 'Controller');

  // Files utilities
  App::uses('Folder', 'Utility');
  App::uses('File', 'Utility');

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
      //parent::authRequired();
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
      // TODO parse yaml file
      $yaml = yaml_parse($read);

      // if there was an error while parsing the file
      // returns read, non-parsed, string
      if(!$yaml) {
        $this->response->statusCode('500');
        $this->response->body($read);
        return;
      }

      // writes out user and data fileds
      // same syntax as Trpee_conf.default.yaml
      $yaml['User'] = $this->user['username'];
      $yaml['Date'] = date('d/m/y');

      // outputs custom yaml file
      $this->response->statusCode('200');
      $this->response->body(yaml_emit($yaml));
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
