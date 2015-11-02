// Multi Input Plugin
// Author: Thomas Jakobi <thomas.jakobi@partout.info>
;
(function($, window, document, undefined) {

	var pluginName = 'multiInput',
			defaults = {
				input: false,
				clearInputs: 1,
				limit: 3,
				limitMessage: 'limit reached',
				separator: '\n',
				inputseparator: '||',
				onElementAdd: null,
				json: false
			};

	// Easy Responsive Tabs
	function Plugin(el, options) {
		// Extending options
		this.options = $.extend({}, defaults, options);

		// Private
		this._defaults = defaults;
		this._name = pluginName;
		this.$el = $(el);
		this.element = (this.options.element) ? this.options.element : $(el);
		this.elementId = this.element.attr('id');
		this.elementInput = (this.options.input) ? (this.options.input) : $('<input>').attr({name: this.elementId + 'Input', id: this.elementId + 'Input', type: 'text'});
		this.elementInputs = null;
		this.elementCounter = 1;
		this.addLink = $('<a>').addClass('add').attr('href', '#').text('Hinzufügen');
		this.removeLink = $('<a>').addClass('remove').attr('href', '#').text('Entfernen');

		this.init();
	}

	// Separate functionality from object creation
	Plugin.prototype = {
		/**
		 * Initialize multiInput plugin
		 * @return {Object} this The plugin object
		 */
		init: function() {
			var _this = this;
			if (_this.element.length) {
				_this.elementInputs = _this.fillElementsValues(_this.element);
				_this.element.hide().before(_this.elementInputs);
			}
			return this;
		},
		/**
		 * Clear all inputs of an element
		 * @param {jQuery} el The element to clear
		 */
		clearInputs: function(el) {
			var _this = this;
			$(':input', el).each(function() {
				switch ($(this).attr('type')) {
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
			_this.saveElementsValues();
		},
		/**
		 * Create one element
		 * @param {jQuery} el The element to create
		 * @param {Number} suffix The new id suffix
		 * @param {string/json} data The value for the element to create
		 * @return {jQuery} The new created element
		 */
		createElement: function(el, suffix, data) {
			var _this = this;
			var clone = el.clone(false);
			var cloneWrap = $('<div>').addClass('inputWrapper').hide().append(clone);
			$('[id]', cloneWrap).each(function() {
				$(this).attr('id', $(this).attr('id') + (suffix));
			});
			$('[name]', cloneWrap).each(function() {
				$(this).attr('name', $(this).attr('name') + (suffix));
			});

			_this.clearInputs(cloneWrap);
			if (!_this.options.json) {
				var values = data.split(_this.options.inputseparator);
			} else {
				values = data;
			}
			var inputs = $(':input', cloneWrap);
			if (values.length) {
				for (var k = 0; k < values.length; k++) {
					$(inputs[k]).val(values[k]);
				}
			}
			var add = _this.addLink.clone(false).click(function(e) {
				e.preventDefault();
				var clone = _this.createElement(_this.elementInput, _this.elementCounter, '');
				$(this).parents('.inputWrapper').after(clone);
				clone.show('fast', function() {
					$(this).removeAttr('style');
				});
				_this.addElementEvents(clone);
				_this.saveElementsValues();
				_this.elementCounter++;
			});
			var remove = _this.removeLink.clone(false).click(function(e) {
				e.preventDefault();
				if ($('.inputWrapper', _this.elementInputs).length > 1) {
					$(this).parents('.inputWrapper').hide('fast', function() {
						$(this).remove();
						_this.saveElementsValues();
					});
				} else {
					_this.clearInputs($(this).parent());
				}
			});
			cloneWrap.append(add).append(remove);
			return cloneWrap;
		},
		/**
		 * Fill elements with values
		 * @param {jQuery} el The element containing the values for the elements
		 * @return {jQuery} The elements
		 */
		fillElementsValues: function(el) {
			var _this = this;
			var id = el.attr('id');
			if (_this.options.json) {
				var values = (el.html()) ? JSON.parse(el.html()) : [];
			} else {
				var values = el.html().replace(/[\s\r\n]+$/, '').split(_this.options.separator);
			}
			var required = (el.hasClass('required')) ? 'required' : '';
			var inputs = $('<div>').attr('id', id + 'Inputs').addClass('multiInput');
			if (values.length) {
				for (var k = 0; k < values.length; k++) {
					var input = _this.createElement(_this.elementInput, k, values[k]).addClass(id + 'Input').addClass(required).show();
					inputs.append(input);
					_this.addElementEvents(input);
					_this.elementCounter++;
				}
			} else {
				var input = _this.createElement(_this.elementInput, 0, '').addClass(id + 'Input').show();
				inputs.append(input);
				_this.addElementEvents(input);
				_this.elementCounter++;
			}
			return (inputs);
		},
		/**
		 * Add events to an element
		 * @param {jQuery} el The element to add events to
		 */
		addElementEvents: function(el) {
			var _this = this;
			$('[name]', el).bind('change keyup', function() {
				_this.saveElementsValues();
				return false;
			});
			if (typeof _this.options.onElementAdd === 'function') {
				_this.options.onElementAdd(el);
			}

		},
		/**
		 * Save elements values
		 */
		saveElementsValues: function() {
			var _this = this;
			if (_this.elementInputs) {
				var elements = _this.elementInputs.children('.inputWrapper');
				var data = [];
				elements.each(function() {
					var inputs = $(':input', $(this));
					var values = [];
					$.each(inputs, function() {
						values.push($(this).val());
					});
					if (_this.options.json) {
						data.push(values);
					} else {
						data.push(values.join(_this.options.inputseparator));
					}
				});
				if (_this.options.json) {
					_this.element.text(JSON.stringify(data).replace(/\[\[/, '[ [').replace(/\]\]/, '] ]'));
				} else {
					_this.element.text(data.join(_this.options.separator));
				}
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
