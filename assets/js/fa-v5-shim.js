(function() {
	'use strict';
	var legacyMap = {
		'fa-sign-out-alt': 'fa-right-from-bracket',
		'fa-sliders-h': 'fa-sliders',
		'fa-user-cog': 'fa-user-gear',
		'fa-exclamation-triangle': 'fa-triangle-exclamation',
		'fa-check-circle': 'fa-circle-check',
		'fa-times-circle': 'fa-circle-xmark',
		'fa-save': 'fa-floppy-disk',
		'fa-user-edit': 'fa-user-pen',
		'fa-sync-alt': 'fa-arrows-rotate'
	};
	function upgradeIcons(root) {
		var scope = root || document;
		var elements = scope.querySelectorAll('i[class*="fa-"]');
		elements.forEach(function(el){
			Object.keys(legacyMap).forEach(function(v5){
				if (el.classList.contains(v5)) {
					el.classList.remove(v5);
					el.classList.add(legacyMap[v5]);
				}
			});
		});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function(){ upgradeIcons(); });
	} else {
		upgradeIcons();
	}
	try {
		var mo = new MutationObserver(function(muts){
			muts.forEach(function(m){
				if (m.addedNodes) {
					m.addedNodes.forEach(function(n){
						if (n.nodeType === 1) { upgradeIcons(n); }
					});
				}
			});
		});
		mo.observe(document.documentElement, { childList: true, subtree: true });
	} catch(e) { /* noop */ }
})();


