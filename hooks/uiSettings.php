<?php
//
// Description
// -----------
// This function returns the settings for the module and the main menu items and settings menu items
//
// Arguments
// ---------
// q:
// tnid:      
// args: The arguments for the hook
//
function qruqsp_tnc_hooks_uiSettings(&$ciniki, $tnid, $args) {
    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['qruqsp.tnc'])
        && (isset($args['permissions']['operators'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        //
        // The main UI is currently broken and not used. It will be fixed in the future
        //
/*        $menu_item = array(
            'priority'=>5000,
            'label'=>'TNC',
            'edit'=>array('app'=>'qruqsp.tnc.main'),
            );
        $rsp['menu_items'][] = $menu_item; */

        //
        // Add settings menu item
        //
        $rsp['settings_menu_items'][] = array('priority'=>5000, 'label'=>'TNC Setup', 'edit'=>array('app'=>'qruqsp.tnc.settings'));
    }

    return $rsp;
}
?>
