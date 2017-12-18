<?php
//
// Description
// -----------
// This function checks for undecoded packets and decodes them.
//
// Arguments
// ---------
// q:
// packet:              The start of the packet (0xc0).
//
function qruqsp_tnc_packetsDecode($ciniki, $tnid, $args) {
    //
    // Get the packets that need to be decoded
    //
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
        . "WHERE p.status = 10 "
        . "ORDER BY p.utc_of_traffic DESC, p.id, a.sequence "
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetDecode');
    foreach($rc['packets'] as $p) {
        $rc = qruqsp_tnc_packetDecode($ciniki, $p['tnid'], $p);
        if( $rc['stat'] != 'ok' ) {
            error_log('ERR: Unable to decode packet: ' . $p['id']);
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.tnc.kisspacket', $p['id'], array('status'=>30), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        } else {
            $addrs = '';
//            foreach($rc['packet']['addrs'] as $d) {
//                $addrs .= $d['callsign'] . ($d['ssid'] > 0 ? '-' . chr($d['ssid']+48) : '') . sprintf(":%x", $d['flags']) . ' > ';
//            }
//            printf("%-60s%s\n", $addrs, $rc['packet']['data']);
        }
    }

    return array('stat'=>'ok');
}
?>
