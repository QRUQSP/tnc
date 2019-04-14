<?php
//
// Description
// -----------
// This method will add a new tnc device for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the TNC Device to.
//
// Returns
// -------
//
function qruqsp_tnc_deviceAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'dtype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Device Type'),
        'device'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Device'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.deviceAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup the settings array
    //
    $settings = array(
        'ADEVICE' => 'plughw:1,0',
        'PTT' => 'GPIO 23',
        );
    foreach($settings as $k => $v) {
        if( isset($ciniki['request']['args']['settings.' . $k]) ) {
            $settings[$k] = $ciniki['request']['args']['settings.' . $k];
        }
    }
    $args['settings'] = serialize($settings);

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.tnc');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the tnc device to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'qruqsp.tnc.device', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tnc');
        return $rc;
    }
    $device_id = $rc['id'];

    //
    // Update the config
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'deviceConfigUpdate');
    $rc = qruqsp_tnc_deviceConfigUpdate($ciniki, $args['tnid'], $device_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.33', 'msg'=>'Unable to update config file', 'err'=>$rc['err']));
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.tnc');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', 'tnc');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.tnc.device', 'object_id'=>$device_id));

    return array('stat'=>'ok', 'id'=>$device_id);
}
?>
