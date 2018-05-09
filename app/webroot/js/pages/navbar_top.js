$(function(){
  // Executes logout call to server, then redirects
  $('#logout').on('click', function(){
    $.ajax({
      url: Defaults.apiRoot + 'logout/',
      method: 'GET'
    }).always(function(data){
      // Reloads page
      location.reload();
      return;
    });
  });
});
