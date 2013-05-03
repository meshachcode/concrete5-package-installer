<?php
/**
 * Extended Concrete5 package base class
 *
 * This class automates most of the install of a Concrete5 Package.
 * It handles installing themes, blocks, attributes, single pages
 * page types, jobs, creating pages and hooking events. Config for
 * these are either stored in class properties or they are auto installed
 * by scanning the appropriate directory.
 *
 * PHP version 5
 *
 * @package    Concrete5 Package Installer
 * @author     Sam Bernard <sam@jollyscience.com>
 * @copyright  2013 JollyScience LLC
 * @license    http://www.wtfpl.net/ WTFPL – Do What the Fuck You Want to Public License
 * @version    1.0
 * @link       https://github.com/jollyscience/concrete5-package-installer
 */

defined('C5_EXECUTE') or die(_("Access Denied."));
if(!class_exists('JollysciencePackage')):

class JollysciencePackage extends Package {

  protected $pkgDescription = 'Default package description.';

  /**
   * __construct function.
   *
   * Automatically title using package handle.
   *
   * @access public
   * @return void
   */
  public function __construct()
  {
    if (!isset($this->pkgHandle)) {
      die('$pkgHandle must be set.');
    }

    if (!isset($this->pkgHandle)) {
      die('$pkgHandle must be set.');
    }    
    
    if (!isset($this->appVersionRequired)){
      die('$appVersionRequired must be set.');
    }
    
    if (!isset($this->pkgName)) {
      $this->pkgName = ucwords(str_replace('_', ' ', $this->pkgHandle));
    }
  }

  /**
   * commonAttributes
   *
   * Associative Array used for attributes common to multiple page types
   *
   * Format:
   * $commonAttributes = array(
   *    'attribute_handle' => array(
   *      'type' => 'attribute_type',
   *      'name' => 'Attribute Name'
   *       ),
   *     )
   *
   * Format for "select" attribute type
   *
   * $commonAttributes = array(
   *    'attribute_handle' => array(
   *      'type' => 'select',
   *      'name' => 'Attribute Name',
   *      'selectOptions' => array(
   *            'Option 1',
   *            'Option 2',
   *            'Option 3'
   *       ),
   *      'selectConfig' => array(
	 *	          'akSelectAllowMultipleValues' => 0,
	 *	          'akSelectAllowOtherValues' => 0,
	 *	          'akSelectOptionDisplayOrder' => 'display_asc'
	 *     	)     
   *     )
   *
   * Default attribute types:
   * address
   * boolean (creates checkbox)
   * date_time
   * image_file (selects from File Manager)
   * number
   * rating
   * select
   * text
   * textarea
   *
   * For information on attribute types
   * go to [Concrete5::Types](http://www.concrete5.org/documentation/developers/attributes/types/)
   *
   * @var mixed
   * @access public
   */
  public $commonAttributes = array();

  /**
   * userAttributes
   *
   * Attributes that are applied to Users.
   * In the same format as $commonAttributes
   *
   * @var array
   * @access protected
   */
  protected $userAttributes = array();

  /**
   * pageTypes
   *
   * Associative Array used for generating page types. Each page
   * type has the following format:
   *
   * $pageTypes = array(
   *    'page_handle' => array(
   *      'name' => 'Page Name',
   *      'attributes' => array(
   *            Associative array of attributes
   *            with the same format as the $commonAttributes
   *            array. These attributes are only associated
   *            with this particular page
   *       ),
   *       'attributeKeys' = array(
   *            List of $commonAttributes keys for attributes
   *            that may be assigned to multiple pages
   *       )
   *     )
   * )
   *
   *
   * @var mixed
   * @access public
   */
  public $pageTypes = array();

  /**
   * pages
   *
   * Array of pages to create
   * Format:
   *
   * $pages = array(
   *    'page_handle' => array(
   *      'type' => 'page_type_handle',
   *      'name' => 'Page Name'
   *     )
   * )
   *
   * @var mixed
   * @access public
   */
  public $pages = array();


