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

    // Returns lastly started genome update
    protected function findLastGenomeUpdate() {
      $last = $this->GenomeUpdate->findLast($this->config);
      // If genome update has been found, adds configuration istance to it
      if(!empty($last)) $last['config'] = $this->config;
      // Returns query result
      return $last;
    }

    // Undo genome update
    protected function undoGenomeUpdate($update) {
      return $this->GenomeUpdate->undoUpdate($update);
    }

    /*
    // Checks if a genome (species_taxid + speceis_name) can be uset to update Orcae
    protected function isGenomeUpdatable() {
      // Searches for last
      $last = $this->findLastGenomeUpdate();
      // If last is empty and a genome with same taxid

    }
    */

    // Defies REST routes (CRUD)
    // Create is the only function needed
    protected function create() {
      // Retrieves uploads
      $this->findGenomeUploads();
      // Retrieves last update
      // $this->retrieveLast();
      // Creates an update istance with uploads and updates inside
      $this->update = array(
        'config' => $this->config,
        'uploads' => $this->uploads
      );
      // Tries to create new update
      if(true !== $result = $this->GenomeUpdate->initUpdate($this->update))
        return $this->errorXxx($result, 500, true);
      // Validates uploads status
      if(true !== $result = $this->GenomeUpdate->validateUploads($this->update))
        return $this->errorXxx($alert, 500, true);
      // Initializes update configuration
      if(true !== $result = $this->GenomeUpdate->initConfig($this->update))
        return $this->errorXxx($result, 500, true);
      // Launches parallel background processing
      $this->GenomeUpdate->startProcess($this->update);
      /*
      // Initializes update: creates new row
      if(true !== $result = $this->GenomeUpdate->initUpdate($this->update))
        return $this->errorXxx($result, 500, true);
      // Initializes update folder
      if(true !== $result = $this->GenomeUpdate->initUpdateFolder($this->update))
        return $this->errorXxx($result, 500, true);
      // Executes parsing scripts: parses folder into .csv
      if(true !== $result = $this->GenomeUpdate->parseUpdateFolder($this->update))
        return $this->errorXxx($result, 500, true);
      // Updates Orcae's database
      if(true !== $result = $this->GenomeUpdate->updateOrcaeDb($this->update))
        return $this->errorXxx($result, 500, true);
      */
      // In case of success: responds with http code 204 No Body
      $this->response->statusCode(204);
      // Serializes to set this as json view
      $this->set('_serialize', '');
    }

    // Read method is not implemented
    protected function read($updateId) {
      $this->error404();
    }

    // Update method is not implemented
    protected function update($updateId) {
      $this->error404();
    }

    // Delete is not implemented
    protected function delete($updateId) {
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
        $this->respone->statusCode(204);
        $this->set('_serialize', '');
      }
    }
  }
?>
