/**
 *  MainController
 */
Ext.define('iFrame.controller.MainCtrl', {
    extend: 'Ext.app.Controller',

    requires: ['iFrame.view.center.TabContent', 'iFrame.view.EmailSetup'],

    refs: [{
        selector: 'viewport > tabholder',
        ref: 'tabHolder'
    },{
        autoCreate: true,
        ref: 'emailSetup',
        selector: '#EmailWindow',
        xtype: 'EmailSetup'
    },{
      autoCreate: true,
      ref: 'enableFlexfilesWindow',
      selector: '#enableFlexfilesWindow',
      xtype: 'enableflexfileswindow'
    },{
        autoCreate: true,
        ref: 'addPlatinumWindow',
        selector: '#addPlatinumWindow',
        xtype: 'addplatinumwindow'
    },{
        ref: 'addPlatinumForm',
        selector: '#addPlatinumForm'
    },{
        ref: 'platinumKeyTextfield',
        selector: '#platinumKeyTextfield'
    },{
        ref: 'platinumRegNameTextfield',
        selector: '#platinumRegNameTextfield'
    },{
        ref: 'platinumActivationCode',
        selector: '#platinumActivationCode'
    },{
        autoCreate: true,
        ref: 'betaWindow',
        selector: '#betaWindow',
        xtype: 'betawindow'
    }],

    copyright: 'Copyright (c) SoftNAS Inc. All Rights Reserved',

    //reload : true,

    init: function () {
        this.control({
            'viewport > tabholder': {
                tabchange: this.tabchange
            },
            'viewport > #treenav': {
                itemclick: this.nodeselection,
                expand : this.treeExpand
            },
            'viewport #toggleFieldLiveSupport': {
              change: this.onChangeToggleFieldLiveSupport
            }
        });
    },

    onChangeToggleFieldLiveSupport: function(field, value) {
      
      var widget_element = Ext.get('drift-widget');
      var skip_saving = false;
      
      if (widget_element.getWidth() > 0 && value || widget_element.getWidth() === 0 && !value) {
        skip_saving = true;
      }
      
      if (!value) {
        Ext.get('drift-widget-container').dom.style.display = 'none';
        window.top.drift.api.hideChat();
        window.top.drift.api.widget.hide();
      } else {
        Ext.get('drift-widget-container').dom.style.display = '';
        //window.top.drift.api.widget.show();
        window.top.drift.api.openChat();
      }
      if (window.top.drift.skip_saving !== undefined) {
        skip_saving = window.top.drift.skip_saving;
        window.top.drift.skip_saving = undefined;
      }
      if (skip_saving) {
        return;
      }
      
      field.disable();
      field.getValue();
      Ext.Ajax.request({
        url: '/buurst/snserver/snserv.php',
        scope: this,
        params: {
          opcode: 'set_status_live_support',
          enabled: value
        },
        success: function(response) {
          response = Ext.decode(response.responseText);
          
          if(!response.success) {
            Ext.Msg.alert('Error', response.msg);
          }
          field.enable();
          field.getValue();
        },
        failure: function(response) {
          response = Ext.decode(response.responseText);
          field.enable();
          field.getValue();
          Ext.Msg.alert('Error', response.msg);
        }
      });
    },

    findNodeByTitle: function( title ) {
        var tree = Ext.ComponentQuery.query("treepanel")[0];
        var record = tree.getRootNode().findChild('text', title, true);
        return record;
    },

    launchNodeByTitle: function( title ) {
        var rec = this.findNodeByTitle( title );
        if( rec )
        {
            var iconCls = rec.data.iconCls;
            var uri = rec.data.uri;
            this.loadTab( title, uri, iconCls );
        }
    },

    handleLicensedFeature: function(featurename, isLicensed) {

        var tree = Ext.ComponentQuery.query("treepanel")[0];
        switch ( featurename )
        {
            case 'snapreplicate':
                var node = tree.getStore().getNodeById('snapreplicateNode');

                if(!node) {
                  break;
                }

                var iconCls = isLicensed ? "icon-replicate" : "icon-pro";
                node.data.iconCls = iconCls;
                if( !isLicensed )
                    node.data.qtip = "PRO Feature: Click on Upgrade to Pro to upgrade.";
                node.data.disabled = ! isLicensed;
                break;

            case 'iscsitarget':
                var node = tree.getStore().getNodeById('iscsitargetNode');

                if(!node) {
                  break;
                }

                var iconCls = isLicensed ? "icon-disk" : "icon-pro";
                node.data.iconCls = iconCls;
                if( !isLicensed )
                    node.data.qtip = "PRO Feature: Click on Upgrade to Pro to upgrade.";
                node.data.disabled = ! isLicensed;
                break;

            case 'iscsiinitiator':
                var node = tree.getStore().getNodeById('iscsiinitiatorNode');

                if(!node) {
                  break;
                }

                var iconCls = isLicensed ? "icon-disk" : "icon-pro";
                node.data.iconCls = iconCls;
                if( !isLicensed )
                    node.data.qtip = "PRO Feature: Click on Upgrade to Pro to upgrade.";
                node.data.disabled = ! isLicensed;
                break;

           case 'activedirectory':
                var node = tree.getStore().getNodeById('kerberosNode');
                if(!node) {
                  break;
                }
                var iconCls = isLicensed ? "icon-kerberos" : "icon-pro";
                node.data.iconCls = iconCls;
                if( !isLicensed )
                    node.data.qtip = "PRO Feature: Click on Upgrade to Pro to upgrade.";
                node.data.disabled = ! isLicensed;
                break;
           case 'ultrafast':
                var node = tree.getStore().getNodeById('ultrafast');
                if(!node) {
                  break;
                }
                if( !isLicensed ) {
                  node.data.qtip = "Option not supported by this license!";
                }
                node.data.disabled = ! isLicensed;
                break;
           case 'flexfiles':
                var node1 = tree.getStore().getNodeById('flexfilesNode');
                var node2 = tree.getStore().getNodeById('flexfilesLiftShiftNode');
                var node3 = tree.getStore().getNodeById('flexfilesSettingsNode');
                if(node1) {
                  if( !isLicensed ) {
                    node1.data.qtip = "Option not supported by this license!";
                  }
                  node1.data.disabled = ! isLicensed;
                }
                if(node2) {
                  if( !isLicensed ) {
                    node2.data.qtip = "Option not supported by this license!";
                  }
                  node2.data.disabled = ! isLicensed;
                }
                if(node3) {
                  if( !isLicensed ) {
                    node3.data.qtip = "Option not supported by this license!";
                  }
                  node3.data.disabled = ! isLicensed;
                }

                break;
           case 'flexfiles_architect':
                var node = tree.getStore().getNodeById('flexfilesArchitectNode');
                if(!node) {
                  break;
                }
                if( !isLicensed ) {
                    node.data.qtip = "Option not supported by this license!";
                }
                node.data.disabled = ! isLicensed;
                break;
           case 'schedsnapshots':
                var node = tree.getStore().getNodeById('schedulesNode');

                if(!node) {
                  break;
                }

                var iconCls = isLicensed ? "icon-schedules" : "icon-pro";
                node.data.iconCls = iconCls;
                if( !isLicensed )
                    node.data.qtip = "PRO Feature: Click on Upgrade to Pro to upgrade.";
                node.data.disabled = ! isLicensed;

                // Also process the "Upgrade to Pro" node here
                node = tree.getStore().getNodeById('upgradeNode');
                if( node && isLicensed )  // this is Pro licensed system
                {
                    node.remove();  // remove the upgrade to pro node
                }

                break;
        }
    },

    isLicensedFeature: function(features, controller) {
        var isLicensed = false;
        var url = "/buurst/snserver/snserv.php";

        var outer_scope = this;

        Ext.Ajax.request({
            url: url,
            scope: this,
            method : 'POST',
            params : {
                opcode: 'islicensedfeatures',
                features: Ext.encode(features)
            },
            timeout: 120000,

            success: function(response, opts) {
                var reply = Ext.decode(response.responseText);
                if( reply.success )
                {
                    //var isLicensed = reply.records.isLicensedFeature;
                    //var featurename = reply.records.featurename;
                    var feature;
                    for (var i in reply.records.features)
                    {
                      feature = reply.records.features[i];
                      this.handleLicensedFeature( feature.featurename, feature.isLicensed );
                    }
                    //this.handleLicensedFeature( featurename, isLicensed );  // let the controller handle feature licensing display
                }
            },
            failure: function(response, opts) {
                var reply;
                if( response && response.responseText )
                reply = Ext.decode(response.responseText);
                // no error message for these problems
                isLicensed = false;  // if we cannot contact the server, disable the feature until we can!
                this.handleLicensedFeature( featurename, isLicensed );  // let the controller handle feature licensing display

                return false;
            }
        });

        return true;
    },

    loadTab: function( title, uri, iconCls ) {
        var newPanel = Ext.create('widget.tabcontent', {
            title: title,
            closable : (title == 'Welcome' ? false : true)
        });
        var tabPanel = this.getTabHolder();
        var tab = tabPanel.items.findBy(function (i) {
            return i.title === title;
        });

       if (!tab) {                          // if tab does not already exist

        tab = tabPanel.add(newPanel);

        this.registerEventsToChildComponent(tab);

        if( iconCls ) {
           tab.tab.setIconCls( iconCls );  // carry over the tree control's icon to the tab
        }
        //this.reload = false;
        tabPanel.setActiveTab(tab);
        //this.reload = true;
        var tabPanel = this.getTabHolder();
        var active = tabPanel.getActiveTab();
        var framePane = active.query('simpleiframe');
        var lastframePane = framePane[framePane.length - 1];

        var myMask = "Please Wait...";
        active.setLoading(myMask);

        Ext.defer(function () {
            if(uri.indexOf('gettingstarted') !== -1) {
              this.openGettingStarted(uri, lastframePane, active);
            }
            else {
              lastframePane.setSrc(uri);
              active.setLoading(false);
            }
        }, 500, this);
      } else if (tab) {
            tabPanel.setActiveTab(tab);
     }
    },

    openGettingStarted: function(uri, lastframePane, active) {

      // #14837
      lastframePane.setSrc(uri);
      active.setLoading(false);
      return;

      // essential license
      if(Util.Util.isCloudEssentials(window.licenseinfo.productID) || window.top.gettingStartedOption === 'essentials') {
        uri = uri.replace('gettingstarted', 'cloudessentials');

        lastframePane.setSrc(uri);
        active.setLoading(false);
      }
      // if no marketplace license associated - default license
      else if(window.top.licenseinfo.model === 'key' && (!window.top.gettingStartedOption || ['essentials', 'enterprise'].indexOf(window.top.gettingStartedOption) === -1)) {
        Ext.create({
          xtype: 'window',
          autoShow: true,
          modal: true,
          closable: false,
          width: 430,
          title: 'License Options',
          layout: 'fit',
          items: {
            xtype: 'form',
            bodyPadding: 5,
            border: false,
            items: {
              xtype: 'fieldset',
              title: 'Which SoftNAS version will you be using on this Instance?',
              items: [{
                xtype: 'radiogroup',
                allowBlank: false,
                columns: 1,
                defaults: {
                  name: 'licenseChoise'
                },
                items: [{
                  boxLabel: 'SoftNAS Cloud Essentials',
                  inputValue: 'essentials'
                },{
                  boxLabel: 'SoftNAS Cloud Enterprise (formerly known as SoftNAS Cloud NAS)',
                  inputValue: 'enterprise'
                }]
              }]
            },
            buttons: ['->', {
              text: 'OK',
              formBind: true,
              ui: 'default',
              scope: this,
              handler: function(btn) {
                var win = btn.up('window'),
                    opt = win.down('radiogroup').getValue().licenseChoise;

                win.setLoading();

                Ext.Ajax.request({
                  url: '/buurst/snserver/snserv.php',
                  scope: this,
                  params: {
                    opcode: 'gettingstarted',
                    command: 'modifyspecificsettings',
                    option: opt
                  },
                  success: function() {
                    if(opt === 'essentials') {
                      uri = uri.replace('gettingstarted', 'cloudessentials');
                    }

                    win.setLoading(false);
                    win.close();
                    lastframePane.setSrc(uri);
                    active.setLoading(false);
                  }
                });
              }
            }]
          }
        });
      }
      else {
        lastframePane.setSrc(uri);
        active.setLoading(false);
      }

      // force window message about reboot to front
      window.pendingReboot && show_pendingreboot_message(window.pendingReboot);
    },

    afterLaunch: function() {
        /*var controller = top.window.maincontroller;
        controller.isLicensedFeature( [
            "schedsnapshots", "snapreplicate", "iscsitarget", "iscsiinitiator", "activedirectory",
            "deltasync", "smarttiers", "ultrafast", "flexfiles", "flexfiles_architect", "dualha"
        ], this );*/
    },

    beforeBrowserClose: function(e)
    {
        // delete session cookie before exiting
        document.cookie = "USER_SS_port" + location.port + "=" + new Date(0).toUTCString() + '; path=/';  // for chrome
    },

    resetSessionTimer: function()
    {
        top.window.activity = top.window.activity + 1;        // indicate activity has taken place
    },

    validateSessionLogout: function ()  // validate the session is still active, if inactive then redirect to session timeout page
    {
    	if(window['only_localhost'] !== undefined){
    		console.log('not checking session');
    		return;
    	}

        var ctrl = top.window.maincontroller;
        var softnas_port = ctrl.getPort();
        var sessionCookie = Ext.util.Cookies.get('USER_SS_port' + softnas_port);
        if( sessionCookie === null || sessionCookie === undefined )
        {
        	ctrl.saveProgRegInputs();
	        window.top.location.href = "/buurst/timeout.php";    // redirect to timeout page
        }
        else  // session cookie remains valid, reset the session timer on the server
        {
          if( top.window.activity > 0 )  // there has been session activity
          {
              top.window.activity = 0;

              // send timer reset signal to server to indicate new activity
              var url = "/buurst/snserver/snserv.php";
              Ext.Ajax.request({
                  url: url,
                  scope: this,
                  method : 'POST',
                  params : {
                      opcode: 'resetsessiontimer'
                  },
                  timeout: 10000,

                  success: function(response, opts) {
                      var reply;
                      if( response && response.responseText )
                      {
                          reply = Ext.decode(response.responseText);
                          if (reply.success !== undefined && reply.success == false) {
                              if (reply.msg !== undefined) {
                                Ext.Msg.alert('Storagecenter Error', reply.msg);
                                return;
                              }
                          }
                          var data = reply.records;
                          if(data.is_registered !== undefined && data.is_registered == true){
    		                    	//StorageCenterApp.controllers.get('MainCtrl').hideProdRegButton();
    		                    	iFrame.getApplication().getController('MainCtrl').hideProdRegButton();
    		                    	if(Ext.ComponentQuery.query('#ProdRegWindow')[0].isVisible())
    		                    	{
    			                    	iFrame.prodRegW.close();
    			                    	Ext.Msg.alert('Product Registration', 'Product already registered');
    		                    	}
    		                    	return;
    		                  }
                          if(Ext.ComponentQuery.query('#ProdRegWindow')[0].isVisible())
                          {
                          	ctrl.saveProgRegInputs();
                          }
                      }
                  },
                  failure: function(response, opts) {
                      var reply;
                      if( response && response.responseText )
                      reply = Ext.decode(response.responseText);
                      return false;
                  }
              });
          }
          // reset timer for next time
          setTimeout( ctrl.validateSessionLogout, 30000 );    // validate the session is still active
        }
    },

    saveProgRegInputs: function(reset_uptime)
    {
        if(reset_uptime === undefined && reset_uptime !== true){
            reset_uptime = false;
        }

    	var prodRegWnd = Ext.ComponentQuery.query('#ProdRegWindow')[0];

    	var comboJobF = prodRegWnd.query("#prodRegJobFunction")[0];
        var comboIndustry = prodRegWnd.query("#prodRegIndustry")[0];
        var comboCountry = prodRegWnd.query("#prodRegCountry")[0];

      var url = "/buurst/snserver/snserv.php";
      Ext.Ajax.request({
          url: url,
          scope: this,
          method : 'POST',
          params : {
              opcode: 'prodreg_inputs',
              command: 'save',
              reset_uptime: reset_uptime ? "true" : "false",

                prodRegFirstName: 		prodRegWnd.query("#prodRegFirstName")[0].getValue(),
                prodRegLastName: 		prodRegWnd.query("#prodRegLastName")[0].getValue(),
                prodRegJobFunction: 	comboJobF.getDisplayValue(),
                prodRegJobFunctionVal: 	comboJobF.getValue(),
                prodRegJobTitle: 		prodRegWnd.query("#prodRegJobTitle")[0].getValue(),
                prodRegBusinessPhone: 	prodRegWnd.query("#prodRegBusinessPhone")[0].getValue(),
                prodRegBusinessEmail: 	prodRegWnd.query("#prodRegBusinessEmail")[0].getValue(),
                prodRegCreateAccount: 	prodRegWnd.query("#prodRegCreateAccount")[0].getValue(),
                //prodRegPassword: 		prodRegWnd.query("#prodRegPassword")[0].getValue(),
                //prodRegPasswordConfirm: prodRegWnd.query("#prodRegPasswordConfirm")[0].getValue(),

                prodRegAccountId: 		prodRegWnd.query("#prodRegAccountId")[0].getValue(),
                prodRegInstanceId: 		prodRegWnd.query("#prodRegInstanceId")[0].getValue(),
                prodRegCompany: 		prodRegWnd.query("#prodRegCompany")[0].getValue(),
                prodRegIndustry: 		comboIndustry.getDisplayValue(),
                prodRegIndustryVal: 	comboIndustry.getValue(),
                prodRegAddress1: 		prodRegWnd.query("#prodRegAddress1")[0].getValue(),
                prodRegAddress2: 		prodRegWnd.query("#prodRegAddress2")[0].getValue(),
                prodRegCity: 			prodRegWnd.query("#prodRegCity")[0].getValue(),
                prodRegZip: 			prodRegWnd.query("#prodRegZip")[0].getValue(),
                prodRegCountry: 		comboCountry.getDisplayValue(),
                prodRegCountryVal: 		comboCountry.getValue(),
                prodRegCheckUpgrades: 	prodRegWnd.query("#prodRegCheckUpgrades")[0].getValue(),
                prodRegCheckPromotions: prodRegWnd.query("#prodRegCheckPromotions")[0].getValue(),
                prodRegCheckNotShowAgain:prodRegWnd.query("#prodRegCheckNotShowAgain")[0].getValue()
          },
          timeout: 10000,

          success: function(response, opts) {
              /*var reply;
              if( response && response.responseText )
              {
                  reply = Ext.decode(response.responseText);
                  var records = reply.records;
              }*/

          },
          failure: function(response, opts) {
              /*var reply;
              if( response && response.responseText )
              reply = Ext.decode(response.responseText);
              return false;*/

          }
      });
    },

    getProgRegInputs: function()
    {
    	var prodRegWnd = Ext.ComponentQuery.query('#ProdRegWindow')[0];

    	var comboJobF = prodRegWnd.query("#prodRegJobFunction")[0];
        var comboIndustry = prodRegWnd.query("#prodRegIndustry")[0];
        var comboCountry = prodRegWnd.query("#prodRegCountry")[0];

      var url = "/buurst/snserver/snserv.php";
      prodRegWnd.mask();
      Ext.Ajax.request({
          url: url,
          scope: this,
          method : 'POST',
          params : {
              opcode: 'prodreg_inputs',
              command: 'get',
          },
          timeout: 10000,

          success: function(response, opts) {
              prodRegWnd.unmask();
              var reply;
              if( response && response.responseText )
              {
                  reply = Ext.decode(response.responseText);
                  if(reply && reply.records !== undefined && typeof(reply.records) === "object")
                  {
                    var data = reply.records;

                    if(data.is_registered !== undefined && data.is_registered == true){
                    	//StorageCenterApp.controllers.get('MainCtrl').hideProdRegButton();
                    	iFrame.getApplication().getController('MainCtrl').hideProdRegButton();
                    	iFrame.prodRegW.close();
                    	Ext.Msg.alert('Product Registration', 'Product already registered');
                    	return;
                    }

	                prodRegWnd.query("#prodRegFirstName")[0].setValue(data.prodRegFirstName);
	                prodRegWnd.query("#prodRegLastName")[0].setValue(data.prodRegLastName);
	                //comboJobF.setDisplayValue(data.prodRegJobFunction),
	                comboJobF.setValue(data.prodRegJobFunctionVal);
	                prodRegWnd.query("#prodRegJobTitle")[0].setValue(data.prodRegJobTitle);
	                prodRegWnd.query("#prodRegBusinessPhone")[0].setValue(data.prodRegBusinessPhone);
	                prodRegWnd.query("#prodRegBusinessEmail")[0].setValue(data.prodRegBusinessEmail);
	                prodRegWnd.query("#prodRegCreateAccount")[0].setValue(data.prodRegCreateAccount);
	                //prodRegWnd.query("#prodRegPassword")[0].setValue(data.prodRegPassword);   ???
	                //prodRegWnd.query("#prodRegPasswordConfirm")[0].setValue(data.prodRegPasswordConfirm); ???

	                //prodRegWnd.query("#prodRegAccountId")[0].setValue(data.prodRegAccountId),
	                //prodRegWnd.query("#prodRegInstanceId")[0].setValue(data.prodRegInstanceId),
	                prodRegWnd.query("#prodRegCompany")[0].setValue(data.prodRegCompany);
	                //comboIndustry.setDisplayValue(data.prodRegIndustry),
	                comboIndustry.setValue(data.prodRegIndustryVal);
	                prodRegWnd.query("#prodRegAddress1")[0].setValue(data.prodRegAddress1);
	                prodRegWnd.query("#prodRegAddress2")[0].setValue(data.prodRegAddress2);
	                prodRegWnd.query("#prodRegCity")[0].setValue(data.prodRegCity);
	                prodRegWnd.query("#prodRegZip")[0].setValue(data.prodRegZip);
	                //comboCountry.setDisplayValue(data.prodRegCountry),
	                comboCountry.setValue(data.prodRegCountryVal);
	                //prodRegCountryIndex:	comboCountry.store.indexOf(record),
	                prodRegWnd.query("#prodRegCheckUpgrades")[0].setValue(data.prodRegCheckUpgrades);
	                prodRegWnd.query("#prodRegCheckPromotions")[0].setValue(data.prodRegCheckPromotions);
	                prodRegWnd.query("#prodRegCheckNotShowAgain")[0].setValue(data.prodRegCheckNotShowAgain);

                    prodRegWnd.onProdRegBusinessEmailBlur();
                    prodRegWnd.onProdRegPasswordChange();

                    prodRegWnd.query("#prodRegFirstName")[0].focus();
                  }
              }
          },
          failure: function(response, opts) {
              prodRegWnd.unmask();
              var reply;
              if( response && response.responseText )
              reply = Ext.decode(response.responseText);
              return false;
          }
      });
    },

    onLaunch: function()
    {
        top.window.maincontroller = this;                     // this is used to navigate back to this object to call loadTab() and load top level applets
        top.window.activity = 0;
        setTimeout( this.afterLaunch, 50);                    // trigger the feature license lockdown in the background
        // License Agreement
        Ext.Ajax.request({
            url: '/buurst/snserver/snserv.php?opcode=readini',
            scope: this,
            method: 'GET',
            timeout: 10000,
            success: function (response) {
                const reply = Ext.decode(response.responseText);
                const data = reply.records.ini;
                if ('registration' in data && 'agreement' in data.registration && data.registration.agreement === 'true') {
                    top.window.maincontroller.processSettings();
                } else {
                    const agreementWindow = Ext.create('iFrame.view.AgreementWindow');
                    agreementWindow.onDisagree = function () {
                        agreementWindow.close();
                        window.top.location.replace('/buurst/html/declined.html');
                    };
                    agreementWindow.onAgree = function () {
                        top.window.maincontroller.submitAgreeAgreement(function () {
                            agreementWindow.close();
                            top.window.maincontroller.processSettings();
                        });
                    };
                    agreementWindow.show();
                }
            }
        });
        setTimeout( this.validateSessionLogout, 30000);       // validate the session is still active
        // note these events are ONLY for the main window (not the iframe sub-windows)
        document.onmousemove = this.resetSessionTimer;        // keep track of user activity
        document.onmousedown = this.resetSessionTimer;        // keep track of user activity
        document.onkeydown = this.resetSessionTimer;          // key tracking NOT working!

// This was removed because it can cause issues with browser Refresh and potentially login loops - needs further work before enabling...
//        top.window.onbeforeunload = this.beforeBrowserClose;  // fire event before main window closes to delete cookie and force a login
    },

    nodeselection: function (sm, selectedRecord) {
    	var me = this,
          dataSelectedRecord = selectedRecord.getData(),
          id = dataSelectedRecord.id,
          enableFlexfilesWin,
          betaWin;

        // 3547 - prevent doubleclick
        if (Date.now !== undefined) { // older IE
        	var time = Date.now();
        	if (window.top['click_'+id] === undefined) {
        		window.top['click_'+id] = time;
	        } else {
		        var click_delay = Math.abs(time - window.top['click_'+id]);
		        window.top['click_'+id] = time;
		        if (click_delay < 500) {
		            return;
		        }
	        }
        }

        var title = dataSelectedRecord.text;
        var uri = dataSelectedRecord.uri;
        var iconCls = dataSelectedRecord.iconCls;

        if( uri == undefined || uri == "" )                   // ignore this node when single-clicked
        {
            return;
        }
        var disabled = dataSelectedRecord.disabled;
        if( disabled )
        {
            Ext.Msg.alert('Feature not supported', title + " is not supported by this license.  Please upgrade your license to enable access.");
            return;
        }

        if( uri == "logout.php" ) {
          window.top.location.href = "/buurst/logout.php";
        }
        else if( uri == "../html/webadmin.php" ) {
          me.loadLocation({location_name:"webmin", path:uri}); // #2576
          window.open(uri, "webadmin" );
        }
        else if( uri.indexOf( "http://www.softnas.com" ) != -1 )
        {
          me.loadLocation({location_name:"www.softnas.com", path:uri}); // #2576
          window.open(uri, title );  // open external sites in separate browser window to avoid CORS security restrictions
        }
        else if( uri.indexOf( "https://docs.softnas.com" ) != -1)
        {
          me.loadLocation({location_name:"docs.softnas.com", path:uri}); // #2576
          window.open(uri, title );  // open external sites in separate browser window to avoid CORS security restrictions
        }
        /*else if(uri.indexOf('{0}:8080/nifi') !== -1) {
          uri = Ext.String.format(uri, window.licenseinfo.local_ip);
          window.open(uri);
        }*/
        else // open a tab with applet/widget content
        {
            if(dataSelectedRecord.needEnableFlexfiles || dataSelectedRecord.isBeta) {
                if (!window.licenseinfo.is_platinum) {
                    Ext.Msg.alert('Platinum/Fuusion Feature', 'A Buurst Fuusion license is required to proceed with the use of Fuusion.Please contact Buurst using the button below to discuss your Fuusion project.', function(btn) {if(btn=="ok") window.open("https://www.buurst.com/contact") });			
                    return;
                }
                else if (dataSelectedRecord.needEnableFlexfiles && !window.top.flexfilesEnabled) {

                    enableFlexfilesWin = me.getEnableFlexfilesWindow();

                    if (enableFlexfilesWin) {
                        enableFlexfilesWin.setClickDelay(click_delay);
                        enableFlexfilesWin.setDataSelectedRecord(dataSelectedRecord);
                        enableFlexfilesWin.show();
                        return;
                    }
                }
                else if (dataSelectedRecord.isBeta && !window.top.betaAgreementAccepted) {

                    betaWin = me.getBetaWindow();

                    if (betaWin) {
                        betaWin.setClickDelay(click_delay);
                        betaWin.setDataSelectedRecord(dataSelectedRecord);
                        betaWin.show();
                        return;
                    }
                }
            }
            me.openTab(dataSelectedRecord, click_delay);
      	}
    },

    openTab: function(dataSelectedRecord, clickDelay) {
      var me = this,
          hideMode = 'display',
          title = dataSelectedRecord.text,
          uri = dataSelectedRecord.uri,
          iconCls = dataSelectedRecord.iconCls,
          myMask = "Please Wait...",
          newPanel,
          tabPanel,
          tab,
          active,
          framePane,
          lastframePane,
          iframe;

      if(dataSelectedRecord.hideModeOffsets) {
        hideMode = 'offsets';
      }

      newPanel = Ext.create('widget.tabcontent', {
          title: title,
          hideMode: hideMode
      });

      tabPanel = me.getTabHolder();
      tab = tabPanel.items.findBy(function (i) {
          return i.title === title;
      });

      if (title !== "Some links" && !tab) {
          me.loadLocation({location_name:title, path:uri}); // #2576
          tab = tabPanel.add(newPanel);

          me.registerEventsToChildComponent(tab);

          if( iconCls ) {
             tab.tab.setIconCls( iconCls );  // carry over the tree control's icon to the tab
          }
          //me.reload = false;
          tabPanel.setActiveTab(tab);
          //me.reload = true;
          tabPanel = me.getTabHolder();
          active = tabPanel.getActiveTab();
          framePane = active.query('simpleiframe');
          lastframePane = framePane[framePane.length - 1];

          active.setLoading(myMask);

          if(uri.indexOf('flexfiles') > -1 || uri.indexOf('nifi.php') > -1) {
              active.setLoading('Loading ...');

              Ext.Ajax.request({
                url: '/buurst/snserver/snserv.php',
                scope: me,
                timeout: 360000,
                params: {
                  opcode: 'flex_check_nifiready'
                },
                success: function(response) {
                  response = Ext.decode(response.responseText);

                  if(!response.success) {
                    Ext.Msg.alert('Failure', response.msg);
                    active.setLoading(false);
                    return;
                  }

                  lastframePane.setSrc(uri);
                  active.setLoading(false);

                  if(uri === '/flexfiles-architect/nifi/' || dataSelectedRecord.id === 'flexfilesArchitectNode') {
                    tab.on('activate', me.onActivateNifi, me);
                    me.onActivateNifi(tab);
                  }
                  // workaround to work in iframe external general system - this is temporary while the system not is refactored
                  else if(uri === '../applets/flexfiles#settings') {
                    tab.on('activate', me.onActivateFlexfilesSettings, me);
                  }
                }
              });

              return;
          }

          Ext.defer(function () {
            if(uri.indexOf('gettingstarted') !== -1) {
              me.openGettingStarted(uri, lastframePane, active);
            }
            else {
              lastframePane.setSrc(uri);
              active.setLoading(false);
            }
          }, 500, me);

       } else if (tab) {
          if (uri == "../html/webadmin.php?path=/exports" && clickDelay > 10000) {
              var now = new Date();

              if('lastTimeViewed' in tab && (now.getTime() - 10000) < tab.lastTimeViewed ) return;

              if(!'lastTimeViewed' in tab)
              {
                  tab.self.addMembers({lastTimeViewed : now.getTime()});
              }
              else
              {
                  tab.lastTimeViewed = now.getTime();
              }

              framePane = tab.query('simpleiframe');
              lastframePane = framePane[framePane.length - 1];
              iframe = lastframePane.getDOM();

              tab.setLoading(myMask);

              Ext.defer(function () {
                  iframe.contentWindow.location.reload(true);
                  //lastframePane.reload();
                  tab.setLoading(false);
              }, 500);
          };
          tabPanel.setActiveTab(tab);
      }

      if(uri === '/flexfiles-architect/nifi/' || dataSelectedRecord.id === 'flexfilesArchitectNode') {
        tab.on('activate', me.onActivateNifi, me);
        me.onActivateNifi(tab);
      }
      // workaround to work in iframe external general system - this is temporary while the system not is refactored
      else if(uri === '../applets/flexfiles#settings') {
        tab.on('activate', me.onActivateFlexfilesSettings, me);
      }
    },

    registerEventsToChildComponent: function(tab) {
      var me = this,
          iFrameCmp = tab.down('simpleiframe'),
          doc,
          win;

      // wait the iframe load
      iFrameCmp.on('load', function() {
        doc = iFrameCmp.getDocument();
        win = doc.parentWindow || doc.defaultView;  // parentWindow to IE

        // Wait load the Ext JS library
        Ext.defer(function() {

          if (typeof win.Ext !== 'undefined') { // some applets are not an Ext JS application (#14191)
            // onReady of the panel into iframe
            win.Ext.onReady(function() {
              var tab = this;

              // wait the state of components (activate, hidden, show, etc)
              Ext.defer(function() {
                var tab = this,
                    doc = tab.down('simpleiframe').getDocument(),
                    win = doc.parentWindow || doc.defaultView, // parentWindow to IE
                    childPanel = win.Ext.ComponentQuery.query('viewport > panel[hidden=false]')[0];

                childPanel && childPanel.on({
                  opennewtab: {fn: me.loadTab, scope: me}, // open a new tab (#14711)
                  close: {fn: me.destroyTab, scope: tab} // on close the child panel, close also the tab
                });

              }, 200, tab);
            }, tab);
          }

        }, 500, tab);
      }, me);
    },

    destroyTab: function() {
      this.destroy();
    },

    // workaround to work in iframe external general system - this is temporary while the system not is refactored
    onActivateFlexfilesSettings: function(tab) {
      var iframeCmp = tab.down('simpleiframe'),
          extRef = iframeCmp ? iframeCmp.getDocument().defaultView.Ext : null,
          flexFilesSettingsCmp,
          settingsViewController;

      // ext is ready then view is ready
      if(extRef && extRef.ComponentQuery) {
        flexFilesSettingsCmp = extRef.ComponentQuery.query('flexfilessettings')[0];

        if(flexFilesSettingsCmp) {
          settingsViewController = flexFilesSettingsCmp.getController();
          if (settingsViewController.getViewModel().get('isSavingSettings') !== true) {
            settingsViewController.boxReady.call(settingsViewController);
          }
        }
      }
    },

    onActivateNifi: function(tab) {
      var me = this,
          tabPanel = tab.up('tabpanel'),
          // activeTab = tabPanel.getActiveTab(),
          // iframe = activeTab && activeTab.query('simpleiframe'),
          records;

      Ext.Ajax.request({
          url: '/buurst/snserver/snserv.php',
          scope: me,
          params: {
            opcode: 'flex_get_nificonfig'
          },
          success: function(response) {
            response = Ext.decode(response.responseText);

            if(response) {

              records = response.records;

              if((records.flexfiles && !records.flexfiles.configured) || (records.repository && !records.repository.valid)) {
                Ext.Msg.show({
                  title:'Configuration Required',
                  message: 'Please complete the initial setup before using FlexFiles Architect.',
                  buttons: Ext.Msg.OKCANCEL,
                  scope: me,
                  buttonText: {
                    ok: 'Setup'
                  },
                  fn: function(opt) {
                    tabPanel.remove(tab);

                    if(opt === 'ok') {
                      me.loadTab('FlexFiles Settings', '../applets/flexfiles#settings', 'icon-settings'); //open a new tab (#14711)
                      // iframe && iframe[0].setSrc('../applets/flexfiles#settings');
                    }
                  }
                });
              }
            }
          }
      });
    },

    tabchange : function( tabPanel, newCard, oldCard, eOpts ) {
        var now = new Date(),
            iframeCmp,
            extRef,
            viewToUpdateLayout;

        // #4260 - avoid broken layout in EDGE browser when change tab while performing any action in tabchange - this problem is because use of iframe. Including Ext.isEdge is available only in version 6 of extjs, no in version 5
        // #14920 - Applet does not load properly - removing IF part
        //if(navigator.appVersion.indexOf('Edge/') !== -1 || Ext.isFirefox) {
          iframeCmp = newCard.down('simpleiframe');
          extRef = iframeCmp ? iframeCmp.getDocument().defaultView.Ext : null;

          // ext is ready then view is ready
          if(extRef && extRef.ComponentQuery) {
            viewToUpdateLayout = extRef.ComponentQuery.query('viewport')[0];
            viewToUpdateLayout && viewToUpdateLayout.updateLayout();
          }
        //}

        if(newCard.title == 'CIFS Shares'){
            var frame_webmin_samba = newCard.el.dom.getElementsByTagName('iframe')[0].contentDocument.getElementById('cifsWebminFrame');
            if(frame_webmin_samba){
                frame_webmin_samba.contentWindow.location.reload(true);
            }
            return;
        }

        if(newCard.title == 'AFP Volumes') {
            var frame_webmin_samba = newCard.el.dom.getElementsByTagName('iframe')[0];
            if(frame_webmin_samba){
                frame_webmin_samba.contentWindow.location.reload(true);
            }
            return;
        }

        if(newCard.title == 'Volumes and LUNs') {
            this.maybeShouldTriggerEvenActivateVolumesTab(newCard);
        }


        if (newCard.title == 'Disk Devices') {
          // update Disk Divees grid after changeing tab
          // #4367
          var doc = newCard.down('simpleiframe').getDocument(),
              win = doc.parentWindow || doc.defaultView, // parentWindow to IE
              devicesGrid;

          if (typeof win.Ext !== "undefined") {
            devicesGrid = win.Ext.getCmp('diskDevicesGrid');

            // load just if grid is ready
            devicesGrid && devicesGrid.getStore().load();
          }
        }


        if(oldCard && oldCard.title == 'NFS Exports') {
            if(!'lastTimeViewed' in oldCard)
            {
                oldCard.self.addMembers({lastTimeViewed : now.getTime()});
            }
            else
            {
                oldCard.lastTimeViewed = now.getTime();
            }
            return;
        } else if (newCard.title != 'NFS Exports' ) {
          return;
        }

        if('lastTimeViewed' in newCard && (now.getTime() - 10000) < newCard.lastTimeViewed )
              return;

        var framePane = newCard.query('simpleiframe');
        var lastframePane = framePane[framePane.length - 1];
        var iframe = lastframePane.getDOM();

        var myMask = "Please Wait...";
        newCard.setLoading(myMask);

        Ext.defer(function () {

            iframe.contentWindow.location.reload(true);
            newCard.setLoading(false);


        }, 500);
    },

    maybeShouldTriggerEvenActivateVolumesTab: function(newCard) {
         var now = new Date(),
            iframeCmp = newCard.down('simpleiframe'),
            volumesApp = iframeCmp ? iframeCmp.getDocument().defaultView.VolumesApp : null,
            gridController = volumesApp && volumesApp.getController ? volumesApp.getController('GridController') : null;

            if(gridController) {
                gridController.fireEvent('activevolumestab', gridController);
            }
    },
    /*tabchange : function( tabPanel, newCard, oldCard, eOpts ) {

        var now = new Date();
        if(oldCard)
        {
            if(!'lastTimeViewed' in oldCard)
            {
                oldCard.self.addMembers({lastTimeViewed : now.getTime()});
            }
            else 
            {
                oldCard.lastTimeViewed = now.getTime();
            }
        }
        

        if(this.reload) {
            
            if('lastTimeViewed' in newCard && (now.getTime() - 60000) < newCard.lastTimeViewed )
              return;

            var framePane = newCard.query('simpleiframe');
            var lastframePane = framePane[framePane.length - 1];

            var myMask = "Please Wait...";
            newCard.setLoading(myMask);

            Ext.defer(function () {

                lastframePane.reload();
                newCard.setLoading(false);
                

            }, 500);
        }
        //console.info(oldCard);
    },*/
    treeExpand : function ( p, eOpts )
    {

    }

    ,

    showEmailWindow: function(email_input){

        var url = "/buurst/snserver/snserv.php";

        var me = this;

    	if(email_input !== undefined && email_input !== ""){

            var win = me.getEmailSetup();
            win.show();
            win.query('#email')[0].setValue(email_input);
            win.query('#emailConf')[0].setValue(email_input);
    	    return;
    	}

        Ext.Ajax.request({
            url: url,
            scope: this,
            method : 'POST',
            params : {
                opcode: 'email_setup',
                command: 'check'
            },
            timeout: 30000,

            success: function(response, opts) {


                var reply = Ext.decode(response.responseText);
                if(reply)
                {
                    if(reply.success)
                    {
                        var win = me.getEmailSetup();
                        if(reply.records.show_email_setup)
                        {
                            win.show();
                        }
                    }
                    else
                    {
                        Ext.Msg.alert('Failed', reply.msg);
                    }
                }
                else
                {
                    Ext.Msg.alert('Failed', 'Unable to read the buurst configuration settings.');
                }

            },
            failure: function(response, opts) {
                switch (opts.failureType) {
                        case Ext.form.action.Action.CLIENT_INVALID:
                            Ext.Msg.alert('Failure', 'Form fields may not be submitted with invalid values');
                            break;
                        case Ext.form.action.Action.CONNECT_FAILURE:
                            Ext.Msg.alert('Failure', 'Ajax communication failed');
                            break;
                        case Ext.form.action.Action.SERVER_INVALID:
                           Ext.Msg.alert('Failure', "Server Error");
                   }
                return false;
            }
        });

    },

    loadLocation: function(options) {
    	if (options === undefined || options.location_name === undefined) {
    		return;
    	}
        Ext.Ajax.request({
            url: "/buurst/snserver/snserv.php",
            scope: this,
            method : 'POST',
            params : {
                opcode: 'loading_location',
                command: options.location_name,
                location: options.path !== undefined ? options.path : ""
            },
            //timeout: 30000
        });
    },

    disableProdRegButton: function(){
    	var btnProd = Ext.get("btnProdReg");
        btnProd.dom.className = "btn-prod-disabled";
		btnProd.dom.onclick = "";
		btnProd.setHTML('Product Registration');
    },

    hideProdRegButton: function(){
    	Ext.get("btnProdReg").hide();
    },

    processSettings: function(){
        var me = this,
            url = "/buurst/snserver/snserv.php";

        Ext.Ajax.request({
            url: url,
            scope: this,
            method : 'POST',
            params : {
                opcode: 'gettingstarted',
                command: 'getsettings'      // get the latest config settings
            },
            timeout: 60000,

            success: function(response, opts) {
                if( response )
                {
                    var reply = Ext.decode(response.responseText);
                    var data = (reply.data === undefined) ? {} : reply.data;

                    const westPanel = top.Ext.getCmp('treenav');
                    westPanel.expand();

                    if(data.showWelcomeOnStartup === undefined){
                        data.showWelcomeOnStartup = "1";
                    }
                    if(data.showOnStartup === undefined){
                        data.showOnStartup = "1";
                    }
                    if(data.agreement === undefined){
                        data.agreement = false;
                    }

                    if(data.flexfilesEnabled === undefined){
                      data.flexfilesEnabled = false;
                    }

                    if(data.betaAgreementAccepted === undefined){
                      data.betaAgreementAccepted = false;
                    }

                    if(data.showWelcomeOnStartup === "1" )
                    {
                        me.loadTab( "Getting Started", "../applets/welcome/", "icon-welcome" );
                    }

                    window.top.show_getting_started = false;
                    window.top.agreement = data.agreement;
                    window.top.flexfilesEnabled = data.flexfilesEnabled;
                    window.top.betaAgreementAccepted = data.betaAgreementAccepted;
                    window.top.gettingStartedOption = data.option;

                    if( data.agreement == true && data.showWelcomeOnStartup != "1")
                    {
                        //me.launchNodeByTitle( "Getting Started" );
                        //me.loadTab( "Getting Started", "../applets/welcome/", "icon-welcome" );
                    }
                    if(data.show_password_warning === true) {
                        me.loadTab('Initial Password', '../snserver/snserv.php?opcode=change_pwd_warning', 'icon-welcome');
                    }
                }
                return true;
            },
            failure: function(response, opts) {
                var reply;
                if( response && response.responseText )
                reply = Ext.decode(response.responseText);

                if( reply && reply.msg )
                Ext.Msg.alert('Operation Failed', reply.msg);
                else
                Ext.Msg.alert('Operation Failed', "Error contacting server. Please resolve any network issues and try again.");
                return false;
            }
        });

        return true;

    },

	getPort: function(){
		if(location.port !== undefined && location.port && location.port != ''){
			return location.port;
		}
		else if(location.protocol.indexOf('https') != -1){
			return 443;
		}
		else if(location.protocol.indexOf('http') != -1){
			return 80;
		}
	},

    onPlatinumSubmitButtonClick: function() {
        var me = this,
            activationCode,
            addPlatinumWindow,
            dataSelectedRecord,
            params,
            reply,
            enableFlexfilesWin,
            betaWin;

        if(me.getAddPlatinumForm().isValid()) {
            activationCode = me.getPlatinumActivationCode().value;
            addPlatinumWindow = me.getAddPlatinumWindow();
            dataSelectedRecord = addPlatinumWindow.getDataSelectedRecord();

            addPlatinumWindow.mask();

            params = {
                opcode: 'submit_platinum_license',
                license_key: me.getPlatinumKeyTextfield().value,
                reg_name: me.getPlatinumRegNameTextfield().value
            };

            if(activationCode) {
                params.activation_code = activationCode;
            }

            Ext.Ajax.request({
                url: '/buurst/snserver/snserv.php',
                scope: this,
                method: 'POST',
                params: params,
                timeout: 60000,
                success: function(response) {
                    addPlatinumWindow.unmask();

                    if(response && response.responseText) {
                        reply = Ext.decode(response.responseText);

                        if(!reply.success) {
                            Ext.Msg.alert('Operation Failed', reply.msg);
                        }
                        else {
                            window.licenseinfo.is_platinum = true;

                            Ext.Msg.alert('License added', 'Platinum license has been added successfully', function() {
                                addPlatinumWindow.close();

                                if(dataSelectedRecord) {
                                    if(dataSelectedRecord.needEnableFlexfiles && !window.top.flexfilesEnabled) {
                                        enableFlexfilesWin = me.getEnableFlexfilesWindow();
                                        if(enableFlexfilesWin) {
                                            enableFlexfilesWin.setClickDelay(addPlatinumWindow.getClickDelay());
                                            enableFlexfilesWin.setDataSelectedRecord(dataSelectedRecord);
                                            enableFlexfilesWin.show();
                                        }
                                    }
                                    else if(dataSelectedRecord.isBeta && !window.top.betaAgreementAccepted) {
                                        betaWin = me.getBetaWindow();

                                        if(betaWin) {
                                            betaWin.setClickDelay(addPlatinumWindow.getClickDelay());
                                            betaWin.setDataSelectedRecord(dataSelectedRecord);
                                            betaWin.show();
                                        }
                                    }
                                }
                            });
                        }
                    }
                },
                failure: function(response) {
                    addPlatinumWindow.unmask();

                    if(response && response.responseText) {
                        reply = Ext.decode(response.responseText);
                    }

                    Ext.Msg.alert('Failure', reply.msg ? reply.msg : 'Server error');
                }
            })
        }
    },

    submitAgreeAgreement: function (callback) {
        if (typeof snf !== 'undefined' && snf.client) {
            snf.client.track({
                firstStorageCenterLoad: true
            });
        }
        const params = {
            opcode: "ackagreement",
            fingerprint: typeof snf !== 'undefined' ? snf.fingerprint : null
        };

        Ext.Ajax.request({
            url: '/buurst/snserver/snserv.php',
            scope: this,
            method: 'POST',
            params: params,
            timeout: 30000,
            waitMsg: "Acknowledging agreement - please wait . . .",
            waitTitle: "Updating agreement status",
            success: callback,
            failure: function(response) {
                const reply = Ext.decode(response.responseText);
                Ext.Msg.alert('Failure', reply.msg ? reply.msg : 'Server error');
            }
        });
    }
});
