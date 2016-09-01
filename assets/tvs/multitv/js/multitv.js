(function ($, window, document, undefined) {

    var pluginName = 'transformField';
    var defaults = {
        mode: '',
        fieldsettings: '',
        language: '',
        kcfinder: true,
        thumbs: '.thumbs/',
        mtvpath: 'assets/tvs/multitv/'
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
        this.data = {};
        this.fieldHeading = $('#' + this.tvid + 'heading');
        this.fieldNames = this.options.fieldsettings.fieldnames;
        this.fieldTypes = this.options.fieldsettings.fieldtypes;
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
        this.init();
    }

    // Separate functionality from object creation
    Plugin.prototype = {
        init: function () {
            var _this = this;

            if (!_this.$el.hasClass('transformed')) {
                // reset all event
                $('a', _this.fieldClear).click(function (e) {
                    e.preventDefault();
                    _this.clear();
                });

                // start edit event
                $('a', _this.fieldEdit).click(function (e) {
                    e.preventDefault();
                    _this.edit();
                });

                // paste box
                _this.pasteBox = $('a', _this.fieldPaste).click(function (e) {
                    e.preventDefault();
                    $.colorbox({
                        inline: true,
                        href: $(this).attr('href'),
                        width: '500px',
                        height: '350px',
                        onComplete: function () {
                            if ($('input:radio[name=pasteas]:checked', _this.fieldPasteForm).length === 0) {
                                $('input:radio[name=pasteas]:first', _this.fieldPasteForm).prop('checked', true);
                            }
                        },
                        onClosed: function () {
                            _this.fieldPasteArea.html('');
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
                $('.cancel', _this.fieldPasteForm).click(function () {
                    _this.pasteBox.colorbox.close();
                    return false;
                });

                // save pasted form
                $('.replace, .append', _this.fieldPasteForm).click(function () {
                    _this.paste($(this).attr('class'), $('input:radio[name=pasteas]:checked', _this.fieldPasteForm).val());
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

                    // sortable
                    if (_this.options.mode !== 'single') {
                        _this.fieldList.sortable({
                            stop: function () {
                                _this.saveMultiValue();
                            },
                            axis: 'y',
                            helper: 'clone'
                        });
                    }
                    _this.prefillInputs(_this.data.value);
                    _this.addElementEvents(_this.fieldListElement);
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
        duplicateElement: function (el, count) {
            var _this = this;

            var clone = el.clone(true).hide();
            $('[id]', clone).each(function () {
                $(this).attr('id', $(this).attr('id') + (count));
            });
            $('[name]', clone).each(function () {
                $(this).attr('name', $(this).attr('name') + (count));
            });
            _this.addElementEvents(clone);

            // clear inputs/textarea
            var inputs = $(':input', clone);
            inputs.each(function () {
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
                        $(this).prop('checked', false);
                        break;
                    default:
                        $(this).val('');
                }
            });
            return clone;
        },
        saveMultiValue: function () {
            var _this = this;

            _this.data.value = [];
            _this.fieldList.children('li').each(function () {
                var multiElement = $(this);
                var fieldValues = {};
                $.each(_this.fieldNames, function () {
                    var fieldInput = $('[name^="' + _this.tvid + this + '_mtv"][type!="hidden"]', multiElement);
                    fieldValues[this] = fieldInput.getValue().replace('&quot;', '"');
                    if (fieldInput.hasClass('mtvImage')) {
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
                _this.data.value.push(fieldValues);
            });
            _this.$el.setValue($.toJSON({
                fieldValue: _this.data.value,
                fieldSettings: _this.data.settings
            }));
        },
        prepareMultiValue: function () {
            var _this = this;

            var jsonValue = $.evalJSON(_this.$el.val().replace(/&#x005B;/g, '[').replace(/&#x005D;/g, ']').replace(/&#x007B;/g, '{').replace(/&#x007B;/g, '}'));
            if (jsonValue) {
                if (jsonValue.constructor === Array) {
                    _this.data.value = jsonValue;
                    if (!_this.data.settings) {
                        _this.data.settings = {};
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
        addElementEvents: function (el) {
            var _this = this;

            // datepicker
            $('.mtvDatePicker', el).click(function () {
                $.extend(datepickerOptions, {
                    changeMonth: true,
                    changeYear: true,
                    dateFormat: 'dd-mm-yy',
                    timeFormat: 'HH:mm:ss',
                    showTimepicker: true
                });
                var picker = $(this).datetimepicker(datepickerOptions);
                picker.datetimepicker('show');
            });
            // file field browser
            $('.browsefile', el).click(function (e) {
                e.preventDefault();
                var field = $(this).prev('input').attr('id');
                BrowseFileServer(field);
            });

            // image field browser
            $('.browseimage', el).click(function (e) {
                e.preventDefault();
                var field = $(this).prev('input').attr('id');
                BrowseServer(field);
            });
            // add element
            $('.copy', el).click(function (e) {
                e.preventDefault();
                var clone = _this.duplicateElement(_this.fieldListElementEmpty, _this.fieldListCounter);
                $(this).parents('.element').after(clone);
                clone.show('fast', function () {
                    $(this).removeAttr('style');
                });
                _this.saveMultiValue();
                _this.fieldListCounter++;
            });
            // remove element
            $('.remove', el).click(function (e) {
                e.preventDefault();
                if ($('.element', _this.fieldList).length > 1) {
                    $(this).parents('.element').hide('fast', function () {
                        $(this).remove();
                        _this.saveMultiValue();
                    });
                } else {
                    // clear inputs/textarea if only one element is present
                    var inputs = $('[name]', $(this).parent());
                    inputs.each(function () {
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
                                $(this).prop('checked', false);
                                break;
                            default:
                                $(this).val('');
                        }
                    });
                    $('.mtvThumb', $(this).parent()).html('');
                }
            });
            // change field
            $('[name]', el).bind('change keyup', function (e) {
                e.preventDefault();
                _this.saveMultiValue();
            });
        },
        setThumbnail: function (path, name, el) {
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
        prefillInputs: function () {
            var _this = this;

            if (_this.data.value) {
                if (_this.options.mode === 'single') {
                    _this.data.value = [_this.data.value[0]];
                }
                $.each(_this.data.value, function () {
                    var values = this;
                    if (_this.fieldListCounter === 1) {
                        $.each(values, function (key, value) {
                            var fieldName = (typeof key === 'number') ? _this.fieldNames[key] : key;
                            var fieldInput = $('[name^="' + _this.tvid + fieldName + '_mtv"][type!="hidden"]', _this.fieldListElement);
                            fieldInput.setValue(values[key]);
                            if (fieldInput.hasClass('mtvImage')) {
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
                        $.each(values, function (key, value) {
                            var fieldName = (typeof key === 'number') ? _this.fieldNames[key] : key;
                            var fieldInput = $('[name^="' + _this.tvid + fieldName + '_mtv"][type!="hidden"]', clone);
                            fieldInput.setValue(values[key]);
                            if (fieldInput.hasClass('mtvImage')) {
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
        clear: function () {
            if (confirm(this.options.language.confirmclear)) {
                this.fieldList.children('li').remove();
                this.$el.val('');
                this.fieldClear.hide();
                this.fieldPaste.hide();
                this.fieldHeading.hide();
                this.fieldEdit.show();
            }
            return false;
        },
        edit: function () {
            var clone = this.fieldListElementEmpty.clone(true);
            this.fieldList.children('li').remove();
            this.fieldList.append(clone);
            this.fieldList.show();
            this.fieldClear.show();
            this.fieldPaste.show();
            this.fieldHeading.show();
            this.fieldEdit.hide();
            // sortable
            this.fieldList.sortable({
                stop: function () {
                    this.saveMultiValue();
                },
                axis: 'y',
                helper: 'clone'
            });
            this.addElementEvents(clone);
            return false;
        },
        paste: function (mode, pasteas) {
            var _this = this;

            var pastedArray = [];
            var clean;
            switch (pasteas) {
                case 'google':
                    clean = _this.fieldPasteArea.htmlClean({
                        allowedTags: ['div', 'span']
                    });
                    $('div', clean).each(function () {
                        // assign html div content field values
                        var pastedRow = {};
                        var tableData = $(this).html().split('<span></span>');
                        if (tableData.length > 0) {
                            var i = 0;
                            $.each(tableData, function () {
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
                    clean = _this.fieldPasteArea.htmlClean({
                        allowedTags: ['br', 'p', 'div']
                    }).html();
                    clean = clean.replace(/<(br|p|div)?>/g, '\n').replace(/<[^>]+>/g, '').split('\n');
                    $.each(clean, function (index, value) {
                        // skip empty lines
                        if (value == '') {
                            return;
                        }
                        // CSV Parser credit goes to Brian Huisman, from his blog entry entitled "CSV String to Array in JavaScript": http://www.greywyvern.com/?post=258
                        for (var tableData = value.split(_this.options.fieldsettings.csvseparator), x = tableData.length - 1, tl; x >= 0; x--) {
                            if (tableData[x].replace(/"\s+$/, '"').charAt(tableData[x].length - 1) === '"') {
                                if ((tl = tableData[x].replace(/^\s+"/, '"')).length > 1 && tl.charAt(0) === '"') {
                                    tableData[x] = tableData[x].replace(/^\s*"|"\s*$/g, '').replace(/""/g, '"');
                                } else if (x) {
                                    tableData.splice(x - 1, 2, [tableData[x - 1], tableData[x]].join(_this.options.fieldsettings.csvseparator));
                                } else
                                    tableData = tableData.shift().split(_this.options.fieldsettings.csvseparator).concat(tableData);
                            } else
                                tableData[x].replace(/""/g, '"');
                        }
                        // assign csv row to field values
                        var pastedRow = {};
                        if (tableData.length > 0) {
                            var i = 0;
                            $.each(tableData, function () {
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
                    $('tr', $(clean)).each(function () {
                        // assign html table row field values
                        var pastedRow = {};
                        var tableData = $('td', $(this));
                        if (tableData.length > 0) {
                            var i = 0;
                            $.each(tableData, function () {
                                if (_this.fieldTypes[i] === 'thumb') {
                                    pastedRow[_this.fieldNames[i]] = '';
                                    i++;
                                }
                                pastedRow[_this.fieldNames[i]] = $(this).html();
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
            } else {
                _this.data.value = pastedArray;
            }
        }
    };

    // The actual plugin
    $.fn[pluginName] = function (options) {
        var args = arguments;
        if (options === undefined || typeof options === 'object') {
            return this.each(function () {
                if (!$.data(this, 'plugin_' + pluginName)) {
                    $.data(this, 'plugin_' + pluginName, new Plugin(this, options));
                }
            });
        } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
            var returns;
            this.each(function () {
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

(function ($, window, document, undefined) {

    var pluginName = 'transformDatatable';
    var defaults = {
        mode: '',
        fieldsettings: '',
        language: '',
        kcfinder: true,
        thumbs: '.thumbs/',
        mtvpath: 'assets/tvs/multitv/'
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
        this.data = {};
        this.fieldHeading = $('#' + this.tvid + 'heading');
        this.fieldNames = this.options.fieldsettings.fieldnames;
        this.fieldTypes = this.options.fieldsettings.fieldtypes;
        this.fieldTable = $('#' + this.tvid + 'table');
        this.fieldEdit = $('#' + this.tvid + 'edit');
        this.fieldClear = $('#' + this.tvid + 'clear');
        this.fieldPaste = $('#' + this.tvid + 'paste');
        this.fieldEditForm = $('#' + this.tvid + 'editform');
        this.fieldEditArea = $('#' + this.tvid + 'editarea');
        this.tableAppend = '<img alt="' + this.options.language.append + ' " src="../' + this.options.mtvpath + 'css/images/add.png" /> ' + this.options.language.append;
        this.tableEdit = '<img alt="' + this.options.language.edit + ' " src="../' + this.options.mtvpath + 'css/images/application_form_edit.png" /> ' + this.options.language.edit;
        this.tableRemove = '<img alt="' + this.options.language.remove + ' " src="../' + this.options.mtvpath + 'css/images/delete.png" /> ' + this.options.language.remove;
        this.tableDuplicate = '<img alt="' + this.options.language.duplicate + ' " src="../' + this.options.mtvpath + 'css/images/copy.gif" /> ' + this.options.language.duplicate;
        this.tableButtons = $('<ul>').addClass('actionButtons');
        this.tableButtonAppend = $('<li>').attr('id', this.tvid + 'tableAppend').append($('<a>').attr('href', '#').html(this.tableAppend));
        this.tableButtonEdit = $('<li>').attr('id', this.tvid + 'tableEdit').append($('<a>').attr('href', '#').addClass('disabled').html(this.tableEdit));
        this.tableButtonRemove = $('<li>').attr('id', this.tvid + 'tableRemove').append($('<a>').attr('href', '#').addClass('disabled').html(this.tableRemove));
        this.tableButtonDuplicate = $('<li>').attr('id', this.tvid + 'tableDuplicate').append($('<a>').attr('href', '#').addClass('disabled').html(this.tableDuplicate));
        this.tableClasses = this.options.fieldsettings.tableClasses;
        this.radioTabs = this.options.fieldsettings.radioTabs;
        this.editBox = '';

        this.init();
    }

    // Separate functionality from object creation
    Plugin.prototype = {
        init: function () {
            var _this = this;

            // transform the input
            if (_this.$el.val() !== '@INHERIT') {
                if (!_this.$el.hasClass('transformed')) {
                    _this.prepareMultiColumns();
                    _this.prepareMultiValue();
                    _this.$el.hide();
                    _this.fieldEdit.hide();
                    if (_this.options.mode != 'dbtable') {
                        _this.fieldTable.dataTable({
                            sDom: '<"clear">lfrtip',
                            aaData: _this.data.value,
                            aoColumns: _this.options.fieldsettings.fieldcolumns,
                            bAutoWidth: false,
                            iDisplayLength: _this.options.fieldsettings.displayLength,
                            aLengthMenu: [
                                _this.options.fieldsettings.displayLengthMenu,
                                _this.options.fieldsettings.displayLengthMenutext
                            ],
                            oLanguage: dataTableLanguage,
                            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                                _this.contextMenu(nRow, iDisplayIndex);
                                _this.toggleRow(nRow);
                            }
                        }).addClass(_this.tableClasses);
                    } else {
                        _this.fieldTable.dataTable({
                            sDom: '<"clear">lfrtip',
                            bProcessing: true,
                            bServerSide: true,
                            bLengthChange: true,
                            iDisplayLength: _this.options.fieldsettings.displayLength,
                            aLengthMenu: [
                                _this.options.fieldsettings.displayLengthMenu,
                                _this.options.fieldsettings.displayLengthMenutext
                            ],
                            sAjaxSource: '../' + _this.options.mtvpath + 'multitv.connector.php',
                            fnServerData: function (sSource, aoData, fnCallback, oSettings) {
                                aoData.push(
                                    {name: 'mode', value: 'dbtable'},
                                    {name: 'action', value: 'loadtable'},
                                    {name: 'config', value: _this.options.fieldsettings.fieldconfig},
                                    {name: 'configtype', value: _this.options.fieldsettings.fieldconfigtype},
                                    {name: 'mtvpath', value: _this.options.mtvpath}
                                );
                                oSettings.jqXHR = $.ajax({
                                    dataType: 'json',
                                    type: 'POST',
                                    url: sSource,
                                    data: aoData,
                                    success: fnCallback
                                });
                            },
                            aoColumns: _this.options.fieldsettings.fieldcolumns,
                            bAutoWidth: false,
                            oLanguage: dataTableLanguage,
                            fnRowCallback: function (nRow, aData, iDisplayIndex) {
                                _this.contextMenu(nRow, iDisplayIndex);
                                _this.toggleRow(nRow);
                            }
                        }).addClass(_this.tableClasses);
                    }

                    if (!_this.options.fieldsettings.sorting) {
                        if (_this.options.mode != 'dbtable') {
                            _this.fieldTable.rowReordering({
                                fnAfterMove: function () {
                                    _this.saveMultiValue();
                                    _this.fieldTable.fnDraw();
                                }
                            });
                        } else {
                            if (_this.options.fieldsettings.sortindex != '') {
                                _this.fieldTable.rowReordering({
                                    iIndexColumn: 2,
                                    sURL: '../' + _this.options.mtvpath + 'multitv.connector.php',
                                    sData: {
                                        mode: 'dbtable',
                                        action: 'sorttable',
                                        config: _this.options.fieldsettings.fieldconfig,
                                        configtype: _this.options.fieldsettings.fieldconfigtype,
                                        mtvpath: _this.options.mtvpath
                                    },
                                    fnAfterMove: function () {
                                        _this.fieldTable.fnDraw();
                                    }
                                });
                            }
                        }
                    }

                    // buttons above datatable
                    _this.fieldTable.parent().prepend(_this.tableButtons);
                    _this.tableButtons.append(_this.tableButtonAppend, _this.tableButtonRemove, _this.tableButtonEdit, _this.tableButtonDuplicate);

                    // remove row event
                    $('a', _this.tableButtonRemove).click(function (e) {
                        e.preventDefault();
                        if ($(this).hasClass('disabled')) {
                            return false;
                        }
                        _this.removeRow($('.row_selected', _this.fieldTable)[0]);
                    });

                    // edit/append row event
                    _this.editBox = $('a', _this.tableButtonEdit.add(_this.tableButtonAppend)).click(function (e) {
                        e.preventDefault();
                        if ($(this).hasClass('disabled')) {
                            return false;
                        }
                        _this.editRow($(this).parent().attr('id').replace(/[\w\d]+table/, '').toLowerCase(), $('.row_selected', _this.fieldTable)[0]);
                    });

                    // duplicate row event
                    $('a', _this.tableButtonDuplicate).click(function (e) {
                        e.preventDefault();
                        if ($(this).hasClass('disabled')) {
                            return false;
                        }
                        _this.duplicateRow($('.row_selected', _this.fieldTable)[0]);
                    });

                    // save/append edit box
                    $('.edit,.append', _this.fieldEditForm).click(function (e) {
                        e.preventDefault();
                        _this.saveRow($(this).hasClass('edit') ? 'edit' : 'append', false);
                    });

                    // close edit box
                    $('.cancel', _this.fieldEditForm).click(function (e) {
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
        clearInputs: function (el) {
            if (typeof tinyMCE !== 'undefined') {
                $('.tabEditor', el).each(function () {
                    var editorId = $(this).attr('id');
                    if(tinyMCE.majorVersion == 4) {
                        tinyMCE.execCommand('mceRemoveEditor', false, editorId);
                    } else {
                        tinyMCE.execCommand('mceRemoveControl', false, editorId);
                    }
                });
            }
            $(':input', el).each(function () {
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
                        $(this).prop('checked', false);
                        break;
                    default:
                        $(this).val('');
                }
            });
            $('.mtvThumb', el).html('');
        },
        saveMultiValue: function () {
            var _this = this;

            function compare(a, b) {
                if (a.MTV_RowId < b.MTV_RowId)
                    return -1;
                if (a.MTV_RowId > b.MTV_RowId)
                    return 1;
                return 0;
            }

            var currentValue = _this.fieldTable.fnGetData();
            var saveValue = [];

            currentValue.sort(compare);

            $.each(currentValue, function () {
                var row = {};
                $.each(this, function (key, value) {
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
        prepareMultiColumns: function () {
            var _this = this;

            $.each(_this.options.fieldsettings.fieldcolumns, function (key, value) {
                this.sDefaultContent = '';
            });
        },
        prepareMultiValue: function () {
            var _this = this;

            if (_this.options.mode != 'dbtable') {
                var jsonValue = $.evalJSON(_this.$el.val().replace(/&#x005B;/g, '[').replace(/&#x005D;/g, ']').replace(/&#x007B;/g, '{').replace(/&#x007B;/g, '}'));
                if (jsonValue) {
                    if (jsonValue.constructor === Array) {
                        _this.data.value = jsonValue;
                        if (!_this.data.settings) {
                            _this.data.settings = {};
                        }
                        _this.data.settings.autoincrement = _this.data.value.length + 1;
                    } else {
                        _this.data.value = jsonValue.fieldValue;
                        _this.data.settings = jsonValue.fieldSettings;
                    }
                    $.each(_this.data.value, function (key, value) {
                        this.DT_RowId = _this.tvid + (key + 1);
                        this.MTV_RowId = (key + 1);
                    });
                } else {
                    _this.data.value = [];
                    _this.data.settings.autoincrement = 1;
                }
            } else {
                _this.data.value = [];
            }
        },
        setThumbnail: function (path, name, el) {
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
        addElementEvents: function (el) {
            var _this = this;

            // datepicker
            $('.mtvDatePicker', el).click(function () {
                $.extend(datepickerOptions, {
                    changeMonth: true,
                    changeYear: true,
                    dateFormat: 'dd-mm-yy',
                    timeFormat: 'HH:mm:ss',
                    showTimepicker: true
                });
                var picker = $(this).datetimepicker(datepickerOptions);
                picker.datetimepicker('show');
            });
            // file field browser
            $('.browsefile', el).click(function () {
                var field = $(this).prev('input').attr('id');
                BrowseFileServer(field);
                return false;
            });

            // image field browser
            $('.browseimage', el).click(function () {
                var field = $(this).prev('input').attr('id');
                BrowseServer(field);
                return false;
            });
            $('[name]', el).bind('change keyup', function (e) {
                e.preventDefault();
                if ($(this).hasClass('mtvImage')) {
                    _this.setThumbnail($(this).val(), $(this).attr('name'), _this.fieldEditForm);
                    _this.editBox.colorbox.resize();
                }
            });
        },
        // load edit data/create new data
        editRow: function (mode, selector) {
            var _this = this;
            var lineValue;

            if (selector && mode === 'edit') {
                var lineValue = _this.fieldTable.fnGetData(selector);
                if (_this.options.mode != 'dbtable') {
                    $.each(lineValue, function (key, value) {
                        var fieldInput = $('[name^="' + _this.tvid + key + '_mtv"][type!="hidden"]', _this.fieldEditArea);
                        fieldInput.setValue(value);
                        if (fieldInput.hasClass('mtvImage')) {
                            _this.setThumbnail(value, fieldInput.attr('name'), _this.fieldListElement);
                        }
                        if (fieldInput.hasClass('setdefault') && fieldInput.getValue() === '') {
                            fieldInput.setValue(fieldInput.attr('alt').supplant({
                                i: _this.data.settings.autoincrement,
                                alias: $('[name="alias"]').getValue()
                            }));
                            _this.data.settings.autoincrement++;
                        }
                    });
                    _this.editBoxOpen(mode);
                } else {
                    $.ajax({
                        dataType: 'json',
                        type: 'POST',
                        url: '../' + _this.options.mtvpath + 'multitv.connector.php',
                        data: {
                            mode: 'dbtable',
                            action: 'loadrecord',
                            config: _this.options.fieldsettings.fieldconfig,
                            configtype: _this.options.fieldsettings.fieldconfigtype,
                            mtvpath: _this.options.mtvpath,
                            rowId: lineValue.id
                        },
                        success: function (data) {
                            if (data) {
                                lineValue = data;
                                $.each(lineValue, function (key, value) {
                                    var fieldInput = $('[name^="' + _this.tvid + key + '_mtv"][type!="hidden"]', _this.fieldEditArea);
                                    fieldInput.setValue(value);
                                    if (fieldInput.hasClass('mtvImage')) {
                                        _this.setThumbnail(value, fieldInput.attr('name'), _this.fieldListElement);
                                    }
                                });
                            }
                            _this.editBoxOpen(mode, lineValue);
                        }
                    });
                }
            } else {
                if (_this.options.mode != 'dbtable') {
                    if (_this.options.fieldsettings.radioTabs) {
                        $('.formtabradio:first', _this.fieldEditForm).addClass('active').find('input[type="radio"]').prop('checked', true);
                    }
                    $.each(_this.fieldNames, function (index, value) {
                        var fieldInput = $('[name^="' + _this.tvid + value + '_mtv"][type!="hidden"]', _this.fieldEditArea);
                        if (fieldInput.hasClass('setdefault')) {
                            fieldInput.setValue(fieldInput.attr('alt').supplant({
                                i: _this.data.settings.autoincrement,
                                alias: $('[name="alias"]').getValue()
                            }));
                            _this.data.settings.autoincrement++;
                        }
                    });
                    _this.editBoxOpen(mode);
                } else {
                    $.ajax({
                        dataType: 'json',
                        type: 'POST',
                        url: '../' + _this.options.mtvpath + 'multitv.connector.php',
                        data: {
                            mode: 'dbtable',
                            action: 'createrecord',
                            config: _this.options.fieldsettings.fieldconfig,
                            configtype: _this.options.fieldsettings.fieldconfigtype,
                            mtvpath: _this.options.mtvpath
                        },
                        success: function (data) {
                            if (data) {
                                lineValue = data;
                                $.each(lineValue, function (key, value) {
                                    var fieldInput = $('[name^="' + _this.tvid + key + '_mtv"][type!="hidden"]', _this.fieldEditArea);
                                    fieldInput.setValue(value);
                                    if (fieldInput.hasClass('mtvImage')) {
                                        _this.setThumbnail(value, fieldInput.attr('name'), _this.fieldListElement);
                                    }
                                });
                            }
                            _this.editBoxOpen(mode, lineValue);
                        }
                    });
                }
            }
        },
        // open edit box
        editBoxOpen: function (mode, lineValue) {
            var _this = this;

            $('.mode', _this.fieldEditForm).hide();
            $('.mode.' + mode, _this.fieldEditForm).show();
            $('.editformtabs', _this.fieldEditForm).easytabs({
                defaultTab: 'li:first-child',
                animate: false
            }).bind('easytabs:after', function () {
                _this.editBox.colorbox.resize();
                $('.formtabradio:not(.active) input[type="radio"]', _this.fieldEditForm).prop('checked', false);
                $('.formtabradio.active input[type="radio"]', _this.fieldEditForm).prop('checked', true);
            });
            $('.formtabradio input[type="radio"]', _this.fieldEditForm).click(function () {
                $(this).siblings('a').click();
            });
            $.colorbox({
                inline: true,
                href: '#' + _this.tvid + 'editform',
                width: (_this.options.fieldsettings.editBoxWidth != '') ? _this.options.fieldsettings.editBoxWidth : '640px',
                close: '',
                open: true,
                opacity: '0.35',
                initialWidth: '0',
                initialHeight: '0',
                overlayClose: false,
                scrolling: false,
                onComplete: function () {
                    if (!_this.fieldEditArea.children('form').length) {
                        _this.fieldEditArea.wrapInner('<form/>');
                    }
                    if (typeof lineValue !== 'undefined' && lineValue.fieldTab) {
                        $('.editformtabs', _this.fieldEditArea).easytabs('select', '#' + _this.tvid + 'tab_radio_' + lineValue.fieldTab);
                        $('.formtabradio:not(.active) input[type="radio"]', _this.fieldEditArea).prop('checked', false);
                        $('.formtabradio.active input[type="radio"]', _this.fieldEditArea).prop('checked', true);
                    }
                    if (typeof tinyMCE !== 'undefined') {
                        $('.tabEditor', _this.fieldEditArea).each(function () {
                            var editorId = $(this).attr('id');
                            if(tinyMCE.majorVersion == 4) {
                                if(modxRTEbridge_tinymce4 != undefined) {
                                    var configObj = window[modxRTEbridge_tinymce4.default];
                                    configObj['selector'] = '#'+editorId;
                                    tinyMCE.init(configObj);
                                } else {
                                    tinyMCE.execCommand('mceAddEditor', false, editorId);
                                }
                            } else {
                                tinyMCE.execCommand('mceAddControl', false, editorId);
                            }
                            tinyMCE.DOM.setStyle(tinyMCE.DOM.get(editorId + '_ifr'), 'height', '200px');
                            tinyMCE.DOM.setStyle(tinyMCE.DOM.get(editorId + '_tbl'), 'height', 'auto');
                            tinyMCE.DOM.setStyle(tinyMCE.DOM.get(editorId + '_ifr'), 'width', '100%');
                            tinyMCE.DOM.setStyle(tinyMCE.DOM.get(editorId + '_tbl'), 'width', '100%');
                        });
                    }
                    setTimeout(function () {
                        _this.editBox.colorbox.resize();
                    }, 250)
                },
                onCleanup: function () {
                    _this.clearInputs(_this.fieldEditArea);
                }
            });
        },

        // save/append edit box
        saveRow: function (mode, values) {
            var _this = this;

            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.triggerSave();
            }
            if(typeof values === 'undefined') {
                values = {};
                var saveTab = $('[name^="' + _this.tvid + 'tab_radio_mtv"]', _this.fieldEditForm).getValue();
                values.fieldTab = (saveTab !== '') ? saveTab : '';
                $.each(_this.fieldNames, function () {
                    var fieldInput = $('[name^="' + _this.tvid + this + '_mtv"][type!="hidden"]', _this.fieldEditForm);
                    values[this] = fieldInput.getValue();
                    if (fieldInput.hasClass('mtvImage')) {
                        _this.setThumbnail(values[this], fieldInput.attr('name'), _this.fieldEditForm);
                    }
                });
            };

            if (_this.options.mode != 'dbtable') {
                if ($('form#mutate [name="id"]').val()) {
                    $.ajax({
                        url: '../' + _this.options.mtvpath + 'multitv.connector.php',
                        data: {
                            action: 'preparevalue',
                            id: $('form#mutate [name="id"]').val(),
                            tvid: _this.tvid,
                            value: $.toJSON(values),
                            mtvpath: _this.options.mtvpath
                        },
                        dataType: 'json',
                        type: 'POST',
                        success: function (answer) {
                            if (answer.error) {
                                alert(answer.msg);
                                return false;
                            }
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
                        error: function (answer) {
                            alert(this.options.language.notprepared);
                            return false;
                        }
                    });
                } else {
                    alert(this.options.language.noidprepare);
                    return false;
                }
            } else {
                var lineId = false;
                if (mode === 'edit') {
                    var lineValue = _this.fieldTable.fnGetData($('.row_selected', _this.fieldTable)[0]);
                    lineId = lineValue.id;
                }
                $.ajax({
                    url: '../' + _this.options.mtvpath + 'multitv.connector.php',
                    data: {
                        mode: 'dbtable',
                        action: 'saverecord',
                        config: _this.options.fieldsettings.fieldconfig,
                        configtype: _this.options.fieldsettings.fieldconfigtype,
                        mtvpath: _this.options.mtvpath,
                        rowId: lineId,
                        value: $.toJSON(values)
                    },
                    dataType: 'json',
                    type: 'POST',
                    success: function (answer) {
                        _this.clearInputs(_this.fieldEditArea);
                        _this.fieldTable.fnDraw();
                        _this.editBox.colorbox.close();
                        return false;
                    },
                    error: function (answer) {
                        alert(answer.msg);
                        return false;
                    }
                });
            }
        },
        // remove row
        removeRow: function (selector) {
            var _this = this;

            $(selector).removeClass('row_selected');
            $('a', _this.tableButtonEdit).addClass('disabled');
            $('a', _this.tableButtonRemove).addClass('disabled');
            $('a', _this.tableButtonDuplicate).addClass('disabled');
            if (_this.options.mode != 'dbtable') {
                _this.fieldTable.fnDeleteRow(selector);
                _this.saveMultiValue();
            } else {
                var lineValue = _this.fieldTable.fnGetData(selector);
                var lineId = lineValue.id;
                $.ajax({
                    url: '../' + _this.options.mtvpath + 'multitv.connector.php',
                    data: {
                        mode: 'dbtable',
                        action: 'deleterecord',
                        config: _this.options.fieldsettings.fieldconfig,
                        configtype: _this.options.fieldsettings.fieldconfigtype,
                        mtvpath: _this.options.mtvpath,
                        rowId: lineId
                    },
                    dataType: 'json',
                    type: 'POST',
                    success: function (answer) {
                        _this.fieldTable.fnDraw();
                        return false;
                    },
                    error: function (answer) {
                        alert(answer.msg);
                        return false;
                    }
                });
            }
        },
        // duplicate row
        duplicateRow: function (selector) {
            var _this = this;

            if (_this.options.mode != 'dbtable') {
                var lineValue = _this.fieldTable.fnGetData(selector);
                _this.saveRow('append', lineValue);
            }
        },
        // toggle row
        toggleRow: function (row) {
            var _this = this;

            if (!$(row).hasClass('toggle')) {
                $(row).addClass('toggle').click(function () {
                    if ($(this).hasClass('row_selected')) {
                        $(this).removeClass('row_selected');
                        $('a', _this.tableButtonEdit).addClass('disabled');
                        $('a', _this.tableButtonRemove).addClass('disabled');
                        $('a', _this.tableButtonDuplicate).addClass('disabled');
                    }
                    else {
                        _this.fieldTable.$('tr.row_selected').removeClass('row_selected');
                        $(this).addClass('row_selected');
                        $('a', _this.tableButtonEdit).removeClass('disabled');
                        $('a', _this.tableButtonRemove).removeClass('disabled');
                        $('a', _this.tableButtonDuplicate).removeClass('disabled');
                    }
                });
            }
        },
        // context menu
        contextMenu: function (row, id) {
            var _this = this;

            if (!$(row).hasClass('context')) {
                $(row).addClass('context').contextMenu('context-menu-' + id, {
                    tableEdit: {
                        click: function (element) {
                            _this.fieldTable.$('tr.row_selected').removeClass('row_selected');
                            $(element[0]).addClass('row_selected');
                            $('a', _this.tableButtonEdit).removeClass('disabled');
                            $('a', _this.tableButtonRemove).removeClass('disabled');
                            $('a', _this.tableButtonDuplicate).removeClass('disabled');
                            _this.editRow('edit', element[0]);
                        },
                        link: _this.tableEdit
                    },
                    tableAppend: {
                        click: function (element) {
                            _this.editRow('append', element[0]);
                        },
                        link: _this.tableAppend
                    },
                    tableDuplicate: {
                        click: function (element) {
                            _this.duplicateRow(element[0]);
                        },
                        link: _this.tableDuplicate
                    },
                    tableRemove: {
                        click: function (element) {
                            _this.removeRow(element[0]);
                        },
                        link: _this.tableRemove
                    }
                });
            }
        }
    };

    // The actual plugin
    $.fn[pluginName] = function (options) {
        var args = arguments;
        if (options === undefined || typeof options === 'object') {
            return this.each(function () {
                if (!$.data(this, 'plugin_' + pluginName)) {
                    $.data(this, 'plugin_' + pluginName, new Plugin(this, options));
                }
            });
        } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
            var returns;
            this.each(function () {
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
