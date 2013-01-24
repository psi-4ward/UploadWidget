<?php

/**
 * @copyright 4ward.media 2013 <http://www.4wardmedia.de>
 * @author Christoph Wiechert <wio@psitrax.de>
 */


// Register the namespace
ClassLoader::addNamespace('Psi');

// Register the classes
ClassLoader::addClasses(array
(
	'Psi\UploadWidget\Widget' 			=> 'system/modules/UploadWidget/classes/Widget.php',
	'Psi\UploadWidget\Upload' 			=> 'system/modules/UploadWidget/classes/Upload.php',
	'Psi\UploadWidget\qqFileUploader' 	=> 'system/modules/UploadWidget/classes/qqFileUploader.php',
));

// Register the templates
TemplateLoader::addFiles(array
(
	'widget_UploadWidget' 				=> 'system/modules/UploadWidget/templates',
));
