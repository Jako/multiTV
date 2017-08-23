# Snippet

The multiTV snippet has to be called to display the content of a multiTV template variable. Call the snippet like this (most expample parameters are using the default values in this example call and could be removed from the call – parameter tvName is required)

```
[!multiTV?
&tvName=`yourMultiTVname`
&docid=`[*id*]`
&tplConfig=``
&outerTpl=`@CODE:<ul>((wrapper))</ul>`
&rowTpl=`@CODE:<li>((event)), ((location)), ((price))</li>`
&display=`5`
&offset=`0`
&rows=`all`
&where=``
&randomize=`0`
&reverse=`0`
&orderBy=``
&toPlaceholder=``
&toJson=`0`
&published=`1`
&emptyOutput=`1`
&noResults=``
&outputSeparator=``
&firstClass=`first`
&lastClass=`last`
&evenClass=``
&oddClass=``
&paginate=`0`
&offsetKey=`page`
!]
```

## Parameters

Name | Description | Default value
---- | ----------- | -------------
tvName | **(required)** Name of the template variable that contains the multiTV (the column names of the mulitTV are received from the config file) | -
docid | Document id where the custom tv is retreived from (i.e. if the multiTV Snippet is called in a Ditto template) | Current document id
tplConfig | Array key in the config file that contains the output templates configuration (will be prefixed with `templates`) | ''
outerTpl | Outer template: chunkname, filename (value starts with `@FILE`) or code (value starts with `@CODE` – placeholders have to be masked by `((` and `))`. Usable [placeholders](#placeholder-in-outertpl). [^1] | `@CODE:<select name="$tvName">[+wrapper+]</select>` or custom template in template variable config file
rowTpl | Row template: chunkname, filename (value starts with `@FILE`) or code (value starts with `@CODE` – placeholders have to be masked by `((` and `))`. Usable [placeholders](#placeholder-in-rowtpl). [^1] | `@CODE:<option value="[+value+]">[+key+]</option>` or custom template in template variable config file
display | Count of rows that are displayed, `all` for all rows | 5
offset | Count of rows from start that are not displayed | 0
rows | Comma separated list of row numbers (or all rows) that should be displayed | all
where | JSON encoded array of where clauses to filter the results. Example [clauses](#placeholder-in-rowtpl). | -
randomize | Random order of displayed rows (disables `reverse` and `orderBy` parameter) | 0
reverse | Reverse order of displayed rows (disables `orderBy` parameter) | 0
orderBy | Column name, column order type and order direction to sort the output (format: `name:type direction` – type could be `text` or `date`, defaults to `text` – direction defaults to `asc`) | -
toPlaceholder | The snippet output is assigned to a placeholder named as the parameter value (i.e. [+myPlaceholder+]), single items are assigned to placeholders named as the parameter value followed by the row number (i.e. [+myPlaceholder.1+]). Normal snippet output is suppressed.[^2] | -
toJson | The snippet output contains the json encoded result of the multitv snippet call. Useful to retreive the multiTV results other snippets by runSnippet | 0
published | Display only multiTVs of published (1), unpublished (0) or both (2) kind of documents | 1
emptyOutput | Return empty string if the multiTV is empty, otherwise return outer template | 1
noResults | No results template: chunkname, filename (value starts with `@FILE`) or code (value starts with `@CODE`) | -
outputSeparator | String inserted between two row templates | empty
firstClass | Content of row.class placeholder in the first row | first
lastClass | Content of row.class placeholder in the last row | last
evenClass | Content of row.class placeholder in an even row | -
oddClass | Content of row.class placeholder in an odd row | -
paginate | Show pagination | 0
offsetKey | Pagination offset parameter key | page

The default templates for outer template and row template could be defined in the config file for the custom template variable. These custom definitions could be overwritten by `rowTpl` and `outerTpl` in snippet call. Both template chunks are parsed by PHx (chunkie class).

### Where clause examples

The *where* parameter could be set with an JSON encoded array of where clauses. Each where clause has to use the following format: `{"fieldname:operator":"value"}` *fieldname* is the name of a multiTV field, *operator* is the comparing operator, *value* is the value the fieldname is compared with. Possible operators are `=`, `!=`, `>`, `<`, `>=`, `<=`, `LIKE NOT`, `LIKE`. The default operator is `=`.  

``&where=`{"price":"2000"}` `` will filter all rows where the mulitTV field price is not 2000.

``&where=`{"city:LIKE":"London"}` `` will filter all rows where the mulitTV field city does not contain London.

Multiple where clauses are combined with `AND` i.e. ``&where=`{"city:LIKE":"London","price":"2000"}` `` will filter all rows where the mulitTV field city does not contain London and where the mulitTV field price is not 2000.

## Placeholder

### Placeholder in rowTpl

Name | Description
---- | -----------
"fieldname" | Each fieldname defined in config file could be used
"property" | Each snippet property in snippet call could be used
iteration | Contains the iteration of the current multiTV element
row.number | Contains the row number of the current multiTV element
row.class | FirstClass parameter setting for first displayed row, lastClass parameter setting for last displayed row, evenClass/oddClass parameter setting for even/odd rows.
row.total | Contains the count of all displayable rows
docid | Value of docid parameter or current document id

### Placeholder in outerTpl

Name | Description
---- | -----------
wrapper | Contains the output of all rows
"property" | Each snippet property in snippet call could be used
rows.offset | Contains the count of rows from start that are not displayed
rows.total | Contains the count of all displayable rows
docid | Value of docid parameter or current document id
pagination | Contains the pagination (if parameter pagination is enabled)

[^1]: Older MODX versions don’t like =, ? and & in snippet parameters. If the template code has to use those signs, put the template code in a chunk or change the default templates in the config file.
[^2]: If the snippet output is assigned to placeholder and PHx is installed, the page should be set to uncached and the Snippet should be called cached. Otherwise PHx will 'steal' the placeholders before the Snippet could fill them.

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
