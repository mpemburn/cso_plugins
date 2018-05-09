<div id="join">
    <div id="join_choice">
        <div>
            <label for="type_choice_new"><input id="type_choice_new" name="type_choice" type="radio" value="0" checked>
                <strong>New Member</strong>
            </label>
        </div>
        <div>
            <label for="type_choice_renew"><input id="type_choice_renew" name="type_choice" type="radio" value="1">
                <strong>Renewing Member</strong>
            </label>
        </div>
    </div> <!-- join_choice -->
    <div id="join_renew">
        <div>You'll need to enter the email address you used to sign up for your original membership, as well as the original zip code.
            Please <a href="contact">contact us</a> if you do not have that information:</div>
        <form method="POST" accept-charset="UTF-8" id="member_verify">
            <div class="form-group">
                <div class="col-md-12 row">
                    <div class="col-md-8">
                        <label for="email" class="col-md-12 control-label">Email</label>
                        <input class="col-md-12 required" placeholder="Email *" name="member_email" type="email" value="">
                    </div>
                    <div class="col-md-3">
                        <label for="zip" class="col-md-12 control-label">Zip</label>
                        <input class="col-md-12 required" placeholder="Zip *" name="member_zip" type="text" value="">
                    </div>
                </div>
                <div id="member_verify_button" class="col-md-11">
                    <div class="col-md-12 field-wrapper text-right">
                        <span id="submit_spinner" class="spinner"></span>
                        <button class="btn btn-primary" id="existing_member" name="existing_member" disabled>Submit</button>
                    </div>
                </div>
                <div class="col-md-12" id="member_verify_message">* Indicates required field.</div>
            </div>
        </form>
    </div>  <!-- join_renew -->
    <div id="join_form">
        <div id="test_button_wrapper" class="col-md-4">
            <button class="ui-button" id="test_button">Test</button>
        </div>
        <form method="POST" accept-charset="UTF-8" id="member_update">
            <input name="id" type="hidden" value="">
            <input name="active" type="hidden" value="">
            <input name="paid_amount" type="hidden" value="<?php echo $dues; ?>">
            <input name="process_type" type="hidden" value="<?php echo $process_type; ?>">
            <div class="panel-body row">
                <main id="form_body" class="main-column col-md-12">
                    <div class="col-md-12" id="required_message">* Indicates required field.</div>
                    <div id="member_fields">
                        <div class="form-group">
                            <label for="name" class="col-md-3 control-label">Name</label>
                            <div class="col-md-12 row">
                                <div class="col-md-3 field-wrapper">
                                    <input class="col-md-12 required" placeholder="First Name *" name="first_name" type="text" value="">
                                </div>
                                <div class="col-md-3 field-wrapper">
                                    <input class="col-md-12" placeholder="Middle" name="middle_name" type="text" value="">
                                </div>
                                <div class="col-md-4 field-wrapper">
                                    <input class="col-md-12 required" placeholder="Last Name *" name="last_name" type="text" value="">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address" class="col-md-3 control-label">Address</label>
                            <div class="col-md-12">
                                <div class="col-md-10 field-wrapper row">
                                    <input class="col-md-12 required" placeholder="Address 1 *" name="address_1" type="text" value="">
                                </div>
                            </div>
                            <div class="col-md-12 col-md-offset-1 row">
                                <div class="col-md-10 field-wrapper">
                                    <input class="col-md-12" placeholder="Address 2" name="address_2" type="text" value="">
                                </div>
                            </div>
                            <div class="col-md-12 col-md-offset-10 row">
                                <div class="col-md-4 field-wrapper">
                                    <input class="col-md-12 required" placeholder="City *" name="city" type="text" value="">
                                </div>
                                <div class="col-md-2 field-wrapper">
                                    <select class="col-md-12 required" name="state">
                                        <option value="">Select *</option>
                                        <?php foreach ($states as $state) :
                                            $selected = ($state == $user->state) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo $state; ?>" <?php echo $selected; ?>><?php echo $state; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 field-wrapper">
                                    <input class="col-md-12 required" placeholder="Zip *" name="zip" type="text" value="">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email" class="col-md-3 control-label">Email</label>
                            <div class="col-md-12 row">
                                <div class="col-md-10 field-wrapper">
                                    <input class="col-md-12 required" placeholder="Email *" name="email" type="email" value="">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address" class="col-md-3 control-label">Cell phone</label>
                            <div class="col-md-3 field-wrapper">
                                <input class="col-md-12 required" placeholder="Cell Phone *" name="cell_phone" type="text" value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address" class="col-md-3 control-label">Emergency Contact</label>
                            <div class="col-md-9 row">
                                <div class="col-md-8 field-wrapper">
                                    <input class="col-md-12 required" placeholder="Contact Name *" name="contact_name" type="text" value="">
                                </div>
                            </div>
                            <div class="col-md-3 field-wrapper">
                                <input class="col-md-12 required" placeholder="Contact Phone *" name="contact_phone" type="text" value="">
                            </div>
                        </div>
                    </div>
                    <div id="paypal" class="form-group">
                        <div class="col-md-12">
                            <div class="col-md-12 field-wrapper row">
                                <div class="col-md-8">
                                    &nbsp;
                                </div>
                                <div class="col-md-4">
                                    <h3>Add to Cart:</h3>
                                </div>
                            </div>
                            <div class="col-md-12 field-wrapper row">
                                <div class="col-md-8">
                                    &nbsp;
                                </div>
                                <div class="col-md-4">
                                    <h4>
                                        Dues: $<?php echo $dues; ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-12 field-wrapper row">
                                <?php
                                if (!empty($payments)) :
                                    $count = 1;
                                    foreach ( $payments as $label => $amount) : ?>
                                        <div class="col-md-8">
                                            &nbsp;
                                        </div>
                                        <div class="col-md-4">
                                            <label for="amount_<?php echo $count; ?>">
                                                <input type="checkbox" id="amount_<?php echo $count; ?>" data-label="<?php echo $label; ?>" value="<?php echo $amount; ?>">
                                                <?php echo $label; ?>: $<?php echo $amount; ?></label>
                                        </div>
                                        <?php
                                        $count++;
                                    endforeach;
                                endif;
                                ?>
                            </div>
                            <div class="col-md-12 field-wrapper row">
                                <div class="col-md-7">
                                    <div id="waiver_wrapper">
                                        Please read the <a id="waiver_link" href=""><strong>WAIVER OF LIABILITY</strong></a>
                                        <label for="waiver" class="waiver">
                                            <input type="checkbox" id="waiver" name="waiver" disabled>* I have read and agree to the liability waiver.
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <span id="paypal_spinner" class="spinner"></span>
                                </div>
                                <div id="paypal_wrapper" class="col-md-2">
                                    <div id="paypal-button-container"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

            </div>
        </form>
    </div> <!-- join_form -->
    <div id="waiver_modal">
        <div id="#modal_content" style="font-size: 10pt !important">
            <?php echo $legal; ?>
        </div>
    </div>
</div>
