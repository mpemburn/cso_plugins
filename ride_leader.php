<?php

include 'roster_api.php';

if (!class_exists('RideLeader')) {
    class RideLeader
    {
        /** @var \RosterAPI $rosterApi */
        protected $rosterApi;
        protected $assetVersion = '1.4';
        protected $memberList;
        protected $devMode = false;
        protected $devApiUrl = 'https://cso_roster.test/api';
        protected $twilioPhone = '+12407135695';
        protected $twilioSid = 'AC74333bbe7cf6359037ca98d27c645299';
        protected $twilioAuthToken = 'eb57a58963ac5ac4c87f49103d8b374c';

        protected function __construct()
        {
        }

        public static function register()
        {
            $instance = new self;
            $instance->loadSettings();
            $instance->rosterApi = new RosterAPI();

            $instance->loadMemberList();
            $instance->enqueueAssets();

            add_action('init', array($instance, 'registerShortcodes'));
            // Add action to register WP API endpoint
            //add_action('rest_api_init', [$instance, 'registerReceiveMessageRoute']);
            // Set up AJAX handlers
            add_action('wp_ajax_ride_leader_add_guest', [$instance, 'addGuest']);
            add_action('wp_ajax_nopriv_ride_leader_add_guest', [$instance, 'addGuest']);

            //$instance->sendText();
        }

        /**
         *
         */
        public function enqueueAssets()
        {
            $version = $this->assetVersion;

            wp_enqueue_style('typeahead', plugin_dir_url(__FILE__) . 'css/jquery.typeahead.css', '', $version);
            wp_enqueue_style('ride_leader', plugin_dir_url(__FILE__) . 'css/ride_leader.css', '', $version);

            wp_register_script('typeahead', plugin_dir_url(__FILE__) . 'js/jquery.typeahead.js', '', $version, true);
            wp_register_script('ride_leader', plugin_dir_url(__FILE__) . 'js/ride_leader.js', '', $version, true);
            wp_enqueue_script('ride_leader');
            wp_enqueue_script('typeahead');

            wp_register_script('ajax-js', null);
            wp_localize_script('ajax-js', 'leaderNamespace', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'memberList' => json_decode($this->memberList),
            ]);
            wp_enqueue_script('ajax-js');
        }

        public function leaderFormHandler($att, $content)
        {
            $this->loadMemberList();

            ob_start();

            include 'leader_form.php';

            $output = ob_get_clean();

            return $output;
        }

        public function loadMemberList()
        {
            $url = '/member/list';
            $response = $this->rosterApi->makeApiCall('GET', $url);

            $this->memberList = $response['body'];
        }

        public function verifyMember($phone)
        {
            $url = '/member/verify/' . $phone;
            $response = $this->rosterApi->makeApiCall('GET', $url);

            if (strstr($response['body'], '{"success"') !== 'false') {
                $memberData = json_decode($response['body']);
                if ($memberData->success) {
                    return $memberData->data->first_name;
                }
            }

            return false;
        }

        public function registerShortcodes()
        {
            add_shortcode('ride-leader', array($this, 'leaderFormHandler'));
        }

        public function addGuest()
        {
            $data = $_POST['data'];
            parse_str($data, $parsed);

            $url = '/guest/add';

            $response = $this->rosterApi->makeApiCall('POST', $url, $parsed);
            $success = $this->getResponseSuccess($response);

            wp_send_json([
                'success' => $success,
                'action' => 'update',
                'data' => $response
            ]);

            die();
        }

        protected function getResponseSuccess($response)
        {
            $success = false;

            $is200 = (isset($response['response'])) ? ($response['response']['code'] == 200) : false;
            if ($is200) {
                $success = (isset($response['body'])) ? json_decode($response['body'])->success : false;
            }

            return $success;
        }

        protected function loadSettings()
        {
            $option = get_option('roster_option_name');

            $settings = (!empty($option)) ? (object)$option : null;

            if (!is_null($settings)) {
                $this->apiUrl = (!$this->devMode) ? $settings->api_uri : $this->devApiUrl;
            }

        }
    }
}
