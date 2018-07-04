<?php
/**
 * This shell is intended to be executed in a parallel php process
 * This allows /API/genome_congif/:config_id/updates/ to return an immediate response, while launching this shell
 * There are handled the most resource-consuming actions, which requires some time to complete
 * Usually, when this shell terminates, calls itself, updating GenomeUpdate status, until every step of execution has been completed
 * WARNING: since this shell is called only by controller, there is non need of cheking user credentials
 */
class GenomeUpdateShell extends AppShell {
  // Models required from this shell
  public $uses = array('GenomeConfig', 'GenomeUpdate', 'GenomeUpload');

  protected $update = array();

  // Retrieves shell input parameters
  public function getOptionParser() {
      $parser = parent::getOptionParser();
      // Configure parser
      $parser->addOption('update', array(
        'short' => 'u',
        'help' => 'The id of Genome Update istance which must be actually updated',
        'required' => true
      ));
      // Returns parser as required from docs
      return $parser;
  }

  // Initializes config and update
  public function main() {
    // Defines reference to update istance
    $update = &$this->update;

    // Initializes update istance
    $this->initUpdate();

    // Creates update folder
    $this->createUpdateFolder();
    // Parses files into .cvs format
    $this->parseUpdateFolder();
    // Parses uploaded files into .csv and loads them into orcae_<5code> database
    $this->loadUpdateFolder();
    // Configures Orcae for genome update (writes config files)
    $this->initConfig();
    // Saves configuration into orcae_bogas database
    $this->saveConfig();

    // If execution reaches this point: sets status to success
    $this->GenomeUpdate->updateStatus($update, 'success');
  }

  // Initializes genome update istance
  protected function initUpdate() {
    $update = &$this->update;

    // Starts transaction
    $db = $this->GenomeUpdate->getDataSource();
    $db->begin();

    // Defines update istance
    $update['id'] = $this->param('update');
    // Redefines update istance retrieving it from database
    $update = $this->GenomeUpdate->findById($update['id']);
    $update = !empty($update) ? $update['GenomeUpdate'] : null;
    // Checks if update has been found
    if(!$update) {
      $db->rollback();
      $this->error('update-not-found', 'Could not find Genome Update instance bound to passed id');
    }

    // Retrieves Genome Config associated with given Genome Update
    $config = array('id' => $update['config_id']);
    $config = $this->GenomeConfig->findById($config['id']);
    // Checks that configuration is valid
    $config = !empty($config) ? $config['GenomeConfig'] : null;
    // Returns error if Genome Config not found
    if(!$config) {
      $db->rollback();
      $this->error('config-not-found', 'Could not find Genome Configuration instance bound to given Genome Update');
    }
    // Puts configuration istance into update istance as an attribute
    else {
      $update['config'] = $config;
    }

    // Retrieves uploads associated with current update
    $update['uploads'] = $this->GenomeUpload->find('all', array(
      'conditions' => array(
        'GenomeUpload.config_id' => $update['config_id']
      )
    ));
    // Parses uploads
    foreach($update['uploads'] as &$upload) {
      $upload = $upload['GenomeUpload'];
    }

    // Closes transaction
    $db->commit();
  }

  // Initializes Genome configuration, looking for errors
  protected function initConfig() {
    $update = &$this->update;
    $config = &$update['config'];
    // Initializes orcae_<5code>.yaml
    $result = $this->GenomeConfig->writeSpeciesYaml($config);
    // Checks result
    if(!$result) {
      $this->error('no-write-species', 'Could not write species\' .yaml configuration file');
    }
    // Initializes orcae_conf.yaml
    $result = $this->GenomeConfig->writeConfigYaml($config);
    // Checks result
    if(!$result) {
      $this->error('no-write-orcae', 'Could not write orcae_conf.yaml configuration file');
    }
  }

  // Triggers creation of update folder
  protected function createUpdateFolder() {
    $result = $this->GenomeUpdate->createUpdateFolder($this->update);
    if($result !== true) {
      $this->error('no-folder', $result);
    }
  }

  // Triggers parsing of update folder
  protected function parseUpdateFolder() {
    $result = $this->GenomeUpdate->parseUpdateFolder($this->update);
    if(!$result) {
      $this->error('parsing', 'Error while parsing files');
    }
  }

  // Loads parsed data into database
  protected function loadUpdateFolder() {
    $result = $this->GenomeUpdate->loadUpdateFolder($this->update);
    // Checks result of loading operations
    if($result !== true) {
      $this->error('error-load', 'Could not load update folder into Orcae\'s database');
    }
  }

  // Saves genome configuration into database
  protected function saveConfig() {
    // Executed only if type is 'insert'
    if($this->update['config']['type'] == 'insert') {
      if($this->GenomeUpdate->saveConfig($this->update) !== true) {
        $this->error('no-config', 'Could not update Orcae\'s species');
      }
    }
  }

  public function error($title, $message = null) {
    // Updates update status
    if($this->update['id']) {
      $this->GenomeUpdate->updateStatus($this->update, 'failure');
    }
    parent::error($title, $message);
  }
}
?>
