<?php
  App::uses('Folder', 'Utility');
  App::uses('File', 'Utility');
  class GenomeUpload extends AppModel {
    public $useDbConfig = 'orcae_upload';
    public $useTable = 'genome_uploads';

    public $prevData = null;

    /**
     * @method beforeDeleteAll gets executed before deleteAll
     * Executes a find query with same conditions as deleteAll
     * Stores previous values into @var this->prevData
     * @return void
     */
    protected function beforeDeleteAll($conditions) {
      $this->prevData = $this->find(
        'all',
        array(
          'conditions' => $conditions
        )
      );
    }

    public function deleteAll($conditions, $cascade = true, $callbacks = false) {
      // Executes 'before' callback
      $this->beforeDeleteAll($conditions);
      // Executes standard deletion function
      return parent::deleteAll($conditions, $cascade, $callbacks);
    }

    /**
     * @method getUploadPath
     * @return file if @param fileName is set and file exists into directory
     * @return null if @param fileName is set but file doesn't exist
     * @return path if @param fileName is not set
     */
    public function getUploadPath($userId, $fileName = '', $create = true) {
      // Defines upload folder (creates it if it doesn't exist)
      $create = ($create == true);
      $folder = new Folder(WWW_ROOT . 'files' . DS . 'genome_uploads' . DS . $userId, $create);
      // fileName parameter is empty: returns folder's path
      if(empty($fileName)) {
        return $folder->pwd();
      }
      // Otherwise, searches for give file name into folder
      else {
        $files = $folder->find($fileName);
        return (isset($files[0])) ? ($folder->pwd() . DS . $files[0]) : null;
      }
    }

    /**
     * @method getUniqueFileName
     * @return fileName which is unique into current user directory
     * WARNING: this method assumes that $file contains a valid file name
     */
    public function getUniqueFileName($userId, $file) {
      // Defines file
      $file = new File($file);
      // Retrieves uploads folder
      $folder = new Folder($this->getUploadPath($userId));
      // Searches for file name not already taken
      do {
        $uniqueFileName = uniqid() . '.' . $file->ext();
      } while (is_file($folder->pwd() . DS . $uniqueFileName));

      return $uniqueFileName;
    }

  }
?>
