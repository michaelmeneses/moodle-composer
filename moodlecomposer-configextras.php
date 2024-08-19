<?php

global $CFG;

if (moodlecomposer_get_env('MOODLE_ENV', 'production') !== 'production') {
    // Prevent send email (when use SMTP provider externally)
    // $CFG->smtphosts = '';
}

if (moodlecomposer_get_env('MOODLE_DEBUG', false)) {
    $CFG->debug = (E_ALL | E_STRICT);
    $CFG->debugdisplay = 1;
}

// Add others configs here
