jQuery(document).ready(function ($) {
    if (jQuery('#paypal-button-container').is('*')) {
        paypal.Button.render({

            env: 'production', // sandbox | production

            // PayPal Client IDs
            client: {
                sandbox:    joinNamespace.paypalSandboxKey,
                production: joinNamespace.paypalProductionKey
            },

            style: {
                    size: 'small',
                    color: 'gold',
                    shape: 'rect',
                    label: 'checkout'
            },

            // Show the buyer a 'Pay Now' button in the checkout flow
            commit: true,

            // payment() is called when the button is clicked
            payment: function(data, actions) {
                joinNamespace.joinCso.showPaypalSpinner(true);

                // Make a call to the REST api to create the payment
                return actions.payment.create({
                      payment: {
                          transactions: [
                              {
                                  amount: { total: joinNamespace.joinCso.getTotal(), currency: 'USD' },
                                  description: "Dues payment"
                              }
                          ]
                      }
                  });
            },

            // onAuthorize() is called when the buyer approves the payment
            onAuthorize: function(data, actions) {

                // Make a call to the REST api to execute the payment
                return actions.payment.execute().then(function() {
                    joinNamespace.joinCso.paypalSuccess();
                });
            },
            onCancel: function(data, actions) {
                joinNamespace.joinCso.showPaypalSpinner(false);
            }

        }, '#paypal-button-container');
    }
});