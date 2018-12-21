var UserAgentInfo = UserAgentInfo || {

    // A utility function to log if debug enabled
    log: function() {
        if (! UserAgentInfo.isDev) return false;

        // Make console logging more resilient to Redmond
        try {
            console.log.apply(this,arguments);
        } catch(err) {
            // Error trying to apply logs to console (problem with IE11)
            try {
                console.log(arguments);
            } catch (err2) {
                // Can't even do that!  Argh - no logging
            }
        }
    },

    // Go through configs and do the substitution
    init: function() {

        UserAgentInfo.log("Debug Mode Enabled!", this);

        $(this.configs).each(function (i,config) {
            UserAgentInfo.log(config);
            var element = $('input[name="' + config.field_name + '"]');
            // Did we find the element?
            if (element.length) {
                // Is there a value to put there?
                if (config.value) {
                    if (element.val().length) {
                        // There is existing data in the input
                        if (config.overwrite) {
                            // We are overriding the old value
                            UserAgentInfo.log ('Overwriting ' + config.field_name, element.val(), config.value);
                            element.val(config.value);
                        } else {
                            // Existing value - do not overwrite
                            UserAgentInfo.log(config.field_name + ' already set');
                        }
                    } else {
                        // The existing input is empty
                        element.val(config.value);
                    }
                } else {
                    UserAgentInfo.log ('There is not value for ' + config.field_name);
                }
            } else {
                UserAgentInfo.log( 'Unable to find ' + config.field_name);
            }
        })
    },

};
