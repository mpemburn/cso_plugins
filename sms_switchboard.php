<?php

include 'admin_settings.php';
include 'crypto.php';


if (!class_exists('SmsSwitchboard')) {
    class SmsSwitchboard
    {

        protected $rosterApi;
        protected $memberList;

        protected function __construct()
        {
        }

        public static function register()
        {
            $instance = new self;
            $instance->rosterApi = new RosterAPI();
            //$instance->loadSettings();
            add_action('rest_api_init', [$instance, 'registerReceiveMessageRoute']);
            add_filter('wp_mail_from', [$instance, 'setMailFrom']);
        }

        public function registerReceiveMessageRoute()
        {
            register_rest_route('rideleader/v2', '/receive_sms', array(
                'methods' => 'POST',
                'callback' => [$this, 'triggerReceiveSms'],
            ));
        }

        public function my_mail_from($email)
        {
            return 'noreply@chesapeakespokesclub.org';
        }

        public function triggerReceiveSms()
        {
            $phone = $_POST['From'];
            $incomingMessage = $_POST['Body'];

            $memberData = $this->verifyMember($phone);
            if ($memberData !== false) {
                $replyMessage = $this->switchboard($phone, strtolower(trim($incomingMessage)), $memberData);
            } else {
                $replyMessage = 'Sorry, your number was not recognized by our system.';
            }

            echo header('content-type: text/xml');
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<Response>';
            $xml .= '<Message>';
            $xml .= $replyMessage;
            $xml .= '</Message>';
            $xml .= '</Response>';

            echo $xml;

            die();
        }

        public function verifyMember($phone)
        {
            $url = '/member/verify/' . $phone;
            $response = $this->rosterApi->makeApiCall('GET', $url);

            if (strstr($response['body'], '{"success"') !== 'false') {
                $memberData = json_decode($response['body']);
                if ($memberData->success) {
                    return $memberData->data;
                }
            }

            return false;
        }

        protected function loadSettings()
        {
            $option = get_option('cso_suite_option_name');

            $settings = (!empty($option)) ? (object)$option : null;

            if (!is_null($settings)) {
            }

        }

        protected function sendEmailtoMember($member, $subject, $message)
        {
            $message = str_replace(PHP_EOL, '<br/>', $message);

            wp_mail($member->email, $subject, $message);
        }

        protected function switchboard($phone, $message, $member)
        {
            $replyMessage = '';

            switch ($message) {
                case 'leader':
                    $replyMessage = 'Hello ' . $member->first_name . '!' . PHP_EOL;
                    $replyMessage .= 'Tap below to go to the Ride Leader query page:' . PHP_EOL;
                    $replyMessage .= get_home_url() . '/ride-leader-service';
                    break;
                case 'vote':
                    $key = md5('Baloney');
                    $cipher = Crypto::encrypt($phone, $key, true);

                    $electionUrl = get_home_url() . '/election-2018?x=' . $cipher;
                    $replyMessage .= 'Click or Tap the link below to go to the Election page:' . PHP_EOL;
                    $replyMessage .= $electionUrl . PHP_EOL;

                    $this->sendEmailtoMember($member, 'Here is your link to vote in the 2018 election', $replyMessage);
                    break;
                default:
                    break;
            }

            return $replyMessage;
        }

    }
}
