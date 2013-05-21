UploadWidget
======================
**Contao 3 Widget for easy uplading files with drag&drop support**


Features
----------------------
* Independent Widget, no need to use the fileTree or the database driven filesystem.
* Upload via drag&drop if the browser supports
* Use the ID of the current element in the path
* Images displayed as thumbnails
* Uploaded file gets stored not before the user presses the `save` or `save and close` button

**Planed features**
* upload of multiple files
* sync with DB-FS
* cascade deleting of files when the related database-row gets deleted

Installation and Usage
--------------------
Just copy all files in `system/modules/UploadWidget`

**DCA-Field example**
```php
'myImage' => array
(
	'label'						=> &$GLOBALS['TL_LANG']['tl_table']['myImage'],
	'exclude'					=> true,
	'inputType'					=> 'UploadWidget',
	'eval'						=> array
	(
		'path'			=> 'files/customImages/{{id}}/myImage',
		'extensions'	=> 'png,jpg,gif',
        'overwrite'		=> false,
		'mandatory'		=> true
	),
	'sql'						=> "varchar(255) NOT NULL default ''"
),
```

### Supported eval-parameters

<table border="1">
	<tr>
    	<th width="100" align="left">path</th>
        <td>The path to store the files. <i>Use {{id}} to reference the ID of the current record.</i></td>
    </tr>
    <tr>
    	<th align="left">extensions</th>
        <td>Comma sperated list of valid file-extensions</td>
    </tr>
    <tr>
    	<th align="left">overwrite</th>
        <td>boolean, default: <i>false</i>. Set to <i>true</i> to overwrite existing files with the same name</td>
    </tr>
    <tr>
    	<th align="left">md5AsFilename</th>
        <td>boolean, default: <i>false</i>. Set to <i>true</i> to rename the file to its md5 hash</td>
    </tr>

</table>


Credits, Licence
----------------------

* Uses [Valums Fineuploader](https://github.com/valums/file-uploader).
* Licence: LGPL
* by [4ward.media](http://www.4wardmedia.de)

