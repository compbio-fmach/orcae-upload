<?php
/**
 * ApiGenomeConfigsController implements /API/genomeconfigs routes
 * Being an API controller, it does not render pages
 */
App::uses('ApiController', 'Controller'); // Required to extend ApiController
class ApiGenomeConfigsController extends ApiController {

  public $uses = array('User', 'GenomeConfig');

  public function beforeFilter() {
    parent::beforeFilter();

    if(!$this->Auth->loggedIn()) {
      $this->error401(true);
    }
  }

  // This controller handles RESTful routes
  public function index($id = null) {
    $this->restify($id);
  }

  protected function create() {
    // Parses data given in request body into Genome Config object
    $data = $this->GenomeConfig->parse($this->request->data);
    // Initializes values correctly cor creation
    $data['id'] = null;
    $data['user_id'] = $this->Auth->user('id');
    $data['created'] = $data['updated'] = date('Y-m-d H:i:s');

    // Validates data
    $validation = $this->GenomeConfig->validate($data);
    $this->set('validation', $validation);

    // Saves only if no error has been returned from validation
    if($validation === true || isset($validation['errors'])) {
      $this->GenomeConfig->save($data);
      $this->response->statusCode(200);
      $this->set('id', $this->GenomeConfig->id);
    } else {
      $this->response->statusCode(400);
    }

    $this->set('_serialize', array('id', 'validation'));
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
      $this->set('genomeConfig', $result['GenomeConfig']);

    } else {
      $this->response->statusCode(404);
      $this->set('genomeConfig', null);
    }

    $this->set('_serialize', 'genomeConfig');
  }

  protected function readMany() {
    $rows = $this->GenomeConfig->find(
      'all',
      array(
        // Does not retrieve biggest field which contain configuration files content
        // and are useless in a genomecs overview
        'fields' => array('id', 'user_id', 'created', 'updated', 'type', 'species_taxid', 'species_name', 'species_5code'),
        // Retrieves only sessions owned by current user
        'conditions' => array('GenomeConfig.user_id' => $this->Auth->user('id'))
      )
    );

    // Parses returned rows (uses only object data)
    foreach ($rows as &$row) {
      $row = $row['GenomeConfig'];
    }

    $this->response->statusCode(200);
    $this->set('genomeConfigs', $rows);
    $this->set('_serialize', 'genomeConfigs');
  }

  protected function update($id) {
    // Parses data given in request body into Genome Config object
    $data = $this->GenomeConfig->parse($this->request->data);
    // Initializes values correctly cor creation
    $data['id'] = $id;
    unset($data['user_id']);
    unset($data['created']);
    $data['updated'] = date('Y-m-d H:i:s');

    $validation = $this->GenomeConfig->validate($data);
    $this->set('validation', $validation);

    // Saves only if no error has been returned from validation
    if($validation === true || isset($validation['errors'])) {
      $this->GenomeConfig->save($data);
      $this->response->statusCode(200);
      $this->set('id', $this->GenomeConfig->id);
    } else {
      $this->response->statusCode(400);
    }

    // Validates data
    $validation = $this->GenomeConfig->validate($data);
    $this->set('validation', $validation);

    $this->set('_serialize', array('id', 'validation'));
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
