<?php
/**
 * Genome Configuration model
 * This model is bound to 'orcae_upload'.'genome_config' table
 */
App::uses('Folder', 'Utility'); // Required for folder handling (used with species_image)
App::uses('File', 'Utility'); //Required for file handling
App::uses('ConnectionManager', 'Model'); // required to access database configurations
// Imports spyc for yaml handling
App::import('Vendor', 'Spyc', array('file' => 'spyc' . DS . 'Spyc.php'));
class GenomeConfig extends AppModel {

    // Defines database where table bound to SessionConfig is
    public $useDbConfig = 'orcae_upload';
    // Defines table bound to SessionConfig
    public $useTable = 'genome_configs';

    // Constructor: imports Species, UsersGroups and Groups models
    public function __construct($id = false , $table = null , $ds = null ) {
      parent::__construct($id, $table, $ds);
      // Loads Species model
      $this->Species = ClassRegistry::init('Species');
      // Loads Group model
      $this->Group = ClassRegistry::init('Group');
      // Loads UserGroup model
      $this->UserGroup = ClassRegistry::init('UserGroup');
    }

    // Handles delete
    public function deleteAll($conditions, $cascade = true , $callbacks = false) {
      // Starts transaction
      $db = $this->getDataSource();
      $db->begin();

      // Needs GenomeConfig.id and GenomeConfig.user_id
      // Defines genome configuration instance using given conditions
      $config = array(
        'id' => isset($conditions['GenomeConfig.id']) ? $conditions['GenomeConfig.id'] : null,
        'user_id' => isset($conditions['GenomeConfig.user_id']) ? $conditions['GenomeConfig.user_id'] : null
      );

      // Retrieves genome updates
      $config['updates'] = $this->getGenomeUpdates($config);
      // Retrieves last update
      $last = array_shift($config['updates']);
      // Checks last update status
      if($last && $last['status'] == 'updating') {
        return 'Unable to delete a genome configuration while updating it into Orcae';
      }

      // Retrieves uploads
      $config['uploads'] = $this->getGenomeUploads($config['id']);

      // Executes deletion
      $result = parent::deleteAll($conditions, $cascade, $callbacks);

      // Case result is false
      if(!$result) {
        $db->rollback(); // Rollback transaction
        return false; // Exits with error status
      }

      // Loads Genome Upload model
      $GenomeUpload = ClassRegistry::init('GenomeUpload');
      // Loops every file
      foreach($config['uploads'] as &$upload) {
        // Deletes uploads' folder
        if($file = new File($GenomeUpload->getUploadPath($config['user_id'], $upload['stored_as'], false))) {
          // checks if iles exists
          if($file->exists()) {
            // Deletes file
            $file->delete();
          }
        }
      }

      // Loads Genome Update model
      $GenomeUpdate = ClassRegistry::init('GenomeUpdate');
      // Deletes updates' files
      foreach($config['updates'] as &$update) {
        // Delete the whole folder which contains updates
        $folder = $GenomeUpdate->getUpdatePath($config['user_id'], $update['id']);
        // Creates folder
        $folder = new Folder($folder, false);
        // Checks if folder exists
        if($folder->path) {
          // deletes folder
          $folder->delete();
        }
      }

      // Closes transaction
      $db->commit();
      // Returns result
      return true;
    }

    // Empty array of validation rules, will be filled with errors and warnings later
    public $validate = array();

    // Defines error validation rules for cakephp validation
    protected $errors = array(
      'type' => array(
        'rule' => array('inList', array('insert', 'update')),
        'required' => true,
        'allowEmpty' => false,
        'message' => "Selected session type is not valid"
      ),
      'species_name' => array(
        'species_name_format' => array(
          'rule' => '/^[a-zA-Z0-9\s]{0,255}$/',
          'allowEmpty' => true,
          'message' => "Species name must not exceed 255 limit of simple chars"
        ),
        'species_name_congruency' => array(
          'rule' => 'validateSpecies',
          'message' => "Species attributes does not match any species data currently on Orcae"
        )
      ),
      'species_taxid' => array(
        'rule' => '/^\d+$/',
        'allowEmpty' => true,
        'message' => "Invalid species taxonomy id"
      ),
      'species_5code' => array(
        array(
          'rule' => '/^[a-zA-Z0-9]{0,5}$/',
          'allowEmpty' => true,
          'message' => 'Invalid species shortname'
        )
      ),
      'group_description' => array(
        'rule' => '/^(|.{0,255})$/',
        'allowEmpty' => true,
        'message' => 'Group description exceeds maximum length of 255 chars'
      ),
      // Checks last update status
      'last_update' => array(
        'success' => array(
          'required' => true, // 'last_update' field is required
          'rule' => array('validateLastUpdate', 'success'),
          'message' => 'Save not allowed: currently updating Orcae with this genome configuration'
        ),
        'updating' => array(
          'rule' => array('validateLastUpdate', 'updating'),
          'message' => 'Save not allowed: Orcae has already been updated with this genome configuration'
        )
      )
    );