  /**
   * events
   * 
   * For information on events and a valid list of event
   * names go to [Concrete5::Events](http://www.concrete5.org/documentation/developers/system/events/)
   * 
   * Format:
   *
   * $events = array(
   *   'event_name' => array(
   *     array(
   *       'class' => 'NameOfClassToBeLoaded',
   *       'method' => 'nameOfMethodToBeCalled',
   *       'path' => 'relative/path/to/class.php'
   *     )
   *   )
   * );
   *
   * Note: Events call class methods in a non-object context,
   * so use `self::$staticVar` or `self::method()` instead of `$this->var` or `$this->method()`
   *
   */
  public $events = array();
  
  
  /**
   * on_start function.
   *
   * This function is run whenever
   * the controller is loaded. For installed
   * packages it runs on every pageload
   * bootstrapping it into the load process
   *
   * @access public
   * @return void
   */
  public function on_start()
  {
    $this->hookEvents();
  }


  /**
   * upgrade function.
   *
   * Run when upgrading the package. By default, will
   * check to make sure all page types and attributes
   * are installed
   *
   * @access public
   * @return void
   */
  public function upgrade()
  {
    Loader::model('collection_types');
    Loader::model('collection_attributes');
    Loader::model('file_set');
    Loader::model('single_page');
    $this->installThemes();
    $this->installBlocks();
    $this->installCommonAttributes();
    $this->installUserAttributes();
    $this->installSinglePages();
    $this->installPageTypes();
    $this->installPages();
    $this->installJobs();
  }


  /**
   * install function.
   * Handles theme installation, custom block template
   * install, custom page types and attributes
   * and creation of sample data
   *
   * @access public
   * @return void
   */
  public function install()
  {
    $pkg = parent::install();
    $this->upgrade();
    return $pkg;
  }


  /**
   * installCommonAttributes function.
   * 
   * Installs Attributes that may not
   * be bound to a page type, and adds
   * them to an Attribute Collection named
   * after the package
   *
   * @access public
   * @return void
   */
  public function installCommonAttributes(){
    if(!empty($this->commonAttributes)){
      $pkg = parent::getByHandle($this->pkgHandle);
      $att_coll = AttributeKeyCategory::getByHandle('collection');
      $att_set = AttributeSet::getByHandle($handle);
      if (!is_object($att_set)) {
        $att_set = $att_coll->addSet($this->pkgHandle, t($this->pkgName), $pkg);
      }    
    
      $this->installAttributes($this->commonAttributes, $pageTypes = array(), $att_set);
    }
  }

  /**
   * installPageTypes function.
   *
   * @access public
   * @return void
   */
  public function installPageTypes()
  {
    $pkg = parent::getByHandle($this->pkgHandle);
    foreach ($this->pageTypes as $handle => $type) {
      $attributes = null;
      if (!empty($type['attributes'])) {
        $attributes = $type['attributes'];
      }

      if (!empty($type['attributeKeys'])) {
        foreach ($type['attributeKeys'] as $attributeKey) {
          if (!empty($this->commonAttributes[$attributeKey])) {
            $attributes[$attributeKey] = $this->commonAttributes[$attributeKey];
          }
        }
      }
      $pt = $this->createPageType($handle, $type['name'], $attributes);

      if (is_object($pt)) {
        if (!empty($type['composer'])) {
          if (!empty($type['composer']['under_type'])) {
            $composerParentType = CollectionType::getByHandle($type['composer']['under_type']);

            if (is_object($composerParentType)) {
              $pt->saveComposerPublishTargetPageType($composerParentType);

              $composerAttrIds=array();
              foreach ( $pt->getAvailableAttributeKeys() as $key ) {
                $composerAttrIds[]=$key->getAttributeKeyID();
              }
              $pt->saveComposerAttributeKeys($composerAttrIds);
            }
          }
        }
      }
    }

  }


  /**
   * installPages function.
   *
   * @access public
   * @return void
   */
  public function installPages()
  {
    foreach ($this->pages as $handle => $page) {
      $this->createPage($handle, $page['name'], $page['type']);
    }
  }


  /**
   * installThemes function.
   *
   * Installs themes by scanning `themes` directory
   *
   * @access public
   * @return void
   */
  public function installThemes()
  {
    $pkg = parent::getByHandle($this->pkgHandle);

    $themes = array();
    $path = DIRNAME_PACKAGES . DIRECTORY_SEPARATOR . $this->pkgHandle . DIRECTORY_SEPARATOR . 'themes';

    if(file_exists($path)){
      $fileinfos = new DirectoryIterator($path);
      foreach ($fileinfos as $fileInfo) {
        if ($fileInfo->isDot()) continue;
        if (!$fileInfo->isDir()) continue;
        $themes[] = $fileInfo->getFilename();
      }
  
  
      if (!empty($themes)) {
        foreach ($themes as $handle) {
          PageTheme::add($handle, $pkg);
        }
      }
    
    }
  }


