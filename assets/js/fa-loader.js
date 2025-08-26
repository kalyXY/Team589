(function(){
	function injectCdn(){
		if (document.getElementById('fa-cdn-link')) return;
		var l = document.createElement('link');
		l.id = 'fa-cdn-link';
		l.rel = 'stylesheet';
		l.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';
		document.head.appendChild(l);
	}
	function checkLoaded(){
		try {
			if (!('fonts' in document)) { return; }
			var solidOk = document.fonts.check('1em "Font Awesome 6 Free"');
			var brandsOk = document.fonts.check('1em "Font Awesome 6 Brands"');
			if (!solidOk || !brandsOk) { injectCdn(); }
		} catch(e) {}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', checkLoaded);
	} else {
		checkLoaded();
	}
})();
