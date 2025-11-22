<?php
namespace quizaccess_faceid\rule;

use mod_quiz\access_rule_base;
use moodle_page;

defined('MOODLE_INTERNAL') || die();

/**
 * Reglas de acceso para FaceID.
 */
class quizaccess_faceid extends access_rule_base {

    public static function make(\quiz $quizobj, $timenow, $canignoretimelimits) {
        global $DB;

        $faceid = $DB->get_record('quizaccess_faceid', ['quizid' => $quizobj->get_quizid()]);
        if ($faceid && $faceid->enabled) {
            return new self($quizobj, $timenow);
        }
        return null;
    }

    public function prevent_access() {
        global $SESSION;
        $key = 'faceid_verified_' . $this->quiz->id;
        if (!isset($SESSION->$key)) {
            return get_string('mustverifyface', 'quizaccess_faceid');
        }
        return false;
    }

    public function setup_attempt_page(moodle_page $page) {
        global $USER, $CFG;

        // Get server configuration from admin settings
        $server_url = get_config('quizaccess_faceid', 'server_url');
        if (empty($server_url)) {
            $server_url = 'http://127.0.0.1:5001'; // Fallback default
        }
        $server_url = rtrim($server_url, '/');
        $endpoint = $server_url . '/verify';

        $page->requires->js_call_amd('quizaccess_faceid/faceid', 'init', [
            [
                'userid' => $USER->id,
                'quizid' => $this->quiz->id,
                'endpoint' => $endpoint,
                'wwwroot' => $CFG->wwwroot
            ]
        ]);
    }
}
