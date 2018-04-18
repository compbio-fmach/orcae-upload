<?php
/**
 * ApiSpeciesController implements /API/species routes
 * Being an API controller, it does not render pages
 * Routes:
 *  - GET /species returns every species in the database
 *  - GET /species?name=<name>&shortname=<shortname>&taxid=<taxid>
 *    Returns species rows based on a combination of parameters set in GET query
 * TODO: handle read rights of taxid table's genomes
 */
App::uses('ApiController', 'Controller'); // Required to extend ApiController
class ApiSpeciesController extends ApiController {

  // Defines models used by this controller
  public $uses = array('Species');

  public function beforeFilter() {
    // Initializes user
    parent::beforeFilter();
    // Checks authorization
    if(!$this->User->getUser()) {
      // thorws error 401 user not authorized
      $this->error401();
      // sends response
      $this->response->send();
      // stops execution flow
      $this->_stop();
    }
  }

  /**
   * @method read implements CRUD's READ method
   * Responds with found species
   * @return void
   */
  public function read() {
    // Array of conditions which will be passed to search query
    $select = array(
      'conditions' => array()
    );

    // Mantains a reference to GET querystring
    $get = $this->request->query;

    // Searches by species name only if specified
    if(isset($get['name'])) {
      $name = $get['name'];
      // Searches species names which starts with passed name
      $select['conditions']['Species.organism'] = "Species.organism LIKE $name.%";
    }
    // Searches by species 5code only if specified
    if(isset($get['5code'])) {
      $select['conditions']['Species.5code'] = $get['5code'];
    }
    // Searches by species taxid only if specified
    if(isset($get['taxid'])) {
      $select['conditions']['Species.NCBI_taxid'] = $get['taxid'];
    }
    // Limits output (client gets a faster response because it )
    if(isset($get['limit']) && is_int($get['limit']) && $get['limit'] >= 0) {
      $select['limit'] = $get['limit'];
    }
    // Orders output using order param (format: <field> <empty|asc|desc>)
    if(isset($get['order']) && preg_match('/^(name|5code|taxid)+\s(|ASC|DESC)$/', $get['order'])) {
      // Retrieves order param splitted to find order field and sort
      $order = explode(' ', $get['order']);
      // Retrived order field
      $field = $order[0];
      // Restrieves order sort (ASC by default)
      $sort = count($order) > 1 ? $order[1] : 'ASC';

      // Parses field
      switch($field) {
        case 'name':
          $field = 'organism';
          break;
        case 'taxid':
          $field = 'NCBI_taxid';
          break;
      }

      // Sets order field into select
      $select['order'] = "Species.$field $sort";
    }

    // Searches species agains orcae_bogas.taxid table using params found into GET querystring
    $species = $this->Species->find('all', $select);
    // Parses every row
    foreach($species as &$s) {
      $s = $this->Species->parseSpecies($s['Species'], '');
    }

    // Sets response code
    $this->response->statusCode(200);
    // Sets response body
    $this->response->body(json_encode($species));
  }
}

?>
