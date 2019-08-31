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
function qruqsp_tnc_hooks_packetSend(&$ciniki, $tnid, $args) {

    //
    // Check for required packet fields
    //
    if( !isset($args['packet']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.3', 'msg'=>'No packet specified'));
    }
    $packet = $args['packet'];
    if( !isset($packet['addrs']) || !is_array($packet['addrs']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.9', 'msg'=>'No addresses specified'));
    }
    if( !isset($packet['control']) ) {
        $packet['control'] = 0x03;
    }
    if( !isset($packet['protocol']) ) {
        $packet['protocol'] = 0xf0;
    }
    if( !isset($packet['data']) || $packet['data'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.10', 'msg'=>'No data specified'));
    }

    //
    // Load the pts device from database
    //
    $strsql = "SELECT device "
        . "FROM qruqsp_tnc_devices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "LIMIT 1 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'device');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.21', 'msg'=>'Unable to find TNC', 'err'=>$rc['err']));
    }
    if( !isset($rc['device']['device']) || $rc['device']['device'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.22', 'msg'=>'No TNC running', 'err'=>$rc['err']));
    }
    $pts_filename = $rc['device']['device'];
    
    $bytes = '';

    //
    // Start the packet
    //
    $bytes .= pack('C*', 0xc0, 0x00);

    //
    // setup the packet callsigns: destination, source, digipeaters
    //
    $c = 1;
    foreach($packet['addrs'] as $addr) {
        list($callsign, $ssid) = explode('-', $addr . '-');
        for($i = 0; $i < 6; $i++) {
            if( $i < strlen($callsign) ) {
                $bytes .= pack('C', ord($callsign[$i])<<1);
            } else {
                $bytes .= pack('C', ord(' ')<<1);
            }
        }
        $end_bit = 0x00;
        if( $c == count($packet['addrs']) ) {
            $end_bit = 0x01;
        }
        if( isset($ssid) && $ssid != '' ) {
            $ssid = intval($ssid);
            $bytes .= pack('C', ((($ssid&0x0f)<<1)|$end_bit));
        } else {
            $bytes .= pack('C', $end_bit);
        }

        $c++;
    }

    //
    // Add the control field and protocol
    //
    $bytes .= pack('C*', $packet['control'], $packet['protocol']);

    //
    // Pack the data
    //
    for($i = 0; $i < strlen($packet['data']); $i++) {
        $byte = $packet['data'][$i];
        if( $byte == 0xc0 ) {
            $bytes .= pack('C*', 0xdb, 0xdc);
        } elseif( $byte == 0xdb ) {
            $bytes .= pack('C*', 0xdb, 0xdd);
        } else {
            $bytes .= pack('a', $byte);
        }
    }

    //
    // End the packet
    //
    $bytes .= pack('C', 0xc0);
   
    //
    // Check if the pts is a symlink, attach directly to device
    //
    if( is_link($pts_filename) ) {
        $pts_filename = readlink($pts_filename);
    }

    if( !file_exists($pts_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.15', 'msg'=>'Unable to open tnc'));
    }

    //
    // Write to the tnc
    //
    $pts_handle = fopen($pts_filename, 'wb');
    if( $pts_handle === false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.14', 'msg'=>'Unable to open tnc'));
    }
    //
    // Check if packet should be logged
    //
    if( isset($ciniki['config']['qruqsp.tnc']['packet.logging']) 
        && $ciniki['config']['qruqsp.tnc']['packet.logging'] == 'yes' 
        && isset($ciniki['config']['qruqsp.core']['log_dir'])
        && $ciniki['config']['qruqsp.core']['log_dir'] != '' 
        ) {
        $log_dir = $ciniki['config']['qruqsp.core']['log_dir'] . '/qruqsp.tnc';
        if( !file_exists($log_dir) ) {
            mkdir($log_dir);
        }

        $dt = new DateTime('now', new DateTimezone('UTC'));
        file_put_contents($log_dir . '/transmit.' . $dt->format('Y-m') . '.log',  
            '[' . $dt->format('d/M/Y:H:i:s O') . '] ' . $packet['data'] . "\n",
            FILE_APPEND);
    }

    $rc = fwrite($pts_handle, $bytes, strlen($bytes));
    if( $rc === false || $rc == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.16', 'msg'=>'Unable to write to tnc'));
    }
    fclose($pts_handle);

    return array('stat'=>'ok');
}
?>
