/**
 * Navigation-block variation: Cookie-Einstellungen.
 */
(function () {
	'use strict';

	if (!window.wp || !wp.blocks || typeof wp.blocks.registerBlockVariation !== 'function') {
		return;
	}

	var cfg = window.ConsentGateEditor || {};
	var hash = cfg.hash || '#consentgate';
	var label = cfg.label || 'Cookie settings';
	var description = cfg.description || 'Opens the Wiedenroth Technologies ConsentGate banner again.';

	wp.blocks.registerBlockVariation('core/navigation-link', {
		name: 'consentgate',
		title: label,
		description: description,
		icon: 'privacy',
		attributes: {
			label: label,
			url: hash,
			kind: 'custom',
			type: 'custom',
			rel: 'consentgate'
		},
		isActive: function (blockAttributes) {
			var url = String((blockAttributes && blockAttributes.url) || '');
			return url === hash || url.indexOf('#consentgate') !== -1;
		}
	});
})();
