<?php
#
# Copyright (c) 2011, Leblanc Simon <contact@leblanc-simon.eu>
# All rights reserved.
# 
# Redistribution and use in source and binary forms, with or without modification,
# are permitted provided that the following conditions are met:
# 
# Redistributions of source code must retain the above copyright notice, this
# list of conditions and the following disclaimer.
# Redistributions in binary form must reproduce the above copyright notice, this
# list of conditions and the following disclaimer in the documentation and/or
# other materials provided with the distribution.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
# AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
# IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
# FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
# DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
# SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
# OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
# OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#

require_once dirname(__FILE__).'/config.inc.php';


/**
 * This class manage the database connection
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @author  Portail Pro <contact@portailpro.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 * @package FreeApiPrint
 * @version 1.0.0
 */
abstract class Database
{
  /**
   * @var     PDO   The PDO object use to interact with database
   * @access  protected
   * @static
   * @since   1.0.0
   */
  protected static $db  = null;
  
  /**
   * @var     PDOStatment   The PDOStatment object use to manipulate SQL query
   * @access  protected
   * @since   1.0.0
   */
  protected $stmt       = null;
  
  
  /**
   * @var     int         The id of the object
   * @access  protected
   * @since   1.0.0
   */
  protected $id         = 0;
  
  /**
   * @var     array         The meta attribute use to store the value of children attribute
   * @access  protected
   * @since   1.0.0
   */
  protected $datas      = array();
  
  /**
   * @var     array         The meta attribute use to store the type of children attribute (PDO::PARAM_*)
   * @access  protected
   * @since   1.0.0
   */
  protected $def_datas  = null;
  
