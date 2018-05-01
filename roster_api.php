<?php

if (!class_exists('RosterAPI')) {

    class RosterAPI
    {
        protected $apiUrl;
        protected $paypalSandboxKey;
        protected $paypalProductionKey;
        protected $paypalAmounts;
        protected $duesAmount;
        protected $waiverPage;
        protected $confirmationPage;
        protected $devMode = false;
        protected $devApiUrl = 'https://cso_roster.test/api';

        public function __construct()
        {
        }

        public function makeApiCall($action, $endpoint, $data = [])
        {
            $response = null;
            $this->loadSettings();

            $url = $this->apiUrl . $endpoint;

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