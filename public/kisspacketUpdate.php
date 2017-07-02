<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
function qruqsp_tnc_kisspacketUpdate(&$q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),
        'kisspacket_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'KISS TNC Packet'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'utc_of_traffic'=>array('required'=>'no', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Time'),
        'raw_packet'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Raw Packet'),
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
    // Make sure this module is activated, and
    // check permission to run this function for this station
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($q, $args['station_id'], 'qruqsp.tnc.kisspacketUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $rc = qruqsp_core_dbTransactionStart($q, 'qruqsp.tnc');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the KISS TNC Packet in the database
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectUpdate');
    $rc = qruqsp_core_objectUpdate($q, $args['station_id'], 'qruqsp.tnc.kisspacket', $args['kisspacket_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        qruqsp_core_dbTransactionRollback($q, 'qruqsp.tnc');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = qruqsp_core_dbTransactionCommit($q, 'qruqsp.tnc');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the station modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'updateModuleChangeDate');
    qruqsp_core_updateModuleChangeDate($q, $args['station_id'], 'qruqsp', 'tnc');

    //
    // Update the web index if enabled
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'hookExec');
    qruqsp_core_hookExec($q, $args['station_id'], 'qruqsp', 'web', 'indexObject', array('object'=>'qruqsp.tnc.kisspacket', 'object_id'=>$args['kisspacket_id']));

    return array('stat'=>'ok');
}
?>
