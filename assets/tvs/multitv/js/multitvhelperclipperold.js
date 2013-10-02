var lastImageCtrl;
var lastFileCtrl;
var rteOptions;
var dataTableLanguage;

if (!String.prototype.supplant) {
	String.prototype.supplant = function(o) {
		return this.replace(/{([^{}]*)}/g,
				function(a, b) {
					var r = o[b];
					return typeof r === 'string' || typeof r === 'number' ? r : a;
				}
		);
	};
}

function SetUrl(url, width, height, alt) {
	url = url.replace(/^.*\/assets\/images\//, 'assets/images/');
	if (lastFileCtrl) {
		var fileCtrl = $('#' + lastFileCtrl);
		fileCtrl.val(url);
		fileCtrl.trigger('change');
		lastFileCtrl = '';
	} else if (lastImageCtrl) {
		var imageCtrl = $('#' + lastImageCtrl);
		imageCtrl.val(url);
		imageCtrl.trigger('change');
		lastImageCtrl = '';
	} else {
		return;
	}
}
