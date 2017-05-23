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

$strsql = "SELECT p.id, "
    . "p.station_id, "
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
        . "AND p.station_id = a.station_id "
        . ") ";
if( isset($argv[1]) && is_numeric($argv[1]) ) {
    $strsql .= "WHERE p.id = " . $argv[1] . " ";
}
$strsql .= "ORDER BY p.utc_of_traffic DESC, a.sequence "
    . "";
qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
$rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, 'qruqsp.tnc', array(
    array('container'=>'packets', 'fname'=>'id', 
        'fields'=>array('id', 'station_id', 'status', 'utc_of_traffic', 'raw_packet', 'port', 'command', 'control', 'protocol', 'data')),
    array('container'=>'addrs', 'fname'=>'addr_id', 
        'fields'=>array('id'=>'addr_id', 'packet_id', 'atype', 'sequence', 'flags', 'callsign', 'ssid')),
    ));
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
}
$packets = $rc['packets'];

qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'packetDecode');
foreach($rc['packets'] as $p) {
    $rc = qruqsp_tnc_packetDecode($q, $p['station_id'], $p);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
    } else {
//        $dest = $rc['packet']['dest_callsign'] . ($rc['packet']['dest_ssid'] > 0 ? '-' . chr($rc['packet']['dest_ssid']+48) : '') . sprintf(":%d", $rc['packet']['dest_flags']);
//        $src = $rc['packet']['src_callsign'] . ($rc['packet']['src_ssid'] > 0 ? '-' . chr($rc['packet']['src_ssid']+48) : '') . sprintf(":%d", $rc['packet']['src_flags']);
        $addrs = '';
        foreach($rc['packet']['addrs'] as $d) {
            $addrs .= $d['callsign'] . ($d['ssid'] > 0 ? '-' . chr($d['ssid']+48) : '') . sprintf(":%x", $d['flags']) . ' > ';
        }
        printf("%-60s%s\n", $addrs, $rc['packet']['data']);
    }
}

exit;
?>
