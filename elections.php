<?php

include 'elections_post_type.php';
include 'roster_api.php';
include 'crypto.php';

if (!class_exists('CsoElections')) {
    class CsoElections
    {
        /** @var RosterAPI $rosterApi */
        protected $rosterApi;
        protected $assetVersion = '1.06';
        protected $memberList;
        protected $membersLoaded = false;
        protected $officeCount = 0;
        protected $raceData = [];
        /** @var ElectionsPosts $electionsPosts */
        protected $electionsPosts;
        protected $block = false;
        protected $errorMessage;

        public static function register()
        {
            $instance = new self;
            $instance->rosterApi = new RosterAPI();

            $instance->loadSettings();

            $instance->enqueueAssets();
            add_action('init', array($instance, 'registerShortcodes'));
            // Register elections post type
            $instance->electionsPosts = new ElectionsPosts();

            // Set up AJAX handlers
            add_action('wp_ajax_cso_elections', [$instance, 'registerVote']);
            add_action('wp_ajax_nopriv_cso_elections', [$instance, 'registerVote']);
        }

        public function registerShortcodes()
        {
            add_shortcode('cso_elections', array($this, 'electionShortcodeHandler'));
        }

        public function electionShortcodeHandler($att, $content)
        {
            if ($this->block) {
                return '';
            }

            global $post;

            $postId = $post->ID;
            $html = '';
            $officeKey = '';

            if (isset($att['start'])) {
                $hash = $this->getHash();
                $eligible = $this->testForElegibility($postId, $hash);
                if (!$eligible) {
                    $this->block = true;
                    return '<h4>' . $this->errorMessage . '</h4>';
                }
                $html = $this->buildFormHead($hash, $postId);
            }

            if (isset($att['date'])) {
                $this->setElectionMeta($att, $postId);
            }

            if (isset($att['office'])) {
                $office = $att['office'];
                $officeKey = strtolower(str_replace(' ', '_', $office));
                $officeKey = preg_replace("/[^A-Za-z0-9 \-\_]/", '', $officeKey);

                $html = $this->buildRace($office, $officeKey);

                $this->officeCount++;
            }

            if (isset($att['candidates'])) {
                $choices = $this->buildCandidates($att['candidates'], $officeKey, $postId);
                $html = str_replace('~~~', $choices, $html);

                $this->setElectionMeta($att, $postId);
            }

            if (isset($att['end'])) {
                $html = $this->buildFormTail();
            }

            return $html;
        }

        protected function loadSettings()
        {
//        $option = get_option('roster_option_name');
//
//        $settings = (!empty($option)) ? (object)$option : null;
//
//        if (!is_null($settings)) {
//            $this->apiUrl = (!$this->devMode) ? $settings->api_uri : $this->devApiUrl;
//        }

        }

        public function loadMemberList()
        {

            $url = '/member/list';
            $response = $this->rosterApi->makeApiCall('GET', $url);

            $this->memberList = $response['body'];
            // Add JS and CSS assets
            $this->enqueueWriteIns();

            $this->membersLoaded = true;
        }

        protected function getHash()
        {
            $request = $_GET;
            $hash = (isset($request['x'])) ? $request['x'] : null;

            return $hash;
        }

        protected function getElectionDateStamp($postId)
        {
            $electionDate = get_post_meta($postId, 'election_date');

            return (!empty($electionDate)) ? array_pop($electionDate) : null;
        }

        protected function verifyHash($hash)
        {
            $phone = Crypto::decrypt($hash);

            $url = '/member/verify/' . $phone;
            $response = $this->rosterApi->makeApiCall('GET', $url);

            if ($response instanceof WP_Error) {
                return false;
            }

            if (strstr($response['body'], '{"success"') !== 'false') {
                $memberData = json_decode($response['body']);
                if ($memberData->success) {
                    return true;
                }
            }

            return false;
        }

        protected function setElectionMeta($attributes, $postId)
        {
            $electionDate = $this->getElectionDateStamp($postId);
            $date = (isset($attributes['date'])) ? $attributes['date'] : null;
            if (empty($electionDate) && !is_null($date)) {
                update_post_meta($postId, 'election_date', strtotime($date));
            }

            $electionData = get_post_meta($postId, 'elections');
            $candidates = (isset($attributes['candidates'])) ? $attributes['candidates'] : null;
            if (empty($electionData) && !is_null($candidates)) {
                // Add the data to the post meta
                update_post_meta($postId, 'elections', $this->raceData);
            }
        }

        protected function buildRace($office, $officeKey)
        {
            $html = '<div class="cso-election" data-office="' . $office . '">';
            $html .= '<input type="hidden" class="required" id="vote_' . $officeKey . '" name="vote_' . $officeKey . '" value=""/>';
            $html .= '<h4>' . $office . '</h4>';
            $html .= '~~~';
            $html .= '</div>';

            return $html;
        }

        protected function buildCandidates($candidatesAttribute, $officeKey, $postId)
        {
            $candidates = explode(',', $candidatesAttribute);
            $choices = '';
            foreach ($candidates as $candidate) {
                $parts = explode(':', $candidate);
                $value = trim($parts[0]);
                $name = trim($parts[1]);
                if (strtolower($name) == 'write-in') {
                    $choice = $this->buildWriteInList($value, $officeKey);
                    // Load the member list 'cuz we're gonna need it
                    if (!$this->membersLoaded) {
                        $this->loadMemberList();
                    }
                } else {
                    $choice = '<label>';
                    $choice .= '<input type="radio"
                                    name="' . $officeKey . '" 
                                    data-key="' . $officeKey . '" 
                                    data-name="' . $name . '" 
                                    value="' . $value . '"/>' . $name;
                    $choice .= '</label>';

                }
                $choices .= $choice;
                // Save the race data
                $this->raceData[$officeKey][$value] = $name;
            }

            return $choices;
        }

        public function buildWriteInList($value, $officeKey)
        {
            $html = '<div class="typeahead__container">';
            $html .= '<label>';
            $html .= '<input type="radio" 
                    name="' . $officeKey . '" 
                    data-key="' . $officeKey . '"
                    value="' . $value . '" 
                    data-type="write-in"/>';
            $html .= 'Write In:';
            $html .= '</label>';
            $html .= '<div class="typeahead__field">';
            $html .= '<span class="typeahead__query">';
            $html .= '<input class="js-typeahead" id="write_in_' . $officeKey . '"
                       name="write_in_' . $officeKey . '"
                       type="search"
                       style="display: none;"
                       data-key="' . $officeKey . '"
                       autocomplete="off">';
            $html .= '<div id="must_be_' . $officeKey . '" style="display: none;">Write-ins must be active members.</div>';
            $html .= '</span>';
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        }

        protected function buildFormHead($hash, $postId)
        {
            if (!empty($hash)) {
                $html = '<div id="election_container" class="col-md-10">';
                $html .= '<form id="cso_election">';
                $html .= '<input type="hidden" id="post_id" name="post_id" value="' . $postId . '">';
                $html .= '<input type="hidden" id="hash" name="hash" value="' . $hash . '">';

                return $html;
            }

            return false;
        }

        protected function buildFormTail()
        {
            $html = '    </form>';
            $html .= '    <div class="col-md-12 field-wrapper text-right">';
            $html .= '        <button class="btn btn-primary  btn-lg" id="vote_button" name="vote_button">Vote</button>';
            $html .= '        <span id="vote_spinner" class="vote_spinner"></span>';
            $html .= '        <div id="verify_message" class=" text-left" style="display: none;">Please make your choices (above) before voting.</div>';
            $html .= '    </div>';
            $html .= '</div>';

            return $html;
        }

        public function registerVote()
        {
            $data = $_POST['data'];
            parse_str($data, $voteData);

            $postId = $voteData['post_id'];
            $hash = $voteData['hash'];
            $electionData = get_post_meta($postId, 'elections');
            unset($voteData['post_id']);
            unset($voteData['hash']);

            $electionDate = $this->getElectionDateStamp($postId);

            $success = $this->electionsPosts->recordVote($voteData, array_pop($electionData), $electionDate, $hash);

            wp_send_json([
                'success' => $success,
                'redirect' => '/vote-success',
                'errorMessage' => $this->electionsPosts->getErrorMessage()
            ]);
        }

        protected function testForElegibility($postId, $hash)
        {
            $verified = $this->verifyHash($hash);
            $electionDate = $this->getElectionDateStamp($postId);
            $alreadyVoted = $this->electionsPosts->hasAlreadyVoted($electionDate, $hash);
            if (!$verified) {
                $this->errorMessage = 'This content is not available.';
                return false;
            }
            if ($alreadyVoted) {
                $this->errorMessage = $this->electionsPosts->getErrorMessage();
                return false;
            }

            return true;
        }

        protected function enqueueAssets()
        {
            $version = $this->assetVersion;

            wp_enqueue_style('election_typeahead', plugin_dir_url(__FILE__) . 'css/jquery.typeahead.css', '', $version);
            wp_enqueue_style('cso_election', plugin_dir_url(__FILE__) . 'css/elections.css', '', $version);

            wp_register_script('election_typeahead', plugin_dir_url(__FILE__) . 'js/jquery.typeahead.js', '', $version, true);
            wp_enqueue_script('election_typeahead');
            wp_register_script('validate', plugin_dir_url(__FILE__) . 'js/validate.js', '', $version, true);
            wp_enqueue_script('validate');
            wp_register_script('cso_election', plugin_dir_url(__FILE__) . 'js/elections.js', '', $version, true);
            wp_enqueue_script('cso_election');

        }

        protected function enqueueWriteIns()
        {
            $version = $this->assetVersion;

            wp_register_script('election-ajax-js', null);
            wp_localize_script('election-ajax-js', 'electionNamespace', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'memberList' => json_decode($this->memberList),
            ]);
            wp_enqueue_script('election-ajax-js');

        }
    }
}