<?php
//
// Description
// -----------
// This function will listen on a kisstnc pts for incoming packets and store them in the database
//
// Arguments
// ---------
// q:
// pts:         The filename of the pts device to listen on
//
function qruqsp_tnc_listen($ciniki, $tnid, $pts) {
    
    //
    // Load required functions
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetReceive');
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetStoreProcess');

    //
    // Check the pts exists
    //
    if( !file_exists($pts) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.28', 'msg'=>"Missing $pts file"));
        exit;
    }

    //
    // Open the pts for reading binary
    //
    $pts_handle = fopen($pts, "rb");

    //
    // Set the loop to not exit yet
    //
    $exit = 'no';
    while( $exit == 'no' ) {
        //
        // Read in a packed byte
        //
        $packed_byte = fread($pts_handle, 1);

        //
        // Break the loop if end of file, means direwolf has stopped
        //
        if( feof($pts_handle) ) {
            break;
        }

        //
        // Unpack the byte to an char/int
        //
        $byte = unpack('C', $packed_byte);

        //
        // Check if the boundary for a packet
        //
        if( $byte[1] == 0xc0 ) {
            $rc = qruqsp_tnc_packetReceive($ciniki, $tnid, $pts_handle, $packed_byte);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['packet']) ) {
                //
                // Fork the process and store and process the packet
                //
/*                $pid = pcntl_fork();
                if( $pid == -1 ) {
                    print "ERR: Unable to fork\n";
                    exit;
                } elseif( $pid == 0 ) {
                    $rc = qruqsp_tnc_packetStoreProcess($ciniki, $tnid, $rc['packet']);
                    if( $rc['stat'] != 'ok' ) {
                        print "ERR: Unable to store packet\n";
                        print_r($rc);
                    }
                    exit;
                } */
            }
        }
    }

    return array('stat'=>'ok');
}
?>
