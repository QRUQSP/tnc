//
// This is the main app for the tnc module
//
function qruqsp_tnc_main() {
    //
    // The panel to list the kisspacket
    //
    this.menu = new M.panel('kisspacket', 'qruqsp_tnc_main', 'menu', 'mc', 'large narrowaside', 'sectioned', 'qruqsp.tnc.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'stats':{'label':'Stats', 'type':'simplegrid', 'aside':'yes', 'num_cols':2,
            'cellClasses':['', 'alignright'],
            },
        'sources5total':{'label':'Top 5 Sources', 'type':'simplegrid', 'aside':'yes', 'num_cols':3,
            'cellClasses':['', 'alignright', 'alignright'],
            'noData':'No sources found',
            },
        'sources5last7days':{'label':'Last 7 Days', 'type':'simplegrid', 'aside':'yes', 'num_cols':3,
            'cellClasses':['', 'alignright', 'alignright'],
            'noData':'Nothing last 7 days',
            },
        'digipeaters5total':{'label':'Top 5 Digipeaters', 'type':'simplegrid', 'aside':'yes', 'num_cols':3,
            'cellClasses':['', 'alignright', 'alignright'],
            'noData':'No digipeaters found',
            },
        'digipeaters5last7days':{'label':'Last 7 Days', 'type':'simplegrid', 'aside':'yes', 'num_cols':3,
            'cellClasses':['', 'alignright', 'alignright'],
            'noData':'Nothing last 7 days',
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':['multiline'],
            'hint':'Search kisspacket',
            'noData':'No kisspacket found',
            },
        'packets':{'label':'KISS TNC Packet', 'type':'simplegrid', 'num_cols':1,
            'cellClasses':['multiline'],
            'noData':'No kisspacket',
            'addTxt':'Add KISS TNC Packet',
            'addFn':'M.qruqsp_tnc_main.packet.open(\'M.qruqsp_tnc_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('qruqsp.tnc.kisspacketSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.qruqsp_tnc_main.menu.liveSearchShow('search',null,M.gE(M.qruqsp_tnc_main.menu.panelUID + '_' + s), rsp.packets);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return '<span class="maintext">' + d.utc_of_traffic + ' <span class="subdue">' + d.addresses + '</span></span><span class="subsubtext">' + d.data + '</span>';
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.qruqsp_tnc_main.packet.open(\'M.qruqsp_tnc_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'stats' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'sources5total' || s == 'sources5last7days' || s == 'digipeaters5total' || s == 'digipeaters5last7days' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
                case 2: return d.percent + '%';
            }
        }
        if( s == 'packets' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.utc_of_traffic + ' <span class="subdue">' + d.addresses + '</span></span><span class="subsubtext">' + d.data + '</span>';
            }
        }
    }
    this.menu.noData = function(s) {
        if( this.sections[s].noData != null ) {
            return this.sections[s].noData;
        }
        return null;
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'packets' ) {
            return 'M.qruqsp_tnc_main.packet.open(\'M.qruqsp_tnc_main.menu.open();\',\'' + d.id + '\',M.qruqsp_tnc_main.packet.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.tnc.kisspacketList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tnc_main.menu;
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
    this.packet = new M.panel('KISS TNC Packet', 'qruqsp_tnc_main', 'packet', 'mc', 'medium', 'sectioned', 'qruqsp.tnc.main.packet');
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
            'save':{'label':'Save', 'fn':'M.qruqsp_tnc_main.packet.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_tnc_main.packet.kisspacket_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tnc_main.packet.remove();'},
            }},
        };
    this.packet.fieldValue = function(s, i, d) { return this.data[i]; }
    this.packet.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.tnc.kisspacketHistory', 'args':{'tnid':M.curTenantID, 'kisspacket_id':this.kisspacket_id, 'field':i}};
    }
    this.packet.open = function(cb, kid, list) {
        if( kid != null ) { this.kisspacket_id = kid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.tnc.kisspacketGet', {'tnid':M.curTenantID, 'kisspacket_id':this.kisspacket_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tnc_main.packet;
            p.data = rsp.packet;
            p.refresh();
            p.show(cb);
        });
    }
    this.packet.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_tnc_main.packet.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.kisspacket_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.tnc.kisspacketUpdate', {'tnid':M.curTenantID, 'kisspacket_id':this.kisspacket_id}, c, function(rsp) {
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
            M.api.postJSONCb('qruqsp.tnc.kisspacketAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tnc_main.packet.kisspacket_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.packet.remove = function() {
        if( confirm('Are you sure you want to remove kisspacket?') ) {
            M.api.getJSONCb('qruqsp.tnc.kisspacketDelete', {'tnid':M.curTenantID, 'kisspacket_id':this.kisspacket_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tnc_main.packet.close();
            });
        }
    }
    this.packet.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.kisspacket_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_tnc_main.packet.save(\'M.qruqsp_tnc_main.packet.open(null,' + this.nplist[this.nplist.indexOf('' + this.kisspacket_id) + 1] + ');\');';
        }
        return null;
    }
    this.packet.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.kisspacket_id) > 0 ) {
            return 'M.qruqsp_tnc_main.packet.save(\'M.qruqsp_tnc_main.packet.open(null,' + this.nplist[this.nplist.indexOf('' + this.kisspacket_id) - 1] + ');\');';
        }
        return null;
    }
    this.packet.addButton('save', 'Save', 'M.qruqsp_tnc_main.packet.save();');
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
        var ac = M.createContainer(ap, 'qruqsp_tnc_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
