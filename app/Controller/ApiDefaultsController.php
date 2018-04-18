<?php

/**
 * Returns files with default values stored in /Config/Defaults/
 * Sets value specific for every request
 */

App::uses('ApiController', 'Controller'); // APIs controller required
App::uses('Folder', 'Utility'); // Folder utility required to search for correct file
App::uses('File', 'Utility'); // File utility required to read a file
/**
 * Imports Spyc library for yaml parsing
 * NOTE: in order to rely as less as possible on server configuration,
 * Spyc has been used for yaml-php parsing and vice-versa
 * It is a php-only library, which is slower than php_yaml.dll module
 * but increases portability
*/
App::import('Vendor', 'Spyc', array('file' => 'spyc/Spyc.php'));

class ApiDefaultsController extends ApiController {

  // File that will be read
  protected $file = null;
  // File value that will be read and returned
  protected $read = null;

  // this API requires authentication
  public function beforeFilter() {
    // Initializes parent values
    parent::beforeFilter();
    // Checks if user is authorized
    if(!$this->User->getUser()) {
      // If user is not authorized, responds with http code '401 unauthorized'
      $this->response = $this->error401();
      // Sends response (automatic response doesn't get called due to _stop function)
      $this->response->send();
      // Exits the execution flow (do not want to execute methods taht require authentication)
      $this->_stop();
    }
  }

  // Retrieves file from passed param and returns file value
  public function index() {
    // Retrieves file name from parameters
    $fileName = $this->params['file'];

    // Validates file name (one could pass ../../root, the system must deny those requests)
    // Checks if file name is <name>.default.<type>
    if(!preg_match('/^\w+\.default\.[a-zA-Z]+$/', $fileName)) {
      return $this->errorXxx("Unvalid request has been issued!", 403);
    }

    // Defines defaults folder
    $dir = new Folder(ROOT.DS.'app'.DS.'Config'.DS.'Defaults'.DS);
    // Checks if error occurred while searching for Defaults directory
    if(empty($dir->path)) {
      return $this->error5xx();
    }

    // Searches for selected file
    if(empty($dir->find($fileName))) {
      // Returns error file not found
      return $this->error404("Requested file not found!");
    }

    // If execution flow reaches this point, file has been found and can be read
    $this->file = new File($dir->path.$fileName);
    // Reads the selected file
    $this->read = $this->file->read();

    // Checks read errors
    if(!$this->read) {
      return $this->error5xx();
    }

    switch($fileName) {
      // Config_5code.default.yaml needs to have some values parsed
      case 'config_species.default.yaml':
        // Calls config_orcae inizializarion function
        $this->initConfigSpecies();
      default:
        // Set response http code as '200 OK'
        $this->response->statusCode(200);
        // Sets response body
        $this->response->body($this->read);
        // Exits the function
        return $this->response;
    }
  }

    /**
     * @method initConfigSpecies initializes values of a copy of config_species.default.yaml
     * Every request will have a custom default file
     * @return void
     */
    protected function initConfigSpecies() {
      $user = $this->User->getUser();
      // writes out user and data fileds
      // same syntax as Trpee_conf.default.yaml
      $count = 1;
      // Substitutes <username> with current user's username
      $this->read = str_replace('<username>', '\''.$user['username'].'\'', $this->read, $count);
      // Substitutes <today> with today's date
      $this->read = str_replace('<today>', '\''.date('d/m/y').'\'', $this->read, $count);
    }

  }

?>
