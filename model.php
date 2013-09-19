<?php

defined("C5_EXECUTE") or die("Access Denied.");

if(!class_exists('JollyscienceModel')):

class JollyscienceModel extends Object {
  public static $fields = array();

  public static function __callStatic($name, $arguments){
    if(!empty($arguments) && strpos($name, 'findBy') === 0 || strpos($name, 'getBy') === 0){
        $field = str_replace('findBy', '', $name);
        $field = str_replace('getBy', '', $field);

      return static::getBy($field, $arguments[0]);
    }  
  }


  public function __construct(){
    $class = get_called_class();
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

  static function findBy($field, $value){
    return static::getBy($field, $value);
  }

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

  public function delete()
  {
    $db = Loader::db();
    $db->execute(sprintf("DELETE FROM %s WHERE %s = ?", static::$tableName, static::$pk), array($this->getPK()));
  }


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

  public function getPK()
  {
    return $this->{static::$pk};
  }


}

endif;