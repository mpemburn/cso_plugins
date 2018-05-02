var CsoElection = {
    formId: '#cso_election',
    formValid: false,
    init: function () {
        //jQuery('#vote').prop('disabled', false)
        this._setListeners();
        Validate.init({
            formId: this.formId,
            caller: this,
            callback: this._validateVote
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
    _enableVoteButton: function() {
        var valid = this.formValid;

        jQuery('#vote_button').prop('disabled', !valid);
        jQuery('#verify_message').toggle(valid);
    },
    _setListeners: function () {
        var self = this;
        jQuery('#vote_button').on('click', function () {
            self._doAjax('cso_elections', self.formId);
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
            input: '#write_in_president',
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