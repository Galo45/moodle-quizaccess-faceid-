<?php
defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\user_updated',
        'callback' => '\quizaccess_faceid\observer::user_updated',
    ),
);