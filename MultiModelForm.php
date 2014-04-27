<?php
/**
 * MultiModelForm.php
 *
 * Handling of multiple records and models in a form
 *
 * Uses the jQuery plugin RelCopy
 * @link http://www.andresvidal.com/labs/relcopy.html
 *
 * @author Joe Blocher <yii@myticket.at>
 * @copyright 2011 myticket it-solutions gmbh
 * @license New BSD License
 * @category User Interface
 * @version 6.0.0
 */
class MultiModelForm extends CWidget
{
    const CLASSPREFIX = 'mmf_'; //prefix for tag classes

    /**
     * The used relcopy js script
     *
     * @var string
     */
    public $jsRelCopy = 'jquery.relcopy.yii.6.0.js';

    /**
     * The model to handle
     *
     * @var CModel $model
     */
    public $model;

    /**
     * Configuration of the form provided by the models method getMultiModelForm()
     *
     * This configuration array defines generation CForm
     * Can be a config array or a config file that returns the configuration
     *
     * @link http://www.yiiframework.com/doc/guide/1.1/en/form.builder
     * @var mixed $elements
     */
    public $formConfig = array();

    /**
     * Array of models loaded from db.
     * Created for example by $model->findAll();
     *
     * @var CModel $data
     */
    public $data;

    /**
     * The controller returns all validated items (array of model)
     * if a validation error occurs.
     * The form will then be rendered with error output.
     * $data will be ignored in this case.
     * @see method run()
     *
     * @var array $validatedItems
     */
    public $validatedItems;

    /**
     * Set to true if the error summary should be rendered for the model of this form
     *
     * @var boolean $showErrorSummary
     */
    public $showErrorSummary = false;


    /**
     * The text of the copy/clone link
     *
     * @var string $addItemText
     */
    public $addItemText = 'Add item';

    /**
     * Show the add item link as button
     *
     * @var boolean $addItemAsButton
     */
    public $addItemAsButton = false;

    /**
     * Alert text if options['limit']>0 and the limit is reached
     * See the options property below
     * @var string
     */
    public $limitText = 'The limit is reached';

    /**
     * Show 'Add item' link and empty item in errormode
     *
     * @var boolean $allowAddOnError
     */
    public $showAddItemOnError = true;


    /**
     * If false, the addItem link and empty row will not be displayed
     * @var bool
     */
    public $allowAddItem = true;

    /**
     * If false, the removeItem will not be displayed
     * @var bool
     */
    public $allowRemoveItem = true;

    /**
     * The text for the remove link
     * Can be an image tag too.
     * Leave empty to disable removing.
     *
     * @var string $removeText
     */
    public $removeText = 'Remove';

    /**
     * The confirmation text before remove an item
     * Set to null/empty to disable confirmation
     *
     * @var string $removeText
     */
    public $removeConfirm = 'Delete this item?';

    /**
     * js code to add to onClick of the remove link
     * Added before the internal mmf onclick
     *
     * @var string $removeOnClick
     */
    public $removeOnClick='';

    /**
     * The htmlOptions for the remove link
     *
     * @var array $removeHtmlOptions
     */
    public $removeHtmlOptions = array();

    /**
     * Show elements as table
     * If set to true, $fieldsetWrapper, $rowWrapper and $removeLinkWrapper will be ignored
     *
     * @var boolean
     */
    public $tableView = false;

    /**
     * The htmlOptions for the table tag
     *
     * @var array $tableHtmlOptions
     */
    public $tableHtmlOptions = array();

    /**
     * Items are rendered as <tfoot><tr><td>Item1</td><td>Item2</td> ...</tr></tfoot>
     *
     * @var string $tableFootCells
     */
    public $tableFootCells = array();


    /**
     * Set this attribute to enable manual sorting by drag/drop of the multiple items
     * Uses the CJuiSortable widget
     *
     * @var string the name of the attribute
     */
    public $sortAttribute;


    /**
     * The options property of the zii.widgets.jui.CJuiSortable
     *
     * @link http://www.yiiframework.com/doc/api/1.1/CJuiWidget#options-detail
     * @link http://jqueryui.com/demos/sortable/
     *
     * @var array
     */
    public $sortOptions = array(
        'placeholder' => 'ui-state-highlight',
        'opacity' => 0.8,
        'cursor' => 'move'
    );
    /**
     * Render elements in bootstrap layout
     * @var bool
     */
    public $bootstrapLayout = false;

    /**
     * The wrapper for each fieldset
     *
     * @var array $fieldsetWrapper
     */
    public $fieldsetWrapper = array(
        'tag' => 'div',
        'htmlOptions' => array('class' => 'view'), //'fieldset' is unknown in the default css context of form.css
    );

    /**
     * The wrapper for a row
     *
     * @var array $rowWrapper
     */
    public $rowWrapper = array(
        'tag' => 'div',
        'htmlOptions' => array('class' => 'row'),
    );

    /**
     * The wrapper for the removeLink
     *
     * @var array $fieldsetWrapper
     */
    public $removeLinkWrapper = array(
        'tag' => 'span',
        'htmlOptions' => array(),
    );

    /**
     * Hide the empty copyTemplate, show on Add Item click
     *
     * @var bool
     */
    public $hideCopyTemplate = true;


    /**
     * Clear all inputs after cloning
     *
     * @var bool
     */
    public $clearInputs = true;

    /**
     * Set a limit on adding items
     * @var int
     */
    public $limit = 0;

    /**
     * The CForm for rendering a item row
     * Override MultiModelRenderForm for custom handling
     *
     * @var string
     */
    public $renderForm = 'MultiModelRenderForm';

    /**
     * The javascript code jsBeforeClone,jsAfterClone ...
     * This allows to handle widgets on cloning.
     * Important: 'this' is the current handled jQuery object
     * For CJuiDatePicker and extension 'datetimepicker' see prepared php-code below: afterNewIdDatePicker,afterNewIdDateTimePicker
     *
     * Usage if you have CJuiDatePicker to clone (assume your form elements are defined in the array $formConfig):
     * 'jsAfterNewId' => MultiModelForm::afterNewIdDateTimePicker($formConfig['elements']['mydatefield']),
     *
     */
    public $jsBeforeClone; // 'jsBeforeClone' => "alert(this.attr('class'));";
    public $jsAfterClone; // 'jsAfterClone' => "alert(this.attr('class'));";
    public $jsBeforeNewId; // 'jsBeforeNewId' => "alert(this.attr('id'));";
    public $jsAfterNewId; // 'jsAfterNewId' => "alert(this.attr('id'));";

    /**
     * A js function as callback after cloning
     * Params newElem,sourceElem
     *
     * Usage
     * echo CHtml::script('function alertIds(newElem,sourceElem){alert(newElem.attr("id"));alert(sourceElem.attr("id"));}');
     *
     * Set 'jsAfterCloneCallback'=>'alertIds'
     *
     * @var a js-function
     */
    public $jsAfterCloneCallback;

