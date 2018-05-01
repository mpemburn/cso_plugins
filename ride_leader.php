<?php

include 'roster_api.php';

if (!class_exists('RideLeader')) {
    class RideLeader
    {
        protected $rosterApi;
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

            $instance->loadListing();
            $instance->enqueueAssets();

            add_action('init', array($instance, 'registerShortcodes'));
            // Add action to register WP API endpoint
            //add_action('rest_api_init', [$instance, 'registerReceiveMessageRoute']);
            // Set up AJAX handlers
            add_action('wp_ajax_ride_leader_add_guest', [$instance, 'addGuest']);
            add_action('wp_ajax_nopriv_ride_leader_add_guest', [$instance, 'addGuest']);

            //$instance->sendText();
        }

        public function registerReceiveMessageRoute()
        {
            register_rest_route('rideleader/v2', '/receive_sms', array(
                'methods' => 'POST',
                'callback' => [$this, 'triggerReceiveSms'],
            ));
        }

        public function triggerReceiveSms()
        {
            $phone = $_POST['From'];

            $memberName = $this->verifyMember($phone);
            if ($memberName !== false) {
                $message = 'Hello ' . $memberName . '!' . PHP_EOL;
                $message .= 'Tap below to go to the Ride Leader query page:' . PHP_EOL;
                $message .= get_home_url() . '/ride-leader-service';
            } else {
                $message = 'Sorry, your number was not recognized by our system.';
            }
            echo header('content-type: text/xml');
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<Response>';
            $xml .= '<Message>';
            $xml .= $message;
            $xml .= '</Message>';
            $xml .= '</Response>';

            echo $xml;

            die();
        }

        /**
         *
         */
        public function enqueueAssets()
        {
            $version = '1.09';
            wp_enqueue_style('jquery-ui' . 'http://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css');
            wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
            wp_enqueue_style('typeahead', plugin_dir_url(__FILE__) . 'css/jquery.typeahead.css', '', $version);
            wp_enqueue_style('ride_leader', plugin_dir_url(__FILE__) . 'css/ride_leader.css', '', $version);

            wp_enqueue_script('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js');
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
            $this->loadListing();

            ob_start();

            include 'leader_form.php';

            $output = ob_get_clean();

            return $output;
        }

        public function loadListing()
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

        protected function makeApiCall($action, $url, $data = [])
        {
            $response = null;

            // TODO: Future security enhancement
            $username = 'your-username';
            $password = 'your-password';
            $headers = array('Authorization' => 'Basic ' . base64_encode("$username:$password"));
            if ($action == 'GET') {
                $response = wp_remote_get($url, [
                    'headers' => $headers,
                    'sslverify' => false
                ]);
            }
            if ($action == 'POST') {
                $response = wp_remote_post($url, [
                    'headers' => $headers,
                    'body' => $data,
                    'sslverify' => false,
                    'timeout' => 45,
                ]);
            }

            return $response;
        }

    }
}
