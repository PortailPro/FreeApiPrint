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

require_once dirname(__FILE__).'/Response.class.php';

/**
 * This class manage the response of the webservice
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @author  Portail Pro <contact@portailpro.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 * @package FreeApiPrint
 * @version 1.0.0
 */
class ApiPrintResponse extends Response
{
  /**
   * Construct the object
   *
   * @access  public
   * @since   1.0.0
   */
  public function __construct()
  {
    parent::__construct();
  }
  
  
  /**
   * Send an HTTP error header (405) : params checking failed
   *
   * @param   string  $message  The content to send
   * @access  public
   * @since   1.0.0
   */
  public function sendCallError($message = null)
  {
    $content = 'Error while process the request';
    
    if ($message !== null) {
      $content .= "\n".$message;
    }
    
    $this->setError(405, $content);
    $this->send();
  }
  
  
  /**
   * Send an HTTP error header (401) : login failed
   *
   * @param   string  $message  The content to send
   * @access  public
   * @since   1.0.0
   */
  public function sendLoginError($message = null)
  {
    $content = 'Error while process the login';
    
    if ($message !== null) {
      $content .= "\n".$message;
    }
    
    $this->setError(401, $content);
    $this->send();
  }
  
  
  /**
   * Send an HTTP error header (503) : transform failed
   *
   * @param   string  $message  The content to send
   * @access  public
   * @since   1.0.0
   */
  public function sendTransformError($message = null)
  {
    $content = 'Error while process the transform URL to PDF';
    
    if ($message !== null) {
      $content .= "\n".$message;
    }
    
    $this->setError(503, $content);
    $this->send();
  }
  
  
  /**
   * Send an HTTP error header (500) : unknown error (error in this program)
   *
   * @param   string  $message  The content to send
   * @access  public
   * @since   1.0.0
   */
  public function sendDisastrousError($message = null)
  {
    $content = 'A disastrous error occurred';
    
    if ($message !== null) {
      $content .= "\n".$message;
    }
    
    $this->setError(500, $content);
    $this->send();
  }
  
  
  /**
   * Prepare header status and HTML content for error
   *
   * @param   int     $code     The error number (header HTTP status)
   * @param   string  $message  The content to send
   * @access  private
   * @since   1.0.0
   */
  private function setError($code, $message)
  {
    if (is_numeric($code) === false) {
      throw new Exception('code must be numeric');
    }
    
    $code = (int)$code;
    
    if (isset(Response::$status_text[$code]) === false) {
      throw new Exception('undefined code');
    }
    
    $this->status = $code;
    $this->content = $message;
  }
}