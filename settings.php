<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Heading for server settings
    $settings->add(new admin_setting_heading(
        'quizaccess_faceid/serverheading',
        get_string('serversettings', 'quizaccess_faceid'),
        get_string('serversettingsdesc', 'quizaccess_faceid')
    ));

    // Server URL setting
    $settings->add(new admin_setting_configtext(
        'quizaccess_faceid/server_url',
        get_string('serverurl', 'quizaccess_faceid'),
        get_string('serverurldesc', 'quizaccess_faceid'),
        'http://127.0.0.1:5001',
        PARAM_URL
    ));

    // Connection timeout setting
    $settings->add(new admin_setting_configtext(
        'quizaccess_faceid/timeout',
        get_string('timeout', 'quizaccess_faceid'),
        get_string('timeoutdesc', 'quizaccess_faceid'),
        '10',
        PARAM_INT
    ));

    // SSL verification setting
    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_faceid/verify_ssl',
        get_string('verifyssl', 'quizaccess_faceid'),
        get_string('verifyssldesc', 'quizaccess_faceid'),
        0
    ));

    // Heading for ID number protection
    $settings->add(new admin_setting_heading(
        'quizaccess_faceid/idnumberheading',
        get_string('idnumberprotection', 'quizaccess_faceid'),
        get_string('idnumberprotectiondesc', 'quizaccess_faceid')
    ));

    // ID number protection password
    $settings->add(new admin_setting_configpasswordunmask(
        'quizaccess_faceid/idnumber_password',
        get_string('idnumberpassword', 'quizaccess_faceid'),
        get_string('idnumberpassworddesc', 'quizaccess_faceid'),
        ''
    ));
}