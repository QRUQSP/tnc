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
// Initialize QRUQSP by including the ciniki-api.ini
//
$start_time = microtime(true);
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}

require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');


//
// Initialize Q
//
$rc = ciniki_core_init($ciniki_root, 'json');
if( $rc['stat'] != 'ok' ) {
    print "ERR: Unable to initialize Q\n";
    exit;
}

//
// Setup the $ciniki variable to hold all things qruqsp.  
//
$ciniki = $rc['ciniki'];

//
// Check for direwolf 
//
if( !isset($ciniki['config']['qruqsp.tnc']['pts']) ) {
    print "ERR: No TNC pts specified\n";
    exit;
}
$pts = $ciniki['config']['qruqsp.tnc']['pts'];

//
// Determine which tnid to use
//
$tnid = $ciniki['config']['ciniki.core']['master_tnid'];
if( isset($ciniki['config']['qruqsp.tnc']['tnid']) ) {
    $tnid = $ciniki['config']['qruqsp.tnc']['tnid'];
}

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'cron', 'private', 'logMsg');

//
// Load tenant modules
//
$strsql = "SELECT ciniki_tenants.status AS tenant_status, "
    . "ciniki_tenant_modules.status AS module_status, "
    . "ciniki_tenant_modules.package, ciniki_tenant_modules.module, "
    . "CONCAT_WS('.', ciniki_tenant_modules.package, ciniki_tenant_modules.module) AS module_id, "
    . "ciniki_tenant_modules.flags, "
    . "(ciniki_tenant_modules.flags&0xFFFFFFFF00000000)>>32 as flags2, "
    . "ciniki_tenant_modules.ruleset "
    . "FROM ciniki_tenants, ciniki_tenant_modules "
    . "WHERE ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "AND ciniki_tenants.id = ciniki_tenant_modules.tnid "
    // Get the options and mandatory module
    . "AND (ciniki_tenant_modules.status = 1 || ciniki_tenant_modules.status = 2 || ciniki_tenant_modules.status = 90) "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'module_id');
if( $rc['stat'] != 'ok' ) {
    return $rc;
}
if( !isset($rc['modules']) ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tnc.21', 'msg'=>'No modules enabled'));
}
$ciniki['tenant']['modules'] = $rc['modules'];

//
// Parent process
//
if( $pid > 0 ) {
    //
    // Load required functions
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetsDecode');

    pcntl_sigprocmask(SIG_BLOCK, array(SIGUSR1));
    while( $signo = pcntl_sigwaitinfo(array(SIGUSR1)) ) {
        $rc = qruqsp_tnc_packetsDecode($ciniki, $tnid, array());
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
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetReceive');
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetStore');

    //
    // Check the pts exists
    //
    if( !file_exists($pts) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.26', 'msg'=>"Missing $pts file"));
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
            $rc = qruqsp_tnc_packetReceive($ciniki, $tnid, $pts_handle, $packed_byte);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['packet']) ) {
                //
                // Store the packet
                //
                $rc = qruqsp_tnc_packetStore($ciniki, $tnid, $rc['packet']);
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
