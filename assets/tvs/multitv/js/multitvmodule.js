var multiTvDefaultTab = $.cookie('multiTvTab') || '.tab:first-child';
multiTvDefaultTab = $(multiTvDefaultTab).length ? multiTvDefaultTab : '.tab:first-child';

$('#multiTvPanes').easytabs({
	tabs: '> .tab-row > .tab',
	defaultTab: multiTvDefaultTab,
	tabActiveClass: 'selected',
	animationSpeed: 'fast'
});
$('#multiTvPanes').bind('easytabs:after', function(evt, tab, panel, data) {
	$.cookie('multiTvTab', '#' + tab.parent().attr('id'));
});

$(document).ready(function() {

	function parseQueryString(str) {
		if (typeof str !== 'string') {
			return {};
		}
		str = str.trim().replace(/^\?/, '');
		if (!str) {
			return {};
		}
		return str.trim().split('&').reduce(function(ret, param) {
			var parts = param.replace(/\+/g, ' ').split('=');
			ret[parts[0]] = parts[1] === undefined ? null : decodeURIComponent(parts[1]);
			return ret;
		}, {});
	}

	function filterPackages() {
		var filter = $('#searchPackages').serialize();
		var url = 'index.php?a=112&id=' + moduleId + '&action=list_installed';
		$.ajax({
			type: 'POST',
			url: url,
			data: filter,
			success: function(data) {
				$('#packagesInstalled').html(data);
				filterPackagesEvents();
			},
			dataType: 'text'
		});
	}

	function filterPackagesEvents() {
		$('#searchPackages input[type=submit]').click(function(e) {
			e.preventDefault();
			var type = $(e.currentTarget).attr('name').replace(/submit_/, '');
			$('#searchPackages input[name=page]').val('');
			if (type == 'reset') {
				$('#searchPackages input[name=search]').val('');
			}
			filterPackages(type);
		});
		$('#searchPagination a').click(function(e) {
			e.preventDefault();
			var href = parseQueryString($(e.currentTarget).attr('href'));
			$('#searchPackages input[name=page]').val(href['page']);
			filterPackages();
		});
		$('a.deleteExtra').click(function(e) {
			e.preventDefault();
			if (confirm(moduleLanguage.confirm_delete_extra) == true) {
				$.ajax({
					type: 'GET',
					url: $(this).attr('href'),
					success: function(data) {
						$('#packagesInstalled').html(data);
						filterPackagesEvents();
					},
					dataType: 'text'
				});
			}
		});
	}

	function uploadPackages() {
		var params = $('#uploadPackage').serialize();
		var url = 'index.php?a=112&id=' + moduleId + '&action=upload_local';
		$.ajax({
			type: 'POST',
			url: url,
			data: params,
			success: function(data) {
				$('#uploadPackage').html(data);
				uploadPackagesEvents();
			},
			dataType: 'text'
		});
	}

	function uploadPackagesEvents() {
		$('#uploadPackage input[type=submit]').click(function(e) {
			e.preventDefault();
			uploadPackages();
		});
	}

	filterPackagesEvents();
});
