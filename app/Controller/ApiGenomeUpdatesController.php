<?php
  App::uses('ApiController', 'controller');
  App::uses('Folder', 'Utility');
  App::uses('File', 'Utility');
  class ApiGenomeUpdatesController extends ApiController {
    public $uses = array('User', 'RequestHandler', 'GenomeConfig', 'GenomeUpload', 'GenomeUpdate');

    public $config, $update, $uploads;

    // Handles REST requests
    public function index($configId = null, $updateId = null) {
      // Checks if configuration is valid
      $this->findGenomeConfig($configId);
      // Case genome configuration is not valid: returns error
      if(empty($this->config)) {
        $this->error404('Genome configuration istance not found');
      }
      // Case genome configuration is valid
      else {
        $this->restify($updateId);
      }
    }

    // Returns config associated with passed genome config id
    protected function findGenomeConfig($configId) {
      $config = $this->GenomeConfig->find('first', array(
        'conditions' => array(
          'GenomeConfig.id' => $configId,
          'GenomeConfig.user_id' => $this->Auth->user('id')
        )
      ));
      // Stores found genome configuration, if any
      $this->config = empty($config) ? null : $config['GenomeConfig'];
    }

    // Returns every file uploaded into a given genome configuration
    protected function findGenomeUploads() {
      // Searches into database for genome uploads
      $uploads = $this->GenomeUpload->find('all', array(
        'conditions' => array(
          'GenomeUpload.config_id' => $this->config['id']
        )
      ));
      // Parses upload istances
      foreach ($uploads as &$upload) {
        $upload = $upload['GenomeUpload'];
        $upload['file'] = $this->GenomeUpload->getUploadPath($this->Auth->User('id'), $upload['stored_as']);
      }
      // Retruns found uploads
      $this->uploads = $uploads;
    }

    // Defies REST routes (CRUD)
    // Create is the only function needed
    protected function create() {
      // Retrieves uploads
      $this->findGenomeUploads();
      // Creates an update istance with uploads and updates inside
      $this->update = array(
        'config' => $this->config,
        'uploads' => $this->uploads
      );

      // Due to parallel process execution, exception must be handled
      try {
        // Validates uploads status
        if(!$this->GenomeUpdate->validateUploads($this->update)) {
          // Exits with error
          $result = 'There could be some file not fully uploaded yet. Otherwise, annotation file or genome file could be missing.';
          return $this->errorXxx($result, 500, true);
        }
        // Initializes update configuration
        $result = $this->GenomeUpdate->initConfig($this->update);
        if($result !== true) {
          return $this->errorXxx($result, 500, true);
        }
        // Initializes update: creates new row
        $result = $this->GenomeUpdate->initUpdate($this->update);
        if($result !== true) {
          return $this->errorXxx($result, 500, true);
        }
        // Initializes update folder
        $this->GenomeUpdate->initUpdateFolder($this->update);
        // Executes parsing scripts: parses folder into .csv
        $this->GenomeUpdate->parseUpdateFolder($this->update);
        // Initializes species' database
        $this->GenomeUpdate->initDatabase($this->update);
        // Executes scripts for uploading parsed files into database
        // TODO: $this->GenomeUpdate->saveUpdateFolder();
        // In case of success: responds with http code 204 No Body
        $this->response->statusCode(204);
      } catch (Exception $e) {
        $this->response->statusCode(500);
        // Enables exception full description only if debug value is higher than 0
        $this->set('error', $error = Configure::read('debug') > 0 ? (string)$e : $e->getMessage());
        $this->set('_serialize', 'error');
      }
    }

    // Read is not implemented
    protected function read($id) {
      $this->error404();
    }
    // Update is not implemented
    protected function update($id) {
      $this->error404();
    }
    // Delete is not implemented
    protected function delete($id) {
      $this->error404();
    }
  }
?>
