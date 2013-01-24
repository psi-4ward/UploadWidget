<?php

/**
 * @copyright 4ward.media 2013 <http://www.4wardmedia.de>
 * @author Christoph Wiechert <wio@psitrax.de>
 */


namespace Psi\UploadWidget;

class Widget extends \Widget
{

	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;


	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		// Load javascript and CSS
		$GLOBALS['TL_JAVASCRIPT']['fineuploader'] = 'system/modules/UploadWidget/assets/js/fineuploader_src.js';
		$GLOBALS['TL_CSS']['fineuploader'] = 'system/modules/UploadWidget/assets/css/fineuploader.css';

		$tpl = new \BackendTemplate('widget_UploadWidget');
		$tpl->id = $this->activeRecord->id;
		$tpl->fld = $this->id;
		$tpl->tbl = $this->strTable;
		$tpl->value = $this->varValue;
		$tpl->errors = $this->getErrorAsHTML();


		return $tpl->parse();
	}


	/**
	 * Parse the template file and return it as string
	 *
	 * @param array $arrAttributes An optional attributes array
	 * @return string The template markup
	 */
	public function parse($arrAttributes=null)
	{
		$this->addAttributes($arrAttributes);

		return $this->generate();
	}


	/**
	 * Validate the user input and set the value
	 * copy the file fromt system/tmp/UploadWidget to the specified folder
	 */
	public function validate()
	{
		$varValue = deserialize($this->getPost($this->strName));

		// run contaos validator
		if(!parent::validator($varValue))
		{
			// delete the uploaded file if theres an error
			if(!empty($varValue) && is_file(TL_ROOT.DIRECTORY_SEPARATOR.$varValue))
			{
				unlink(TL_ROOT.DIRECTORY_SEPARATOR.$varValue);
			}
			return false;
		}

		// theres no upload, no existing file and the field is not mandatory
		if(empty($varValue))
		{
			// this is not a new-uploaded file
			return true;
		}

		// check if the file exists
		if(!is_file(TL_ROOT.DIRECTORY_SEPARATOR.$varValue))
		{
			$this->addError('File not found!');
			return false;
		}

		// the file exists in the filesystem and is not in system/tmp/UploadWidget, so its NOT a new uploaded one
		if(strpos($varValue, 'system'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'UploadWidget') === false)
		{
			return true;
		}

		$path = $this->path ?: 'files';
		if(substr($path,-1) != DIRECTORY_SEPARATOR)
		{
			$path .= DIRECTORY_SEPARATOR;
		}

		// support current id in path
		$path = str_replace('{{id}}', $this->activeRecord->id, $path);

		// create path if it doesnt exists
		if(!is_dir(TL_ROOT.DIRECTORY_SEPARATOR.$path))
		{
			mkdir(TL_ROOT.DIRECTORY_SEPARATOR.$path,0770,true);
		}

		$filename = substr($varValue,strrpos($varValue,DIRECTORY_SEPARATOR)+1);
		$targetFile = TL_ROOT.DIRECTORY_SEPARATOR.$path.$filename;
		if(!$this->overwrite && is_file($targetFile))
		{
			// alter the filename if theres an existing file with the same name
			$f = substr($targetFile,0,strrpos($targetFile,'.'));
			$ext = substr($targetFile,strrpos($targetFile,'.')+1);
			$i=0;
			do {
				$i++;
				$targetFile = $f.'_'.$i.'.'.$ext;
			} while(is_file($targetFile));
		}

		rename(TL_ROOT.DIRECTORY_SEPARATOR.$varValue, $targetFile);
		$this->varValue = $path.$filename;
	}
}