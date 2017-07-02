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

$shriek = '';
if( isset($argv[1]) && $argv[1] != '' ) {
    $shriek = $argv[1];
}

//
// Setup the $qruqsp variable to hold all things qruqsp.  
//
$q = $rc['q'];
$strsql = "SELECT p.id, GROUP_CONCAT(CONCAT_WS('-', a.callsign, a.ssid) ORDER BY a.sequence SEPARATOR ' > ') AS addrs "
    . "FROM qruqsp_tnc_kisspackets AS p, qruqsp_tnc_kisspacket_addrs AS a "
    . "WHERE p.id = a.packet_id AND a.atype > 10 "
    . "GROUP BY p.id "
    . "";
print $strsql;
qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbQuote');
qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbUpdate');
$rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.tnc', 'item');
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}
foreach($rc['rows'] as $row) {
//    if( $row['addresses'] != $row['addrs'] ) {
        $strsql = "UPDATE qruqsp_tnc_kisspackets SET addresses = '" . qruqsp_core_dbQuote($q, $row['addrs']) . "' "
            . "WHERE id = '" . qruqsp_core_dbQuote($q, $row['id']) . "' ";
        $rc = qruqsp_core_dbUpdate($q, $strsql, 'qruqsp.tnc');
        if( $rc['stat'] != 'ok' ) {
            print_r($rc);
        }
//    }
}




?>
