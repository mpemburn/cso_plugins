<?php

include 'admin_settings.php';

if (!class_exists('JoinCso')) {
    class JoinCso
    {

        protected $rosterApi;
        protected $paypalSandboxKey;
        protected $paypalProductionKey;
        protected $paypalAmounts;
        protected $duesAmount;
        protected $waiverPage;
        protected $confirmationPage;
        protected $devMode = false;
        protected $devApiUrl = 'https://cso_roster.test/api';

        public static function register()
        {
            $instance = new self;

            $instance->loadSettings();
            $instance->rosterApi = new RosterAPI();

            $instance->enqueueAssets();

            add_action('init', array($instance, 'registerShortcodes'));
            // Set up AJAX handlers
            add_action('wp_ajax_join_cso_verify', [$instance, 'verifyMemberInRoster']);
            add_action('wp_ajax_join_cso_update', [$instance, 'updateMember']);
            add_action('wp_ajax_nopriv_join_cso_verify', [$instance, 'verifyMemberInRoster']);
            add_action('wp_ajax_nopriv_join_cso_update', [$instance, 'updateMember']);
        }

        private function __construct()
        {
        }

        public function verifyMemberInRoster()
        {
            $post = $_POST['data'];

            // Parse query string into array
            parse_str($post, $data);
            $data['email'] = $data['member_email'];
            $data['zip'] = $data['member_zip'];

            $this->loadSettings();

            $url = '/member/verify/' . $data['email'] . '/' . $data['zip'];
            $response = $this->rosterApi->makeApiCall('GET', $url);

            $success = $this->getResponseSuccess($response);

            wp_send_json([
                'success' => $success,
                'action' => 'verify',
                'message' => $this->getVerifyMessage($success),
                'data' => $response,
            ]);

            die();
        }

        /**
         *
         */
        public function enqueueAssets()
        {
            $version = '1.2';
            wp_enqueue_style('jquery-ui' . 'http://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css');
            wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
            wp_enqueue_style('join_cso', plugin_dir_url(__FILE__) . 'css/join_cso.css', '', $version);

            wp_enqueue_script('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script('paypal', 'https://www.paypalobjects.com/api/checkout.js');
            wp_register_script('validate', plugin_dir_url(__FILE__) . 'js/validate.js', '', $version, true);
            wp_enqueue_script('validate');
            wp_enqueue_style('wp-jquery-ui-dialog');
            wp_register_script('join_cso', plugin_dir_url(__FILE__) . 'js/join_cso.js', '', $version, true);
            wp_enqueue_script('join_cso');
            wp_register_script('paypal_join', plugin_dir_url(__FILE__) . 'js/paypal_join.js', '', $version, true);
            wp_enqueue_script('paypal_join');

            wp_register_script('ajax-js', null);
            wp_localize_script('ajax-js', 'joinNamespace', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'paypalSandboxKey' => $this->paypalSandboxKey,
                'paypalProductionKey' => $this->paypalProductionKey,
                'duesAmount' => $this->duesAmount,
                'confirmationPage' => '/' . $this->confirmationPage
            ]);
            wp_enqueue_script('ajax-js');
        }

        public function memberFormHandler($att, $content)
        {
            $states = ['DC', 'DE', 'MD', 'NJ', 'NY', 'PA', 'VA'];
            $dues = $this->duesAmount;
            $payments = $this->getPaypalAmounts();
            $legal = $this->getLegalContent();
            $process_type = 'new_member';

            ob_start();
            include 'join_form.php';
            $output = ob_get_clean();

            return $output;
        }

        public function registerShortcodes()
        {
            add_shortcode('memberform', array($this, 'memberFormHandler'));
        }

        public function updateMember()
        {
            $data = $_POST['data'];
            parse_str($data, $parsed);

            $this->loadSettings();
            $url = ($parsed['process_type'] == 'new_member') ? '/member/join' : '/member/payment';

            $response = $this->rosterApi->makeApiCall('POST', $url, $parsed);
            $success = $this->getResponseSuccess($response);

            wp_send_json([
                'success' => $success,
                'action' => 'update',
                //'url' => $url,
                'data' => $response
            ]);

            die();
        }

        protected function getVerifyMessage($success)
        {
            if ($success) {
                $message = 'Thanks! Please continue your renewal process by clicking the <strong>PayPal</strong> button.';
            } else {
                $message = 'Sorry! We were unable to find a member with that email address and zip code in our database.
                You may have used a different email address to sign up originally.
                Please try again, or contact our <a href="/contact">Club Secretary</a>';
            }

            return $message;
        }

        protected function getPageIdFromSlug($slug)
        {
            $query = new WP_Query(
                array(
                    'name' => $slug,
                    'post_type' => 'page',
                    'numberposts' => 1,
                    'fields' => 'ids',
                ));
            $posts = $query->get_posts();
            $pageId = array_shift($posts);

            return $pageId;
        }

        protected function getPaypalAmounts()
        {
            $amounts = [];

            if (!empty($this->paypalAmounts)) {
                $lines = explode(PHP_EOL, $this->paypalAmounts);
                foreach ($lines as $line) {
                    $lineParts = explode(':', $line);
                    $label = trim($lineParts[0]);
                    $amount = trim($lineParts[1]);
                    if (stristr($label, 'dues') !== false) {
                        $this->duesAmount = $amount;
                    }

                    $amounts[$label] = $amount;
                }
            }

            return $amounts;
        }

        protected function getLegalContent()
        {
            global $post;

            $waiverPageId = $this->getPageIdFromSlug($this->waiverPage);

            if ($waiverPageId == $post->ID) {
                return 'An error has occurred on this page: Waiver Page is not set correctly in Settings>Rostejoin. Must not be set to a page containing the [memberform] shortcode.';
            }
            $contentPost = get_post($waiverPageId);
            $content = $contentPost->post_content;
            $content = apply_filters('the_content', $content);
            $content = str_replace(']]>', ']]&gt;', $content);

            return $content;
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
                $this->paypalSandboxKey = $settings->paypal_sandbox;
                $this->paypalProductionKey = $settings->paypal_production;
                $this->duesAmount = $settings->dues_amount;
                $this->waiverPage = $settings->waiver_page;
                $this->confirmationPage = $settings->confirmation_page;
                $this->paypalAmounts = $settings->paypal_amounts;

            }
        }

    }
}