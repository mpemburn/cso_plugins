<?php

if (!class_exists('CsoSuiteAdminSettings')) {
    class CsoSuiteAdminSettings
    {
        /**
         * Holds the values to be used in the fields callbacks
         */
        private $options;

        /**
         * Start up
         */
        public function __construct()
        {
            add_action('admin_menu', array($this, 'addPluginPage'));
            add_action('admin_init', array($this, 'pageInit'));
        }

        public function adminTabs( $current = 'homepage' ) {
            $tabs = array( 'homepage' => 'Home Settings', 'general' => 'General', 'footer' => 'Footer' );
            echo '<div id="icon-themes" class="icon32"><br></div>';
            echo '<h2 class="nav-tab-wrapper">';
            foreach( $tabs as $tab => $name ){
                $class = ( $tab == $current ) ? ' nav-tab-active' : '';
                echo "<a class='nav-tab$class' href='?page=theme-settings&tab=$tab'>$name</a>";

            }
            echo '</h2>';
        }
        /**
         * Add options page
         */
        public function addPluginPage()
        {
            // This page will be under "Settings"
            add_options_page(
                'CSO Suite',
                'CSO Suite',
                'manage_options',
                'cso-suite-setting-admin',
                array($this, 'createAdminPage')
            );
        }

        /**
         * Options page callback
         */
        public function createAdminPage()
        {
            // Set class property
            $this->options = get_option('cso_suite_option_name');
            ?>
            <div class="wrap">
                <h1>CSO Suite</h1>
                <form method="post" action="options.php">
                    <?php
                    // This prints out all hidden setting fields
                    settings_fields('cso_suite_option_group');
                    do_settings_sections('cso-suite-setting-admin');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        /**
         * Register and add settings
         */
        public function pageInit()
        {
            register_setting(
                'cso_suite_option_group', // Option group
                'cso_suite_option_name', // Option name
                array($this, 'sanitize') // Sanitize
            );

            add_settings_section(
                'setting_section_id', // ID
                'CSO Suite Settings', // Title
                array($this, 'printSectionInfo'), // Callback
                'cso-suite-setting-admin' // Page
            );

            add_settings_field(
                'api_uri', // ID
                'API URI', // Title
                array($this, 'apiUriCallback'), // Callback
                'cso-suite-setting-admin', // Page
                'setting_section_id' // Section
            );

            add_settings_field(
                'paypal_sandbox',
                'PayPal Sandbox Key',
                array($this, 'paypalSandboxCallback'),
                'cso-suite-setting-admin',
                'setting_section_id'
            );

            add_settings_field(
                'paypal_production',
                'PayPal Production Key',
                array($this, 'paypalProductionCallback'),
                'cso-suite-setting-admin',
                'setting_section_id'
            );

            add_settings_field(
                'dues_amount',
                'Dues Amount',
                array($this, 'duesAmountCallback'),
                'cso-suite-setting-admin',
                'setting_section_id'
            );

            add_settings_field(
                'waiver_page',
                'Waiver Page',
                array($this, 'waiverPageCallback'),
                'cso-suite-setting-admin',
                'setting_section_id'
            );

            add_settings_field(
                'confirmation_page',
                'Confirmation Page',
                array($this, 'confirmationPageCallback'),
                'cso-suite-setting-admin',
                'setting_section_id'
            );

//        add_settings_field(
//            'paypal_amounts',
//            'Additional Charges',
//            array( $this, 'paypalAmountsCallback' ),
//            'cso-suite-setting-admin',
//            'setting_section_id'
//        );
        }

        /**
         * Sanitize each setting field as needed
         *
         * @param array $input Contains all settings fields as array keys
         */
        public function sanitize($input)
        {
            $new_input = array();
            if (isset($input['api_uri'])) {
                $new_input['api_uri'] = sanitize_text_field($input['api_uri']);
            }

            if (isset($input['paypal_sandbox'])) {
                $new_input['paypal_sandbox'] = sanitize_text_field($input['paypal_sandbox']);
            }

            if (isset($input['paypal_production'])) {
                $new_input['paypal_production'] = sanitize_text_field($input['paypal_production']);
            }

            if (isset($input['dues_amount'])) {
                $new_input['dues_amount'] = $input['dues_amount'];
            }

            if (isset($input['waiver_page'])) {
                $new_input['waiver_page'] = $input['waiver_page'];
            }

            if (isset($input['confirmation_page'])) {
                $new_input['confirmation_page'] = $input['confirmation_page'];
            }

//        if (isset($input['paypal_amounts'])) {
//            $new_input['paypal_amounts'] = $input['paypal_amounts'];
//        }

            return $new_input;
        }

        /**
         * Print the Section text
         */
        public function printSectionInfo()
        {
            print 'Enter your settings below:';
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function apiUriCallback()
        {
            printf(
                '<input type="text" style="width: 25rem;" id="api_uri" name="cso_suite_option_name[api_uri]" value="%s" />',
                isset($this->options['api_uri']) ? esc_attr($this->options['api_uri']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function paypalSandboxCallback()
        {
            printf(
                '<input type="text" style="width: 25rem;" id="paypal_sandbox" name="cso_suite_option_name[paypal_sandbox]" value="%s" />',
                isset($this->options['paypal_sandbox']) ? esc_attr($this->options['paypal_sandbox']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function paypalProductionCallback()
        {
            printf(
                '<input type="text" style="width: 25rem;" id="paypal_production" name="cso_suite_option_name[paypal_production]" value="%s" />',
                isset($this->options['paypal_production']) ? esc_attr($this->options['paypal_production']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function duesAmountCallback()
        {
            printf(
                '<input type="text" style="width: 5rem;" id="dues_amount" name="cso_suite_option_name[dues_amount]" value="%s" />',
                isset($this->options['dues_amount']) ? esc_attr($this->options['dues_amount']) : ''
            );
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function waiverPageCallback()
        {
            $pages = $this->getPages();
            $waiverPage = isset($this->options['waiver_page']) ? esc_attr($this->options['waiver_page']) : '';

            $html = '<select style="width: 15rem;" id="waiver_page" name="cso_suite_option_name[waiver_page]">';
            $html .= '<option value=""> -- Select --</option >';
            foreach ($pages as $slug => $page) {
                $selected = ($slug == $waiverPage) ? ' selected' : '';
                $html .= '<option value="' . $slug . '"' . $selected . '>' . $page . '</option >';
            }
            $html .= '</select >';

            printf($html);
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function confirmationPageCallback()
        {
            $pages = $this->getPages();
            $confirmationPage = isset($this->options['confirmation_page']) ? esc_attr($this->options['confirmation_page']) : '';

            $html = '<select style="width: 15rem;" id="confirmation_page" name="cso_suite_option_name[confirmation_page]">';
            $html .= '<option value=""> -- Select --</option >';
            foreach ($pages as $slug => $page) {
                $selected = ($slug == $confirmationPage) ? ' selected' : '';
                $html .= '<option value="' . $slug . '"' . $selected . '>' . $page . '</option >';
            }
            $html .= '</select >';

            printf($html);
        }

        /**
         * Get the settings option array and print one of its values
         */
        public function paypalAmountsCallback()
        {
            printf(
                '<div>Enter each amount on a new line in the format: Description: 20.00</div>
            <textarea id="paypal_amounts" name="cso_suite_option_name[paypal_amounts]" style="width: 25rem; height: 10rem;">%s</textarea>',
                isset($this->options['paypal_amounts']) ? esc_attr($this->options['paypal_amounts']) : ''
            );
        }

        protected function getPages()
        {
            $args = array(
                'sort_order' => 'asc',
                'sort_column' => 'post_title',
                'hierarchical' => 1,
                'exclude' => '',
                'include' => '',
                'meta_key' => '',
                'meta_value' => '',
                'authors' => '',
                'child_of' => 0,
                'parent' => -1,
                'exclude_tree' => '',
                'number' => '',
                'offset' => 0,
                'post_type' => 'page',
                'post_status' => 'publish'
            );
            $pages = get_pages($args);

            $pageSelect = [];
            foreach ($pages as $page) {
                $pageSelect[$page->post_name] = $page->post_title;
            }
            return $pageSelect;
        }
    }
}