<?php
//
// Description
// -----------
// This method will return the list of KISS TNC Packets for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:              The ID of the tenant to get KISS TNC Packet for.
//
function qruqsp_tnc_kisspacketList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.kisspacketList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Get the list of packets
    //
    $strsql = "SELECT p.id, "
        . "p.status, "
        . "p.utc_of_traffic, "
        . "p.port, "
        . "p.command, "
        . "p.control, "
        . "p.protocol, "
        . "p.data, "
        . "p.addresses "
//        . "a.id AS a_id, "
//        . "CONCAT_WS('-', a.callsign, a.ssid) AS addresses, "
//        . "a.callsign, "
//        . "a.ssid "
        . "FROM qruqsp_tnc_kisspackets AS p "
//        . "LEFT JOIN qruqsp_tnc_kisspacket_addrs AS a ON ("
//            . "p.id = a.packet_id "
//            . "AND a.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//            . ") "
        . "WHERE p.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY p.utc_of_traffic DESC "
        . "LIMIT 250 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
        array('container'=>'packets', 'fname'=>'id', 
            'fields'=>array('id', 'status', 'utc_of_traffic', 'port', 'command', 'control', 'protocol', 'data', 'addresses'),
//            'dlists'=>array('addresses'=>' > '),
            'utctotz'=>array('utc_of_traffic'=>array('timezone'=>'UTC', 'format'=>$datetime_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['packets']) ) {
        $packets = $rc['packets'];
        $packet_ids = array();
        foreach($packets as $iid => $packet) {
            $packet_ids[] = $packet['id'];
        }
    } else {
        $packets = array();
        $packet_ids = array();
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');

    //
    // Get the total number of packets stored
    //
    $stats = array();
    $total_packets = 0;
    $last7days_packets = 0;
    $strsql = "SELECT COUNT(*) AS packets "
        . "FROM qruqsp_tnc_kisspackets "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'qruqsp.tnc', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']) ) {
        $stats[] = array('label'=>'Total Packets', 'value'=>number_format($rc['num'], 0));
        $total_packets = $rc['num'];
    }

    //
    // Get the number of packets for the last 7 days
    //
    $strsql = "SELECT COUNT(*) AS packets "
        . "FROM qruqsp_tnc_kisspackets "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND DATEDIFF(UTC_TIMESTAMP(), utc_of_traffic) < 7 "
        . "";
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'qruqsp.tnc', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']) ) {
        $stats[] = array('label'=>'Last 7 Days', 'value'=>number_format($rc['num'], 0));
        $last7days_packets = $rc['num'];
    }

    //
    // Get the number of packets for the last 30 days
    //
    $strsql = "SELECT COUNT(*) AS packets "
        . "FROM qruqsp_tnc_kisspackets "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND DATEDIFF(UTC_TIMESTAMP(), utc_of_traffic) < 30 "
        . "";
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'qruqsp.tnc', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']) ) {
        $stats[] = array('label'=>'Last 30 Days', 'value'=>number_format($rc['num'], 0));
    }

    $rsp = array('stat'=>'ok', 'packets'=>$packets, 'nplist'=>$packet_ids, 'stats'=>$stats);

    //
    // Top 5 sources since database started
    //
    $strsql = "SELECT callsign, ssid, COUNT(*) AS packets "
        . "FROM qruqsp_tnc_kisspacket_addrs "
        . "WHERE atype = 20 "
        . "GROUP BY callsign, ssid "
        . "ORDER BY packets DESC "
        . "LIMIT 5 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'callsigns');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $rsp['sources5total'] = array();
        foreach($rc['rows'] as $row) {
            $rsp['sources5total'][] = array(
                'label'=>$row['callsign'] . '-' . $row['ssid'], 
                'value'=>number_format($row['packets'], 0),
                'percent'=>number_format(($row['packets']/$total_packets)*100, 0),
                );
        }
    }
    
    //
    // Top 5 sources last 7 days
    //
    $strsql = "SELECT a.callsign, a.ssid, COUNT(*) AS packets "
        . "FROM qruqsp_tnc_kisspacket_addrs AS a, qruqsp_tnc_kisspackets AS p "
        . "WHERE a.atype = 20 "
        . "AND a.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND a.packet_id = p.id "
        . "AND DATEDIFF(UTC_TIMESTAMP(), p.utc_of_traffic) < 7 "
        . "AND p.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY a.callsign, a.ssid "
        . "ORDER BY packets DESC "
        . "LIMIT 5 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'callsigns');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $rsp['sources5last7days'] = array();
        foreach($rc['rows'] as $row) {
            $rsp['sources5last7days'][] = array(
                'label'=>$row['callsign'] . '-' . $row['ssid'], 
                'value'=>number_format($row['packets'], 0),
                'percent'=>number_format(($row['packets']/$last7days_packets)*100, 0),
                );
        }
    }
    
    //
    // Top 5 digipeaters since database started
    //
    $strsql = "SELECT callsign, ssid, COUNT(*) AS packets "
        . "FROM qruqsp_tnc_kisspacket_addrs "
        . "WHERE atype = 30 "
        . "AND callsign not like 'WIDE%' "
        . "GROUP BY callsign, ssid "
        . "ORDER BY packets DESC "
        . "LIMIT 5 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'callsigns');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $rsp['digipeaters5total'] = array();
        foreach($rc['rows'] as $row) {
            $rsp['digipeaters5total'][] = array(
                'label'=>$row['callsign'] . '-' . $row['ssid'], 
                'value'=>number_format($row['packets'], 0),
                'percent'=>number_format(($row['packets']/$total_packets)*100, 0),
            );
        }
    }
    
    //
    // Top 5 digipeaters last 7 days
    //
    $strsql = "SELECT a.callsign, a.ssid, COUNT(*) AS packets "
        . "FROM qruqsp_tnc_kisspacket_addrs AS a, qruqsp_tnc_kisspackets AS p "
        . "WHERE a.atype = 30 "
        . "AND callsign not like 'WIDE%' "
        . "AND a.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND a.packet_id = p.id "
        . "AND DATEDIFF(UTC_TIMESTAMP(), p.utc_of_traffic) < 7 "
        . "AND p.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY a.callsign, a.ssid "
        . "ORDER BY packets DESC "
        . "LIMIT 5 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'callsigns');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $rsp['digipeaters5last7days'] = array();
        foreach($rc['rows'] as $row) {
            $rsp['digipeaters5last7days'][] = array(
                'label'=>$row['callsign'] . '-' . $row['ssid'], 
                'value'=>number_format($row['packets'], 0),
                'percent'=>number_format(($row['packets']/$last7days_packets)*100, 0),
                );
        }
    }
    
    return $rsp;
}
?>
