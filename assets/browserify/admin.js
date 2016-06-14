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



// update webhook url as secret key changes
var secretKeyInput = document.getElementById('webhook-secret-key-input');
var webhookUrlInput = document.getElementById('webhook-url-input');
var button = document.getElementById('webhook-generate-button');

function updateWebhookUrlSecret() {
	var sanitized = secretKeyInput.value.replace(/\W+/g, "");
	if( sanitized != secretKeyInput.value ) { secretKeyInput.value = sanitized; }
	var format = webhookUrlInput.getAttribute('data-url-format');
	webhookUrlInput.value = format.replace('%s', secretKeyInput.value );
}

$(secretKeyInput).keyup(updateWebhookUrlSecret);
$(button).click(function() {
	secretKeyInput.value = Math.random().toString(36).substring(7);
	updateWebhookUrlSecret();
});