#!/usr/bin/php
<?php
//
// Description
// -----------
// This script will analyze packets
//

//
// Initialize QRUQSP by including the qruqsp_api.php
//
$start_time = microtime(true);
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}

require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');

//
// Initialize Q
//
$rc = ciniki_core_init($ciniki_root, 'json');
if( $rc['stat'] != 'ok' ) {
    print "ERR: Unable to initialize Q\n";
    exit;
}

$shriek = '';
if( isset($argv[1]) && $argv[1] != '' ) {
    $shriek = $argv[1];
}

//
// Setup the $ciniki variable to hold all things qruqsp.  
//
$ciniki = $rc['ciniki'];
$strsql = "SELECT p.id, GROUP_CONCAT(CONCAT_WS('-', a.callsign, a.ssid) ORDER BY a.sequence SEPARATOR ' > ') AS addrs "
    . "FROM qruqsp_tnc_kisspackets AS p, qruqsp_tnc_kisspacket_addrs AS a "
    . "WHERE p.id = a.packet_id AND a.atype > 10 "
    . "GROUP BY p.id "
    . "";
print $strsql;
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
$rc = qruqsp_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'item');
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}
foreach($rc['rows'] as $row) {
//    if( $row['addresses'] != $row['addrs'] ) {
        $strsql = "UPDATE qruqsp_tnc_kisspackets SET addresses = '" . qruqsp_core_dbQuote($ciniki, $row['addrs']) . "' "
            . "WHERE id = '" . qruqsp_core_dbQuote($ciniki, $row['id']) . "' ";
        $rc = qruqsp_core_dbUpdate($ciniki, $strsql, 'qruqsp.tnc');
        if( $rc['stat'] != 'ok' ) {
            print_r($rc);
        }
//    }
}




?>
