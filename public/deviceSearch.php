<?php
//
// Description
// -----------
// This method searchs for a TNC Devices for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get TNC Device for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function qruqsp_tnc_deviceSearch($ciniki) {
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
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.deviceSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of devices
    //
    $strsql = "SELECT qruqsp_tnc_devices.id, "
        . "qruqsp_tnc_devices.name, "
        . "qruqsp_tnc_devices.status, "
        . "qruqsp_tnc_devices.dtype, "
        . "qruqsp_tnc_devices.device, "
        . "qruqsp_tnc_devices.flags "
        . "FROM qruqsp_tnc_devices "
        . "WHERE qruqsp_tnc_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
        array('container'=>'devices', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'dtype', 'device', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['devices']) ) {
        $devices = $rc['devices'];
        $device_ids = array();
        foreach($devices as $iid => $device) {
            $device_ids[] = $device['id'];
        }
    } else {
        $devices = array();
        $device_ids = array();
    }

    return array('stat'=>'ok', 'devices'=>$devices, 'nplist'=>$device_ids);
}
?>