    /**
     * Available options for the jQuery plugin RelCopy
     *
     * string excludeSelector - A jQuery selector used to exclude an element and its children
     * integer limit - The number of allowed copies. Default: 0 is unlimited
     * string append - Additional HTML to attach at the end of each copy.
     * string copyClass - A class to attach to each copy
     * boolean clearInputs - Option to clear each copies text input fields or textarea
     *
     * @link http://www.andresvidal.com/labs/relcopy.html
     *
     * @var array $options
     */
    public $options = array();


    /**
     * Used for callbacks
     * @var
     */
    public $masterModel;

    /**
     * Replace existing files in the models fileDir
     * if false, the files with the same name will be indexed filename_x.ext
     *
     * @var bool
     */
    public $fileReplaceExisting=false;

    /**
     * The html options for the preview image tag for file attributes
     * @var array
     */
    public $fileImagePreviewHtmlOptions =  array('style' => 'max-width: 100px; max-height: 100px;');

    /**
     * The html options for the link for file attributes
     * @var array
     */
    public $fileLinkHtmlOptions = array('target'=>'_blank');

    /**
     * The assets url
     *
     * @var string $_assets
     */
    private $_assets;

    /**
     * Internal record count
     * @var integer
     */
    private $_recordCount;


    /**
     * Old file attribute values
     * @var integer
     */
    private $_oldFileAttributes;

    /**
     * The file attribute names
     * @var integer
     */
    private $_fileAttributes;


    /**
     * The CUploadedFiles array, prepared for saveAs
     *
     * @var
     */
    private $_uploadedFiles;



    /**
     * Support for CJuiDatePicker
     * Set 'jsAfterNewId'=MultiModelForm::afterNewIdDateTimePicker($myFormConfig['elements']['mydate'])
     * if you use at least one datepicker.
     *
     * The options will be assigned from the config array of the element
     *
     * @param array $element
     * @return string
     */
    public static function afterNewIdDatePicker($element)
    {
        $options = isset($element['options']) ? $element['options'] : array();
        $jsOptions = CJavaScript::encode($options);

        $language = isset($element['language']) ? $element['language'] : '';
        if (!empty($language))
            $language = "jQuery.datepicker.regional['$language'],";

        return "if(this.hasClass('hasDatepicker')) {this.removeClass('hasDatepicker'); this.datepicker(jQuery.extend({showMonthAfterYear:false}, $language {$jsOptions}));};";
    }

    /**
     * Support for extension datetimepicker
     * @link http://www.yiiframework.com/extension/datetimepicker/
     *
     * @param array $element
     * @return string
     */
    public static function afterNewIdDateTimePicker($element)
    {
        $options = isset($element['options']) ? $element['options'] : array();
        $jsOptions = CJavaScript::encode($options);

        $language = isset($element['language']) ? $element['language'] : '';
        if (!empty($language))
            $language = "jQuery.datepicker.regional['$language'],";

        return "if(this.hasClass('hasDatepicker')) {this.removeClass('hasDatepicker').datetimepicker(jQuery.extend($language {$jsOptions}));};";
    }

    /**
     * Support for CJuiAutoComplete.
     *
     * @contributor Smirnov Ilya php1602agregator[at]gmail.com
     * @param array $element
     * @return string
     */
    public static function afterNewIdAutoComplete($element)
    {
        $options = isset($element['options']) ? $element['options'] : array();
        if (isset($element['sourceUrl']))
            $options['source'] = CHtml::normalizeUrl($element['sourceUrl']);
        else
            $options['source'] = $element['source'];

        $jsOptions = CJavaScript::encode($options);

        return "if ( this.hasClass('ui-autocomplete-input') )
			{
				var mmfAutoCompleteParent = this.parent();
				// cloning autocomplete element (without data and events)
				var mmfAutoCompleteClone  = this.clone();

				// removing old autocomplete element
				mmfAutoCompleteParent.empty();
				// re-init autocomplete with default options
				mmfAutoCompleteClone.autocomplete({$jsOptions});

				// inserting new autocomplete
				mmfAutoCompleteParent.append(mmfAutoCompleteClone);
			}";
    }


    /**
     * Support for EJuiComboBox
     *
     * @contributor Smirnov Ilya php1602agregator[at]gmail.com
     * @param array $element
     * @param bool  $allowText
     * @return string
     */
    public static function afterNewIdJuiComboBox($element, $allowText=true)
    {
        $options = array();
        if ( $allowText )
        {
            $options['allowText'] = true;
        }

        $jsOptions = CJavaScript::encode($options);

        return "if ( this.attr('type') == 'text' && this.hasClass('ui-autocomplete-input') )
			{
				var mmfComboBoxParent   = this.parent();
				// cloning autocomplete and select elements (without data and events)
				var mmfComboBoxClone    = this.clone();
				var mmfComboSelectClone = this.prev().clone();

				// removing old combobox
				mmfComboBoxParent.empty();
				// addind new cloden elements ()
				mmfComboBoxParent.append(mmfComboSelectClone);
				mmfComboBoxParent.append(mmfComboBoxClone);

				// re-init autocomplete with default options
				mmfComboBoxClone.combobox({$jsOptions});
			}

			if ( this.attr('type') == 'button' )
			{// removing old combobox button
				this.remove();
			}";
    }

    /**
     * @param $fileUrl
     * @return string
     */
    public static function getFileUrl($fileUrl)
    {
        return Yii::app()->baseUrl . '/' . $fileUrl;
    }

    /**
     * Create a unique filename.
     * Calls the mmfFileDir(attribute,this) if implemented in the model,
     * otherwise the filepath is the relative path from webroot: files/modelclass.
     * Adds numeric idx name_idx.ext until the file not exists in the filepath.
     *
     * @param $model
     * @param $attribute
     * @param $filename
     * @return string
     */
    public function createUniqueFilenamePath($model,$attribute,$filename)
    {
        if(method_exists($model,'mmfFileDir'))
            $filePath = call_user_func(array($model,'mmfFileDir'),$attribute,$this);
        else
            $filePath = 'files/'.strtolower(get_class($model));

        $file = str_replace('/',DIRECTORY_SEPARATOR,$filePath).DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($filePath))
            mkdir($filePath, 0777, true);

        if($this->fileReplaceExisting)
        {
            if(is_file($file))
                unset($file);
        }
        else
        {
            $idx = 0;
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $rawfileName = substr(basename($file),0,-(strlen($ext)+1));
            while (is_file($file))
            {
                $idx++;
                $file = $filePath . DIRECTORY_SEPARATOR . $rawfileName . '_' . $idx .'.'. $ext;
            }
        }
        return $filePath.DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Return the relative filepath from webroot
     *
     * @param $url
     * @return string
     */
    public function getFilePathFromUrl($url)
    {
        return str_replace('/',DIRECTORY_SEPARATOR,$url);
    }


