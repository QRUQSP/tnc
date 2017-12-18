<?php
//
// Description
// -----------
// This method will add a new kiss tnc packet for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:              The ID of the tenant to add the KISS TNC Packet to.
//
function qruqsp_tnc_kisspacketAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'),
        'utc_of_traffic'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Time'),
        'raw_packet'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Raw Packet'),
        'port'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Port'),
        'command'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Command'),
        'control'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Command'),
        'protocol'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Command'),
        'data'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Data'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.kisspacketAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Add the kiss tnc packet to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'qruqsp.tnc.kisspacket', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tnc');
        return $rc;
    }
    $kisspacket_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'qruqsp', 'web', 'indexObject', array('object'=>'qruqsp.tnc.kisspacket', 'object_id'=>$kisspacket_id));

    return array('stat'=>'ok', 'id'=>$kisspacket_id);
}
?>
