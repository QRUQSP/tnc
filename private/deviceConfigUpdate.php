<?php
//
// Description
// -----------
// This function will load device information from the database and update the config file for direwolf
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_tnc_deviceConfigUpdate(&$ciniki, $tnid, $device_id) {

    //
    // Get the device settings
    //
    $strsql = "SELECT qruqsp_tnc_devices.id, "
        . "qruqsp_tnc_devices.name, "
        . "qruqsp_tnc_devices.status, "
        . "qruqsp_tnc_devices.dtype, "
        . "qruqsp_tnc_devices.device, "
        . "qruqsp_tnc_devices.flags, "
        . "qruqsp_tnc_devices.settings "
        . "FROM qruqsp_tnc_devices "
        . "WHERE qruqsp_tnc_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND qruqsp_tnc_devices.id = '" . ciniki_core_dbQuote($ciniki, $device_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
        array('container'=>'devices', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'dtype', 'device', 'flags', 'settings'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.40', 'msg'=>'TNC Device not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['devices'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.41', 'msg'=>'Unable to find TNC Device'));
    }
    $device = $rc['devices'][0];

    //
    // Setup the default settings for the direwolf
    //
    $settings = array(
        'ADEVICE' => 'plughw:1,0',
        'ACHANNELS' => '1',
        'CHANNEL' => '0',
        'MYCALL' => 'QRUQSP',
        'MODEM' => '1200',
        'PTT' => 'GPIO 23',
        'DWAIT' => '0',
        'TXDELAY' => '10',
        'TXTAIL' => '10',
        );
    $db_settings = unserialize($device['settings']);
    foreach($db_settings as $k => $v) {
        $settings[$k] = $v;
    }

    //
    // Setup the config file
    //
    $config = '';
    foreach($settings as $k => $v) {
        $config .= $k . ' ' . $v . "\n";
    }

    //
    // Write the config file
    //
    $filename = $ciniki['config']['ciniki.core']['root_dir'] . '/direwolf-' . $device['id'] . '.conf';
    $rc = file_put_contents($filename, $config);
    if( $rc === false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.38', 'msg'=>'Unable to save config file'));
    }

    return array('stat'=>'ok');
}
?>
