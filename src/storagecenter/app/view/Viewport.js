Ext.define('iFrame.view.Viewport', {
    extend: 'Ext.container.Viewport',

    requires: [
		'Ext.layout.container.Border',
		'iFrame.view.west.Treenav',
		'iFrame.view.center.TabHolder'
    ],

	layout: 'border',
	padding: '0 5 0 5',

    initComponent: function() {
        var me = this;
        me.items = [
	        {
                region: 'north',
                xtype: 'container',
                itemId: 'mainHeader',
                height: 50,
                header: false,
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                },
                items: [{
                    border: false,
                    id: 'idNorthPanel',
                    html: '<center><h2>Buurst Fuusion&trade; &copy; Buurst Inc., All Rights Reserved</h2></center>', // fallback title
                    flex: 1
                }
                ]
            },
            {
                region  : 'center',
                xtype   : 'tabholder'
            },
            {
                region  : 'west',
                xtype   : 'treenav'
            }
        ];

        // fetch product information from license on server side
        var url = "/softnas/snserver/snserv.php";
        Ext.Ajax.request({
            url: url,
            scope: this,
            method : 'POST',
            params : {
                opcode: 'licenseinfo',
                fulldetails: '1'
            },
            timeout: 30000,

            success: function(response, opts)
            {
                if( response )
                {
                    var reply = Ext.decode(response.responseText);
                    var data = reply.records;
                    window.licenseinfo = data;
                    var prodType = data.producttype;
                    var licType = data.licensetype;
                    var licValid = data.valid;
                    var licStatus = data.status;
                    var regName = data.regname;
                    var licExpiration = data.expiration;
                    var isTrial = data.istrial;
                    var isPerpetual = data.is_perpetual;
                    var isActivated = data.is_activated;
                    var majorversion = data.majorversion;
                    var minorversion = data.minorversion;
                    var updateversion = data.updateversion;
                    var versionStr = data.version;
                    var storeMenu = top.Ext.getCmp('treenav').getStore();

                    var panel = Ext.getCmp('idNorthPanel');
                    panel.body.update(data.header);

                    if(typeof snf !== 'undefined' && snf.client && data.track30DaysOld) {
                        snf.client.track({
                            storageCenterConversion: true,
                            originalFingerprint: data.fingerprint
                        });
                    }

                    if(data.registration !== undefined){
                        window['prodreg_info'] = data.registration;
                        Ext.ComponentQuery.query("#prodRegInstanceId")[0].setValue(data.registration.prodreg_instance_id);
                        var txt_account_id = Ext.ComponentQuery.query("#prodRegAccountId")[0];
                        var account_label = txt_account_id.getFieldLabel();
                        account_label = account_label.replace("Account ID", data.registration.prodreg_account_label);
                        txt_account_id.setFieldLabel(account_label);
                        txt_account_id.setValue(data.registration.prodreg_account);
                    }
                    if(window['prodreg_info'] === undefined){
                        window['prodreg_info'] = {};
                    }
                    window['prodreg_info'].platform = data.platform;

                    window['prodreg_info'].prodreg_not_show_again = data.registration.prodreg_not_show_again;

                    var reginfo = {};
                    if(data.registration !== undefined){
                        reginfo = data.registration;
                    }
                    if(reginfo.is_registered === undefined){
                        reginfo.is_registered = true;
                    }
                    if(reginfo.days_unregistered_count === undefined){
                        reginfo.days_unregistered_count = -1;
                    }
                    if(reginfo.show_getting_started === undefined){
                        reginfo.show_getting_started = false;
                    }
                    if(reginfo.prodreg_not_show_again === undefined){
                        reginfo.prodreg_not_show_again = false;
                    }

                    var btnProd = Ext.get("btnProdReg");

                    if(reginfo.is_registered !== true && reginfo.is_registered !== 'true')
                    {
                        btnProd.setStyle('color', 'red');
                        btnProd.setHtml('Product Registration - unregistered product');

                        if(reginfo.prodreg_not_show_again == false && reginfo.days_unregistered_count >= 7){
                            setTimeout(function(){ iFrame.prodRegW.show(); }, 500);
                        }
                    }else{
                        //StorageCenterApp.controllers.get('MainCtrl').hideProdRegButton();
                        iFrame.getApplication().getController('MainCtrl').hideProdRegButton();
                    }

                    deleteAllRebootCookies();
                    if (data.reboot_array !== undefined && data.reboot_array) {
                        for (i in data.reboot_array) {
                            setCookie(i, data.reboot_array[i], "session");
                        }
                    }
                    if (data.pendingreboot !== undefined && data.pendingreboot && data.pendingreboot.length) {
                        //if (!is_reboot_warning_seen()) {
                            window.pendingReboot = data.pendingreboot;
                            show_pendingreboot_message(data.pendingreboot);
                        //}
                    }
                    
                    var product_license_name = '';
                    if (window.licenseinfo['is_platinum']) {
                        product_license_name = 'Buurst Fuusion';
                        if (window.licenseinfo['product-id'] == 31) {
                            product_license_name = 'Buurst Fuusion';
                        }
                    } else {
                        product_license_name = 'SoftNAS Cloud Enterprise';
                    }
                    var welcome_text = 'Congratulations on your selection of '+ product_license_name +'.\n'+
                            'The edition of '+ product_license_name +' that you have selected includes Buurst Gold Support.\n'+
                            'To learn more the benefits you are entitled to with Buurst Gold Support, please click the Learn More below.';
                    
                    
                    if (window.licenseinfo['storage-capacity-GB'] >= 20000 && window.licenseinfo['storage-capacity-GB'] <= 1000000) {
                        Ext.Ajax.request({
                            url: url,
                            scope: this,
                            method: 'GET',
                            params: {
                                opcode: 'gold_support_welcome'
                            },
                            timeout: 30000,

                            success: function (response, opts) {
                                if (response) {
                                    var reply = Ext.decode(response.responseText);
                                    if (!reply['records']['hideGoldSupportWelcome']) {
                                        Ext.ComponentQuery.query('#goldWelcomeMainText')[0].setValue(welcome_text);
                                        iFrame.goldWelcomeWindow.show();
                                    }
                                }
                            }
                        });
                    }
                    
                    if (data.live_support_enabled === undefined || data.live_support_enabled === true || data.live_support_enabled === 'true') {
                        data.live_support_enabled = true;
                    } else {
                        data.live_support_enabled = false;
                    }
                    if (data.drift_user_id === undefined || !data.drift_user_id || !data.drift_user_id.length) {
                        data.drift_user_id = null;
                    }
                    if (data.live_support_enabled && (data.drift_id != undefined && data.drift_id && data.drift_id.length)) {
                        me.loadLiveSupport(data.drift_id, data.live_support_enabled, data.drift_user_id);
                    }

                    //hide options in the menu according to the license
                    storeMenu.checkLicense();

                    // Check if it is a Buurst cloud license
                    if (data.productID === 66) {
                        storeMenu.proxy.url = "data/tree_buurst.json";
                        storeMenu.load();
                    }
                    // Check if it is a Buurst BYOL
                    if (parseInt(data['storage-capacity-GB']) >= 16384000 && parseInt(data['storage-capacity-GB']) <= 16449536) {
                        storeMenu.proxy.url = "data/tree_buurst.json";
                        storeMenu.load();
                    }
                }
                return true;
            },
            failure: function(response, opts) {
                var reply;
                if( response && response.responseText )
                reply = Ext.decode(response.responseText);

    //            if( reply && reply.msg )
    //            Ext.Msg.alert('Operation Failed', reply.msg);
    //            else
    //            Ext.Msg.alert('Operation Failed', "Error contacting server. Please resolve any network issues and try again.");
    //            return false;
            }
        });

        Ext.Ajax.request({
            url: "/softnas/snserver/snserv.php",
            scope: this,
            method: 'POST',
            params: {
                opcode: 'meterstatus'
            },
            timeout: 30000,

            success: function (response, opts) {
                if (response) {
                    var reply = Ext.decode(response.responseText);
                    if (reply && reply.records !== undefined && reply.records.not_amazon !== undefined) {
                        return;
                    }
                    if (reply.success !== false) {
                        var counter = reply.records.counter;
                        if (counter > 0) {
                            Ext.Msg.alert("Can not connect to AWS Billing Endpoint", "Please correct Network Access! Stopping NAS services in " + (18 - counter) + " hours");
                        }
                    }
                }
            },
            failure: function (response, opts) {

            }
        });

        // complete initialization process
        this.callParent();
    },
    
    loadLiveSupport: function(account_id, is_enabled, drift_user_id) {
        var me = this;
        //"use strict"; ??
        
        if (drift_user_id) { // keep the same contact on different browsers
            setCookie("driftt_eid", drift_user_id, 730);
        }
        
        !function() {
          var t = window.driftt = window.drift = window.driftt || [];
          if (!t.init) {
            if (t.invoked) return void (window.console && console.error && console.error("Drift snippet included twice."));
            t.invoked = !0, t.methods = [ "identify", "config", "track", "reset", "debug", "show", "ping", "page", "hide", "off", "on" ], 
            t.factory = function(e) {
              return function() {
                var n = Array.prototype.slice.call(arguments);
                return n.unshift(e), t.push(n), t;
              };
            }, t.methods.forEach(function(e) {
              t[e] = t.factory(e);
            }), t.load = function(t) {
              var e = 3e5, n = Math.ceil(new Date() / e) * e, o = document.createElement("script");
              o.type = "text/javascript", o.async = !0, o.crossorigin = "anonymous", o.src = "https://js.driftt.com/include/" + n + "/" + t + ".js";
              var i = document.getElementsByTagName("script")[0];
              i.parentNode.insertBefore(o, i);
            };
          }
        }();
        drift.SNIPPET_VERSION = '0.3.1';
        drift.load(account_id);
        
        drift.on('ready', function(api, data) {
          
          drift.api.widget.hide();
          
          drift.openSupportChat = function() {
            drift.api.openChat();
          };
          
          var chat_name = "", chat_email = "", wait_time = 10;
          if (!drift_user_id) {
              wait_time = 5000;
              chat_name = "user" + Math.floor(Math.random() * 100000) + Date.now();
              chat_email = chat_name + "@" + chat_name + ".com";
              drift.identify(chat_name, {"email": chat_email, "name": chat_name})
          }
          
          setTimeout(function(){
              me.get_live_support_info(chat_name);
              drift.live_support_interval = setInterval(function(){me.get_live_support_info(chat_name);}, 1000*900);
          }, wait_time);
          
          drift.on('sidebarClose',function(e){
            me.change_drift_enabled(false, 500);
          });
          drift.on('awayMessage:close', function() {
            me.change_drift_enabled(false, 1500);
          });
          drift.on('welcomeMessage:close', function() {
            me.change_drift_enabled(false, 1500);
          });
          drift.on('message',function(e){
            me.change_drift_enabled(true, 200);
          });
          
        });
    },
    
    get_live_support_info: function(chat_name) {
        var me = this;
        var drft = window.drift;
        
        Ext.Ajax.request({
            url: "/softnas/snserver/snserv.php",
            method: 'POST',
            params: {
                opcode: 'get_live_support_info',
                drift_external_id: chat_name
            },
            success: function (response, opts) {
                
            }
        });
    },
    
    change_drift_enabled: function(new_value, wait_time) {
        var widget_element = Ext.get('drift-widget');
        if (wait_time === undefined) {
            wait_time = 1500;
        }
        if (!new_value) {
            drift.api.widget.hide();
        }
    },

    displayHeader: function() {
    }
	
});
