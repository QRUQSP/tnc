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
function qruqsp_tnc_packetStore($ciniki, $tnid, $packet_data) {

    $dt = new DateTime('now', new DateTimezone('UTC'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'qruqsp.tnc.kisspacket', array(    
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
