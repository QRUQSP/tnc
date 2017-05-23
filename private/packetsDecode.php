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
function qruqsp_tnc_packetsDecode($q, $station_id, $args) {
    //
    // Get the packets that need to be decoded
    //
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
            . ") "
        . "WHERE p.status = 10 "
        . "ORDER BY p.utc_of_traffic DESC, a.sequence "
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

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectUpdate');
    qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'packetDecode');
    foreach($rc['packets'] as $p) {
        $rc = qruqsp_tnc_packetDecode($q, $p['station_id'], $p);
        if( $rc['stat'] != 'ok' ) {
            error_log('ERR: Unable to decode packet: ' . $p['id']);
            $rc = qruqsp_core_objectUpdate($q, $station_id, 'qruqsp.tnc.kisspacket', $p['id'], array('status'=>30), 0x04);
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
