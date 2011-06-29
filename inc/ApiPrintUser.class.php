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

require_once dirname(__FILE__).'/Database.class.php';

/**
 * This class manage the api user
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @author  Portail Pro <contact@portailpro.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 * @package FreeApiPrint
 * @version 1.0.0
 */
class ApiPrintUser extends Database
{
  /**
   * Construct the object
   *
   * @see     Database::initDatas()
   * @access  public
   * @since   1.0.0
   */
  public function __construct()
  {
    $this->table = 'api_print_user';
    
    $this->def_datas = array(
      'email'   => PDO::PARAM_STR, // l'adresse email de l'utilisateur
      'passwd'  => PDO::PARAM_STR, // le mot de passe (pas utilisé pour l'api) de l'utilisateur
      'api_key' => PDO::PARAM_STR, // La clé privé d'utilisation de l'API
      'nb_get'  => PDO::PARAM_INT, // le nombre de fois que l'utilisateur a imprimé une page
    );
    
    $this->initDatas();
  }
  
  
  /**
   * Retrieve a user according to email and api key
   * 
   * @param   string    $email      The email of the user wanted
   * @param   string    $api_key    The api key of the user wanted
   * @return  ApiPrintUser          The user object or null if it doesn't exist
   * @access  public
   * @static
   * @since   1.0.0
   */
  public static function retrieveByEmailAndKey($email, $api_key)
  {
    if (is_string($email) === false || is_string($api_key) === false) {
      throw new Exception('email and api_key must be string');
    }
    
    $api_print_user = new ApiPrintUser();
    
    $sql  = 'SELECT * FROM '.$api_print_user->getTableName().' ';
    $sql .= 'WHERE email = :email AND api_key = :api_key';
    
    $stmt = Database::getSingleton()->prepare($sql);
    
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':api_key', $api_key, PDO::PARAM_STR);
    
    $res = $stmt->execute();
    
    if ($res === false) {
      if (DEBUG === true) {
        ob_start();
        $stmt->debugDumpParams();
        $error = ob_get_contents();
        ob_end_clean();
        throw new Exception('Error in the query : '.$error);
      }
      return null;
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result === false) {
      return null;
    }
    
    $api_print_user->load($result);
    return $api_print_user;
  }
}