    /**
     * Return a image tag if fileUrl is an image, downloadlink otherwise
     * Used in renderElement as hint for a file attribute.
     *
     * If the model implements a method mmfGetFileInfo like below, this method will be used instead of default implementation.
     * public function mmfGetFileInfo($attribute,$value,$multiModelform)
     * {
     *    ... render the imagepreview/downloadlink ... for this attribute
     * }
     *
     * @param $value
     * @return string
     */
    public function getFileInfo($attribute,$value)
    {
        if(empty($value))
            return;

        if(method_exists($this->model,'mmfGetFileInfo'))
            return call_user_func(array($this->model,'mmfGetFileInfo'),$attribute,$value,$this);
        else
        {
            $fileNamePath = $this->getFilePathFromUrl($value);
            if(!is_file($fileNamePath))
                return;

            $mimeType = CFileHelper::getMimeType($fileNamePath);
            $value = self::getFileUrl($value);
            if(strpos($mimeType,'image')===0) //image
                return CHtml::image($value,basename($value),$this->fileImagePreviewHtmlOptions);
            else
                return CHtml::link($value,$value,$this->fileLinkHtmlOptions);
        }
    }


    /**
     * Check if a file attribute has an old value
     *
     * @param $model
     * @param $idx
     * @param $attribute
     * @return bool
     */
    protected function hasOldFileAttributeValue($model,$idx,$attribute)
    {
        $modelClass = get_class($model);
        return !empty($this->_oldFileAttributes) &&
        isset($this->_oldFileAttributes[$modelClass]) &&
        isset($this->_oldFileAttributes[$modelClass][$idx]) &&
        isset($this->_oldFileAttributes[$modelClass][$idx][$attribute]);
    }


    /**
     * Get the old value of a file attribute
     *
     * @param $model
     * @param $idx
     * @param $attribute
     * @return mixed|null
     */
    protected function getOldFileAttributeValue($model,$idx,$attribute)
    {
        $value = null;
        $modelClass = get_class($model);
        if($this->hasOldFileAttributeValue($model,$idx,$attribute))
            $value = json_decode($this->_oldFileAttributes[$modelClass][$idx][$attribute],true);
        return $value;
    }


    /**
     * Save all registered uploads and assign the model attribute
     */
    public function saveUploadedFiles()
    {
        $uploadOk = true;
        if(!empty($this->_uploadedFiles))
        {
            foreach($this->_uploadedFiles as $item)
            {
                foreach($item['uploads'] as $attribute=>$uploadedFile)
                {
                    $this->saveUploadedFile($item['model'],$attribute,$uploadedFile);
                    if($item['model']->hasErrors())
                        $uploadOk = false;
                }
            }
        }

        return $uploadOk;
    }


    /**
     * @param $uploadedFile CUploadedFile
     * Set the attribute to the (relative) fileUrl
     * @param $model
     */
    public function saveUploadedFile($model,$attribute,$uploadedFile)
    {
        if(!empty($uploadedFile) && !empty($model))
        {
            if($model->hasErrors($attribute)) //save uploaded file only if not has errors
                return;

            if(method_exists($model,'mmfSaveUploadedFile'))
                call_user_func(array($model,'mmfSaveUploadedFile'),$attribute,$uploadedFile,$this);
            else
                $this->saveFile($model,$attribute,$uploadedFile);
        }
    }

    /**
     * @param $uploadedFile
     * @param $model
     * @param $attribute
     * @return bool
     */
    public function saveFile($model,$attribute,$uploadedFile)
    {
        $relPath = $this->createUniqueFilenamePath($model,$attribute,$uploadedFile->getName());
        $saved = $uploadedFile->saveAs($relPath);
        if ($saved)
        {
            $model->$attribute = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
        } else
            $model->addError($attribute, 'Unable to save uploaded file');

        return $saved;
    }

    /**
     * Get the CUploadedFile instance for a file attribute
     *
     * @param $model
     * @param $prefix
     * @param $idx
     * @param $attribute
     * @return CUploadedFile
     */
    protected function getUploadedFile($model,$prefix,$idx,$attribute)
    {
        return empty($prefix) ? CUploadedFile::getInstance($model, $attribute."[$idx]") : CUploadedFile::getInstance($model, $prefix."[$idx]".$attribute);
    }

    /**
     * Register a attribute as file attribute on form rendering
     *
     * @param $attribute
     */
    public function registerFileAttribute($attribute)
    {
        if(!isset($this->_fileAttributes))
            $this->_fileAttributes = array();

        if((!in_array($attribute,$this->_fileAttributes)))
            $this->_fileAttributes[]=$attribute;
    }

    /**
     * Check the file attributes and upload the files
     * Assign path to the file attribute
     * Restore old fileattribute value if no file is uploaded
     *
     * @param $model
     * @param $name
     * @return bool
     */
    protected function registerUploadedFiles($formData,&$model,$prefix,$idx,$isCloned=false)
    {
        $registeredAttributes = array();
        $uploads = array();
        foreach ($this->getFileAttributes($formData) as $attribute)
        {
            $uploadedFile = $this->getUploadedFile($model,$prefix,$idx,$attribute);
            if (is_null($uploadedFile))
            {
                if(!$isCloned)
                {
                    if($this->hasOldFileAttributeValue($model,$idx,$attribute))
                        $model->$attribute=$this->getOldFileAttributeValue($model,$idx,$attribute);
                }
                else
                    $model->$attribute=null;

                continue;
            }

            $uploads[$attribute]=$uploadedFile;
            $registeredAttributes[]=$attribute;
            //$this->saveUploadedFile($model,$attribute,$uploadedFile);
        }

        if(!empty($uploads))
            $this->_uploadedFiles[]=array('model'=>$model,'uploads'=>$uploads);

        return $registeredAttributes;
    }


    /**
     * Get the attributes of type file, submitted by hidden fields from the form
     *
     * @param $formData
     * @return int
     */
    public function getFileAttributes($formData)
    {
        if(!isset($this->_fileAttributes))
        {
            $name=get_class($this->model) . '_fileAttributes';
            $this->_fileAttributes = isset($formData[$name]) ? $formData[$name] : array();
        }

        return $this->_fileAttributes;
    }



