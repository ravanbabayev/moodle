<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2025061113;       // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2022112800;       // Requires this Moodle version (4.1).
$plugin->component = 'local_lidio';    // Full name of the plugin.
$plugin->maturity  = MATURITY_ALPHA;   // Maturity level.
$plugin->release   = '0.4.3';          // Human-readable version name.

// Add callback for page header to inject the redirect script
$plugin->callbacks = [
    'before_standard_html_head' => 'local_lidio_before_standard_html_head',
]; 