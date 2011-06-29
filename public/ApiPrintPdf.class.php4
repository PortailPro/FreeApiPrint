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
 * This class manage the print into PDF via API print
 * Example :
 * <code>
 * $print = new ApiPrintPdf();
 * $print->setService('http://simon.leblanc.portailpro.net/api_print/index.php');
 * $print->setEmail('admin@example.com');
 * $print->setApiKey('api token');
 * $print->setUrl('http://example.com');
 * $print->setOptions(array('grayscale' => true));
 * $res = $print->callApi();
 * if ($res === true) {
 *   $print->download('mon test.pdf');
 * }
 * </code>
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @author  Portail Pro <contact@portailpro.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 * @package FreeApiPrint
 * @version 1.0.0
 */
class ApiPrintPdf
{
  /**
   * @var     string  The service's URL to use to convert HTML to PDF
   * @access  private
   * @since   1.0.0
   */
  var $service  = null;
  
  /**
   * @var     string  The email (login) to use for the identification
   * @access  private
   * @since   1.0.0
   */
  var $email    = null;
  
  /**
   * @var     string  The key of the API to use for the identification
   * @access  private
   * @since   1.0.0
   */
  var $api_key  = null;
  
  /**
   * @var     string  The URL to convert to PDF
   * @access  private
   * @since   1.0.0
   */
  var $url      = null;
  
  /**
   * @var     string  The HTML content to convert to PDF
   * @access  private
   * @since   1.0.0
   */
  var $content  = null;
  
  /**
   * @var     array   The options to use by the API
   * @access  private
   * @since   1.0.0
   */
  var $options  = null;
  
  
  /**
   * @var     array   The errors
   * @access  private
   * @since   1.0.0
   */
  var $errors   = array();
  
  
  /**
   * @var     string   The PDF result of the API call
   * @access  private
   * @since   1.0.0
   */
  var $pdf      = null;
  
  
  
  /**
   * Construct the object
   *
   * @param   string  $service    The service's URL to use to convert HTML to PDF
   * @param   string  $email      The email (login) to use for the identification
   * @param   string  $api_key    The key of the API to use for the identification
   * @param   string  $url        The URL to convert to PDF
   * @param   string  $content    The HTML content to convert to PDF
   * @access  public
   * @since   1.0.0
   */
  function __contruct($service = null, $email = null, $api_key = null, $url = null, $content = null)
  {
    if ($email !== null) {
      $this->setEmail($email);
    }
    if ($api_key !== null) {
      $this->setApiKey($api_key);
    }
    if ($url !== null) {
      $this->setUrl($url);
    }
    if ($content !== null) {
      $this->setContent($content);
    }
  }
  
  
  /**
   * Launch the API call
   *
   * @return  bool    True if the convert is ok, false else
   * @access  public
   * @since   1.0.0
   */
  function callApi()
  {
    if ($this->checkBeforeCall() === false) {
      return $this->errors;
    }
    
    $input = array();
    if ($this->url !== null) {
      $input['url'] = $this->url;
    } elseif ($this->content !== null) {
      $input['content'] = $this->content;
    }
    
    if ($this->options !== null) {
      foreach ($this->options as $key => $value) {
        $input['options['.$key.']'] = (is_bool($value) === true) ? (int)$value : $value;
      }
    }
    
    $curl = curl_init();
    
    curl_setopt($curl, CURLOPT_URL, $this->service);
    
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Api-Email: '.$this->email, 'X-Api-Token: '.$this->api_key));
    
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $input);
    
    curl_setopt($curl, CURLOPT_USERAGENT, 'ApiPrintPdf');
    
    $response       = curl_exec($curl);
    $headers        = curl_getinfo($curl);
    $error_number   = curl_errno($curl);
    $error_message  = curl_error($curl);
    
    curl_close($curl);
    
