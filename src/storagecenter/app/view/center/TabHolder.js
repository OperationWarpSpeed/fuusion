Ext.define('iFrame.view.center.TabHolder', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.tabholder',
	
	id: 'main-tabs',
    
    requires: [
    	
	],
	
    margins: '5 0 5 5',
	maxTabWidth: 370,
	
    initComponent: function() {
		
        /*this.items = [{
            
        }];*/
		
        this.callParent(arguments);
    }
    
});