    // Defines warning validation rules for cakephp validation
    protected $warnings = array(
      'species_taxid' => array(
        'rule' => '/^\d{1,}$/',
        'allowEmpty' => false,
        'message' => 'Species taxonomy id should be set and contain numbers only'
      ),
      'species_5code' => array(
        'rule' => '/^[a-zA-Z0-9]{5}$/',
        'allowEmpty' => false,
        'message' => 'Species shortname should be set as a string of 5 chars'
      )
    );

    /**
     * @method validate
     * Validates GenomeConfig passed as associative array
     * Validates only set fields (which could be empty however)
     * @param data is the Genome Configuration which will be validated
     * @param type specifies to validate errors, warnings or both
     * @return array of errors and warnings
     */
    public function validate($data, $type = null) {
      if($type != 'errors' && $type != 'warnings') {
        // Data about errors and warnings to be returned
        $return = array(
          'errors' => $this->validate($data, 'errors'),
          'warnings' => $this->validate($data, 'warnings')
        );
        // Deletes previous validation errors results
        $this->validationErrors = array();
        // Returns validation results
        return $return;
      }

      // Defines validation rules
      $rules = ($type == 'errors') ? $this->errors : $this->warnings;
      // Removes rules which does not match data set fields
      $rules = array_intersect_key($rules, $data);
      // Sets data to be validated
      $this->set($data);
      // Sets validation rules
      $this->validate = $rules;
      // Deletes previous validation results
      $this->validationErrors = array();
      // Validates
      $this->validates();
      // Returns validation errors
      return $this->validationErrors;
    }

    /**
     * @method validateSpecies
     * This method validates if there is a species which matches taxid and 5code of validated data
     */
    public function validateSpecies($check) {
      // Retreives other fields
      $data = $this->data['GenomeConfig'];
      // Validates congruency only for update type
      if($data['type'] != 'update') return true;
      // Checks if species with same and 5code exists on Orcae
      $exists = $this->Species->find('count', array(
        'conditions' => array(
          // 'Species.organism' => $data['species_name'],
          'Species.NCBI_taxid' => $data['species_taxid'],
          'Species.5code' => $data['species_5code']
        )
      ));
      return $exists > 0;
    }

    /**
     * @method validateLastUpdate
     * Checks if last update allows to edit current genome configuration
     * @param status which makes validation end successfully
     */
    public function validateLastUpdate($check, $status) {
      // Retreives field
      $last = $this->data['GenomeConfig']['last_update'];

      // Checks last update status
      switch($last ? $last['status'] : false) {
        // Case last update status equals given status
        case $status:
          return false;
        // Last update is absent
        case false:
        // By default returns false
        default:
          return true;
      }
    }

    /**
     * @method normalizeSpecies5code
     * Normalizes species_5code attribute
     * @return true
     */
    public function normalizeSpecies5code($check) {
      $shortname = $this->data['GenomeConfig']['species_5code'];
      $this->data['GenomeConfig']['species_5code'] = ucfirst(strtolower($shortname));
      // Returns true every time
      return true;
    }

    /**
     * @method getSpeciesImage
     * Return species images of Genome Configuration given as param, if any
     * @param data is an associative array representing Genome Configuration
     * @param link specifies if a local or web url must be returned
     * @return species_image url
     */
    public function getSpeciesImage($data, $link = 'web') {
      // Defines images folder
      $folder = new Folder(WWW_ROOT . 'files' . DS . 'species_images' . DS, false);
      // Checks if folder exists
      if(!$folder->path) return false;
      // List all matching image files
      $images = preg_grep('/^species_image_'.$data['id'].'\./', $folder->find());
      // Returns only the first image found
      // WARNING: uses array_shift beacuse indexes are not modified form preg_grep
      $image = array_shift($images);

      // Defines image path
      if($image) {
        // Case a web url is erquired
        if($link == 'web') {
          $image = Router::url('/', true) . 'files' . DS . 'species_images' . DS . $image;
        }
        // Case an internal link is required
        else {
          $image = $folder->pwd() . DS . $image;
        }
      }

      // Returns retrieved image url or path
      return $image;
    }

