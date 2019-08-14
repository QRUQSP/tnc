#!/usr/bin/php
<?php
//
// Description
// -----------
// This script will listen on a pts for kiss tnc
//

//
// Initialize QRUQSP by including the ciniki-api.ini
//
$start_time = microtime(true);
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}

//
// Check to make sure the device id was passed as an argument
//
if( !isset($argv[1]) || $argv[1] == '' || !is_numeric($argv[1]) ) {
    print "No device specified\n";
    exit;
}
$device_id = $argv[1];

//
// Check to make sure script is not already running
//
exec('ps ax | grep "direwolf_listen.php ' . $argv[1] . '" |grep -v grep ', $pids);
$parent_pid = getmypid();
foreach($pids as $details) {
    //
    // If any of the pids do not match our process id, then direwolf is already running
    //
    if( !preg_match("/^\s*" . $parent_pid . "\s/", $details) ) {
        print "Listener already running for device " . $argv[1] . "\n";
        exit;
    }
}

//
// Check for config file for the specified device
//
$config_file = $ciniki_root . '/direwolf-' . $argv[1] . '.conf';
if( !file_exists($config_file) ) {
    print "No config file for direwolf device\n";
    exit;
}

//
// Fork
//


//
// Parent - run direwolf
// - save psuedo terinal to database
// - signal child to listen on the pts
// 

//
// Child - listen on virtual tnc
//

// FIXME: Save device file 

error_log('Starting direwolf');
//sleep(600);
//exit;

//
// The fork must happen before the Ciniki Init, otherwise same database connection 
// handle gets forked and screws up
//
$pid = pcntl_fork();

require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');

//
// Initialize Ciniki after fork, initializes unique instance for each process
//
$rc = ciniki_core_init($ciniki_root, 'json');
if( $rc['stat'] != 'ok' ) {
    print "ERR: Unable to initialize Ciniki\n";
    exit;
}

//
// Setup the $ciniki variable to hold all things qruqsp.  
//
$ciniki = $rc['ciniki'];

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
    print "ERR: Unable to load tenant modules\n";
    exit;
}
if( !isset($rc['modules']) ) {
    print "ERR: Unable to get tenant modules\n";
    exit;
}
$ciniki['tenant']['modules'] = $rc['modules'];

//
// Parent process
//
if( $pid > 0 ) {
    exec('amixer -c 1 set Mic 0db');
    exec('amixer -c 1 set Headphone 50%');

    //
    // Start Direwolf and wait for it to finish
    //
    if( file_exists($ciniki['config']['ciniki.core']['root_dir'] . '/qruqsp-mods/pibin/bin/direwolf') ) {
        $cmd = $ciniki['config']['ciniki.core']['root_dir'] . '/qruqsp-mods/pibin/bin/direwolf';
    } elseif( file_exists('/usr/local/bin/direwolf') ) {
        $cmd = '/usr/local/bin/direwolf';
    } elseif( file_exists('/usr/local/sbin/direwolf') ) {
        $cmd = '/usr/local/sbin/direwolf';
    } elseif( file_exists('/opt/local/bin/direwolf') ) {
        $cmd = '/opt/local/bin/direwolf';
    } elseif( file_exists('/opt/local/sbin/direwolf') ) {
        $cmd = '/opt/local/sbin/direwolf';
    } elseif( file_exists('/usr/sbin/direwolf') ) {
        $cmd = '/usr/sbin/direwolf';
    } elseif( file_exists('/usr/bin/direwolf') ) {
        $cmd = '/usr/bin/direwolf';
    } 
    $config_file = $ciniki['config']['ciniki.core']['root_dir'] . '/direwolf-' . $device_id . '.conf';
    if( !file_exists($config_file) ) {
        print "ERR: watcher: Config file does not exist: $config_file\n";
        exit;
    }
    $cmd .= " -c $config_file -t 0 -q d -p";

    //
    // Start direwolf with a pipe to read the output
    //
    $handle = popen($cmd, "r");
    $exit = 'no';
    $line = '';
    while( $exit == 'no' ) {
        $byte = fread($handle, 1);
        if( $byte == "\n" ) {
            print 'direwolf: ' . $line . "\n";
            if( preg_match("/Virtual KISS TNC.*available.* (\/.*)$/", $line, $m) ) {
                //
                // Found the virtual TNC, Update the device in the database
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.tnc.device', $device_id, array('device'=>$m[1], 'status'=>40));
                if( $rc['stat'] != 'ok' ) {
                    print "ERR: " . $rc['err']['msg'] . "\n";
                    exit;
                }
                print "watcher: Updated device with: " . $m[1] . "\n";

                //
                // Signal child process to start listening on pts
                //
                posix_kill($pid, SIGUSR1);
            }
            $line = '';
        } else {
            $line .= $byte;
        }

        if( feof($handle) ) {   
            break;
        }
    }

    //
    // When direwolf exits, update the status in database and remove device file
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.tnc.device', $device_id, array('device'=>'', 'status'=>60));
    if( $rc['stat'] != 'ok' ) {
        print "ERR: " . $rc['err']['msg'] . "\n";
        exit;
    }
    print "watcher: direwolf offline\n";

    //
    // Kill the child
    //
    posix_kill($pid, SIGUSR1);
    
    //
    // Wait for child to end
    //
    print "watcher: Waiting for child\n";
    pcntl_waitpid($pid, $status);
    print "watcher: Done\n";
} 

//
// Child process for decoding packets
//
else {
    //
    // Load required functions
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetsDecode');
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetReceive');
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetStoreProcess');

    print "listener: waiting for direwolf\n";
    pcntl_sigprocmask(SIG_BLOCK, array(SIGUSR1));
    $signo = pcntl_sigwaitinfo(array(SIGUSR1));

    print "listener: starting\n";

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'tenantDetails');
    $rc = ciniki_tenants_hooks_tenantDetails($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        print "ERR: Unable to load tenant details\n";
        exit;
    }
    $ciniki['tenant']['name'] = $rc['details']['name'];
    // FIXME: Change to station callsign when implemented
    $ciniki['tenant']['callsign'] = $rc['details']['name'];

    //
    // Load the pts device from database
    //
    $strsql = "SELECT device "
        . "FROM qruqsp_tnc_devices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $device_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tnc', 'device');
    if( $rc['stat'] != 'ok' ) {
        print "ERR: Unable to load device\n";
        exit;
    }
    if( !isset($rc['device']) ) {
        print "ERR: Unable to find device\n";
        exit;
    }
    $device = $rc['device'];
    
    //
    // Check the pts exists
    //
    if( !file_exists($device['device']) ) {
        print "ERR: Device does not exist\n";
        exit;
    }

    //
    // Open the pts for reading binary
    //
    $pts_handle = fopen($device['device'], "rb");

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
                print "ERR: Error receiving packet: " . $rc['err']['msg'] . "\n";
            } elseif( isset($rc['packet']) ) {
                //
                // Store the packet
                //
                $rc = qruqsp_tnc_packetStoreProcess($ciniki, $tnid, $rc['packet']);
                if( $rc['stat'] != 'ok' && $rc['stat'] != 'ignore' ) {
                    print "ERR: Error storing and processing packet: " . $rc['err']['msg'] . "\n";
                }
            }
        }
    }

    print "listener: EOF of file\n";
    exit;
}

exit;
?>
