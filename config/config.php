<?php

/**
 * @copyright 4ward.media 2013 <http://www.4wardmedia.de>
 * @author Christoph Wiechert <wio@psitrax.de>
 */

$GLOBALS['BE_FFL']['UploadWidget'] = 'Psi\UploadWidget\Widget';

$GLOBALS['TL_HOOKS']['executePostActions'][] = array('Psi\UploadWidget\Upload', 'ajaxHandler');

// register onDelete callbacks to cascade unlink of files
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Psi\UploadWidget\Upload', 'registerOnDeleteCallback');
