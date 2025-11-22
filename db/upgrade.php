<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for quizaccess_faceid plugin
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool success
 */
function xmldb_quizaccess_faceid_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025091100) {
        // Define table quizaccess_faceid_profile to be created.
        $table = new xmldb_table('quizaccess_faceid_profile');

        // Add fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('iddocument_filename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('iddocument_filepath', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('iddocument_filesize', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('verified', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('verification_score', XMLDB_TYPE_NUMBER, '5,4', null, null, null, null);
        $table->add_field('last_verification', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN_UNIQUE, array('userid'), 'user', array('id'));

        // Add indexes.
        $table->add_index('verified', XMLDB_INDEX_NOTUNIQUE, array('verified'));

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025091100, 'quizaccess', 'faceid');
    }

    if ($oldversion < 2025091201) {
        // Add verification_type field to quizaccess_faceid table
        $table = new xmldb_table('quizaccess_faceid');
        $field = new xmldb_field('verification_type', XMLDB_TYPE_CHAR, '20', null, null, null, 'basic', 'enabled');

        // Conditionally launch add field verification_type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025091201, 'quizaccess', 'faceid');
    }

    return true;
}