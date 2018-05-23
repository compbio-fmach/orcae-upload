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
    }

    public function getUploadPath($userId, $storedAs) {
      return $this->GenomeUpload->getUploadPath($userId, $storedAs);
    }

    // Retrieves update folder path, if any
    public function getUpdatePath($userId, $updateId) {
      $folder = new Folder(WWW_ROOT . 'files' . DS . 'genome_updates' . DS . $userId . DS . $updateId);
      return $folder->pwd();
    }

    // Retrieves script (used in getParser and getLoader)
    protected function getPerlScript($script) {
      $folder = new File(Configure::read('OrcaeUpload.orcaeScripts', false));
      if(!$folder) throw new Exception('Orcae\'s perl scripts folder not found');
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
    public function initConfig($update) {
      $config = $update['config'];
      return $this->GenomeConfig->initConfig($config);
      /*
      // TODO: Execute configuration as a transaction
      // Calculates species_2code (takes first 2 char, random char if empty)
      $ch1 = chr(rand(97,122));
      $ch2 = chr(rand(97,122));
      $config['species_2code'] = strtoupper(substr($config['species_name'] . $ch1 . $ch2, 0, 2));
      // First inserts orcae_bogas.taxid row
      // Saves istance through Species model
      $this->Species->save(array(
        'NCBI_taxid' => $config['species_taxid'],
        'internal_taxid' => null,
        '2code' => $config['species_2code'],
        '5code' => $config['species_5code'],
        'organism' => $config['species_name']
      ));
      // Retrieves new species id
      $config['species_id'] = $this->Species->id;
      // Adds image to images folder (of Orcae)
      if($config['species_image']) {
        // Creates new file istance which references to configuration's species image
        $speciesImage = new File($config['species_image']);
        // Checks if species image is valid
        if($speciesImage->exists()) {
          // Defines orcae's images folder
          $orcaeSpeciesImage = Configure::read('OrcaeUpload.orcaeWeb') . DS . 'app' . DS . 'webroot' . DS . 'img' . DS . $speciesImage->name();
          // Copies species image into orcae folder
          $speciesImage->copy($orcaeSpeciesImage, true);
        }
      }
      // TODO: Inserts new group (welcome text and group description)
      $this->Group->save(array(
        'id' => null,
        'name' => '',
        'taxid' => $config['species_taxid'],
        'description' => $config['group_description'],
        'invitationmail' => null,
        'upgrademail' => null,
        'alertmail' => null,
        'welcome' => $config['group_welcome']
      ));
      // Stores id of saved group
      $config['group_id'] = $this->Group->id;
      // TODO: Adds current user to group with admin rights over it
      $this->UserGroup->save(array(
        'id' => null,
        'group_id' => $config['group_id'],
        'user_id' => $config['user_id'],
        'status' => null
      ));
      // TODO: Retrieves .sql dump (Orcae-Upload uses a default file instead)
      // TODO: Sets correct db name into previously dumped one
      $speciesDB = new File(WWW_ROOT . 'files' . DS . 'defaults' . DS . 'orcae_bogas.sql');
      if($speciesDB->exists()) {}
      // TODO: Writes new yaml SPECIES config file (<5code>_conf.yaml) into config directory
      // In Orcae-Upload yaml file is taken from config row stored into db
      $speciesConfigFile = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . $config['species_5code'] . '_conf.yaml', true);
      $speciesConfigYaml = $speciesConfigFile->prepare($config['species_config']);
      $speciesConfigFile->write($speciesConfigYaml);
      // TODO: Makes a backup copy of ORCAE config file
      $orcaeConfigFile = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . 'orcae_conf.yaml', true);
      $orcaeConfigFile->copy($orcaeConfigFile->Folder->pwd() . DS . 'orcae_conf.yaml.bak', true);
      // TODO: Writes new yaml ORCAE config file (orcae_conf.yaml)
      $orcaeConfigYaml = Spyc::YAMLLoad($config['orcae_config']);
      debug($orcaeConfigYaml);
      // - If flag update is TRUE: takes 'current' section of <5code> section as default
      // - If flag update is FALSE: takes trpee 'current' section
      // TODO: Overwrites 'current' section
      // TODO: Secondary configuration steps
      // TODO: Removes temporary files
      */
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
      return true;
    }

    // Cretaes folder with contents
    public function initUpdateFolder($update) {
      $config = $update['config'];
      $upload = $update['uploads'];
      // Defines folder where file which will be updated are stored
      $updateFolder = new Folder($this->getUpdatePath($config['user_id'], $update['id']), true);
      // Loops every update file
      foreach ($uploads as $upload) {
        // Retrieves file which will be updated
        $in = new File($this->getUploadPath($config['user_id'], $upload['stored_as']));
        // Puts input file's content in the correct file
        switch($upload['type']) {
          case 'genome':
            $out = new File($updatePath . DS . 'genome.fasta');
            break;
          case 'annot':
            $out = new File($updatePath . DS . 'annot.gff3', true);
            break;
          default:
            $out = false;
            break;
        }
        // Outputs uploaded file's content into updatable file
        if($out !== false) $out->append($in->read());
      }
    }

    // Parses files in folder using perl script
    public function parseUpdateFolder($update) {
      // Retrieves annotator id
      $userId = $update['config']['user_id'];
      $updateId = $update['id'];
      // Path to folder where files which will be parsed are stored
      $updateFolder = $this->getUpdatePath($userId, $updateId);
      // Checks if parsing script exists
      $parser = $this->getPerlParser();
      // Executes parser after changing folder
      shell_exec("cd $updateFolder; perl $parser -spe -gff $genome -fa $annot -status active -ann_id $userId");
    }

    // Creates database for uploading a new species
    public function initDatabase($update) {
      $config = $update['config'];
      // Lists databases
      $dbs = $this->query('SHOW DATABASES;');
      // Parses databases
      foreach($dbs as &$db) {
        $db = $db['SCHEMATA']['Database'];
      }
      // Creates new database
      $db = 'orcae_' . $update['config']['species_5code'];
      $this->query('CREATE DATABASE ' . $db . ';');
      /*
      if(!$this->query('CREATE DATABASE ' . $db . ';')) {
        return "Couldn't create new species' database";
      }*/
      // TODO: cretaes database structure
      // Takes db structure from file as a string
      $dbStructure = new File(WWW_ROOT . 'files' . DS . 'defaults' . DS . 'orcae_species.sql');
      $dbStructure = $dbStructure->read();
      debug($dbStructure);
      debug($db);
      $this->query('USE ' . $db . '; ' . $dbStructure);
      /*if(!) {
        return "Couldn't create database structure";
      }*/
      // DEBUG
      debug($dbs);
      return true;
    }
  }
?>
