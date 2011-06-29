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
 * This class manage the print options
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @author  Portail Pro <contact@portailpro.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 * @package FreeApiPrint
 * @version 1.0.0
 */
class ApiPrintOption
{
  const REPLACE_VALUE = '%VALUE%';
  
  /**
   * Define the available option and their validator
   * 'name of option' => array(
   *                           0 => default value
   *                           1 => callable validator
   *                           2 => parameter for callable validator
   *                           3 => value if option is boolean and enable
   *                           4 => value if option is boolean and disable
   *                          ),
   *
   * @var     array     The defined of avalaible options
   * @access  private
   * @static
   * @since   1.0.0
   */
  private static $available_options = array(
    // Title of the PDF
    'title'                     => array(
      '',
      'is_string',
      null,
      null,
      null,
    ),
    
    // Print PDF with gray color
    'grayscale'                 => array(
      false,
      'is_numeric',
      null,
      '--grayscale',
      '',
    ),
    
    // Define the number of copies
    'copies'                    => array(
      1,
      'is_numeric',
      null,
      null,
      null,
    ),
    
    // Define orientation page
    'orientation'               => array(
      'Portrait',
      'preg_match',
      array('/^(Portrait|Landscape)$/', ApiPrintOption::REPLACE_VALUE),
      null,
      null,
    ),
    
    // Define pagesize
    'page-size'                 => array(
      'A4',
      'is_string',
      null,
      null,
      null,
    ),
    
    // Define the margin bottom
    'margin-bottom'             => array(
      '10mm',
      'preg_match',
      array('/^[0-9]{1,}(mm|cm|px|em)$/', ApiPrintOption::REPLACE_VALUE),
      null,
      null,
    ),
    
    // Define the margin left
    'margin-left'               => array(
      '10mm',
      'preg_match',
      array('/^[0-9]{1,}(mm|cm|px|em)$/', ApiPrintOption::REPLACE_VALUE),
      null,
      null,
    ),
    
    // Define the margin right
    'margin-right'              => array(
      '10mm',
      'preg_match',
      array('/^[0-9]{1,}(mm|cm|px|em)$/', ApiPrintOption::REPLACE_VALUE),
      null,
      null,
    ),
    
    // Define the margin top
    'margin-top'                => array(
      '10mm',
      'preg_match',
      array('/^[0-9]{1,}(mm|cm|px|em)$/', ApiPrintOption::REPLACE_VALUE),
      null,
      null,
    ),
    
    // Indicate if the PDF must be a table of contents
    'toc'                       => array(
      false,
      'is_numeric',
      null,
      '--toc',
      '',
    ),
    
    // Use the media print stylesheet
    'print-media-type'          => array(
      true,
      'is_numeric',
      null,
      '--print-media-type',
      '--no-print-media-type',
    ),
    
    // Print background color and images
    'background'                => array(
      true,
      'is_numeric',
      null,
      '--background',
      '--no-background',
    ),
    
    // Print image
    'images'                    => array(
      true,
      'is_numeric',
      null,
      '--images',
      '--no-images',
    ),
    
    // Enable the external links
    'enable-external-links'     => array(
      true,
      'is_numeric',
      null,
      '--enable-external-links',
      '--disable-external-links',
    ),
    
    // Enable the local file include
    'enable-local-file-access'  => array(
      true,
      'is_numeric',
      null,
      '--enable-local-file-access',
      '--disable-local-file-access',
    ),
  );
  
  
  
  /**
   * Check and build the options for command line
   *
   * @param   array   $options    The options to check
   * @return  string              The options in command line format
   * @access  public
   * @static
   * @since   1.0.0
   * @see     ApiPrintOption::parseOption()
   */
  public static function getOptions($options)
  {
    $options_cmdline = '';
    
    foreach ($options as $option => $value) {
      $options_cmdline .= ApiPrintOption::parseOption($option, $value);
    }
    
    return $options_cmdline;
  }
  
  
  
  /**
   * Check and build a option for command line
   *
   * @param   string   $option    The option name to check
   * @param   string   $value     The value of option to check
   * @return  string              The options in command line format
   * @access  private
   * @static
   * @since   1.0.0
   * @see     ApiPrintOption::parseOption()
   */
  private static function parseOption($option, $value)
  {
    // if option name is unknowned, throw exception
    if (isset(ApiPrintOption::$available_options[$option]) === false) {
      throw new Exception('unknown option : '.$option);
    } 
    
    $default_value    = ApiPrintOption::$available_options[$option][0];
    $check_function   = ApiPrintOption::$available_options[$option][1];
    $check_parameters = ApiPrintOption::$available_options[$option][2];
    $enable_value     = ApiPrintOption::$available_options[$option][3];
    $disable_value    = ApiPrintOption::$available_options[$option][4];
    
    // replace the value in the parameter array
    if (is_array($check_parameters) === true) {
      $key = array_search(ApiPrintOption::REPLACE_VALUE, $check_parameters);
      if ($key !== false) {
        $check_parameters[$key] = $value;
      }
    } else {
      $check_parameters = array($value);
    }
    
    // check if the check function is callable
    if (is_callable($check_function, false) === false) {
      throw new Exception('The check function isn\'t callable');
    }
    
    // execute validator
    if ((bool)call_user_func_array($check_function, $check_parameters) === false) {
      throw new Exception('The option '.$option.' doesn\'t pass the test');
    }
    
    // Check value and build command line
    if (is_bool($default_value) === true) {
      if ((bool)$value === true) {
        $option_cmdline = ' '.$enable_value;
      } else {
        $option_cmdline = ' '.$disable_value;
      }
    } else {
      $option_cmdline = ' --'.$option.' '.escapeshellarg($value);
    }
    
    return $option_cmdline;
  }
}