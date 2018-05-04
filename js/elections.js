var CsoElection = {
    formId: '#cso_election',
    formValid: false,
    allValid: false,
    validator: null,
    init: function () {
        this._setListeners();
        this.validator = Validate;
        this.validator.init({
            formId: this.formId,
            listen: false
        });
    },
    _doAjax: function (action, formId) {
        var self = this;
        var formData = jQuery(formId).serialize();
        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: electionNamespace.ajaxUrl,
            data: {
                action: action,
                data: formData,
                //security: jQuery('#cso_elections_nonce').val()
            },
            success: function (response) {
                if (response.success) {
                    document.location = response.redirect;
                } else {
                    if (response.errorMessage) {
                        alert(response.errorMessage);
                        jQuery('#election_container').remove();
                    }
                }
                console.log(response);
                //jQuery('#submit_spinner').hide();
            },
            error: function (response) {
                console.log(response);
                //jQuery('#submit_spinner').hide();
            }
        });
    },
    _isValidVote: function () {
        var self = this;
        this.allValid = true;
        jQuery(this.formId + ' *').filter(':input').each(function (evt) {
            var isValid = self.validator.validate(this);
            self.allValid = (self.allValid && isValid);
        })

        return false;
    },
    _setListeners: function () {
        var self = this;
        jQuery('#vote_button').on('click', function () {
            if (self._isValidVote()) {
                self._doAjax('cso_elections', self.formId);
            }
        });
        jQuery('input:radio').on('click', function () {
            var $this = jQuery(this);
            var radioName = $this.attr('name');
            var isWriteIn = ($this.attr('data-type') === 'write-in');
            var $writeIn = jQuery('#write_in_' + radioName);
            $writeIn.toggleClass('required', isWriteIn);
        });
    },
    _validateVote: function (self, isValid) {
        self.formValid = isValid;
        self._enableVoteButton();
    },
};

jQuery(document).ready(function ($) {
    if (jQuery('#cso_election').is('*')) {
        jQuery.typeahead({
            input: '.js-typeahead',
            order: "asc",
            source: {
                data: electionNamespace.memberList
            },
            callback: {
                onInit: function (node) {
                    console.log('It init');
                },
                onReady: function (node) {
                    console.log('It is ready');
                },
                onSearch: function (node, query) {
                    console.log('It searches');
                },
                onResult: function (node, query, result, resultCount, resultCountPerGroup) {
                    console.log('Some typing');
                    // var found = (query.length > 2 && resultCount === 0);
                    // electionNamespace.rideLeader.toggleGuestButton(found, query);
                }
            }
        });
        electionNamespace.election = Object.create(CsoElection);
        electionNamespace.election.init();
    }
});