<?php
//
// Description
// -----------
// This method will return the list of TNC Devices for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get TNC Device for.
//
// Returns
// -------
//
function qruqsp_tnc_deviceList($ciniki) {
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
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.deviceList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'maps');
    $rc = qruqsp_tnc_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of devices
    //
    $strsql = "SELECT qruqsp_tnc_devices.id, "
        . "qruqsp_tnc_devices.name, "
        . "qruqsp_tnc_devices.status, "
        . "qruqsp_tnc_devices.status AS status_text, "
        . "qruqsp_tnc_devices.dtype, "
        . "qruqsp_tnc_devices.dtype AS dtype_text, "
        . "qruqsp_tnc_devices.device, "
        . "qruqsp_tnc_devices.flags "
        . "FROM qruqsp_tnc_devices "
        . "WHERE qruqsp_tnc_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
        array('container'=>'devices', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'status_text', 'dtype', 'dtype_text', 'device', 'flags'),
            'maps'=>array('status_text'=>$maps['device']['status'],
                'dtype_text'=>$maps['device']['dtype'],
                ),
            ),
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
