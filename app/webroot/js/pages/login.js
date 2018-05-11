// Executes when DOM is ready
$(function(){
  // On login button click
  $('#login-button').click(function(e){
    $.ajax({
      url: '../API/login',
      method: 'POST',
      // Passes serialized array to API inside POST body
      data: $('#credentials').serializeArray(),
      dataType: 'json'
    })
    // If login was successfull, reloads and gets redirected to default page
    .done(function(data){
      location.reload();
    })
    // If login failed, shows error message
    .fail(function(data){
      var message = data.responseJSON ? data.responseJSON : data.responseText;
      $('#error').html('<strong>Error!</strong> ' + message + '!').fadeIn();
    });
  });
});
