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
function qruqsp_tnc_packetDecode($ciniki, $tnid, $p) {
    //
    // Make a copy of the packet object to compare later to see what needs to be updated
    //
    if( is_array($p) ) {
        $pkt = $p; 
    } elseif( is_numeric($p) ) {
        //
        // FIXME: Add code to load packet from database
        //
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.22', 'msg'=>'Missing code'));
    }

    //
    // Set addrs and data base to empty
    //
    $pkt['addrs'] = array();
    $pkt['addresses'] = '';
    $pkt['data'] = '';

    //
    // The packet is stored as a binary string, so it must be unpacked into an array of bytes
    //
    $bytes = unpack('C*', $pkt['raw_packet']);
    $byte = array_shift($bytes);
    if( $byte != 0xc0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.23', 'msg'=>'Missing start of packet'));
    }

    //
    // The port/command
    //
    $byte = array_shift($bytes);
    $pkt['port'] = $byte >> 4;
    $pkt['command'] = $byte & 0x0f;

    //
    // Decode the addresses
    //
    $c = 0;
    $atype = 10;
    while( isset($bytes[0]) && $bytes[0] != 0x03 ) {
        $addr = array(
            'packet_id' => $pkt['id'],
            'atype' => $atype,
            'sequence' => $c, 
            'flags' => 0,
            'callsign' => '', 
            'ssid' => 0, 
            );
        for($i = 0; $i < 6; $i++) {
            $byte = array_shift($bytes);
            $addr['callsign'] .= chr($byte >> 1);
        }
        $addr['callsign'] = trim($addr['callsign']);
        $byte = array_shift($bytes);
        $addr['flags'] = ($byte>>5)&0x07;
        $addr['ssid'] = ($byte>>1)&0x0f;
        $pkt['addrs'][] = $addr;
        if( $atype > 10 ) {
            $pkt['addresses'] .= ($pkt['addresses'] != '' ? ' > ' : '') . $addr['callsign'] . ($addr['ssid'] > 0 ? '-' . $addr['ssid'] : '');
        }

        //
        // The last bit of last byte is 1, then this is the last address.
        //
        if( ($byte&0x01) == 0x01 ) {
            break;
        }
        $c++;

        //
        // Setup the next type of address
        //
        if( $atype == 10 ) {
            $atype = 20;
        } elseif( $atype == 20 ) {
            $atype = 30;
        }
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
  
    //
    // Save the data of the packet
    //
    while( ($byte = array_shift($bytes)) !== null ) {
//        printf("%2x %3d %08s  [%1c]\n", $byte, $byte, decbin($byte), ($byte > 13 ? $byte : ''));
//            $rbyte, $rbyte, decbin($rbyte), ($rbyte > 27 ? $rbyte : ''));
        //
        // Check for an escape frame
        //
        if( $byte == 0xdb ) {
            $byte == array_shift($bytes);
            if( $byte == 0xdc ) {
                $pkt['data'] .= chr(0xc0);
            } elseif( $byte == 0xdd ) {
                $pkt['data'] .= chr(0xdb);
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
            $pkt['data'] .= chr($byte);
        }
    }

    $pkt['status'] = 20;

    //
    // Update the database with packet data
    //
    $fields = array('status', 'port', 'command', 'control', 'protocol', 'addresses', 'data');
    $update_args = array();
    foreach($fields as $f) {
        if( !isset($p[$f]) || $pkt[$f] != $p[$f] ) {
            $update_args[$f] = $pkt[$f];
        }
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    if( count($update_args) > 0 ) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.tnc.kisspacket', $pkt['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update any addresses
    //
    foreach($pkt['addrs'] as $addr) {
        //
        // Check to make sure the address doesn't exist
        //
        $exists = 'no';
        if( isset($p['addrs']) ) {
            foreach($p['addrs'] as $a) {
                //
                // Check if the address already exists for the sequence
                //
                if( $addr['sequence'] == $a['sequence'] ) {
                    $exists = 'yes';
                    //
                    // If the address sequence already exists, update with new values
                    //
                    $update_args = array();
                    $fields = array('atype', 'sequence', 'flags', 'callsign', 'ssid');
                    foreach($fields as $f) {
                        if( !isset($a[$f]) || $addr[$f] != $a[$f] ) {
                            $update_args[$f] = $addr[$f];
                        }
                    }
                    if( count($update_args) > 0 ) {
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.tnc.kisspacketaddr', $addr['id'], $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                    }
                }
            }
        }

        //
        // Add the address
        //
        if( $exists == 'no' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'qruqsp.tnc.kisspacketaddr', $addr, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check for hooks to receive packet
    //
    foreach($ciniki['tenant']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'packetReceived');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, array('packet' => $pkt));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.7', 'msg'=>'Error processing packet', 'err'=>$rc['err']));
            }

        }
    }

    return array('stat'=>'ok', 'packet'=>$pkt);
}
?>
