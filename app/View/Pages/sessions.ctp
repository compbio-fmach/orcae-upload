<?php
  // set current page title
  $this->assign('title', 'active sessions');
  // set css
  $this->Html->css('sessions', array('inline' => false));

  // navbar
  echo $this->element('navbar.top', array('current' => 'sessions'));
?>

<!-- table -->
<div class="container main">
  <h4 class="mb-3 d-inline-block text-left">Configuration sessions not completed</h4>
  <a href="<?php echo $this->webroot; ?>sessions/config" class="mt-2 d-inline-block float-right text-primary">Create new genome</a>
  <table class="table table-bordered bg-white">
    <thead>
      <tr>
        <th>#</th>
        <th>Species' name</th>
        <th>Created</th>
        <th>Last update</th>
        <th>Status</th>
        <th>View</th>
        <th>Delete</th>
      </tr>
    </thead>
    <tbody>
      <!-- tbody will be filled by ajax -->
    </tbody>
  </table>
</div>

<script type="text/javascript">

  // webrrot path retrieved by the server
  var webRoot = <?php echo $this->webroot; ?>;

  $(document).ready(function(){
    // binds table tbody
    var $tbody = $('tbody');

    // retrieves sessions configurations from server
    $.ajax({
      url: webRoot + '/API/sessions/config',
      method: 'GET',
      dataType: 'json',
      complete: function(xhr, textStatus) {
        // catches server error
        if(xhr.status != '200') {
          return;
        }

        // retrieves sessions from response
        var sessions = xhr.responseJSON;

        // creates table
        sessions.forEach(function(value, index){
          $tbody.append(createTableRow(value, index));
        });

        // after content loaded, show it
        $('.container.main').fadeIn('slow');
      }
    });
  });

  /*
  * @return table row created from session
  * @param session is the session object from which the row will be created
  * @param index is the index of row in table
  */
  function createTableRow(session, index) {
    // creates tr
    var $row = $('<tr/>');

    // cell index
    $row.append($('<td/>', {
      text: index
    }));

    // cell species name
    $row.append($('<td/>', {
      text: session.sepcies_name
    }));

    // cell creation time
    $row.append($('<td/>', {
      text: session.created
    }));

    // cell last update time
    $row.append($('<td/>', {
      text: session.updated
    }));

    // cell status
    $row.append($('<td/>', {
      text: 'n/d'
    }));

    // cell view link
    $row.append($('<td/>', {
      html: "<a class='text-primary' href='" + webRoot + "sessions/" + session.id + "/config'>View</a>"
    }));

    // cell delete link
    $row.append($('<td/>', {
      html: "<a class='text-danger' href='#'>Delete</a>"
    }));

    // returns created roww
    return $row;
  }
</script>
