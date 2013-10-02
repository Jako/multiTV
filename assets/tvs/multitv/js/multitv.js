(function($, window, document, undefined) {

	var pluginName = 'transformField',
			defaults = {
		mode: '',
		fieldsettings: '',
		language: '',
		kcfinder: true,
		thumbs: '.thumbs/'
	};

	// Plugin constructor
	function Plugin(el, options) {
		// Extending options
		this.options = $.extend({}, defaults, options);

		// Private
		this._defaults = defaults;
		this._name = pluginName;
		this.$el = $(el);

		this.tvid = this.$el.attr('id');
		this.data = new Object();
		this.fieldHeading = $('#' + this.tvid + 'heading');
		this.fieldNames = this.options.fieldsettings['fieldnames'];
		this.fieldTypes = this.options.fieldsettings['fieldtypes'];
		this.fieldList = $('#' + this.tvid + 'list');
		this.fieldListElement = $('li:first', this.fieldList);
		this.fieldListElementEmpty = this.fieldListElement.clone();
		this.fieldEdit = $('#' + this.tvid + 'edit');
		this.fieldClear = $('#' + this.tvid + 'clear');
		this.fieldPaste = $('#' + this.tvid + 'paste');
		this.fieldPasteForm = $('#' + this.tvid + 'pasteform');
		this.fieldPasteArea = $('#' + this.tvid + 'pastearea');
		this.fieldListCounter = 1;
		this.pasteBox = '';
		this.colorboxProperties = {
			inline: true,
			href: $(this).attr('href'),
			width: '500px',
			height: '350px',
			onClosed: function() {
				_this.fieldPasteArea.html('');
			},
			close: '',
			open: true,
			opacity: '0.35',
			initialWidth: '0',
			initialHeight: '0',
			overlayClose: false
		}

		this.init();
	}
	;

	// Separate functionality from object creation
	Plugin.prototype = {
		init: function() {
			var _this = this;

			if (!_this.$el.hasClass('transformed')) {
				// reset all event
				$('a', _this.fieldClear).click(function(e) {
					e.preventDefault();
					_this.clear();
				});

				// start edit event
				$('a', _this.fieldEdit).click(function(e) {
					e.preventDefault();
					_this.edit();
				});

				// paste box
				_this.pasteBox = $('a', _this.fieldPaste).click(function(e) {
					e.preventDefault();
					$.colorbox(_this.colorboxProperties);
				});

				// close paste box
				$('.cancel', _this.fieldPasteForm).click(function() {
					_this.pasteBox.colorbox.close();
					return false;
				});

				// save pasted form
				$('.replace, .append', _this.fieldPasteForm).click(function() {
					_this.paste();
					_this.prefillInputs();
					_this.saveMultiValue();
					_this.pasteBox.colorbox.close();
					return false;
				});
			}

			// transform the input
			if (_this.$el.val() !== '@INHERIT') {
				if (!_this.$el.hasClass('transformed')) {
					_this.prepareMultiValue();

					_this.$el.hide();
					_this.fieldEdit.hide();
					_this.addElementEvents(_this.fieldListElement);

					// sortable
					if (_this.options.mode !== 'single') {
						_this.fieldList.sortable({
							stop: function() {
								_this.saveMultiValue();
							},
							axis: 'y',
							helper: 'clone'
						});
					}
					_this.prefillInputs(_this.data.value);
					_this.$el.addClass('transformed');
				}

			} else {
				_this.fieldHeading.hide();
				_this.fieldList.hide();
				_this.$el.hide();
				_this.fieldClear.hide();
				_this.fieldPaste.hide();
			}
		},
		duplicateElement: function(el, count) {
			var _this = this;

			var clone = el.clone(true).hide();
			$('[id]', clone).each(function() {
				$(this).attr('id', $(this).attr('id') + (count));
			});
			$('[name]', clone).each(function() {
				$(this).attr('name', $(this).attr('name') + (count));
			});
			_this.addElementEvents(clone);

			// clear inputs/textarea
			var inputs = $(':input', clone);
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
		},
		saveMultiValue: function() {
			var _this = this;

			_this.data.values = [];
			_this.fieldList.children('li').each(function() {
				var multiElement = $(this);
				var fieldValues = new Object();
				$.each(_this.fieldNames, function() {
					var fieldInput = $('[name^="' + _this.tvid + this + '_mtv"][type!="hidden"]', multiElement);
					fieldValues[this] = fieldInput.getValue().replace('&quot;', '"');
					if (fieldInput.hasClass('image')) {
						_this.setThumbnail(fieldValues[this], fieldInput.attr('name'), multiElement);
					}
					if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
						fieldInput.setValue(fieldInput.attr('alt').supplant({
							i: _this.data.settings.autoincrement,
							alias: $('[name="alias"]').getValue()

						}));
						_this.data.settings.autoincrement++;
					}
				});
				_this.data.values.push(fieldValues);
			});
			_this.$el.setValue($.toJSON({
				fieldValue: _this.data.values,
				fieldSettings: _this.data.settings
			}));
		},
		prepareMultiValue: function() {
			var _this = this;

			var jsonValue = $.evalJSON(_this.$el.val().replace(/&#x005B;/g, '[').replace(/&#x005D;/g, ']').replace(/&#x007B;/g, '{').replace(/&#x007B;/g, '}'));
			if (jsonValue) {
				if (jsonValue.constructor === Array) {
					_this.data.value = jsonValue;
					if (!_this.data.settings) {
						_this.data.settings = new Object();
					}
					_this.data.settings.autoincrement = _this.data.value.length + 1;
				} else {
					_this.data.value = jsonValue.fieldValue;
					_this.data.settings = jsonValue.fieldSettings;
				}
			} else {
				_this.data.value = [];
				_this.data.settings.autoincrement = 1;
			}
		},
		addElementEvents: function(el) {
			var _this = this;

			// datepicker
			$('.mtvDatePicker', el).click(function() {
				var picker = $(this).datetimepicker({
					changeMonth: true,
					changeYear: true,
					dateFormat: 'dd-mm-yy',
					timeFormat: 'h:mm:ss'
				});
				picker.datepicker('show');
			});
			// file field browser
			$('.browsefile', el).click(function(e) {
				e.preventDefault();
				var field = $(this).prev('input').attr('id');
				BrowseFileServer(field);
			});

			// image field browser
			$('.browseimage', el).click(function(e) {
				e.preventDefault();
				var field = $(this).prev('input').attr('id');
				BrowseServer(field);
			});
			// add element
			$('.copy', el).click(function(e) {
				e.preventDefault();
				var clone = _this.duplicateElement(_this.fieldListElementEmpty, _this.fieldListCounter);
				$(this).parents('.element').after(clone);
				clone.show('fast', function() {
					$(this).removeAttr('style');
				});
				_this.saveMultiValue();
				_this.fieldListCounter++;
			});
			// remove element
			$('.remove', el).click(function(e) {
				e.preventDefault();
				if ($('.element', _this.fieldList).length > 1) {
					$(this).parents('.element').hide('fast', function() {
						$(this).remove();
						_this.saveMultiValue();
					});
				} else {
					// clear inputs/textarea if only one element is present
					var inputs = $('[name]', $(this).parent());
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
			});
			// change field
			$('[name]', el).bind('change keyup mouseup', function(e) {
				e.preventDefault();
				_this.saveMultiValue();
			});
		},
		setThumbnail: function(path, name, el) {
			var _this = this;

			var thumbPath = path.split('/');
			var thumbName = thumbPath.pop();
			var thumbId = name.replace(/^(.*?)(\d*)$/, '#$1preview$2');
			if (thumbName !== '') {
				if (_this.options.kcfinder) {
					$(thumbId, el).html('<img src="../' + (thumbPath.join('/') + '/').replace('/images/', '/' + _this.options.thumbs + 'images/') + thumbName + '" />');
				} else {
					$(thumbId, el).html('<img src="../' + thumbPath.join('/') + '/.thumb_' + thumbName + '" />');
				}
			} else {
				$(thumbId, el).html('');
			}
		},
		prefillInputs: function() {
			var _this = this;

			if (_this.data.value) {
				if (_this.options.mode === 'single') {
					_this.data.value = [_this.data.value[0]];
				}
				$.each(_this.data.value, function() {
					var values = this;
					if (_this.fieldListCounter === 1) {
						$.each(values, function(key, value) {
							var fieldName = (typeof key === 'number') ? _this.fieldNames[key] : key;
							var fieldInput = $('[name^="' + _this.tvid + fieldName + '_mtv"][type!="hidden"]', _this.fieldListElement);
							fieldInput.setValue(values[key]);
							if (fieldInput.hasClass('image')) {
								_this.setThumbnail(values[key], fieldInput.attr('name'), _this.fieldListElement);
							}
							if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
								fieldInput.setValue(fieldInput.attr('alt').supplant({
									i: _this.data.settings.autoincrement,
									alias: $('[name="alias"]').getValue()
								}));
								_this.data.settings.autoincrement++;
							}
						});
					} else {
						var clone = _this.duplicateElement(_this.fieldListElementEmpty, _this.fieldListCounter);
						clone.show();
						_this.fieldList.append(clone);
						$.each(values, function(key, value) {
							var fieldName = (typeof key === 'number') ? _this.fieldNames[key] : key;
							var fieldInput = $('[name^="' + _this.tvid + fieldName + '_mtv"][type!="hidden"]', clone);
							fieldInput.setValue(values[key]);
							if (fieldInput.hasClass('image')) {
								_this.setThumbnail(values[key], fieldInput.attr('name'), clone);
							}
							if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
								fieldInput.setValue(fieldInput.attr('alt').supplant({
									i: _this.data.settings.autoincrement,
									alias: $('[name="alias"]').getValue()
								}));
								_this.data.settings.autoincrement++;
							}
						});
					}
					_this.fieldListCounter++;
				});
			}

		},
		clear: function() {
			if (confirm(this.options.language.confirmclear)) {
				this.fieldList.children('li').remove();
				this.$el.val('[]');
				this.fieldClear.hide();
				this.fieldPaste.hide();
				this.fieldHeading.hide();
				this.fieldEdit.show();
			}
			return false;
		},
		edit: function() {
			var clone = this.fieldListElementEmpty.clone(true);
			this.fieldList.append(clone);
			this.$el.val('[]');
			this.fieldList.show();
			this.fieldClear.show();
			this.fieldPaste.show();
			this.fieldHeading.show();
			this.fieldEdit.hide();
			// sortable
			this.fieldList.sortable({
				stop: function() {
					this.saveMultiValue();
				},
				axis: 'y',
				helper: 'clone'
			});
			this.addElementEvents(clone);
			return false;
		},
		paste: function() {
			var _this = this;

			var pastedArray = [];
			var mode = $(this).attr('class');
			var pasteas = $('input:radio[name=pasteas]:checked').val();
			var clean;
			switch (pasteas) {
				case 'google':
					clean = _this.fieldPasteArea.htmlClean({
						allowedTags: ['div', 'span']
					});
					$('div', clean).each(function() {
						var pastedRow = [];
						var tableData = $(this).html().split('<span></span>');
						if (tableData.length > 0) {
							var i = 0;
							$.each(tableData, function() {
								if (_this.fieldTypes[i] === 'thumb') {
									pastedRow[_this.fieldNames[i]] = '';
									i++;
								}
								pastedRow[_this.fieldNames[i]] = $.trim(this);
								i++;
							});
							pastedArray.push(pastedRow);
						}
					});
					break;
				case 'csv':
					clean = _this.fieldPasteArea[0].innerText;
					clean = clean.split('\n');
					$.each(clean, function(index, value) {
						// CSV Parser credit goes to Brian Huisman, from his blog entry entitled "CSV String to Array in JavaScript": http://www.greywyvern.com/?post=258
						for (var tableData = value.split(_this.options.fieldsettings['csvseparator']), x = tableData.length - 1, tl; x >= 0; x--) {
							if (tableData[x].replace(/"\s+$/, '"').charAt(tableData[x].length - 1) === '"') {
								if ((tl = tableData[x].replace(/^\s+"/, '"')).length > 1 && tl.charAt(0) === '"') {
									tableData[x] = tableData[x].replace(/^\s*"|"\s*$/g, '').replace(/""/g, '"');
								} else if (x) {
									tableData.splice(x - 1, 2, [tableData[x - 1], tableData[x]].join(_this.options.fieldsettings['csvseparator']));
								} else
									tableData = tableData.shift().split(_this.options.fieldsettings['csvseparator']).concat(tableData);
							} else
								tableData[x].replace(/""/g, '"');
						}
						var pastedRow = {};
						if (tableData.length > 0) {
							var i = 0;
							$.each(tableData, function() {
								if (_this.fieldTypes[i] === 'thumb') {
									pastedRow[_this.fieldNames[i]] = '';
									i++;
								}
								pastedRow[_this.fieldNames[i]] = $.trim(this);
								i++;
							});
							pastedArray.push(pastedRow);
						}
					});
					break;
				case 'word':
				default:
					clean = _this.fieldPasteArea.htmlClean({
						allowedTags: ['table', 'tbody', 'tr', 'td']
					}).html();
					clean = clean.replace(/\n/mg, '').replace(/.*<table>/mg, '<table>').replace(/<\/table>.*/mg, '</table>');
					$('tr', $(clean)).each(function() {
						var pastedRow = [];
						var tableData = $('td', $(this));
						if (tableData.length > 0) {
							var i = 0;
							$.each(tableData, function() {
								if (_this.fieldTypes[i] === 'thumb') {
									pastedRow[_this.fieldNames[i]] = '';
									i++;
								}
								pastedRow[_this.fieldNames[i]] = $.trim(this);
								i++;
							});
							pastedArray.push(pastedRow);
						}
					});
					break;
			}
			$('li:gt(0)', _this.fieldList).remove();
			_this.fieldListCounter = 1;
			if (mode === 'append') {
				_this.data.value = $.merge(_this.data.value, pastedArray);
			}
		}
	};

	// The actual plugin
	$.fn[pluginName] = function(options) {
		var args = arguments;
		if (options === undefined || typeof options === 'object') {
			return this.each(function() {
				if (!$.data(this, 'plugin_' + pluginName)) {
					$.data(this, 'plugin_' + pluginName, new Plugin(this, options));
				}
			});
		} else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
			var returns;
			this.each(function() {
				var instance = $.data(this, 'plugin_' + pluginName);
				if (instance instanceof Plugin && typeof instance[options] === 'function') {
					returns = instance[options].apply(instance, Array.prototype.slice.call(args, 1));
				}
				if (options === 'destroy') {
					$.data(this, 'plugin_' + pluginName, null);
				}
			});
			return returns !== undefined ? returns : this;
		}
	};
}(jQuery, window, document));

(function($, window, document, undefined) {

	var pluginName = 'transformDatatable',
			defaults = {
		mode: '',
		fieldsettings: '',
		language: '',
		kcfinder: true,
		thumbs: '.thumbs/'
	};

	// Plugin constructor
	function Plugin(el, options) {
		// Extending options
		this.options = $.extend({}, defaults, options);

		// Private
		this._defaults = defaults;
		this._name = pluginName;
		this.$el = $(el);

		this.tvid = this.$el.attr('id');
		this.data = new Object();
		this.fieldHeading = $('#' + this.tvid + 'heading');
		this.fieldTable = $('#' + this.tvid + 'table');
		this.fieldEdit = $('#' + this.tvid + 'edit');
		this.fieldClear = $('#' + this.tvid + 'clear');
		this.fieldPaste = $('#' + this.tvid + 'paste');
		this.fieldEditForm = $('#' + this.tvid + 'editform');
		this.fieldEditArea = $('#' + this.tvid + 'editarea');
		this.tableAppend = '<img alt="' + this.options.language.append + ' " src="../assets/tvs/multitv/css/images/add.png" /> ' + this.options.language.append;
		this.tableEdit = '<img alt="' + this.options.language.edit + ' " src="../assets/tvs/multitv/css/images/application_form_edit.png" /> ' + this.options.language.edit;
		this.tableRemove = '<img alt="' + this.options.language.remove + ' " src="../assets/tvs/multitv/css/images/delete.png" /> ' + this.options.language.remove;
		this.tableButtons = $('<ul>').addClass('actionButtons');
		this.tableButtonAppend = $('<li>').attr('id', this.tvid + 'tableAppend').append($('<a>').attr('href', '#').html(this.tableAppend));
		this.tableButtonEdit = $('<li>').attr('id', this.tvid + 'tableEdit').append($('<a>').attr('href', '#').addClass('disabled').html(this.tableEdit));
		this.tableButtonRemove = $('<li>').attr('id', this.tvid + 'tableRemove').append($('<a>').attr('href', '#').addClass('disabled').html(this.tableRemove));
		this.tableClasses = this.options.fieldsettings['tableClasses'];
		this.radioTabs = this.options.fieldsettings['radioTabs'];
		this.editBox = '';

		this.init();
	}

	// Separate functionality from object creation
	Plugin.prototype = {
		init: function() {
			var _this = this;

			// transform the input
			if (_this.$el.val() !== '@INHERIT') {
				if (!_this.$el.hasClass('transformed')) {
					_this.prepareMultiColumns();
					_this.prepareMultiValue();
					_this.$el.hide();
					_this.fieldEdit.hide();
					_this.fieldTable.dataTable({
						sDom: '<"clear">lfrtip',
						aaData: _this.data.value,
						aoColumns: _this.options.fieldsettings.fieldcolumns,
						bAutoWidth: false,
						oLanguage: dataTableLanguage,
						fnRowCallback: function(nRow, aData, iDisplayIndex) {
							_this.contextMenu(nRow, iDisplayIndex);
							_this.toggleRow(nRow);
						}
					}).rowReordering({
						fnAfterMove: function() {
							_this.saveMultiValue();
							_this.fieldTable.fnDraw();
						}
					}).addClass(_this.tableClasses);

					// buttons above datatable
					_this.fieldTable.parent().prepend(_this.tableButtons);
					_this.tableButtons.append(_this.tableButtonAppend, _this.tableButtonRemove, _this.tableButtonEdit);

					// remove row event
					$('a', _this.tableButtonRemove).click(function(e) {
						e.preventDefault();
						if ($(this).hasClass('disabled')) {
							return false;
						}
						_this.removeRow($('.row_selected', _this.fieldTable)[0]);
					});

					// edit/append row event
					_this.editBox = $('a', _this.tableButtonEdit.add(_this.tableButtonAppend)).click(function(e) {
						e.preventDefault();
						if ($(this).hasClass('disabled')) {
							return false;
						}
						_this.editRow($(this).parent().attr('id').replace(/tv\d+table/, '').toLowerCase(), $('.row_selected', _this.fieldTable)[0]);
					});

					// save/append edit box
					$('.edit,.append', _this.fieldEditForm).click(function(e) {
						e.preventDefault();
						_this.saveRow($(this).hasClass('edit') ? 'edit' : 'append');
					});

					// close edit box
					$('.cancel', _this.fieldEditForm).click(function(e) {
						e.preventDefault();
						_this.editBox.colorbox.close();
					});

					_this.addElementEvents(_this.fieldEditForm);
					_this.$el.addClass('transformed');
				}

			} else {
				_this.fieldHeading.hide();
				_this.fieldTable.hide();
				_this.$el.hide();
				_this.fieldClear.hide();
				_this.fieldPaste.hide();
			}
		},
		clearInputs: function(el) {
			$('.tabEditor', el).each(function() {
				var editorId = $(this).attr('id');
				tinyMCE.execCommand('mceRemoveControl', false, editorId);
			});
			$(':input', el).each(function() {
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
		},
		saveMultiValue: function() {
			var _this = this;

			function compare(a, b) {
				if (a.MTV_RowId < b.MTV_RowId)
					return -1;
				if (a.MTV_RowId > b.MTV_RowId)
					return 1;
				return 0;
			}

			var currentValue = _this.fieldTable.fnGetData();
			var saveValue = new Array();

			currentValue.sort(compare);

			$.each(currentValue, function() {
				var row = new Object();
				$.each(this, function(key, value) {
					if (key !== 'DT_RowId' && key !== 'MTV_RowId' && key.substr(0, 9) !== 'mtvRender') {
						row[key] = value.replace('&quot;', '"');
					}
				});
				saveValue.push(row);
			});
			if (saveValue.length) {
				_this.$el.setValue($.toJSON({
					fieldValue: saveValue,
					fieldSettings: _this.data.settings
				}));
			} else {
				_this.$el.setValue('');
			}
		},
		prepareMultiColumns: function() {
			var _this = this;

			$.each(_this.options.fieldsettings.fieldcolumns, function(key, value) {
				if (this.render) {
					this.mRender = function(data, type, full) {
						return full[this.render];
					};
				}
			});
		},
		prepareMultiValue: function() {
			var _this = this;

			var jsonValue = $.evalJSON(_this.$el.val().replace(/&#x005B;/g, '[').replace(/&#x005D;/g, ']').replace(/&#x007B;/g, '{').replace(/&#x007B;/g, '}'));
			if (jsonValue) {
				if (jsonValue.constructor === Array) {
					_this.data.value = jsonValue;
					if (!_this.data.settings) {
						_this.data.settings = new Object();
					}
					_this.data.settings.autoincrement = _this.data.value.length + 1;
				} else {
					_this.data.value = jsonValue.fieldValue;
					_this.data.settings = jsonValue.fieldSettings;
				}
			} else {
				_this.data.value = [];
				_this.data.settings.autoincrement = 1;
			}
			$.each(_this.data.value, function(key, value) {
				this.DT_RowId = _this.tvid + (key + 1);
				this.MTV_RowId = (key + 1);
			});
		},
		setThumbnail: function(path, name, el) {
			var _this = this;

			var thumbPath = path.split('/');
			var thumbName = thumbPath.pop();
			var thumbId = name.replace(/^(.*?)(\d*)$/, '#$1preview$2');
			if (thumbName !== '') {
				if (_this.options.kcfinder) {
					$(thumbId, el).html('<img src="../' + (thumbPath.join('/') + '/').replace('/images/', '/' + _this.options.thumbs + 'images/') + thumbName + '" />');
				} else {
					$(thumbId, el).html('<img src="../' + thumbPath.join('/') + '/.thumb_' + thumbName + '" />');
				}
			} else {
				$(thumbId, el).html('');
			}
		},
		addElementEvents: function(el) {
			// datepicker
			$('.mtvDatePicker', el).click(function() {
				var picker = $(this).datetimepicker({
					changeMonth: true,
					changeYear: true,
					dateFormat: 'dd-mm-yy',
					timeFormat: 'h:mm:ss'
				});
				picker.datepicker('show');
			});
			// file field browser
			$('.browsefile', el).click(function() {
				var field = $(this).prev('input').attr('id');
				BrowseFileServer(field);
				return false;
			});

			// image field browser
			$('.browseimage', el).click(function() {
				var field = $(this).prev('input').attr('id');
				BrowseServer(field);
				return false;
			});
		},
		// open edit box
		editRow: function(mode, selector) {
			var _this = this;

			if (selector && mode === 'edit') {
				var lineValue = _this.fieldTable.fnGetData(selector);
				$.each(lineValue, function(key, value) {
					var fieldInput = $('#' + _this.tvid + key + '_mtv');
					if (fieldInput.hasClass('image')) {
						_this.setThumbnail(value, fieldInput.attr('name'), _this.fieldEditArea);
					}
					fieldInput.setValue(value);
				});
			} else {
				$('.formtabradio:first', _this.fieldEditForm).addClass('active').find('input[type="radio"]').attr('checked', 'checked');
			}
			$('.mode', _this.fieldEditForm).hide();
			$('.mode.' + mode, _this.fieldEditForm).show();
			$('.editformtabs', _this.fieldEditForm).easytabs({
				defaultTab: 'li:first-child',
				animate: false
			}).bind('easytabs:after', function() {
				_this.editBox.colorbox.resize();
				$('.formtabradio input[type="radio"]', _this.fieldEditForm).attr('checked', false);
				$('.formtabradio.active input[type="radio"]', _this.fieldEditForm).attr('checked', 'checked');
			});
			$('.formtabradio input[type="radio"]', _this.fieldEditForm).click(function() {
				$(this).siblings('a').click();
			});
			$.colorbox({
				inline: true,
				href: '#' + _this.tvid + 'editform',
				width: '640px',
				close: '',
				open: true,
				opacity: '0.35',
				initialWidth: '0',
				initialHeight: '0',
				overlayClose: false,
				scrolling: false,
				onComplete: function() {
					if (!_this.fieldEditArea.children('form').length) {
						_this.fieldEditArea.wrapInner('<form/>');
					}
					if (lineValue && lineValue.fieldTab) {
						$('.editformtabs', _this.fieldEditArea).easytabs('select', '#' + _this.tvid + 'tab_radio_' + lineValue.fieldTab);
						$('.formtabradio input[type="radio"]', _this.fieldEditArea).attr('checked', false);
						$('.formtabradio.active input[type="radio"]', _this.fieldEditArea).attr('checked', 'checked');
					}
					$('.tabEditor', _this.fieldEditArea).each(function() {
						var editorId = $(this).attr('id');
						tinyMCE.execCommand('mceAddControl', false, editorId);
						tinyMCE.DOM.setStyle(tinyMCE.DOM.get(editorId + '_ifr'), 'height', '200px');
						tinyMCE.DOM.setStyle(tinyMCE.DOM.get(editorId + '_tbl'), 'height', 'auto');
						tinyMCE.DOM.setStyle(tinyMCE.DOM.get(editorId + '_ifr'), 'width', '100%');
						tinyMCE.DOM.setStyle(tinyMCE.DOM.get(editorId + '_tbl'), 'width', '100%');
					});
					_this.editBox.colorbox.resize();
				},
				onCleanup: function() {
					_this.clearInputs(_this.fieldEditArea);
				}
			});
		},
		// save/append edit box
		saveRow: function(mode) {
			var _this = this;

			if (typeof tinyMCE != 'undefined') {
				tinyMCE.triggerSave();
			}
			var values = new Object();
			var saveTab = $('[name^="' + _this.tvid + 'tab_radio_mtv"]', _this.fieldEditForm).getValue();
			values.fieldTab = (saveTab !== '') ? saveTab : '';
			$(':input', _this.fieldEditArea).each(function(i) {
				if ($(this).attr('name')) {
					var key = $(this).attr('name').replace(/tv\d+(.*)_mtv/, '$1');
					if (key !== '') {
						values[key] = $(this).val();
					}
				}
			});
			$.ajax({
				url: "../assets/tvs/multitv/multitv.connector.php",
				data: {
					action: 'preparevalue',
					id: $('form#mutate [name="id"]').val(),
					tvid: _this.tvid,
					value: $.toJSON(values)
				},
				dataType: 'json',
				type: 'POST',
				success: function(answer) {
					answer = $.parseJSON(answer.msg);
					values = answer.fieldValue[0];
					if (mode === 'edit') {
						var selected = $('.row_selected', _this.fieldTable)[0];
						var lineValue = _this.fieldTable.fnGetData(selected);
						values.MTV_RowId = lineValue.MTV_RowId;
						values.DT_RowId = lineValue.DT_RowId;
						_this.fieldTable.fnUpdate(values, selected);
					} else {
						values.MTV_RowId = _this.fieldTable.fnGetData().length + 1;
						values.DT_RowId = _this.tvid + (_this.fieldTable.fnGetData().length + 1);
						_this.fieldTable.fnAddData(values);
					}
					_this.clearInputs(_this.fieldEditArea);
					_this.saveMultiValue();
					_this.editBox.colorbox.close();
					return false;
				},
				error: function(answer) {
					alert(answer.msg);
					return false;
				}
			});
		},
		// remove row
		removeRow: function(selector) {
			var _this = this;

			$(selector).removeClass('row_selected');
			$('a', _this.tableButtonEdit).addClass('disabled');
			$('a', _this.tableButtonRemove).addClass('disabled');
			_this.fieldTable.fnDeleteRow(selector);
			_this.saveMultiValue();
		},
		// toggle row

		toggleRow: function(row) {
			var _this = this;

			if (!$(row).hasClass('toggle')) {
				$(row).addClass('toggle').click(function() {
					if ($(this).hasClass('row_selected')) {
						$(this).removeClass('row_selected');
						$('a', _this.tableButtonEdit).addClass('disabled');
						$('a', _this.tableButtonRemove).addClass('disabled');
					}
					else {
						_this.fieldTable.$('tr.row_selected').removeClass('row_selected');
						$(this).addClass('row_selected');
						$('a', _this.tableButtonEdit).removeClass('disabled');
						$('a', _this.tableButtonRemove).removeClass('disabled');
					}
				});
			}
		},
		// context menu
		contextMenu: function(row, id) {
			var _this = this;

			if (!$(row).hasClass('context')) {
				$(row).addClass('context').contextMenu('context-menu-' + id, {
					tableEdit: {
						click: function(element) {
							_this.fieldTable.$('tr.row_selected').removeClass('row_selected');
							$(element[0]).addClass('row_selected');
							$('a', _this.tableButtonEdit).removeClass('disabled');
							$('a', _this.tableButtonRemove).removeClass('disabled');
							_this.editRow('edit', element[0]);
						},
						link: _this.tableEdit
					},
					tableAppend: {
						click: function(element) {
							_this.editRow('append', element[0]);
						},
						link: _this.tableAppend
					},
					tableRemove: {
						click: function(element) {
							_this.removeRow(element[0]);
						},
						link: _this.tableRemove
					}
				});
			}
		}
	};

	// The actual plugin
	$.fn[pluginName] = function(options) {
		var args = arguments;
		if (options === undefined || typeof options === 'object') {
			return this.each(function() {
				if (!$.data(this, 'plugin_' + pluginName)) {
					$.data(this, 'plugin_' + pluginName, new Plugin(this, options));
				}
			});
		} else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
			var returns;
			this.each(function() {
				var instance = $.data(this, 'plugin_' + pluginName);
				if (instance instanceof Plugin && typeof instance[options] === 'function') {
					returns = instance[options].apply(instance, Array.prototype.slice.call(args, 1));
				}
				if (options === 'destroy') {
					$.data(this, 'plugin_' + pluginName, null);
				}
			});
			return returns !== undefined ? returns : this;
		}
	};
}(jQuery, window, document));
