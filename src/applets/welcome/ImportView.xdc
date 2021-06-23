{
    "xdsVersion": "2.1.0",
    "frameworkVersion": "ext41",
    "internals": {
        "type": "window",
        "reference": {
            "name": "items",
            "type": "array"
        },
        "codeClass": null,
        "userConfig": {
            "autoLoad": null,
            "designer|userClassName": "ImportWindow",
            "designer|userAlias": "importwin",
            "height": 600,
            "itemId": "importWin",
            "maxHeight": 600,
            "width": 636,
            "autoScroll": true,
            "title": "Import Pool",
            "modal": true
        },
        "customConfigs": [
            {
                "group": "(Custom Properties)",
                "name": "autoLoad",
                "type": "string"
            }
        ],
        "cn": [
            {
                "type": "fieldset",
                "reference": {
                    "name": "items",
                    "type": "array"
                },
                "codeClass": null,
                "userConfig": {
                    "container|align": "stretch",
                    "designer|userClassName": "MyFieldSet9",
                    "layout": "hbox",
                    "title": null
                },
                "cn": [
                    {
                        "type": "checkboxfield",
                        "reference": {
                            "name": "items",
                            "type": "array"
                        },
                        "codeClass": null,
                        "userConfig": {
                            "designer|userClassName": "MyCheckbox4",
                            "id": "idForceImport",
                            "itemId": "cbForceImport",
                            "padding": "'5 5 5 5'",
                            "name": "forced",
                            "fieldLabel": null,
                            "boxLabel": "Force the import operation"
                        }
                    },
                    {
                        "type": "label",
                        "reference": {
                            "name": "items",
                            "type": "array"
                        },
                        "codeClass": null,
                        "userConfig": {
                            "layout|flex": 1,
                            "designer|userClassName": "MyLabel6",
                            "designer|displayName": "spacer",
                            "maxWidth": 350,
                            "width": 350,
                            "text": " "
                        }
                    },
                    {
                        "type": "button",
                        "reference": {
                            "name": "items",
                            "type": "array"
                        },
                        "codeClass": null,
                        "userConfig": {
                            "designer|userClassName": "MyButton23",
                            "designer|displayName": "Close",
                            "height": 24,
                            "itemId": "closeBtn",
                            "margin": null,
                            "maxHeight": 24,
                            "padding": null,
                            "width": 57,
                            "text": "Close"
                        },
                        "cn": [
                            {
                                "type": "basiceventbinding",
                                "reference": {
                                    "name": "listeners",
                                    "type": "array"
                                },
                                "codeClass": null,
                                "userConfig": {
                                    "designer|userClassName": "onCloseBtnClick",
                                    "fn": "onCloseBtnClick",
                                    "implHandler": [
                                        "var win = button.up(\"window\");\r",
                                        "win.close();"
                                    ],
                                    "name": "click",
                                    "scope": "me"
                                }
                            }
                        ]
                    }
                ]
            },
            {
                "type": "form",
                "reference": {
                    "name": "items",
                    "type": "array"
                },
                "codeClass": null,
                "userConfig": {
                    "designer|userClassName": "MyForm4",
                    "designer|displayName": "HiddenForm",
                    "height": 510,
                    "loader": [
                        "{\r",
                        "win: this,\r",
                        "url : \"../../snserver/importlist.php\",\r",
                        "scripts  : false,\r",
                        "loadMask : true,\r",
                        "autoLoad : true, // important\r",
                        "renderer : 'html', // this is also the default option, other options are data | component\r",
                        "success: function(el,response,opts) {\r",
                        "  this.win.setLoading(false);\r",
                        "},\r",
                        "failure:function(el,response,opts) {\r",
                        "  this.win.setLoading(false);\r",
                        "  alert(\"Unable to load Import page!\");\r",
                        "}\r",
                        "}\r",
                        ""
                    ],
                    "maxHeight": null,
                    "minHeight": null,
                    "autoScroll": true,
                    "layout": null,
                    "bodyPadding": 10,
                    "title": null,
                    "url": "/softnas/snserver/snserv.php?opcode=importpool"
                },
                "cn": [
                    {
                        "type": "hiddenfield",
                        "reference": {
                            "name": "items",
                            "type": "array"
                        },
                        "codeClass": null,
                        "userConfig": {
                            "designer|userClassName": "MyHiddenField",
                            "designer|displayName": "OpcodeHiddenField",
                            "id": "idOpcodeImport",
                            "itemId": "opcodeImport",
                            "name": "opcode",
                            "fieldLabel": "Label"
                        }
                    }
                ]
            }
        ]
    },
    "linkedNodes": {},
    "boundStores": {},
    "boundModels": {}
}