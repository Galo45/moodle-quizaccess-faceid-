<?php
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/faceid/classes/profile_helper.php');

// Check login
require_login();

$userid = optional_param('id', $USER->id, PARAM_INT);

// Permission check
if ($userid != $USER->id && !has_capability('moodle/user:update', context_system::instance())) {
    print_error('nopermissions', 'error');
}

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$context = context_user::instance($userid);

// Set up page
$PAGE->set_context($context);
$PAGE->set_url('/mod/quiz/accessrule/faceid/profile_verification.php', array('id' => $userid));
$PAGE->set_title(get_string('profileverification', 'quizaccess_faceid'));
$PAGE->set_heading(get_string('profileverification', 'quizaccess_faceid'));

$helper = new \quizaccess_faceid\profile_helper();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    
    // Handle verification request
    if (optional_param('verify_profile', '', PARAM_RAW)) {
        try {
            $result = $helper->verify_user_profile($userid);
            
            if ($result['success']) {
                if ($result['verified']) {
                    $message = get_string('profileverified_success', 'quizaccess_faceid');

                    // Add ID number verification info if available
                    if (isset($result['id_number_verification'])) {
                        $idverif = $result['id_number_verification'];
                        if (isset($idverif['match'])) {
                            if ($idverif['match']) {
                                $message .= '<br><strong>✓ ' . get_string('id_number_verified', 'quizaccess_faceid') . '</strong> ' .
                                           htmlspecialchars($idverif['profile_number']);
                            } else {
                                $message .= '<br><strong>⚠ ' . get_string('id_number_warning', 'quizaccess_faceid') . '</strong> ' .
                                           get_string('id_number_document_not_match', 'quizaccess_faceid',
                                               (object)['extracted' => htmlspecialchars($idverif['extracted'] ?? 'no detectado'),
                                                       'profile' => htmlspecialchars($idverif['profile_number'])]);
                            }
                        } elseif (isset($idverif['error'])) {
                            // Show OCR detection error with details
                            $message .= '<br><strong>ℹ️ ' . get_string('id_number_verification_info', 'quizaccess_faceid') . '</strong> ' .
                                       htmlspecialchars($idverif['error']);
                            if (!empty($idverif['raw_text']) && strlen($idverif['raw_text']) > 0) {
                                $message .= '<br><small>' . get_string('text_detected', 'quizaccess_faceid') . ' ' .
                                           htmlspecialchars(substr($idverif['raw_text'], 0, 100)) .
                                           (strlen($idverif['raw_text']) > 100 ? '...' : '') . '</small>';
                            }
                        }
                    }

                    \core\notification::add($message, \core\notification::SUCCESS);
                } else {
                    // Si el mensaje del servidor indica un documento inválido, mostrarlo directamente
                    if (!empty($result['message'])) {
                        \core\notification::add($result['message'], \core\notification::ERROR);
                    } else {
                        \core\notification::add(get_string('profileverified_failed', 'quizaccess_faceid') .
                            ' (Score: ' . number_format($result['score'], 3) . ')',
                            \core\notification::ERROR);
                    }
                }
            } else {
                \core\notification::add($result['message'], \core\notification::ERROR);
            }
        } catch (Exception $e) {
            \core\notification::add(get_string('error_verification', 'quizaccess_faceid') . ' ' . $e->getMessage(), \core\notification::ERROR);
        }
        
        // Redirect to avoid resubmission
        redirect($PAGE->url);
    }
    
    // Handle file upload
    if (!empty($_FILES['iddocument']['tmp_name']) && $_FILES['iddocument']['error'] === UPLOAD_ERR_OK) {
        try {
            // Validate file
            $allowed_types = array('image/jpeg', 'image/jpg', 'image/png');
            if (!in_array($_FILES['iddocument']['type'], $allowed_types)) {
                throw new Exception(get_string('file_type_not_allowed', 'quizaccess_faceid'));
            }

            if ($_FILES['iddocument']['size'] > 5 * 1024 * 1024) { // 5MB max
                throw new Exception(get_string('file_too_large', 'quizaccess_faceid'));
            }
            
            // Create file storage
            $fs = get_file_storage();
            
            // Delete existing files first
            $fs->delete_area_files($context->id, 'quizaccess_faceid', 'iddocument', $userid);
            
            // Prepare file record
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'quizaccess_faceid',
                'filearea' => 'iddocument',
                'itemid' => $userid,
                'filepath' => '/',
                'filename' => clean_filename($_FILES['iddocument']['name']),
                'timecreated' => time(),
                'timemodified' => time()
            );
            
            // Store file
            $storedfile = $fs->create_file_from_pathname($fileinfo, $_FILES['iddocument']['tmp_name']);
            
            if ($storedfile) {
                // Update profile data
                $helper->update_profile_verification($userid, [
                    'iddocument_filename' => $storedfile->get_filename(),
                    'iddocument_filepath' => $storedfile->get_filepath(),
                    'iddocument_filesize' => $storedfile->get_filesize(),
                    'verified' => 0  // Reset verification when new document is uploaded
                ]);
                
                \core\notification::add(get_string('iddocument_uploaded', 'quizaccess_faceid'), 
                    \core\notification::SUCCESS);
            }
        } catch (Exception $e) {
            \core\notification::add(get_string('error_uploading_file', 'quizaccess_faceid') . ' ' . $e->getMessage(), \core\notification::ERROR);
        }
        
        // Redirect to avoid resubmission
        redirect($PAGE->url);
    }
}

