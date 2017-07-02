#!/usr/bin/php
<?php
//
// Description
// -----------
// This script will output the packets from the database.
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

$strsql = "SELECT station_id, status, utc_of_traffic, raw_packet "
    . "FROM qruqsp_tnc_kisspackets "
    . "ORDER BY utc_of_traffic DESC "
    . "LIMIT 50 "
    . "";
qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
$rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.tnc', 'item');

if( $rc['stat'] != 'ok' ) {
    print_r($rc);
}

//    	$text = print_r($rc);
//   	print "\nDEBUG: text=$text\n";
//    	$toprint = substr ($text, 20, 1);
//    	print "\nDEBUG: toprint=$toprint\n";
qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'packetDecode');
foreach($rc['rows'] as $row) {
    $rc = qruqsp_tnc_packetDecode($q, $row['station_id'], $row['raw_packet']);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
    } else {
        print_r($rc['packet']);
    }
}

exit;
?>
