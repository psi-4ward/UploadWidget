<?php

/**
 * @copyright 4ward.media 2013 <http://www.4wardmedia.de>
 * @author Christoph Wiechert <wio@psitrax.de>
 */
 
namespace Psi\UploadWidget;

class Upload extends \System
{

	public function ajaxHandler($strAction, \DataContainer $dc)
	{
		if($strAction == 'UploadWidget')
		{
			$objUploader = new qqFileUploader();

			// if theres a field we could use the DCA attributes
			if(\Input::post('fld'))
			{
				// set allowed extensions
				$objUploader->allowedExtensions = explode(',',$GLOBALS['TL_DCA'][$dc->table]['fields'][\Input::post('fld')]['eval']['extensions']) ?: array();
			}

			// get unique temp target directory
			do {
				$targetTmpDir = 'system'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'UploadWidget'.DIRECTORY_SEPARATOR.mt_rand(0,99999);
			} while(is_dir(TL_ROOT.DIRECTORY_SEPARATOR.$targetTmpDir));

			mkdir(TL_ROOT.DIRECTORY_SEPARATOR.$targetTmpDir,0770,true);

			// Call handleUpload() with the name of the folder, relative to PHP's getcwd()
			$result = $objUploader->handleUpload(TL_ROOT.DIRECTORY_SEPARATOR.$targetTmpDir);

			$result['uploadName'] = $targetTmpDir.DIRECTORY_SEPARATOR.$objUploader->getUploadName();

			if(in_array(substr($result['uploadName'],-3),array('jpg','jpeg','png','gif')))
			{
				$result['img'] = '<img src="'.\Image::get($result['uploadName'],100,100).'" alt="">';
			}

			header("Content-Type: text/plain");
			echo json_encode($result);
		}

		if($strAction == 'UploadWidget_delete')
		{
			$file = \Input::post('file');
			if(substr($file,0,10) == 'system'.DIRECTORY_SEPARATOR.'tmp' && is_file(TL_ROOT.DIRECTORY_SEPARATOR.$file))
			{
				unlink(TL_ROOT.DIRECTORY_SEPARATOR.$file);
			}

			// files from files/ directory are deleted through the DCA
		}
	}


	public function registerOnDeleteCallback($strTable)
	{
		foreach($GLOBALS['TL_DCA'][$strTable]['fields'] as $fld => $data)
		{
			if($data['inputType'] == 'UploadWidget')
			{
				$GLOBALS['TL_DCA'][$strTable]['config']['ondelete_callback'][] = array('Psi\UploadWidget\Upload', 'deleteFiles');
				return;
			}
		}
	}


	public function deleteFiles(\DataContainer $dc)
	{
		foreach($GLOBALS['TL_DCA'][$dc->table]['fields'] as $fld => $data)
		{
			if($data['inputType'] == 'UploadWidget' && !$data['eval']['doNotDelete'])
			{
				$file = TL_ROOT.DIRECTORY_SEPARATOR.$dc->activeRecord->{$fld};
				if(is_file($file))
				{
					unlink($file);
				}
			}
		}
	}
}