var $j = jQuery.noConflict();

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
	if (lastFileCtrl) {
		var fileCtrl = $j('#' + lastFileCtrl);
		fileCtrl.val(url);
		fileCtrl.trigger('change');
		lastFileCtrl = '';
	} else if (lastImageCtrl) {
		var imageCtrl = $j('#' + lastImageCtrl);
		imageCtrl.val(url);
		imageCtrl.trigger('change');
		lastImageCtrl = '';
	} else {
		return;
	}
}

function TransformField(tvid, tvmode, tvfields, tvlanguage) {
	var field = $j('#' + tvid);
	var fieldMode = tvmode;
	var fieldValue = new Array();
	var fieldSettings = new Object();
	var fieldHeading = $j('#' + tvid + 'heading');
	var fieldNames = tvfields['fieldnames'];
	var fieldTypes = tvfields['fieldtypes'];
	var fieldCsvSeparator = tvfields['csvseparator'];
	var fieldList = $j('#' + tvid + 'list');
	var fieldListElement = fieldList.find('li:first');
	var fieldListElementEmpty = fieldListElement.clone();
	var fieldEdit = $j('#' + tvid + 'edit');
	var fieldClear = $j('#' + tvid + 'clear');
	var fieldPaste = $j('#' + tvid + 'paste');
	var fieldPasteForm = $j('#' + tvid + 'pasteform');
	var fieldPasteArea = $j('#' + tvid + 'pastearea');
	var fieldListCounter = 1;
	var pasteBox;

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
			switch (type) {
				case 'button':
					break;
				case 'reset':
					break;
				case 'submit':
					break;
				case 'checkbox':
				case 'radio':
					$j(this).attr('checked', false);
					break;
				default:
					$j(this).val('');
			}
		});
		return clone;
	}

	function setMultiValue() {
		var multiElements = fieldList.children('li');
		var values = [];
		multiElements.each(function() {
			var multiElement = $j(this);
			var fieldValues = new Object();
			$j.each(fieldNames, function() {
				var fieldInput = multiElement.find('[name^="' + tvid + this + '_mtv"][type!="hidden"]');
				var fieldValue = fieldInput.getValue();
				fieldValues[this] = fieldValue;
				if (fieldInput.hasClass('image')) {
					setThumbnail(fieldValue, fieldInput.attr('name'), multiElement);
				}
				if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
					fieldInput.setValue(fieldInput.attr('alt').supplant({
						i: fieldSettings.autoincrement,
						alias: $j('[name="alias"]').getValue()

					}));
					fieldSettings.autoincrement++;
				}
			});
			values.push(fieldValues);
		});
		field.setValue($j.toJSON({
			fieldValue: values,
			fieldSettings: fieldSettings
		}));
	}

	function AddElementEvents(element) {
		// datepicker
		element.find('.DatePicker').click(function() {
			var picker = $j(this).datetimepicker({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'dd-mm-yy',
				timeFormat: 'h:mm:ss'
			});
			picker.datepicker('show');
		});
		// file field browser
		element.find('.browsefile').click(function() {
			var field = $j(this).prev('input').attr('id');
			BrowseFileServer(field);
			return false;
		});

		// image field browser
		element.find('.browseimage').click(function() {
			var field = $j(this).prev('input').attr('id');
			BrowseServer(field);
			return false;
		});
		// add element
		element.find('.copy').click(function() {
			var clone = DuplicateElement(fieldListElementEmpty, fieldListCounter);
			$j(this).parents('.element').after(clone);
			clone.show('fast', function() {
				$j(this).removeAttr('style');
			});
			setMultiValue();
			fieldListCounter++;
			return false;
		});
		// remove element
		element.find('.remove').click(function() {
			if (fieldList.find('.element').length > 1) {
				$j(this).parents('.element').hide('fast', function() {
					$j(this).remove();
					setMultiValue();
				});
			} else {
				// clear inputs/textarea
				var inputs = $j(this).parent().find('[name]');
				inputs.each(function() {
					var type = $j(this).attr('type');
					switch (type) {
						case 'button':
							break;
						case 'reset':
							break;
						case 'submit':
							break;
						case 'checkbox':
						case 'radio':
							$j(this).attr('checked', false);
							break;
						default:
							$j(this).val('');
					}
				});
			}
			return false;
		});
		// change field
		element.find('[name]').bind('change keyup mouseup', function() {
			setMultiValue();
			return false;
		});
	}

	function setThumbnail(fieldValue, fieldName, listElement) {
		var thumbPath = fieldValue.split('/');
		var thumbName = thumbPath.pop();
		var thumbId = fieldName.replace(/^(.*?)(\d*)$/, '#$1preview$2');
		if (thumbName !== '') {
			listElement.find(thumbId).html('<img src="../' + thumbPath.join("/") + '/.thumb_' + thumbName + '" />');
		} else {
			listElement.find(thumbId).html('');
		}
	}

	function prefillInputs(fieldValue) {
		if (fieldMode === 'single') {
			fieldValue = [fieldValue[0]];
		}
		if (fieldValue) {
			$j.each(fieldValue, function() {
				var values = this;
				if (fieldListCounter === 1) {
					$j.each(values, function(key, value) {
						var fieldName = (typeof key === 'number') ? fieldNames[key] : key;
						var fieldInput = fieldListElement.find('[name^="' + tvid + fieldName + '_mtv"][type!="hidden"]');
						fieldInput.setValue(values[key]);
						if (fieldInput.hasClass('image')) {
							setThumbnail(values[key], fieldInput.attr('name'), fieldListElement);
						}
						if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
							fieldInput.setValue(fieldInput.attr('alt').supplant({
								i: fieldSettings.autoincrement,
								alias: $j('[name="alias"]').getValue()
							}));
							fieldSettings.autoincrement++;
						}
					});
				} else {
					var clone = DuplicateElement(fieldListElementEmpty, fieldListCounter);
					clone.show();
					fieldList.append(clone);
					$j.each(values, function(key, value) {
						var fieldName = (typeof key === 'number') ? fieldNames[key] : key;
						var fieldInput = clone.find('[name^="' + tvid + fieldName + '_mtv"][type!="hidden"]');
						fieldInput.setValue(values[key]);
						if (fieldInput.hasClass('image')) {
							setThumbnail(values[key], fieldInput.attr('name'), clone);
						}
						if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
							fieldInput.setValue(fieldInput.attr('alt').supplant({
								i: fieldSettings.autoincrement,
								alias: $j('[name="alias"]').getValue()
							}));
							fieldSettings.autoincrement++;
						}
					});
				}
				fieldListCounter++;
			});
		}
		field.addClass('transformed');

	}

	if (!field.hasClass('transformed')) {
		// reset all event
		fieldClear.find('a').click(function() {
			var answer = confirm(tvlanguage.confirmclear);
			if (answer) {
				fieldList.children('li').remove();
				field.val('[]');
				fieldClear.hide();
				fieldPaste.hide();
				fieldHeading.hide();
				fieldEdit.show();
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
			fieldPaste.show();
			fieldHeading.show();
			fieldEdit.hide();
			// sortable
			fieldList.sortable({
				stop: function() {
					setMultiValue();
				},
				axis: 'y',
				helper: 'clone'
			});
			AddElementEvents(clone);
			return false;
		});

		// paste box
		pasteBox = fieldPaste.find('a').click(function(e) {
			e.preventDefault();
			$j.colorbox({
				inline: true,
				href: $j(this).attr('href'),
				width: "500px",
				height: "350px",
				onClosed: function() {
					fieldPasteArea.html('');
				},
				close: '',
				open: true,
				opacity: '0.35',
				initialWidth: '0',
				initialHeight: '0',
				overlayClose: false
			});
		});

		// close paste box
		fieldPasteForm.find('.cancel').click(function() {
			pasteBox.colorbox.close();
			return false;
		});

		// save pasted form
		fieldPasteForm.find('.replace, .append').click(function() {
			var pastedArray = [];
			var mode = $j(this).attr('class');
			var pasteas = $j('input:radio[name=pasteas]:checked').val();
			var clean;
			switch (pasteas) {
				case 'google':
					clean = fieldPasteArea.htmlClean({
						allowedTags: ['div', 'span']
					});
					clean.find('div').each(function() {
						var pastedRow = [];
						var tableData = $j(this).html().split('<span></span>');
						if (tableData.length > 0) {
							var i = 0;
							$j.each(tableData, function() {
								if (fieldTypes[i] === 'thumb') {
									pastedRow.push('');
									i++;
								}
								pastedRow.push($j.trim(this));
								i++;
							});
							pastedArray.push(pastedRow);
						}
					});
					break;
				case 'csv':
					clean = fieldPasteArea.htmlClean({
						allowedTags: ['div', 'p']
					});
					clean.find('div, p').each(function() {
						var pastedRow = [];
						// CSV Parser credit goes to Brian Huisman, from his blog entry entitled "CSV String to Array in JavaScript": http://www.greywyvern.com/?post=258
						for (var tableData = $j(this).html().split(fieldCsvSeparator), x = tableData.length - 1, tl; x >= 0; x--) {
							if (tableData[x].replace(/"\s+$/, '"').charAt(tableData[x].length - 1) === '"') {
								if ((tl = tableData[x].replace(/^\s+"/, '"')).length > 1 && tl.charAt(0) === '"') {
									tableData[x] = tableData[x].replace(/^\s*"|"\s*$/g, '').replace(/""/g, '"');
								} else if (x) {
									tableData.splice(x - 1, 2, [tableData[x - 1], tableData[x]].join(fieldCsvSeparator));
								} else
									tableData = tableData.shift().split(fieldCsvSeparator).concat(tableData);
							} else
								tableData[x].replace(/""/g, '"');
						}
						if (tableData.length > 0) {
							var i = 0;
							$j.each(tableData, function() {
								if (fieldTypes[i] === 'thumb') {
									pastedRow.push('');
									i++;
								}
								pastedRow.push($j.trim(this));
								i++;
							});
							pastedArray.push(pastedRow);
						}
					});
					break;
				case 'word':
				default:
					clean = fieldPasteArea.htmlClean({
						allowedTags: ['table', 'tbody', 'tr', 'td']
					}).html();
					clean = clean.replace(/\n/mg, '').replace(/.*<table>/mg, '<table>').replace(/<\/table>.*/mg, '</table>');
					$j(clean).find('tr').each(function() {
						var pastedRow = [];
						var tableData = $j(this).find('td');
						if (tableData.length > 0) {
							var i = 0;
							tableData.each(function() {
								if (fieldTypes[i] === 'thumb') {
									pastedRow.push('');
									i++;
								}
								pastedRow.push($j(this).text());
								i++;
							});
							pastedArray.push(pastedRow);
						}
					});
					break;
			}
			fieldList.find('li:gt(0)').remove();
			fieldListCounter = 1;
			if (mode === 'append') {
				pastedArray = $j.merge(fieldValue, pastedArray);
			}
			prefillInputs(pastedArray);
			setMultiValue();
			pasteBox.colorbox.close();
			return false;
		});
	}

	// transform the input		
	if (field.val() !== '@INHERIT') {
		if (!field.hasClass('transformed')) {
			var jsonValue = $j.evalJSON(field.val());
			if (jsonValue) {
				if (jsonValue.constructor === Array) {
					fieldValue = jsonValue;
					fieldSettings.autoincrement = fieldValue.length + 1;
				} else {
					fieldValue = jsonValue.fieldValue;
					fieldSettings = jsonValue.fieldSettings;
				}
			} else {
				fieldValue = [];
				fieldSettings.autoincrement = 1;
			}

			//field.hide();
			fieldEdit.hide();
			AddElementEvents(fieldListElement);

			// sortable
			if (fieldMode !== 'single') {
				fieldList.sortable({
					stop: function() {
						setMultiValue();
					},
					axis: 'y',
					helper: 'clone'
				});
			}
			prefillInputs(fieldValue);
		}

	} else {
		fieldHeading.hide();
		fieldList.hide();
		field.hide();
		fieldClear.hide();
		fieldPaste.hide();
	}
}

function TransformDatatable(tvid, tvfields, tvlanguage) {
	var field = $j('#' + tvid);
	var fieldValue = new Array();
	var fieldSettings = new Object();
	var fieldHeading = $j('#' + tvid + 'heading');
	var fieldNames = tvfields['fieldnames'];
	var fieldTypes = tvfields['fieldtypes'];
	var fieldColumns = tvfields['fieldcolumns'];
	var fieldRenderer = tvfields['fieldrenderer'];
	var fieldCsvSeparator = tvfields['csvseparator'];
	var fieldTable = $j('#' + tvid + 'table');
	var fieldEdit = $j('#' + tvid + 'edit');
	var fieldClear = $j('#' + tvid + 'clear');
	var fieldPaste = $j('#' + tvid + 'paste');
	var fieldEditForm = $j('#' + tvid + 'editform');
	var fieldEditArea = $j('#' + tvid + 'editarea');
	var tableButtons = $j('<ul>').addClass('actionButtons');
	var tableAppend = $j('<li>').attr('id', tvid + 'tableAppend').append($j('<a>').html('<img alt="' + tvlanguage.append + ' " src="../assets/tvs/multitv/css/images/add.png" /> ' + tvlanguage.append));
	var tableEdit = $j('<li>').attr('id', tvid + 'tableEdit').append($j('<a>').addClass('disabled').html('<img alt="' + tvlanguage.edit + ' " src="../assets/tvs/multitv/css/images/application_form_edit.png" /> ' + tvlanguage.edit));
	var tableRemove = $j('<li>').attr('id', tvid + 'tableRemove').append($j('<a>').addClass('disabled').html('<img alt="' + tvlanguage.remove + ' " src="../assets/tvs/multitv/css/images/delete.png" /> ' + tvlanguage.remove));
	var fieldListCounter = 1;
	var editBox;

	var aaData = new Array();
	var aoColumns = new Array();

	function compareId(a, b) {
		if (a.id < b.id)
			return -1;
		if (a.id > b.id)
			return 1;
		return 0;
	}

	function clearInputs(elem) {
		fieldEditArea.find('.tabEditor').each(function() {
			var editorId = $j(this).attr('id');
			tinyMCE.get(editorId).remove();
		});
		var inputs = elem.find(':input');
		inputs.each(function() {
			var inputtype = $j(this).attr('type');
			var inputid = $j(this).attr('id');
			switch (inputtype) {
				case 'button':
					break;
				case 'reset':
					break;
				case 'submit':
					break;
				case 'checkbox':
				case 'radio':
					$j(this).attr('checked', false);
					break;
				default:
					$j(this).val('');
			}
		});
	}

	function setMultiValue() {
		var currentValue = fieldTable.fnGetData();
		var saveValue = new Array();
		currentValue.sort(compareId);

		$j.each(currentValue, function() {
			var row = new Object();
			$j.each(this, function(key, value) {
				if (key !== 'DT_RowId' && key !== 'MTV_RowId') {
					row[key] = value;
				}
			});
			saveValue.push(row);
		});
		if (saveValue.length) {
			field.setValue($j.toJSON({
				fieldValue: saveValue,
				fieldSettings: fieldSettings
			}));
		} else {
			field.setValue('');
		}
	}

	function AddElementEvents(element) {
		// datepicker
		element.find('.DatePicker').click(function() {
			var picker = $j(this).datetimepicker({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'dd-mm-yy',
				timeFormat: 'h:mm:ss'
			});
			picker.datepicker('show');
		});
		// file field browser
		element.find('.browsefile').click(function() {
			var field = $j(this).prev('input').attr('id');
			BrowseFileServer(field);
			return false;
		});

		// image field browser
		element.find('.browseimage').click(function() {
			var field = $j(this).prev('input').attr('id');
			BrowseServer(field);
			return false;
		});
	}


	// transform the input
	if (field.val() !== '@INHERIT') {
		if (!field.hasClass('transformed')) {
			var jsonValue = $j.evalJSON(field.val());
			if (jsonValue) {
				if (jsonValue.constructor === Array) {
					fieldValue = jsonValue;
					fieldSettings.autoincrement = fieldValue.length + 1;
				} else {
					fieldValue = jsonValue.fieldValue;
					fieldSettings = jsonValue.fieldSettings;
				}
			} else {
				fieldValue = [];
				fieldSettings.autoincrement = 1;
			}
			$j.each(fieldValue, function(key, value) {
				this.DT_RowId = key + 1;
				this.MTV_RowId = key + 1;
			});

			field.hide();
			fieldEdit.hide();
			fieldTable.dataTable({
				sDom: '<"clear">lfrtip',
				aaData: fieldValue,
				aoColumns: fieldColumns,
				bAutoWidth: false,
				oLanguage: dataTableLanguage
			}).rowReordering({
				fnAfterMove: function() {
					setMultiValue();
					fieldTable.fnDraw();
				}
			});

			fieldTable.find('tr').live('click', function() {
				if ($(this).hasClass('row_selected')) {
					$(this).removeClass('row_selected');
					tableEdit.find('a').addClass('disabled');
					tableRemove.find('a').addClass('disabled');
				}
				else {
					fieldTable.$('tr.row_selected').removeClass('row_selected');
					$(this).addClass('row_selected');
					tableEdit.find('a').removeClass('disabled');
					tableRemove.find('a').removeClass('disabled');
				}
			});
			fieldTable.parent().prepend(tableButtons);
			tableButtons.append(tableAppend, tableRemove, tableEdit);

			// remove row event
			tableRemove.find('a').click(function(e) {
				e.preventDefault();
				if ($j(this).hasClass('disabled')) {
					return false;
				}
				var selected = fieldTable.find('.row_selected')[0];
				fieldTable.fnDeleteRow(selected);
				setMultiValue();
			});

			// edit/append row event
			editBox = tableEdit.add(tableAppend).find('a').click(function(e) {
				e.preventDefault();
				if ($j(this).hasClass('disabled')) {
					return false;
				}
				var mode = $j(this).parent().attr('id').replace(/tv.\d+table/, '').toLowerCase();
				var selected = fieldTable.find('.row_selected')[0];
				if (selected && mode === 'edit') {
					var lineValue = fieldTable.fnGetData(selected);
					$j.each(lineValue, function(key, value) {
						$j('#' + tvid + key + '_mtv').setValue(value);
					});
				}
				fieldEditForm.find('.mode').hide();
				fieldEditForm.find('.mode.' + mode).show();
				fieldEditForm.find(".editformtabs").easytabs({
					animate: false
				}).bind('easytabs:after', function() {
					editBox.colorbox.resize();
				});
				$j.colorbox({
					inline: true,
					href: '#' + tvid + 'editform',
					width: "550px",
					close: '',
					open: true,
					opacity: '0.35',
					initialWidth: '0',
					initialHeight: '0',
					overlayClose: false,
					scrolling: false,
					onComplete: function() {
						fieldEditArea.find('.tabEditor').each(function() {
							var editorId = $j(this).attr('id');
							tinyMCE.execCommand('mceAddControl', true, editorId);
						});
						editBox.colorbox.resize();
					}
				});
			});

			// save/append edit box
			fieldEditForm.find('.edit,.append').click(function() {
				editBox.colorbox.close();
				tinyMCE.triggerSave();
				var values = new Object();
				fieldEditArea.find(':input').each(function(i) {
					var key = $j(this).attr('name').replace(/tv.\d(.*)_mtv/, '$1');
					if (key !== '') {
						values[key] = $j(this).val();
					}
				});
				if ($j(this).hasClass('edit')) {
					var selected = fieldTable.find('.row_selected')[0];
					var lineValue = fieldTable.fnGetData(selected);
					values.MTV_RowId = lineValue.MTV_RowId;
					values.DT_RowId = lineValue.DT_RowId;
					fieldTable.fnUpdate(values, selected);
				} else {
					values.MTV_RowId = fieldValue.length + 1;
					values.DT_RowId = fieldValue.length + 1;
					fieldTable.fnAddData(values);
				}
				clearInputs(fieldEditArea);
				setMultiValue();
				return false;
			});

			// close edit box
			fieldEditForm.find('.cancel').click(function() {
				editBox.colorbox.close();
				clearInputs(fieldEditArea);
				return false;
			});

			AddElementEvents(fieldEditForm);
			field.addClass('transformed');
		}

	} else {
		fieldHeading.hide();
		fieldTable.hide();
		field.hide();
		fieldClear.hide();
		fieldPaste.hide();
	}
}