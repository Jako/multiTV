#Template Variable

All options for a custom template variable are set in a PHP Array or JSON config file in the folder *configs* with the same name as the template variable (otherwise the default config is used) and *.config.inc.php* or *config.json* as extension (a JSON file is used in priority to PHP Array file).

## Display mode

The display mode of the input fields in the multi field list could be set in the key `display` to *horizontal* (events example), *vertical* (images example), *single*, *datatable* (links or multicontent example) or *dbtable* (dbtabledemo example). A multiTV with single display configuration contains only one list element. With a multiTV in *dbtable* mode a (custom) table in the MODX database could be displayed and edited.

## Fields

The fields of the multitv could be defined in the key `fields`. This key contains an array of fieldnames and each fieldname contains an array of field properties.

Property | Description | Default
-------- | ----------- | -------
caption | Caption (horizontal) or label (vertical) for the input | -
type | Type of the input (could be set to almost all MODX input types[^1] and `thumb` for thumbnail display of image tvs[^2]) | text
elements | Same options as in the *input option values* of a [MODX template variable](http://rtfm.modx.com/evolution/1.0/developers-guide/template-variables/creating-a-template-variable) are possible i.e. for a dropdown with all documents in the MODX root: ``@SELECT `pagetitle`, `id` FROM `modx_site_content` WHERE parent = 0 ORDER BY `menuindex` ASC`` | -
default | Default value for the input. This value could contain calculated parts. There are two placeholders available: `{i}` contains an autoincremented index `{alias}` contains the alias of the edited document. | -
thumbof | Name of an image input. A thumbnail of the selected image will be rendered into this area | -
width | Width of the input | 100

[^1]: Supported MODX input types: text, rawtext, email, number, textareamini, textarea, rawtextarea, htmlarea, date, dropdown, listbox, listbox-multiple, checkbox, option, image, file
[^2]: See [images config](https://github.com/Jako/multiTV/blob/master/assets/tvs/multitv/configs/images.config.inc.php) for thumb

In datatable mode a layer will be displayed during adding/editing one row. In this editing layer the MODX input type richtext is possible.

## Columns

In *datatable* and *dbtable* mode the visible columns for the datatable could be defined in the key `columns`. This key contains an array of column settings. Each column setting contains an array of properties. If a property is not set, the field property in key `fields` is used.

Property | Description | Default
-------- | ----------- | -------
fieldname | **(required)** Fieldname that is displayed in this column | -
caption | Caption of the column | Caption for fieldname in `fields`
width | Width of the column | Width for fieldname in `fields`
render | Enable rengering of the column content with this PHx capable string | -
sortable | Enable sorting for this column by clicking on the column header in *datatable* or *dbtable* mode. Only active if sorting is disabled in [other options](#other-options) | true

## Editing Layer

In *datatable* and *dbtable* mode the content of the editing layer could be defined in the key `form`. This key contains an array of form tab settings.

Property | Description | Default
-------- | ----------- | -------
caption | **(required)** Caption for the form tab | -
content | **(required)** Associative array of field settings | -

Each form tab setting contains an associative array of field properties (the key contains the fieldname in `fields`). If a field property is not set, the field property in `fields` is used.

Property | Description | Default
-------- | ----------- | -------
caption | Caption for the input | Caption for fieldname in `fields`

## Default Output Templates

The default output templates for the multiTV snippet could be defined in the key `templates`.

Property | Description | Default
---- | ----------- | -------
rowTpl | Default row template chunk for the snippet output. Could be changed in snippet call. See [snippet documentation](/multiTV/snippet.html) for possible placeholders | -
outerTpl | Default outer template chunk for the snippet output. Could be changed in snippet call. See [snippet documentation](/multiTV/snippet.html) for possible placeholders | -

##   Other options

The other options for one multiTV could be defined in the key `configuration`.

Property | Description | Default
---- | ----------- | -------
csvseparator | Column separator for csv clipboard table data. The csv clipboard table data should contain a new line for each row. | ,
displayLength | Number of entries displayed by default in *datatable* and *dbtable* mode. | 10
displayLengthMenu | Entries in the 'number of entries' selector in *datatable* and *dbtable* mode. | 10,25,50,100
enablePaste | multiTV could contain *paste table data* link that displays a paste box. In this box you could paste Word/HTML table clipboard data, Google Docs table clipboard data and csv data. | true
enableClear | multiTV could contain *clear all* link that clears the content of the multiTV | true
hideHeader | Hide the table header in *datatable* and *dbtable* mode. | false
radioTabs | Tabs in the datatable editing layer are displayed as radio buttons. The button state is saved in *fieldTab* key of each multiTV row. | false
sortindex | Field that contains the sort index (to enable draggable sorting in the module or *dbtable* mode) | -
sorting | Enable sorting by clicking on the column header in *datatable* or *dbtable* mode. Row reordering by drag & drop will be disabled. | false

See the [multidemo config](https://github.com/Jako/multiTV/blob/master/assets/tvs/multitv/configs/multidemo.config.inc.php) for all usable vertical settings and the [multicontent config](https://github.com/Jako/multiTV/blob/master/assets/tvs/multitv/configs/multicontent.config.inc.php) for all usable datatable settings.

<!-- Piwik -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//piwik.partout.info/";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', 12]);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript><p><img src="//piwik.partout.info/piwik.php?idsite=12" style="border:0;" alt="" /></p></noscript>
<!-- End Piwik Code -->
