<?php
/**
 * ApiGenomeUploadsController implements /API/genomecs/upload routes
 * Being an API controller, it does not render pages
 */
App::uses('ApiController', 'Controller');
class ApiGenomeUploadsController extends ApiController {

  public $uses = array('User', 'GenomeConfig');
  public $components = array(
    'Auth',
    'GenomeUpload',
    'RequestHandler'
  );

  public function beforeFilter() {
    // Defines response as json
    $this->RequestHandler->renderAs($this, 'json');
    // From there on allows every route
    $this->Auth->allow();
    // Checks authorization
    if(!$this->Auth->loggedIn()) {
      $this->error401();
    }
  }

  // Index function taken from https://github.com/hugodias/FileUpload/blob/master/Controller/HandlerController.php
  // Licensed under MIT
  public function index($id = null, $name = null) {
    // Fakes genome configuration passing only the id
    $this->GenomeUpload->set_genome_config(array(
      'id' => $id,
      'user_id' => $this->Auth->user('id')
    ));
    // Handles request method
    switch ($this->request->method()) {
		    case 'HEAD':
		    case 'GET':
          $this->read();
          break;
		    case 'POST':
          $delete = (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE');
          $delete ? $this->delete() : $this->create();
          break;
		    case 'DELETE':
	        $this->delete();
	        break;
		    default:
          // Sets 'method not allowed http code'
		      $this->response->statusCode(405);
          $this->set('response', '');
          break;
		}
    // Serializes response
    $this->set('_serialize', 'response');
  }

  // Defines action to handle GET method
  protected function read() {
    $this->response->statusCode(200);
    $this->set('response', $this->GenomeUpload->get(false));
    // $this->set('_serialize', 'response');
  }

  // Defines action to handle POST method
  protected function create() {
    // Retrieves response without wrapper element
    $response = $this->GenomeUpload->post(false);
    $response = array_shift($response);
    // Sets response
    $this->set('response', $response);
    // $this->set('_serialize', 'response');
  }

  // Defines action to handle DELETE method (either done through POST method)
  protected function delete() {
    $this->response->statusCode(200);
    $this->set('response', $this->GenomeUpload->delete(false));
    // $this->set('_serialize', 'response');
  }

}

 ?>
