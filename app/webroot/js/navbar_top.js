/**
 * @function logout makes user unauthenticated
 * After logout, redirects to root page
 * @param webroot allows to specify webroot path
 * @return void
 */
function logout(_webroot = undefined) {

  // Defines relative logout API url
  var url = 'API/logout';

  // Checks if parameter is set (overrides previous value)
  if(_webroot) {
    url = _webroot + url;
  }
  // If no _webroot parameter set, checks if global webroot variable is set
  else if(webroot != undefined) {
    url = webroot + url;
  }
  // Any webroot set: specifies a default one from current path
  else {
    url = './' + url;
  }

  // Calls API and redirects
  $.ajax({
    method: 'POST',
    url: url
  }).always(function(data){
    // Reloads page (should redirect to login)
    location.reload();
  });
}
