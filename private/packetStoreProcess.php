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
function qruqsp_tnc_packetStoreProcess($ciniki, $tnid, $packet_data) {

    $dt = new DateTime('now', new DateTimezone('UTC'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'qruqsp.tnc.kisspacket', array(    
        'status' => 10,
        'utc_of_traffic'=> $dt->format('Y-m-d H:i:s'),
        'raw_packet' => $packet_data,
        ), 0x07);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.29', 'msg'=>'Unable to add packet', 'err'=>$rc['err']));
    }

    $packet = array(
        'id' => $rc['id'],
        'uuid' => $rc['uuid'],
        'utc_of_traffic' => $dt->format('Y-m-d H:i:s'),
        'raw_packet' => $packet_data,
        );

    //
    // Decode the packet
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tnc', 'private', 'packetDecode');
    $rc = qruqsp_tnc_packetDecode($ciniki, $tnid, $packet);
    if( $rc['stat'] != 'ok' ) {
        //
        // Check if packet should be ignored
        //
        if( $rc['stat'] == 'ignore' ) {
            print "IGNORE: Removing packet\n";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'qruqsp.tnc.kisspacket', $packet['id'], $packet['uuid'], 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tnc.18', 'msg'=>'Unable to remove ignored packet', 'err'=>$rc['err']));
            }
        }

        return $rc;
    }


    return array('stat'=>'ok', 'packet'=>$rc['packet']);
}
?>
