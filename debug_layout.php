<?php
define('CLI_SCRIPT', true);
require_once('config.php');

echo "Current theme: " . get_config('core', 'theme') . "\n";
echo "Site ID: " . SITEID . "\n";

// Check frontpage settings
echo "Frontpage setting: " . get_config('core', 'frontpage') . "\n";
echo "Frontpage logged in: " . get_config('core', 'frontpageloggedin') . "\n";

// Check if we're on the site frontpage
$context = context_course::instance(SITEID);
echo "Site context: " . $context->id . "\n";
echo "Site shortname: " . format_string($SITE->shortname, true, ['context' => $context, "escape" => false]) . "\n"; 