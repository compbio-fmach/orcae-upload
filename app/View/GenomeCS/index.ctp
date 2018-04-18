<?php
/**
 * This view shows a table, which will be filled of user's sessions
 * @param subtitle 'Your genome config sessions' is passed to layout
*/
// Defines subtitle
$this->assign('subtitle', "genome config sessions");
// Defines custom css
$this->Html->css('genomecs', array('inline' => false));
?>

<!-- Top navbar -->
<?php
  // Outputs top navbar. Param page specifies which state navbar must have.
  echo $this->element('navbar.top', array('page' => 'sessions'));
?>

<!-- Page main content (not displayed by default, must be showed using javascript) -->
<div class="container main">

  <!-- Table title container -->
  <div class="table-title">
    <!-- Title (left) -->
    <h4 class="table-title-left">Genome configuration sessions</h4>
    <!-- Link to new session creation (right) -->
    <a class="table-title-right text-primary" href="./genomecs/new">Create new genome</a>
  </div>

  <!-- Table of sessions: ses Ajax to load contents -->
  <table class="table table-bordered bg-white">
    <thead>
      <tr>
        <!-- Row number -->
        <th>#</th>
        <th>Species' name</th>
        <th>Created</th>
        <th>Last update</th>
        <!-- Link to access the session represented by the current row -->
        <th>View</th>
        <!-- Asks if user wants to delete this session -->
        <th>Delete</th>
      </tr>
    </thead>
    <!-- Table body will be filled with ajax -->
    <tbody id="genomecs-list"></tbody>
  </table>
</div>

<!-- Page configuration scripts -->
<script type="text/javascript">
  // Initializes page main container
  var $container = undefined;
  // Initializes sessions container (tbody)
  var $sessions = undefined;

  // jQuery inizialization of current page
  $(document).ready(function(){

    // Retrieves page main container
    $container = $('.main.container');
    // Retrieves tbody dom elements
    $tbody = $('#genomecs-list');

    // Ajax API request to fill table
    $.ajax({
      // Relative url to sessions API
      url: './API/genomecs',
      method: 'GET',
      // datatype json enables json auto parsing (creates xhr.responseJSON)
      dataType: 'json'
    }).done(function(data){
      // Retrieves array of genomecs
      var rows = data;
      // Every genomecs is evaluated and a DOM element is created
      rows.forEach(function(row, index) {
        $tbody.append(createTableRow(row, index));
      });
      // Shows page content
      $container.fadeIn('slow');
    });

  });

  /**
   * @function createTableRow creates a row <tr/> for #sessions <table/> (actually <tbody/>)
   * @param session is the session object from which the row will be created
   * @param index is the index of row in table
   * @return table row created from session
   */
  function createTableRow(genomecs, index) {
    // creates <tr/>
    var $row = $('<tr/>');

    // creates an array of cell html fields
    var cells = new Array(
      index,
      genomecs.species_name,
      genomecs.created,
      genomecs.updated,
      "<a class='text-primary' href='./genomecs/" + genomecs.id + "'>View</a>",
      "<a class='text-danger' href='#'>Delete</a>"
    );

    // creates table cells
    for(var i = 0; i < cells.length; i++) {
      $row.append($('<td/>', {
        html: cells[i]
      }));
    }

    // returns created row
    return $row;
  }

</script>
