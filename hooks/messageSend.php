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
    error_log('to: ' . $callsign . strlen($callsign));
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
    error_log('from: ' . $callsign . strlen($callsign));
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
//        $str .= pack('C', 0x01);
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
    error_log($args['content']);
    $message = sprintf(":%-9s:%s", $args['to_callsign'], $args['content']);
    error_log($message);
    $str .= pack('a*', $message);

    //
    // End the packet
    //
    $str .= pack('C', 0xC0);
    
    error_log(file_put_contents('/tmp/str', $str));
    error_log(file_put_contents('/ciniki/sites/qruqsp.local/logs/str', $str));

    if( is_link($ciniki['config']['qruqsp.tnc']['pts']) ) {
        $ciniki['config']['qruqsp.tnc']['pts'] = readlink($ciniki['config']['qruqsp.tnc']['pts']);
    }

    if( !file_exists($ciniki['config']['qruqsp.tnc']['pts']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.sams.15', 'msg'=>'Unable to open tnc'));
    }

    //
    // Write to the tnc
    //
    error_log('sending packet: ' . $ciniki['config']['qruqsp.tnc']['pts']);

    //$pts_handle = fopen($ciniki['config']['qruqsp.tnc']['pts'], 'wb');
    $pts_handle = fopen('/dev/pts/4', 'wb');
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
