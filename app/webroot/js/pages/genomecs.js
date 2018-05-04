/**
 * @function GenomeCS creates an objects which implements calls to /API/GenomeCS APIs
 * @return void
 */
function GenomeCS(webroot) {
  // Defines root url
  var webroot = (webroot != undefined) ? webroot : '/';
  // Defines API route based on webroot
  var api = webroot + 'API/genomecs';

  /**
   * @method get retrieves GenomeCS object from server
   * @param id allows to search GenomeCS by id
   * @param params allows to add parameters to GET request
   * @return ajax request
   */
  this.get = function(id, params) {
    var $ajax = undefined;
    if(id != undefined) {
      $ajax = $.ajax({
        url: api + '/' + id,
        method: 'GET',
        dataType: 'json',
        data: params
      });
    }
    // Calls
    else {
      $ajax = $.ajax({
        url: api,
        method: 'GET',
        dataType: 'json',
        data: params
      });
    }

    return ajax;
  }

  /**
   * @method post sends GenomeCS to server
   * It is configured to send species image either
   * @return ajax request
   */
  this.send = function(data) {
    return $.ajax({
      url: api + '/' + data.id
      method: 'POST',
      processData: false,
      contentType: false,
      data: data,
      dataType: 'json'
    });
  }
}
