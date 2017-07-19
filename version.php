<?php
/**
 * @package   turnitintool
 * @copyright 2012 Turnitin
 */

if (!isset($plugin)) {
    $plugin = new StdClass();
}

$plugin->version  = 2017071901;  // The current module version (Date: YYYYMMDDXX)
$plugin->release   = "2.6+";
$plugin->component = 'mod_turnitintool';
$plugin->maturity  = MATURITY_STABLE;
$plugin->cron     = 1800;        // Period for cron to check this module (secs)
$plugin->requires  = 2013111800;
