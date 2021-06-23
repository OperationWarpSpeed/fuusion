// Avoid apply overrides for other versions
if([5, 6].indexOf(Ext.versions.extjs.major) !== -1) {
    Ext.Loader.setConfig({
        paths: {
            Util: '/softnas/applets/flexfiles/packages/local/util/src',
            'Util.ux.quickhelp': '/softnas/applets/flexfiles/packages/local/util/classic/src/ux/quickhelp',
            'Ext.ux': '/softnas/applets/flexfiles/packages/local/util/classic/src/ux'
        }
    });

    Ext.require([
        'Util.ux.quickhelp.plugin.Icon',
        'Util.ux.quickhelp.Button',
        'Util.ux.quickhelp.Image',
        'Util.ux.quickhelp.Mixin',
        'Util.ux.quickhelp.Tool',
        'Util.Util',
        'Ext.ux.form.field.Toggle',
        'Util.Config'
    ]);

    Ext.define('Overrides.draw.sprite.AttributeDefinition', {
    	override: 'Ext.draw.sprite.AttributeDefinition',

        normalize: function(changes, keepUnrecognized) {
            if (!changes) {
                return {};
            }
            var definition = this,
                processors = definition.getProcessors(),
                aliases = definition.getAliases(),
                translation = changes.translation || changes.translate,
                normalized = {},
                name, val, rotation, scaling, matrix, split;
            if ('rotation' in changes) {
                rotation = changes.rotation;
            } else {
                rotation = ('rotate' in changes) ? changes.rotate : undefined;
            }
            if ('scaling' in changes) {
                scaling = changes.scaling;
            } else {
                scaling = ('scale' in changes) ? changes.scale : undefined;
            }
            if (translation) {
                if ('x' in translation) {
                    normalized.translationX = translation.x;
                }
                if ('y' in translation) {
                    normalized.translationY = translation.y;
                }
            }
            if (typeof scaling !== 'undefined') {
                if (Ext.isNumber(scaling)) {
                    normalized.scalingX = scaling;
                    normalized.scalingY = scaling;
                } else {
                    if ('x' in scaling) {
                        normalized.scalingX = scaling.x;
                    }
                    if ('y' in scaling) {
                        normalized.scalingY = scaling.y;
                    }
                    if ('centerX' in scaling) {
                        normalized.scalingCenterX = scaling.centerX;
                    }
                    if ('centerY' in scaling) {
                        normalized.scalingCenterY = scaling.centerY;
                    }
                }
            }
            if (typeof rotation !== 'undefined') {
                if (Ext.isNumber(rotation)) {
                    rotation = Ext.draw.Draw.rad(rotation);
                    normalized.rotationRads = rotation;
                } else {
                    if ('rads' in rotation) {
                        normalized.rotationRads = rotation.rads;
                    } else if ('degrees' in rotation) {
                        normalized.rotationRads = Ext.draw.Draw.rad(rotation.degrees);
                    }
                    if ('centerX' in rotation) {
                        normalized.rotationCenterX = rotation.centerX;
                    }
                    if ('centerY' in rotation) {
                        normalized.rotationCenterY = rotation.centerY;
                    }
                }
            }
            if ('matrix' in changes) {
                matrix = Ext.draw.Matrix.create(changes.matrix);
                split = matrix.split();
                normalized.matrix = matrix;
                normalized.rotationRads = split.rotation;
                normalized.rotationCenterX = 0;
                normalized.rotationCenterY = 0;
                normalized.scalingX = split.scaleX;
                normalized.scalingY = split.scaleY;
                normalized.scalingCenterX = 0;
                normalized.scalingCenterY = 0;
                normalized.translationX = split.translateX;
                normalized.translationY = split.translateY;
            }
            for (name in changes) {
                val = changes[name];
                if (typeof val === 'undefined') {
                    
                    continue;
                }
                if (name in aliases) {
                    name = aliases[name];
                }
                if (name in processors) {
                    val = (name === 'fontSize' && isNaN(val)) ? '12px' : processors[name].call(this, val); // override
                    if (typeof val !== 'undefined') {
                        normalized[name] = val;
                    }
                } else if (keepUnrecognized) {
                    normalized[name] = val;
                }
            }
            return normalized;
        }
    });

    // #6170
    Ext.define('EXTJS_23846.Element', {
        override: 'Ext.dom.Element'
    }, function(Element) {
        var supports = Ext.supports,
            proto = Element.prototype,
            eventMap = proto.eventMap,
            additiveEvents = proto.additiveEvents;

        if ((Ext.versions.extjs.major === 5 || Ext.versions.extjs.major === 6) && Ext.os.is.Desktop && supports.TouchEvents && !supports.PointerEvents) {
            eventMap.touchstart = 'mousedown';
            eventMap.touchmove = 'mousemove';
            eventMap.touchend = 'mouseup';
            eventMap.touchcancel = 'mouseup';

            additiveEvents.mousedown = 'mousedown';
            additiveEvents.mousemove = 'mousemove';
            additiveEvents.mouseup = 'mouseup';
            additiveEvents.touchstart = 'touchstart';
            additiveEvents.touchmove = 'touchmove';
            additiveEvents.touchend = 'touchend';
            additiveEvents.touchcancel = 'touchcancel';

            additiveEvents.pointerdown = 'mousedown';
            additiveEvents.pointermove = 'mousemove';
            additiveEvents.pointerup = 'mouseup';
            additiveEvents.pointercancel = 'mouseup';
        }
    });

    // #6170
    Ext.define('EXTJS_23846.Gesture', {
        override: 'Ext.event.publisher.Gesture'
    }, function(Gesture) {
        var me = Gesture.instance;

        if ((Ext.versions.extjs.major === 5 || Ext.versions.extjs.major === 6)  && Ext.supports.TouchEvents && !Ext.isWebKit && Ext.os.is.Desktop) {
            me.handledDomEvents.push('mousedown', 'mousemove', 'mouseup');
            me.registerEvents();
        }
    });
    
    // #15416
    if ((Ext.versions.extjs.major === 5 || Ext.versions.extjs.major === 6) && Ext.os.is.Desktop && Ext.supports.touchScroll == 2) {
        Ext.supports.touchScroll = 1;
    }
    
    Ext.Ajax.on('requestexception', function(conn, response) {
        var json = response.responseText,
            res = Util.Util.isJson(json) ? Ext.decode(json) : {};

        if (window.top && window.top.iFrame) {
            var activeTab = window.top.iFrame.getApplication().getMainView().down('tabholder').getActiveTab();
            if(response.status === 401) {
                Ext.Msg.alert('Unauthorized', !Ext.isEmpty(res.msg) ? res.msg : 'Permission denied', function() {
                    activeTab.destroy();
                });
            }
        }
    });

    Ext.define('Overrides.form.Basic', {
        override: 'Ext.form.Basic',

        afterAction: function(action, success) {
            this.callParent(arguments);

            if(!success && action.response.status === 401) {
                var res = Ext.decode(action.response.responseText);

                Ext.Msg.alert('Unauthorized', (!Ext.isEmpty(res.msg) ? res.msg : 'Permission denied'));

                action.failure = Ext.emptyFn;
            }
        }
    });
}