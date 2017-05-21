<?php
//
// Description
// -----------
// This function will decode a raw packet from TNC. When decoding packets from KISS TNC, there is not Flags or FCS at either 
// end of the packet.
//
// The packet format is:
// 1 byte  - 0xc0 - start of packet
// 1 byte  - 0x00 - port/command
// 7 bytes - Dest Callsign + SSID (each byte is shifted 1 left)
// 7 bytes - Source Callsign + SSID (each byte is shifted 1 left)
// 0-56 bytes - digipeater callsigns + ssids.
// 1 byte  - 0x03 - Control Field - UI Frame
// 1 byte  - 0xf0 - Protocol ID
// 1-256 bytes - Data
//
// Arguments
// ---------
// q:
// packet:              The start of the packet (0xc0).
//
function qruqsp_tnc_packetDecode($q, $station_id, $packet) {
    $pkt = array(
        'port' => 0,
        'command' => 0,
        'dest_callsign' => '',
        'dest_ssid' => '',
        'src_callsign' => '',
        'src_ssid' => '',
        'digipeaters' => array(),
        'data' => '',
        );

    //
    // The packet is stored as a binary string, so it must be unpacked into an array of bytes
    //
    $bytes = unpack('C*', $packet);
    $byte = array_shift($bytes);
    if( $byte != 0xc0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.99', 'msg'=>'Missing start of packet'));
    }

    //
    // The port/command
    //
    $byte = array_shift($bytes);
    $pkt['port'] = $byte >> 8;
    $pkt['command'] = $byte & 0x0f;

    //
    // Get the destination callsign and SSID
    //
    for($i = 0; $i < 6; $i++) {
        $byte = array_shift($bytes);
        $pkt['dest_callsign'] .= chr($byte >> 1);
    }
    $byte = array_shift($bytes);
    $pkt['dest_ssid'] .= chr($byte);

    //
    // Get the source callsign and SSID
    //
    for($i = 0; $i < 6; $i++) {
        $byte = array_shift($bytes);
        $pkt['src_callsign'] .= chr($byte >> 1);
    }
    $byte = array_shift($bytes);
    $pkt['src_ssid'] .= chr($byte >> 1);

    //
    // Parse the digipeaters
    //
    while( $bytes[0] != 0x03 ) {
        $digipeater = array('callsign' => '', 'ssid' => '');
        for($i = 0; $i < 6; $i++) {
            $byte = array_shift($bytes);
            $digipeater['callsign'] .= chr($byte >> 1);
        }
        $byte = array_shift($bytes);
        $digipeater['ssid'] .= chr($byte >> 1);
        $pkt['digipeaters'][] = $digipeater;
    }

    //
    // Control field
    //
    $byte = array_shift($bytes);
    $pkt['control'] = $byte;

    //
    // Protocol ID
    //
    $byte = array_shift($bytes);
    $pkt['protocol'] = $byte;
   
    while( ($byte = array_shift($bytes)) !== null ) {
        printf("%2x %3d %08s  [%1c]\n", $byte, $byte, decbin($byte), ($byte > 13 ? $byte : ''));
//            $rbyte, $rbyte, decbin($rbyte), ($rbyte > 27 ? $rbyte : ''));
        //
        // Check for an escape frame
        //
        if( $byte == 0xdb ) {
            $byte == array_shift($bytes);
            if( $byte == 0xdc ) {
                $pkt['data'][] = 0xc0;
            } elseif( $byte == 0xdd ) {
                $pkt['data'][] = 0xdb;
            }
        } 
        //
        // End of the packet
        //
        elseif( $byte == 0xc0 ) {
            break;
        }
        //
        // Add to data
        //
        else {
            $pkt['data'][] = $byte;
        }
    }

    return array('stat'=>'ok', 'packet'=>$pkt);
}
?>
