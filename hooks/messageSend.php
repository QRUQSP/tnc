<?php
//
// Description
// -----------
// Send a message via APRS
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_tnc_hooks_messageSend(&$ciniki, $tnid, $args) {

    //
    // Check for required args
    //
    if( !isset($args['from_callsign']) || $args['from_callsign'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.3', 'msg'=>'No from callsign'));
    }
    if( !isset($args['to_callsign']) || $args['to_callsign'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.4', 'msg'=>'No to callsign'));
    }
    if( !isset($args['content']) || $args['content'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.5', 'msg'=>'No content'));
    }

    //
    // Make sure pts is defined in config file
    //
    if( !isset($ciniki['config']['qruqsp.tnc']['pts']) || $ciniki['config']['qruqsp.tnc']['pts'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.6', 'msg'=>'No tnc defined in config.'));
    }

    //
    // Make sure callsigns are uppercase
    //
    $args['to_callsign'] = strtoupper($args['to_callsign']);
    if( !isset($args['next_callsign']) || $args['next_callsign'] == '' ) {
        $args['next_callsign'] = strtoupper($args['to_callsign']);
    }
    $args['from_callsign'] = strtoupper($args['from_callsign']);

    $str = '';

    //
    // Start the packet
    //
    $str .= pack('C*', 0xC0, 0x00);

    //
    // Parse the callsign of the next callsign to send to
    //
    list($callsign, $ssid) = explode('-', $args['next_callsign'] . '-');
    for($i = 0; $i < 6; $i++) {
        if( $i < strlen($callsign) ) {
            $str .= pack('C', ord($callsign[$i])<<1);
        } else {
            $str .= pack('C', ord(' ')<<1);
        }
    }
    if( isset($ssid) && $ssid != '' ) {
        $ssid = intval($ssid);
        $str .= pack('C', ($ssid&0x0f)<<1);
    } else {
        $str .= pack('C', 0x00);
    }
    //
    // Parse the from callsign
    //
    list($callsign, $ssid) = explode('-', $args['from_callsign'] . '-');
    for($i = 0; $i < 6; $i++) {
        if( $i < strlen($callsign) ) {
            $str .= pack('C', ord($callsign[$i])<<1);
        } else {
            $str .= pack('C', ord(' ')<<1);
        }
    }
    if( isset($ssid) && $ssid != '' ) {
        $ssid = intval($ssid);
        // Add 0x01 to signal the end of the callsigns
        // FIXME: Remove 0x01 when digipeater addresses are included
        $str .= pack('C', ((($ssid&0x0f)<<1)|0x01) );
    } else {
        $str .= pack('C', 0x01);
    }
    
    //
    // Add the control field and protocol
    //
    $str .= pack('C*', 0x03, 0xf0);

    //
    // Setup message in APRS format
    //
    $message = sprintf(":%-9s:%s", $args['to_callsign'], $args['content']);
    $str .= pack('a*', $message);

    //
    // End the packet
    //
    $str .= pack('C', 0xC0);
    
    $pts_filename = $ciniki['config']['qruqsp.tnc']['pts'];

    if( is_link($pts_filename) ) {
        error_log('symlink');
        $pts_filename = readlink($pts_filename);
    } else {
        error_log('no link');
    }

    if( !file_exists($pts_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.15', 'msg'=>'Unable to open tnc'));
    }

    //
    // Write to the tnc
    //
    $pts_handle = fopen($pts_filename, 'wb');
    if( $pts_handle === false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.14', 'msg'=>'Unable to open tnc'));
    }
    $rc = fwrite($pts_handle, $str, strlen($str));
    if( $rc === false || $rc == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.16', 'msg'=>'Unable to write to tnc'));
    }
    fclose($pts_handle);


    return array('stat'=>'ok');
}
?>
