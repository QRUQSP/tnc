<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
function qruqsp_tnc_objects(&$q) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['ax25packet'] = array(
        'name'=>'AX25 Packet',
        'o_name'=>'packet',
        'o_container'=>'packets',
        'sync'=>'yes',
        'table'=>'qruqsp_tnc_ax25packets',
        'fields'=>array(
            'status'=>array('name'=>'Status'),
            'utc_of_traffic'=>array('name'=>'Time'),
            'raw_packet'=>array('name'=>'Raw Packet'),
            ),
        'history_table'=>'qruqsp_tnc_history',
        );
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
