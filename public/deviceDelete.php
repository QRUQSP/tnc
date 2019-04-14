<?php
//
// Description
// -----------
// This method will delete an tnc device.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the tnc device is attached to.
// device_id:            The ID of the tnc device to be removed.
//
// Returns
// -------
//
function qruqsp_tnc_deviceDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'device_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'TNC Device'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'ciniki.tnc.deviceDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the tnc device
    //
    $strsql = "SELECT id, uuid "
        . "FROM qruqsp_tnc_devices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['device_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tnc', 'device');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['device']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.30', 'msg'=>'TNC Device does not exist.'));
    }
    $device = $rc['device'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'qruqsp.tnc.device', $args['device_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.31', 'msg'=>'Unable to check if the tnc device is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.32', 'msg'=>'The tnc device is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.tnc');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the device
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.tnc.device',
        $args['device_id'], $device['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tnc');
        return $rc;
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

    return array('stat'=>'ok');
}
?>