    /**
     * This static function should be used in the controllers update action
     * The models will be validated before saving
     *
     * If a record is not valid, the invalid model will be set to $model
     * to display error summary
     *
     * @param mixed $model CActiveRecord or other CModel
     * @param array $validatedItems returns the array of validated records
     * @param array $deleteItems
     * @param array $masterValues attributes to assign before saving
     * @param array $formData (default = $_POST)
     * @return boolean
     */
    public static function save($model, &$validatedItems, &$deleteItems = array(), $masterValues = array(), $formData = null,$masterModel=null)
    {
        //validate if empty: means no validation has been done
        $doValidate = empty($validatedItems) && empty($deleteItems);

        if (!isset($formData))
            $formData = $_POST;

        $sortAttribute = !empty($formData[get_class($model) . '_sortAttribute']) ? $formData[get_class($model) . '_sortAttribute'] : null;
        $sortIndex = 0;

        if ($doValidate)
        {
            //validate and assign $masterValues
            if (!self::validate($model, $validatedItems, $deleteItems, $masterValues, $formData,$masterModel))
                return false;
        }

        if (!empty($validatedItems))
            foreach ($validatedItems as $item)
            {
                if (!$doValidate) //assign $masterValues
                {
                    if (!empty($masterValues))
                        $item->setAttributes($masterValues, false);
                }

                //if sortable, assign the sortAttribute
                if (!empty($sortAttribute))
                {
                    $sortIndex++;
                    $item->$sortAttribute = $sortIndex;
                }

                if (!$item->save())
                    return false;
            }

        //$deleteItems = array of primary keys to delete
        if (!empty($deleteItems))
            foreach ($deleteItems as $pk)
                if (!empty($pk))
                {
                    //workaround, if no composite pk
                    if (count($pk) == 1)
                    {
                        $vals = array_values($pk);
                        $pk = $vals[0];
                    }

                    $model->deleteByPk($pk);
                }

        return true;
    }

    /**
     * Validates submitted formdata
     * If a record is not valid, the invalid model will be set to $model
     * to display error summary
     *
     * @param mixed $model
     * @param array $validatedItems returns the array of validated records
     * @param array $deleteItems returns the array of model for deleting
     * @param array $masterValues attributes to assign before saving
     * @param array $formData (default = $_POST)
     * @param null $masterModel the instance of the mastermodel will be submitted to the mmfCallbacks (fileupload, ...)
     * @param array $initAttributes assign virtual attributes, which are not part of attributenames and will not be assigned by setAttributes
     * @return bool
     */
    public static function validate($model, &$validatedItems, &$deleteItems = array(), $masterValues = array(), $formData = null,$masterModel=null,$initAttributes=array())
    {
        $widget = new MultiModelForm;
        $widget->model = $model;
        $widget->masterModel = $masterModel;

        $widget->checkModel();

        if (!$widget->initItems($validatedItems, $deleteItems, $masterValues, $formData,$initAttributes))
        {
            $widget->saveUploadedFiles(); //saves files only if the fileattribute has no errors
            return false; //at least one item is not valid
        }
        else
        {
            return $widget->saveUploadedFiles();
        }
    }

    /**
     * Converts the submitted formdata into an array of model
     *
     * @param array $formData the postdata $_POST submitted by the form
     * @param array $validatedItems the items which were validated
     * @param array $deleteItems the items to delete
     * @param array $masterValues assign additional masterdata before save
     * @param array $initAttributes assign virtual attributes, which are not part of attributenames and will not be assigned by setAttributes
     * @return array array of model
     */
    public function initItems(&$validatedItems, &$deleteItems, $masterValues = array(), $formData = null,$initAttributes=array())
    {
        if (!isset($formData))
            $formData = $_POST;

        $result = true;
        $newItems = array();
        $this->_fileAttributes = null;
        $this->_uploadedFiles = array();

        $validatedItems = array(); //bugfix: 1.0.2
        $deleteItems = array();
        $modelClass = get_class($this->model);

        if (!isset($formData) || empty($formData[$modelClass]))
            return true;


        //init the old file attributes array
        $this->_oldFileAttributes = array();
        if (isset($formData[$modelClass]['f__']))
        {
            foreach ($formData[$modelClass]['f__'] as $idx => $attributes)
            {
                if(!isset($this->_oldFileAttributes[$modelClass]))
                    $this->_oldFileAttributes[$modelClass] = array();
                if(!isset($this->_oldFileAttributes[$modelClass][$idx]))
                    $this->_oldFileAttributes[$modelClass][$idx] = array();
                $this->_oldFileAttributes[$modelClass][$idx] = $attributes;
            }
            unset($formData[$modelClass]['f__']);
        }

        //----------- NEW (on validation error) -----------

        if (isset($formData[$modelClass]['n__']))
        {
            $submittedItems = $formData[$modelClass]['n__'];
            unset($formData[$modelClass]['n__']);

            foreach ($submittedItems as $idx => $attributes)
            {
                $model = new $modelClass;
                if(!empty($initAttributes))
                    foreach($initAttributes as $k=>$v)
                        $model->$k = $v;

                $model->attributes = $attributes;
                $registeredForUpload=$this->registerUploadedFiles($formData,$model,"[n__]",$idx);

                if($this->allSubmittedAttributesEmpty($model,$formData,$registeredForUpload))
                    continue;

                if(!empty($registeredForUpload))
                    $this->validateUploadFile($model);

                if (!empty($masterValues))
                    $model->setAttributes($masterValues, false); //assign mastervalues

                // validate
                if (!$model->validate(null,false)) //don't clear errors
                    $result = false;

                $validatedItems[] = $model;
            }
        }

        //----------- UPDATE -----------

        $allExistingPk = isset($formData[$modelClass]['pk__']) ? $formData[$modelClass]['pk__'] : null; //bugfix: 1.0.1

        if (isset($formData[$modelClass]['u__']))
        {
            $submittedItems = $formData[$modelClass]['u__'];
            unset($formData[$modelClass]['u__']);

            foreach ($submittedItems as $idx => $attributes)
            {
                $model = new $modelClass('update');
                if(!empty($initAttributes))
                    foreach($initAttributes as $k=>$v)
                        $model->$k = $v;

                //load the models and/or assign the primary keys
                if (is_array($allExistingPk))
                {
                    $primaryKeys = $allExistingPk[$idx];
                    if (method_exists($model, 'findByPk')) //only if is CActiveRecord 
                    {
                        //workaround, if no composite pk
                        if (count($primaryKeys) == 1)
                        {
                            $vals = array_values($primaryKeys);
                            $primaryKeys = $vals[0];
                        }

                        $model = $model->findByPk($primaryKeys); //load the model attributes from db
                    }
                    elseif(method_exists($model,'setOldPrimaryKey'))
                        //allow to change pk, if pk is part of the visible formelements
                        $model->setOldPrimaryKey($primaryKeys);

                    //ensure to assign primary keys (when pk is unsafe or not defined in rules)
                    $model->setAttributes($primaryKeys, false);
                }

                //should work for CModel, mongodb models... too
                if (method_exists($model, 'setIsNewRecord'))
                    $model->setIsNewRecord(false);

                $model->attributes = $attributes;

                $registeredForUpload=$this->registerUploadedFiles($formData,$model,"[u__]",$idx);
                if(!empty($registeredForUpload))
                    $this->validateUploadFile($model);

                if (!empty($masterValues))
                    $model->setAttributes($masterValues, false); //assign mastervalues

                // validate
                if (!$model->validate(null,false))
                    $result = false;

                $validatedItems[] = $model;

                // remove from $allExistingPk
                if (is_array($allExistingPk))
                    unset($allExistingPk[$idx]);
            }
        }

        //----------- DELETE -----------

        // add remaining primarykeys to $deleteItems (reindex)
        if (is_array($allExistingPk))
            foreach ($allExistingPk as $idx => $delPks)
                $deleteItems[] = $delPks;

        // remove handled formdata pk__
        unset($formData[$modelClass]['pk__']);

        //----------- Check for cloned elements by jQuery -----------

        if (!empty($formData[$modelClass])) //has cloned elements
        {
            //convert the submitted structure ModelClass[attribute]=>array(idx=>value) to ModelClass[idx]=>array(attribute=>value)
            $modelsData = array();
            foreach($formData[$modelClass] as $modelAttribute=>$attributeValues)
            {
                foreach($attributeValues as $idx=>$value)
                {
                    if(!isset($modelsData[$idx]))
                        $modelsData[$idx] = array();

                    $modelsData[$idx][$modelAttribute]=$value;
                }
            }

            foreach($modelsData as $idx => $attributes)
            {
                $model = new $modelClass;
                if(!empty($initAttributes))
                    foreach($initAttributes as $k=>$v)
                        $model->$k = $v;

                $model->attributes = $attributes; //safe only
                $registeredForUpload=$this->registerUploadedFiles($formData,$model,'',$idx,true);

                if($this->allSubmittedAttributesEmpty($model,$formData,$registeredForUpload))
                    continue;

                if(!empty($registeredForUpload))
                    $this->validateUploadFile($model);

                //assign mastervalues without checking rules
                if (!empty($masterValues))
                    $model->setAttributes($masterValues, false);

                // validate
                if (!$model->validate(null,false))
                    $result = false;

                $validatedItems[] = $model;
            }
        }

        return $result;
    }

