# Module

The multiTV Database Manager is a MODX Evolution module providing almost the same options as a multiTV Template Variable in datatable mode. The main difference is the direct access of (custom) tables in the MODX Evolution database.

With the Database Manager you could manage (CRUD) your own database tables and use MODX input types for managing table row fields.

## Configuration

Each Database Manager configuration file will create a tab in a Database Manager module if it is referenced in the module configuration. The value of *Configurations* has to be filled by a comma separated list of config names in *assets/tvs/multitv/moduleconfigs* (i.e. event_log for referencing the event_log.moduleconfig.json)

Each configuration file contains a JSON encoded array with the following settings:

### Table

The database table could be set in the key `table`. The table name is prefixed by the MODX table prefix.

### Caption

The Database Manager module tab text and tab caption could be set in the key `caption`.

### Processors

The processors folder could be set in the key `processors`. If it is not set, the default processors in *assets/tvs/multitv/processors* are used. If it is set, the processors are retrieved from a subfolder of *assets/tvs/multitv/processors* named as the key value. If that subfolder does not exist, the value points to a subfolder of the MODX base path.

There are five processors usable at the moment. 

Processor | Description
-------- | -----------
loadtable | Loads the current table with limit and offset into the datatable
loadrecord | Loads a table row into the editing layer
createrecord | Creates a new table row for the editing layer
deleterecord | Deletes a table row
saverecord | Saves the values of the editing layer into a table row

### Fields

The fields of the database table could be defined in the key `fields`. This key contains an array of fieldnames and each fieldname contains an array of field properties.

Property | Description | Default
-------- | ----------- | -------
caption | Caption (horizontal) or label (vertical) for the input | -
type | Type of the input (could be set to almost all MODX input types[^1], `thumb` for thumbnail display of image tvs[^2]) and `unixtime` to convert the datetime table data to unixtime and vice versa | text
elements | [Same options](http://rtfm.modx.com/evolution/1.0/developers-guide/template-variables/creating-a-template-variable) as in the *input option values* of a MODX template variable are possible i.e. for a dropdown with all documents in the MODX root: ``@SELECT `pagetitle`, `id` FROM `modx_site_content` WHERE parent = 0 ORDER BY `menuindex` ASC`` | -
default | Default value for the input. This value could contain calculated parts. There are two placeholders available: `{i}` contains an autoincremented index `{alias}` contains the alias of the edited document. | -
thumbof | Name of an image input. A thumbnail of the selected image will be rendered into this area | -
width | Width of the input | 100

[^1]: Supported MODX input types: text, rawtext, email, number, textareamini, textarea, rawtextarea, htmlarea, date, dropdown, listbox, listbox-multiple, checkbox, option, image, file
[^2]: See [images config](https://github.com/Jako/multiTV/blob/master/assets/tvs/multitv/configs/images.config.inc.php) for thumb

During adding/editing one row a layer is displayed. In this editing layer the MODX input type richtext is not usable at the moment (degrades to textarea).

### Columns

The visible columns for the datatable could be defined in the key `columns`. This key contains an array of column settings. Each column setting contains an array of properties. If a property is not set, the field property in key `fields` is used.

Property | Description | Default
-------- | ----------- | -------
fieldname | **(required)** Fieldname that is displayed in this column | -
caption | Caption of the column | Caption for fieldname in `fields`
width | Width of the column | Width for fieldname in `fields`
render | Enable rengering of the column content with this PHx capable string | -

### Editing Layer

The content of the editing layer during adding/editing one row could be defined in the key `form`. This key contains an array of form tab settings. 

Property | Description | Default
-------- | ----------- | -------
caption | **(required)** Caption for the form tab | -
content | **(required)** Associative array of field settings | -

Each form tab setting contains an associative array of field properties (the key contains the fieldname in `fields`). If a field property is not set, the field property in `fields` is used.

Property | Description | Default
-------- | ----------- | -------
caption | Caption for the input | Caption for fieldname in `fields`

### Buttons

Own buttons for the Database Manager module tab could be defined in the key `buttons`. This key contains an associative array of button group configs.

Property | Description | Default
-------- | ----------- | -------
position | **(required)** Position of the button group (could be `topleft`, `topright`, `bottomleft` or `bottom right`)| -
buttons | **(required)** Associative array of button configs | -

Each button config contains an associative array of button settings.

Property | Description | Default
-------- | ----------- | -------
caption | Caption for the button | -
icon | Icon for the button located in *assets/tvs/multitv/css/images* | -
processor | name of the processor file located in *assets/tvs/multitv/processors/[groupkey]* (groupkey contains the group config key)| -
form | Array of form tab settings (see [Editing layer](#editing-layer)) | -

For every button a javascript file will be included with the following path *assets/tvs/multitv/buttons/[groupkey]/[buttonkey].button.js* (groupkey contains the button group config key, buttonkey contains the button config key)

Example for a generate coupons button with one group config key *coupons* and one button config key *generate*. The button section could contain several group configs and each one several button configs.

    "buttons": {
        "coupons": {
            "position": "topright",
            "buttons": {
                "generate": {
                    "caption": "Generate",
                    "icon": "wand.png",
                    "processor": "generate",
                    "form": [
                        {
                            "caption": "Coupon",
                            "content": {
                                "count": {},
                                "discount": {},
                                "validuser": {},
                                "validgroup": {},
                                "validuntil": {},
                                "maxuse": {}
                            }
                        }
                    ]
                }
            }
        }
    },
    
The [event_log module config](https://github.com/Jako/multiTV/blob/master/assets/tvs/multitv/moduleconfigs/event_log.moduleconfig.json)) contains a simpler example for a csvexport button.

### Other options

The other options for one multiTV could be defined in the key `configuration`.

Property | Description | Default
---- | ----------- | -------
radioTabs | Tabs in the datatable editing layer are displayed as radio buttons. The button state is saved in *fieldTab* column of each table row (this column has to exist). | false
sorting | Enable sorting by column header. | false
sortindex | Column name that ist used as sorting index. The column type has to be integer and it should contain an autoincremented index (see in [Fields](#fields)) as default value. | false
css | Reference custom CSS files in a comma separated list. | -
scripts | Reference custom JS files in a comma separated list. | -

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
