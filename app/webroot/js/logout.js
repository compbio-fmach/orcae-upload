// logout function
// makes a call for logout API, then reloads the page
function logout(webRoot) {
  console.log(webRoot);
  $.ajax({
    method: 'post',
    url: webRoot + '/API/logout',
    complete: function(xhr, textStatus) {
      location.reload();
      return;
    }
  });
}