    /**
     * Get the primary key as array (key => value).
     * A pk is needed to determine which records to delete.
     * If no pk is available it works too, but the deleteditems on validate is always empty and each record isnew.
     * Detect the pk from CActiveRecord and EMongoDocument.
     * If working with formmodels a custom callbackMethod mmfPrimaryKey of the model will be called if defined.
     *
     * @param CModel $model
     * @return array
     */
    public function getPrimaryKey($model)
    {
        $result = array();

        if ($model instanceof CActiveRecord)
        {
            $pkValue = $model->primaryKey;
            if (!empty($pkValue))
            {
                $pkName = $model->primaryKey();
                if (empty($pkName))
                    $pkName = $model->tableSchema->primaryKey;

                $result = is_array($pkValue) ? $pkValue : array($pkName => $pkValue);
            }
        }
        elseif(method_exists($model,'primaryKey')) // when working with EMongoDocument
        {
            $pkName = $model->primaryKey();
            if(is_array($pkName))
            {
                $result = array();
                foreach($pkName as $pkN)
                    $result[$pkN]=$model->$pkN;
            }
            else
            {
                $pkValue = $model->$pkName;
                if (empty($pkValue))
                    $result = array($pkName => $pkValue);
            }
        }
        elseif(method_exists($model,'mmfPrimaryKey'))
        {
            $result = $model->mmfPrimaryKey();
        }

        return $result;
    }

    /**
     * Get the copyClass
     *
     * @return string
     */
    public function getCopyClass()
    {
        if (isset($this->options['copyClass']))
            return $this->options['copyClass'];
        else
        {
            $selector = $this->id . '_copy';
            $this->options['copyClass'] = $selector;
            return $selector;
        }
    }

    /**
     * @since 3.2
     * @return string
     */
    public function getCopyFieldsetId()
    {
        return $this->id . '_copytemplate';
    }

    /**
     * The link for removing a fieldset
     *
     * @return string
     */
    public function getRemoveLink($isCopyTemplate = false)
    {
        if ($isCopyTemplate && !$this->hideCopyTemplate)
            return '';

        if (empty($this->removeText) || !$this->allowRemoveItem) //added v3.1
            return '';

        $onClick = '$(this).parent().parent().remove(); mmfRecordCount--; return false;';

        if ($isCopyTemplate && $this->hideCopyTemplate)
        {
            $copyId = $this->getCopyFieldsetId();
            $onClick = 'if($(this).parent().parent().attr("id")=="' . $copyId . '") {clearAllInputs($("#' . $copyId . '"));$(this).parent().parent().hide()} else ' . $onClick;
        }

        if (!empty($this->removeConfirm))
            $onClick = "if(confirm('{$this->removeConfirm}')) " . $onClick;

        $removeOnClick = trim($this->removeOnClick);
        if (!empty($removeOnClick))
        {
            if(substr($removeOnClick, -1) != ';')
                $removeOnClick = $removeOnClick .  ';';
            $onClick = $removeOnClick . $onClick;
        }

        $htmlOptions = array_merge($this->removeHtmlOptions, array('onclick' => $onClick));
        $htmlOptions['class'] = isset($htmlOptions['class']) ? $htmlOptions['class'] . ' ' . self::CLASSPREFIX . 'removelink' : self::CLASSPREFIX . 'removelink';

        $link = CHtml::link($this->removeText, '#', $htmlOptions);

        return CHtml::tag($this->removeLinkWrapper['tag'],
            $this->removeLinkWrapper['htmlOptions'], $link);
    }

    /**
     * Check if rows has to be sortable
     * Works only if not is as tableView because the submitted $_POST data are not in the correct sorted order
     * Sorting in tableView needs more investigation/workaround ...
     *
     * @return bool
     */
    public function isSortable()
    {
        return !empty($this->sortAttribute) && !$this->tableView;
    }

    /**
     * Initialize the widget: register scripts
     */
    public function init()
    {
        $this->removeLinkWrapper['htmlOptions']['class'] = !empty($this->removeLinkWrapper['htmlOptions']['class']) ?
            $this->removeLinkWrapper['htmlOptions']['class'] . ' ' . self::CLASSPREFIX . 'removelink' :
            self::CLASSPREFIX . 'removelink';

        if ($this->tableView)
        {
            $this->fieldsetWrapper = array('tag' => 'tr', 'htmlOptions' => array('class' => self::CLASSPREFIX . 'row'));
            $this->rowWrapper = array('tag' => 'td', 'htmlOptions' => array('class' => self::CLASSPREFIX . 'cell'));
            $this->removeLinkWrapper = $this->rowWrapper;
            if ($this->bootstrapLayout)
            {
                if (!isset($this->tableHtmlOptions['class']))
                    $this->tableHtmlOptions['class'] = 'table ' . self::CLASSPREFIX . 'table';
            }
        } else
            if ($this->bootstrapLayout)
            {
                $this->rowWrapper = array('tag' => 'div', 'htmlOptions' => array('class' => 'control-group ' . self::CLASSPREFIX . 'row'));
            }

        $this->_recordCount = 0;

        $this->checkModel();
        $this->registerClientScript();
        parent::init();
    }