// Get current verification data
$profile = $helper->get_profile_verification($userid);
$idfile = $helper->get_id_document_file($userid);

// Start output
echo $OUTPUT->header();

// Display current verification status
echo $OUTPUT->heading(get_string('profileverification', 'quizaccess_faceid'), 2);

if ($profile) {
    $statusclass = $profile->verified ? 'alert-success' : 'alert-warning';
    $statusicon = $profile->verified ? '✅' : '❌';
    $statustext = $profile->verified ? 
        get_string('verified', 'quizaccess_faceid') : 
        get_string('not_verified', 'quizaccess_faceid');
    
    echo html_writer::div($statusicon . ' ' . $statustext, "alert {$statusclass}");
    
    if ($profile->verification_score > 0) {
        echo html_writer::div(
            get_string('verification_score', 'quizaccess_faceid', 
                number_format($profile->verification_score, 3)), 
            'small text-muted mb-2'
        );
    }
    
    if ($profile->last_verification) {
        echo html_writer::div(
            get_string('last_verification', 'quizaccess_faceid', 
                userdate($profile->last_verification)), 
            'small text-muted mb-3'
        );
    }
} else {
    echo html_writer::div('❌ ' . get_string('not_verified', 'quizaccess_faceid'), 'alert alert-warning');
}

echo html_writer::tag('hr', '');

// Upload form
echo html_writer::start_tag('form', array(
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'class' => 'mform'
));

echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

// Current file info
if ($idfile) {
    echo html_writer::div(
        html_writer::tag('h4', get_string('current_id_document_image', 'quizaccess_faceid')) .
        html_writer::div(
            get_string('file_label', 'quizaccess_faceid') . ' ' . $idfile->get_filename() . '<br>' .
            get_string('size_label', 'quizaccess_faceid') . ' ' . display_size($idfile->get_filesize()) . '<br>' .
            get_string('uploaded_label', 'quizaccess_faceid') . ' ' . userdate($idfile->get_timecreated()),
            ''
        ),
        'alert alert-info mb-3'
    );
} else {
    echo html_writer::div(
        '⚠️ ' . get_string('no_id_document_uploaded', 'quizaccess_faceid'),
        'alert alert-warning mb-3'
    );
}

// File input section
echo html_writer::start_tag('div', array('class' => 'form-group mb-3'));
echo html_writer::tag('h4', get_string('iddocument', 'quizaccess_faceid'));
echo html_writer::div(get_string('iddocument_help', 'quizaccess_faceid'), 'form-text text-muted mb-3');

echo html_writer::tag('label', get_string('select_file', 'quizaccess_faceid'), array('for' => 'iddocument', 'class' => 'form-label'));
echo html_writer::empty_tag('input', array(
    'type' => 'file',
    'name' => 'iddocument',
    'id' => 'iddocument',
    'accept' => '.jpg,.jpeg,.png',
    'class' => 'form-control mb-2'
));

echo html_writer::empty_tag('input', array(
    'type' => 'submit',
    'name' => 'upload_file',
    'value' => get_string('upload_id_document_btn', 'quizaccess_faceid'),
    'class' => 'btn btn-primary'
));

echo html_writer::end_tag('div');

echo html_writer::end_tag('form');

// Verification form (only if file exists)
if ($idfile) {
    echo html_writer::tag('hr', '');
    echo html_writer::tag('h4', get_string('verify_profile_title', 'quizaccess_faceid'));
    echo html_writer::div(get_string('compare_profile_photo', 'quizaccess_faceid'), 'text-muted mb-3');
    
    echo html_writer::start_tag('form', array('method' => 'post'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'name' => 'verify_profile',
        'value' => get_string('verifyprofile', 'quizaccess_faceid'),
        'class' => 'btn btn-success btn-lg'
    ));
    echo html_writer::end_tag('form');
}

// Back link
echo html_writer::tag('hr', '');
echo html_writer::div(
    html_writer::link(
        new moodle_url('/user/profile.php', array('id' => $userid)),
        get_string('back_to_profile', 'quizaccess_faceid'),
        array('class' => 'btn btn-outline-secondary')
    ),
    'mt-3'
);

echo $OUTPUT->footer();