  /**
   * installBlocks function.
   *
   * Installs blocks by scanning `blocks` directory
   *
   * @access public
   * @return void
   */
  public function installBlocks()
  {
    $pkg = parent::getByHandle($this->pkgHandle);

    $blocks = array();
    $path = DIRNAME_PACKAGES . DIRECTORY_SEPARATOR . $this->pkgHandle . DIRECTORY_SEPARATOR . 'blocks';
    
    
    if(file_exists($path)){
      $fileinfos = new DirectoryIterator($path);
  
      foreach ($fileinfos as $fileInfo) {
        if ($fileInfo->isDot()) continue;
        if (!$fileInfo->isDir()) continue;
        
        $blocks[] = $fileInfo->getFilename();
      }
  
      if (!empty($blocks)) {
        foreach ($blocks as $handle) {
          $block = BlockType::getByHandle($handle, $pkg);
          if (!is_object($block))
            BlockType::installBlockTypeFromPackage($handle, $pkg);
        }
      }
    }
  }


  /**
   * installSinglePages function.
   *
   * Installs single pages by scanning `single_pages` directory
   *
   * @access public
   * @return void
   */
  public function installSinglePages()
  {
    Loader::model('single_page');
    $pkg = parent::getByHandle($this->pkgHandle);

    $singlePages = array();
    $path = DIRNAME_PACKAGES . DIRECTORY_SEPARATOR . $this->pkgHandle .  DIRECTORY_SEPARATOR . 'single_pages';

    if(file_exists($path)){

      $fileinfos = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path)
      );
  
