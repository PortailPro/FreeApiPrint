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
require_once dirname(__FILE__).'/ApiPrintOption.class.php';

/**
 * This class manage the print into PDF
 *
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 * @author  Portail Pro <contact@portailpro.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 * @package FreeApiPrint
 * @version 1.0.0
 */
class ApiPrint extends Database
{
  /**
   * @var     string   The HTML temporary folder
   * @access  private
   * @static
   * @since   1.0.0
   */
  private static $html_tmp_folder = null;
  
  /**
   * @var     string   The PDF temporary folder
   * @access  private
   * @static
   * @since   1.0.0
   */
  private static $pdf_tmp_folder  = null;
  
  /**
   * @var     array   The default options using to print in to PDF
   * @access  private
   * @static
   * @since   1.0.0
   */
  private static $default_options = array(
    'title'                     => '',
    'grayscale'                 => 0,
    'copies'                    => 1,
    'orientation'               => 'Portrait',
    'page-size'                 => 'A4',
    'margin-bottom'             => '10mm',
    'margin-left'               => '10mm',
    'margin-right'              => '10mm',
    'margin-top'                => '10mm',
    'toc'                       => 0,
    'print-media-type'          => 1,
    'background'                => 1,
    'images'                    => 1,
    'enable-external-links'     => 1,
    'enable-local-file-access'  => 1,
  );
  
  
  /**
   * Construct the object
   *
   * @see     Database::initDatas()
   * @access  public
   * @since   1.0.0
   */
  public function __construct()
  {
    $this->table = 'api_print';
    
    $this->def_datas = array(
      'id_user' => PDO::PARAM_INT, // l'identifiant de l'utilisateur appellant la page
      'url'     => PDO::PARAM_STR, // l'URL de la page à imprimer
      'content' => PDO::PARAM_STR, // le contenu de la page à imprimer (si pas d'URL)
      'md5'     => PDO::PARAM_STR, // le md5 du contenu de la page ou de l'url
      'nb'      => PDO::PARAM_INT, // le nombre de fois que la page a été imprimé
    );
    
    $this->initDatas();
  }
  
  
  /**
   * Retrieve a print according to user and md5
   * 
   * @param   ApiPrintUser  $user  The API user
   * @param   string        $md5   The md5 of the URL or content
   * @return  ApiPrint              The print object or null if it doesn't exist
   * @access  public
   * @static
   * @since   1.0.0
   */
  public static function retrieveByUserAndMd5(ApiPrintUser $user, $md5)
  {
    if (is_string($md5) === false || empty($md5) === true) {
      throw new Exception('MD5 must be a no empty string');
    }
    
    $api_print = new ApiPrint();
    
    $sql  = 'SELECT * FROM '.$api_print->getTableName().' ';
    $sql .= 'WHERE id_user = :id_user AND md5 = :md5';
    
    $stmt = Database::getSingleton()->prepare($sql);
    
    $stmt->bindValue(':id_user', $user->getId(), PDO::PARAM_INT);
    $stmt->bindValue(':md5', $md5, PDO::PARAM_STR);
    
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
    
    $api_print->load($result);
    return $api_print;
  }
  
  
  /**
   * Convert the api-user choice to PDF
   * 
   * @param   array   $options  The options table
   * @return  mixed             The absolute PDF file (with path) or false when convert failed
   * @access  public
   * @see     ApiPrint::printUrl()
   * @see     ApiPrint::printContent()
   * @since   1.0.0
   */
  public function printPage($options = array())
  {
    $url = $this->getUrl();
    $content = $this->getContent();
    
    // merge options with default options
    $options = array_merge(ApiPrint::$default_options, $options);
    
    // if cache enable, get cache file
    if (PRINT_ENABLE_CACHE === true) {
      $result = $this->getCached($options);
    } else {
      $result = null;
    }
    
    // No cache return, convert
    if ($result === null) {
      if (empty($url) === false) {
        $result = $this->printUrl($options);
      } elseif (empty($content) === false) {
        $result = $this->printContent($options);
      }
    }
    
    if ($result === false) {
      throw new Exception('Fail to print');
    }
    
    // increase the number of print
    $this->setNb($this->getNb() + 1);
    
    return $result;
  }
  
  
  /**
   * Get the cache filename if it exists and is valid
   * 
   * @param   array   $options  The options table
   * @return  mixed             The absolute PDF file (with path) or null if cache doesn't exists
   * @access  private
   * @since   1.0.0
   */
  private function getCached($options = array())
  {
    $md5 = $this->getMd5();
    
    if (empty($md5) === true) {
      throw new Exception('md5 can\'t be empty');
    }
    
    $filename = ApiPrint::getPdfTmpFilename($md5, $options);
    
    if (file_exists($filename) === true && filemtime($filename) > time() - PRINT_TIME_CACHE) {
      return $filename;
    }
    
    return null;
  }
  
  
  /**
   * Convert URL to PDF
   * 
   * @param   array   $options  The options table
   * @return  mixed             The absolute PDF file (with path) or false when convert failed
   * @access  private
   * @see     ApiPrint::printCmd()
   * @since   1.0.0
   */
  private function printUrl($options = array())
  {
    $url = $this->getUrl();
    $md5 = $this->getMd5();
    
    if (empty($url) === true || empty($md5) === true) {
      throw new Exception('url and md5 can\'t be empty');
    }
    
    // printUrl allow only http or https protocol
    if ((bool)preg_match('/^https?:/', $url) === false) {
      throw new Exception('url must be HTTP protocole');
    }
    
    $pdf_filename = ApiPrint::getPdfTmpFilename($md5, $options);
    
    return $this->printCmd($url, $pdf_filename, $options);
  }
  
  
  /**
   * Convert HTML content to PDF
   * 
   * @param   array   $options  The options table
   * @return  mixed             The absolute PDF file (with path) or false when convert failed
   * @access  private
   * @see     ApiPrint::printCmd()
   * @since   1.0.0
   */
  private function printContent($options = array())
  {
    $content = $this->getContent();
    $md5 = $this->getMd5();
    
    if (empty($content) === true || empty($md5) === true) {
      throw new Exception('Content and md5 can\'t be empty');
    }
    
    $html_filename = ApiPrint::getHtmlTmpFolder().'/'.$md5.'.html';
    if (file_put_contents($html_filename, $content) === false) {
      throw new Exception('unable to create HTML tmp file');
    }
    
    $pdf_filename = ApiPrint::getPdfTmpFilename($md5, $options);
    
    return $this->printCmd($html_filename, $pdf_filename, $options);
  }
  
  
  /**
   * Convert HTML file to PDF
   * 
   * @param   string  $url      The URL (local or remote) to convert
   * @param   string  $pdf      The absolute path where save the PDF
   * @param   array   $options  The options table
   * @return  mixed             The absolute PDF file (with path) or false when convert failed
   * @access  private
   * @since   1.0.0
   */
  private function printCmd($url, $pdf, $options = array())
  {
    if (empty($url) === true || empty($pdf) === true) {
      throw new Exception('url and pdf can\'t be empty');
    }
    
    if (file_exists(PRINT_BIN) === false) {
      throw new Exception('the convert program doesn\'t exists');
    }
    
    if (file_exists($pdf) === true) {
      // On supprime l'ancien PDF avant
      unlink($pdf);
    }
    
    $command = escapeshellcmd(PRINT_BIN);
    
    // Ajout des options si necessaire
    $command .= ApiPrintOption::getOptions($options);
    
    $command .= ' '.PRINT_CONSTANT_OPTIONS;
    
    $command .= ' '.escapeshellarg($url).' '.escapeshellarg($pdf);
    
    $res = exec($command, $output, $return);
    if ($return !== 0) {
      if (DEBUG === true) {
        throw new Exception('Error in command line : '.$command."\n".$res."\n".print_r($output, true));
      }
      return false;
    } else {
      return $pdf;
    }
  }
  
  
  /**
   * Get the HTML temporary folder (when api user give HTML content)
   * And create it if necessary
   * 
   * @return  string            The absolute HTML temporary folder's path
   * @access  private
   * @static
   * @since   1.0.0
   */
  private static function getHtmlTmpFolder()
  {
    if (ApiPrint::$html_tmp_folder === null) {
      $folder = PRINT_TMP.'/html';
      if (file_exists($folder) === false || is_dir($folder) === false) {
        if (mkdir($folder, 0777, true) === false) {
          throw new Exception('Impossible to create HTML temp folder');
        }
      }
      ApiPrint::$html_tmp_folder = $folder;
    }
    
    return ApiPrint::$html_tmp_folder;
  }
  
  
  /**
   * Get the PDF temporary folder and create it if necessary
   * 
   * @return  string            The absolute PDF temporary folder's path
   * @access  private
   * @static
   * @since   1.0.0
   */
  private static function getPdfTmpFolder()
  {
    if (ApiPrint::$pdf_tmp_folder === null) {
      $folder = PRINT_TMP.'/pdf';
      if (file_exists($folder) === false || is_dir($folder) === false) {
        if (mkdir($folder, 0777, true) === false) {
          throw new Exception('Impossible to create HTML temp folder');
        }
      }
      ApiPrint::$pdf_tmp_folder = $folder;
    }
    
    return ApiPrint::$pdf_tmp_folder;
  }
  
  
  /**
   * Generate the PDF filename according to md5 and options
   * 
   * @param   string  $md5      The md5 of the URL or content
   * @param   array   $options  The options table
   * @return  string            The absolute PDF file (with path)
   * @access  private
   * @static
   * @since   1.0.0
   */
  private static function getPdfTmpFilename($md5, $options = array())
  {
    $folder = ApiPrint::getPdfTmpFolder();
    $filename = sha1($md5.serialize($options));
    
    return $folder.'/'.$filename.'.pdf';
  }
}