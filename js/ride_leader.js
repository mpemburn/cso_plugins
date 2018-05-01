RideLeader = {
    init: function () {
        this._setListener();
    },
    toggleGuestButton: function (show, guestName) {
        jQuery('.typeahead__button, #guest_phone, .guest_message').toggle(show);
        jQuery('.guest_message').html('Tap "Add" to add ' + guestName + ' to Guest List')
    },
    _addGuest: function () {
        var $input = jQuery('#member_search');
        this._doAjax('add_member', 'leader_form');
    },
    _doAjax: function (action, formId) {
        var self = this;
        var formData = jQuery('#' + formId).serialize();
        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: leaderNamespace.ajaxUrl,
            data: {
                action: action,
                data: formData
            },
            success: function (response) {
                jQuery('.leader_spinner, .typeahead__button').hide();
                jQuery('#member_search, #guest_phone, .guest_message').val('');
            },
            error: function (response) {
                jQuery('.leader_spinner').hide();
                console.log(response);
            }
        });
    },
    _setListener: function () {
        var self = this;
        jQuery('#add_guest').off()
            .on('click touchstart', function (evt) {
                evt.preventDefault();
                // Write the value from the typeahead input to the hidden guest_name field
                jQuery('#guest_name').val(jQuery('#member_search').val());
                jQuery('.leader_spinner').show();

                self._doAjax('ride_leader_add_guest', 'leader_form');
            });
    }
};

jQuery(document).ready(function ($) {
    if (jQuery('#leader_form').is('*')) {
        jQuery.typeahead({
            input: '.js-typeahead',
            order: "asc",
            source: {
                data: leaderNamespace.memberList
            },
            callback: {
                onInit: function (node) {
                },
                onResult: function (node, query, result, resultCount, resultCountPerGroup) {
                    var found = (query.length > 2 && resultCount === 0);
                    leaderNamespace.rideLeader.toggleGuestButton(found, query);
                }
            }
        });
        leaderNamespace.rideLeader = Object.create(RideLeader);
        leaderNamespace.rideLeader.init();
    }
});