    /**
     * Check the model instance on init / after create
     * Add all model attributes as hidden and visible=false if they are not part of the formConfig
     * Need this because on update all attributes have to be submitted, no 'loadModel' is called
     */
    protected function checkModel()
    {
        if (is_string($this->model))
            $this->model = new $this->model;

        if (isset($this->model) &&
            isset($this->formConfig) &&
            !empty($this->formConfig['elements']) &&
            !method_exists($this->model, 'findByPk')
        )
        {
            // if not method_exists($this->model, 'findByPk'):
            // add undefined attributes in the form config as hidden fields and attribute visible = false
            // because the model will not be loaded on update: see UPDATE in method initItems
            foreach ($this->model->attributes as $attribute => $value)
            {
                if (!array_key_exists($attribute, $this->formConfig['elements']))
                    $this->formConfig['elements'][$attribute] = array('type' => 'hidden', 'visible' => false);
            }
        }
    }

    /**
     * @return array the javascript options
     */
    protected function getClientOptions()
    {
        if (empty($this->options))
            $this->options = array();

        if (!empty($this->removeText) && !$this->hideCopyTemplate)
        {
            $append = $this->getRemoveLink();
            $this->options['append'] = empty($this->options['append']) ? $append : $append . ' ' . $this->options['append'];
        }

        if (!empty($this->jsBeforeClone))
            $this->options['beforeClone'] = $this->jsBeforeClone;

        if (!empty($this->jsAfterClone))
            $this->options['afterClone'] = $this->jsAfterClone;

        if (!empty($this->jsBeforeNewId))
            $this->options['beforeNewId'] = $this->jsBeforeNewId;

        if (!empty($this->jsAfterNewId))
            $this->options['afterNewId'] = $this->jsAfterNewId;

        if (!empty($this->jsAfterCloneCallback))
            $this->options['afterCloneCallback'] = $this->jsAfterCloneCallback;

        $this->options['limitText'] = $this->limitText;
        $this->options['clearInputs'] = $this->clearInputs;

        return CJavaScript::encode($this->options);
    }

    /**
     * The id selector for jQuery.sortable
     *
     * @return string
     */
    protected function getSortSelectorId()
    {
        return get_class($this->model) . '_' . self::CLASSPREFIX . 'sortable';
    }

    /**
     * Registers the relcopy javascript file.
     */
    public function registerClientScript()
    {
        $cs = Yii::app()->getClientScript();

        $this->_assets = Yii::app()->assetManager->publish(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets');

        $cs->registerCoreScript('jquery');
        $cs->registerScriptFile($this->_assets . '/js/'.$this->jsRelCopy);

        $options = $this->getClientOptions();
        $cs->registerScript(__CLASS__ . '#' . $this->id, "jQuery('#{$this->id}').relCopy($options);");

        //add the script for jQuery.sortable
        if ($this->isSortable())
        {
            $cs->registerCoreScript('jquery.ui');
            $cssFile = $cs->getCoreScriptUrl() . '/jui/css/base/jquery-ui.css';
            $cs->registerCssFile($cssFile);

            $options = CJavaScript::encode($this->sortOptions);
            Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $this->id . 'Sortable', "jQuery('#{$this->getSortSelectorId()}').sortable({$options});");
        }
    }

    /**
     * Render the top of the table: AddLink, Table header
     */
    public function renderTableBegin($renderAddLink)
    {
        $form = $this->createModelForm($this->model);
        $form->parentWidget = $this;

        //add link as div
        if ($renderAddLink)
        {
            $addLink = $form->getAddLink();
            echo CHtml::tag('div', array('class' => self::CLASSPREFIX . 'addlink'), $addLink);
        }

        $tableHtmlOptions = array_merge(array('class' => self::CLASSPREFIX . 'table'), $this->tableHtmlOptions);

        //table
        echo CHtml::tag('table', $tableHtmlOptions, false, false);

        //thead
        $form->renderTableHeader();

        //tfoot
        if (!empty($this->tableFootCells))
        {
            $cells = '';
            foreach ($this->tableFootCells as $cell)
            {
                $cells .= CHtml::tag('td', array('class' => self::CLASSPREFIX . 'cell'), $cell);
            }

            $cells = CHtml::tag('tr', array('class' => self::CLASSPREFIX . 'row'), $cells);
            echo CHtml::tag('tfoot', array(), $cells);
        }

        //tbody
        $tbodyOptions = $this->isSortable() ? array('id' => $this->getSortSelectorId()) : array();
        echo CHtml::tag('tbody', $tbodyOptions, false, false);
    }

    /**
     * Check if limit is set and reached
     * @return bool
     */
    public function limitReached()
    {
        $limit = !empty($this->options['limit']) ? $this->options['limit'] : 0;

        return $limit > 0 ? ($limit - $this->_recordCount) <= 0 : false;
    }

    /**
     * Renders the active form if a model and formConfig is set
     * $this->data is array of model
     */
    public function run()
    {
        if (empty($this->model) || empty($this->formConfig))
            return;

        //form is displayed again with some invalid models
        $isErrorMode = !empty($this->validatedItems);
        $showAddLink = $this->allowAddItem && (!$isErrorMode || ($isErrorMode && $this->showAddItemOnError));

        $this->formConfig['activeForm'] = array('class' => 'MultiModelEmbeddedForm');

        $idx = 0;
        $errorPk = null;

        if ($isErrorMode)
        {
            if ($this->showErrorSummary)
                echo CHtml::errorSummary($this->validatedItems);

            $data = $this->validatedItems;
        } else
            $data = $this->data; //from the db


        if ($this->tableView)
            $this->renderTableBegin($showAddLink);

        if ($this->isSortable())
        {
            //render the name of the sortAttribute as hidden input
            //used in MultiModelForm::save
            echo CHtml::hiddenField(get_class($this->model) . '_sortAttribute', $this->sortAttribute);

            if (!$this->tableView)
                echo CHtml::openTag('div', array('id' => $this->getSortSelectorId()));
        }

        // existing records
        if (is_array($data) && !empty($data))
        {
            $this->_recordCount = count($data);

            foreach ($data as $model)
            {
                $form = $this->createModelForm($model);
                $form->index = $idx;
                $form->parentWidget = $this;

                $form->primaryKey = $this->getPrimaryKey($model);

                if (!$this->tableView)
                {
                    if ($showAddLink && $idx == 0) // no existing data rendered
                        echo $form->renderAddLink();
                }

                // render pk outside of removeable tag, for checking records to delete
                // see method initItems()
                echo $form->renderHiddenPk('[pk__]');
                echo $form->render();

                $idx++;
            }
        }

        //if form is displayed first time or in errormode and want to show 'Add item' (and a 'CopyTemplate')
        if ($showAddLink)
        {
            // add an empty fieldset as CopyTemplate
            $form = $this->createModelForm($this->model);
            $form->index = $idx;
            $form->parentWidget = $this;
            $form->isCopyTemplate = true;

            if (!$this->tableView)
            {
                if ($idx == 0) // no existing data rendered
                    echo $form->renderAddLink();
            }

            echo $form->render();
        }

        echo CHtml::script('mmfRecordCount=' . $this->_recordCount);
        if(!empty($this->_fileAttributes))
            foreach($this->_fileAttributes as $fileAttribute)
                echo CHtml::hiddenField(get_class($this->model) . '_fileAttributes[]', $fileAttribute);

        if ($this->tableView)
        {
            echo CHtml::closeTag('tbody');
            echo CHtml::closeTag('table');
        } elseif ($this->isSortable())
            echo CHtml::closeTag('div');
    }

