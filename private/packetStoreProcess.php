<?php
//
// Description
// -----------
// This function will listen on a kisstnc pts for incoming packets and store them in the database
//
// Arguments
// ---------
// q:
// packet:          The packet to be stored and processed
//
function qruqsp_tnc_packetStoreProcess($q, $station_id, $packet_data) {

    $dt = new DateTime('now', new DateTimezone('UTC'));

    qruqsp_core_loadMethod($q, 'qruqsp', 'core', 'private', 'objectAdd');
    $rc = qruqsp_core_objectAdd($q, $station_id, 'qruqsp.tnc.kisspacket', array(    
        'status' => 10,
        'utc_of_traffic'=> $dt->format('Y-m-d H:i:s'),
        'raw_packet' => $packet_data,
        ), 0x07);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.2', 'msg'=>'Unable to add packet', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok', 'id'=>$rc['id']);
}
?>
