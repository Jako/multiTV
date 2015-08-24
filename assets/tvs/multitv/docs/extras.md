##PHx modifier

Since the JSON string in multiTV starts with `[[` and ends with `]]`[^1], you *can't* check if the multiTV is empty by i.e. ```[*multittvname:ne=``:then=`not empty`*]```.

But you could to use the PHx modifier in the folder `phx-modifier` in that case. Move the two files to `assets/plugins/phx/modifiers` and call it like this ``[+phx:multitvisempty=`tvname|docid`:then=`xxx`:else=`yyy`+]`` or like this ``[+phx:multitvisnotempty=`tvname|docid`:then=`xxx`:else=`yyy`+]``. If docid is not set it defaults to current document.

##Ditto filter extender

If you want to filter displayed Ditto rows by the values of multiTV field content, you could use the Ditto multitv filter extender. As all other Ditto filters it filters the row away if the condition is true.

The extender uses the following parameters

Name | Description
---- | -----------
multiTvFilterBy | multiTV name to filter by (required)
multiTvFilterOptions | (Array of) json encoded object(s) of filter options
{:.table .table-striped .table-hover}

The following **filter options** could be used

Name | Description
---- | -----------
name | mulitTV field name that is used for filtering
type | Type of the multiTV field content (possible content: date, text)
value | The value the multiTV field content is filtered with
mode | Mode for filtering the multiTV field content
conjunction | Logical conjunction with the previous filter result (AND/OR)
{:.table .table-striped .table-hover}

The following modes could be used for **text** type:

Name | Description
---- | -----------
contains | filtered if one value contains filterValue
allcontains | filtered if all values containing filterValue
containsnot | filtered if one value not contains filterValue
allcontainsnot | filtered if all values not containing filterValue
is | filtered if one value is filterValue
allis | filtered if all values are filterValue
isnot | filtered if one value is not filterValue
allisnot | filtered if all values are not filterValue
{:.table .table-striped .table-hover}

The following modes could be used for **date** type:

Name | Description
---- | -----------
before | filtered if one value is before filterValue
beforeall | filtered if all values are before filterValue
equal | filtered if one value is equal filterValue
equalall | filtered if all values are equal filterValue
after | filtered if one value is after filterValue
afterall | filtered if one value is after filterValue
{:.table .table-striped .table-hover}

### Examples

The following example displays all documents within containers 3, 4, and 5 where the multiTV `event` values in column `title` not containing `Important` in any multiTV row.

```
[[Ditto?
&parents=`3,4,5`
&display=`all`
&tpl=`...`
&extenders=`@FILE assets/tvs/multitv/dittoExtender/multitvfilter.extender.inc.php`
&multiTvFilterBy=`event`
&multiTvFilterOptions=`[{"name":"title","type":"text","value":"Important","mode":"contains"}]`]]
]]
```

If you want to filter Ditto by several multiTV values, you ave to append an option object to the `multiTvFilterOptions`. The next example will display all documents within containers 3, 4, and 5 where the multiTV `event` values in column `title` not containing `Important` and column `location` is `Outdoor` in any multiTV row.

```
[[Ditto?
&parents=`3,4,5`
&display=`all`
&tpl=`...`
&extenders=`@FILE assets/tvs/multitv/dittoExtender/multitvfilter.extender.inc.php`
&multiTvFilterBy=`event,event`
&multiTvFilterOptions=`[{"name":"title","type":"text","value":"Important","mode":"contains"},{"name":"location","type":"text","value":"Outdoor","mode":"allisnot","conjunction":"OR"}]`]]
]]
```


##Update to the new data format

Version 1.4.11 of multiTV uses a new data format (the column names are saved as key with each value). The custom tv and the snippet code supports the old and new format, so you don't have to update your multiTVs. It is only nessesary, if you want to add/remove columns in your multiTVs.

Create a new snippet called updateTV with the following snippet code

```
<?php
return include(MODX_BASE_PATH.'assets/tvs/multitv/updatetv.snippet.php');
?>
```

Call the snippet on one (temporary) MODX document like this:

```
[!updateTV?
&tvNames=`yourMultiTVname1,yourMultiTVname2`
!]
```

##Parameters

Name | Description | Default value
---- | ----------- | -------------
tvNames | **(required)** comma separated list of template variable names that contain multiTV data | -
{:.table .table-striped .table-hover}


[^1]: The JSON string the multitv is converted to starts with `[[` and ends with `]]` so the MODX parser thinks it contains a snippet and you can't place the template variable directly in the template.

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
