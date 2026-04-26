<?php
// Load WordPress from plugin directory
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
$enabled = get_option( 'siteagent_enabled_modules' );
echo "Enabled modules: " . print_r($enabled, true);
