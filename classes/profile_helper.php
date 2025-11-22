<?php
namespace quizaccess_faceid;

defined('MOODLE_INTERNAL') || die();

/**
 * Profile helper class for handling profile verification functionality
 */
class profile_helper {

    /**
     * Get profile verification data for a user
     *
     * @param int $userid User ID
     * @return object|false Profile data or false if not found
     */
    public function get_profile_verification($userid) {
        global $DB;
        
        return $DB->get_record('quizaccess_faceid_profile', ['userid' => $userid]);
    }

    /**
     * Create or update profile verification record
     *
     * @param int $userid User ID
     * @param array $data Profile data
     * @return bool Success
     */
    public function update_profile_verification($userid, $data) {
        global $DB;
        
        $existing = $this->get_profile_verification($userid);
        $time = time();
        
        if ($existing) {
            $data['id'] = $existing->id;
            $data['timemodified'] = $time;
            return $DB->update_record('quizaccess_faceid_profile', $data);
        } else {
            $data['userid'] = $userid;
            $data['timecreated'] = $time;
            $data['timemodified'] = $time;
            return $DB->insert_record('quizaccess_faceid_profile', $data);
        }
    }

    /**
     * Save ID document file
     *
     * @param int $userid User ID
     * @param stored_file $file File to save
     * @return array File information
     */
    public function save_id_document($userid, $file) {
        global $CFG;
        
        $fs = get_file_storage();
        $context = \context_user::instance($userid);
        
        // Prepare file record
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'quizaccess_faceid',
            'filearea'  => 'iddocument',
            'itemid'    => $userid,
            'filepath'  => '/',
            'filename'  => $file->get_filename(),
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        
        // Delete existing files
        $fs->delete_area_files($context->id, 'quizaccess_faceid', 'iddocument', $userid);
        
        // Store new file
        $storedfile = $fs->create_file_from_storedfile($fileinfo, $file);
        
        return [
            'filename' => $storedfile->get_filename(),
            'filepath' => $storedfile->get_filepath(),
            'filesize' => $storedfile->get_filesize()
        ];
    }

    /**
     * Get ID document file for a user
     *
     * @param int $userid User ID
     * @return stored_file|false
     */
    public function get_id_document_file($userid) {
        $fs = get_file_storage();
        $context = \context_user::instance($userid);
        
        $files = $fs->get_area_files($context->id, 'quizaccess_faceid', 'iddocument', $userid, 'timemodified DESC', false);
        
        if (empty($files)) {
            return false;
        }
        
        return reset($files);
    }

    /**
     * Verify user profile by comparing face in profile photo and ID document
     *
     * @param int $userid User ID
     * @return array Verification result
     */
    public function verify_user_profile($userid) {
        global $CFG;
        
        $result = [
            'success' => false,
            'verified' => false,
            'score' => 0.0,
            'message' => ''
        ];
        
        try {
            // Get user profile picture
            $user = \core_user::get_user($userid);
            if (!$user) {
                $result['message'] = get_string('user_not_found', 'quizaccess_faceid');
                return $result;
            }

            // Get ID document
            $idfile = $this->get_id_document_file($userid);
            if (!$idfile) {
                $result['message'] = get_string('id_document_not_found', 'quizaccess_faceid');
                return $result;
            }
            
            // Call RFSERVER for comparison
            $verification = $this->compare_faces_with_server($userid, $idfile);

            if ($verification['success']) {
                // Check if ID numbers match (if OCR was performed)
                $idnumber_matches = true;
                $idnumber_message = '';

                if (isset($verification['id_number_verification'])) {
                    $result['id_number_verification'] = $verification['id_number_verification'];

                    // If the server extracted an ID number from the document
                    if (!empty($verification['id_number_verification']['extracted_id'])) {
                        $extracted_id = trim($verification['id_number_verification']['extracted_id']);
                        $user_idnumber = trim($user->idnumber);

                        // Compare ID numbers (case-insensitive, ignore spaces and dashes)
                        $extracted_normalized = preg_replace('/[\s\-]/', '', strtolower($extracted_id));
                        $user_normalized = preg_replace('/[\s\-]/', '', strtolower($user_idnumber));

                        if ($extracted_normalized !== $user_normalized) {
                            $idnumber_matches = false;
                            $idnumber_message = get_string('idnumber_mismatch', 'quizaccess_faceid',
                                (object)['expected' => $user_idnumber, 'found' => $extracted_id]);
                        }
                    }
                }

                // Only mark as verified if both face matches AND ID numbers match
                $is_verified = $verification['verified'] && $idnumber_matches;

                $data = [
                    'verified' => $is_verified ? 1 : 0,
                    'verification_score' => $verification['score'],
                    'last_verification' => time()
                ];

                $this->update_profile_verification($userid, $data);

                $result['success'] = true;
                $result['verified'] = $is_verified;
                $result['score'] = $verification['score'];

                // Set appropriate message
                if (!$is_verified) {
                    if (!$verification['verified']) {
                        $result['message'] = $verification['message'];
                    } else if (!$idnumber_matches) {
                        $result['message'] = $idnumber_message;
                    }
                } else {
                    $result['message'] = $verification['message'];
                }
            } else {
                $result['message'] = $verification['message'];
            }
            
        } catch (\Exception $e) {
            $result['message'] = get_string('error_in_verification', 'quizaccess_faceid') . $e->getMessage();
        }

        return $result;
    }

