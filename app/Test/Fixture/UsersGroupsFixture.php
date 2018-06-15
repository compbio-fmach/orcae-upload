<?php
// Cretaes a fake table for users_groups into orcae_upload_test database
// The table will be automatically deleted after testing
class UsersGroupsFixture extends CakeTestFixture {
  // Imports original table and its rows
  public $table = 'users_groups';
  public $import = array('table' => 'users_groups', 'connection' => 'orcae_bogas', 'records' => false);
}
?>
