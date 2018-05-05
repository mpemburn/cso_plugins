var CsoElection = {
    formId: '#cso_election',
    formValid: false,
    allValid: false,
    init: function () {
        this._setListeners();
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

        jQuery('.required').each(function (evt) {
            var isValid = (jQuery(this).val() !== '');
            self.allValid = (self.allValid && isValid);
        });

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
            var $mustBe = jQuery('#must_be_' + radioName);
            jQuery('#checked_' + radioName).val('true');
            $writeIn.toggleClass('required', isWriteIn)
                .toggle(isWriteIn)
                .focus();
            $mustBe.toggle(isWriteIn);
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
            minLength: 3,
            source: {
                data: electionNamespace.memberList
            },
            callback: {
                onSearch: function (node, query) {
                    // Prevent user from typing items not in list
                    if (query.length > 2) {
                        var found = jQuery.grep(electionNamespace.memberList, function(value, i) {
                            return value.indexOf(query) !== -1
                        }).length;
                        if (found === 0) {
                            node.val(query.slice(0, -1));
                        }
                    }
                }
            }
        });
        electionNamespace.election = Object.create(CsoElection);
        electionNamespace.election.init();
    }
});