    /**
     * Compare faces using RFSERVER
     *
     * @param int $userid User ID
     * @param stored_file $idfile ID document file
     * @return array Comparison result
     */
    private function compare_faces_with_server($userid, $idfile) {
        global $CFG;

        $result = [
            'success' => false,
            'verified' => false,
            'score' => 0.0,
            'message' => ''
        ];

        try {
            // Get user data including ID number
            $user = \core_user::get_user($userid);

            // Get profile image URL
            $profileurl = $CFG->wwwroot . '/user/pix.php/' . $userid . '/f3.jpg';

            // Prepare data for RFSERVER
            $postdata = [
                'profile_url' => $profileurl,
                'userid' => $userid
            ];

            // Add ID number if available
            if (!empty($user->idnumber)) {
                $postdata['idnumber'] = $user->idnumber;
            }
            
            // Prepare ID document file
            $filename = $idfile->get_filename();
            $filecontent = $idfile->get_content();
            
            // Create multipart form data
            $boundary = '----WebKitFormBoundary' . uniqid();
            $data = '';
            
            // Add regular form fields
            foreach ($postdata as $name => $value) {
                $data .= "--{$boundary}\r\n";
                $data .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
                $data .= "{$value}\r\n";
            }
            
            // Add file
            $data .= "--{$boundary}\r\n";
            $data .= "Content-Disposition: form-data; name=\"iddocument\"; filename=\"{$filename}\"\r\n";
            $data .= "Content-Type: image/jpeg\r\n\r\n";
            $data .= $filecontent . "\r\n";
            $data .= "--{$boundary}--\r\n";
            
            // Get server configuration from admin settings
            $server_url = get_config('quizaccess_faceid', 'server_url');
            if (empty($server_url)) {
                $server_url = 'http://127.0.0.1:5001'; // Fallback default
            }
            $server_url = rtrim($server_url, '/');
            $verify_profile_url = $server_url . '/verify-profile';

            $timeout = (int)get_config('quizaccess_faceid', 'timeout') ?: 30;
            $verify_ssl = (bool)get_config('quizaccess_faceid', 'verify_ssl');

            // Configure cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $verify_profile_url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: multipart/form-data; boundary={$boundary}",
                    "Content-Length: " . strlen($data)
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => $verify_ssl,
                CURLOPT_SSL_VERIFYHOST => $verify_ssl ? 2 : 0
            ]);
            
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpcode !== 200) {
                $result['message'] = get_string('error_connecting_server', 'quizaccess_faceid');
                return $result;
            }

            $responsedata = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $result['message'] = get_string('invalid_server_response', 'quizaccess_faceid');
                return $result;
            }

            $result['success'] = true;
            $result['verified'] = !empty($responsedata['verified']);
            $result['score'] = floatval($responsedata['score'] ?? 0.0);
            $result['message'] = $responsedata['message'] ?? get_string('verification_completed', 'quizaccess_faceid');

            // Include ID number verification info if available
            if (isset($responsedata['id_number_verification'])) {
                $result['id_number_verification'] = $responsedata['id_number_verification'];
            }

        } catch (\Exception $e) {
            $result['message'] = get_string('error_in_comparison', 'quizaccess_faceid') . $e->getMessage();
        }

        return $result;
    }

    /**
     * Reset profile verification status when profile picture is updated
     *
     * @param int $userid User ID
     * @return bool Success
     */
    public function reset_profile_verification($userid) {
        global $DB;
        
        $existing = $this->get_profile_verification($userid);
        
        if ($existing) {
            // Reset verification status to unverified
            $data = [
                'id' => $existing->id,
                'verified' => 0,
                'verification_score' => 0.0,
                'last_verification' => null,
                'timemodified' => time()
            ];
            
            return $DB->update_record('quizaccess_faceid_profile', $data);
        }
        
        return true; // If no existing record, nothing to reset
    }

    /**
     * Get verification status text for display
     *
     * @param int $userid User ID
     * @return string Status text
     */
    public function get_verification_status_text($userid) {
        $profile = $this->get_profile_verification($userid);
        
        if (!$profile) {
            return get_string('not_verified', 'quizaccess_faceid');
        }
        
        if ($profile->verified) {
            return get_string('verified', 'quizaccess_faceid');
        } else {
            return get_string('not_verified', 'quizaccess_faceid');
        }
    }
}