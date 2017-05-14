<?php
//
// Description
// -----------
// This function will listen on a kisstnc pts for incoming packets and store them in the database
//
// Arguments
// ---------
// q:
// pts_handle:          The open file handle for the pts stream
// packet:              The start of the packet (0xc0).
//
function qruqsp_tnc_packetReceive($q, $station_id, $pts_handle, $packet) {
    //
    // Read 1 byte at a time until the 0xc0 marker is found
    //
    while( ($packed_byte = fread($pts_handle, 1)) !== null  ) {
        //
        // Add the byte to the binary string
        //
        $packet .= $packed_byte;

        //
        // Check for end of packet to stop
        //
        $byte = unpack('C', $packed_byte);
        if( $byte[1] == 0xc0 ) {
            break;
        }
    }

    return array('stat'=>'ok', 'packet'=>$packet);
}
?>
