var $j = jQuery.noConflict();

var lastImageCtrl;
var lastFileCtrl;

function OpenServerBrowser(url, width, height) {
	var iLeft = (screen.width - width) / 2;
	var iTop = (screen.height - height) / 2;

	var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes';
	sOptions  += ',width=' + width;
	sOptions += ',height=' + height;
	sOptions += ',left=' + iLeft;
	sOptions += ',top=' + iTop;

	var oWindow = window.open(url, 'FCKBrowseWindow', sOptions);
}

function multiBrowseServer(ctrl, basepath) {
	lastImageCtrl = ctrl;
	var w = screen.width * 0.7;
	var h = screen.height * 0.7;
	OpenServerBrowser(basepath+'/manager/media/browser/mcpuk/browser.html?Type=images&Connector='+basepath+'/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath='+basepath, w, h);
}

function multiBrowseFileServer(ctrl, basepath) {
	lastFileCtrl = ctrl;
	var w = screen.width * 0.7;
	var h = screen.height * 0.7;
	OpenServerBrowser(basepath+'/manager/media/browser/mcpuk/browser.html?Type=files&Connector='+basepath+'/manager/media/browser/mcpuk/connectors/php/connector.php&ServerPath='+basepath, w, h);
}


function SetUrl(url, width, height, alt) {
	if(lastFileCtrl) {
		$j('#' + lastFileCtrl).val(url);
		$j('#' + lastFileCtrl).trigger('change');
		lastFileCtrl = '';
	} else if(lastImageCtrl) {
		$j('#' + lastImageCtrl).val(url);
		$j('#' + lastImageCtrl).trigger('change');
		lastImageCtrl = '';
	} else {
		return;
	}
}

