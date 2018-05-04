/**
 * This library implements validation of sessions/config form
 * It is similar to SessionConfig model validation
 * There are basically two types of errors:
 *  - errors (blocking): does not allow to save data
 *  - warnings (non-blocking): advise that the user can save data,
 *    but will not be able to go on with update of the system with those saved data
 */
function Validator() {

  // Warnings definitions
  this.warnings = {
    'species_taxid': {
      regex: /^\d{1,}$/,
      message: "Species taxonomy id should be set"
    },
    'species_5code': {
      regex: /^.{5}$/,
      message: "Species shortname should contain 5 chars"
    },
    'group_description': {
      regex: /^.{1,}$/,
      message: "Group description should be set"
    },
    'gorup_welcome': {
      regex: /^.{1,}$/,
      message: "Group description should be set"
    }
  }

  // Blocking-errors definitions
  this.errors = {
    'species_taxid': {
      regex: /^\d{0,}$/,
      message: "Species taxonomy must contain only digits"
    },
    'species_name': [
      {
        regex: /^.{0,50}$/,
        message: 'Species must be less than 50 chars long'
      },
      {
        regex: /^[a-zA-Z0-9\s]{0,}$/,
        message: 'Species name must contain only numbers, letters and spaces'
      },
      {
        regex: /^.{1,}$/,
        message: 'Species name must be set'
      }
    ],
    'species_5code': [
      {
        regex: /^.{0,5}$/,
        message: 'Species shortname must be less than 5 chars long'
      },
      {
        regex: /^[a-zA-Z0-9]{0,}$/,
        message: 'Species shortname must contain only numbers and letters'
      }
    ],
    'group_description': {
      regex: /^.{0,255}$/,
      message: 'Group description must be less than 255 chars long'
    }
  }

  /**
   * @method validate renders error/warning of given input field
   * @param field is the index of the validator
   * @param value is the value which will be tested
   * @return {error: message} where error is the first error found for tested field
   * @return {warning: message} where warning is the first warning found for tested field (If no error has been found)
   * @return true if no error or warning found
   */
  this.validate = function(field, value) {

    // Checks errors
    if(this.errors[field]) {
      var errors = Array.isArray(this.errors[field]) ? this.errors[field] : new Array(this.errors[field]);
      for(var i = 0; i < errors.length; i++) {
        // Defines current error
        var error = errors[i];

        // Checks value with regex
        if(!error.regex.test(value)) {
          // Returns an object with error message in error attribute
          return { 'error': error.message };
        }
      }
    }

    // Checks warnings
    if(this.warnings[field]) {
      var warnings = Array.isArray(this.warnings[field]) ? this.warnings[field] : new Array(this.warnings[field]);
      for(var i = 0; i < warnings.length; i++) {
        // Defines current warning
        var warning = warnings[i];

        // Checks warning
        if(!warning.regex.test(value)) {
          return { 'warning': warning.message };
        }
      }
    }

    // Returns true if every condition has been satisfied
    return true;
  }
}
