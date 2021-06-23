// vim: sw=2:ts=2:nu:nospell:fdc=2:expandtab
/**
* @class Ext.ux.SimpleIFrame
* @extends Ext.Panel
*
* A simple ExtJS 4 implementaton of an iframe providing basic functionality.
* For example:
*
* var panel=Ext.create('Ext.ux.SimpleIFrame', {
*   border: false,
*   src: 'http://localhost'
* });
* panel.setSrc('http://www.sencha.com');
* panel.reset();
* panel.reload();
* panel.getSrc();
* panel.update('<div><b>Some Content....</b></div>');
* panel.destroy();
*
* @author    Conor Armstrong
* @copyright (c) 2011 Conor Armstrong
* @date      12 April 2011
* @version   0.1
*
* @license Ext.ux.SimpleIFrame.js is licensed under the terms of the Open Source
* LGPL 3.0 license. Commercial use is permitted to the extent that the 
* code/component(s) do NOT become part of another Open Source or Commercially
* licensed development library or toolkit without explicit permission.
* 
* <p>License details: <a href="http://www.gnu.org/licenses/lgpl.html"
* target="_blank">http://www.gnu.org/licenses/lgpl.html</a></p>
*
*/

Ext.require([
	'Ext.panel.*'
]);

Ext.define('iFrame.ux.SimpleIFrame', {
  extend: 'Ext.Panel',
  alias: 'widget.simpleiframe',
  src: 'about:blank',
  loadingText: 'Loading ...',

  initComponent: function(){
    this.updateHTML();
    this.eventTrack();
    this.callParent(arguments);
  },

  eventTrack: function() {
     var ctrl = top.window.maincontroller;
     ctrl.resetSessionTimer();                         // send a reset event related to this launch event

     // Hook events to track user activity note these events are ONLY for this IFRAME window
     document.onmousemove = ctrl.resetSessionTimer;      // keep track of user activity
     document.onmousedown = ctrl.resetSessionTimer;      // keep track of user activity
     document.onkeydown = ctrl.resetSessionTimer;        // key press tracking NOT working!

  },

  onRender: function() {
    var me = this;

    me.callParent(arguments);
    Ext.get(me.getDOM()).on('load', me.onLoadIframe, me);
  },

  onLoadIframe: function() {
    this.fireEvent('load', this);
  },

  updateHTML: function() {
    this.html='<iframe id="iframe-'+this.id+'"'+
        ' style="overflow:auto;width:100%;height:100%;"'+
        ' frameborder="0" '+
        ' src="'+this.src+'"'+
        '></iframe>';
  },
  reload: function() {
    this.setSrc(this.src);
  },
  reset: function() {
    var iframe=this.getDOM();
    var iframeParent=iframe.parentNode;
    if (iframe && iframeParent) {
      iframe.src='about:blank';
      iframe.parentNode.removeChild(iframe);
    }

    iframe=document.createElement('iframe');
    iframe.frameBorder=0;
    iframe.src=this.src;
    iframe.id='iframe-'+this.id;
    iframe.style.overflow='auto';
    iframe.style.width='100%';
    iframe.style.height='100%';
    iframeParent.appendChild(iframe);
  },
  setSrc: function(src, loadingText) {
    this.src=src;
    var iframe=this.getDOM();
    if (iframe) {
      iframe.src=src;
    }
  },
  getSrc: function() {
    return this.src;
  },
  getDOM: function() {
    return document.getElementById('iframe-'+this.id);
  },
  getDocument: function() {
    var iframe=this.getDOM();
    iframe = (iframe.contentWindow) ? iframe.contentWindow : (iframe.contentDocument.document) ? iframe.contentDocument.document : iframe.contentDocument;
    return iframe.document;
  },
  destroy: function() {
    var iframe=this.getDOM();
    if (iframe && iframe.parentNode) {
//        var frameDoc = iframe.contentDocument || iframe.contentWindow.document;
//        frameDoc.removeChild(frameDoc.documentElement);
        iframe.src='about:blank';
        iframe.parentNode.removeChild(iframe);
//        document.body.removeChild(iframe);
    }
    this.callParent(arguments);
  },
  update: function(content) {
    this.setSrc('about:blank');
    try {
      var doc=this.getDocument();
      doc.open();
      doc.write(content);
      doc.close();
    } catch(err) {
      // reset if any permission issues
      this.reset();
      var doc=this.getDocument();
      doc.open();
      doc.write(content);
      doc.close();
    }
  }
});
