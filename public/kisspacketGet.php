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
// tnid:               The ID of the tenant the kiss tnc packet is attached to.
// kisspacket_id:          The ID of the kiss tnc packet to get the details for.
//
function qruqsp_tnc_kisspacketGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'kisspacket_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'KISS TNC Packet'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.kisspacketGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

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
            . "WHERE qruqsp_tnc_kisspackets.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_tnc_kisspackets.id = '" . ciniki_core_dbQuote($ciniki, $args['kisspacket_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
            array('container'=>'packets', 'fname'=>'id', 
                'fields'=>array('status', 'utc_of_traffic', 'port', 'command', 'control', 'protocol'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.24', 'msg'=>'KISS TNC Packet not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['packets'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.25', 'msg'=>'Unable to find KISS TNC Packet'));
        }
        $packet = $rc['packets'][0];
    }

    return array('stat'=>'ok', 'packet'=>$packet);
}
?>
