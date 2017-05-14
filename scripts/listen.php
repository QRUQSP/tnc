#!/usr/bin/php
<?php
//
// Description
// -----------
// This script will listen on a pts for kiss tnc
//

//
// Initialize QRUQSP by including the qruqsp_api.php
//
$start_time = microtime(true);
global $qruqsp_root;
$qruqsp_root = dirname(__FILE__);
if( !file_exists($qruqsp_root . '/qruqsp-api.ini') ) {
    $qruqsp_root = dirname(dirname(dirname(dirname(__FILE__))));
}

require_once($qruqsp_root . '/qruqsp-mods/core/private/loadMethod.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/init.php');

//
// Initialize Q
//
$rc = qruqsp_core_init($qruqsp_root, 'json');
if( $rc['stat'] != 'ok' ) {
    print "ERR: Unable to initialize Q\n";
    exit;
}

//
// Setup the $qruqsp variable to hold all things qruqsp.  
//
$q = $rc['q'];

//
// Check for direwolf 
//
if( !isset($q['config']['qruqsp.tnc']['pts']) ) {
    print "ERR: No TNC pts specified\n";
    exit;
}

qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'listen');
$rc = qruqsp_tnc_listen($q, $q['config']['qruqsp.tnc']['station_id'], $q['config']['qruqsp.tnc']['pts']);
if( $rc['stat'] != 'ok' ) {
    print "TNC Error\n";
    print_r($rc);
    exit;
}

exit;
?>