    if ($error_number === 0 && $headers['http_code'] === 200) {
      $this->pdf = $response;
      return true;
    } else {
      $this->errors[] = $response;
      return false;
    }
  }
  
  
  /**
   * Send header and content of the converted PDF to force client to download the file
   *
   * @param   string  $filename     The name of the file which proposed to client
   * @access  public
   * @since   1.0.0
   */
  function download($filename = 'api_print.pdf')
  {
    header('Content-type: application/force-download');
    header('Content-disposition: attachment; filename="'.str_replace('"', "'", $filename).'"');
    header('Content-Transfer-Encoding: application/octet-stream');
    header('Content-Length:'.mb_strlen($this->pdf));
    header('Pragma: no-cache');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0, public');
    header('Expires: 0');
    die($this->pdf);
  }
  
  
  /**
   * Save the converted PDF 
   *
   * @param   string  $filename     The path where you want save the PDF file
   * @return  bool                  True if the save is ok, false else
   * @access  public
   * @since   1.0.0
   */
  function save($filename)
  {
    if (file_put_contents($filename, $this->pdf) === false) {
      return false;
    }
    
    return true;
  }
  
  
  /**
   * Return all errors (when API is called or in check parameter)
   *
   * @return  array     All the errors
   * @access  public
   * @since   1.0.0
   */
  function getErrors()
  {
    return $this->errors;
  }
  
  
  /**
   * Define the service's URL to use to convert HTML to PDF
   *
   * @param   string  $service    The service's URL to use to convert HTML to PDF
   * @access  public
   * @since   1.0.0
   */
  function setService($service)
  {
    $this->service = $service;
  }
  
  
  /**
   * Define the email (login) to use for the identification
   *
   * @param   string  $email    The email (login) to use for the identification
   * @access  public
   * @since   1.0.0
   */
  function setEmail($email)
  {
    $this->email = $email;
  }
  
  
  /**
   * Define the key of the API to use for the identification
   *
   * @param   string  $api_key    The key of the API to use for the identification
   * @access  public
   * @since   1.0.0
   */
  function setApiKey($api_key)
  {
    $this->api_key = $api_key;
  }
  
  
  /**
   * Define the URL to convert to PDF
   *
   * @param   string  $url    The URL to convert to PDF
   * @access  public
   * @since   1.0.0
   */
  function setUrl($url)
  {
    $this->url = $url;
  }
  
  
  /**
   * Define the HTML content to convert to PDF
   *
   * @param   string  $content    The HTML content to convert to PDF
   * @access  public
   * @since   1.0.0
   */
  function setContent($content)
  {
    $this->content = $content;
  }
  
  
  /**
   * Define the options to use by the API
   *
   * @param   array   $options    The options to use by the API
   * @access  public
   * @since   1.0.0
   */
  function setOptions($options)
  {
    if (is_array($options) === false) {
      trigger_error('options must be an array', E_USER_ERROR);
    }
    
    $this->options = $options;
  }
  
  
  /**
   * Check the defined attribute before call the API
   *
   * @return  bool      true if all it's ok, false else
   * @access  private
   * @since   1.0.0
   */
  function checkBeforeCall()
  {
    $this->errors = array();
    $not_null = array('service', 'email', 'api_key', );
    
    foreach ($not_null as $check) {
      if ($this->$check === null || empty($this->$check) === true) {
        $this->errors[] = $check.' can\'t be null';
      }
    }
    
    if ($this->url === null && $this->content === null) {
      $this->errors[] = 'you must defined url or content';
    }
    
    if (count($this->errors) > 0) {
      return false;
    }
    
    return true;
  }
}

// For PHP 4, define the file_put_contents function
if(!function_exists('file_put_contents')) {
 function file_put_contents($filename, $data, $file_append = false) {
  $fp = fopen($filename, (!$file_append ? 'w+' : 'a+'));
  if(!$fp) {
   trigger_error('file_put_contents can\'t write in the file.', E_USER_ERROR);
   return;
  }
  $fwrite = fwrite($fp, $data);
  if ($fwrite === false) {
    return false;
  }
  fclose($fp);
  return $fwrite;
 }
}