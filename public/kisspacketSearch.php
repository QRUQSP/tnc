<?php
//
// Description
// -----------
// This method searchs for a KISS TNC Packets for a station.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:         The ID of the station to get KISS TNC Packet for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
function qruqsp_tnc_kisspacketSearch($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to station_id as owner, or sys admin.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($q, $args['station_id'], 'qruqsp.tnc.kisspacketSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of packets
    //
    $strsql = "SELECT qruqsp_tnc_kisspackets.id, "
        . "qruqsp_tnc_kisspackets.status, "
        . "qruqsp_tnc_kisspackets.utc_of_traffic, "
        . "qruqsp_tnc_kisspackets.port, "
        . "qruqsp_tnc_kisspackets.command, "
        . "qruqsp_tnc_kisspackets.control, "
        . "qruqsp_tnc_kisspackets.protocol "
        . "FROM qruqsp_tnc_kisspackets "
        . "WHERE qruqsp_tnc_kisspackets.station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
        . "AND ("
            . "name LIKE '" . qruqsp_core_dbQuote($q, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . qruqsp_core_dbQuote($q, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . qruqsp_core_dbQuote($q, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, 'qruqsp.tnc', array(
        array('container'=>'packets', 'fname'=>'id', 
            'fields'=>array('id', 'status', 'utc_of_traffic', 'port', 'command', 'control', 'protocol')),
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

    return array('stat'=>'ok', 'packets'=>$packets, 'nplist'=>$packet_ids);
}
?>