      foreach ($fileinfos as $pathname => $fileinfo) {
        if (!$fileinfo->isFile()) continue;
        $pathname = str_replace(array($path, 'view.php', '.php'), '', $pathname);
  /*       $pathname = rtrim($pathname, '/'); */
        
        if (substr($pathname, 0, 1) !== '/') {
          $pathname = '/'.$pathname;
        }
  
        $singlePages[] = $pathname;
      }
      
      
      if (!empty($singlePages)) {
        foreach ($singlePages as $path) {
          SinglePage::add($path, $pkg);
        }
      }
    }
  }

  /**
   * overrideSinglePage function.
   * 
   * Override's a core single page with the 
   * packages version.
   *
   * @access public
   * @param string $path
   * @return void
   */
  public function overrideSinglePage($path)
  {
    $pkg = parent::getByHandle($this->pkgHandle);
    // Override /profile page
    $cID = Page::getByPath($path)->getCollectionID();
    
    if($cID && $cID !== 1){
      Loader::db()->execute('update Pages set pkgID = ? where cID = ?', array($pkg->pkgID, $cID));
    }    
  }

  /**
   * unOverrideSinglePage function.
   * 
   * UnOverride's a core single page so that
   * it uses the core version.
   *
   * @access public
   * @param string $path
   * @return void
   */
  public function unOverrideSinglePage($path)
  {
    $cID = Page::getByPath($path)->getCollectionID();
    
    if($cID && $cID !== 1){
      Loader::db()->execute('update Pages set pkgID = NULL where cID = ?', array($cID));
    }    
  }

  /**
   * Scans `jobs` directory and installs found jobs
   */
  public function installJobs()
  {
    $pkg = parent::getByHandle($this->pkgHandle);
    $path = DIRNAME_PACKAGES . DIRECTORY_SEPARATOR . $this->pkgHandle . DIRECTORY_SEPARATOR . 'jobs';

    if(file_exists($path)){

      $fileinfos = new DirectoryIterator($path);
  
      $jobs = array();
  
      foreach ($fileinfos as $fileInfo) {
        if ($fileInfo->isDot()) continue;
        if (!$fileInfo->isFile()) continue;
        $jobs[] = str_replace('.php', '', $fileInfo->getFilename());
      }
      
      if (!empty($jobs)) {
        Loader::model("job");
  
        foreach ($jobs as $handle) {
          $job = Job::getByHandle($handle);
          if (empty($job) || !is_object($job)) {
            Job::installByPackage($handle, $pkg);
          }
        }
      }
    }
  }


  /**
   * createPage function.
   *
   * Creates a page
   *
   * @access public
   * @param mixed $handle
   * @param mixed $name
   * @param mixed $type
   * @param mixed $parentPage. (default: null)
   * @return void
   */
  public function createPage($handle, $name, $type, $parentPage = null)
  {
    Loader::model('collection_types');

    if (!$parentPage->cID) {
      $parentPage = Page::getByID(1); //home page
    }

    $newPage = Page::getByPath($parentPage->cPath . "/" . $handle);

    if (!is_object($newPage) || !$newPage->cID) {
      $data = array(
        'name' => $name,
        'cHandle' => $handle,
        'cDescription' => ""
      );

      $pt = CollectionType::getByHandle($type);

      if (is_object($pt)) {
        $parentPage->add($pt, $data);
      }
    }
  }


  /**
   * createPageType function.
   *
   * Creates a Page Type and assigns any attributes
   * to it.
   *
   * @access public
   * @return void
   */
  public function createPageType($handle, $name, $attributes = null)
  {
    $pkg = parent::getByHandle($this->pkgHandle);

    $pageType = CollectionType::getByHandle($handle);

    if (!is_object($pageType)) {
      $data['ctHandle'] = $handle;
      $data['ctName'] = $name;
      $pageType = CollectionType::add($data, $pkg);
    }

    if(is_object($pageType)){
      $pageType->populateAvailableAttributeKeys();
    }

    if (is_object($pageType) && is_array($attributes) and !empty($attributes)) {
      $att_coll = AttributeKeyCategory::getByHandle('collection');
      $att_set = AttributeSet::getByHandle($handle);
      if (!is_object($att_set)) {
        $att_set = $att_coll->addSet($handle, t($name), $pkg);
      }

      if (is_object($att_set)) {
        $this->installAttributes($attributes, $pageType, $att_set);
      }
    }

    return $pageType;
  }


  /**
   * installAttributes function.
   * Create custom attributes and assign them to a page.
   * Optionally add them to an attribute set.
   *
   * @access public
   * @param mixed $attributes
   * @param mixed $pageTypes
   * @param mixed $att_set. (default: null)
   * @return void
   */
  public function installAttributes($attributes, $pageTypes = array(), $att_set = null)
  {
    $pkg = parent::getByHandle($this->pkgHandle);
    
    if (!is_array($pageTypes)) {
      $pageTypes = array($pageTypes);
    }

    foreach ($attributes as $handle => $attribute) {
      $attr = CollectionAttributeKey::getByHandle($handle);
      if (!$attr) {
        $options = array(
          'akHandle' => $handle,
          'akName' => t($attribute['name']),
          'akIsSearchable' => true,
          'akShowSubpages' => true
        );

        if (!empty($attribute['options'])) {
          $options = array_merge($options, $attribute['options']);
        }

        $attr = CollectionAttributeKey::add(
          AttributeType::getByHandle($attribute['type']),
          array(
            'akHandle' => $handle,
            'akName' => t($attribute['name']),
            'akIsSearchable' => true,
            'akShowSubpages' => true
          ),
          $pkg);

        if (!empty($pageTypes)) {
          foreach ($pageTypes as $pageType) {
            if (is_object($pageType)) {
              $pageType->assignCollectionAttribute($attr);
            }
          }
        }
      }

      if (is_object($attr)) {

        if (is_object($att_set)) {
          $attr->setAttributeSet($att_set);
        }

        if ($attribute['type'] == 'select' && !empty($attribute['selectOptions'])) {
          
          if(empty($attribute['selectConfig'])){
            $attribute['selectConfig'] = array();
          }
          
          $this->addSelectOptions($attr, $attribute['selectConfig'], $attribute['selectOptions']);
        }

        if (!empty($pageTypes)) {
          foreach ($pageTypes as $pageType) {
            $install = true;
            if (is_object($pageType)) {
              $availableAttributes = $pageType->getAvailableAttributeKeys();

              foreach ($availableAttributes as $availableAttribute) {
                if ($availableAttribute->akHandle == $handle) {
                  $install = false;
                }
              }

              if ($install) {
                $pageType->assignCollectionAttribute($attr);
              }
            }
          }
        }
      }
    }
  }

  /**
   * installUserAttributes function.
   * Create custom user attributes.
   *
   * @access public
   * @param mixed $attributes
   * @return void
   */
  public function installUserAttributes($attributes = null)
  {
    // If no attributes passed then take the default user attributes
    if (empty($attributes)) {
      $attributes = $this->userAttributes;
    }

    $pkg = parent::getByHandle($this->pkgHandle);

    foreach ($attributes as $handle => $attribute) {
      $attr = UserAttributeKey::getByHandle($handle);

      $options = array(
        'akHandle' => $handle,
        'akName' => t($attribute['name']),
      );

      if (!empty($attribute['options'])) {
        $options = array_merge($options, $attribute['options']);
      }

      if (!is_object($attr)) {
        $attr = UserAttributeKey::add(
          AttributeType::getByHandle($attribute['type']),
          $options,
          $pkg);
      } else {
        $attr->update($options);
      }

      if (is_object($attr)) {
        if ($attribute['type'] == 'select' && !empty($attribute['selectOptions'])) {

          if (empty($attribute['selectConfig'])) {
            $attribute['selectConfig'] = array();
          }

          $this->addSelectOptions($attr, $attribute['selectConfig'], $attribute['selectOptions']);
        }
      }
    }
  }//end installUserAttributes()

  /**
   * Handles creating select options for Select Attribute Type
   */

	public function addSelectOptions($ak, $data, $selectOptions) {
		$defaults = array(
		  'akSelectAllowMultipleValues' => 0,
		  'akSelectAllowOtherValues' => 0,
		  'akSelectOptionDisplayOrder' => 'display_asc'
		);
		
		$data = array_merge($defaults, $data);
		
		$db = Loader::db();

		$initialOptionSet = array();//$attr->getOptions();
		
		$akSelectAllowMultipleValues = $data['akSelectAllowMultipleValues'];
		$akSelectAllowOtherValues = $data['akSelectAllowOtherValues'];
		$akSelectOptionDisplayOrder = $data['akSelectOptionDisplayOrder'];
		
		if ($data['akSelectAllowMultipleValues'] != 1) {
			$akSelectAllowMultipleValues = 0;
		}
		if ($data['akSelectAllowOtherValues'] != 1) {
			$akSelectAllowOtherValues = 0;
		}
		if (!in_array($data['akSelectOptionDisplayOrder'], array('display_asc', 'alpha_asc', 'popularity_desc'))) {
			$akSelectOptionDisplayOrder = 'display_asc';
		}
				
		// now we have a collection attribute key object above.
		$db->Replace('atSelectSettings', array(
			'akID' => $ak->getAttributeKeyID(), 
			'akSelectAllowMultipleValues' => $akSelectAllowMultipleValues, 
			'akSelectAllowOtherValues' => $akSelectAllowOtherValues,
			'akSelectOptionDisplayOrder' => $akSelectOptionDisplayOrder
		), array('akID'), true);
		
		// Now we add the options
		$newOptionSet = new SelectAttributeTypeOptionList();
		$displayOrder = 0;
		foreach($selectOptions as $option) {
			$opt = SelectAttributeTypeOption::getByValue(t($option));
			
			if(!is_object($opt)){
		    $opt = SelectAttributeTypeOption::add($ak, t($option));
			}
			
			if ($akSelectOptionDisplayOrder == 'display_asc') {
				$opt->setDisplayOrder($displayOrder);
			}
			$newOptionSet->add($opt);
			$displayOrder++;
		}
		
		// Now we remove all options that appear in the 
		// old values list but not in the new
		foreach($initialOptionSet as $iopt) {
			if (!$newOptionSet->contains($iopt)) {
				$iopt->delete();
			}
		}
	}

  /**
   * updatePageType function.
   *
   * Used to change a page type. Primarily used
   * for changing the type of already created pages
   * such as the hom epage
   *
   * @access public
   * @param mixed $c
   * @param mixed $ctID
   * @param mixed $theme. (default: null)
   * @return void
   */
  function updatePageType($c, $ctID, $theme = null)
  {
    $nvc = $c->getVersionToModify();

    if (is_object($theme)) {
      $nvc->setTheme($theme);
    }

    $data = array();
    if (!$c->isGeneratedCollection()) {
      if ($ctID) {
        if ($c->getCollectionID() > 1) {
          Loader::model('collection_types');
          $ct = CollectionType::getByID($ctid);
        }

        $data['ctID'] = $ctID;
        $data['approved'] = 1;
        $nvc->update($data);
        $nvc->getVersionObject()->approve();
      }

    }
  }
  
  public function hookEvents(){
    if(!empty($this->events)){
      foreach($this->events as $event => $eventActions){
        foreach($eventActions as $action){
          $path = 'packages' . DIRECTORY_SEPARATOR . $this->pkgHandle . DIRECTORY_SEPARATOR .$action['path'];
          Events::extend($event, $action['class'], $action['method'],  $path);
        }
      }
    }
  }
}

endif;