# Installation

There are two possibilities to install multiTV in MODX Evolution

- Use the MODX Evolution [Package Manager](https://github.com/Jako/PackageManager) and install the [latest multiTV package](https://github.com/Jako/multiTV/archive/master.zip)
- Install it on your own:
    1. Upload the folder *assets/tvs/multitv* in the corresponding folder in your installation.
    2. Create a new template variable with imput type *custom input* (if you name this template variable *multidemo* it will use the already uploaded multidemo config file)
    3. Insert the following code into the *input option values*
```@INCLUDE/assets/tvs/multitv/multitv.customtv.php```
    4. Create a new snippet called multiTV with the following snippet code
```<?php return include(MODX_BASE_PATH.'assets/tvs/multitv/multitv.snippet.php'); ?>```

## Compatibility patches

4. If you want to modify the multiTV with ManagerManager[^1] *before MODX version 1.0.9* you have to patch the file mm.inc.php and insert
```case 'custom_tv':``` in line 136 just before the line
```$t = 'textarea';```
5. If you want to use multiTV with YAMS you have to patch yams.plugin.inc.php according to the instructions on this [issue comment](https://github.com/Jako/multiTV/issues/9#issuecomment-6992127).
6. If you are updating from 1.4.10 and below you could install the updateTV snippet and modify the data in your multiTVs to the new format. Since the custom tv and the snippet code supports the old and new format, this is only nessesary, if you want to add/remove columns in your multiTVs or if you want to sort the output by a column.
7. If you want to use PHx with multiTV you have to modify the PHx plugin code a bit:

```
if (!class_exists('PHxParser')) {
    include MODX_BASE_PATH . "assets/plugins/phx/phx.parser.class.inc.php";
}
$e = &$modx->Event;
switch($e->name) {
    case 'OnParseDocument':
        $PHx = new PHxParser($phxdebug,$phxmaxpass);
        $PHx->OnParseDocument();
        break;
}
```

[^1]: ManagerManager expects a custom tv field to be an input tag. Because of single and double quote issues the field containing the multiTV value is a textarea.

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
