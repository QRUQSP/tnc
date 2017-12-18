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

$strsql = "SELECT p.id, "
    . "p.tnid, "
    . "p.status, "
    . "p.utc_of_traffic, "
    . "p.raw_packet, "
    . "p.port, "
    . "p.command, "
    . "p.control, "
    . "p.protocol, "
    . "p.data, "
    . "a.id AS addr_id, "
    . "a.packet_id, "
    . "a.atype, "
    . "a.sequence, "
    . "a.flags, "
    . "a.callsign, "
    . "a.ssid "
    . "FROM qruqsp_tnc_kisspackets AS p "
    . "LEFT JOIN qruqsp_tnc_kisspacket_addrs AS a ON ("
        . "p.id = a.packet_id "
        . "AND p.tnid = a.tnid "
        . ") "
    . "WHERE p.status = 20 "
    . "ORDER BY p.id, a.sequence "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
    array('container'=>'packets', 'fname'=>'id', 
        'fields'=>array('id', 'tnid', 'status', 'utc_of_traffic', 'raw_packet', 'port', 'command', 'control', 'protocol', 'data')),
    array('container'=>'addrs', 'fname'=>'addr_id', 
        'fields'=>array('id'=>'addr_id', 'packet_id', 'atype', 'sequence', 'flags', 'callsign', 'ssid')),
    ));
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
}
$packets = $rc['packets'];

foreach($packets as $p) {
    $addrs = '';
    if( isset($p['addrs']) ) {
        foreach($p['addrs'] as $a) {
            $addrs .= ($addrs != '' ? ' > ' : '') . trim($a['callsign']) . ($a['ssid'] > 0 ? sprintf("-%d", $a['ssid']) : '') . sprintf(":%d", $a['flags']);
        }
    }
    printf("%6d %-75s%s\n", $p['id'], $addrs, $p['data']);
}

exit;
?>
