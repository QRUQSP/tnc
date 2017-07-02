<?php
//
// Description
// ===========
// This method will return all the information about an kiss tnc packet.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:         The ID of the station the kiss tnc packet is attached to.
// kisspacket_id:          The ID of the kiss tnc packet to get the details for.
//
function qruqsp_tnc_kisspacketGet($q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),
        'kisspacket_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'KISS TNC Packet'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this station
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($q, $args['station_id'], 'qruqsp.tnc.kisspacketGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load station settings
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'intlSettings');
    $rc = qruqsp_core_intlSettings($q, $args['station_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dateFormat');
    $date_format = qruqsp_core_dateFormat($q, 'php');

    //
    // Return default for new KISS TNC Packet
    //
    if( $args['kisspacket_id'] == 0 ) {
        $packet = array('id'=>0,
            'status'=>'',
            'utc_of_traffic'=>'',
            'raw_packet'=>'',
            'port'=>'0',
            'command'=>'0',
            'control'=>'0',
            'protocol'=>'0',
            'data'=>'',
        );
    }

    //
    // Get the details for an existing KISS TNC Packet
    //
    else {
        $strsql = "SELECT qruqsp_tnc_kisspackets.id, "
            . "qruqsp_tnc_kisspackets.status, "
            . "qruqsp_tnc_kisspackets.utc_of_traffic, "
            . "qruqsp_tnc_kisspackets.port, "
            . "qruqsp_tnc_kisspackets.command, "
            . "qruqsp_tnc_kisspackets.control, "
            . "qruqsp_tnc_kisspackets.protocol "
            . "FROM qruqsp_tnc_kisspackets "
            . "WHERE qruqsp_tnc_kisspackets.station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
            . "AND qruqsp_tnc_kisspackets.id = '" . qruqsp_core_dbQuote($q, $args['kisspacket_id']) . "' "
            . "";
        qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = qruqsp_core_dbHashQueryArrayTree($q, $strsql, 'qruqsp.tnc', array(
            array('container'=>'packets', 'fname'=>'id', 
                'fields'=>array('status', 'utc_of_traffic', 'port', 'command', 'control', 'protocol'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.103', 'msg'=>'KISS TNC Packet not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['packets'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.104', 'msg'=>'Unable to find KISS TNC Packet'));
        }
        $packet = $rc['packets'][0];
    }

    return array('stat'=>'ok', 'packet'=>$packet);
}
?>