    /**
     * @method updateSpeciesImage updates an image if it is set into files
     * @param data is an associative array which holds Genome Config instance
     * @return true if updated successfully
     * @return error string otherwise
     */
    public function updateSpeciesImage($data) {
      // Defines warning (return value)
      $warning = null;

      // Retrives images folder
      $folder = new Folder(WWW_ROOT . 'files' . DS . 'species_images' . DS, true);
      // Searches into folder every image bound to current genome config istance
      $images = $folder->find("species_image_".$data['id'].".*");
      // Loopts through folder images
      foreach($images as $i) {
        // Deletes all images associated with this config
        $i = new File($folder->pwd().DS.$i);
        $i->delete();
      }

      // Defines a reference to species image
      $image = $data['species_image'];

      // Exits if no file has been issued
      if(!$image) return true;

      // Uploads file into directory
      if(!$warning && $image['error'] != UPLOAD_ERR_OK) {
        $warning = "Unable to upload the given image";
      }

      // Checks image extension
      $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
      if(!$warning && !preg_match('/^(jpg|jpeg|png)$/', $ext)) {
        $warning = "Image extesion is not valid";
      }

      /*
      // Checks image dimension (1 Mb)
      if(!$warning && $image['size'] > 1000000) {
        $warning = "Image size exceeded file size limit";
      }
      */

      // Saves image
      $name = "species_image_".$data['id'].".".$ext;
      if(!$warning && !move_uploaded_file($image['tmp_name'], $folder->pwd().DS.$name)) {
        $warning = "Could not upload image.";
      }

      // Puts results into validation array
      return empty($warning) ? true : $warning;
    }

    // Retrieves genome uploads bound to this config instance
    public function getGenomeUploads($config) {
      // Query using join
      $uploads = $this->find('all', array(
        'conditions' => array(
          'GenomeConfig.id' => $config['id']
        ),
        'joins' => array(
          array(
            'table' => 'genome_uploads',
            'alias' => 'GenomeUpload',
            'type' => 'inner',
            'conditions' => array(
              'GenomeConfig.id = GenomeUpload.config_id'
            )
          )
        ),
        'fields' => 'GenomeUpload.*',
        'order' => 'GenomeUpload.id DESC'
      ));

      // Parses results
      foreach($uploads as &$upload) {
        $upload = $upload['GenomeUpload'];
      }

      // returns uploads
      return $uploads;
    }

    // Retrieves genome updates bound to this config istance
    public function getGenomeUpdates($config) {
      // Loads Process model
      $Process = ClassRegistry::init('Process');
      // List of updates to be returned
      $updates = array();
      // Executes query using this config attributes
      $results = $this->find('all', array(
        'conditions' => array(
          'GenomeConfig.id' => $config['id']
        ),
        'joins' => array(
          array(
            'table' => 'genome_updates',
            'alias' => 'GenomeUpdate',
            'type' => 'inner',
            'conditions' => array(
              'GenomeConfig.id = GenomeUpdate.config_id'
            )
          )
        ),
        'fields' => 'GenomeUpdate.*',
        'order' => 'GenomeUpdate.id DESC'
      ));

      // Parses results
      foreach($results as &$result) {
        $result = $result['GenomeUpdate'];
        // Checks if genome update is actually updating
        if($result['status'] == 'updating') {
          // Retrieves process
          $process = $Process->get($result['process_id'], $result['process_start']);
          // Checks if process has been retrieved
          if(!$process) {
            // Sets process status into update instance
            $result['status'] = 'failure';
          }
        }
      }

      // Returns populated array
      return $results;
    }

    // Wrapper for getGenomeUpdates, retrieves only first genome update (the one with highest id)
    public function getLastGenomeUpdate($config) {
      $updates = $this->getGenomeUpdates($config);
      return array_shift($updates);
    }

    // Saves species data into database
    public function saveSpecies(&$config) {
      // Creates 2code
      $ch1 = chr(rand(97,122));
      $ch2 = chr(rand(97,122));
      $config['species_2code'] = strtoupper(substr($config['species_name'] . $ch1 . $ch2, 0, 2));
      // Inserts into database
      $result = $this->Species->save(array(
        'NCBI_taxid' => $config['species_taxid'],
        'internal_taxid' => null,
        '2code' => $config['species_2code'],
        '5code' => $config['species_5code'],
        'organism' => $config['species_name']
      ));
      // Wrong insert execution's results
      if(!$result) {
        return false;
      }
      // Data successfully inserted
      else {
        // Updates species id
        $config['species_id'] = $this->Species->id;
        return true;
      }
    }

