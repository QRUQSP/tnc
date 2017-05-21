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
    $objects['kisspacket'] = array(
        'name'=>'KISS TNC Packet',
        'o_name'=>'packet',
        'o_container'=>'packets',
        'sync'=>'yes',
        'table'=>'qruqsp_tnc_kisspackets',
        'fields'=>array(
            'status'=>array('name'=>'Status'),
            'utc_of_traffic'=>array('name'=>'Time'),
            'raw_packet'=>array('name'=>'Raw Packet'),
            'port'=>array('name'=>'Port', 'default'=>0),
            'command'=>array('name'=>'Command', 'default'=>0),
            'dest_callsign'=>array('name'=>'Destination Callsign', 'default'=>''),
            'dest_ssid'=>array('name'=>'Destination SSID', 'default'=>''),
            'src_callsign'=>array('name'=>'Source Callsign', 'default'=>''),
            'src_ssid'=>array('name'=>'Source SSID', 'default'=>''),
            'digipeaters'=>array('name'=>'Digipeaters', 'default'=>''),
            'data'=>array('name'=>'Data', 'default'=>''),
            ),
        'history_table'=>'qruqsp_tnc_history',
        );
 
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
