<?php
// Links user to groups and vice-versa, into a many-to-many relationship
class UserGroup extends AppModel {
  public $useDbConfig = 'orcae_bogas';
  public $useTable = 'users_groups';
}
?>
