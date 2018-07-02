<?php
  App::uses('ApiController', 'Controller');
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

    /*
    // Undo genome update
    protected function undoGenomeUpdate($update) {
      return $this->GenomeUpdate->undoUpdate($update);
    }
    */

    // Defies REST routes (CRUD)
    // Create is the only function needed
    protected function create() {
      // Retrieves uploads
      $this->findGenomeUploads();
      // Creates an update istance with uploads and updates inside
      $this->update = array(
        'config_id' => $this->config['id'],
        'config' => $this->config,
        'uploads' => $this->uploads,
        'status' => 'updating'
      );

      // Tries to create new update
      $result = $this->GenomeUpdate->initUpdate($this->update);
      // Checks initialization result
      if($result !== true) {
        $this->response->statusCode(500);
        $this->set('errors', array('validation' => $result)); // Serializes errors as output
        $this->set('_serialize', 'errors');
        return; // Exit
      }

      // Lauches background update script
      $this->GenomeUpdate->startProcess($this->update);

      // In case of success: responds with http code 204 No Body
      $this->response->statusCode(200);
      // Serializes update
      unset($this->update['config']);
      unset($this->update['uploads']);
      $this->set('update', $this->update);
      $this->set('_serialize', 'update');
    }

    // Reads last update relative to passed config id
    protected function read() {
      $config = &$this->config;
      // Retrieves updates
      $updates = $this->GenomeConfig->getGenomeUpdates($config);

      // Outputs response
      $this->response->statusCode(200);
      $this->set('updates', $updates);
      $this->set('_serialize', 'updates');
    }

    // Update method is not implemented
    protected function update() {
      $this->error404();
    }

    // Delete is not implemented
    protected function delete() {
      $this->error404();
    }
    /*
    protected function delete() {
      // $this->error404();
      // Retrieves last update
      $last = $this->findLastGenomeUpdate();
      // No update found: returns error
      $alert = "No update is actually active, therefore, no update could be stopped";
      if(empty($last) || preg_match('/^(success|error)$/', $last['step'])){
        return $this->errorXxx($alert, 500, true);
      }
      // Accordingly to genome update status, stops it
      $stopped = $this->undoGenomeUpdate($last);
      // Returns response
      if($stopped !== true) {
        $this->errorXxx($stopped, 500, true);
      } else {
        $this->respone->statusCode(200);
        $this->set('_serialize', '');
      }
    }
    */
  }
?>
