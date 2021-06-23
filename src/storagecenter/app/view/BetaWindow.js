Ext.define('iFrame.view.BetaWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.betawindow',
    requires: [
        'Ext.form.Panel',
        'Ext.form.field.Checkbox',
        'Ext.form.FieldSet',
        'Ext.form.Label',
        'Ext.button.Button'
    ],
    modal: true,
    width: 550,
    closable: false,
    resizable: false,
    scrollable: false,
    itemId: 'betaWindow',
    title: 'Beta Features Agreement',
    defaultListenerScope: true,
    config: {
        clickDelay: 0,
        dataSelectedRecord: null
    },
    dockedItems: [{
        xtype: 'form',
        dock: 'top',
        width: 540,
        height: 100,
        bodyPadding: 10,
        scrollable: true,
        html: '<p>You are about to use a Beta Feature and it should NOT be used in a production environment.</p><p>Read more about Beta Features here: <a href="https://www.softnas.com/wp/platinum-beta/">https://www.softnas.com/wp/platinum-beta/</a></p>'
    }],
    items: [/*{
        xtype: 'checkboxfield',
        padding: '5 0 3 10',
        hideLabel: true,
        itemId: 'hideBetaInfo',
        name: 'hide_beta_info',
        boxLabel: 'Don\'t show this again.'
    }*/,{
        xtype: 'container',
        layout: {
            type: 'hbox',
            pack: 'center'
        },
        items: [{
            xtype: 'button',
            text: 'Accept',
            width: 70,
            handler: 'onAcceptBetaAgreement',
            margin: '10 5'
        },{
            xtype: 'button',
            text: 'Decline',
            width: 70,
            handler: 'onDeclineBetaAgreement',
            margin: '10 5'
        }]
    }],

    onAcceptBetaAgreement: function(btn, e, eOpts) {
        var me = this,
            beta_link_win = window.open("https://www.softnas.com/wp/platinum-beta/", "_blank");

        me.setLoading('Starting beta services...');
        
        beta_link_win && beta_link_win.focus();

        Ext.Ajax.request({
            url: '/softnas/snserver/snserv.php',
            scope: me,
            method : 'POST',
            params : {
                opcode: 'acceptbetaagreement'
            },
            timeout: 900 * 1000,
            success: function(response, opts) {
                me.setLoading(false);
                
                if(response) {

                    response = Ext.decode(response.responseText);

                    if(response.success) {
                        window.top.betaAgreementAccepted = true;
                        window.top.flexfilesEnabled = true; // the acceptbetaagreement opcode already starts flexfiles services and save the [flexfiles][enabled] = true
                        
                        me.close();
                        
                        // open beta feature
                        iFrame.getApplication().getController('MainCtrl').openTab(me.getDataSelectedRecord(), me.getClickDelay());
                    }
                    else {
                        Ext.Msg.alert('Error', response.msg ? response.msg : 'Unable to accept the Beta Features Agreement.');
                    }
                }
            },
            failure: function(response, opts) {
                if(response && response.responseText) {
                    response = Ext.decode(response.responseText);
                }
                me.setLoading(false);
                Ext.Msg.alert('Failure', response.msg ? response.msg : 'Server error');
            }
        });
    },

    onDeclineBetaAgreement: function() {
        this.close();
    }
});