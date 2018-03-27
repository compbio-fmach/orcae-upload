// this function allows to see which file has been selected from a bootstrap 4 file browser
function fileBrowser(e) {
  // takes a DOM element as input
  // it works with onchange attribute in html5
  var $this = $(e);

  // calculates the filename
  let fileName = $this.val().split('\\').pop();
  // shows filename into label
  $this.next('.custom-file-label').addClass("selected").html(fileName);
}
