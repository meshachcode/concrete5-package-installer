Concrete5 Package Installer
===========================

`jollyscience_package.php` is a superclass that can be extended
by a Concrete5 package controller to automate most of the standard
install options.


This class automates most of the install of a Concrete5 Package.
It handles installing themes, blocks, attributes, single pages
page types, jobs, creating pages and hooking events. Config for
these are either stored in class properties or they are auto installed
by scanning the appropriate directory.

## Installation

1. Put `jollyscience_package.php` in `package_dir/libraries/concrete5-package-installer`
2. In your `package_dir/controller.php` file, add this line _before_ the class declaration: 
`Loader::library('concrete5-package-installer/jollyscience_package', 'your_package_handle');` 
where `your_package_handle` is the your package's handle.
3. Make sure your class extends `JollysciencePackage` instead of `Package`
4. Make sure `$pkgHandle`, `$pkgDescription`, `$appVersionRequired`, and `$pkgVersion` are set 

## Usage

### Themes

Themes included in the `package_dir/themes` directory are automatically installed

### Blocks

Blocks included in the `package_dir/blocks` directory are automatically installed

### Jobs

Jobs included in the `package_dir/jobs` directory are automatically installed

### Single Pages

Single Pages included in the `package_dir/single_pages` directory are automatically installed

### Attributes

Attributes are installed in 2 ways:

1. In the `$commonAttributes` array
2. In the `$pageTypes` array

#### CommonAttributes
`$commonAttributes` is a class property. It is an associative array used for attributes common to multiple page types, or attribtues that are not associated with any page type.

Format:

    $commonAttributes = array(
   		'attribute_handle' => array(
     	'type' => 'attribute_type',
     	'name' => 'Attribute Name'
      	)
    );

1. `attribute_handle` is a unique handle. It should be lowercase and only contain letters and underscores (_)
2. `type` is one of the following (others may be used if installed)
	* address
	* boolean (creates checkbox)
	* date_time
	* image_file (selects from File Manager)
	* number
	* rating
	* select
	* text
	* textarea
	
	For information on attribute types
	go to [Concrete5::Types](http://www.concrete5.org/documentation/developers/attributes/types/)
3. `name` is what is displayed in the Concrete5 Dashboard

Attributes of the type `select` have the following format:

    $commonAttributes = array(
       'attribute_handle' => array(
         'type' => 'select',
         'name' => 'Attribute Name',
         'selectOptions' => array(
               'Option 1',
               'Option 2',
               'Option 3'
          ),
         'selectConfig' => array(
    	          'akSelectAllowMultipleValues' => 0,
    	          'akSelectAllowOtherValues' => 0,
    	          'akSelectOptionDisplayOrder' => 'display_asc'
        	)     
        )
    )

1. `selectOptions` is a list of options for the select box
2. `selectConfig` contains additional options for the select box. 
	* `akSelectAllowMultipleValues` set to 1 gives multiple checkboxes
	instead of a select box
	* `akSelectAllowOtherValues` set to 1 allows the user to type in a different value
	* `akSelectOptionDisplayOrder` determines how the options are ordered

### Page Types

Page types are created from the `$pageTypes` property. It has the following format:
    
    $pageTypes = array(
       'page_handle' => array(
         'name' => 'Page Name',
         'attributes' => array(
         	/**
               Associative array of attributes
               with the same format as the $commonAttributes
               array. These attributes are only associated
               with this particular page
          	**/
          ),
          'attributeKeys' = array(
            /**
               List of $commonAttributes keys for attributes
               that may be assigned to multiple pages
          	**/
          )
        )
    )

### Pages
Created from `$pages` property.

    $pages = array(
       'page_handle' => array(
         'type' => 'page_type_handle',
         'name' => 'Page Name'
        )
    )


### Hooking Events

Events are hooked using the `$events` property.

For information on events and a valid list of event
names go to [Concrete5::Events](http://www.concrete5.org/documentation/developers/system/events/)

Format:
  
    $events = array(
      'event_name' => array(
        array(
          'class' => 'NameOfClassToBeLoaded',
          'method' => 'nameOfMethodToBeCalled',
          'path' => 'relative/path/to/class.php'
        )
      )
    );

_Note:_ Events call class methods in a non-object context,
so use `self::$staticVar` or `self::method()` instead of `$this->var` or `$this->method()`


Concrete5 Model Base Class
===========================
This is a base class which can be extended in projects to more easily handle basic CRUD functinality.
## Installation

1. Put `jollyscience_model.php` in `package_dir/libraries/concrete5-package-installer`
2. Create your models in `package_dir/models`
3. At the top of each model file, add this line _before_ the class declaration: 
`Loader::library('concrete5-package-installer/jollyscience_model', 'your_package_handle');` 
where `your_package_handle` is the your package's handle.
3. Make sure your class extends `JollyscienceModel`
4. Make sure `self::$tableName` and `self::$pk` are set 