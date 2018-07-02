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
    "<a class='text-secondary update-status' href='#'>Searching for updates    <div class='loader'></div></a>",
    "<a class='text-primary' href='./" + row.id + "/'>View</a>",
    "<a class='text-danger'>Delete</a>"
  );

  // creates table cells
  for(var i = 0; i < cells.length; i++) {
    $row.append($('<td/>', {
      html: cells[i]
    }));
  }

  // Creates a new instance of genome update checker
  var updater = new GenomeUpdates({
    genomeConfig: row
  });
  // Starts polling
  updater.polling({
    onEmpty: function() {
      $row.find('.update-status').text('n.d.');
    },
    onSuccess: function(update) {
      $row.find('.update-status').text('Success');
    },
    onFailure: function(update) {
      $row.find('.update-status').text('Failed');
    }
  });

  // returns created row
  return $row;
}
