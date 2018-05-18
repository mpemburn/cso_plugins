<?php

include 'crypto.php';

if (!class_exists('ElectionsPosts')) {
    class ElectionsPosts
    {
        protected $errorMessage;

        public function __construct()
        {
            add_action('init', array($this, 'registerPostType'));
        }

        public function recordVote($voteData, $electionData, $electionDate, $hash)
        {
            if ($this->hasAlreadyVoted($electionDate, $hash)) {
                return false;
            }

            $success = true;

            foreach ($voteData as $office => $vote) {
                // Ignore all fields unless they begin with 'vote_'. (these are hidden fields)
                if (substr($office, 0, 5) !== "vote_") {
                    continue;
                }
                // Chop off 'vote_' prefix
                $office = str_replace('vote_', '', $office);
                // Store vote.  Title is office and timestamp; Content is candidate voted for.
                $postId = wp_insert_post(array(
                    'post_type' => 'elections',
                    'post_title' => $office . ';' . time(),
                    'post_content' => $vote,
                    'post_status' => 'publish',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                ));
                if ($postId == 0 || $postId instanceof WP_Error) {
                    $success = false;
                } else {
                    // Add post meta to store election date at hash
                    update_post_meta($postId, 'election_date', $electionDate);
                    update_post_meta($postId, 'hash', $hash);
                }
            }

            return $success;
        }

        public function registerPostType()
        {
            $labels = [
                'name' => _x('Election', 'Post Type General Name', 'twentyseventeen'),
                'singular' => _x('Election', 'Post Type Singular Name', 'twentyseventeen'),
                'plural' => __('Elections', 'twentyseventeen')
            ];
            $postTypeArgs = [
                'label' => __('elections', 'twentyseventeen'),
                'description' => __('Election', 'twentyseventeen'),
                'labels' => $labels,
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_rest' => true,
                'show_in_menu' => true,
                'menu_position' => 5,
                'show_in_admin_bar' => true,
                'show_in_nav_menus' => true,
                'can_export' => true,
                'has_archive' => true,
                'exclude_from_search' => false,
                'publicly_queryable' => false,
                'rewrite' => array('slug' => 'election'),
                'capability_type' => 'post',
            ];

            register_post_type('elections', $postTypeArgs);
        }

        public function getErrorMessage()
        {
            return $this->errorMessage;
        }

        public function getVotesByDate($electionDate)
        {
            $votes = new WP_Query(array(
                'post_type' => 'elections',
                'meta_query' => array(
                    array(
                        'key' => 'election_date',
                        'value' => $electionDate,
                        'compare' => '='
                    ),
                )
            ));

            return $votes;
        }

        public function getVotesForElection($electionDate)
        {
            $votes = $this->getVotesByDate($electionDate);

            $tally = [];
            foreach ($votes->posts as $vote) {
                $postId = $vote->ID;
                $race = explode(';', $vote->post_title)[0];

                $tally[$race][] = $vote->post_content;
            }

            return $tally;
        }

        public function getTally($electionDate)
        {
            $races = $this->getVotesForElection($electionDate);

            $tally = [];

            foreach ($races as $officeKey => $race) {
                $tally[$officeKey] = [
                    'race' => $officeKey,
                    'results' => array_count_values($race)
                ];
            }

            return $tally;
        }

        public function hasAlreadyVoted($electionDate, $hash)
        {
            $hasVoted = false;

            $election = $this->getVotesByDate($electionDate);
            foreach ($election->posts as $vote) {
                $postId = $vote->ID;
                $savedHash = get_post_meta($postId, 'hash')[0];

                if ($this->compareHashes($savedHash, $hash)) {
                    $hasVoted = true;
                }
            }

            if ($hasVoted) {
                $this->errorMessage = 'You have already cast your ballot for this election';
            }

            return $hasVoted;
        }

        protected function compareHashes($savedHash, $testHash)
        {
            $foundPhone = Crypto::decrypt($savedHash);
            $testPhone = Crypto::decrypt($testHash);

            return ($foundPhone == $testPhone);
        }
    }
}
