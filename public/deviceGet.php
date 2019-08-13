<?php
//
// Description
// ===========
// This method will return all the information about an tnc device.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the tnc device is attached to.
// device_id:          The ID of the tnc device to get the details for.
//
// Returns
// -------
//
function qruqsp_tnc_deviceGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'device_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'TNC Device'),
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
    $rc = qruqsp_tnc_checkAccess($ciniki, $args['tnid'], 'qruqsp.tnc.deviceGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new TNC Device
    //
    if( $args['device_id'] == 0 ) {
        $device = array('id'=>0,
            'name'=>'',
            'status'=>'10',
            'dtype'=>'10',
            'device'=>'',
            'flags'=>'0',
            'settings.ADEVICE'=>'plughw:1,0',
            'settings.PTT'=>'GPIO 24',
        );
    }

    //
    // Get the details for an existing TNC Device
    //
    else {
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
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tnc', array(
            array('container'=>'devices', 'fname'=>'id', 
                'fields'=>array('name', 'status', 'dtype', 'device', 'flags', 'settings'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.36', 'msg'=>'TNC Device not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['devices'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.37', 'msg'=>'Unable to find TNC Device'));
        }
        $device = $rc['devices'][0];
        $settings = unserialize($device['settings']);
        foreach($settings as $k => $v) {
            $device['settings.' . $k] = $v;
        }
    }

    return array('stat'=>'ok', 'device'=>$device);
}
?>
