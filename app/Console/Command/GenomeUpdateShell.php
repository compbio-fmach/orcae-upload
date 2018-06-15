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
  public $uses = array('GenomeConfig', 'GenomeUpdate');

  protected $update;

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
    // Defines update istance
    $this->update = array('id' => $this->param('update'));
    $this->update = $this->GenomeUpdate->findById($this->update['id']);
    $this->update = !empty($this->update) ? $this->update['GenomeUpdate'] : null;
    // Checks if update has been found
    if(!$this->update) {
      $this->error('update-not-found', 'Could not find Genome Update instance bound to passed id');
    }

    // Retrieves Genome Config associated with given Genome Update
    $config = array('id' => $this->update['config_id']);
    $config = $this->GenomeConfig->findById($config['id']);
    // Checks that configuration is valid
    $config = !empty($config) ? $config['GenomeConfig'] : null;
    // Returns error if Genome Config not found
    if(!$config) {
      $this->error('config-not-found', 'Could not find Genome Configuration instance bound to given Genome Update');
    } else {
      $this->update['config'] = $config;
    }

    // Updates
    $this->initConfig();
    $this->createUpdateFolder();
    $this->loadUpdateFolder();
    $this->saveConfig();

    // If execution reaches this point: sets status to success
    $this->GenomeUpdate->updateStatus($this->upload, 'success');
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
    $this->GenomeUpdate->createUpdateFolder($this->update);
  }

  // Triggers parsing of update folder
  protected function parseUpdateFolder() {
    $result = $this->GenomeUpdate->parseUpdateFolder($this->$update);
    if(!$result) {
      $this->error('parsing', 'Could not parse update folder');
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
    if($this->GenomeUpdate->saveConfig($this->update) !== true) {
      $this->error('no-config', 'Could not update Orcae\'s species');
    }
  }

  // Overwrites error function to set 'failure' on error
  protected function error($title, $message = null) {
    // Updates update status
    if($this->update['id']) {
      $this->GenomeUpdate->updateStatus($this->update, 'failure');
    }
    parent::error($title, $message);
  }
}
?>