function TransformField(tvid, tvfields, tvlanguage) {
	var field = $j('#' + tvid);
	var fieldValue = [];
	var fieldHeading = $j('#' + tvid + 'heading');
	var fieldNames = tvfields;
	var fieldList = $j('#' + tvid + 'list');
	var fieldListElement = fieldList.find('li:first');
	var fieldListElementEmpty = fieldListElement.clone();
	var fieldListCopyButton = $j('<a href="#" class="copy" title="'+tvlanguage.add+'">'+tvlanguage.add+'</a>');
	var fieldEdit = $j('#' + tvid + 'edit');
	var fieldClear = $j('#' + tvid + 'clear');
	var fieldListCounter = 1;
	
	function DuplicateElement(element, elementCount) {
		var clone = element.clone(true).hide();
		var elementId;
		clone.find('[id]').each(function() {
			elementId = $j(this).attr('id');
			$j(this).attr('id', elementId + (elementCount));
		});
		clone.find('[name]').each(function() {
			$j(this).attr('name', $j(this).attr('name') + (elementCount));
		});
		AddElementEvents(clone);

		// clear inputs/textarea
		var inputs = clone.find(':input');
		inputs.each(function() {
			var type = $j(this).attr('type');
			switch(type) {
				case 'button':
					break;
				case 'reset':
					break;
				case 'submit':
					break;
				case 'checkbox':
				case 'radio':
					$j(this).attr('checked', '');
					break;
				default:
					$j(this).val('');
			}
		});
		return clone;
	}

	function AddElementEvents(element) {
		// datepicker
		element.find('.DatePicker').datetimepicker({
			changeMonth: true,
			changeYear: true,
			dateFormat: 'dd-mm-yy',
			timeFormat: 'h:mm:ss'
		});
		// file field browser
		element.find('.browsefile').click(function() {
			var field = $j(this).prev('input').attr('id');
			var path = $j(this).attr('rel');
			multiBrowseFileServer(field, path);
			return false;
		});

		// image field browser
		element.find('.browseimage').click(function() {
			var field = $j(this).prev('input').attr('id');
			var path = $j(this).attr('rel');
			multiBrowseServer(field, path);
			return false;
		});
		// remove element
		element.find('.remove').click(function() {
			if(fieldList.find('.element').length > 1) {
				$j(this).parents('.element').remove();
			} else {
				// clear inputs/textarea
				var inputs = $j(this).parent().find('[name]');
				inputs.each(function() {
					var type = $j(this).attr('type');
					switch(type) {
						case 'button':
							break;
						case 'reset':
							break;
						case 'submit':
							break;
						case 'checkbox':
							$j(this).attr('checked', '');
							break;
						default:
							$j(this).val('');
					}
				});
			}
			fieldList.find('li:first input:first').trigger('change');
			return false;
		});
		// change field
		element.find('[name]').bind('change keyup mouseup', function() {
			var multiElements = fieldList.children('li');
			var values = [];
			multiElements.each(function() {
				var multiElement = $j(this);
				var fieldValues = [];
				$j.each(fieldNames, function() {
					var fieldInput = multiElement.find('[name^="'+tvid+this+'_mtv"][type!="hidden"]');
					var fieldValue = fieldInput.getValue();
					fieldValues.push(fieldValue);
					if (fieldInput.hasClass('image')) {
						setThumbnail(fieldValue, fieldInput.attr('name'), multiElement);
					}
				});
				values.push(fieldValues);
			});
			field.val(Json.toString(values));
			return false;
		});

	}

	function setThumbnail(fieldValue, fieldName, listElement) {
		var thumbPath = fieldValue.split('/');
		var thumbName = thumbPath.pop();
		var thumbId = fieldName.replace(/^(.*?)(\d*)$/, '#$1preview$2');
		if (thumbName != '') {
			listElement.find(thumbId).html('<img src="../'+thumbPath.join("/")+'/.thumb_'+thumbName+'" />');
		} else {
			listElement.find(thumbId).html('');
		}
	}
	
	// reset all event
	fieldClear.find('a').click(function() {
		var answer = confirm(tvlanguage.confirmclear);
		if (answer) {
			fieldList.children('li').remove();
			field.val('');
			fieldClear.hide();
			fieldHeading.hide();
			fieldEdit.show();
			fieldListCopyButton.hide();
		}
		return false;
	});

	// start edit event
	fieldEdit.find('a').click(function() {
		var clone = fieldListElementEmpty.clone(true);
		fieldList.append(clone);
		field.val('[]');
		fieldList.show();
		fieldClear.show();
		fieldHeading.show();
		fieldEdit.hide();
		fieldListCopyButton.show();
		AddElementEvents(clone);
		return false;
	});

	// copy element event
	fieldListCopyButton.click(function() {
		var clone = DuplicateElement(fieldListElementEmpty, fieldListCounter);
		fieldList.find('li:last').after(clone);
		clone.show('fast', function() {
			$j(this).removeAttr('style');
		});
		fieldListCounter++;
		fieldList.find('li:first input:first').trigger('change');
		return false;
	});
			
	// transform the input		
	if (field.val() != '@INHERIT') { 
		fieldValue = $j.parseJSON(field.val());
		fieldValue = (fieldValue.constructor == Array) ? fieldValue : [];

		field.hide();
		fieldEdit.hide();
		AddElementEvents(fieldListElement);

		// sortable
		fieldList.sortable({
			stop : function() {
				fieldList.find('li:first input:first').trigger('change');
			},
			axis: 'y',
			helper: 'clone'
		});

		// prefill inputs
		if (!field.hasClass('transformed')) {
			fieldList.before(fieldListCopyButton);
			$j.each(fieldValue, function() {
				var values = this;
				var i = 0;
				if (fieldListCounter == 1) {
					i = 0;
					$j.each(values, function() {
						var fieldInput = fieldListElement.find('[name^="'+tvid+fieldNames[i]+'"][type!="hidden"]');
						fieldInput.setValue(values[i]);
						if (fieldInput.hasClass('image')) {
							setThumbnail(values[i], fieldInput.attr('name'), fieldListElement);
						}
						i++;
					}) 
				} else {
					var clone = DuplicateElement(fieldListElementEmpty, fieldListCounter);
					clone.show();
					fieldList.append(clone);
					i = 0;
					$j.each(values, function() {
						var fieldInput = clone.find('[name^="'+tvid+fieldNames[i]+'"][type!="hidden"]');
						fieldInput.setValue(values[i]);
						if (fieldInput.hasClass('image')) {
							setThumbnail(values[i], fieldInput.attr('name'), clone);
						}
						i++;
					}) 
				//AddElementEvents(clone);
				}
				fieldListCounter++;
			});
			field.addClass('transformed');
		}
	} else {
		fieldHeading.hide();
		fieldList.hide();
		field.hide();
		fieldClear.hide();
		fieldList.before(fieldListCopyButton);
		fieldListCopyButton.hide();
	}
}
