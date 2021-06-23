Ext.define('iFrame.view.center.TabContent', {
    extend: 'Ext.container.Container',
    alias: 'widget.tabcontent',

    requires: [
        'iFrame.ux.SimpleIFrame'
    ],

    closable: true,
    layout: 'border',
    padding: 5,

    initComponent: function () {

        this.items = [{
            region: 'center',
            xtype: 'simpleiframe'
        }];

        this.callParent(arguments);

    }

});