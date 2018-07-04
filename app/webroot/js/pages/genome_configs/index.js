$(function(){
  // Loads page table
  $.ajax({
    // Relative url to sessions API
    url: Defaults.apiRoot + 'genome_configs/',
    method: 'GET',
    // datatype json enables json auto parsing (creates xhr.responseJSON)
    dataType: 'json'
  }).done(function(data){
    // Retrieves array of genomecs
    var rows = data;
    // Every genomecs is evaluated and a DOM element is created
    var $rows = new Array();
    rows.forEach(function(row, index) {
      $rows.push(createTableRow(row, index));
    });

    // Shows page content
    $('#genome-configs').append($rows);
    $('.main.container').fadeIn('normal');
  });
});

function createTableRow(row, index) {
  // creates <tr/>
  var $row = $('<tr/>');

  // creates an array of cell html fields
  var cells = new Array(
    index,
    row.species_name,
    row.created,
    row.modified,
    "<a class='update-status' href='#'>Checking updates...</a>",
    "<a class='update-view text-primary' href='./" + row.id + "/'>View</a>",
    "<a class='update-delete text-danger' href='#'>Delete</a>"
  );

  // creates table cells
  for(var i = 0; i < cells.length; i++) {
    $row.append($('<td/>', {
      html: cells[i]
    }));
  }

  // Handles row deletion
  $row.on('click', '.update-delete', function(e) {
    deleteTableRow(row, index);
  });

  // Creates a new instance of genome update checker
  var updater = new GenomeUpdates({
    genomeConfig: row
  });
  // Starts polling
  updater.polling({
    onUpdateEmpty: function() {
      $row.find('.update-status').text('No update');
    },
    onUpdateSuccess: function(update) {
      // Appends last update to row
      row.last_update = update;
      // changes update status text
      $row.find('.update-status').text('Successful');
      // Sets row status to 'disabled'
      $row.addClass('update-success');
    },
    onUpdateUpdating: function(update) {
      row.last_update = update;
      $row.find('.update-status').text('Updating...');
    },
    onUpdateFailure: function(update) {
      row.last_update = update;
      $row.find('.update-status').text('Failed');
    }
  });

  // returns created row
  return $row;
}

function deleteTableRow(row, index) {
  // Uses index to retrieve correct row
  var $row = $($('#genome-configs').find('tr').get(index));

  // Case status is 'updating'
  if(row.last_update && row.last_update == 'updating') {
    // Does nothing
    return;
  }

  // Saves old row content
  var $oldRow = $row.clone(true, true);

  // Otherwise
  // Creates new row
  var $newRow = $(
    '<tr class="update-alert-danger">' +
      '<td colspan="' + $oldRow.find('td').length + '">' +
        'Delete selected genome configuration? ' +
        'Press <a class="cancel" href="#">CANCEL</a> to abort changes. ' +
        'Press <a class="delete" href="#">DELETE</a> otherwise.' +
      '</td>' +
    '</tr>'
  );
  // Substitutes original row with message
  $row.replaceWith($newRow);

  // Handlers
  $newRow
  // Cancel handler
  .on('click', '.cancel', function(e) {
    // Restores old row
    $newRow.replaceWith($oldRow);
  })
  // Delete handler
  .on('click', '.delete', function(e) {
    // Sends json message to APIs
    $.ajax({
      url: Defaults.apiRoot + 'genome_configs/' + row.id,
      method: 'DELETE',
      dataType: 'json'
    })
    // Row deleted
    .done(function(data) {
      $oldRow.remove();
      $newRow.remove();
    });
  });
}
