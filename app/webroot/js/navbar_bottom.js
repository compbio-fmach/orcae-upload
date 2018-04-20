/**
 * @function toUploads goes to upload page from current path
 * @return false
 */
function toUploads() {
  // Creates new path
  var href = window.location.href;
  // Redirects to location
  window.location.href = href;
  // Prevents executing other actions
  return false;
}

/**
 * @function toConfig goes to genome configuration page
 * @return false
 */
function toConfig() {
  // Creates new path
  var href = window.location.href;
  // Redirects to location
  window.location.href = href;
  // Prevents executing other actions
  return false;
}
