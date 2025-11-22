<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/faceid/classes/profile_helper.php');

/**
 * Adds links to user profile navigation
 *
 * @param \navigation_node $navigation The navigation node to extend
 * @param stdClass $user The user object
 * @param context_user $usercontext The context of the user
 * @param stdClass $course The course object
 * @param context_course $coursecontext The context of the course
 * @return void
 */
function quizaccess_faceid_extend_navigation_user($navigation, $user, $usercontext, $course, $coursecontext) {
    global $USER, $PAGE;
    
    // Only add for own profile or if admin
    if ($user->id == $USER->id || has_capability('moodle/user:update', context_system::instance())) {
        $url = new moodle_url('/mod/quiz/accessrule/faceid/profile_verification.php', array('id' => $user->id));
        $navigation->add(
            get_string('profileverification', 'quizaccess_faceid'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'faceid_verification'
        );
    }
}

/**
 * Callback to inject HTML into user profile page
 */
function quizaccess_faceid_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $CFG, $USER;
    
    // Only show for current user or if admin
    if ($iscurrentuser || has_capability('moodle/user:update', context_system::instance())) {
        $helper = new \quizaccess_faceid\profile_helper();
        $profile = $helper->get_profile_verification($user->id);
        
        $status = get_string('not_verified', 'quizaccess_faceid');
        $statusclass = 'text-danger';
        
        if ($profile && $profile->verified) {
            $status = get_string('verified', 'quizaccess_faceid');
            $statusclass = 'text-success';
        }
        
        $content = html_writer::span($status, $statusclass);
        
        if ($profile && $profile->verification_score > 0) {
            $content .= html_writer::div(
                'Score: ' . number_format($profile->verification_score, 3),
                'small text-muted'
            );
        }
        
        $url = new moodle_url('/mod/quiz/accessrule/faceid/profile_verification.php', array('id' => $user->id));
        $content .= html_writer::div(
            html_writer::link($url, get_string('verifyprofile', 'quizaccess_faceid'), 
                array('class' => 'btn btn-sm btn-outline-primary mt-1')),
            'mt-2'
        );
        
        $node = new \core_user\output\myprofile\node('contact', 'faceid_verification',
            get_string('profileverification', 'quizaccess_faceid'), null, null, $content);
        
        $tree->add_node($node);
    }
}

/**
 * Devuelve la clase de regla de acceso implementada por este plugin.
 *
 * @return array
 */
function quizaccess_faceid_get_rule_classes() {
    return ['quizaccess_faceid'];
}

/**
 * Inyecta el JavaScript y configuración necesaria para la verificación facial.
 * NOTA: Esta función es legacy. El flujo principal está en rule.php
 *
 * @param object $quiz El objeto del cuestionario.
 * @param moodle_page $page La página de intento del cuestionario.
 */
function quizaccess_faceid_setup_attempt_page($quiz, $page) {
    global $USER, $PAGE, $CFG;

    // Get server configuration from admin settings
    $server_url = get_config('quizaccess_faceid', 'server_url');
    if (empty($server_url)) {
        $server_url = 'http://127.0.0.1:5001'; // Fallback default
    }
    $server_url = rtrim($server_url, '/');
    $endpoint = $server_url . '/verify';

    // Inyectar módulo JS AMD y pasarle los parámetros necesarios
    $PAGE->requires->js_call_amd('quizaccess_faceid/faceid', 'init', [
        'userid' => $USER->id,
        'quizid' => $quiz->id,
        'wwwroot' => $CFG->wwwroot,
        'endpoint' => $endpoint
    ]);
}

/**
 * Hook to extend user edit form with ID document upload field
 *
 * @param object $mform The form object
 * @param array $customfields Custom field data
 * @param object $user User data
 */
function quizaccess_faceid_user_edit_form_hook($mform, $customfields, $user) {
    global $CFG, $USER;
    
    // Only add field if editing own profile or if admin
    if ($user->id == $USER->id || has_capability('moodle/user:update', context_system::instance())) {
        
        // Add ID document section
        $mform->addElement('header', 'faceid_verification', get_string('profileverification', 'quizaccess_faceid'));
        
        // ID document file upload
        $mform->addElement('filepicker', 'iddocument', get_string('iddocument', 'quizaccess_faceid'), null, 
            array(
                'maxbytes' => $CFG->maxbytes,
                'accepted_types' => array('.jpg', '.jpeg', '.png'),
                'return_types' => FILE_INTERNAL
            )
        );
        $mform->addHelpButton('iddocument', 'iddocument', 'quizaccess_faceid');
        
        // Verify button
        $mform->addElement('submit', 'verify_profile', get_string('verifyprofile', 'quizaccess_faceid'));
        $mform->registerNoSubmitButton('verify_profile');
    }
}

