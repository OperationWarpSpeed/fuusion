/**
 * Created by pavelzindyaev on 19/06/2017.
 */

Ext.define('iFrame.view.GoldWelcomeWindow', {
    extend: 'Ext.window.Window',

    requires: [
        'Ext.form.field.TextArea',
        'Ext.container.Container',
        'Ext.button.Button',
        'Ext.form.field.Checkbox'
    ],

    modal: true,
    height: 250,
    itemId: 'goldWelcomeWindow',
    width: 512,
    layout: 'border',
    title: 'Gold Support Notification',

    items: [
        {
            xtype: 'textareafield',
            region: 'center',
            value: 'Congratulations on your selection of Buurst.\nThe edition of Buurst Fuusion that you have selected includes Buurst Gold Support.\nTo learn more the benefits you are entitled to with Buurst Gold Support, please click the Learn More below.',
            editable: false,
            itemId: 'goldWelcomeMainText',
            fieldStyle: {
                'fontSize': '14px'
            }
        }
    ],

    dockedItems: [
        {
            xtype: 'container',
            dock: 'bottom',
            layout: {
                type: 'hbox',
                align: 'middle',
                pack: 'center'
            },
            items: [
                {
                    xtype: 'button',
                    height: 30,
                    margin: 5,
                    text: 'Learn More',
                    itemId: 'goldWelcomeLearnMoreButton',
                    listeners: {
                        click: {
                            fn: function (button, e, eOpts) {
                                window.open('https://www.softnas.com/documents/SOFTNAS%20Gold%20Support%20v2.2.pdf');
                            }
                        }
                    }
                },
                {
                    xtype: 'container',
                    flex: 2
                },
                {
                    xtype: 'checkboxfield',
                    flex: 2,
                    boxLabel: 'Show this screen on startup',
                    checked: true,
                    itemId: 'goldWelcomeStartupCheckbox',
                    listeners: {
                        change: {
                            fn: function (box, newValue, oldValue, eOpts) {
                                Ext.Ajax.request({
                                    url: "/softnas/snserver/snserv.php",
                                    method: 'POST',
                                    params: {
                                        opcode: 'gold_support_welcome',
                                        hideGoldSupportWelcome: !newValue
                                    }
                                });
                            }
                        }
                    }
                }
            ]
        }
    ]
});
