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

/**
 * This class manage the HTTP response
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @author  Portail Pro <contact@portailpro.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 * @package FreeApiPrint
 * @version 1.0.0
 */
class Response
{
  /**
   * @var     array       All possibles responses status
   * @access  protected
   * @static
   * @since   1.0.0
   */
  protected static $status_text = array(
    100 => 'Continue',
    101 => 'Switching Protocols',
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => '(Unused)',
    307 => 'Temporary Redirect',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
  );
  
  
  /**
   * @var     array       The headers to send of client
   * @access  protected
   * @since   1.0.0
   */
  protected $headers = array();
  
  /**
   * @var     int         The status number to send of client (must be in self::$status_text)
   * @access  protected
   * @since   1.0.0
   */
  protected $status  = null;
  
  /**
   * @var     string    The content to send of client
   * @access  protected
   * @since   1.0.0
   */
  protected $content = null;
  
  
  /**
   * Construct the object and add default status and header
   *
   * @access  public
   * @since   1.0.0
   */
  public function __construct()
  {
    $this->status = 200;
    $this->addCustomHeader('Content-Type', 'text/html; charset=UTF-8', true);
  }
  
  
  /**
   * Define the content to send
   *
   * @param   string  $content  The content to set (string value or filename)
   * @param   bool    $is_file  True if the content is a filename, false else
   * @access  public
   * @since   1.0.0
   */
  public function setContent($content, $is_file = false)
  {
    if ($is_file === true) {
      if (file_exists($content) === false) {
        throw new Exception('File doesn\'t exist');
      }
      
      $content = file_get_contents($content);
      if ($content === false) {
        throw new Exception('Impossible to use file to complete content');
      }
    }
    
    $this->content = $content;
  }
  
  
  /**
   * Send the headers and content to client
   *
   * @access  public
   * @since   1.0.0
   */
  public function send()
  {
    $this->sendHeader();
    echo $this->content;
    exit;
  }
  
  
  /**
   * Send only the headers to client
   *
   * @access  public
   * @since   1.0.0
   */
  public function sendHeaderOnly()
  {
    $this->sendHeader();
    exit;
  }
  
  
  /**
   * Add a custom header in the HTTP response
   *
   * @param   string  $name     The name of the header
   * @param   string  $value    The value of the header
   * @param   bool    $replace  True if you want force the replace in an existing header, false else
   * @access  public
   * @since   1.0.0
   */
  public function addCustomHeader($name, $value, $replace = false)
  {
    if ($replace === false) {
      if (isset($this->headers[$name]) === true) {
        return;
      }
    }
    
    $this->headers[$name] = $value;
  }
  
  
  /**
   * Set the default headers for a download
   *
   * @param   string  $filename   The name of the file which proposed to client
   * @access  public
   * @since   1.0.0
   */
  public function setDownloadHeader($filename)
  {
    $this->addCustomHeader('Content-Type', 'application/force-download', true);
    $this->addCustomHeader('Content-disposition', 'attachment; filename="'.str_replace('"', "'", $filename).'"', true);
    $this->addCustomHeader('Content-Transfer-Encoding', 'application/octet-stream', true);
    $this->addCustomHeader('Content-Length', mb_strlen($this->content), true);
    $this->addCustomHeader('Pragma', 'no-cache', true);
    $this->addCustomHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0, public', true);
    $this->addCustomHeader('Expires', '0', true);
  }
  
  
  /**
   * Send the headers to client
   *
   * @access  public
   * @since   1.0.0
   */
  private function sendHeader()
  {
    // Construction du statut
    if (isset(Response::$status_text[$this->status]) === false) {
      throw new Exception('undefined status code');
    }
    header('HTTP/1.0 '.$this->status.' '.Response::$status_text[$this->status]);
    
    foreach ($this->headers as $name => $value) {
      header($name.': '.$value, true);
    }
  }
}