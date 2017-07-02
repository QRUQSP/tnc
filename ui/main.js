//
// This is the main app for the tnc module
//
function qruqsp_tnc_main() {
    //
    // The panel to list the kisspacket
    //
    this.menu = new Q.panel('kisspacket', 'qruqsp_tnc_main', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.tnc.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search kisspacket',
            'noData':'No kisspacket found',
            },
        'packets':{'label':'KISS TNC Packet', 'type':'simplegrid', 'num_cols':1,
            'noData':'No kisspacket',
            'addTxt':'Add KISS TNC Packet',
            'addFn':'Q.qruqsp_tnc_main.packet.open(\'Q.qruqsp_tnc_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            Q.api.getJSONBgCb('qruqsp.tnc.kisspacketSearch', {'station_id':Q.curStationID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                Q.qruqsp_tnc_main.menu.liveSearchShow('search',null,Q.gE(Q.qruqsp_tnc_main.menu.panelUID + '_' + s), rsp.packets);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.utc_of_traffic;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'Q.qruqsp_tnc_main.packet.open(\'Q.qruqsp_tnc_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'packets' ) {
            switch(j) {
                case 0: return d.utc_of_traffic;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'packets' ) {
            return 'Q.qruqsp_tnc_main.packet.open(\'Q.qruqsp_tnc_main.menu.open();\',\'' + d.id + '\',Q.qruqsp_tnc_main.packet.nplist);';
        }
    }
    this.menu.open = function(cb) {
        Q.api.getJSONCb('qruqsp.tnc.kisspacketList', {'station_id':Q.curStationID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_tnc_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit KISS TNC Packet
    //
    this.packet = new Q.panel('KISS TNC Packet', 'qruqsp_tnc_main', 'packet', 'mc', 'medium', 'sectioned', 'qruqsp.tnc.main.packet');
    this.packet.data = null;
    this.packet.kisspacket_id = 0;
    this.packet.nplist = [];
    this.packet.sections = {
        'general':{'label':'', 'fields':{
            'status':{'label':'Status', 'type':'text'},
            'utc_of_traffic':{'label':'Time', 'type':'date'},
            'port':{'label':'Port', 'type':'text'},
            'command':{'label':'Command', 'type':'text'},
            'control':{'label':'Command', 'type':'text'},
            'protocol':{'label':'Command', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'Q.qruqsp_tnc_main.packet.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return Q.qruqsp_tnc_main.packet.kisspacket_id > 0 ? 'yes' : 'no'; },
                'fn':'Q.qruqsp_tnc_main.packet.remove();'},
            }},
        };
    this.packet.fieldValue = function(s, i, d) { return this.data[i]; }
    this.packet.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.tnc.kisspacketHistory', 'args':{'station_id':Q.curStationID, 'kisspacket_id':this.kisspacket_id, 'field':i}};
    }
    this.packet.open = function(cb, kid, list) {
        if( kid != null ) { this.kisspacket_id = kid; }
        if( list != null ) { this.nplist = list; }
        Q.api.getJSONCb('qruqsp.tnc.kisspacketGet', {'station_id':Q.curStationID, 'kisspacket_id':this.kisspacket_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                Q.api.err(rsp);
                return false;
            }
            var p = Q.qruqsp_tnc_main.packet;
            p.data = rsp.packet;
            p.refresh();
            p.show(cb);
        });
    }
    this.packet.save = function(cb) {
        if( cb == null ) { cb = 'Q.qruqsp_tnc_main.packet.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.kisspacket_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                Q.api.postJSONCb('qruqsp.tnc.kisspacketUpdate', {'station_id':Q.curStationID, 'kisspacket_id':this.kisspacket_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        Q.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            Q.api.postJSONCb('qruqsp.tnc.kisspacketAdd', {'station_id':Q.curStationID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    Q.api.err(rsp);
                    return false;
                }
                Q.qruqsp_tnc_main.packet.kisspacket_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.packet.remove = function() {
        if( confirm('Are you sure you want to remove kisspacket?') ) {
            Q.api.getJSONCb('qruqsp.tnc.kisspacketDelete', {'station_id':Q.curStationID, 'kisspacket_id':this.kisspacket_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    Q.api.err(rsp);
                    return false;
                }
                Q.qruqsp_tnc_main.packet.close();
            });
        }
    }
    this.packet.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.kisspacket_id) < (this.nplist.length - 1) ) {
            return 'Q.qruqsp_tnc_main.packet.save(\'Q.qruqsp_tnc_main.packet.open(null,' + this.nplist[this.nplist.indexOf('' + this.kisspacket_id) + 1] + ');\');';
        }
        return null;
    }
    this.packet.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.kisspacket_id) > 0 ) {
            return 'Q.qruqsp_tnc_main.packet.save(\'Q.qruqsp_tnc_main.packet.open(null,' + this.nplist[this.nplist.indexOf('' + this.kisspacket_id) - 1] + ');\');';
        }
        return null;
    }
    this.packet.addButton('save', 'Save', 'Q.qruqsp_tnc_main.packet.save();');
    this.packet.addClose('Cancel');
    this.packet.addButton('next', 'Next');
    this.packet.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
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
        var ac = Q.createContainer(ap, 'qruqsp_tnc_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}