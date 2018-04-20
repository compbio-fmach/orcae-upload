<?php
/**
 * ApiGenomeUploadsController implements /API/genomecs/upload routes
 * Being an API controller, it does not render pages
 */
App::import('Vendor', 'UploadHandler', array('file' => 'uploader/UploadHandler.php'));
App::uses('ApiController', 'Controller');
class ApiGenomeUploadsController extends ApiController {

  public $uses = array('User');

  public function beforeFilter() {
    parent::beforeFilter();

    // Initializes attributes
    $this->user = $this->User->getUser();

    // Checks authorization
    if(!$this->user) {
      $this->response = $this->error401();
      $this->response->send();
      $this->_stop();
    }
  }

  /**
   * Handles POST request to /uploads
   */
  public function index() {
    $this->create();
  }

  /**
   * Retrieves file/s information
   */
  public function read() {}

  /**
   * Uploads file
   */
  public function create() {
    /*
    // DEBUG
    echo var_dump($_FILES);
    echo var_dump(WWW_ROOT.'files');
    */
    
    $uploader = new UploadHandler(array(
      'upload_dir' => WWW_ROOT.'files/',
      'accept_file_types' => '/\.(gif|jpe?g|png)$/i'
    ));
  }
}

 ?>
