<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an tnc device.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// device_id:          The ID of the tnc device to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
//
function qruqsp_tnc_deviceHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'device_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'TNC Device'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.deviceHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'qruqsp.tnc', 'qruqsp_tnc_history', $args['tnid'], 'qruqsp_tnc_devices', $args['device_id'], $args['field']);
}
?>
