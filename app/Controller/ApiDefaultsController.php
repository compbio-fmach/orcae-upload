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

  // Files directory
  protected $dir;

  // this API requires authentication
  public function beforeFilter() {
    // Initializes parent values
    parent::beforeFilter();
    // Checks if user is authorized
    if(!$this->Auth->loggedIn()) {
      $this->error401(true);
    }

    $this->dir = WWW_ROOT.DS.'files'.DS.'defaults';
  }

  // Retrieves file from passed param and returns file value
  public function index() {
    // Retrieves file name from parameters
    $file = $this->request->query('file');

    if($file == 'config_species') {
      $this->set('read', $this->readConfigSpecies());
    }
    else if( $file == 'config_bogas') {
      $this->set('read', $this->readConfigBogas());
    }
    else {
      $this->error404('Requested resource has not been found!');
      return;
    }

    // Sets response header as 200 OK
    $this->response->statusCode(200);
    // Defines response as yaml
    $this->RequestHandler->renderAs($this, 'application/x-yaml');
    $this->set('_serialize', 'read');
  }

  protected function readConfigSpecies() {
    // Retrieves user id
    $user = $this->Auth->user('username');
    // Retrieves file content
    $file = new File($this->dir.DS.'config_species.yaml');
    $read = $file->read();
    // Number of times a string must be replaced
    $count = 1;
    // Substitutes <username> with current user's username
    $read = str_replace('<username>', '\''.$user.'\'', $read);
    // Substitutes <today> with today's date
    $read = str_replace('<today>', '\''.date('d/m/y').'\'', $read);

    return $read;
  }

  protected function readConfigBogas() {
    // Retrieves file content
    $file = new File($this->dir.DS.'config_bogas.yaml');
    $read = $file->read();
    return $read;
  }
}
?>