  /**
   * @var     string        The name of the SQL table to use
   * @access  protected
   * @since   1.0.0
   */
  protected $table      = null;
  
  
  /**
   * Get the PDO object
   *
   * @return  PDO     The PDO object to use for access in database
   * @access  public
   * @static
   * @since   1.0.0
   */
  public static function getSingleton()
  {
    if (self::$db === null) {
      self::$db = self::getConnection();
    }
    
    return self::$db;
  }
  
  
  /**
   * Initialize the PDO connection
   *
   * @return  PDO     The PDO object to use for access in database
   * @access  protected
   * @static
   * @since   1.0.0
   */
  protected static function getConnection()
  {
    if (substr(DSN, 0, 5) === 'mysql') {
      $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
    } else {
      $options = array();
    }
    
    if (DB_USER === null) {
      // SQLite
      $db = new PDO(DSN);
    } else {
      $db = new PDO(DSN, DB_USER, DB_PASS, $options);
    }
    
    return $db;
  }
  
  
  /**
   * Initialize the datas of the children class
   *
   * @return  void
   * @access  protected
   * @since   1.0.0
   */
  protected function initDatas()
  {
    $this->id = 0;
    
    $this->datas = array();
    
    foreach ($this->def_datas as $key => $value) {
      if ($value === PDO::PARAM_INT) {
        $this->datas[$key] = 0;
      } elseif ($value === PDO::PARAM_STR) {
        $this->datas[$key] = '';
      }
    }
  }
  
  
  /**
   * Check the type of the parameter and return the casted value
   *
   * @param   string    $name   The name of the parameter (defined in children class)
   * @param   mixed     $value  The value to check
   * @return  mixed             The casted value
   * @access  protected
   * @since   1.0.0
   */
  protected function checkType($name, $value)
  {
    if (isset($this->def_datas[$name]) === false) {
      throw new Exception($name.' doesn\'t exist !');
    }
    
    $type = $this->def_datas[$name];
    
    switch ($type) {
      case PDO::PARAM_INT:
        if (is_numeric($value) === false) {
          throw new Exception('Wrong type : '.$name.' must be numeric');
        }
        return (int)$value;
      
      case PDO::PARAM_STR:
        if (is_string($value) === false) {
          throw new Exception('Wrong type : '.$name.' must be string');
        }
        return (string)$value;
        
      default:
        throw new Exception('Wrong type !');
    }
  }
  
  
  /**
   * Save the children object in database
   *
   * @return  self
   * @access  public
   * @since   1.0.0
   */
  public function save()
  {
    if ($this->getId() === 0) {
      $sql = 'INSERT INTO';
    } else {
      $sql = 'UPDATE';
    }
    
    $sql = $this->prepareQuery($sql);
    
    if ($this->getId() === 0) {
      $this->stmt = self::getSingleton()->prepare($sql);
      $this->bind();
    } else {
      $sql .= ' WHERE id = :id';
      $this->stmt = self::getSingleton()->prepare($sql);
      $this->bind();
      $this->stmt->bindValue(':id', $this->getId(), PDO::PARAM_INT);
    }
    
    if ($this->stmt->execute() === true) {
      if ($this->getId() === 0) {
        $this->setId(self::getSingleton()->lastInsertId());
      }
      return $this;
    } else {
      return false;
    }
  }
  
  
  /**
   * Build the sql to prepare for save (insert or update)
   *
   * @param   string  $begin  The begin of the SQL query
   * @return  string          The SQL query to send in prepare method
   * @access  protected
   * @since   1.0.0
   */
  protected function prepareQuery($begin)
  {
    $sql = $begin.' '.$this->table;
    
    $sql .= ' SET ';
    
    $num_column = 0;
    foreach ($this->def_datas as $key => $value) {
      if ($num_column++ > 0) {
        $sql .= ', ';
      }
      
      $sql .= $key.' = :'.$key;
    }
    
    return $sql;
  }
  
  
  /**
   * Bind the SQL query with the value of the object
   *
   * @return  void
   * @access  protected
   * @since   1.0.0
   */
  protected function bind()
  {
    foreach ($this->def_datas as $key => $value) {
      $this->stmt->bindValue(':'.$key, call_user_func(array($this, 'get'.self::getCamelCase($key)), $value));
    }
  }
  
  
  /**
   * Load a SQL item in the object by id or array
   *
   * @param   mixed   $value    The id of the row (int) or an array to populate the object
   * @return  self
   * @access  protected
   * @since   1.0.0
   */
  public function load($value)
  {
    if (is_numeric($value) === true) {
      return $this->loadById($value);
    } elseif (is_array($value) === true) {
      return $this->loadByArray($value);
    }
    
    throw new Exception('Impossible to load the data');
  }
  
  
  /**
   * Populate the object with a array
   *
   * @param   array     $values    an array to populate the object
   * @return  self
   * @access  protected
   * @since   1.0.0
   */
  protected function loadByArray($values)
  {
    foreach ($this->def_datas as $key => $value) {
      if (isset($values[$key]) === false) {
        throw new Exception('Error in the data loading');
      }
      
      $this->datas[$key] = $values[$key];
    }
    
    $this->id = $values['id'];
    
    return $this;
  }
  
  
  /**
   * Load a SQL item in the object by id
   *
   * @param   int       $value    The id of the row (int)
   * @return  self
   * @access  protected
   * @since   1.0.0
   */
  protected function loadById($id)
  {
    if (is_numeric($id) === false) {
      throw new Exception('Wrong type for id : impossible to load !');
    }
    
    $sql = 'SELECT * FROM '.$this->table.' WHERE id = :id';
    
    $this->stmt = self::getSingleton()->prepare($sql);
    $this->stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    $this->stmt->execute();
    
    $result = $this->stmt->fetch(PDO::FETCH_ASSOC);
    if ($result === false) {
      throw new Exception('Impossible to get the data !');
    }
    
    foreach ($this->def_datas as $key => $value) {
      $this->datas[$key] = $result[$key];
    }
    $this->id = $result['id'];
    
    return $this;
  }
  
  
  /**
   * Convert a string in CamelCase
   *
   * @param   string      $column     The string to convert
   * @return  string                  The string converted in CamelCase mode
   * @access  protected
   * @static
   * @since   1.0.0
   */
  protected static function getCamelCase($column)
  {
    return ucwords(preg_replace("/(\_(.))/e", "strtoupper('\\2')", strtolower($column)));
  }
  
  
  /**
   * Convert a string in upper camel case to underscore mode
   *
   * @param   string  $string   the string to convert
   * @return  string            the string converted in underscore mode
   * @access  protected
   * @static
   * @since   1.0.0
   */
  protected static function revertUpperCamelCase($string)
  {
    if(empty($string) === true){
      return $string;
    }

    if(strlen($string) > 1) {
      $string = strtolower(substr($string, 0, 1)).substr($string, 1);
    }

    return preg_replace("/([A-Z])/e", "'_'.strtolower('\\1')", $string);
  }
  
  
  /**
   * Getter for the id
   *
   * @return  int     The id of the object
   * @access  public
   * @since   1.0.0
   */
  public function getId()
  {
    return $this->id;
  }
  
  
  /**
   * Setter for the id
   *
   * @param   int     The id of the object
   * @return  self
   * @access  public
   * @since   1.0.0
   */
  public function setId($value)
  {
    if (is_numeric($value) === false) {
      throw new Exception('id must be numeric');
    }
    
    $this->id = (int)$value;
    
    return $this;
  }
  
  
  /**
   * Get the SQL table name to use
   *
   * @return  string    The table name to use
   * @access  public
   * @since   1.0.0
   */
  public function getTableName()
  {
    return $this->table;
  }
  
  
  /**
   * Magic method __call is use for the setter and getter
   *
   * @param   string  $name       The name of the called method
   * @param   array   $arguments  The method arguments
   * @return  mixed               the value (if getter) of the attribute or self (if setter)
   * @access  public
   * @since   1.0.0
   */
  public function __call($name, $arguments = array())
  {
    $prefix = substr($name, 0, 3);
    
    switch ($prefix) {
      case 'get':
        $name = self::revertUpperCamelCase(substr($name, 3));
        if (isset($this->datas[$name]) === false) {
          throw new RuntimeException('Fatal error : call to undefined method '.__CLASS__.'::'.$name);
        }
        
        return $this->datas[$name];
      
      case 'set':
        $name = self::revertUpperCamelCase(substr($name, 3));
        if (isset($this->datas[$name]) === false) {
          throw new RuntimeException('Fatal error : call to undefined method '.__CLASS__.'::'.$name);
        }
        
        if (count($arguments) !== 1) {
          throw new RuntimeException('Fatal error : call to undefined method '.__CLASS__.'::'.$name);
        }
        
        $this->datas[$name] = $this->checkType($name, $arguments[0]);
        return $this;
      
      default:
        throw new RuntimeException('Fatal error : call to undefined method '.__CLASS__.'::'.$name);
    }
  }
}