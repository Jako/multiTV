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

(function($) {

	$.fn.transformField = function(options) {

		// Create some defaults, extending them with any options that were provided
		var settings = $.extend({
			mode: '',
			fieldsettings: '',
			language: ''
		}, options);

		return this.each(function() {

			var field = $(this);
			var tvid = field.attr('id');
			var data = new Object();
			var fieldHeading = $('#' + tvid + 'heading');
			var fieldNames = settings.fieldsettings['fieldnames'];
			var fieldTypes = settings.fieldsettings['fieldtypes'];
			var fieldCsvSeparator = settings.fieldsettings['csvseparator'];
			var fieldList = $('#' + tvid + 'list');
			var fieldListElement = fieldList.find('li:first');
			var fieldListElementEmpty = fieldListElement.clone();
			var fieldEdit = $('#' + tvid + 'edit');
			var fieldClear = $('#' + tvid + 'clear');
			var fieldPaste = $('#' + tvid + 'paste');
			var fieldPasteForm = $('#' + tvid + 'pasteform');
			var fieldPasteArea = $('#' + tvid + 'pastearea');
			var fieldListCounter = 1;
			var pasteBox;

			function DuplicateElement(el, count) {
				var clone = el.clone(true).hide();
				var elementId;
				clone.find('[id]').each(function() {
					elementId = $(this).attr('id');
					$(this).attr('id', elementId + (count));
				});
				clone.find('[name]').each(function() {
					$(this).attr('name', $(this).attr('name') + (count));
				});
				addElementEvents(clone);

				// clear inputs/textarea
				var inputs = clone.find(':input');
				inputs.each(function() {
					var type = $(this).attr('type');
					switch (type) {
						case 'button':
							break;
						case 'reset':
							break;
						case 'submit':
							break;
						case 'checkbox':
						case 'radio':
							$(this).attr('checked', false);
							break;
						default:
							$(this).val('');
					}
				});
				return clone;
			}

			function saveMultiValue() {
				var multiElements = fieldList.children('li');
				data.values = [];
				multiElements.each(function() {
					var multiElement = $(this);
					var fieldValues = new Object();
					$.each(fieldNames, function() {
						var fieldInput = multiElement.find('[name^="' + tvid + this + '_mtv"][type!="hidden"]');
						var fieldValue = fieldInput.getValue();
						fieldValues[this] = fieldValue;
						if (fieldInput.hasClass('image')) {
							setThumbnail(fieldValue, fieldInput.attr('name'), multiElement);
						}
						if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
							fieldInput.setValue(fieldInput.attr('alt').supplant({
								i: data.settings.autoincrement,
								alias: $('[name="alias"]').getValue()

							}));
							data.settings.autoincrement++;
						}
					});
					data.values.push(fieldValues);
				});
				field.setValue($.toJSON({
					fieldValue: data.values,
					fieldSettings: data.settings
				}));
			}

			function addElementEvents(el) {
				// datepicker
				el.find('.DatePicker').click(function() {
					var picker = $(this).datetimepicker({
						changeMonth: true,
						changeYear: true,
						dateFormat: 'dd-mm-yy',
						timeFormat: 'h:mm:ss'
					});
					picker.datepicker('show');
				});
				// file field browser
				el.find('.browsefile').click(function() {
					var field = $(this).prev('input').attr('id');
					BrowseFileServer(field);
					return false;
				});

				// image field browser
				el.find('.browseimage').click(function() {
					var field = $(this).prev('input').attr('id');
					BrowseServer(field);
					return false;
				});
				// add element
				el.find('.copy').click(function() {
					var clone = DuplicateElement(fieldListElementEmpty, fieldListCounter);
					$(this).parents('.element').after(clone);
					clone.show('fast', function() {
						$(this).removeAttr('style');
					});
					saveMultiValue();
					fieldListCounter++;
					return false;
				});
				// remove element
				el.find('.remove').click(function() {
					if (fieldList.find('.element').length > 1) {
						$(this).parents('.element').hide('fast', function() {
							$(this).remove();
							saveMultiValue();
						});
					} else {
						// clear inputs/textarea
						var inputs = $(this).parent().find('[name]');
						inputs.each(function() {
							var type = $(this).attr('type');
							switch (type) {
								case 'button':
									break;
								case 'reset':
									break;
								case 'submit':
									break;
								case 'checkbox':
								case 'radio':
									$(this).attr('checked', false);
									break;
								default:
									$(this).val('');
							}
						});
					}
					return false;
				});
				// change field
				el.find('[name]').bind('change keyup mouseup', function() {
					saveMultiValue();
					return false;
				});
			}

			function setThumbnail(path, name, el) {
				var thumbPath = path.split('/');
				var thumbName = thumbPath.pop();
				var thumbId = name.replace(/^(.*?)(\d*)$/, '#$1preview$2');
				if (thumbName !== '') {
					el.find(thumbId).html('<img src="../' + thumbPath.join("/") + '/.thumb_' + thumbName + '" />');
				} else {
					el.find(thumbId).html('');
				}
			}

			function prefillInputs() {
				if (settings.mode === 'single') {
					data.value = [data.value[0]];
				}
				if (data.value) {
					$.each(data.value, function() {
						var values = this;
						if (fieldListCounter === 1) {
							$.each(values, function(key, value) {
								var fieldName = (typeof key === 'number') ? fieldNames[key] : key;
								var fieldInput = fieldListElement.find('[name^="' + tvid + fieldName + '_mtv"][type!="hidden"]');
								fieldInput.setValue(values[key]);
								if (fieldInput.hasClass('image')) {
									setThumbnail(values[key], fieldInput.attr('name'), fieldListElement);
								}
								if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
									fieldInput.setValue(fieldInput.attr('alt').supplant({
										i: data.settings.autoincrement,
										alias: $('[name="alias"]').getValue()
									}));
									data.settings.autoincrement++;
								}
							});
						} else {
							var clone = DuplicateElement(fieldListElementEmpty, fieldListCounter);
							clone.show();
							fieldList.append(clone);
							$.each(values, function(key, value) {
								var fieldName = (typeof key === 'number') ? fieldNames[key] : key;
								var fieldInput = clone.find('[name^="' + tvid + fieldName + '_mtv"][type!="hidden"]');
								fieldInput.setValue(values[key]);
								if (fieldInput.hasClass('image')) {
									setThumbnail(values[key], fieldInput.attr('name'), clone);
								}
								if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
									fieldInput.setValue(fieldInput.attr('alt').supplant({
										i: data.settings.autoincrement,
										alias: $('[name="alias"]').getValue()
									}));
									data.settings.autoincrement++;
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
							saveMultiValue();
						},
						axis: 'y',
						helper: 'clone'
					});
					addElementEvents(clone);
					return false;
				});

				// paste box
				pasteBox = fieldPaste.find('a').click(function(e) {
					e.preventDefault();
					$.colorbox({
						inline: true,
						href: $(this).attr('href'),
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
					var mode = $(this).attr('class');
					var pasteas = $('input:radio[name=pasteas]:checked').val();
					var clean;
					switch (pasteas) {
						case 'google':
							clean = fieldPasteArea.htmlClean({
								allowedTags: ['div', 'span']
							});
							clean.find('div').each(function() {
								var pastedRow = [];
								var tableData = $(this).html().split('<span></span>');
								if (tableData.length > 0) {
									var i = 0;
									$.each(tableData, function() {
										if (fieldTypes[i] === 'thumb') {
											pastedRow.push('');
											i++;
										}
										pastedRow.push($.trim(this));
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
								for (var tableData = $(this).html().split(fieldCsvSeparator), x = tableData.length - 1, tl; x >= 0; x--) {
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
									$.each(tableData, function() {
										if (fieldTypes[i] === 'thumb') {
											pastedRow.push('');
											i++;
										}
										pastedRow.push($.trim(this));
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
							$(clean).find('tr').each(function() {
								var pastedRow = [];
								var tableData = $(this).find('td');
								if (tableData.length > 0) {
									var i = 0;
									tableData.each(function() {
										if (fieldTypes[i] === 'thumb') {
											pastedRow.push('');
											i++;
										}
										pastedRow.push($(this).text());
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
						pastedArray = $.merge(data.value, pastedArray);
					}
					prefillInputs(pastedArray);
					saveMultiValue();
					pasteBox.colorbox.close();
					return false;
				});
			}

			// transform the input
			if (field.val() !== '@INHERIT') {
				if (!field.hasClass('transformed')) {
					var jsonValue = $.evalJSON(field.val());
					if (jsonValue) {
						if (jsonValue.constructor === Array) {
							data.value = jsonValue;
							data.settings.autoincrement = data.value.length + 1;
						} else {
							data.value = jsonValue.fieldValue;
							data.settings = jsonValue.fieldSettings;
						}
					} else {
						data.value = [];
						data.settings.autoincrement = 1;
					}

					field.hide();
					fieldEdit.hide();
					addElementEvents(fieldListElement);

					// sortable
					if (settings.mode !== 'single') {
						fieldList.sortable({
							stop: function() {
								saveMultiValue();
							},
							axis: 'y',
							helper: 'clone'
						});
					}
					prefillInputs(data.value);
				}

			} else {
				fieldHeading.hide();
				fieldList.hide();
				field.hide();
				fieldClear.hide();
				fieldPaste.hide();
			}
		});
	};
})(jQuery);

(function($) {

	$.fn.transformDatatable = function(options) {

		// Create some defaults, extending them with any options that were provided
		var settings = $.extend({
			fieldsettings: '',
			language: ''
		}, options);

		return this.each(function() {

			var field = $(this);
			var tvid = field.attr('id');
			var data = new Object();
			var fieldHeading = $('#' + tvid + 'heading');
			var fieldTable = $('#' + tvid + 'table');
			var fieldEdit = $('#' + tvid + 'edit');
			var fieldClear = $('#' + tvid + 'clear');
			var fieldPaste = $('#' + tvid + 'paste');
			var fieldEditForm = $('#' + tvid + 'editform');
			var fieldEditArea = $('#' + tvid + 'editarea');
			var tableButtons = $('<ul>').addClass('actionButtons');
			var tableAppend = $('<li>').attr('id', tvid + 'tableAppend').append($('<a>').html('<img alt="' + settings.language.append + ' " src="../assets/tvs/multitv/css/images/add.png" /> ' + settings.language.append));
			var tableEdit = $('<li>').attr('id', tvid + 'tableEdit').append($('<a>').addClass('disabled').html('<img alt="' + settings.language.edit + ' " src="../assets/tvs/multitv/css/images/application_form_edit.png" /> ' + settings.language.edit));
			var tableRemove = $('<li>').attr('id', tvid + 'tableRemove').append($('<a>').addClass('disabled').html('<img alt="' + settings.language.remove + ' " src="../assets/tvs/multitv/css/images/delete.png" /> ' + settings.language.remove));
			var fieldListCounter = 1;
			var editBox;

			function clearInputs(el) {
				el.find('.tabEditor').each(function() {
					var editorId = $(this).attr('id');
					tinyMCE.get(editorId).remove();
				});
				var inputs = el.find(':input');
				inputs.each(function() {
					var inputtype = $(this).attr('type');
					var inputid = $(this).attr('id');
					switch (inputtype) {
						case 'button':
							break;
						case 'reset':
							break;
						case 'submit':
							break;
						case 'checkbox':
						case 'radio':
							$(this).attr('checked', false);
							break;
						default:
							$(this).val('');
					}
				});
			}

			function saveMultiValue() {

				function compare(a, b) {
					if (a.MTV_RowId < b.MTV_RowId)
						return -1;
					if (a.MTV_RowId > b.MTV_RowId)
						return 1;
					return 0;
				}

				var currentValue = fieldTable.fnGetData();
				var saveValue = new Array();
				currentValue.sort(compare);

				$.each(currentValue, function() {
					var row = new Object();
					$.each(this, function(key, value) {
						if (key !== 'DT_RowId' && key !== 'MTV_RowId') {
							row[key] = value;
						}
					});
					saveValue.push(row);
				});
				if (saveValue.length) {
					field.setValue($.toJSON({
						fieldValue: saveValue,
						fieldSettings: data.settings
					}));
				} else {
					field.setValue('');
				}
			}

			function getMultiValue() {
				var jsonValue = $.evalJSON(field.val());
				if (jsonValue) {
					if (jsonValue.constructor === Array) {
						data.value = jsonValue;
						if (!data.settings) {
							data.settings = new Object();
						}
						data.settings.autoincrement = data.value.length + 1;
					} else {
						data.value = jsonValue.fieldValue;
						data.settings = jsonValue.fieldSettings;
					}
				} else {
					data.value = [];
					data.settings.autoincrement = 1;
				}
				$.each(data.value, function(key, value) {
					this.DT_RowId = key + 1;
					this.MTV_RowId = key + 1;
				});
			}

			function setThumbnail(path, name, el) {
				var thumbPath = path.split('/');
				var thumbName = thumbPath.pop();
				var thumbId = name.replace(/^(.*?)(\d*)$/, '#$1preview$2');
				if (thumbName !== '') {
					el.find(thumbId).html('<img src="../' + thumbPath.join("/") + '/.thumb_' + thumbName + '" />');
				} else {
					el.find(thumbId).html('');
				}
			}

			function addElementEvents(el) {
				// datepicker
				el.find('.DatePicker').click(function() {
					var picker = $(this).datetimepicker({
						changeMonth: true,
						changeYear: true,
						dateFormat: 'dd-mm-yy',
						timeFormat: 'h:mm:ss'
					});
					picker.datepicker('show');
				});
				// file field browser
				el.find('.browsefile').click(function() {
					var field = $(this).prev('input').attr('id');
					BrowseFileServer(field);
					return false;
				});

				// image field browser
				el.find('.browseimage').click(function() {
					var field = $(this).prev('input').attr('id');
					BrowseServer(field);
					return false;
				});
			}

			// transform the input
			if (field.val() !== '@INHERIT') {
				if (!field.hasClass('transformed')) {
					getMultiValue();
					field.hide();
					fieldEdit.hide();
					fieldTable.dataTable({
						sDom: '<"clear">lfrtip',
						aaData: data.value,
						aoColumns: settings.fieldsettings.fieldcolumns,
						bAutoWidth: false,
						oLanguage: dataTableLanguage
					}).rowReordering({
						fnAfterMove: function() {
							saveMultiValue();
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
						if ($(this).hasClass('disabled')) {
							return false;
						}
						var selected = fieldTable.find('.row_selected')[0];
						fieldTable.fnDeleteRow(selected);
						saveMultiValue();
					});

					// edit/append row event
					editBox = tableEdit.add(tableAppend).find('a').click(function(e) {
						e.preventDefault();
						if ($(this).hasClass('disabled')) {
							return false;
						}
						var mode = $(this).parent().attr('id').replace(/tv.\d+table/, '').toLowerCase();
						var selected = fieldTable.find('.row_selected')[0];
						if (selected && mode === 'edit') {
							var lineValue = fieldTable.fnGetData(selected);
							$.each(lineValue, function(key, value) {
								var fieldInput = $('#' + tvid + key + '_mtv');
								if (fieldInput.hasClass('image')) {
									setThumbnail(value, fieldInput.attr('name'), fieldEditArea);
								}
								$('#' + tvid + key + '_mtv').setValue(value);
							});
						}
						fieldEditForm.find('.mode').hide();
						fieldEditForm.find('.mode.' + mode).show();
						fieldEditForm.find(".editformtabs").easytabs({
							animate: false
						}).bind('easytabs:after', function() {
							editBox.colorbox.resize();
						});
						$.colorbox({
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
									var editorId = $(this).attr('id');
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
							var key = $(this).attr('name').replace(/tv.\d(.*)_mtv/, '$1');
							if (key !== '') {
								values[key] = $(this).val();
							}
						});
						if ($(this).hasClass('edit')) {
							var selected = fieldTable.find('.row_selected')[0];
							var lineValue = fieldTable.fnGetData(selected);
							values.MTV_RowId = lineValue.MTV_RowId;
							values.DT_RowId = lineValue.DT_RowId;
							fieldTable.fnUpdate(values, selected);
						} else {
							values.MTV_RowId = data.value.length + 1;
							values.DT_RowId = data.value.length + 1;
							fieldTable.fnAddData(values);
						}
						clearInputs(fieldEditArea);
						saveMultiValue();
						return false;
					});

					// close edit box
					fieldEditForm.find('.cancel').click(function() {
						editBox.colorbox.close();
						clearInputs(fieldEditArea);
						return false;
					});

					addElementEvents(fieldEditForm);
					field.addClass('transformed');
				}

			} else {
				fieldHeading.hide();
				fieldTable.hide();
				field.hide();
				fieldClear.hide();
				fieldPaste.hide();
			}
		});
	};
})(jQuery);
