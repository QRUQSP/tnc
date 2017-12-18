<?php
//
// Description
// -----------
// This method searchs for a KISS TNC Packets for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:               The ID of the tenant to get KISS TNC Packet for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
function qruqsp_tnc_kisspacketSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.kisspacketSearch');
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
        . "AND ("
            . "p.data LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR p.addresses LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
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
/*    $strsql = "SELECT qruqsp_tnc_kisspackets.id, "
        . "qruqsp_tnc_kisspackets.status, "
        . "qruqsp_tnc_kisspackets.utc_of_traffic, "
        . "qruqsp_tnc_kisspackets.port, "
        . "qruqsp_tnc_kisspackets.command, "
        . "qruqsp_tnc_kisspackets.control, "
        . "qruqsp_tnc_kisspackets.protocol, "
        . "qruqsp_tnc_kisspackets.data "
        . "FROM qruqsp_tnc_kisspackets "
        . "WHERE qruqsp_tnc_kisspackets.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "data LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    } 
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
        array('container'=>'packets', 'fname'=>'id', 
            'fields'=>array('id', 'status', 'utc_of_traffic', 'port', 'command', 'control', 'protocol', 'data')),
        )); */
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
