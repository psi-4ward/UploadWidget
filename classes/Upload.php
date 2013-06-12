<?php

/**
 * @copyright 4ward.media 2013 <http://www.4wardmedia.de>
 * @author Christoph Wiechert <wio@psitrax.de>
 */
 
namespace Psi\UploadWidget;

/**
 * Helper class for the Upload Widget
 * handles fileuploads through AJAX and manages the deletion of files
 */
class Upload extends \System
{

	/**
	 * Handle the ajax upload
	 *
	 * @param $strAction
	 * @param \DataContainer $dc
	 */
	public function ajaxHandler($strAction, \DataContainer $dc)
	{
		if($strAction == 'UploadWidget')
		{
			header("Content-Type: text/plain");

			$this->loadLanguageFile('UploadWidget');

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
			$uploadName = $targetTmpDir.DIRECTORY_SEPARATOR.$objUploader->getUploadName();

			// some more validation
			if(\Input::post('fld') && !$result['error'])
			{
				$err = $this->getValidationErrors($GLOBALS['TL_DCA'][$dc->table]['fields'][\Input::post('fld')]['eval'], $uploadName);
				if($err)
				{
					echo json_encode(array('error'=>$err));
					unlink(TL_ROOT.DIRECTORY_SEPARATOR.$uploadName);
					return;
				}
			}


			// rewrite filename to md5-hash if md5AsFilename option is set
			if($GLOBALS['TL_DCA'][$dc->table]['fields'][\Input::post('fld')]['eval']['md5AsFilename'] && !$result['error'])
			{
				$md5 = md5_file(TL_ROOT.DIRECTORY_SEPARATOR.$uploadName);
				$newFileName = $targetTmpDir.DIRECTORY_SEPARATOR.$md5.substr($uploadName,strrpos($uploadName,'.'));
				rename(TL_ROOT.DIRECTORY_SEPARATOR.$uploadName, TL_ROOT.DIRECTORY_SEPARATOR.$newFileName);
				$uploadName = $newFileName;
			}

			$result['uploadName'] = $uploadName;

			if(in_array(substr($result['uploadName'],-3),array('jpg','jpeg','png','gif')) &&  !$result['error'])
			{
				$result['img'] = '<img src="'.\Image::get($result['uploadName'],100,100).'" alt="">';
			}

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


	/**
	 * Validate the $file against the eval-array
	 * returns a string with an error message or false if the validation passes
	 *
	 * @param $arrEval
	 * @param $file
	 * @return bool|string
	 */
	protected function getValidationErrors($arrEval, $file)
	{
		if($arrEval['exactSize'])
		{
			$imgSize = getimagesize(TL_ROOT.DIRECTORY_SEPARATOR.$file);
			if($imgSize === false)
			{
				return $GLOBALS['TL_LANG']['UploadWidget']['notAnImage'];
			}
			if($imgSize[0] != $arrEval['exactSize'][0] || $imgSize[1] != $arrEval['exactSize'][1])
			{
				return sprintf($GLOBALS['TL_LANG']['UploadWidget']['notExpectedSize'], $arrEval['exactSize'][0],$arrEval['exactSize'][1]);
			}
		}
		return false;
	}


	/**
	 * Callback to register onDelete_Callback for tables
	 * using the UploadWidget
	 *
	 * @param $strTable
	 */
	public function registerOnDeleteCallback($strTable)
	{
		if(!is_array($GLOBALS['TL_DCA'][$strTable]['fields'])) return;
		foreach($GLOBALS['TL_DCA'][$strTable]['fields'] as $fld => $data)
		{
			if($data['inputType'] == 'UploadWidget')
			{
				$GLOBALS['TL_DCA'][$strTable]['config']['ondelete_callback'][] = array('Psi\UploadWidget\Upload', 'deleteFiles');
				return;
			}
		}
	}


	/**
	 * Callback to delete files when the record gets deleted
	 *
	 * @param \DataContainer $dc
	 */
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

					// try to remove empty folders
					$dir = dirname($file);
					while(@rmdir($dir))
					{
						$dir = substr($dir,0,strrpos($dir,DIRECTORY_SEPARATOR));

						// never delete files folder
						if($dir == TL_ROOT.DIRECTORY_SEPARATOR.'files') break;
					}
				}
			}
		}
	}
}