<?php
//
// Description
// -----------
// This function will load the TNC devices that should be active, and check to make sure a listener
// is running for each device.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_tnc_listenerCheck(&$ciniki, $tnid) {

    //
    // Load the list of TNC devices
    //
    $strsql = "SELECT id, name, status, device, flags "
        . "FROM qruqsp_tnc_devices "
        . "WHERE (status = 40 OR status = 60) "     // Should be running in either of these states
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'qruqsp.tnc', array(
        array('container'=>'devices', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'device', 'flags'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.34', 'msg'=>'Unable to load device', 'err'=>$rc['err']));
    }
    $devices = isset($rc['devices']) ? $rc['devices'] : array();
  
    //
    // Get the list of listeners running
    //
    exec('ps ax | grep direwolf_listen.php |grep -v grep ', $pids);
    foreach($pids as $details) {
        if( preg_match("/direwolf_listen.php ([0-9]+)/", $details, $m) ) {
            if( isset($devices[$m[1]]) ) {  
                $devices[$m[1]]['running'] = 'yes';
            }
        }
    }

    //
    // Check each device there is a listener running for it
    //
    foreach($devices as $device) {
       
        if( !isset($device['running']) || $device['running'] != 'yes' ) {
            //
            // Start the listener
            //
            $cmd = $ciniki['config']['qruqsp.core']['modules_dir'] . '/tnc/scripts/direwolf_listen.php ' . $device['id']; 
            $log_file = $ciniki['config']['qruqsp.core']['log_dir'] . '/direwolf-' . $device['id'] . '.log';
            error_log('starting ' . $device['name']);       
            exec('php ' . $cmd . ' >> ' . $log_file . ' 2>&1 &');
        }
    }

    return array('stat'=>'ok');
}
?>
