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
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/qruqsp-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}

require_once($ciniki_root . '/qruqsp-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/qruqsp-mods/core/private/init.php');

//
// Initialize Q
//
$rc = ciniki_core_init($ciniki_root, 'json');
if( $rc['stat'] != 'ok' ) {
    print "ERR: Unable to initialize Q\n";
    exit;
}

//
// Setup the $ciniki variable to hold all things qruqsp.  
//
$ciniki = $rc['ciniki'];

//
// Check for direwolf 
//
if( !isset($ciniki['config']['qruqsp.tnc']['pts']) ) {
    print "ERR: No TNC pts specified\n";
    exit;
}

$strsql = "SELECT tnid, status, utc_of_traffic, raw_packet "
    . "FROM qruqsp_tnc_kisspackets "
    . "ORDER BY utc_of_traffic DESC "
    . "LIMIT 1000 "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'item');
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
}

ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetDecode');
foreach($rc['rows'] as $row) {
    $rc = qruqsp_tnc_packetDecode($ciniki, $row['tnid'], $row['raw_packet']);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
    } else {
        print_r($rc['packet']);
    }
}

exit;
?>
