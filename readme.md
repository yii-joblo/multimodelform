This extension allows to work with multiple records and different models in a edit form.
It handles clientside cloning and removing input elements/fieldsets and serverside batchInsert/Update/Delete.

Creating forms with this functionality is a 'tricky' workaround in views and controllers.
This widget should do the main part of the work for you.
It can be useful for order/registration forms ...

Find the latest releases on  [github](https://github.com/yii-joblo/multimodelform "")


##Features

- Clientside Clone/Remove form elements / fieldsets with
  the jQuery plugin http://www.andresvidal.com/labs/relcopy.html
- Simple handling of the submitted form data in the controllers create/update action
- Simultanous editing multiple records and models (master/detail...)
- Supports validation and error summary for each model/record
- Autogenerating input form elements with the 'form builder' functionality of Yii
- tableView (= gridView)
- filefields with full upload handling and image preview in the edit form.


##Notes

Not every form input element is supported. 

You can use the most basic CFormInputElements (text, textarea, dropdownlist, ...).

Some input widgets need a workaround with js-code after clientside cloning. 
Currently supported with ready to use javascript code in methods:

- CJuiDatePicker
- [datetimepicker](http://www.yiiframework.com/extension/datetimepicker/ "") 
- EJuiComboBox and CJuiAutoComplete (added by Smirnov Ilya)

Special handling:

- Checkbox: you have to use a checkboxlist instead (see below and the demo.3.2)

New in v6.0.0

- Filefield with fully handling all uploading and validating stuff

are not supported.


##Requirements

- Yii 1.1.6+ 


##Usage


- Extract the files under .../protected/extensions or use the composer

###Master/Detail example:

You can find the implemtation explained below in the demo application.

Assume you have two models 'group' (id, title) and 'member' (id, groupid, firstname,lastname,membersince).
The id attribute is the autoincrement primary key.

- Generate the models 'Group' and 'Member' with gii.
   For testing the error summary set the members firstname/lastname as required in the rules.

- Generate the 'GroupController' and the group/views with gii.
   You don't need to create a 'MemberController' and the member views for this example.

- Change the default actionUpdate of the GroupController to

~~~
[php]

	public function actionUpdate($id)
	{
		Yii::import('ext.multimodelform.MultiModelForm');
	
		$model=$this->loadModel($id); //the Group model
	
		$member = new Member;
		$validatedMembers = array(); //ensure an empty array
	
		if(isset($_POST['Group']))
		{
			$model->attributes=$_POST['Group'];
	
			//the value for the foreign key 'groupid'
			$masterValues = array ('groupid'=>$model->id);
	
			if( //Save the master model after saving valid members
				MultiModelForm::save($member,$validatedMembers,$deleteMembers,$masterValues) &&
				$model->save()
			   )
					$this->redirect(array('view','id'=>$model->id));
		}
	
		$this->render('update',array(
			'model'=>$model,
			//submit the member and validatedItems to the widget in the edit form
			'member'=>$member,
			'validatedMembers' => $validatedMembers,
		));
	}
~~~


- Change the code of actionCreate like this

~~~
[php]

	public function actionCreate()
	{
		Yii::import('ext.multimodelform.MultiModelForm');
	
		$model = new Group;
	
		$member = new Member;
		$validatedMembers = array();  //ensure an empty array
	
		if(isset($_POST['Group']))
		{
			$model->attributes=$_POST['Group'];
	
			if( //validate detail before saving the master
				MultiModelForm::validate($member,$validatedMembers,$deleteItems) &&
				$model->save()
			   )
			   {
				 //the value for the foreign key 'groupid'
				 $masterValues = array ('groupid'=>$model->id);
				 if (MultiModelForm::save($member,$validatedMembers,$deleteMembers,$masterValues))
					$this->redirect(array('view','id'=>$model->id));
			    }
		}
	
		$this->render('create',array(
			'model'=>$model,
			//submit the member and validatedItems to the widget in the edit form
			'member'=>$member,
			'validatedMembers' => $validatedMembers,
		));
	}
~~~


- Change the renderPartial in views/group/create.php and update.php
   to transfer the parameters $member and $validatedMembers to the _form.php

~~~
[php]

    echo $this->renderPartial('_form', array('model'=>$model,
                          'member'=>$member,'validatedMembers'=>$validatedMembers));
~~~


- Change the generated code of the GroupController's form view (views/group/_form.php).

~~~
[php]

	<div class="form wide">
	
	<?php $form=$this->beginWidget('CActiveForm', array(
			'id'=>'group-form',
			'enableAjaxValidation'=>false,
	)); ?>
	
	<p class="note">Fields with <span class="required">*</span> are required.</p>
	
	<?php
		//show errorsummary at the top for all models
		//build an array of all models to check
		echo $form->errorSummary(array_merge(array($model),$validatedMembers));
	?>
	
	<div class="row">
		<?php echo $form->labelEx($model,'title'); ?>
		<?php echo $form->textField($model,'title'); ?>
		<?php echo $form->error($model,'title'); ?>
	</div>

<?php

	// see http://www.yiiframework.com/doc/guide/1.1/en/form.table
	// Note: Can be a route to a config file too,
	//       or create a method 'getMultiModelForm()' in the member model
	
	$memberFormConfig = array(
		  'elements'=>array(
			'firstname'=>array(
				'type'=>'text',
				'maxlength'=>40,
			),
		  	'lastname'=>array(
		  		'type'=>'text',
		  		'maxlength'=>40,
		  	),
			'membersince'=>array(
				'type'=>'dropdownlist',
				//it is important to add an empty item because of new records
				'items'=>array(''=>'-',2009=>2009,2010=>2010,2011=>2011,),
			),
		));
	
	$this->widget('ext.multimodelform.MultiModelForm',array(
			'id' => 'id_member', //the unique widget id
			'formConfig' => $memberFormConfig, //the form configuration array
			'model' => $member, //instance of the form model
	
			//if submitted not empty from the controller,
			//the form will be rendered with validation errors
			'validatedItems' => $validatedMembers,
	
	        //array of member instances loaded from db
			'data' => $member->findAll('groupid=:groupId', array(':groupId'=>$model->id)),
		));
	?>
	
	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>
	
	<?php $this->endWidget(); ?>
	
	</div><!-- form -->
~~~

##Usage Validate/Save

You can split the validate() and save() methods of the Multimodelform to modify the items before saving.

~~~
[php]

	//validate formdata and populate $validatedItems/$deleteItems
	if (MultiModelForm::validate($model,$validatedItems,$deleteItems,$masterValues)) 
	{
	
	 //... alter the model attributes of $validatedItems if you need ... 
	
	 //will not execute internally validate again, because $validatedItems/$deleteItems are not empty
	 MultiModelForm::save($model,$validatedItems,$deleteItems,$masterValues);
	}

~~~

Of course you can save() without extra validate before. 
Validation will be internally done when $validatedItems/$deleteItems are empty.

~~~
[php]

	$validatedItems=array();
	
	if(isset($_POST['FORMDATA']) && MultiModelForm::save($model,$validatedItems,$deleteItems,$masterValues)) 
	{
	  //... validation and saving is ok ...
	  //redirect ...
	}
	
	//No POST data or validation error on save 
	$this->render ...
~~~



##Usage tableView

Set the property 'tableView'=>true.

~~~
[php]

    $this->widget('ext.multimodelform.MultiModelForm',array(
           ...
            'tableView' => true,
         //'tableFootCells' => array('footerCol1','footerCol2'...), //optional table footer
           ...
        ));
~~~


##Usage widget form elements

Yii allows type='AWidget' as form element.
So in your form config array you can use: 

~~~
[php]

        array(
           'elements'=>array(
                 ....
                  'lastname'=>array(
                    'type'=>'text',
                    'maxlength'=>40,
                    ),

                    'dayofbirth'=>array(
                        'type'=>'zii.widgets.jui.CJuiDatePicker',
                        'language'=>'de',
                        'options'=>array(
                            'showAnim'=>'fold',
                        ),
                   ...
                )
            )
~~~

This however needs a javascript workaround on cloning the date element.
We have to assign the CJuiDatePicker functionality to the cloned new element.

There are the properties jsBeforeClone,jsAfterClone,jsBeforeNewId,jsAfterNewId available where javascript code can be implemented. Use 'this' as the current jQuery object.

For CJuiDatePicker, the extension [datetimepicker](http://www.yiiframework.com/extension/datetimepicker/ ""),
CJuiAutoComplete and EJuiComboBox there are predefined functions available, so it's easy to make cloning date fields work.

You have to assign the property 'jsAfterNewId' to the prepared code.

Assume your form definition elements are defined in the array $formConfig.
'afterNewIdDatePicker' reads the options from the specified element and adds the 'datepicker': 

~~~
[php]

    $this->widget('ext.multimodelform.MultiModelForm',array(
           ...
             // 'jsBeforeNewId' => "alert(this.attr('id'));",
            'jsAfterNewId' => MultiModelForm::afterNewIdDatePicker($formConfig['elements']['dayofbirth']),
           ...
        ));
~~~

Now cloning of the field 'dayofbirth' should work. 

Support for

- datetimepicker: use afterNewIdDateTimePicker()
- CJuiAutoComplete: use afterNewIdAutoComplete()
- EJuiCombobox: use afterNewIdJuiComboBox()
 

For other widgets you have to find out the correct javascript code on cloning.
Please let me know if you have found a javascript code for other widgets.


##Sortable fieldsets

Set the property 'sortAttribute' to your db-field for sorting (should be an integer) if you want to order the items by drag/drop manually. 
Uses jQuery UI sortable, but works only when 'tableView' is false.
See the demo.

~~~
[php]

	$this->widget('ext.multimodelform.MultiModelForm',array(
	       ...
	        'sortAttribute' => 'position', //if assigned: sortable fieldsets is enabled
	       ...
		));
~~~


##Checkboxes

Unfortunatly the basic checkbox is not supported, because it's not so easy to handle (see the comments in the forum).

But if you need checkboxes, you can use the checkboxlist instead.
If you only need a single checkbox you can set the item-data to an array with one item.

In the view / formConfig:

~~~
[php]

	$memberFormConfig = array(
		  'elements'=>array(

             ...
            'flags'=>array(
              'type'=>'checkboxlist',
              'items'=>array('1'=>'Founder','2'=>'Developer','3'=>'Marketing'), //One single checkbox: array('1'=>'Founder')
              ),
		));
~~~

In the model you have to convert array <-> string on saving/loading - see the Member model in the demo


~~~
[php]

	//Convert the flags array to string
    public function beforeSave()
    {
        if(parent::beforeSave())
        {
            if(!empty($this->flags) && is_array($this->flags))
                $this->flags = implode(',',$this->flags);

            return true;
        }

        return false;
    }

	//Convert the flags string to array
	public function afterFind()
	{
	   $this->flags = empty($this->flags) ? array() : explode(',',$this->flags);
	}
~~~

##File fields and file upload

MultiModelForm handles all the stuff with uploading files and images.
Assigned **images** will be **displayed as preview**, **files as download link in the edit form** near the upload input.

Add a input element of type 'file' to the form config.
It's important to add *'visible'=>true* because by Yii default a file field is not safe and unsafe attributes will not be visible.

~~~
[php]

	$memberFormConfig = array(
		  'elements'=>array(
			'firstname'=>array(
				'type'=>'text',
				'maxlength'=>40,
			),
		  	...
			'image'=>array(
                  'type'=>'file',
                  'visible'=>true, //Important!
              ),
		));
~~~

Add the image field to the rules of your model.
The image is of type file, so a CFileValidator will be used and you can add allowed types, mimetypes, maxSize etc.
It's **important to set 'allowEmpty'=>true** otherwise, the user will be enforced to always upload a new image on updating a record.

~~~
[php]

	public function rules()
	{
		
		return array(
			array('firstname, lastname', 'length', 'max'=>40),
			array('image', 'file', 'allowEmpty'=>true,'types'=>'jpg,gif,png'),
            ...
		);
	}
~~~

The **file input field in the db must be of type string**: VARCHAR(200) or similar to save the relative file path.

###Default behavior

The default behavior on uploading is, that **mmf will save the uploaded file in the public webroot folder files/modelclass.** 'modelclass' is the class of the multimodel (files/member/image1.jpg).

**The relative path of the uploaded file will be assigned to the file attribute** (image, ...).

Ensure the public folder **files is writeable** like the assets folder.

**MMF takes care about unique filenames** in the folder by adding index: filename-1.jpg, filename-2.jpg ... if necessary.

###Change default behavior

You can change this default behavior by adding **callback methods** to your mmf model:

Add a method ***mmfFileDir()*** to you mmf model (member,...)

~~~
[php]
	
	public function mmfFileDir($attribute,$mmf)
	{		
		return 'media/'.strtolower(get_class($this));
	}

~~~

The $attribute can be used too, for example if there are multiple file fields in a model. 
$mmf is the MultiModelForm widget.


Maybe you need another behavior on file upload, for example you want to create image presets (resize, ...) on upload or save the file into the db.

Add a callback method ***mmfSaveUploadedFile()*** to the mmf model.
The param $uploadedFile is a CUploadedFile instance.

~~~
[php]
	
	public function mmfSaveUploadedFile($attribute,$uploadedFile,$mmf)
	{		
		if(!empty($uploadedFile))
        {
          $uploadedFile->saveAs(...);
          ... resize or save to db or whatever ...
          
          $this->$attribute = ... path to the preset or other values you like ...    
        }             
	}

~~~

If you need access to the mastermodel (group,...) inside this callback methods, you have to assign the new param 'masterModel' in the controller action.

~~~
[php]

	public function actionUpdate($id)
	{
		$model=$this->loadModel($id); //the Group model
	
		$member = new Member;
		$validatedMembers = array(); //ensure an empty array
	
		if(isset($_POST['Group']))
		{
			...

	        //the last param $model is the masterModel 'group'.
			if(MultiModelForm::save($member,$validatedMembers,$deleteMembers,$masterValues,$_POST['Member'],$model) 

            ...
		}
	...
	}
~~~

Now in your callback methods you can use ***$groupModel=$mmf->masterModel***;



###Delete files after removing items

MMF does not delete the uploaded files if items are removed by the user in the edit form.
If you want to delete the file after removing items you have to code like below:

~~~
[php]
	
	if (MultiModelForm::save($model,$validatedItems,$deleteItems,$masterValues)) 
	{
	   foreach($deleted as $deletedModel)
       {
          if(!empty($deletedModel->image) && is_file($deletedModel->image))
              unlink($deletedModel->image);
       }       	 	 
	}

~~~

###Attributes for file handling

- *fileReplaceExisting=false*: if true, delete existing files with the same name on upload
- *fileImagePreviewHtmlOptions=array('style' => 'max-width: 100px; max-height: 100px;')* the html options for the image preview tag in the edit form
- *fileLinkHtmlOptions=array('target'=>'_blank')* the htmlOptions for the download link in the edit form


##Property showAddItemOnError

Regarding to requests and workarounds in the [forum topic](http://www.yiiframework.com/forum/index.php?/topic/20289-extension-multimodelformjqrelcopy/page__p__99292__hl__multimodelform ""):

A user should not be able to add/clone items, when in error mode (model rules not passed successfully).
Now you can set the property $showAddItemOnError to false to enable this behavior.
See the demo.

##Properties allowAddItem and allowRemoveItem (since v3.1)

For example, if you only want to allow an admin user to add new items or remove items,
you can use these properties to display the addlink and the removelinks

~~~
[php]

	$this->widget('ext.multimodelform.MultiModelForm',array(
	       ...
	       'allowAddItem' => Yii::app()->user->isAdmin(),
	       'allowRemoveItem' => Yii::app()->user->hasRole('admin'),
	       ...
		));
~~~

##Bootstrap

Set the property 'bootstrapLayout'=true if you use Twitters Bootstrap CSS or one of the Yii bootstrap extensions.

The formelements/labels will be wrapped by 'control-group', 'controls', ... so that the multimodelform should be displayed correct.

##Property jsAfterCloneCallback

If you need to modify the cloned elements or execute js-actions after cloning, you can assign a js function(newElem,sourceElem) as callback.

- newElem: the new cloned jquery object
- sourceElem: the clone source object

The callback will be executed for all inputs of a record row.

Usage:

~~~
[php]

	// a js function in your view
	echo CHtml::script('function alertIds(newElem,sourceElem) { 
	    alert(newElem.attr("id"));
	    alert(sourceElem.attr("id"));}'
	);
	
	$this->widget('ext.multimodelform.MultiModelForm',array(
	  ...
	   'jsAfterCloneCallback'=>'alertIds',
	  ...
	));
~~~


##Notes

- If you **upgrade** MultiModelForm, **don't forget to delete the assets**.

- You can use **multiple MultiModelForm widgets in a view**, but the mmf **models MUST be of different classes**.
  Take care to assign a unique widget id when adding more multimodelform widgets.

- Take a look at MultiModelForm.php for more options of the widget.

- The widget never will render a form begin/end tag.
  So you have always to add $this->beginWidget('CActiveForm',...) ... $this->endWidget in the view.
  
- The implementation of the class MultiModelEmbeddedForm needs review in upcoming Yii releases.
  In 1.1.6+ the only output of CActiveForm.init() and CActiveForm.run() is the form begin and end tag.

- The extension should work for non activerecord models too: instances of CModel, CFormModel...

- Use Yii::app()->controller->action->id or $model->scenario
  to generate different forms for your needs (readonly fields on update ...)
  

##Ressources

- multimodelform on [github](https://github.com/yii-joblo/multimodelform "")

- Forum topic [multimodelform/jqrelcopy](http://www.yiiframework.com/forum/index.php?/topic/20289-extension-multimodelformjqrelcopy/page__p__99292__hl__multimodelform "")

- Tutorial [Using Form Builder](http://www.yiiframework.com/doc/guide/1.1/en/form.builder "")

- jQuery plugin [RelCopy](http://www.andresvidal.com/labs/relcopy.html "")


##Changelog
- v6.0.0 Support for filefields with handling the fileupload and preview
  - new parameter $initAttributes for method validate()
  - new parameter $masterModel for method save()
  - Added composer support / composer.json
  - internal changes and minor bugfixes 	
- v5.0 better custom js handling
  please see comments in the code; don't forget to clear assets after update  
  - new property 'removeOnClick' to exec js code on clicking the remove link
  - new property 'jsAfterCloneCallback' - usage: see documentation above
  - new property 'clearInputs' (= options['clearInputs'], default true) with bugfix on cloning 
values
  - new property 'jsRelCopy' to use your own modified jsrelcopy js-script (placed in assets folder)
- v4.5
  - added support for composite pk
  - new property 'renderForm' allows a custom 'MultiModelRenderForm'
  - changed update behavior: loads the record from the db by findByPK before update (like default behavior of actionUpdate in a controller) 
- v4.1
 - Bugfix: rendering tableView with Bootstrap layout
- v4.0
 - Bugfix: two remove columns in tableview when hideCopyTemplate=true. Many thanks to [shoemaker](http://www.yiiframework.com/forum/index.php/user/57523-shoemaker/ ""). See [this forum topic](http://www.yiiframework.com/forum/index.php/topic/20289-extension-multimodelformjqrelcopy/page__view__findpost__p__180480 "")
 - Support for Twitters Bootstrap 
 - New property 'addItemAsButton' to show a button 'Add item' instead of a link.
 - Minor bugfix in js-code
 
- v3.3 
 - Correct support for the options['limit'] property from relcopy.js. See property limitText.
- v3.2
 - New property 'hideCopyTemplate' (default:true), means that the empty copyTemplate is not visible
 - New demo with CheckBoxList
- v3.1
 - New: Added properties $allowAddItem and $allowRemoveItem
 - Bugfix: hidden fields in the Tableheader no more displayed
 - Bugfix: Strings as elements formconfig (htmlttags,CFormStringElement) have not been rendered
- v3.0
 - New: Added sortable feature for fieldsets (not in table view)
 - New: Property $showAddItemOnError=true; Show 'Add item' link and empty item in errormode
 - New: Demo with sortable fieldsets 
 - Bugfixes: visible/hidden fields

- v2.2.1 Bugfix: Array elements need extra 'allEmpty' check 
- v2.2
 - Support for array elements (checkboxlist, radiolist) in form config
   These elements didn't work on creating only on update
- v2.1.1
 - Bugfix - Labels of hidden elements have been rendered
- v2.1
 - Bugfix - tableView: Hidden input have been rendered into a cell
 - Changed parameters and handling of MultiModelForm::save
- v2.0.1 Bugfix: 
  Tableheader displayed hidden fields too.
  Better internal handling of labels, now supports required '*' 
- v2.0 Added 'tableView' and support for cloning date/time widgets
- v1.0.2 Bugfix: Detailrecord was created twice on creating master
- v1.0.1 Bugfix 'Undefined Index','Undefined variable' when error_reporting(E_ALL) is set