/**
 * Hook to display profile verification status on user profile page
 *
 * @param object $user User object
 * @return string HTML content to display
 */
function quizaccess_faceid_user_profile_hook($user) {
    global $OUTPUT;
    
    $helper = new \quizaccess_faceid\profile_helper();
    $profile = $helper->get_profile_verification($user->id);
    
    $html = html_writer::start_tag('tr');
    $html .= html_writer::tag('td', get_string('profileverification', 'quizaccess_faceid') . ':', 
        array('class' => 'c0'));
    
    if ($profile && $profile->verified) {
        $statustext = get_string('verified', 'quizaccess_faceid');
        $statusclass = 'text-success';
    } else {
        $statustext = get_string('not_verified', 'quizaccess_faceid');
        $statusclass = 'text-danger';
    }
    
    $html .= html_writer::tag('td', html_writer::tag('span', $statustext, array('class' => $statusclass)), 
        array('class' => 'c1'));
    $html .= html_writer::end_tag('tr');
    
    return $html;
}

/**
 * Process file uploads and verification requests
 *
 * @param object $data Form data
 * @param object $user User object
 */
function quizaccess_faceid_process_user_data($data, $user) {
    global $CFG;

    if (isset($data->iddocument) && $data->iddocument > 0) {
        $fs = get_file_storage();
        $context = context_user::instance($user->id);

        // Get uploaded file from draft area
        $draftfiles = $fs->get_area_files(context_user::instance($user->id)->id, 'user', 'draft',
            $data->iddocument, 'id', false);

        if (!empty($draftfiles)) {
            $file = reset($draftfiles);

            // Save ID document
            $helper = new \quizaccess_faceid\profile_helper();
            $fileinfo = $helper->save_id_document($user->id, $file);

            // Update profile data
            $helper->update_profile_verification($user->id, [
                'iddocument_filename' => $fileinfo['filename'],
                'iddocument_filepath' => $fileinfo['filepath'],
                'iddocument_filesize' => $fileinfo['filesize']
            ]);
        }
    }

    // Process verification request
    if (isset($data->verify_profile)) {
        $helper = new \quizaccess_faceid\profile_helper();
        $result = $helper->verify_user_profile($user->id);

        if ($result['success']) {
            if ($result['verified']) {
                \core\notification::add(get_string('profileverified_success', 'quizaccess_faceid'),
                    \core\notification::SUCCESS);
            } else {
                \core\notification::add(get_string('profileverified_failed', 'quizaccess_faceid'),
                    \core\notification::ERROR);
            }
        } else {
            \core\notification::add($result['message'], \core\notification::ERROR);
        }
    }
}

/**
 * Hook to inject ID number protection JavaScript on user profile edit pages
 * This is called before the page footer
 */
function quizaccess_faceid_before_footer() {
    global $PAGE, $CFG;

    // Check if password protection is configured
    $configured_password = get_config('quizaccess_faceid', 'idnumber_password');

    // Only inject if password is configured and we're on a user edit page
    if (!empty($configured_password) &&
        (strpos($PAGE->url->get_path(), '/user/edit.php') !== false ||
         strpos($PAGE->url->get_path(), '/user/editadvanced.php') !== false)) {

        // Load required strings for JavaScript
        $PAGE->requires->strings_for_js([
            'unlock_idnumber',
            'idnumber_locked_help',
            'idnumber_unlocked_help',
            'idnumber_unauthorized_change',
            'idnumber_password_title',
            'idnumber_password_prompt'
        ], 'quizaccess_faceid');

        // Also load core strings used in the module
        $PAGE->requires->strings_for_js([
            'error',
            'password',
            'confirm',
            'ok',
            'required'
        ], 'core');

        // Inject the ID number protection JavaScript
        $PAGE->requires->js_call_amd('quizaccess_faceid/idnumber_protection', 'init', [$CFG->wwwroot]);
    }
}
