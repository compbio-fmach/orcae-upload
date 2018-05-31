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
  public $uses = array('GenomeConfig', 'GenomeUpload', 'GenomeUpdate', 'Species');

  // This class' attributes
  protected $config, $update;

  // Retrieves shell input parameters
  public function getOptionParser() {
      $parser = parent::getOptionParser();
      // Configure parser
      $parser->addOption('config', array(
        'short' => 'c',
        'help' => 'The id of genome configuration relative to the genome update which will be actually updated',
        'required' => true
      ));
      return $parser;
  }

  // Initializes config and update
  protected function init() {
    // Defines config
    $this->config = array('id' => $this->param('config'));
    // Finds config by id
    $this->config = $this->GenomeConfig->findById($this->config['id']);
    // Parses genome config
    $this->config = !empty($this->config) ? $this->config['GenomeConfig'] : null;
    // If no config has been found: returns error
    if(empty($this->config)) $this->error('no-config', 'Could not find genome configuration bound to passed id');
    // Else, finds last GenomeUpdate actually active
    $this->update = $this->GenomeUpdate->findLast($this->config);
    if(empty($this->update))  $this->error('no-update', 'Could not find any active update');
    // DEBUG
    // debug($this->config);
    // debug($this->update);
  }

  // Launches another istance of this script in parallel, saving its pid and starting time
  protected function process() {
    // Defines update istance
    $update = $this->update;
    // Adds configuration istance to update istance
    $update['config'] = $config;
    // Executes next shell script
    $this->GenomeUpdate->process($update);
  }

  // Handles execution form 'config' to 'folder' status
  protected function createUpdateFolder() {
    if(true !== ($result = $this->GenomeUpdate->createUpdateFolder($this->update))) {
      $this->error('error-folder', 'Could not create update folder');
    }
    // Updates update step (states that has terminated this step)
    $this->update['step'] = 'folder';
    // Case no error: parallelize next function
    // DEBUG
    $this->next();
  }

  // Handles execution from 'folder' to 'parsed'
  protected function parseUpdateFolder() {
    if(true !== ($result = $this->GenomeUpdate->parseUpdateFolder($this->update))) {
      $this->error('error-parsing', 'Could not parse update folder');
    }
    $this->update['step'] = 'parsed';
    $this->next();
  }

  // Handles execution from 'parsed' to 'success' or 'failure'
  protected function loadUpdateFolder() {
    if(true !== ($result = $this->GenomeUpdate->createUpdateFolder($this->update))) {
      $this->error('error-load', 'Could not load update folder into Orcae\'s database');
    }
    // Sets update step as 'success'
    $this->update['step'] = 'success';
    // Saves progress
    $this->GenomeUpdate->save(array(
      'id' => $update['id'],
      'process_id' => null,
      'process_start' => null,
      'step' => $update['step']
    ));
  }

  // Executes the update
  public function main() {
    // Intializes config and update istances
    $this->init();
    // At this point config and update have been correctly set
    // Otherwise script would have already been terminated with error
    // Chooses the status of update
    $update = $this->update;
    switch($update['step']) {
      // 'config' status: configuration has been done, folder creation must be done
      case 'config':
        $this->createUpdateFolder();
        break;
      // 'folder' status: update folder has been created with aggregated files, must start parsing
      case 'folder':
        $this->parseUpdateFolder();
        break;
      // 'parsed' status: folder has been parsed, must start uploading into database
      case 'parsed':
        $this->loadUpdateFolder();
        break;
      default:
        $this->error('error-status', 'Current update\'s status is not valid');
        break;
    }
  }
}
?>
