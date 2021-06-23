Ext.define('iFrame.view.EnableFlexfilesWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.enableflexfileswindow',
    modal: true,
    width: 500,
    height: 120,
    closable: false,
    resizable: false,
    scrollable: false,
    itemId: 'enableFlexfilesWindow',
    title: 'Enable FlexFiles',
    defaultListenerScope: true,
    bodyStyle: {
        backgroundColor: '#fff'
    },
    bodyPadding: 10,
    config: {
        clickDelay: 0,
        dataSelectedRecord: null
    },
    html: 'FlexFiles and UltraFast services are currently disabled. Do you want to enable them?<br><br>This can take 5 minutes to complete.',
    dockedItems: {
        xtype: 'toolbar',
        dock: 'bottom',
        ui: 'footer',
        layout: {
            pack: 'center'
        },
        defaults: {
            width: 75
        },
        items: [{
            text: 'Yes',
            handler: 'onYesHandler'
        },{
            text: 'No',
            handler: 'onNoHandler'
        }]
    },

    onYesHandler: function() {
        var me = this;

        me.setLoading('Enabling FlexFiles...');

        Ext.Ajax.request({
            url: Util.Config.baseUrl,
            scope: me,
            params: {
                opcode: 'enableflexfiles'
            },
            timeout: 900 * 1000,
            success: function(res) {
                me.setLoading(false);
                
                res = Ext.decode(res.responseText);

                if(res.success) {
                    window.top.flexfilesEnabled = true;

                    // open module
                    iFrame.getApplication().getController('MainCtrl').openTab(me.getDataSelectedRecord(), me.getClickDelay());
                }
                else {
                    Ext.Msg.alert('Error', res.msg ? res.msg : 'Unable to enable FlexFiles.');
                }

                me.close();
            },
            failure: function(res) {
                res = Ext.decode(res.responseText);

                Ext.Msg.alert('Failure', res.msg ? res.msg : 'Server error');
                me.setLoading(false);
            }
        });
    },

    onNoHandler: function() {
        this.close();
    }
});