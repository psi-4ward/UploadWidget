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

		$this->loadLanguageFile('UploadWidget');

		// check if the file exists
		if($this->varValue && !is_file(TL_ROOT.DIRECTORY_SEPARATOR. $this->varValue))
		{
			\System::log("UploadWidget [{$this->strTable}.{$this->id} ID:{$this->activeRecord->id}] could not find file {$this->varValue}", 'UploadWidget::generate()', 'ERROR');
			$this->varValue = '';
		}

		$tpl = new \BackendTemplate('widget_UploadWidget');
		$tpl->id = $this->activeRecord->id;
		$tpl->label = $this->strLabel;
		$tpl->fld = $this->id;
		$tpl->tbl = $this->strTable;
		$tpl->value = $this->varValue;
		$tpl->errors = $this->getErrorAsHTML();
		$tpl->md5AsFilename = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->id]['eval']['md5AsFilename'];


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
		$this->varValue = $varValue;

		// delete old file
		if($this->activeRecord->{$this->strField} != $varValue && is_file(TL_ROOT.DIRECTORY_SEPARATOR.$this->activeRecord->{$this->strField}))
		{
			unlink(TL_ROOT.DIRECTORY_SEPARATOR.$this->activeRecord->{$this->strField});
		}

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
			$this->addError($GLOBALS['TL_LANG']['UploadWidget']['fileNotFound']);
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

		// support placeholders
		if(preg_match_all("~\{\{([a-z-9]+)\}\}~i", $path, $erg))
		{
			foreach(array_unique($erg[1]) as $fld)
			{
				$path = str_replace('{{'.$fld.'}}', $this->activeRecord->$fld, $path);
			}
		}

		// create path if it doesnt exists
		if(!is_dir(TL_ROOT.DIRECTORY_SEPARATOR.$path))
		{
			mkdir(TL_ROOT.DIRECTORY_SEPARATOR.$path,0770,true);
		}

		$filename = substr($varValue,strrpos($varValue,DIRECTORY_SEPARATOR)+1);
		$targetFile = $path.$filename;
		if(!$this->overwrite && is_file(TL_ROOT.DIRECTORY_SEPARATOR.$targetFile))
		{
			// alter the filename if theres an existing file with the same name
			$f = substr($targetFile,0,strrpos($targetFile,'.'));
			$ext = substr($targetFile,strrpos($targetFile,'.')+1);
			$i=0;
			do {
				$i++;
				$targetFile = $f.'_'.$i.'.'.$ext;
			} while(is_file( TL_ROOT.DIRECTORY_SEPARATOR.$targetFile));
		}

		rename(TL_ROOT.DIRECTORY_SEPARATOR.$varValue, TL_ROOT.DIRECTORY_SEPARATOR.$targetFile);

		// remove temp file directory
		rmdir(substr(TL_ROOT.DIRECTORY_SEPARATOR.$varValue,0,strrpos(TL_ROOT.DIRECTORY_SEPARATOR.$varValue,'/')));

		$this->varValue = $targetFile;

		return true;
	}
}
