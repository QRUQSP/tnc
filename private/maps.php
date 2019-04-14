<?php
//
// Description
// -----------
// This function returns the int to text mappings for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_tnc_maps(&$ciniki) {
    //
    // Build the maps object
    //
    $maps = array();
    $maps['device'] = array(
        'status'=>array(
            '10'=>'Inactive',
            '40'=>'Active',
            '60'=>'Offline',
            '90'=>'Archived',
            ),
        'dtype'=>array(
            '10'=>'Direwolf',
            ),
        );
    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
