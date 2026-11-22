/**
 * ConsentGate admin: scan, service save, banner preview.
 */
(function () {
	'use strict';

	var cfg = window.ConsentGateAdmin || {};

	function post(action, data, done) {
		var body = new URLSearchParams();
		body.set('action', action);
		body.set('nonce', cfg.nonce || '');
		Object.keys(data || {}).forEach(function (key) {
			body.set(key, data[key]);
		});
		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (res) {
				return res.text().then(function (text) {
					try {
						return JSON.parse(text);
					} catch (e) {
						return { success: false };
					}
				});
			})
			.then(done)
			.catch(function () {
				done({ success: false });
			});
	}

	function bindScan() {
		var btn = document.querySelector('[data-cg-scan]');
		var status = document.querySelector('[data-cg-scan-status]');
		var live = document.querySelector('[data-cg-scan-live]');
		if (!btn) {
			return;
		}

		function text(node, value) {
			if (node) node.textContent = value;
		}

		function pageLabel(template, index, total) {
			return String(template || 'Page %1$d of %2$d').replace('%1$d', String(index)).replace('%2$d', String(total));
		}

		function addRow(list, step) {
			if (!list || !step) return;
			var item = document.createElement('li');
			item.className = 'consentgate-scan__item' + (step.empty ? ' is-clear' : ' is-hit');
			var name = document.createElement('span');
			name.className = 'consentgate-scan__name';
			name.textContent = step.title || step.url || '';
			var result = document.createElement('span');
			result.className = 'consentgate-scan__result';
			if (step.empty || !step.found || !step.found.length) {
				result.textContent = cfg.i18n.scanClear || 'Nothing external on this page';
			} else {
				result.textContent = (cfg.i18n.scanFound || 'Found') + ': ' + step.found.join(', ');
			}
			item.appendChild(name);
			item.appendChild(result);
			list.appendChild(item);
			list.scrollTop = list.scrollHeight;
		}

		function runSteps() {
			post('consentgate_scan', { phase: 'step' }, function (json) {
				if (!json || !json.success || !json.data) {
					btn.disabled = false;
					text(status, cfg.i18n.error || 'Scan failed.');
					return;
				}
				var step = json.data;
				var list = live ? live.querySelector('[data-cg-scan-list]') : null;
				var bar = live ? live.querySelector('[data-cg-scan-bar]') : null;
				var current = live ? live.querySelector('[data-cg-scan-current]') : null;
				var meta = live ? live.querySelector('[data-cg-scan-meta]') : null;
				if (step.title || step.url) {
					text(current, (cfg.i18n.scanNow || 'Now looking at') + ' ' + (step.title || step.url));
					addRow(list, step);
				}
				if (step.total) {
					text(meta, pageLabel(cfg.i18n.scanPage, step.index || 0, step.total));
					if (bar) bar.style.width = Math.min(100, Math.round(((step.index || 0) / step.total) * 100)) + '%';
				}
				if (!step.done) {
					runSteps();
					return;
				}
				text(status, step.message || cfg.i18n.scanned || 'Scan finished.');
				text(current, step.message || cfg.i18n.scanned || 'Scan finished.');
				window.setTimeout(function () {
					var next = btn.getAttribute('data-cg-scan-next');
					window.location.href = next || window.location.href;
				}, 700);
			});
		}

		btn.addEventListener('click', function () {
			btn.disabled = true;
			if (status) status.textContent = cfg.i18n.scanning || '…';
			if (live) {
				live.hidden = false;
				var intro = live.querySelector('[data-cg-scan-intro]');
				var list = live.querySelector('[data-cg-scan-list]');
				var bar = live.querySelector('[data-cg-scan-bar]');
				text(intro, cfg.i18n.scanIntro || '');
				text(live.querySelector('[data-cg-scan-current]'), cfg.i18n.scanning || '');
				text(live.querySelector('[data-cg-scan-meta]'), '');
				if (list) list.textContent = '';
				if (bar) bar.style.width = '0%';
			}
			post('consentgate_scan', { phase: 'start' }, function (json) {
				if (!json || !json.success) {
					btn.disabled = false;
					text(status, cfg.i18n.error || 'Scan failed.');
					return;
				}
				var total = json.data && json.data.total ? json.data.total : 0;
				var meta = live ? live.querySelector('[data-cg-scan-meta]') : null;
				text(meta, pageLabel(cfg.i18n.scanPage, 0, total));
				runSteps();
			});
		});
	}

	function bindServices() {
		document.querySelectorAll('[data-cg-service]').forEach(function (row) {
			var key = row.getAttribute('data-cg-service');
			function save() {
				var category = row.querySelector('[data-cg-field="category"]');
				var policy = row.querySelector('[data-cg-field="policy"]');
				var included = row.querySelector('[data-cg-field="included"]');
				post('consentgate_save_service', {
					service_key: key,
					category: category ? category.value : 'marketing',
					policy: policy ? policy.value : 'with_consent',
					included: included && included.checked ? '1' : ''
				}, function () {});
			}
			row.querySelectorAll('[data-cg-field]').forEach(function (field) {
				field.addEventListener('change', save);
			});
		});

		var add = document.querySelector('[data-cg-add-service]');
		if (!add) {
			return;
		}
		add.addEventListener('click', function () {
			var name = document.getElementById('cg-manual-name');
			var host = document.getElementById('cg-manual-host');
			var cat = document.getElementById('cg-manual-cat');
			post('consentgate_add_service', {
				name: name ? name.value : '',
				host: host ? host.value : '',
				category: cat ? cat.value : 'marketing'
			}, function (json) {
				if (json && json.success) {
					window.location.reload();
				}
			});
		});
	}

	function bindGoogleConsent() {
		var modal = document.querySelector('[data-cg-google-modal]');
		var openBtn = document.querySelector('[data-cg-google-settings]');
		var closeBtn = document.querySelector('[data-cg-google-close]');
		var doneBtn = document.querySelector('[data-cg-google-done]');
		var toggle = document.getElementById('cg-google-advanced');
		var badge = document.querySelector('[data-cg-cm-badge]');
		if (!modal || !openBtn || typeof modal.showModal !== 'function') {
			return;
		}

		function enabled() {
			return !!(toggle && toggle.checked);
		}

		function syncBadge() {
			if (badge) {
				badge.hidden = !enabled();
			}
		}

		function save(done) {
			post('consentgate_save_google_consent', {
				enabled: enabled() ? '1' : ''
			}, function () {
				syncBadge();
				if (done) {
					done();
				}
			});
		}

		function close() {
			if (modal.open) {
				modal.close();
			}
		}

		openBtn.addEventListener('click', function () {
			modal.showModal();
		});
		if (closeBtn) {
			closeBtn.addEventListener('click', close);
		}
		if (doneBtn) {
			doneBtn.addEventListener('click', function () {
				save(close);
			});
		}
		if (toggle) {
			toggle.addEventListener('change', function () {
				save();
			});
		}
		modal.addEventListener('click', function (event) {
			if (event.target === modal) {
				close();
			}
		});
	}

	function bindPreview() {
		var panel = document.querySelector('[data-cg-preview] .cg-panel');
		if (!panel) {
			return;
		}
		var title = document.querySelector('[data-cg-preview-title]');
		var desc = document.querySelector('[data-cg-preview-desc]');
		var loc = panel.getAttribute('data-cg-preview-locale') === 'en' ? 'en' : 'de';
		var titleInput = document.getElementById('cg-' + loc + '-title');
		var descInput = document.getElementById('cg-' + loc + '-description');
		var pos = document.querySelector('[data-cg-position]');
		var size = document.querySelector('[data-cg-size]');

		function color(name, fallback) {
			var input = document.querySelector('[data-cg-color="' + name + '"]');
			return input && input.value ? input.value : fallback;
		}

		function paint() {
			panel.style.setProperty('--cg-bg', color('background_color', '#ffffff'));
			panel.style.setProperty('--cg-fg', color('text_color', '#1a1a1a'));
			panel.style.setProperty('--cg-accept-bg', color('accept_bg', '#27335a'));
			panel.style.setProperty('--cg-accept-fg', color('accept_text', '#ffffff'));
			panel.style.setProperty('--cg-reject-bg', color('reject_bg', '#ffffff'));
			panel.style.setProperty('--cg-reject-fg', color('reject_text', '#27335a'));
			panel.style.setProperty('--cg-primary', color('primary_color', '#27335a'));
			var height = logoHeightValue();
			panel.style.setProperty('--cg-logo-height', height + 'px');
			var bannerLogo = document.querySelector('[data-cg-preview-logo]');
			if (bannerLogo) {
				bannerLogo.style.setProperty('height', height + 'px', 'important');
				bannerLogo.style.setProperty('width', 'auto', 'important');
				bannerLogo.style.setProperty('max-width', '100%', 'important');
				bannerLogo.style.setProperty('max-height', 'none', 'important');
			}
			var wrap = document.querySelector('[data-cg-preview-logo-wrap]');
			if (wrap) {
				wrap.classList.remove('cg-logo-wrap--left', 'cg-logo-wrap--center');
				wrap.classList.add('cg-logo-wrap--' + logoAlignValue());
			}
			if (title && titleInput) {
				title.textContent = titleInput.value;
			}
			if (desc && descInput) {
				desc.textContent = descInput.value;
			}
			if (pos) {
				var sizeValue = size && /^(compact|normal|wide)$/.test(size.value) ? size.value : 'normal';
				panel.className = 'cg-panel cg-panel--' + pos.value + ' cg-size-' + sizeValue;
			}
			syncPreviewLogo();
		}

		function logoHeightValue() {
			var logoHeight = document.querySelector('[data-cg-logo-height]');
			var height = logoHeight ? parseInt(logoHeight.value, 10) : 28;
			if (!height || height < 16) height = 16;
			if (height > 200) height = 200;
			return height;
		}

		function logoAlignValue() {
			var align = document.querySelector('[data-cg-logo-align]');
			return align && align.value === 'center' ? 'center' : 'left';
		}

		function setLogoHeight(next) {
			var height = parseInt(next, 10);
			if (!height || height < 16) height = 16;
			if (height > 200) height = 200;
			var hidden = document.querySelector('[data-cg-logo-height]');
			var range = document.querySelector('[data-cg-logo-height-range]');
			var opener = document.querySelector('[data-cg-logo-height-open]');
			var manual = document.querySelector('[data-cg-logo-height-manual]');
			if (hidden) hidden.value = String(height);
			if (range) range.value = String(height);
			if (manual) manual.value = String(height);
			if (opener) opener.textContent = height + ' px';
			paint();
		}

		var range = document.querySelector('[data-cg-logo-height-range]');
		var opener = document.querySelector('[data-cg-logo-height-open]');
		var dialog = document.querySelector('[data-cg-logo-height-dialog]');
		var manual = document.querySelector('[data-cg-logo-height-manual]');
		var applyBtn = document.querySelector('[data-cg-logo-height-apply]');
		var cancelBtn = document.querySelector('[data-cg-logo-height-cancel]');
		if (range) {
			range.addEventListener('input', function () {
				setLogoHeight(range.value);
			});
		}
		if (opener && dialog && typeof dialog.showModal === 'function') {
			opener.addEventListener('click', function () {
				if (manual) manual.value = String(logoHeightValue());
				dialog.showModal();
				if (manual) manual.focus();
			});
		}
		if (applyBtn) {
			applyBtn.addEventListener('click', function () {
				setLogoHeight(manual ? manual.value : logoHeightValue());
				if (dialog && dialog.open) dialog.close();
			});
		}
		if (cancelBtn && dialog) {
			cancelBtn.addEventListener('click', function () {
				dialog.close();
			});
		}
		if (manual) {
			manual.addEventListener('keydown', function (event) {
				if (event.key !== 'Enter') return;
				event.preventDefault();
				setLogoHeight(manual.value);
				if (dialog && dialog.open) dialog.close();
			});
		}

		document.querySelectorAll('[data-cg-color], [data-cg-position], [data-cg-size], [data-cg-logo-align], #cg-de-title, #cg-de-description, #cg-en-title, #cg-en-description').forEach(function (el) {
			el.addEventListener('input', paint);
			el.addEventListener('change', paint);
		});
		paint();
	}

	function syncPreviewLogo() {
		var preview = document.querySelector('[data-cg-logo-preview]');
		var bannerLogo = document.querySelector('[data-cg-preview-logo]');
		if (!bannerLogo) {
			return;
		}
		var wrap = document.querySelector('[data-cg-preview-logo-wrap]');
		var src = preview && preview.getAttribute('src') && !preview.hidden ? preview.getAttribute('src') : '';
		if (src) {
			bannerLogo.setAttribute('src', src);
			bannerLogo.hidden = false;
			if (wrap) wrap.hidden = false;
		} else {
			bannerLogo.removeAttribute('src');
			bannerLogo.hidden = true;
			if (wrap) wrap.hidden = true;
		}
	}

	function bindLogoPicker() {
		var pick = document.querySelector('[data-cg-logo-pick]');
		var remove = document.querySelector('[data-cg-logo-remove]');
		var input = document.querySelector('[data-cg-logo-id]');
		var cleared = document.querySelector('[data-cg-logo-cleared]');
		var preview = document.querySelector('[data-cg-logo-preview]');
		if (!pick || typeof wp === 'undefined' || !wp.media) {
			return;
		}

		var frame;
		var changeLabel = cfg.i18n.logoChange || 'Change logo';
		var chooseLabel = cfg.i18n.logoChoose || 'Choose from Media Library';

		function setPreview(url, id) {
			if (input) {
				input.value = id ? String(id) : '0';
			}
			if (cleared) {
				cleared.value = url ? '0' : '1';
			}
			if (preview) {
				if (url) {
					preview.setAttribute('src', url);
					preview.hidden = false;
				} else {
					preview.removeAttribute('src');
					preview.hidden = true;
				}
			}
			if (remove) {
				remove.hidden = !url;
			}
			pick.textContent = url ? changeLabel : chooseLabel;
			syncPreviewLogo();
		}

		pick.addEventListener('click', function (event) {
			event.preventDefault();
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media({
				title: cfg.i18n.logoTitle || 'Choose banner logo',
				button: { text: cfg.i18n.logoUse || 'Use logo' },
				library: { type: 'image' },
				multiple: false
			});
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				var url = att.url;
				if (att.sizes && att.sizes.medium && att.sizes.medium.url) {
					url = att.sizes.medium.url;
				}
				setPreview(url, att.id);
			});
			frame.open();
		});

		if (remove) {
			remove.addEventListener('click', function (event) {
				event.preventDefault();
				setPreview('', 0);
			});
		}
	}

	function bindSetup() {
		document.querySelectorAll('[data-cg-setup]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				document.querySelectorAll('[data-cg-setup]').forEach(function (el) {
					el.disabled = true;
				});
				post('consentgate_complete_setup', {
					show_branding: btn.getAttribute('data-cg-branding') === '1' ? '1' : ''
				}, function (json) {
					if (json && json.success && json.data && json.data.redirect) {
						window.location.href = json.data.redirect;
						return;
					}
					document.querySelectorAll('[data-cg-setup]').forEach(function (el) {
						el.disabled = false;
					});
				});
			});
		});
	}

	function bindFound() {
		var dialog = document.querySelector('[data-cg-found-dialog]');
		if (!dialog) {
			return;
		}
		var title = dialog.querySelector('[data-cg-found-title]');
		var summary = dialog.querySelector('[data-cg-found-summary]');
		var list = dialog.querySelector('[data-cg-found-list]');
		var i18n = cfg.i18n || {};
		function close() {
			if (dialog.open) {
				dialog.close();
			}
		}
		dialog.querySelectorAll('[data-cg-found-close]').forEach(function (btn) {
			btn.addEventListener('click', close);
		});
		dialog.addEventListener('click', function (event) {
			if (event.target === dialog) {
				close();
			}
		});
		document.querySelectorAll('[data-cg-found]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var count = parseInt(btn.getAttribute('data-cg-found-count') || '0', 10);
				var name = btn.getAttribute('data-cg-found-name') || '';
				var pages = [];
				if (count <= 3) {
					try {
						pages = JSON.parse(btn.getAttribute('data-cg-found-pages') || '[]');
					} catch (error) {
						pages = [];
					}
				}
				if (title) {
					title.textContent = name;
				}
				if (list) {
					list.textContent = '';
					list.hidden = count < 1 || count > 3;
				}
				if (summary) {
					if (count > 3) {
						summary.textContent = (i18n.foundMany || 'Found on %d pages.').replace('%d', String(count));
					} else if (count === 1) {
						summary.textContent = i18n.foundOne || 'Found on 1 page.';
					} else if (count > 1) {
						summary.textContent = (i18n.foundMany || 'Found on %d pages.').replace('%d', String(count));
					} else {
						summary.textContent = i18n.foundNone || 'No public page recorded yet. Run a scan to refresh this.';
					}
				}
				if (list && count > 0 && count <= 3 && Array.isArray(pages)) {
					pages.forEach(function (page) {
						if (!page || !page.url) {
							return;
						}
						var item = document.createElement('li');
						var link = document.createElement('a');
						link.href = page.url;
						link.target = '_blank';
						link.rel = 'noopener noreferrer';
						link.textContent = page.title || page.url;
						item.appendChild(link);
						list.appendChild(item);
					});
					list.hidden = !list.childNodes.length;
				}
				if (typeof dialog.showModal === 'function') {
					dialog.showModal();
				}
			});
		});
	}

	function bindBrandingPreview() {
		var toggle = document.getElementById('consentgate-show-branding');
		var brand = document.querySelector('[data-cg-preview-brand]');
		if (!toggle || !brand) {
			return;
		}
		function sync() {
			brand.hidden = !toggle.checked;
		}
		toggle.addEventListener('change', sync);
		sync();
	}

	bindScan();
	bindServices();
	bindFound();
	bindGoogleConsent();
	bindPreview();
	bindLogoPicker();
	bindSetup();
	bindBrandingPreview();
})();
