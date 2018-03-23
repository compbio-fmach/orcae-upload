// logout function
// makes a call for logout API, then reloads the page
function logout() {
  $.ajax({
    method: 'post',
    url: './API/logout',
    complete: function(xhr, textStatus) {
      location.reload();
      return;
    }
  });
}
