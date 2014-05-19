var multiTvDefaultTab = jQuery.cookie('multiTvTab') || '.tab:first-child';
multiTvDefaultTab = jQuery(multiTvDefaultTab).length ? multiTvDefaultTab : '.tab:first-child';

jQuery('#multiTvPanes').easytabs({
	tabs: '> .tab-row > .tab',
	defaultTab: multiTvDefaultTab,
	tabActiveClass: 'selected',
	animationSpeed: 'fast'
});
jQuery('#multiTvPanes').bind('easytabs:after', function(evt, tab, panel, data) {
    jQuery.cookie('multiTvTab', '#' + tab.parent().attr('id'));
});
