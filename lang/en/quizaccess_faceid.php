<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Face ID verification';
$string['privacy:metadata'] = 'The Face ID verification plugin does not store any personal data.';

// Settings form strings
$string['faceidenabled'] = 'Enable Face ID verification';
$string['faceidenabled_help'] = 'If enabled, students must verify their identity using face recognition before starting the quiz.';
$string['faceid_with_profile'] = 'Enable Face ID with profile verification';
$string['faceid_with_profile_help'] = 'If enabled, students must verify their facial identity by comparing with their previously verified profile.';
$string['verification_mode'] = 'Verification mode';
$string['verification_mode_help'] = 'Select the type of facial verification: basic (anti-spoofing only) or with profile (comparison with verified profile).';
$string['disabled'] = 'Disabled';

// Pre-flight check strings
$string['faceidrequired'] = 'Face ID verification required';
$string['requireface'] = 'Face verification required';
$string['faceinstructions'] = 'Please click the button below to verify your identity using your camera. Make sure your face is clearly visible and well-lit.';
$string['verifyface'] = 'Verify Face';

// Validation strings
$string['facemismatch'] = 'Face verification was not completed successfully. Please verify your identity before proceeding.';
$string['faceverificationfailed'] = 'Face verification failed. Please try again.';
$string['faceverificationsuccess'] = 'Face verification successful!';

// Error strings
$string['cameraerror'] = 'Unable to access camera. Please check your camera permissions.';
$string['networkerror'] = 'Network error during verification. Please try again.';
$string['verificationerror'] = 'Verification error. Please contact your instructor if the problem persists.';

// Server configuration strings
$string['serversettings'] = 'Face verification server settings';
$string['serversettingsdesc'] = 'Configure the connection to the facial verification server';

$string['serverurl'] = 'Server URL';
$string['serverurldesc'] = 'Complete URL of the facial verification server (e.g., http://192.168.1.100:5001 or https://faceid.university.edu:5001)';

$string['timeout'] = 'Connection timeout';
$string['timeoutdesc'] = 'Maximum time in seconds to wait for server response (default: 10)';

$string['verifyssl'] = 'Verify SSL certificates';
$string['verifyssldesc'] = 'Enable SSL certificate verification (disable only for development/testing with self-signed certificates)';

// Error messages
$string['servernotconfigured'] = 'Facial verification server is not configured';
$string['servernotreachable'] = 'Cannot connect to facial verification server';
$string['configurationerror'] = 'Plugin configuration error';

// Profile verification strings
$string['iddocument'] = 'ID Document Image';
$string['iddocument_help'] = 'Upload an image of your identity document (ID card) for profile verification.';
$string['profileverification'] = 'Profile Verified';
$string['verified'] = 'Yes';
$string['not_verified'] = 'No';
$string['uploadiddocument'] = 'Upload ID document image';
$string['verifyprofile'] = 'Verify profile';
$string['profileverified_success'] = 'Profile verified successfully';
$string['profileverified_failed'] = 'Profile verification failed. Faces do not match.';
$string['no_iddocument'] = 'No ID document image uploaded';
$string['verification_score'] = 'Similarity score: {$a}';
$string['last_verification'] = 'Last verification: {$a}';
$string['reverify'] = 'Re-verify';
$string['iddocument_uploaded'] = 'ID document image uploaded successfully';
$string['current_file'] = 'Current file';

// ID number protection strings
$string['idnumberprotection'] = 'ID Number Protection';
$string['idnumberprotectiondesc'] = 'Configure password protection for the ID number field in user profiles';
$string['idnumberpassword'] = 'ID Number Edit Password';
$string['idnumberpassworddesc'] = 'Password required to edit user ID numbers. Leave empty to disable protection.';
$string['idnumber_mismatch'] = 'ID number verification failed: Expected "{$a->expected}" but found "{$a->found}" in document';
$string['idnumberpassword_notconfigured'] = 'ID number password protection is not configured';
$string['idnumberpassword_correct'] = 'Password correct. You can now edit the ID number.';
$string['idnumberpassword_incorrect'] = 'Incorrect password. Please try again.';
$string['unlock_idnumber'] = 'Unlock to Edit';
$string['idnumber_locked_help'] = 'This field is protected. Click "Unlock to Edit" to make changes.';
$string['idnumber_unlocked_help'] = 'Field unlocked. You can now edit the ID number.';
$string['idnumber_unauthorized_change'] = 'You must unlock the ID number field before making changes.';
$string['idnumber_password_title'] = 'Verify Password';
$string['idnumber_password_prompt'] = 'Enter the password to unlock the ID number field:';

// Additional verification strings
$string['profile_not_verified'] = 'Profile not verified';
$string['must_verify_profile_before_quiz'] = 'You must verify your profile before you can take this quiz.';
$string['verify_profile_now'] = 'Verify Profile Now';
$string['face_verification_valid'] = 'Valid facial verification';
$string['identity_verified_recently'] = 'Your identity was recently verified. You can ';
$string['must_verify_identity_to'] = 'You must verify your identity to ';
$string['new_attempt'] = 'new attempt';
$string['continue_attempt'] = 'continue with the attempt';
$string['start_quiz'] = 'start the quiz';
$string['continue_quiz'] = 'continue with the attempt';
$string['face_verification_with_profile'] = 'facial verification with profile';
$string['face_verification_basic'] = 'facial verification';
$string['must_complete_verification'] = 'You must complete the {$a->type} to {$a->action}.';
$string['id_number_verified'] = 'ID number verified:';
$string['id_number_warning'] = 'Warning:';
$string['id_number_document_not_match'] = 'The ID number on the document ({$a->extracted}) does not match the profile ({$a->profile})';
$string['id_number_verification_info'] = 'ID number verification:';
$string['text_detected'] = 'Text detected:';
$string['error_verification'] = 'Verification error:';
$string['file_type_not_allowed'] = 'File type not allowed. Use JPG or PNG.';
$string['file_too_large'] = 'File too large. Maximum 5MB.';
$string['error_uploading_file'] = 'Error uploading file:';
$string['current_id_document_image'] = 'Current ID document image:';
$string['file_label'] = 'File:';
$string['size_label'] = 'Size:';
$string['uploaded_label'] = 'Uploaded:';
$string['no_id_document_uploaded'] = 'You have not uploaded any ID document image yet.';
$string['select_file'] = 'Select file:';
$string['upload_id_document_btn'] = 'Upload ID document image';
$string['verify_profile_title'] = 'Verify profile';
$string['compare_profile_photo'] = 'Compare your profile photo with the uploaded ID document image.';
$string['back_to_profile'] = '← Back to profile';
$string['user_not_found'] = 'User not found';
$string['id_document_not_found'] = 'ID document image not found';
$string['error_in_verification'] = 'Verification error: ';
$string['error_connecting_server'] = 'Error connecting to verification server';
$string['invalid_server_response'] = 'Invalid server response';
$string['verification_completed'] = 'Verification completed';
$string['error_in_comparison'] = 'Comparison error: ';

// JavaScript strings
$string['starting_camera'] = 'Starting camera...';
$string['camera_activated'] = 'Camera activated. Position your face in the center.';
$string['error_camera_access'] = 'Error: Could not access camera. ';
$string['capture_face'] = 'Capture Face';
$string['processing_image'] = 'Processing image...';
$string['incomplete_configuration'] = 'Incomplete configuration. Check endpoint, userid, quizid and wwwroot.';
$string['verification_successful_continue'] = '✅ Verification successful. You can continue with the quiz.';
$string['could_not_verify_identity'] = '❌ Could not verify identity';
$string['error_processing_response'] = '❌ Error processing server response';