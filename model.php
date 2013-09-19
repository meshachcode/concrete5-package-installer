<?php
/**
 * Concrete5  Model Base
 *
 * This class handles basic CRUD operations for C5. It implements a basic
 * `findBy`/`getBy` magic method which can be used to get a record by a condition.
 * Any subclass must define both a `$pk` and a `$tableName` static vars. `$fields` is
 * an optional static array that includes the table's field list. If not set, this will
 * be generated the first timw using mysql `DESCRIBE` functionality, and cached for 
 * subsequent calls.
 *
 * PHP version 5.3
 *
 * @package    Concrete5 Model Base
 * @author     Sam Bernard <sam@jollyscience.com>
 * @copyright  2013 JollyScience LLC
 * @license    http://www.wtfpl.net/ WTFPL – Do What the Fuck You Want to Public License
 * @version    1.0
 * @link       https://github.com/jollyscience/concrete5-package-installer
 */

defined("C5_EXECUTE") or die("Access Denied.");

if(!class_exists('JollyscienceModel')):

class JollyscienceModel extends Object {

  /**
   * checks that static vars have been set, loads fields using
   * `DESCRIBE` if `self::$fields` is not defined.
   */
  public function __construct(){
    if(!isset(static::$tableName) || empty(static::$tableName)){
      throw new Exception("static var tableName not set");
    }
    
    if(!isset(static::$pk) || empty(static::$pk)){
      throw new Exception("static var pk not set");
    }

    if(!isset(static::$fields) || empty(static::$fields)){
      $fields = Cache::get('model', get_called_class());

      if(empty($fields)){
        $db = Loader::db();
        $data = $db->getAll(sprintf("DESCRIBE %s", static::$tableName));
        $fields = array();
        foreach($data as $field){
          if($field['Field'] != static::$pk){
            array_push($fields, $field['Field']);
          }
        }

        
        Cache::set('model', get_called_class(), $fields, $expire = false);
      }

      static::$fields = $fields;
    }

  }

  /**
   * implements `findBy`/`getBy` magic methods
   */
  public static function __callStatic($name, $arguments){
    if(!empty($arguments) && strpos($name, 'findBy') === 0 || strpos($name, 'getBy') === 0){
        $field = str_replace('findBy', '', $name);
        $field = str_replace('getBy', '', $field);

      return static::getBy($field, $arguments[0]);
    }  
  }

  /**
   * gets a row by a specific field and value
   * @param  string $field the field to search by
   * @param  mixed $value the value of the field to search by
   * @return object instance of called class if row is found
   */
  static function getBy($field, $value){
    if(strtolower($field) == 'id'){
      $field = static::$pk;
    }

    $db = Loader::db();
    $data = $db->getRow(sprintf("SELECT * FROM %s WHERE %s = ?", static::$tableName, $field), array($value));

    $class = get_called_class();

    if (!empty($data)) {
      $instance = new $class();
      $instance->setPropertiesFromArray($data);
    }
    return (is_a($instance, $class)) ? $instance : false;    
  }

  /**
   * proxy function for getBy
   * @param  string $field the field to search by
   * @param  mixed $value the value of the field to search by
   * @return object instance of called class if row is found
   */
  static function findBy($field, $value){
    return static::getBy($field, $value);
  }  

  /**
   * deletes the database row defined by the current instance's primary key value
   * @return object ADODB result of query
   */
  public function delete()
  {
    $db = Loader::db();
    return $db->execute(sprintf("DELETE FROM %s WHERE %s = ?", static::$tableName, static::$pk), array($this->getPK()));
  }

  /**
   * inserts a table row, validates that fields are in `self::$fields`
   * @param  array $data key-value pairs of table fields/values.
   * @return object instance of called class if row is created successfully
   */
  public static function add($data)
  {
    $db = Loader::db();
    $placeholders = array();

    foreach($data as $key => $value){
      if(!in_array($key, static::$fields) && $key != static::$pk){
        unset($data[$key]);
      } else {
        $placeholders[] = '?';
      }

    }

    $fields = array_keys($data);
    $fields = implode(', ', $fields);

    $placeholders = implode(', ', $placeholders);

    $vals = array_values($data);

    $db->query(sprintf('INSERT INTO %s (%s) VALUES (%s)', static::$tableName, $fields, $placeholders), $vals);
    $id = $db->_insertID();

    if (intval($id) > 0) {
      return static::getByID($id);
    } else {
      return false;
    }
  }

  /**
   * updates a table row, validates that fields are in `self::$fields`
   * @param  array $data key-value pairs of table fields/values.
   * @return object instance of called class if row is updated successfully
   */
  public function save($data)
  {
    $vals = array();
    $command = array();

    foreach($data as $key => $value){
      if(in_array($key, static::$fields)|| $key == static::$pk){
        $command[] =  sprintf(' `%s` = ? ', $key);
        $vals[] = $value;
      }
    }
    
    $command = implode(', ', $command);
    $vals[] =  $this->getPK();
    $db = Loader::db();
    $db->query(sprintf("UPDATE %s SET %s WHERE %s = ?", static::$tableName, $command, static::$pk), $vals);
    $instance = static::getByID($this->getPK());
    return (is_a($instance, get_called_class())) ? $instance : false;
  }

  /**
   * Utility functions get value of instance primary key
   * @return mixed primary key (int or string)
   */
  public function getPK()
  {
    return $this->{static::$pk};
  }


}

endif;