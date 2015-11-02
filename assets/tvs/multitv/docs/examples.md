#Examples

##Sortable Sidebar

**Requirements**

- Config: [sidebar config](https://github.com/Jako/multiTV/blob/master/assets/tvs/multitv/configs/sidebar.config.inc.php)
- Template Variable: sidebar
- Extras: [Quill](http://modx.com/extras/package/quill)

**Usage**

```
[!Ditto?
&documents=`[[multiTV? &tvName=`sidebar`]]`
&outputSeparator=`,`
&extenders=`@FILE assets/tvs/multitv/dittoExtender/customsort.extender.inc.php`
&tpl=`…`!]
```

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
