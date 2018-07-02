/**
 * This library implements validation
 * Each field is evaluated against errors firstly and against warnings secondly
 * If validation finds an error, exits and returns that error
 * If no error has been found, validation checks if there are warnings
 * Every field has its own validation objects, which are stored into errors or warnings array
 * If there is an array of objects instead of a single objects, validator validates every fields,
 * but returns only the first error or warnings found for that field.
 */
function Validator() {

  // Warnings definitions
  this.warnings = {}

  // Blocking-errors definitions
  this.errors = {}

  // Creates validation result object
  this.result = function(type = undefined, value = '') {
    switch (type) {
      case 'errors':
        return {error: value};
      case 'warnings':
        return {warning: value};
      default:
        return true;
    }
  }

  /**
   * @method validate
   * @param field is the index of the validator
   * @param value is the value which will be tested
   * @return void
   */
  this.validate = function(field, value) {
      // defines reference to validator
      var self = this;

      // Defines an object containing errors and warnings for current field
      var rules = {
        'errors': self.errors[field] ? self.errors[field] : new Array(),
        'warnings': self.warnings[field] ? self.warnings[field] : new Array()
      };

      // Loops through errors first and warnings then
      // Type states if warning or error
      for(var type in rules) {
        // Transforms every set of rules into array
        if(!Array.isArray(rules[type])) {
          rules[type] = new Array(rules[type]);
        }
        // loops through every validatiopn rule
        for(var index in rules[type]) {
          // Retrieve rule attributes
          var rule = rules[type][index]['rule'];
          var message = rules[type][index]['message'];
          // Checks how to validate
          // Checks if rule is a function
          if(rule instanceof Function) {
            // Executes test function, if returns false exits
            if(!rule(value)) {
              // console.log(self.result(type, message));
              return self.result(type, message);
            }
          }
          // Checks if rule is a regular expression
          else if(rule instanceof RegExp) {
            if(!rule.test(value)) {
              return self.result(type, message);
            }
          }
        }
      }

      // If execution flow reaches this point: no error or warning has been found
      return self.result();
  }
}
