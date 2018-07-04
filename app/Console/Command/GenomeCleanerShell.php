<?php
/**
 * This shell is intended to be executed as a chron process
 * Handles useless files removal
 */
class GenomeCleanerShell extends AppShell {
  // Models required from this shell
  public $uses = array('GenomeConfig', 'GenomeUpdate', 'GenomeUpload', 'Species', 'User');

  // Orcae's users
  protected $users = array();

  // Initializes config and update
  public function main() {
    $this->retrieveUsers();

    // Adds genome configurations to user
    // Adds genome uploads and genome updates to genome configurations
    foreach($this->users as &$user) {
      $this->retrieveUserConfigs($user);
      foreach($user['configs'] as &$config) {
        $this->retrieveConfigUploads($config);
        $this->retrieveConfigUpdates($config);
      }
    }

    // Removes not used databases
    $this->dropUnusedDatabases();
  }

  // Retrieves Orcae's users
  protected function retrieveUsers() {
    $users = $this->User->find('all');
    foreach($users as $user) {
      $this->users[$user['id']] = $user['User'];
    }
  }

  // Retrieves genome configs relative to a specific user
  protected function retrieveUserConfigs(&$user) {
    $configs = $this->GenomeConfig->find('all', array(
      'conditions' => array(
        'user_id' => $user['id']
      )
    ));
    $user['configs'] = array_map(array_shift, $configs);
  }

  // Retrieves genome uploads relative to genome config
  protected function retrieveConfigUploads(&$config) {
    // Defines array
    $config['uploads'] = array();

    // Executes query
    $uploads = $this->GenomeUpload->find('all', array(
      'conditions' => array(
        'config_id' => $config['id']
      )
    ));

    // Parses uploads retrieved
    foreach($uploads as $upload) {
      $upload = $upload['GenomeUpload'];
      $index = $upload['stored_as'];
      if($index) {
        $config['uploads'][$index] = $upload;
      }
    }
  }

  // Retreieves genome updates relative to genome config
  protected function retrieveConfigUpdates(&$config) {
    $config['updates'] = array();
    $updates = $this->GenomeUpdate->find('all', array(
      'conditions' => array(
        'config_id' => $config['id']
      )
    ));
    // Parses uploads retrieved
    foreach($updates as $update) {
      $update = $update['GenomeUpload'];
      $index = $update['id'];
      if($index) {
        $config['updates'][$index] = $update;
      }
    }
  }

  // Finds user by id into users array
  protected function findUser($userId) {
    return (isset($this->users[$userId]) && !empty($this->users[$userId])) ? $this->users[$userId] : null;
  }

  // Removes uploaded files whose name is not bound to any genome upload instance
  protected function removeGenomeUploads() {
    // Retrieves genome uploads main folder (before users' folders)
    $rootDir = new Folder($this->GenomeUpload->getUploadPath(''), false);

    // Checks if folder exists
    if(!$rootDir->Path) {
      $this->error('no-uploads-root', 'Uploads root folder has not been found');
    }

    // Loops users' folders
    foreach($rootDir->find() as $userId) {
      // Instances folder
      $userDir = new Folder($rootDir->pwd() . DS . $userId, false);
      // Checks if folder exists
      if($userDir->path) {
        // Searches user
        $user = $this->findUser($userId);
        // Case folder is not bound to any user
        if(!$user) {
          // Removes folder
          $userDir->remove();
        }
        // Case folder ha an user bound to istelf
        else {
          // Makes a list of files which should be stored into current user's directory
          $uploads = array();
          foreach($user['configs'] as $config) {
            $uploads = array_merge($uploads, $config['uploads']);
          }

          // Lists files into directory
          $files = $userDir->find();
          // Loops every file
          foreach($files as $file) {
            // Checks if name is present into uploads array
            if (!isset($uploads[$file]) || !$uploads[$file]) {
              $file = new File($userDir->pwd() . DS . $file, false);
              $file->delete();
            }
          }
        }
      }
    }
  }

  // Removes upload folders whose id is not bound to any genome update instance
  protected function removeGenomeUpdates() {
    // Retrieves genome uploads main folder (before users' folders)
    $rootDir = new Folder(WWW_ROOT . DS . 'files' . DS . 'genome_updates', false);

    // Checks if folder exists
    if(!$rootDir->Path) {
      $this->error('no-updates-root', 'Updates root folder has not been found');
    }

    // Loops users' folders
    foreach($rootDir->find() as $userId) {
      // Instances folder
      $userDir = new Folder($rootDir->pwd() . DS . $userId, false);
      // Checks if folder exists
      if($userDir->path) {
        // Searches user
        $user = $this->findUser($userId);
        // Case folder is not bound to any user
        if(!$user) {
          // Removes folder
          $userDir->remove();
        }
        // Case folder ha an user bound to istelf
        else {
          // Makes a list of files which should be stored into current user's directory
          $updates = array();
          foreach($user['configs'] as $config) {
            $updates = array_merge($updates, $config['updates']);
          }

          // Lists files into directory
          $folders = $userDir->find();
          // Loops every folder (should be named as update id)
          foreach($folders as $folder) {
            // Checks if name is present into uploads array
            if (!isset($updates[$folder]) || !$updates[$folder]) {
              $folder = new Folder($userDir->pwd() . DS . $folder, false);
              if($folder->path) {
                $folder->delete();
              }
            }
          }
        }
      }
    }
  }

  // Reads orcae conf yaml file
  // Drops unused databases
  protected function dropUnusedDatabases() {
    // Reads orcae conf yaml file
    $yaml = $this->GenomeConfig->readConfigYaml();
    // Checks content
    if(!$yaml) {
      $this->error('no-yaml-conf', 'orcae_conf.yaml configuration file cannot be read');
    }

    // Retrieves databases
    $foundDbs = $this->GenomeConfig->query('SHOW DATABASES;');
    // Parses database as name
    foreach($foundDbs as &$foundDb) {
      $foundDb = $foundDb['SCHEMATA']['Database'];
    }
    // Retrieves only valid databases
    $foundDbs = preg_grep('/^orcae_/', $foundDbs);
    $foundDbs = preg_grep('/_v[d+]$/', $foundDbs);

    // loops through .yaml file species
    // Defines used databases
    $requiredDbs = array();
    foreach($yaml as $species) {
      // Retrieves 'current' attribute of the species
      $current = array_shift($species);
      // Retrieves database name
      if(isset($current['database']) && !empty($current['database'])) {
        $requiredDbs[] = $current['database'];
      }
    }

    // Makes the difference between found databases and required ones
    $unusedDbs = array_diff($foundDbs, $requiredDbs);
    // Drops unused databases
    foreach($unusedDbs as $db) {
      $this->GenomeConfig->query('DROP DATABASE ' . $db . ';')
    }
  }

  public function error($title, $message = null) {
    parent::error($title, $message);
  }
}
?>
