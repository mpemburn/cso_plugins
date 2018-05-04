var Validate = {
    formId: '',
    caller: null,
    callback: null, //function() {},
    listen: true,
    validRequired: {},
    init: function(options) {
        jQuery.extend(this, options);
        this._loadValidations();
        if (this.listen) {
            this._setListener();
        }
    },
    validate: function (thisInput) {
        var $this = jQuery(thisInput);
        var isValid = true;

        var valid = this._setValid($this, ($this.val() !== ''));
        this._toggleValid($this, valid);

        for  (var field in this.validRequired) {
            if (this.validRequired.hasOwnProperty(field)) {
                if (!this.validRequired[field]) {
                    isValid = false;
                }
            }
        }
        if (this.callback !== null) {
            this.callback(this.caller, isValid);
        }

        return isValid;
    },
    _loadValidations: function () {
        var self = this;
        this.validRequired = {};

        // Set all required fields to false to begin with
        jQuery(this.formId + ' *').filter(':input').each(function () {
            self._setValid(jQuery(this), false);
        });
    },
    _setValid: function ($this, truth) {
        var value = $this.val();
        var fieldName = $this.attr('name');
        if ($this.hasClass('required')) {
            this.validRequired[fieldName] = truth;
        }

        return truth;
    },
    _toggleValid: function ($this, isValid) {
        $this.toggleClass('valid', isValid);
    },
    _setListener: function() {
        var self = this;

        jQuery(this.formId + ' *').filter(':input').off()
            .on('keyup change', function (evt) {
                self.validate(this);
        });
    }

};