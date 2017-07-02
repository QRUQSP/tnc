<?php
//
// Description
// -----------
// This method will delete an kiss tnc packet.
//
// Arguments
// ---------
// api_key:
// auth_token:
// station_id:            The ID of the station the kiss tnc packet is attached to.
// kisspacket_id:            The ID of the kiss tnc packet to be removed.
//
function qruqsp_tnc_kisspacketDelete(&$q) {
    //
    // Find all the required and optional arguments
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'prepareArgs');
    $rc = qruqsp_core_prepareArgs($q, 'no', array(
        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),
        'kisspacket_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'KISS TNC Packet'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to station_id as owner
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'checkAccess');
    $rc = qruqsp_tnc_checkAccess($q, $args['station_id'], 'qruqsp.tnc.kisspacketDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the kiss tnc packet
    //
    $strsql = "SELECT id, uuid "
        . "FROM qruqsp_tnc_kisspackets "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $args['station_id']) . "' "
        . "AND id = '" . qruqsp_core_dbQuote($q, $args['kisspacket_id']) . "' "
        . "";
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.tnc', 'packet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['packet']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.100', 'msg'=>'KISS TNC Packet does not exist.'));
    }
    $packet = $rc['packet'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectCheckUsed');
    $rc = qruqsp_core_objectCheckUsed($q, $args['station_id'], 'qruqsp.tnc.kisspacket', $args['kisspacket_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.101', 'msg'=>'Unable to check if the kiss tnc packet is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.102', 'msg'=>'The kiss tnc packet is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionStart');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbDelete');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectDelete');
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');
    $rc = qruqsp_core_dbTransactionStart($q, 'qruqsp.tnc');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the packet
    //
    $rc = qruqsp_core_objectDelete($q, $args['station_id'], 'qruqsp.tnc.kisspacket',
        $args['kisspacket_id'], $packet['uuid'], 0x04);
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

    return array('stat'=>'ok');
}
?>
