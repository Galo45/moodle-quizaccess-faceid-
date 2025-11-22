<?php
$functions = [
    'quizaccess_faceid_verify' => [
        'classname'   => 'quizaccess_faceid\external',
        'methodname'  => 'verify',
        'classpath'   => 'mod/quiz/accessrule/faceid/classes/external.php',
        'description' => 'Validate captured face against profile photo',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
$services = [
    'Quiz FaceID service' => [
        'functions' => ['quizaccess_faceid_verify'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
