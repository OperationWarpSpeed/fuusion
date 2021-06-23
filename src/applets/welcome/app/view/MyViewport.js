/*
 * File: app/view/MyViewport.js
 *
 * This file was generated by Sencha Architect version 4.2.4.
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 5.1.x library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 5.1.x. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('MyApp.view.MyViewport', {
    extend: 'Ext.container.Viewport',
    alias: 'widget.myviewport',

    requires: [
        'Ext.panel.Panel',
        'Ext.form.field.Checkbox'
    ],

    layout: 'fit',
    defaultListenerScope: true,

    items: [
        {
            xtype: 'panel',
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [
                {
                    xtype: 'panel',
                    flex: 1,
                    height: 381,
                    html: '<iframe src="/release_notes.html" width="100%" height="100%" frameborder="2" scrolling="auto"></iframe>',
                    title: 'Release Notes'
                }
            ],
            dockedItems: [
                {
                    xtype: 'checkboxfield',
                    flex: 1,
                    dock: 'bottom',
                    id: 'checkShowWelcomeOnStartup',
                    itemId: 'checkShowWelcomeOnStartup',
                    margin: '10px',
                    width: '',
                    fieldLabel: ' ',
                    labelSeparator: ' ',
                    boxLabel: 'Show Welcome panel on startup',
                    checked: true,
                    listeners: {
                        change: 'onCheckShowWelcomeOnStartupChange'
                    }
                }
            ]
        }
    ],

    onCheckShowWelcomeOnStartupChange: function(field, newValue, oldValue, eOpts) {
        if(window['ini_loaded'] === undefined || !window['ini_loaded']){
            return;
        }
        var checkbox = Ext.ComponentQuery.query('#checkShowWelcomeOnStartup')[0];
        var url = "/softnas/snserver/snserv.php";

        checkbox.disable();

        Ext.Ajax.request({
            url: url,
            scope: this,
            method : 'POST',
            params : {
                opcode: 'gettingstarted',
                command: 'modifysettings_welcome',
                showWelcomeOnStartup: (checkbox.getValue() == true) ? "1" : "0"
            },

            success: function(response, opts) {
                checkbox.enable();
                var reply = Ext.decode(response.responseText);
                if( !reply.success )
                {
                    Ext.MessageBox.show({
                        title: 'Server Error',
                        msg: "Error while saving settings!",
                        buttons: Ext.Msg.OK
                    });
                    return false;
                }
            },
            failure: function(response, opts) {
                checkbox.enable();
                Ext.MessageBox.show({
                    title: 'Server Error',
                    msg: "Error while saving settings!",
                    buttons: Ext.Msg.OK
                });

                return false;
            }
        });

    }

});