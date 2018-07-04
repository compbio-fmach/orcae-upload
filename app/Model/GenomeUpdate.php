<?php
  // Imports file and folder handlers
  App::uses('Folder', 'Utility');
  App::uses('File', 'Utility');
  // Imports spyc for yaml handling
  App::import('Vendor', 'Spyc', array('file' => 'spyc' . DS . 'Spyc.php'));+
  // Imports connection manager to handle databases
  App::import('Model', 'ConnectionManager');
  class GenomeUpdate extends AppModel {

    public $useDbConfig = 'orcae_upload';
    public $useTable = 'genome_updates';

    // Defines validation rules
    public $validate = array(
      // Uses fake field init to manually trigger validation for initialization
      'init' => array(
        'validGenomeInsert' => array(
          'rule' => array('validateGenome', 'insert'),
          'message' => 'This genome is already present on Orcae'
        ),
        'validGenomeUpdate' => array(
          'rule' => array('validateGenome', 'update'),
          'message' => 'This genome does not match the ones already present on Orcae'
        ),
        'validUploads' => array(
          'rule' => 'validateUploads',
          'message' => 'Some files are missing: check that genome and annotation files have been correctly uploaded'
        ),
        'validProcessing' => array(
          'rule' => 'validateProcessing',
          'message' => 'Could not upadte Orcae with this genome: an update process is already being executed'
        )
      )
    );

    // Initializes model
    public function __construct($id = false, $table = null, $ds = null) {
      parent::__construct($id, $table, $ds);
      // Adds reference to GenomeConfig model
      $this->GenomeConfig = ClassRegistry::init('GenomeConfig');
      // Adds reference to GenomeUpload model
      $this->GenomeUpload = ClassRegistry::init('GenomeUpload');
      // Adds reference to Process model
      $this->Process = ClassRegistry::init('Process');
      // Adds reference to Species model
      $this->Species = ClassRegistry::init('Species');
    }

    // Retrieves last started for given config
    public function findLast($config) {
      $found = $this->find('first', array(
        // Retrieves only genome updates bound to this genome config
        'conditions' => array(
          'GenomeUpdate.config_id' => $config['id']
        ),
        // Retrieves last genome update
        'order' => 'GenomeUpdate.id DESC'
      ));
      // Returns query result
      return empty($found) ? null : $found['GenomeUpdate'];
    }

    // Updates status
    public function updateStatus(&$update, $status) {
      // Clears previous data
      $this->clear();
      // Saves step without calling validation
      $result = $this->save(array(
        'id' => $update['id'],
        'status' => $status
      ), false);
      // Updates update istance
      if($result) {
        $update['status'] = $status;
        return true;
      }
      // In case of error while saving, returns false
      return false;
    }

    // Validates genome
    public function validateGenome($data, $action) {
      // Overwrites single field (id in this case) with the whole record
      $data = $this->data['GenomeUpdate'];
      // Retrieves config from genome
      $config = $data['config'];

      // Checks if validation is required
      if($action != $config['type']) return true;

      // Defines conditions for 'update' action
      if($action == 'update') {
        $conditions = array(
          'Species.NCBI_taxid' => $config['species_taxid'],
          'Species.5code' => $config['species_5code']
        );
      }
      // Defines conditions for 'insert' action
      else {
        $conditions = array(
          'OR' => array(
            // 'Species.organism' => $config['species_name'],
            'Species.NCBI_taxid' => $config['species_taxid'],
            'Species.5code' => $config['species_5code']
          )
        );
      }

      // Checks if there are uncompatible genomes already uploaded into orcae
      $collisions = $this->Species->find('count', array(
        'conditions' => $conditions
      ));

      // Case action is 'update'
      if($action == 'update') {
        // There must be a matching species instance
        return $collisions == 1;
      }
      // Case action is insert
      else {
        // Must be unique
        return $collisions == 0;
      }
    }

    // Validates uploads
    public function validateUploads($data) {
      $data = $this->data['GenomeUpdate'];
      // Checks if there is at least 1 genome
      $countGenomes = 0;
      // Checks if there is 1 annotation file
      $countAnnot = 0;
      // Checks every upload
      $uploads = $data['uploads'];
      $config = $data['config'];
      // Loops through every file uploaded
      foreach($uploads as $upload) {
        // Creates a reference to currently looped file
        $file = new File($this->getUploadPath($config['user_id'], $upload['stored_as']));
        // Checks if actual file size matches the one set into upload istance
        if($file->size() != $upload['size']) return false;
        // Updates genome files count
        if($upload['type'] == 'genome') {
          $countGenomes++;
        }
        // Updates annotation files count
        else if($upload['type'] == 'annot') {
          $countAnnot++;
        }
      }
      // Checks counters
      return ($countGenomes >= 0 && $countAnnot == 1);
    }

    // Validates ongoing processes
    public function validateProcessing($data) {
      $data = $this->data['GenomeUpdate'];
      // Defines genome configuration istance
      $config = $data['config'];
      // Retrieves genome uploads which would block current upload
      $updates = $this->find('all', array(
        // Defines joining with Genome Config table
        'joins' => array(
          array(
            'table' => 'genome_configs',
            'alias' => 'GenomeConfig',
            'type' => 'INNER',
            'conditions' => array(
              // Joins with GenomeConfig table
              'GenomeUpdate.config_id = GenomeConfig.id',
              // Retrieves only the genome updates which did not terminate yet
              'GenomeUpdate.status' => 'updating'
            ),
            // Retrieves only species related fields
            'fields' => array('id', 'species_name', 'species_taxid', 'species_5code')
          )
        ),
        'conditions' => array(
          // Searches for conflicts
          'OR' => array(
            // 'GenomeConfig.species_name' => $config['species_name'],
            'GenomeConfig.species_taxid' => $config['species_taxid'],
            'GenomeConfig.species_5code' => $config['species_5code']
          )
        )
      ));

      // Loops through every found update: must check that the process is still going on
      foreach($updates as &$update) {
        // Gets Genome update content only
        $update = $update['GenomeUpdate'];
        // Retrieves process, if any
        $updating = $this->Process->get($update['process_id'], $update['process_start']);
        // Checks if there is at least 1 conflict
        if($updating) return false;
      }
      // Returns false if there is at least 1 blocking conflict
      return true;
    }

    // Returns upload path of current config
    public function getUploadPath($userId, $storedAs) {
      return $this->GenomeUpload->getUploadPath($userId, $storedAs);
    }

    // Retrieves update folder path, if any
    public function getUpdatePath($userId, $updateId) {
      return WWW_ROOT . 'files' . DS . 'genome_updates' . DS . $userId . DS . $updateId;
    }

    // Retrieves script (used in getParser and getLoader)
    protected function getPerlScript($script) {
      $script = new File(Configure::read('OrcaeUpload.orcaeScripts') . DS . $script, false);
      return $script->exists() ? $script->pwd() : null;
    }

    // Retrieves parser script (.annot, .gff3, ... -> .csv)
    public function getPerlParser() {
      return $this->getPerlScript('gff2structGeneCSV.pl');
    }

    /**
     * @method iniUpdate
     * Checks if genome is updatable
     *  - Checks if there is an already saved genome into Orcae's database
     *  - Checks if there is an already running update for the same genome
     *  - Checks if files uploaded are correct
     * Deletes previous genome initialization
     *  - Deletes created update folder
     * Initializes new update
     *  - Saves new update into database
     */
    public function initUpdate(&$update) {
      // Initializes transaction
      $db = $this->getDataSource();
      $db->begin();

      // Saves update data (save method calls validation)
      $update['init'] = true; // Adds trigger for validation on update initialization
      $update['status'] = 'updating'; // Sets initial status
      $result = $this->save($update); // Saves (with validation)
      unset($update['init']); // Removes trigger for later uploads

      // Case update istance has not been saved
      if(!$result) {
        // Rollback transaction
        $db->rollback();
        // Returns error message
        return $this->validationErrors;
      }
      // Case validation was successfull and update istance has been saved
      else {
        // Commits transaction
        $db->commit();
        // Updates id save executed successfully
        $update['id'] = $this->id;
        // Deletes id from model
        unset($this->id);
        return true;
      }
    }

    /**
     * @method initConfig
     * Initializes update of Orcae with a new species
     * @return true if configuration has been inserted
     * @return string error otherwise
     */
    public function initConfig(&$update) {
      // Config variable is the reference to update's internal config attribute
      $config = &$update['config'];
      // Initializes configuration
      return $this->GenomeConfig->initConfig($config);
    }

    // Cretaes folder with contents
    public function createUpdateFolder($update) {
      // Retrieves uploads and update istances
      $config = $update['config'];
      $uploads = $update['uploads'];
      // Defines folder where file which will be updated are stored
      $updateFolder = new Folder($this->getUpdatePath($config['user_id'], $update['id']), true);
      // Checks errors
      if($updateFolder->errors()) {
        return 'Error happened while creating new folder for update';
      }
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
      return true;
    }

    // Parses files in folder using perl script
    public function parseUpdateFolder($update) {
      // Retrieves annotator id
      $config = $update['config'];
      // Path to folder where files which will be parsed are stored
      $updatePath = $this->getUpdatePath($config['user_id'], $update['id']);
      $genomeFile = $updatePath . DS . 'genome.fasta';
      $annotFile = $updatePath . DS . 'annot.gff3';
      // Checks if parsing script exists
      $parser = $this->getPerlParser();
      // Verifies parser file
      if(!$parser) return false;
      // Executes parser after changing folder
      $shell = "cd $updatePath && perl $parser -gff $annotFile -tfa $genomeFile -status active -annID " . $config['user_id'] . " 2>&1 > /dev/null";
      $result = shell_exec($shell);
      // Removes files that have been parsed
      $annotFile = new File($updatePath . DS . $annotFile, false);
      $annotFile->delete();
      $genomeFile = new File($updatePath . DS . $genomeFile, false);
      $genomeFile->delete();
      // Retruns true: there is no way to check that script has been executed correcly from here
      return true;
    }

    // Loads parsed csv into database
    public function loadUpdateFolder(&$update) {
      // Previous operation result (default false)
      $result = false;
      // Tries to create database
      if(!$this->createUpdateDB($update)) {
        // Updates status
        $this->updateStatus($update, 'failure');
        // Returns error message
        return "Could not create database";
      }
      // Defines a database connection to orcae_<5code>
      $db = null;
      // Tries to create database structure (creates the connection also)
      $result = $this->structureUpdateDB($update, $db);
      $result = $result && $this->populateUpdateDB($update, $db);
      // Case operation failed: removes database
      if(!$result) {
        // Updates status
        $this->updateStatus($update, 'failure');
        // Drops newly cretaed database
        $this->dropUpdateDB($update);
        // Returns error message
        return "Could not populate database";
      }
      // Returns true if execution has reached this point
      return true;
    }

    // Tries to remove database
    public function dropUpdateDB($update) {
      try {
        $drop = 'DROP DATABASE IF EXISTS orcae_' . strtolower($update['config']['species_5code']) . ';';
        $this->query($drop);
      } catch (Exception $e) {
        return $e->getMessage();
      }
      return true;
    }

    // Tries to create the new database
    public function createUpdateDB(&$update) {
      $config = $update['config'];
      try {
        // Defines new database name
        $dbName = $dbUniqueName = 'orcae_' . strtolower($config['species_5code']);
        // Retrieves databases currently on Orcae
        $dbNames = $this->query('SHOW DATABASES;');
        // Parses databases
        foreach($dbNames as &$db) {
          $db = $db['SCHEMATA']['Database'];
        }
        // debug($dbs);
        // Creates unique database name
        $i = 1;
        while(preg_grep('/' . $dbUniqueName . '/', $dbNames)) {
          $dbUniqueName = $dbName . '_v' . $i++;
        }
        // debug($dbUniqueName);
        // Creates database (e.g. orcae_trpee)
        $create = 'CREATE DATABASE IF NOT EXISTS ' . $dbUniqueName . ';';
        $this->query($create);
        // Sets database name into update instance
        $update['config']['database'] = $dbUniqueName;
      } catch (Exception $e) {
        // Catches exception: could not create the database
        return false;
      }
      // Case database has been succesfully created
      return true;
    }

    // Imports default genome's database structure
    public function structureUpdateDB($update, &$db) {
      $config = $update['config'];
      // Imports database structure from default file
      $structure = new File(WWW_ROOT . 'files' . DS . 'defaults' . DS . 'orcae_species.sql', false);
      // Checks if structure file exists
      if(!$structure->exists()) return false;
      // Reads query held by structure file
      $structure = $structure->read();
      // Retrieves connection params to orcae_bogas
      $db = ConnectionManager::getDataSource('orcae_bogas');
      // Creates new connection to orcae_<5code> database
      // $name = 'orcae_' . strtolower($config['species_5code']);
      $name = $config['database'];
      $db = ConnectionManager::create(
        // Defines a name for the connection
        $name,
        // Configuration for new connection is the same as for orcae_bogas, except for database name
        array_merge(
          $db->config,
          array(
            'database' => $name,
            // Sets PDO flag which allows to load data from file
            'flags' => array(
              PDO::MYSQL_ATTR_LOCAL_INFILE => true
            )
          )
        )
      );
      // Executes raw query for database structure creation
      $result = $db->rawQuery($structure);
      // Returns true if sterror is empty
      return $result === true;
    }

    // Stores data from .csv parsed files into database
    public function populateUpdateDB($update, &$db) {
      // Defines a query which imports csv files into database
      // This query is directly taken from orcae/src/perl/programs/orcaeDB_load.tcsh
      // $load = 'LOAD DATA LOCAL INFILE \'<csv>\' INTO TABLE <table> FIELDS TERMINATED BY \';\' ENCLOSED BY \'"\' LINES TERMINATED BY \'\\r\\n\';';
      $load = "LOAD DATA LOCAL INFILE '<csv>' INTO TABLE `<table>` FIELDS TERMINATED BY ';' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' SET id = NULL";
        // Defines a list of files to be loaded
      // Ordered as in orcae/src/perl/programs/orcaeDB_load.tcsh
      $files = array(
        'contig.csv',
        'locked.csv',
        'comments.csv',
        'est.csv',
        'function.csv',
        'gene.csv',
        'go_terms.csv',
        'history.csv',
        'protein.csv',
        'protein_domain.csv',
        'protein_homolog.csv',
        'structure.csv'
      );
      // Loops through every file
      foreach($files as $file) {
        // Redefines file as File instance
        $file = new File($this->getUpdatePath($update['config']['user_id'], $update['id']) . DS . $file);
        // Checks if file exists
        if($file->exists()) {
          // Cretaes custom load query for every file
          $loadFile = $load;
          $loadFile = preg_replace('/<csv>/', $file->pwd(), $loadFile);
          $loadFile = preg_replace('/<table>/', $file->name(), $loadFile);
          // debug($loadFile);
          // Executes query which loads data
          $result = $db->rawQuery($loadFile);
          // debug($result);
          // Throws error only if raw query result is not true
          if(!$result) return false;
        }
      }
      // Returns true if no exception has ben fired
      return true;
    }

    // Updates orcae_bogas: links orcae_bogas to the newly created
    public function saveConfig(&$update) {
      // Defines a reference to update's Genome Config istance
      $config = &$update['config'];
      // Starts transaction
      $db = $this->Species->getDataSource();
      $db->begin();
      // Calls saveconfig method of Genome Config model
      $result = $this->GenomeConfig->saveSpecies($config);
      $result = $result && $this->GenomeConfig->saveSpeciesGroup($config);
      // Species image saving does not block execution on failures
      $this->GenomeConfig->saveSpeciesImage($config);
      // Checks result
      if(!$result) {
        // Rollback
        $db->rollback();
        // Updates Genome Update status
        $this->updateStatus($update, 'failure');
        // Returns error message
        return $result;
      }
      // Commits and returns true
      $db->commit();
      return true;
    }

    // Wrapper for start method of Process model
    public function startProcess(&$update) {
      // Defines update folder path
      $updatePath = $this->getUpdatePath($update['config']['user_id'], $update['id']);
      // Defines output file path (overwrites the former one, if any)
      $outFile = (new File($updatePath . DS . 'log.txt', true))->pwd();
      // Defines error file path (overwrites the former one, if any)
      $errFile = (new File($updatePath . DS . 'error.txt', true))->pwd();
      // Defines command to execute next shell as background process
      $process = $this->Process->start(APP . DS . 'Console/cake genome_update -u ' . $update['id'] . ' > ' . $outFile . ' 2>' . $errFile . ' &');
      // Case process has been started
      if($process) {
        // Saves process results into update table
        $update['process_id'] = $process['process_id'];
        $update['process_start'] = $process['process_start'];
        // Executes saving query
        $saved = $this->save(array(
          'id' => $update['id'],
          'process_id' => $update['process_id'],
          'process_start' => $update['process_start'],
          'status' => $update['status']
        ));
        // Case process has not been saved: don't want to have a process without refernece
        if(!$saved) {
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
