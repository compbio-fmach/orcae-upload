<?php
  // Imports file and folder handlers
  App::uses('Folder', 'Utility');
  App::uses('File', 'Utility');
  // Imports spyc for yaml handling
  App::import('Vendor', 'Spyc', array('file' => 'spyc' . DS . 'Spyc.php'));
  class GenomeUpdate extends AppModel {

    public $useDbConfig = 'orcae_upload';
    public $useTable = 'genome_updates';

    // Initializes model
    public function __construct($id = false, $table = null, $ds = null) {
      parent::__construct($id, $table, $ds);
      // Adds reference to GenomeConfig model
      $this->GenomeConfig = ClassRegistry::init('GenomeConfig');
      // Adds reference to GenomeUpload model
      $this->GenomeUpload = ClassRegistry::init('GenomeUpload');
      // Adds reference to Process model
      $this->Process = ClassRegistry::init('Process');
    }

    public function getUploadPath($userId, $storedAs) {
      return $this->GenomeUpload->getUploadPath($userId, $storedAs);
    }

    // Retrieves update folder path, if any
    public function getUpdatePath($userId, $updateId, $create = false) {
      // debug($userId);
      // debug($updateId);
      $folder = new Folder(WWW_ROOT . 'files' . DS . 'genome_updates' . DS . $userId . DS . $updateId, $create);
      return $folder->pwd();
    }

    // Retrieves script (used in getParser and getLoader)
    protected function getPerlScript($script) {
      $folder = new Folder(Configure::read('OrcaeUpload.orcaeScripts', false));
      if(!$folder) throw new Exception('Orcae\'s perl scripts folder not found');
      // Searches for the script into scripts' folder
      $scripts = $folder->find($script);
      // If script not found: returns false
      if(empty($scripts))  throw new Exception('Script not found');
      // Returns path to script
      return $folder->pwd() . DS .$scripts[0];
    }

    // Retrieves parser script (.annot, .gff3, ... -> .csv)
    public function getPerlParser() {
      $this->getPerlScript('gff2structGeneCSV.pl');
    }

    // Retrieves loader script (.csv files -> DB)
    public function getPerlLoader() {
      $this->getPerlScript('');
    }

    // Validates if actual uploads are completely done
    public function validateUploads($update) {
      // Checks if there is at least 1 genome
      $countGenomes = 0;
      // Checks if there is 1 annotation file
      $countAnnot = 0;
      // Checks every upload
      $uploads = $update['uploads'];
      $config = $update['config'];
      foreach($uploads as $upload) {
        $file = new File($this->getUploadPath($config['user_id'], $upload['stored_as']));
        // DEBUG
        // debug($upload);
        // debug($file->size());
        // Checks if actual file size matches the one set into upload istance
        if($file->size() != $upload['size']) return false;
        // Updates genome files count
        if($upload['type'] == 'genome') {
          $countGenomes++;
        }
        // Updates annotation files count
        else if($upload['type'] == 'annot') {
          // Case annotation files count exceeds 1
          if(++$countAnnot > 1) {
            return false;
          }
        }
      }
      // Checks counters
      if($countGenomes <= 0 || $countAnnot <= 0) {
        return false;
      }
      // If execution reaches this point, validation is ok
      return true;
    }

    // Initializes configuration for update
    // It takes major steps of addNewGenome.pl
    public function initConfig(&$update) {
      // Config variable is the reference to update's internal config attribute
      $config = &$update['config'];
      // Case error has been found
      if(true !== $result = $this->GenomeConfig->initConfig($config)) {
        return $result;
      }
      // Case config initialized successfully
      else {
        return $this->updateStep($update, 'configured');
      }
    }

    // Initializes update saving data and retireving id
    public function initUpdate(&$update) {
      // Saves update data
      $result = $this->save(array(
        'config_id' => $update['config']['id'],
        'status' => 'started'
      ));
      // Checks saving result
      if(!$result) return "Could not save new update";
      // Updates id if save executed successfully
      $update['id'] = $this->id;
      // Deletes id from model
      unset($this->id);
      return true;
    }

    // Cretaes folder with contents
    public function createUpdateFolder($update) {
      $config = $update['config'];
      $uploads = $update['uploads'];
      // Defines folder where file which will be updated are stored
      $updateFolder = new Folder($this->getUpdatePath($config['user_id'], $update['id']), true);
      // Deletes folder content
      $updateFolder->delete();
      // Loops every updated file
      foreach ($uploads as $upload) {
        // Retrieves file which will be updated
        $in = new File($this->getUploadPath($config['user_id'], $upload['stored_as']));
        // Puts input file's content in the correct file
        switch($upload['type']) {
          case 'genome':
            $out = new File($updateFolder->pwd() . DS . 'genome.fasta');
            break;
          case 'annot':
            $out = new File($updateFolder->pwd() . DS . 'annot.gff3', true);
            break;
          default:
            $out = false;
            break;
        }
        // Outputs uploaded file's content into updatable file
        if($out !== false) {
          $out->append($in->read());
          $out->close();
        }
        // Closes read/write streams
        $in->close();
      }
      // Saves status
      return $this->updateStep($update, 'structured');
    }

    // Parses files in folder using perl script
    public function parseUpdateFolder($update) {
      // Retrieves annotator id
      $userId = $update['config']['user_id'];
      $updateId = $update['id'];
      // Path to folder where files which will be parsed are stored
      $updatePath = $this->getUpdatePath($userId, $updateId, true);
      $genomeFile = 'genome.fasta';
      $annotFile = 'annot.gff3';
      debug($updatePath);
      // Checks if parsing script exists
      $parser = $this->getPerlParser();
      // Executes parser after changing folder
      // debug(shell_exec("perl -v && cd $updatePath && perl $parser -spe -gff $genomeFile -fa $annotFile -status active -ann_id $userId"));
      $result = shell_exec("perl $parser -spe -gff $genomeFile -fa $annotFile -status active -ann_id $userId");
      // TODO validates results
      debug($result);
      return true;
    }

    // Tries to load parsed folder into Orcae's database
    public function loadUpdateFolder($update) {
      // Initializes db istance
      $db = null;
      // Initializes a new database into orcae
      $this->initOrcaeDb($update, $db);
      // Populates the initialized db
      $this->populateOrcaeDb($update, $db);
    }

    // Creates database for uploading a new species
    public function initOrcaeDb(&$update, &$db) {
      $config = $update['config'];
      // Creates new database
      $db = 'orcae_' . $update['config']['species_5code'];
      $this->query('CREATE DATABASE ' . $db . ';');
      // Takes db structure from file as a string
      $dbStructure = new File(WWW_ROOT . 'files' . DS . 'defaults' . DS . 'orcae_species.sql');
      $dbStructure = $dbStructure->read();
      // Creates database structure
      $this->query('USE ' . $db . '; ' . $dbStructure);
      return true;
    }

    // Populates newly created Orcae's database
    public function populateOrcaeDb(&$update, &$db) {}

    // Deletes an update
    // Deletes row of orcae_bogas.taxid if step is 'config'
    // Deletes files from update folder if step is 'folder'
    // Deletes database from Orcae if step is 'database'
    public function undoUpdate(&$update) {
      $result = false;
      switch($update['step']) {
        case 'config':
          $result = $this->undoUpdateConfig($update);
          break;
        case 'folder':
          $result = $this->undoUpdateFolder($update);
          break;
        case 'database':
          $result = $this->undoeUpdateDb($update);
          break;
        default:
          return "Could not delete this update";
      }
      // Case operation went wrong
      if($result !== true) {
        return $result;
      }
      // Otherwise, updates step as error
      else {
        return $this->updateStep($update, 'error');
      }
    }

    // Deletes genome configuration from Orcae
    protected function undoUpdateConfig(&$update) {
      $config = $update['config'];
      // Deletes row from orcae_bogas.taxid
      $result = $this->Species->deleteAll(array(
        'NCBI_taxid' => $config['species_taxid'],
        'organism' => $config['species_name'],
        '5code' => $config['species_5code']
      ));
      // Returns deletion reuslt
      return $result ? true : "Could not delete genome configuration";
    }

    // Deletes update folder from orcae
    protected function undoUpdateFolder(&$update) {
      // Retrieves update folder
      $folder = new Folder($this->getUpdatePath($config['user_id'], $update['id']), false);
      // Deletes folder recursively (this will also stop parsing process)
      if(!empty($folder->pwd()) && !$folder->delete())
        return "Could not delete update folder";
      // Calls delete config to return to original status
      return $this->deleteUpdateConfig($update);
    }

    // Deletes newly created orcae database
    protected function undoUpdateDb(&$update) {
      // Deletes database if exists
      try {
        $this->query('DROP DATABASE IF EXISTS orcae_'. $update['config']['species_5code'] . ';');
      } catch(Exception $e) {
        return $e->getMessage();
      }
      // Executes remaining delete functions
      return $this->undoUpdateFolder($update);
    }

    // Updates step of current update istance
    public function updateStep(&$update, $step) {
      // Ensures that id has not been already set
      unset($this->id);
      // Saves step
      $result = $this->save(array(
        'id' => $update['id'],
        'step' => $step
      ));
      // Updates update istance
      if($result) {
        $update['step'] = $step;
        return true;
      }
      // In case of error while saving, returns false
      return false;
    }

    // Retrieves last started udpate
    public function findLast($config) {
      $found = $this->find('first', array(
        // Retrieves only genome updates bound to this genome config
        'conditions' => array(
          'GenomeUpdate.config_id' => $config['id']
        ),
        // Retrieves last genome update
        'order' => 'GenomeUpdate.id DESC'
      ));
      return empty($found) ? null : $found['GenomeUpdate'];
    }

    // Wrapper for start method of Process model
    public function startProcess($update) {
      // Defines config istance
      $config = $update['config'];
      // Defines update folder path
      $updatePath = $this->getUpdatePath($config['user_id'], $update['id']);
      // Defines output file path (overwrites the former one, if any)
      $outFile = (new File($updatePath . DS . 'log.txt', true))->pwd();
      // Defines error file path (overwrites the former one, if any)
      $errFile = (new File($updatePath . DS . 'error.txt', true))->pwd();
      // Defines command to execute next shell as background process
      $process = $this->Process->start('(' . APP . DS . 'Console/cake GenomeUpdate -config ' . $config['id'] . ' > ' . $outFile . ' 2>' . $errFile . ' &)');
      // Case process has been started
      if($process) {
        // Saves process results into update
        $update['process_id'] = $process['process_id'];
        $update['process_start'] = $process['process_start'];
        // Executes saving query
        $save = $this->save(array(
          'id' => $update['id'],
          'process_id' => $update['process_id'],
          'process_start' => $update['process_start'],
          'step' => $update['step']
        ));
        // Case process has not been saved: don't want to have a process without refernece
        if(!$save) {
          // Stops process
          $this->Process->stop($update['process_id'], $update['process_start']);
          return false;
        }
        // This is the only case where success must be returned
        return true;
      }
      // returns false by default
      return false;
    }

    // Wrapper for get method of Process model
    public function getProcess($update) {
      return $this->Process->get($update['process_id'], $update['process_start']);
    }
  }
?>
