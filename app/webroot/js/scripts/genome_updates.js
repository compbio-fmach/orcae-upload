/**
 * This library implements calls to genome updates api
 * Implements polling functions to retrieve Orcae's update status
 */
function GenomeUpdates(options) {
  // Defines internal options
  var options = Object.assign({
    // Genome config instance
    genomeConfig: {id: ''},
    // Api url
    apiRoot: Defaults.apiRoot + 'genome_configs/',
    // Defines default polling interval
    pollingInterval: 1000
  }, options);

  // Makes genome updates url
  this.makeUrl = function() {
    return options.apiRoot + options.genomeConfig.id + '/updates/';
  }

  // Makex a GET call
  this.get = function() {
    // Defines a reference to current object
    var self = this;
    // Makes ajax request
    return $.ajax({
      url: self.makeUrl(),
      method: 'GET',
      dataType: 'json'
    })
  }

  // Makes a POST call
  this.post = function(params = {}) {
    // Defines a reference to current object
    var self = this;
    // Makes ajax request
    return $.ajax({
      url: self.makeUrl(),
      method: 'POST',
      data: params,
      dataType: 'json'
    })
  }

  // Makes polling request to server in order to retirve status
  this.polling = function(after = {}) {
    // Defines a reference to current object
    var self = this;
    // Makes ajax request
    self.get()
    // Handles successful AJAX response
    .done(function(data) {
      // Data is an array of genome updates
      // Retrieves first genome update
      var genomeUpdate = data.shift();

      // Case no genome update
      if(!genomeUpdate) {
        if(after.onUpdateEmpty) {
          // Executes function on 'updatable'
          after.onUpdateEmpty();
        }
        return;
      }

      // Case genome update finished successfully
      if(genomeUpdate.status == 'success' && after.onUpdateSuccess) {
        after.onUpdateSuccess(genomeUpdate);
        return;
      }

      // Case genome update finished without success
      if(genomeUpdate.status == 'failure' && after.onUpdateFailure) {
        after.onUpdateFailure(genomeUpdate);
        return;
      }

      // Case genome update has not finished yet
      if(genomeUpdate.status == 'updating') {
        // Calls function on each result
        if(after.onUpdateUpdating) {
          // Terminates if 'onUpdating' function returns false
          if(!after.onUpdateUpdating(genomeUpdate)) {
            return;
          }
        }
        // Calls himself after some time
        setTimeout(function() {
          self.polling(after);
        }, options.pollingInterval);
      }
    })
    // Handles errors on AJAX response
    .fail(function(data) {
      if(after.onFailure) after.onFailure(data);
    });
  }
}
