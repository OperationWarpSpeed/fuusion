/**
 * @author ATHANASIOS
 */
Ext.Loader.setConfig({enabled: true});


Ext.application({
	
    name: 'iFrame',
    requires: ['iFrame.view.EmailSetup', 'iFrame.view.ProdRegWindow', 'iFrame.view.FeatureRequestWindow', 'iFrame.view.GoldWelcomeWindow', 'iFrame.view.EnableFlexfilesWindow', 'iFrame.view.BetaWindow'],
  	appFolder: 'app',
	autoCreateViewport: true,
	
 	stores: ['JsonTreeStore'],
	controllers: ['MainCtrl', 'ProdRegController'],
	views : ['EmailSetup', 'ProdRegWindow', 'GoldWelcomeWindow', 'EnableFlexfilesWindow', 'BetaWindow'],
	launch: function() {
		window.StorageCenterApp = this;
		
		iFrame.prodRegW = Ext.create('iFrame.view.ProdRegWindow', {renderTo: Ext.getBody()});
		iFrame.featureW = Ext.create('iFrame.view.FeatureRequestWindow', {renderTo: Ext.getBody()});
		iFrame.goldWelcomeWindow = Ext.create('iFrame.view.GoldWelcomeWindow', {renderTo: Ext.getBody()});
		iFrame.request_url = "/softnas/snserver/snserv.php";
		
		// Adding ids to Ext.MessageBox buttons:
		window.setMsgButtonsIds = function(){
			var i = 0, btn_text = "",
				msg_box = document.getElementsByClassName('x-message-box')[0],
				//buttons = msg_box.getElementsByTagName('button');
				buttons = msg_box.getElementsByClassName('x-btn-inner'); // Extjs5

			for(i = 0; i < buttons.length; i++){
				btn_text = buttons[i].textContent;
				if(btn_text == 'OK' || btn_text == 'Yes' || btn_text == 'No' || btn_text == 'Cancel'){
					buttons[i].id = "msgBtn" + btn_text;
				}
				if(btn_text == 'Reboot Now'){
					buttons[i].id = "msgBtnReboot";
				}
				if(btn_text == 'Continue Anyway'){
					buttons[i].id = "msgBtnContinue";
				}
			}
		};

		Ext.override(Ext.MessageBox, {
			show: function(){ this.callParent(arguments); window.setMsgButtonsIds(); return this; }
		});
		
  }
	
});

Ext.override(Ext.data.proxy.Ajax, { timeout:15000 });