    /**
     * @return mixed
     */
    protected function createModelForm($model)
    {
        $class = $this->renderForm;
        return new $class($this->formConfig,$model);
    }


    /**
     * Check if a value is empty.
     * Check arrays recursive.
     *
     * @param $value
     */
    protected function isEmptyValue($value)
    {
       $empty=empty($value);

       if(!$empty && is_array($value))
       {
           $allEmpty = true;
           foreach($value as $val)
           {
               $itemEmpty = $this->isEmptyValue($val);
               if(!$itemEmpty)
               {
                   $allEmpty = false;
                   break;
               }
           }
           $empty = $allEmpty;
       }

       return $empty;
    }


    /**
     * Check if all submitted attributes are empty
     *
     * @param $formData
     * @param $modelClass
     * @param $registeredForUpload
     * @param $model
     * @return bool
     */
    protected function allSubmittedAttributesEmpty($model,$formData,$registeredForUpload)
    {
        $allEmpty = true;
        $submittedAttributes = array_keys($formData[get_class($model)]);
        foreach ($submittedAttributes as $attribute)
        {
            if (in_array($attribute, $registeredForUpload) || !$this->isEmptyValue($model->{$attribute}))
            {
                $allEmpty = false;
                break;
            }
        }
        return $allEmpty;
    }

    /**
     * @param $model
     * @param $modelClass
     * @return bool
     */
    protected function validateUploadFile($model)
    {
        foreach ($model->getValidators() as $validator)
        {
            if ($validator instanceof CFileValidator)
            {
                foreach ($this->_uploadedFiles as $uploadItem)
                {
                    if ($uploadItem['model'] == $model)
                    {
                        $class = get_class($model);
                        $fileModel = new $class;
                        foreach ($uploadItem['uploads'] as $fileAttr => $cUploadFile)
                            $fileModel->$fileAttr = $cUploadFile;
                        $validator->validate($fileModel);
                        if ($fileModel->hasErrors())
                            $model->addErrors($fileModel->getErrors());
                    }
                }
            }
        }
    }
}

/**
 * The CForm to render the input form
 */
class MultiModelRenderForm extends CForm
{
    public $parentWidget;
    public $index;
    public $isCopyTemplate;
    public $primaryKey;
    public $fileAttributes;

    /**
     * Modified for bootstrapLayout
     */
    public function renderButtons()
    {
        if ($this->parentWidget->bootstrapLayout)
        {
            $output = '';
            foreach ($this->getButtons() as $button)
                $output .= $this->renderElement($button);
            return $output !== '' ? "<div class=\"form-actions\">" . $output . "</div>\n" : '';
        } else
            parent::renderButtons();
    }

    /**
     * Modified for bootstrapLayout
     */
    public function renderElement($element)
    {
        if ($this->parentWidget->bootstrapLayout) //begin bootstrapLayout
        {
            if (is_string($element))
            {
                if (($e = $this[$element]) === null && ($e = $this->getButtons()->itemAt($element)) === null)
                    return $element;
                else
                    $element = $e;
            }
            if ($element->getVisible())
            {
                if ($element instanceof CFormInputElement)
                {
                    if ($element->type === 'hidden')
                        return "<div style=\"display:none\">\n" . $element->render() . "</div>\n";
                    else
                        return "<div class=\"controls field_{$element->name}\">\n" . $element->render() . "</div>\n";
                } else if ($element instanceof CFormButtonElement)
                    return $element->render() . "\n";
                else
                    return $element->render();
            }
            return '';
        } //end bootstrapLayout
        else
            parent::renderElement($element);
    }

    /**
     * Wraps a content with row wrapper
     *
     * @param string $content
     * @return string
     */
    protected function getWrappedRow($element,$elemName,$content)
    {
        $element->getParent()->getModel();
        $htmlOptions=$this->parentWidget->rowWrapper['htmlOptions'];

        if($this->parentWidget->bootstrapLayout && $element->getParent()->getModel()->hasErrors($elemName))
            $htmlOptions['class']=$htmlOptions['class'].' error';

        return CHtml::tag($this->parentWidget->rowWrapper['tag'],$htmlOptions, $content);
    }

    /**
     * Wraps a content with fieldset wrapper
     *
     * @param string $content
     * @return string
     */
    protected function getWrappedFieldset($content)
    {
        $htmlOptions = $this->parentWidget->fieldsetWrapper['htmlOptions'];

        if ($this->isCopyTemplate)
        {
            $htmlOptions['id'] = $this->parentWidget->getCopyFieldsetId();
            if ($this->parentWidget->hideCopyTemplate)
                $htmlOptions['style'] = !empty($htmlOptions['style']) ? $htmlOptions['style'] . ' display:none;' : 'display:none;';
        }

        return CHtml::tag($this->parentWidget->fieldsetWrapper['tag'], $htmlOptions, $content);
    }

    /**
     * Returns the generated label from Yii form builder
     * Needs to be replaced by the real attributeLabel
     * @see method  renderFormElements()
     *
     * @param string $prefix
     * @param string $attributeName
     * @return string
     */
    protected function getAutoCreatedLabel($prefix, $attributeName)
    {
        return ($this->model->generateAttributeLabel('[' . $prefix . '][' . $this->index . ']' . $attributeName));
    }

    /**
     * Renders the table head
     *
     * @return string
     */
    public function renderTableHeader()
    {
        $cells = '';

        foreach ($this->getElements() as $element)
        {
            if ($element->visible && isset($element->type) && $element->type != 'hidden') //bugfix v3.1
            {
                $text = empty($element->label) ? '&nbsp;' : $element->label;
                $options = array();

                if ($element->getRequired())
                {
                    $options = array('class' => CHtml::$requiredCss);
                    $text .= CHtml::$afterRequiredLabel;
                }

                $cells .= CHtml::tag('th', $options, $text);
            }
        }

        if (!empty($cells))
        {
            //add an empty column instead of remove link
            $cells .= CHtml::tag('th', array(), '&nbsp');

            $row = $this->getWrappedFieldset($cells);
            echo CHtml::tag('thead', array(), $cells);
        }
    }


    /**
     * Check if elem is a array type
     *
     * @param string $type
     * @return boolean
     */
    protected function isElementArrayType($type)
    {
        switch ($type)
        {
            case 'checkboxlist':
            case 'radiolist':
                return true;
            default:
                return false;
        } // switch
    }

