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

  /**
   * @method validate
   * @param field is the index of the validator
   * @param value is the value which will be tested
   * @return void
   */
  this.validate = function(field, value) {
      // Defines validations for the specified field
      var errors = this.errors[field];
      var warnings = this.warnings[field];

      // Checks if there are error validations to be done
      if(errors) {
        // Defines an array of errors, even if it is not an array currently
        var errors = Array.isArray(errors) ? errors : new Array(errors);
        // Loops through every error
        for(var i = 0; i < errors.length; i++) {
          var error = errors[i];
          // Checks value with regex
          if(!error.regex.test(value)) {
            return {'error': error.message };
          }
        }
      }

      // Checks if there are warning validations to be done
      if(warnings) {
        var warnings = Array.isArray(warnings) ? warnings : new Array(warnings);
        for(var i = 0; i < warnings.length; i++) {
          var warning = warnings[i];
          // Checks value with regex
          if(!warning.regex.test(value)) {
            return {'warning': warning.message };
          }
        }
      }

      // Returns true if every condition has been satisfied
      return {};
  }
}
