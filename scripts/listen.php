#!/usr/bin/php
<?php
//
// Description
// -----------
// This script will listen on a pts for kiss tnc
//

$parent_pid = getmypid();
$pid = pcntl_fork();

//
// Initialize QRUQSP by including the qruqsp_api.php
//
$start_time = microtime(true);
global $qruqsp_root;
$qruqsp_root = dirname(__FILE__);
if( !file_exists($qruqsp_root . '/qruqsp-api.ini') ) {
    $qruqsp_root = dirname(dirname(dirname(dirname(__FILE__))));
}

require_once($qruqsp_root . '/qruqsp-mods/core/private/loadMethod.php');
require_once($qruqsp_root . '/qruqsp-mods/core/private/init.php');


//
// Initialize Q
//
$rc = qruqsp_core_init($qruqsp_root, 'json');
if( $rc['stat'] != 'ok' ) {
    print "ERR: Unable to initialize Q\n";
    exit;
}

//
// Setup the $qruqsp variable to hold all things qruqsp.  
//
$q = $rc['q'];

//
// Check for direwolf 
//
if( !isset($q['config']['qruqsp.tnc']['pts']) ) {
    print "ERR: No TNC pts specified\n";
    exit;
}
$pts = $q['config']['qruqsp.tnc']['pts'];

//
// Parent process
//
if( $pid > 0 ) {
    //
    // Load required functions
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'packetsDecode');

    pcntl_sigprocmask(SIG_BLOCK, array(SIGUSR1));
    while( $signo = pcntl_sigwaitinfo(array(SIGUSR1)) ) {
        $rc = qruqsp_tnc_packetsDecode($q, $q['config']['qruqsp.tnc']['station_id'], array());
    }

    pcntl_waitpid($pid, $status);
} 

//
// Child process for decoding packets
//
else {
    //
    // Load required functions
    //
    qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'packetReceive');
    qruqsp_core_loadMethod($q, 'qruqsp', 'tnc', 'private', 'packetStore');

    //
    // Check the pts exists
    //
    if( !file_exists($pts) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.1', 'msg'=>"Missing $pts file"));
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
            //
            // Receive the packet
            //
            $rc = qruqsp_tnc_packetReceive($q, $q['config']['qruqsp.tnc']['station_id'], $pts_handle, $packed_byte);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['packet']) ) {
                //
                // Store the packet
                //
                $rc = qruqsp_tnc_packetStore($q, $q['config']['qruqsp.tnc']['station_id'], $rc['packet']);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }

                //
                // Signal the child process to run the decoder on new packets
                //
                posix_kill($parent_pid, SIGUSR1);
            }
        }
    }
}

exit;
?>
