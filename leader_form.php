<form id="leader_form">
    <input type="hidden" id="guest_name" name="guest_name"/>
    <div class="typeahead__container">
        <div class="typeahead__field">

            <span class="typeahead__query">
                <input class="js-typeahead"
                       id="member_search"
                       name="member_search"
                       type="search"
                       autocomplete="off">
            </span>
            <span class="typeahead__button">
                <button type="submit" id="add_guest">
                    <span class="add_button">Add</span>
                </button>
            </span>
        </div>
        <div>
            <input type="text" id="guest_phone" name="guest_phone" placeholder="Cell Phone"/>
            <div id="submit_spinner" class="leader_spinner"></div>
        </div>
        <div class="guest_message">Tap "Add" to add to Guest List</div>
    </div>
</form>