    // Saves species image into orcae's folder, if it is set (not blocking)
    public function saveSpeciesImage(&$config) {
      $speciesImage = $this->getSpeciesImage($config, 'internal');
      // Executes onfly if image exists
      if(!empty($speciesImage)) {
        // References to species image file
        $speciesImage = new File($speciesImage);
        // Full path to Orcae's images folder
        $orcaeSpeciesImage = Configure::read('OrcaeUpload.orcaeWeb') . DS . 'app' . DS . 'webroot' . DS . 'img' . DS . $config['species_5code'] . '.' . $speciesImage->ext();
        debug($orcaeSpeciesImage);
        // Copies species image to Orcae's folder
        $speciesImage->copy($orcaeSpeciesImage);
      }
    }

    // Saves group information
    public function saveSpeciesGroup(&$config) {
      // Saves group into Orcae's db
      $saved = $this->Group->save(array(
        'id' => null,
        'name' => '',
        'taxid' => $config['species_taxid'],
        'description' => $config['group_description'],
        'invitationmail' => null,
        'upgrademail' => null,
        'alertmail' => null,
        'welcome' => $config['group_welcome']
      ));
      // Returns false if saving went wrong
      if(!$saved) return false;
      // Updates group id
      $config['group_id'] = $this->Group->id;
      // Saves many-to-many relationshipt between Group and User, called UserGroup
      $saved = $this->UserGroup->save(array(
        'id' => null,
        'group_id' => $config['group_id'],
        'user_id' => $config['user_id'],
        'status' => null
      ));
      // Returns saving results
      return $saved;
    }

    // Saves species config yaml file
    public function writeSpeciesYaml($config) {
      // Creates new orcae_<5code>.yaml file
      debug(Configure::read('OrcaeUpload.orcaeConfig') . DS . $config['species_5code'] . '_conf.yaml');
      $speciesFile = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . $config['species_5code'] . '_conf.yaml', true);
      // Prepares file to be saved into orcae_conf directory
      $speciesYaml= $speciesFile->prepare($config['config_species']);
      // Tries to write species configuration into Orcae's folder
      return $speciesFile->write($speciesYaml);
    }

    // Saves orcae config .yaml paragraph
    public function writeConfigYaml($config) {
      // Defines 5code of species which will be updated
      $shortname = $config['species_5code'];
      // Reads and parses user created .yaml configuration
      $yaml = Spyc::YAMLLoad($config['config_bogas']);
      // Retrieves first element of .yaml configuration, which should be <5code>, but we don't know which
      $yaml = array($shortname => array_shift($yaml));
      // Leaves only 'current' section of .yaml configuration
      $current = isset($yaml[$shortname]['current']) ? $yaml[$shortname]['current'] : array();
      $yaml[$shortname] = array('current' => $current);
      // Defines standard configuration to be intersected with user defined values
      $yaml[$shortname]['current'] = array_intersect(
        // Default values
        array(
          'current_release' => 1,
          'description' => '',
          'major_version' => 1,
          'minor_version' => 1,
          'security' => 'develop',
          'source' => '',
          'start_locus' => ''
        ),
        // User specified values
        $yaml[$shortname]['current']
      );
      // Adds fields to .yaml configuration which cannot be modified by the user
      $db = ConnectionManager::getDataSource('orcae_bogas')->config; // Database's data
      $yaml[$shortname]['current'] = array_merge(
        $yaml[$shortname]['current'],
        array(
          'password'  => $db['password'],
          'database'  => $config['database'],
          'port'      => (int)$db['port'],
          'hostname'  => $db['host'],
          'username'  => $db['login']
        )
      );

      // Creates a reference to old configuration file
      $oldFile = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . 'orcae_conf.yaml', true);
      // Parses file into .yaml format
      $oldYaml = Spyc::YAMLLoad($oldFile->read());
      // Adds newly created configuration section
      $oldYaml = array_merge($oldYaml, $yaml);
      debug($oldYaml);
      // Saves updated yaml file
      return $oldFile->write(Spyc::YAMLDump($oldYaml, false, 0));
    }

    // Reads orcae conf .yaml file
    // Returns file's content as associative array
    public function readConfigYaml() {
      // Defines a file stream
      $file = new File(Configure::read('OrcaeUpload.orcaeConfig') . DS . 'orcae_conf.yaml', false);

      // Checks if file exists
      if(!$file->exists()) {
        return false;
      }

      // Reads file content
      $yaml = $file->read();

      // Parses file content into yaml
      $yaml = Spyc::YAMLLoad($file);

      // Returns file content
      return $yaml;
    }
}
?>
