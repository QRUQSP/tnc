<?php
//
// Description
// -----------
// This script is to run the tnc script once a minute from cron to query devices configured.
//

//
// Initialize Moss by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

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
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tnc.4', 'msg'=>'No modules enabled'));
}
$ciniki['tenant']['modules'] = $rc['modules'];

//
// Check for TNC Listeners
//
ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'listenerCheck');
$rc = qruqsp_tnc_listenerCheck($ciniki, $tnid);
if( $rc['stat'] != 'ok' ) {
    ciniki_cron_logMsg($ciniki, $tnid, array('code'=>'qruqsp.tnc.5', 'severity'=>50, 'msg'=>'Unable to check TNC devices', 'err'=>$rc['err']));
}

exit(0);
?>
