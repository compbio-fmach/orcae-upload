// Executes when DOM is ready
$(function(){
  // On login button click
  $('#login-button').click(function(e){
    $.ajax({
      url: '../API/login',
      method: 'POST',
      // Passes serialized array to API inside POST body
      data: $('#credentials').serializeArray(),
      dataType: 'json',
      complete: function(xhr) {
        // Retrieves response status
        var http = xhr.status;
        var message = ''

        // Checks if response http code is '204' for successful login
        if(xhr.status == 200) {
          // Reloads page
          location.reload();
          return;
        }
        // Checks if response is '401 Not Authorized' or any other error message
        else if (xhr.status == 401){
          message = xhr.responseJSON;
        } else {
          message = xhr.responseText;
        }

        $('#error').html('<strong>Error!</strong> ' + message + '!').fadeIn();
      }
    });
  });
});