    /**
     * Renders the label for this input.
     * The default implementation returns the result of {@link CHtml activeLabelEx}.
     * @return string the rendering result
     */
    public function renderElementLabel($element, $htmlOptions = array())
    {
        $class = '';

        $options = array_merge($htmlOptions, array(
            'label' => $element->getLabel(),
            'required' => $element->getRequired()
        ));

        if ($this->parentWidget->bootstrapLayout)
        {
            switch ($element->type)
            {
                case 'checkbox':
                case 'checkboxlist':
                    $class = 'checkbox';

                case 'radio':
                case 'radiolist':
                    $class = 'radio';

                default:
                    $class = 'control-label';
            }
        }

        if (!empty($class))
            $options['class'] = $class;

        if (!empty($element->attributes['id']))
        {
            $options['for'] = $element->attributes['id'];
        }

        return CHtml::activeLabel($element->getParent()->getModel(), $element->name, $options);
    }

    /**
     * Renders a single form element
     * Remove the '[]' from the label
     *
     * @return string
     */
    protected function renderFormElements()
    {
        $output = '';

        $elements = $this->getElements();
        $fileAttributes = array();

        foreach ($elements as $element)
        {
            if (isset($element->name)) //element is an attribute of the model
            {
                $elemName = $element->name;

                if ($this->parentWidget->bootstrapLayout && !$this->parentWidget->tableView)
                    $element->layout = "{label}<div class=\"controls\">{input}\n{hint}\n<span class=\"help-inline\"><p class=\"help-block\">{error}</p></span></div>";

                $elemLabel = $this->parentWidget->tableView ? '' : $this->renderElementLabel($element);
                $replaceLabel = array('{label}' => $elemLabel);
                $element->label = false; //no label on $element->render()
                $element->layout = strtr($element->layout, $replaceLabel);

                $doRender = false;

                $elem_pk = $this->primaryKey;
                $valid_elem_pk = !empty($elem_pk);
                if($valid_elem_pk && is_array($elem_pk))
                {
                    $valid_elem_pk = true;
                    foreach($elem_pk as  $t_pk)
                    {
                        //allow numerical 0, boolean false as valid pk
                        $valid_elem_pk = $valid_elem_pk && $t_pk!=='' && !is_null($t_pk);
                    }
                }

                if ($this->isCopyTemplate && $element->visible) // new fieldset
                {
                    //Array types have to be rendered as array in the CopyTemplate
                    $element->name = $this->isElementArrayType($element->type) ? $elemName . '[][]' : $elemName . '[]';
                    $doRender = true;
                }
                elseif($valid_elem_pk)
                { // existing fieldsets update
                    $element->name = '[u__][' . $this->index . ']' . $elemName;
                    $doRender = true;
                }
                else
                { //in validation error mode: the new added items before
                    if ($element->visible)
                    {
                        $element->name = '[n__][' . $this->index . ']' . $elemName;
                        $doRender = true;
                    }
                }

                if ($doRender)
                {
                    //set the hint to image or download link
                    //render a hidden input for restoring file attribute old value
                    if($element->type=='file')
                    {
                        //Need the fileAttributes in initItems on validation: see method MultiModelForm::isFileAttribute
                        $this->parentWidget->registerFileAttribute($elemName);

                        $fileModel = $element->getParent()->getModel();
                        $fileValue = $fileModel->$elemName;

                        if(!empty($fileValue))
                        {
                            $hint = $this->parentWidget->getFileInfo($elemName,$fileValue);
                            if(!empty($hint))
                                $element->hint = $hint;

                            $output .= CHtml::hiddenField(get_class($fileModel).'[f__][' . $this->index . '][' . $elemName .']',json_encode($fileValue));
                        }
                    }

                    $elemOutput = $element->render();
                    $output .= $element->type == 'hidden' ? $elemOutput : $this->getWrappedRow($element,$elemName,$elemOutput);
                }
            } else //CFormStringElement...
                $output .= $element->render();
        }

        $output .= $this->parentWidget->getRemoveLink($this->isCopyTemplate);

        return $output;
    }

    /**
     * Renders the primary key value as hidden field
     * Need determine which records to delete
     *
     * @param string $classSuffix
     * @return string
     */
    public function renderHiddenPk($classSuffix = '[pk__]')
    {
        $output = '';
        foreach ($this->primaryKey as $key => $value)
        {
            $modelClass = get_class($this->parentWidget->model);
            $name = $modelClass . $classSuffix . '[' . $this->index . ']' . '[' . $key . ']';
            $output .= CHtml::hiddenField($name, $value);
        }

        return $output;
    }

    /**
     * Get the add item link or button
     *
     * @return string
     */
    public function getAddLink()
    {
        if ($this->parentWidget->addItemAsButton)
        {
            echo CHtml::htmlButton($this->parentWidget->addItemText,
                array('id' => $this->parentWidget->id,
                    'rel' => '.' . $this->parentWidget->getCopyClass()
                ));
        } else
        {
            return CHtml::tag('a',
                array('id' => $this->parentWidget->id,
                    'href' => '#',
                    'rel' => '.' . $this->parentWidget->getCopyClass()
                ),
                $this->parentWidget->addItemText
            );
        }
    }

    /**
     * Renders the link 'Add' for cloning the DOM element
     *
     * @return string
     */
    public function renderAddLink()
    {
        $tag = $this->parentWidget->rowWrapper['tag'];
        $htmlOptions = $this->parentWidget->rowWrapper['htmlOptions'];

        $htmlOptions['class'] = !empty($htmlOptions['class']) ? $htmlOptions['class'] . ' mmf_additem' : 'mmf_additem';

        return CHtml::tag($tag, $htmlOptions, $this->getAddLink());
    }

    /**
     * Renders the CForm
     * Each fieldset is wrapped with the fieldsetWrapper
     *
     * @return string
     */
    public function render()
    {
        $elemOutput = $this->renderBegin();
        $elemOutput .= $this->renderFormElements();
        $elemOutput .= $this->renderEnd();
        // wrap $elemOutput
        $wrapperClass = $this->parentWidget->fieldsetWrapper['htmlOptions']['class'];

        if ($this->isCopyTemplate)
        {
            $class = empty($wrapperClass)
                ? $this->parentWidget->getCopyClass()
                : $wrapperClass . ' ' . $this->parentWidget->getCopyClass();
        } else
            $class = $wrapperClass;

        $this->parentWidget->fieldsetWrapper['htmlOptions']['class'] = $class;
        return $this->getWrappedFieldset($elemOutput);
    }
}

/**
 * MultiModelEmbeddedForm
 *
 * A CActiveForm with no output of the form begin and close tag
 * In Yii 1.1.6 the form end/close is the only output of the methods init() and run()
 * Needs review in upcoming releases
 *
 */
class MultiModelEmbeddedForm extends CActiveForm
{
    /**
     * Initializes the widget.
     * Don't render the open tag
     */
    public function init()
    {
        ob_start();
        parent::init();
        ob_get_clean();
    }

    /**
     * Runs the widget.
     * Don't render the close tag
     */
    public function run()
    {
        ob_start();
        parent::run();
        ob_get_clean();
    }
}
