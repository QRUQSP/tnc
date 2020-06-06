//
// This is the settings app for the tnc module
//
function qruqsp_tnc_settings() {
    
    //
    // The panel to list the device
    //
    this.menu = new M.panel('device', 'qruqsp_tnc_settings', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.tnc.settings.menu');
    this.menu.data = {};
    this.menu.sections = {
        'devices':{'label':'Devices', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Name', 'Device', 'Status'],
            'noData':'No devices setup',
            'addTxt':'Add TNC',
            'addFn':'M.qruqsp_tnc_settings.device.open(\'M.qruqsp_tnc_settings.menu.open();\',0);',
            },
    }
    this.menu.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.name;
            case 1: return d.device;
            case 2: return d.status_text;
        }
    }
    this.menu.rowFn = function(s, i, d) {
        return 'M.qruqsp_tnc_settings.device.open(\'M.qruqsp_tnc_settings.menu.open();\',\'' + d.id + '\',M.qruqsp_tnc_settings.menu.nplist);';
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.tnc.deviceList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tnc_settings.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit TNC Device
    //
    this.device = new M.panel('TNC Device', 'qruqsp_tnc_settings', 'device', 'mc', 'medium', 'sectioned', 'qruqsp.tnc.main.device');
    this.device.data = null;
    this.device.device_id = 0;
    this.device.nplist = [];
    this.device.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Inactive', '40':'Active', '60':'Offline', '90':'Archive'}},
//            'dtype':{'label':'Type', 'type':'text'},
//            'device':{'label':'Device', 'type':'text'},
//            'flags':{'label':'Options', 'type':'text'},
            }},
        'direwolf_settings':{'label':'Settings', 'fields':{
            'settings.ADEVICE':{'label':'ADEVICE', 'type':'text'},
            'settings.PTT':{'label':'PTT', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_tnc_settings.device.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_tnc_settings.device.device_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tnc_settings.device.remove();'},
            }},
        };
    this.device.fieldValue = function(s, i, d) { return this.data[i]; }
    this.device.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.tnc.deviceHistory', 'args':{'tnid':M.curTenantID, 'device_id':this.device_id, 'field':i}};
    }
    this.device.open = function(cb, did, list) {
        if( did != null ) { this.device_id = did; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.tnc.deviceGet', {'tnid':M.curTenantID, 'device_id':this.device_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tnc_settings.device;
            p.data = rsp.device;
            p.refresh();
            p.show(cb);
        });
    }
    this.device.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_tnc_settings.device.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.device_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.tnc.deviceUpdate', {'tnid':M.curTenantID, 'device_id':this.device_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.tnc.deviceAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tnc_settings.device.device_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.device.remove = function() {
        M.confirm('Are you sure you want to remove device?',null,function() {
            M.api.getJSONCb('qruqsp.tnc.deviceDelete', {'tnid':M.curTenantID, 'device_id':M.qruqsp_tnc_settings.device.device_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tnc_settings.device.close();
            });
        });
    }
    this.device.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.device_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_tnc_settings.device.save(\'M.qruqsp_tnc_settings.device.open(null,' + this.nplist[this.nplist.indexOf('' + this.device_id) + 1] + ');\');';
        }
        return null;
    }
    this.device.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.device_id) > 0 ) {
            return 'M.qruqsp_tnc_settings.device.save(\'M.qruqsp_tnc_settings.device.open(null,' + this.nplist[this.nplist.indexOf('' + this.device_id) - 1] + ');\');';
        }
        return null;
    }
    this.device.addButton('save', 'Save', 'M.qruqsp_tnc_settings.device.save();');
    this.device.addClose('Cancel');
    this.device.addButton('next', 'Next');
    this.device.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the settings panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'qruqsp_tnc_settings', 'yes');
        if( ac == null ) {
            M.alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
