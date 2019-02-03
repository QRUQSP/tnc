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
    if( !isset($args['source_callsign']) || $args['source_callsign'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.3', 'msg'=>'No source callsign'));
    }
    if( !isset($args['destination_callsign']) || $args['destination_callsign'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.4', 'msg'=>'No destination callsign'));
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
    $args['destination_callsign'] = strtoupper($args['destination_callsign']);
//    if( !isset($args['next_callsign']) || $args['next_callsign'] == '' ) {
//        $args['next_callsign'] = strtoupper($args['destination_callsign']);
//    }
    $args['source_callsign'] = strtoupper($args['source_callsign']);

    $str = '';

    //
    // Start the packet
    //
    $str .= pack('C*', 0xC0, 0x00);

    if( isset($args['path']) ) {
        $addrs = explode(',', $args['path']);
    } else {
        $addrs = array();
    }
    array_unshift($addrs, $args['destination_callsign'], $args['source_callsign']);

    //
    // setup the packet callsigns: destination, source, digipeaters
    //
    $c = 1;
    foreach($addrs as $addr) {
        list($callsign, $ssid) = explode('-', $addr . '-');
        for($i = 0; $i < 6; $i++) {
            if( $i < strlen($callsign) ) {
                $str .= pack('C', ord($callsign[$i])<<1);
            } else {
                $str .= pack('C', ord(' ')<<1);
            }
        }
        $end_bit = 0x00;
        if( $c == count($addrs) ) {
            $end_bit = 0x01;
        }
        if( isset($ssid) && $ssid != '' ) {
            $ssid = intval($ssid);
            $str .= pack('C', ((($ssid&0x0f)<<1)|$end_bit));
        } else {
            $str .= pack('C', $end_bit);
        }

        $c++;
    }

    //
    // Add the control field and protocol
    //
    $str .= pack('C*', 0x03, 0xf0);

    //
    // Setup message in APRS format
    //
    $message = sprintf(":%-9s:%s", $args['destination_callsign'], $args['content']);
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
