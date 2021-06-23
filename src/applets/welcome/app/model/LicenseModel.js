/*
 * File: app/model/LicenseModel.js
 *
 * This file was generated by Sencha Architect version 4.2.2.
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 5.1.x library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 5.1.x. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('MyApp.model.LicenseModel', {
    extend: 'Ext.data.Model',

    requires: [
        'Ext.data.field.String',
        'Ext.data.field.Boolean'
    ],

    fields: [
        {
            type: 'string',
            name: 'status'
        },
        {
            type: 'string',
            name: 'expiration'
        },
        {
            type: 'string',
            name: 'licensetype'
        },
        {
            type: 'string',
            name: 'istrial'
        },
        {
            type: 'boolean',
            name: 'valid'
        },
        {
            type: 'string',
            name: 'today'
        },
        {
            type: 'string',
            name: 'gracedays'
        },
        {
            type: 'string',
            name: 'graceperiod'
        },
        {
            type: 'string',
            name: 'graceremaining'
        },
        {
            type: 'boolean',
            name: 'is_perpetual'
        },
        {
            type: 'boolean',
            name: 'is_subscription'
        },
        {
            type: 'boolean',
            name: 'maint_expired'
        },
        {
            type: 'string',
            name: 'maint_expiration'
        },
        {
            type: 'boolean',
            name: 'is_activated'
        },
        {
            type: 'string',
            name: 'producttype'
        },
        {
            type: 'string',
            name: 'platform'
        }
    ]
});