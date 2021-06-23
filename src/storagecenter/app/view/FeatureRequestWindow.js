/*
 * File: ../app/view/FeatureRequestWindow.js
 *
 * This file was generated by Sencha Architect version 3.2.0.
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

Ext.define('iFrame.view.FeatureRequestWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.featurerequestwindow',

    requires: [
        'iFrame.view.FeatureRequestWindowViewModel',
        'Ext.form.FieldContainer',
        'Ext.form.field.TextArea',
        'Ext.button.Button'
    ],

    viewModel: {
        type: 'featurerequestwindow'
    },
    height: 355,
    width: 445,
    closeAction: 'hide',
    title: 'Feature Request',
    modal: true,
    defaultListenerScope: true,

    items: [
        {
            xtype: 'fieldcontainer',
            height: '100%',
            width: 429,
            fieldLabel: '',
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [
                {
                    xtype: 'textfield',
                    itemId: 'featureRequestSummary',
                    margin: '10 10 2 10',
                    fieldLabel: 'Request',
                    labelAlign: 'top',
                    labelSeparator: '',
                    allowBlank: false,
                    blankText: '',
                    emptyText: 'One sentence summary of your request'
                },
                {
                    xtype: 'textareafield',
                    height: 178,
                    itemId: 'featureRequestDetails',
                    margin: 10,
                    fieldLabel: 'Please add more details',
                    labelAlign: 'top',
                    labelSeparator: '',
                    emptyText: 'Why is it useful, who would benefit from it, how should it work?'
                },
                {
                    xtype: 'fieldcontainer',
                    height: 42,
                    width: 429,
                    fieldLabel: '',
                    items: [
                        {
                            xtype: 'button',
                            itemId: 'featureRequestSubmit',
                            margin: '10 10 10 30',
                            padding: '5 15',
                            text: 'Submit',
                            listeners: {
                                click: 'onFeatureRequestSubmitClick'
                            }
                        },
                        {
                            xtype: 'button',
                            itemId: 'featureRequestExit',
                            margin: 10,
                            padding: '5 15',
                            text: 'Cancel',
                            listeners: {
                                click: 'onFeatureRequestExitClick'
                            }
                        }
                    ]
                }
            ]
        }
    ],

    onFeatureRequestSubmitClick: function(button, e, eOpts) {
        var reqWnd = this;
        var summary = Ext.util.Format.trim(reqWnd.query("#featureRequestSummary")[0].getValue());
        if(!summary){
            Ext.Msg.alert('Submit failed', 'Please fill the required fields.');
            return;
        }
        Ext.Ajax.request({
            url: iFrame.request_url,
            scope: this,
            method : 'POST',
            params : {
                opcode: 'feature_request',
                featureRequestSummary: summary,
                featureRequestDetails: reqWnd.query("#featureRequestDetails")[0].getValue()
            },
            timeout: 10000,

            success: function(response, opts) {
                //console.log(response, opts.params);
                data = JSON.parse(response.responseText);
                if(data.success){
                    Ext.Msg.alert('Feature Request', 'Submit successful');
                    reqWnd.close();
                }else{
                    Ext.Msg.alert('Feature Request', 'Submit error, please try again later');
                }
            },
            failure: function(response, opts) {
                //console.log(response, opts.params);
                Ext.Msg.alert('Feature Request', 'Submit error, please try again later');
            }

        });
    },

    onFeatureRequestExitClick: function(button, e, eOpts) {
        this.close();
    }

});