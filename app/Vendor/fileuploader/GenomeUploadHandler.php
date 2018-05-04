<?php
App::import('Vendor', 'UploadHandler', array('file' => 'fileuploader/UploadHandler.php'));
class GenomeUploadHandler extends UploadHandler {

  /**
   * @method get_unique_filename returns an unique file name
   * If file has been found and content range is not 0, returns the specified filename
   * If file has been found and content range is 0, creates a new filename (new version of the same filename)
   * If file has not been found, creates a new file name
   * @return name
   */
  protected function get_unique_filename($file_path, $name, $size, $type, $error, $index, $content_range) {
    // Retrieves field and token
    $field = isset($_REQUEST['field']) ? $_REQUEST['field'] : '';
    // Checks if field is valid
    $is_genome = preg_match('/^genome[\d]+/', $field);
    $is_annot = preg_match('/^annot[\d]+/', $field);
    if(!$is_genome && !$is_annot) {
      die('This is not a gene or an annot field');
    }

    // Adds field to name
    $name = implode('.', array(uniqid(), $name));

    return parent::get_unique_filename($file_path, $name, $size, $type, $error, $index, $content_range);
  }
}
?>
