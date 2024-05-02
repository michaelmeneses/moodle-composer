<?php // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

/**
 * MOODLE COMPOSER
 *
 * Import moodlecomposer-util.php
 */
require_once(__DIR__ . '/../moodlecomposer-util.php');

//=========================================================================
// 1. DATABASE SETUP
//=========================================================================
$CFG->dbtype    = moodlecomposer_get_env('MOODLE_DBTYPE', 'mysqli');
$CFG->dblibrary = moodlecomposer_get_env('MOODLE_DBLIBRARY', 'native');
$CFG->dbhost    = moodlecomposer_get_env('MOODLE_DBHOST', 'localhost');
$CFG->dbname    = moodlecomposer_get_env('MOODLE_DBNAME');
$CFG->dbuser    = moodlecomposer_get_env('MOODLE_DBUSER');
$CFG->dbpass    = moodlecomposer_get_env('MOODLE_DBPASS');
$CFG->prefix    = moodlecomposer_get_env('MOODLE_DBPREFIX', 'mdl_');
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbport' => moodlecomposer_get_env('MOODLE_DBPORT', ''),
    'dbsocket' => '',
    'dbcollation' => moodlecomposer_get_env('MOODLE_DBCOLLATION', 'utf8mb4_unicode_ci'),
);

//=========================================================================
// 2. WEB SITE LOCATION
//=========================================================================
$CFG->wwwroot   = moodlecomposer_get_env('MOODLE_WWWROOT');

//=========================================================================
// 3. DATA FILES LOCATION
//=========================================================================
$CFG->dataroot  = moodlecomposer_get_env('MOODLE_DATAROOT');

/**
 * MOODLE COMPOSER
 *
 * Import moodlecomposer-configextras.php (if exists)
 */
$moodlecomposer_configextras = __DIR__ . '/../moodlecomposer-configextras.php';
if (file_exists($moodlecomposer_configextras)) {
    require_once($moodlecomposer_configextras);
}
unset($moodlecomposer_configextras);

require_once(__DIR__ . '/lib/setup.php');

// There is no PHP closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
