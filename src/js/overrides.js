// override to Bug 548397 - window.getComputedStyle() returns null inside an iframe with display: none (https://bugzilla.mozilla.org/show_bug.cgi?id=548397)
if(/firefox/i.test(navigator.userAgent)) {
	window.oldGetComputedStyle = window.getComputedStyle;
	window.getComputedStyle = function (element, pseudoElt) {
		var t = window.oldGetComputedStyle(element, pseudoElt);

		return t === null ? {} : t;
	};
}

// Need this polyfill for IE
if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position){
        return this.substr(position || 0, searchString.length) === searchString;
    };
}