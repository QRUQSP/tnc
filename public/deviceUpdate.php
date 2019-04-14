<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_tnc_deviceUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'device_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'TNC Device'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
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
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.deviceUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current settings
    //
    $strsql = "SELECT qruqsp_tnc_devices.id, "
        . "qruqsp_tnc_devices.name, "
        . "qruqsp_tnc_devices.status, "
        . "qruqsp_tnc_devices.dtype, "
        . "qruqsp_tnc_devices.device, "
        . "qruqsp_tnc_devices.flags, "
        . "qruqsp_tnc_devices.settings "
        . "FROM qruqsp_tnc_devices "
        . "WHERE qruqsp_tnc_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND qruqsp_tnc_devices.id = '" . ciniki_core_dbQuote($ciniki, $args['device_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'device');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.19', 'msg'=>'Unable to load device', 'err'=>$rc['err']));
    }
    if( !isset($rc['device']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.20', 'msg'=>'Unable to find requested device'));
    }
    $device = $rc['device'];
    $settings = unserialize($device['settings']);

    //
    // Check if any updates
    //
    foreach($settings as $k => $v) {
        if( isset($ciniki['request']['args']['settings.' . $k]) ) {
            $settings[$k] = $ciniki['request']['args']['settings.' . $k];
        }
    }
    $args['settings'] = serialize($settings);
    if( $args['settings'] == $device['settings'] ) {
        unset($args['settings']);
    }

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
    // Update the TNC Device in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.tnc.device', $args['device_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tnc');
        return $rc;
    }

    //
    // Update the config
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'deviceConfigUpdate');
    $rc = qruqsp_tnc_deviceConfigUpdate($ciniki, $args['tnid'], $args['device_id']);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.tnc.device', 'object_id'=>$args['device_id']));

    return array('stat'=>'ok');
}
?>
