Ext.define('iFrame.view.EmailSetup', {
    extend: 'Ext.window.Window',
    alias : 'widget.EmailSetup',
    requires: [
        'Ext.form.Panel',
        'Ext.form.FieldSet',
        'Ext.form.field.Text',
        'Ext.button.Button'
    ],

    height: 189,
    width: 520,
    resizable: false,
    layout: 'border',
    title: 'Email Setup  ',
    modal: true,
    itemId : 'EmailWindow',
    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'form',
                    region: 'center',
                    itemId: 'EmailForm',
                    id: 'EmailForm',
                    bodyPadding: 10,
                    title: '',
                    baseParams: {
                        opcode: 'email_setup',
                        command: 'update'
                    },
                    url: '/softnas/snserver/snserv.php',
                    items: [
                        {
                            xtype: 'fieldset',
                            height: 128,
                            padding: 10,
                            title: '',
                            items: [
                                {
                                    xtype: 'textfield',
                                    anchor: '100%',
                                    itemId: 'email',
                                    fieldLabel: 'Your Email',
                                    name: 'email',
                                    emptyText: 'email@example.com (must not be gmail, hotmail and yahoo)',
                                    msgTarget: 'side',
                                    autoFitErrors: false,
                                    regex: /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(((?!(gmail|hotmail|yahoo)+)[a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
                                    allowBlank: false
                                },
                                {
                                    xtype: 'textfield',
                                    validator: function(value) {
                                        //console.info(me);
                                        var form = me.getComponent('EmailForm').getForm();
                                        var EmailButton = me.down("#EmailButton");
                                        var email = form.findField('email');
                                        var emailConf = form.findField('emailConf');


                                        if(email.getValue() !== '')
                                        {
                                            if(email.getValue() != emailConf.getValue())
                                            {
                                                EmailButton.disable();
                                                return "password does not match";
                                            }
                                            else
                                            {
                                                EmailButton.enable();
                                            }
                                        }
                                        return true;

                                    },
                                    anchor: '100%',
                                    itemId: 'emailConf',
                                    autoFitErrors: false,
                                    fieldLabel: 'Confirm Email',
                                    msgTarget: 'side',
                                    name: 'emailConf',
                                    emptyText: 'email@example.com (email confirmation)',
                                    allowBlank: false
                                },
                                {
                                    xtype: 'container',
                                    height: 35,
                                    margin: '20 0 0 0',
                                    width: 443,
                                    layout: 'column',
                                    items: [
                                        {
                                            xtype: 'container',
                                            height: 30,
                                            width: 219
                                        },
                                        {
                                            xtype: 'container',
                                            height: 33,
                                            width: 110,
                                            items: [
                                                {
                                                    xtype: 'button',
                                                    height: 30,
                                                    itemId: 'LaterButton',
                                                    width: 100,
                                                    text: 'Later',
                                                    listeners: {
                                                        click: {
                                                            fn: me.onLaterButtonClick,
                                                            scope: me
                                                        }
                                                    }
                                                }
                                            ]
                                        },
                                        {
                                            xtype: 'container',
                                            height: 35,
                                            width: 109,
                                            items: [
                                                {
                                                    xtype: 'button',
                                                    height: 30,
                                                    itemId: 'EmailButton',
                                                    width: 100,
                                                    text: 'Save',
                                                    type: 'button',
                                                    listeners: {
                                                        click: {
                                                            fn: me.onEmailButtonClick,
                                                            scope: me
                                                        }
                                                    }
                                                }
                                            ]
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                }
            ],
            listeners: {
                show: {
                    fn: me.onEmailSetupShow,
                    scope: me
                }
            }
        });

        me.callParent(arguments);
    },

    onEmailSetupShow: function(button, e, eOpts) {
        setTimeout(function(){
            Ext.ComponentQuery.query("#email")[0].focus();
        }, 100);
    },

    onLaterButtonClick: function(button, e, eOpts) {
        this.close();
    },

    onEmailButtonClick: function(button, e, eOpts) {
        var form = Ext.getCmp('EmailForm').getForm();
        var me = this;
        if(form.isValid())
        {
            form.submit({
                submitEmptyText: false,
                waitMsg: "Saving Email - please wait . . .",
                waitTitle: "Saving Email",
                success: function(form, action) {
                    me.close();

                },
                failure: function(form, action) {
                    switch (action.failureType) {
                        case Ext.form.action.Action.CLIENT_INVALID:
                            Ext.Msg.alert('Failure', 'Form fields may not be submitted with invalid values');
                            break;
                        case Ext.form.action.Action.CONNECT_FAILURE:
                            Ext.Msg.alert('Failure', 'Ajax communication failed');
                            break;
                        case Ext.form.action.Action.SERVER_INVALID:
                           Ext.Msg.alert('Failure', action.result.msg);
                   }
                }
            });

        }
    }

});