<?php
/**
 * ApiGenomeUploadsController implements /API/genomecs/upload routes
 * Being an API controller, it does not render pages
 */
App::uses('ApiController', 'Controller');
class ApiGenomeUploadsController extends ApiController {

  public $uses = array('User', 'RequestHandler', 'GenomeConfig');
  public $components = array(
    'Auth',
    'GenomeUpload'
  );

  public function beforeFilter() {
    $this->autoRender = false;
    // Checks authorization
    if(!$this->Auth->loggedIn()) {
      $this->error401();
    }
  }

  // Index function taken from https://github.com/hugodias/FileUpload/blob/master/Controller/HandlerController.php
  // Licensed under MIT
  public function index($id = null, $title = null) {
    // Fakes genome configuration passing only the id
    $this->GenomeUpload->set_genome_config(array('id' => $id));
    $method = $this->request->method();
    switch ($method) {
		    case 'OPTIONS':
		        break;
		    case 'HEAD':
		    case 'GET':
		        $this->GenomeUpload->get();
		        break;
		    case 'POST':
		        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
		            $this->GenomeUpload->delete();
		        } else {
		            $this->GenomeUpload->post();
		        }
		        break;
		    case 'DELETE':
		        $this->GenomeUpload->delete(true, $title);
		        break;
		    default:
		        header('HTTP/1.1 405 Method Not Allowed');
		}
  }
}

 ?>
