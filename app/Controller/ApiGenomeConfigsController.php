<?php
/**
 * ApiGenomeConfigsController implements /API/genomeconfigs routes
 * Being an API controller, it does not render pages
 */
App::uses('ApiController', 'Controller'); // Required to extend ApiController
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
class ApiGenomeConfigsController extends ApiController {

  public $uses = array('User', 'GenomeConfig');

  public function beforeFilter() {
    parent::beforeFilter();

    if(!$this->Auth->loggedIn()) {
      $this->error401(true);
    }
  }

  /**
   * @method parseRequest
   * Parses request to retrieve values used into this controller
   * @return void
   */
  protected function parseRequest() {
    // Request istance
    $request = $this->request;
    // Request method
    $method = $request->method();
    // Object that will be returned
    $parsed = array();

    if($method == 'PUT' || $method == 'POST') {
      // Retrieves Genome Configuration schema
      foreach($this->GenomeConfig->schema() as $attribute => $description) {
        // Initializes attribute's value if it has been passed
        $parsed[$attribute] = $request->data($attribute);
      }

      // Retrieves species image from files
      if(isset($_FILES['species_image'])) {
        $parsed['species_image'] = $_FILES['species_image'];
      }

      // Defines Genome Configuration type
      $type = ($parsed['type'] == 'insert' || $parsed['type'] == 'update') ? $parsed['type'] : 'insert';
      // Deletes some fields from parsed data (which will not be validated and saved)
      if($type == 'update') {
        // Unsets fields which will not be used
        unset(
          $parsed['group_description'],
          $parsed['group_welcome'],
          $parsed['config_species'],
          $parsed['config_bogas']
        );
      }

      // Unsets 'updated' and 'modified' because are handled automatically by cakephp
      unset($parsed['created'], $parsed['updated']);
    }

    return $parsed;
  }

  // This controller handles RESTful routes
  public function index($id = null) {
    $this->restify($id);
  }

  protected function create() {
    // Parses data given in request body into Genome Config object
    $data = $this->parseRequest();
    // Initializes values at creation time
    $data['id'] = null;
    $data['user_id'] = $this->Auth->user('id');

    // Validates data (only set fields)
    $validation = $this->GenomeConfig->validate($data);
    // Defines if validation found some errors
    $valid = empty($validation['errors']);
    // Saves only if no error has been returned from validation
    if($valid) {
      $this->GenomeConfig->save($data, false);
      $data['id'] = $this->GenomeConfig->id;
      $this->response->statusCode(200);
    } else {
      $this->response->statusCode(400);
    }

    // If no errors has been found, uploads image
    if($valid && isset($data['species_image'])) {
      $this->updateSpeciesImage($data, $validation);
    }

    // Adds species image url to data
    $data['species_image'] = $this->GenomeConfig->getSpeciesImage($data);
    // Adds validation to data
    $data['validation'] = $validation;
    // Sets response
    $this->set('data', $data);
    $this->set('_serialize', 'data');
  }

  protected function read($id) {
    // Returns single row bound to passed id
    if(!empty($id)) {
      $this->readOne($id);
    }
    // return every row bound to user
    else {
      $this->readMany();
    }
  }

  protected function readOne($id) {
    $result = $this->GenomeConfig->find(
      'first',
      array(
        'conditions' => array(
          'GenomeConfig.id' => $id,
          'GenomeConfig.user_id' => $this->Auth->user('id')
        )
      )
    );

    if(!empty($result)) {
      $this->response->statusCode(200);
      $result['GenomeConfig']['species_image'] = $this->GenomeConfig->getSpeciesImage($result['GenomeConfig']);
      $this->set('data', $result['GenomeConfig']);
    } else {
      $this->response->statusCode(404);
      $this->set('data', null);
    }

    $this->set('_serialize', 'data');
  }

  protected function readMany() {
    $rows = $this->GenomeConfig->find(
      'all',
      array(
        // Does not retrieve biggest field which contain configuration files content
        // and are useless in a genomecs overview
        'fields' => array('id', 'user_id', 'created', 'modified', 'type', 'species_taxid', 'species_name', 'species_5code'),
        // Retrieves only sessions owned by current user
        'conditions' => array('GenomeConfig.user_id' => $this->Auth->user('id'))
      )
    );

    // Parses returned rows (uses only object data)
    foreach ($rows as &$row) {
      $row = $row['GenomeConfig'];
    }

    $this->response->statusCode(200);
    $this->set('data', $rows);
    $this->set('_serialize', 'data');
  }

  protected function update($id) {
    // Parses data given in request body into Genome Config object
    $data = $this->parseRequest();
    // Initializes values correctly cor creation
    $data['id'] = $id;
    unset($data['user_id']);

    $validation = $this->GenomeConfig->validate($data);
    $valid = empty($validation['errors']);

    // Saves only if no error has been returned from validation
    if($valid) {
      $this->GenomeConfig->save($data, false);
      $this->response->statusCode(200);
      $data['id'] = $this->GenomeConfig->id;
    } else {
      $this->response->statusCode(400);
    }

    // If no errors has been found, uploads image
    if($valid && isset($data['species_image'])) {
      $warning = $this->GenomeConfig->updateSpeciesImage($data);
      if($warning !== true) {
        $validation['warnings']['species_image'] = $warning;
      }
    }

    // Adds species image url to data
    $data['species_image'] = $this->GenomeConfig->getSpeciesImage($data);
    // Adds validation to data
    $data['validation'] = $validation;
    // Sets response
    $this->set('data', $data);
    $this->set('_serialize', 'data');
  }

  protected function delete($id) {
    $this->GenomeConfig->deleteAll(array(
      'GenomeConfig.id' => $id,
      'GenomeConfig.user_id' => $this->Auth->user('id')
    ));

    $this->response->statusCode(204);
  }

}
?>
