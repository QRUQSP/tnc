<?php
//
// Description
// -----------
// This function will check if the user has access to the  module.
//
// Arguments
// ---------
// q:
// station_id:                  The ID of the station to check the session user against.
// method:                      The requested method.
//
function qruqsp_tnc_checkAccess(&$q, $station_id, $method) {
    //
    // Check if the station is active and the module is enabled
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'checkModuleAccess');
    $rc = qruqsp_core_checkModuleAccess($q, $station_id, array('package'=>'qruqsp', 'module'=>'tnc'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Sysadmins are allowed full access
    //
    if( ($q['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok');
    }

    //
    // Check to makes sure the session user is a station operator
    //
    $strsql = "SELECT station_id, user_id "
        . "FROM qruqsp_core_station_users "
        . "WHERE station_id = '" . qruqsp_core_dbQuote($q, $station_id) . "' "
        . "AND user_id = '" . qruqsp_core_dbQuote($q, $q['session']['user']['id']) . "' "
        . "AND status = 10 "
        . "AND permission_group = 'operators' "
        . "";
    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'dbHashQuery');
    $rc = qruqsp_core_dbHashQuery($q, $strsql, 'qruqsp.core', 'user');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.1', 'msg'=>'Access denied.'));
    }
    //
    // If the user has permission, return ok
    //
    if( isset($rc['rows']) && isset($rc['rows'][0])
        && $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $q['session']['user']['id'] ) {
        return array('stat'=>'ok');
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.2', 'msg'=>'Access denied'));
}
?>
