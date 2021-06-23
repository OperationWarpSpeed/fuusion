//
// Override the base ExtJS tree view and modify it to add "qaid" tag to generated node HTML elements
//
Ext.define( "MyApp.tree.View", {
  override: "Ext.tree.View",

    // globally override how tree views are created to add "qid" to generated HTML elements
    // to support QA automation with Selenium.
    collectData: function(records) {
        var data = this.callParent(arguments),
            rows = data.rows,
            len = rows.length,
            i = 0,
            row, record;

        for (; i < len; i++) {
            row = rows[i];
            record = records[i];
            if (record.get('qtip')) {
                row.rowAttr = 'data-qtip="' + record.get('qtip') + '"';
                if (record.get('qtitle')) {
                    row.rowAttr += ' ' + 'data-qtitle="' + record.get('qtitle') + '"';
                }
            }

            if (record.get('text')) {
                row.rowAttr += ' ' + 'qaid="' + record.get('text') + '"';
            }

            if (record.isExpanded()) {
                row.rowCls = (row.rowCls || '') + ' ' + this.expandedCls;
            }
            if (record.isLeaf()) {
                row.rowCls = (row.rowCls || '') + ' ' + this.leafCls;
            }
            if (record.isLoading()) {
                row.rowCls = (row.rowCls || '') + ' ' + this.loadingCls;
            }
        }

        return data;
    }

});

//
// Tree navigation control in left side (west) menu area
//
Ext.define('iFrame.view.west.Treenav', {
	extend: 'Ext.tree.Panel',
	alias: 'widget.treenav',
	
	initComponent: function() {
		Ext.apply(this, {
		  title: 'Administration',
		  id: 'treenav',
		  store: 'JsonTreeStore',
		  collapsible: true,
      hideCollapseTool: false,
      floatable: false,
      collapsed: true,
      titleCollapse: false,
		  animCollapse: true,
		  rootVisible: false,
		  autoScroll: true,
		  width: 250,
		  margins: '5 0 5 0',
		  scroll: 'vertical',
		  layout: 'fit',
      lockedPanel:true
		});  
       this.callParent(arguments);
	},

  toggleCollapse: function() {
    if (this.lockedPanel) {
      return false;
    }
    this.callParent();
	}
});
