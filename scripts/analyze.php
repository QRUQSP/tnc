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
if( !file_exists($ciniki_root . '/qruqsp-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}

require_once($ciniki_root . '/qruqsp-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/qruqsp-mods/core/private/init.php');

//
// Initialize Q
//
$rc = qruqsp_core_init($ciniki_root, 'json');
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
$strsql = "SELECT COUNT(*) AS numpackets FROM qruqsp_tnc_kisspackets "
    . ($shriek != '' ? "WHERE data LIKE '%$shriek%' " : '');

qruqsp_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = qruqsp_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
    array('container'=>'count', 'fname'=>'numpackets', 
        'fields'=>array('numpackets')),
    ));
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}

// print_r($rc);
print "count=" . $rc['count'][0]['numpackets'] . "\n";

//
// Look for unique callsigns and count of each 
//
// Count of each sounrce callsign thta is not a digipeater
$strsql = "SELECT a.callsign, a.ssid, COUNT(a.packet_id) AS numpackets "
    . "FROM qruqsp_tnc_kisspacket_addrs AS a, qruqsp_tnc_kisspackets AS p "
    . "WHERE a.atype = 20 "
    . "AND a.packet_id = p.id "
    . ($shriek != '' ? "AND p.data LIKE '%$shriek%' " : '')
    . "GROUP BY a.callsign, a.ssid "
    . "ORDER BY numpackets DESC "
    . "";

qruqsp_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = qruqsp_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
    array('container'=>'callsigns', 'fname'=>'callsign', 
        'fields'=>array('callsign', 'ssid', 'numpackets')),
    ));
// print_r($rc);
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}

foreach ($rc['callsigns'] as $cid => $callsign) {
    // print trim($callsign['callsign']) . $callsign['ssid'] . '=' . $callsign['numpackets'] . " ";
    // ? is an if operator
    // : is an or operator
    print trim($callsign['callsign']) 
        . ($callsign['ssid'] != '' ? '-' . $callsign['ssid'] : '')
        . '=' . $callsign['numpackets'] . " ";
}
print "\n\n";

///
// find frequencies
//
$strsql = "SELECT p.id, p.data, a.callsign, a.ssid "
  . "FROM qruqsp_tnc_kisspackets AS p, qruqsp_tnc_kisspacket_addrs AS a "
  . "WHERE p.data REGEXP '[^0-9][0-9]+\.[0-9]+' "
  . ($shriek != '' ? "AND p.data LIKE '%$shriek%' " : '')
  . "AND p.id = a.packet_id "
  . "AND a.atype = 20 "
  . "";

qruqsp_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = qruqsp_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
    array('container'=>'packets', 'fname'=>'id', 'fields'=>array('id', 'data', 'callsign', 'ssid'))
    ));
// print_r($rc);
if( $rc['stat'] != 'ok' ) {
    print_r($rc);
    exit;
}

$frequencies = array();
foreach($rc['packets'] as $p) {
    if( preg_match("/[^0-9]([0-9]+\.[0-9]+)[^0-9NWSE]/", $p['data'], $matches) ) {
        // (float) casts the frequency as a float so that we equate 444.65 with 444.650
        $frequency = number_format($matches[1], 3, '.', '');
        //  $frequency = preg_replace("/^.[^0-9]([0-9]+\.[0-9]+).*$/", "$1", $p['data']);
        $callsign = trim($p['callsign']) . ($p['ssid'] != '' ? '-' . $p['ssid'] : '');
        if( !isset($frequencies[$frequency]) ) {
            $frequencies[$frequency] = array();
        }
        if( !isset($frequencies[$frequency][$callsign]) ) {
            $frequencies[$frequency][$callsign] = 0;
        }
        $frequencies[$frequency][$callsign]++;
    }
}

// print_r($frequencies);

ksort ($frequencies);

foreach($frequencies as $freq => $callsigns) {
    $s = '';
    foreach($callsigns as $callsign => $cnt) {
        $s .= ($s != '' ? ', ' : '') . "$callsign($cnt)";
    }
    print (float)$freq . " = $s\n";
}

exit;
?>
