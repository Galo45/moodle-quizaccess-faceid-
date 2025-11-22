<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Local library functions for profile verification integration
 */

/**
 * Hook to add profile verification fields to user edit form
 * This function should be called from user edit form
 */
function quizaccess_faceid_user_edit_form_definition_after_data($mform) {
    global $CFG, $USER;
    
    if (!isset($mform->_form)) {
        return;
    }
    
    $user = $mform->_customdata['user'] ?? null;
    if (!$user) {
        return;
    }
    
    // Only add field if editing own profile or if admin
    if ($user->id == $USER->id || has_capability('moodle/user:update', context_system::instance())) {
        
        require_once($CFG->dirroot . '/mod/quiz/accessrule/faceid/classes/profile_helper.php');
        $helper = new \quizaccess_faceid\profile_helper();
        
        // Add ID document section after miscellaneous section
        $mform->addElement('header', 'faceid_verification', get_string('profileverification', 'quizaccess_faceid'));
        
        // Show current verification status
        $profile = $helper->get_profile_verification($user->id);
        if ($profile) {
            $statustext = $profile->verified ? 
                get_string('verified', 'quizaccess_faceid') : 
                get_string('not_verified', 'quizaccess_faceid');
            $statusclass = $profile->verified ? 'alert-success' : 'alert-warning';
            
            $status_html = html_writer::div($statustext, "alert {$statusclass}");
            
            if ($profile->verification_score) {
                $status_html .= html_writer::div(
                    get_string('verification_score', 'quizaccess_faceid', 
                        number_format($profile->verification_score, 3)), 
                    'small text-muted'
                );
            }
            
            if ($profile->last_verification) {
                $status_html .= html_writer::div(
                    get_string('last_verification', 'quizaccess_faceid', 
                        userdate($profile->last_verification)), 
                    'small text-muted'
                );
            }
            
            $mform->addElement('html', $status_html);
        }
        
        // ID document file upload
        $mform->addElement('filepicker', 'iddocument', get_string('iddocument', 'quizaccess_faceid'), null, 
            array(
                'maxbytes' => $CFG->maxbytes,
                'accepted_types' => array('.jpg', '.jpeg', '.png'),
                'return_types' => FILE_INTERNAL,
                'maxfiles' => 1
            )
        );
        $mform->addHelpButton('iddocument', 'iddocument', 'quizaccess_faceid');
        
        // Current ID document info
        $idfile = $helper->get_id_document_file($user->id);
        if ($idfile) {
            $fileinfo = html_writer::div(
                get_string('current_file', 'core', $idfile->get_filename()) . 
                ' (' . display_size($idfile->get_filesize()) . ')',
                'small text-muted'
            );
            $mform->addElement('html', $fileinfo);
        }
        
        // Verify button
        $mform->addElement('submit', 'verify_profile', get_string('verifyprofile', 'quizaccess_faceid'));
        $mform->registerNoSubmitButton('verify_profile');
        
        // Add separator
        $mform->addElement('html', '<hr>');
    }
}

/**
 * Hook to process form data after user edit form submission
 */
function quizaccess_faceid_user_edit_form_save_changes($data, $usercontext) {
    global $CFG;
    
    if (!isset($data->id)) {
        return;
    }
    
    require_once($CFG->dirroot . '/mod/quiz/accessrule/faceid/classes/profile_helper.php');
    $helper = new \quizaccess_faceid\profile_helper();
    
    // Process ID document upload
    if (isset($data->iddocument) && $data->iddocument > 0) {
        $fs = get_file_storage();
        
        // Get uploaded file from draft area
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', 
            $data->iddocument, 'id', false);
        
        if (!empty($draftfiles)) {
            $file = reset($draftfiles);
            
            try {
                // Save ID document
                $fileinfo = $helper->save_id_document($data->id, $file);
                
                // Update profile data
                $helper->update_profile_verification($data->id, [
                    'iddocument_filename' => $fileinfo['filename'],
                    'iddocument_filepath' => $fileinfo['filepath'],
                    'iddocument_filesize' => $fileinfo['filesize'],
                    'verified' => 0  // Reset verification when new document is uploaded
                ]);
                
                \core\notification::add(
                    get_string('iddocument_uploaded', 'quizaccess_faceid'), 
                    \core\notification::SUCCESS
                );
                
            } catch (Exception $e) {
                \core\notification::add(
                    'Error subiendo imagen de cédula: ' . $e->getMessage(), 
                    \core\notification::ERROR
                );
            }
        }
    }
    
    // Process verification request
    if (isset($data->verify_profile)) {
        try {
            $result = $helper->verify_user_profile($data->id);
            
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
        } catch (Exception $e) {
            \core\notification::add(
                'Error en la verificación: ' . $e->getMessage(), 
                \core\notification::ERROR
            );
        }
    }
}

/**
 * Hook to display profile verification status on user profile page
 */
function quizaccess_faceid_user_profile_content($user, $currentuser, $course) {
    global $CFG, $OUTPUT;
    
    require_once($CFG->dirroot . '/mod/quiz/accessrule/faceid/classes/profile_helper.php');
    $helper = new \quizaccess_faceid\profile_helper();
    
    $profile = $helper->get_profile_verification($user->id);
    
    $content = '';
    $content .= html_writer::start_tag('div', ['class' => 'profile-verification']);
    $content .= html_writer::tag('h3', get_string('profileverification', 'quizaccess_faceid'));
    
    if ($profile && $profile->verified) {
        $statustext = get_string('verified', 'quizaccess_faceid');
        $content .= html_writer::div(
            html_writer::tag('span', '✅ ' . $statustext, ['class' => 'badge badge-success']),
            'mb-2'
        );
        
        if ($profile->verification_score) {
            $content .= html_writer::div(
                get_string('verification_score', 'quizaccess_faceid', 
                    number_format($profile->verification_score, 3)), 
                'small text-muted'
            );
        }
        
        if ($profile->last_verification) {
            $content .= html_writer::div(
                get_string('last_verification', 'quizaccess_faceid', 
                    userdate($profile->last_verification)), 
                'small text-muted'
            );
        }
    } else {
        $statustext = get_string('not_verified', 'quizaccess_faceid');
        $content .= html_writer::div(
            html_writer::tag('span', '❌ ' . $statustext, ['class' => 'badge badge-warning']),
            'mb-2'
        );
    }
    
    $content .= html_writer::end_tag('div');
    
    return $content;
}