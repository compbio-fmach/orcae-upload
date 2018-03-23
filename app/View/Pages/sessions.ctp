<?php
  // set current page title
  $this->assign('title', 'active sessions');
  // set css
  $this->Html->css('orcae-upload', array('inline' => false));

  // navbar
  echo $this->element('navbar.top', array('active' => 'sessions'));
?>

<!-- table -->
<div class="container pt-3">
  <h4 class="mb-3 text-left">Configuration sessions not completed</h4>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>#</th>
        <th>Species' name</th>
        <th>Created</th>
        <th>Last updated</th>
        <th>Status</th>
        <th>View</th>
        <th>Delete</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td>Species' name</td>
        <td>Created</td>
        <td>Last updated</td>
        <td>Status</td>
        <td class="text-primary"><u>View</u></td>
        <td class="text-danger"><u>Delete</u></td>
      </tr>
      <tr>
        <td>2</td>
        <td>Species' name</td>
        <td>Created</td>
        <td>Last updated</td>
        <td>Status</td>
        <td class="text-primary"><u>View</u></td>
        <td class="text-danger"><u>Delete</u></td>
      </tr>
      <tr>
        <td>2</td>
        <td>Species' name</td>
        <td>Created</td>
        <td>Last updated</td>
        <td>Status</td>
        <td class="text-primary"><u>View</u></td>
        <td class="text-danger"><u>Delete</u></td>
      </tr>
    </tbody>
  </table>
</div>
