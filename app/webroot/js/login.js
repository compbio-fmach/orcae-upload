// this javascript library handles login and logout

// login functions
// sends an ajax request to /API/login
// reloads page if successfull
// shows error otherwise
function login(webRoot) {
  // retrieves form elements
  var $form = $('#form-signin'); // form
  var $message = $form.find('#message-signin'); // message container

  // deletes previous error message
  $message.hide(0, function(){

    // request to /API/login
    $.ajax({
      method: 'POST',
      url: webRoot + 'API/login',
      data: $form.serializeArray(),
      complete: function(xhr, textStatus) {
        // DEBUG
        console.log('----- LOGIN DATA -----');
        console.log($form.serializeArray());

        // if status equals 204, reload page
        if(xhr.status == '204') {
          location.reload();
          return;
        }

        // show error otherwise
        if(xhr.status == '401') {
          $message.html("<p class=\"text-danger\">Wrong username or password</p>").show();
          return;
        }

        // this point is reached only if an unexpected error happens
        if(xhr.status == '401') {
          $message.html("<p class=\"text-danger\">" + xhr.responseText + "</p>").show();
        }
      }
    });

  });
}
