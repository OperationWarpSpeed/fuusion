Ext.define('iFrame.view.AgreementWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.agreementwindow',

    requires: [
        'Ext.form.Panel',
        'Ext.form.field.Display',
        'Ext.form.FieldSet',
        'Ext.form.Label',
        'Ext.button.Button'
    ],

    xtype: 'agreementwindow',

    onAgree: function () {},
    onDisagree: function () {},

    modal: true,
    itemId: 'AgreementWindow',
    maxHeight: 640,
    scrollable: false,
    width: 851,
    closable: false,
    title: 'License Agreement',
    defaultListenerScope: true,

    initComponent: function () {
        const me = this;
        me.dockedItems = [
            {
                xtype: 'form',
                dock: 'top',
                height: 450,
                loader: {
                    win: this,
                    url: '/softnas/html/agreement.html',
                    scripts: false,
                    loadMask: true,
                    autoLoad: true,
                    // important
                    renderer: 'html',
                    // this is also the default option, other options are data | component
                    success: function(el,response,opts) {
                        if(this.win){
                            //this.win.setLoading(false);
                        }

                    },
                    failure: function(el,response,opts) {
                        if( this.win){
                            //this.win.setLoading(false);
                        }
                        alert("Unable to load page!");
                    }
                },
                scrollable: true,
                width: 838,
                bodyPadding: 10
            }
        ];

        me.items = [
            {
                xtype: 'displayfield',
                padding: '5 0 3 10',
                value: 'Please review the license agreement and press the Agree button to continue using the software.'
            },
            {
                xtype: 'fieldset',
                border: 0,
                height: 42,
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                },
                items: [
                    {
                        xtype: 'label',
                        flex: 1,
                        maxWidth: 250,
                        text: ' '
                    },
                    {
                        xtype: 'button',
                        height: 24,
                        itemId: 'idDisagree',
                        maxHeight: 24,
                        width: 70,
                        text: 'I Disagree',
                        listeners: {
                            click: me.onDisagreeClick
                        }
                    },
                    {
                        xtype: 'label',
                        flex: 1,
                        maxWidth: 100,
                        width: 100,
                        text: ' '
                    },
                    {
                        xtype: 'button',
                        height: 24,
                        itemId: 'agree',
                        maxHeight: 24,
                        width: 70,
                        text: 'I Agree',
                        listeners: {
                            click: me.onAgreeClick
                        }
                    }
                ]
            }
        ];

        me.callParent(arguments);
    },

    onDisagreeClick: function(button, e, eOpts) {
        const me = button.up('[xtype=agreementwindow]');
        me.onDisagree();
    },

    onAgreeClick: function(button, e, eOpts) {
        const me = button.up('[xtype=agreementwindow]');
        me.onAgree();
    }

});