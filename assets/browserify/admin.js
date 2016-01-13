'use strict';

var m = window.m = require('mithril');
var Wizard = require('./admin/wizard.js');
var FieldMapper = require('./admin/field-mapper.js');
var $ = window.jQuery;

// init wizard
var wizardContainer = document.getElementById('wizard');
if( wizardContainer ) {
	m.module( wizardContainer , Wizard );
}

// init fieldmapper
new FieldMapper($('.mc4wp-sync-field-map'));