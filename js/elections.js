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

        if (this.allValid) {
            var confirmed = confirm('If you are satisfied with your choices, click OK to complete your vote.');
            return confirmed;
        }
        return false;
    },
    _toggleWriteIn: function (isWriteIn, officeKey) {
        var $writeIn = jQuery('#write_in_' + officeKey);
        var $mustBe = jQuery('#must_be_' + officeKey);
        $writeIn.toggleClass('required', isWriteIn)
            .toggle(isWriteIn)
            .val('')
            .focus();
        $mustBe.toggle(isWriteIn);
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
            var officeKey = $this.attr('data-key');
            var officeName = $this.attr('data-name');
            var isWriteIn = ($this.attr('data-type') === 'write-in');
            if (!isWriteIn) {
                jQuery('#vote_' + officeKey).val(officeName);
            }
            self._toggleWriteIn(isWriteIn, officeKey);
        });
    }
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
                onResult: function (node, query, result, resultHtmlList) {
                    // Prevent user from typing items not in list
                    if (query.length > 2) {
                        var officeKey = node.attr('data-key');
                        var $mustBe = jQuery('#must_be_' + officeKey);
                        var found = jQuery.grep(electionNamespace.memberList, function(value, i) {
                            return value.indexOf(query) !== -1
                        }).length;
                        var notFound = false
                        if (found === 0) {
                            if (result.length === 0) {
                                node.val(query.slice(0, -1));
                                notFound = true;
                            }
                        }
                        $mustBe.toggleClass('election-error', notFound);
                    }
                },
                onClickAfter: function (node, a, item, event) {
                    var officeKey = node.attr('data-key');
                    jQuery('#vote_' + officeKey).val(item.display);
                }
            }
        });
        electionNamespace.election = Object.create(CsoElection);
        electionNamespace.election.init();
    }
});