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

require_once dirname(__FILE__).'/inc/config.inc.php';
require_once dirname(__FILE__).'/inc/ApiPrint.class.php';
require_once dirname(__FILE__).'/inc/ApiPrintUser.class.php';
require_once dirname(__FILE__).'/inc/ApiPrintResponse.class.php';

// Prepare response
$response = new ApiPrintResponse();

// Check headers
if (isset($_SERVER['HTTP_X_API_EMAIL']) === false || isset($_SERVER['HTTP_X_API_TOKEN']) === false) {
  $response->sendCallError();
}

try {
  // Get the user
  $api_user = ApiPrintUser::retrieveByEmailAndKey($_SERVER['HTTP_X_API_EMAIL'], $_SERVER['HTTP_X_API_TOKEN']);
  if ($api_user === null) {
    $response->sendLoginError();
  }
  
  // Check URL or HTML content
  $md5 = null;
  $url = null;
  $content = null;
  $options = array();
  
  if (isset($_POST['url']) === true && is_string($_POST['url']) === true && empty($_POST['url']) === false) {
    $url = (string)$_POST['url'];
    $md5 = md5($url);
  } elseif (isset($_POST['content']) === true && is_string($_POST['content']) === true && empty($_POST['content']) === false) {
    $content = (string)$_POST['content'];
    $md5 = md5($content);
  }
  
  if ($md5 === null) {
    $response->sendCallError('URL ou contenu manquant');
  }
  
  // Start print to PDF : get an older print if it exists
  try {
    $api_print = ApiPrint::retrieveByUserAndMd5($api_user, $md5);
  } catch (Exception $e) {
    $response->sendCallError('Impossible to get content');
  }
  
  // New print...
  if ($api_print === null) {
    $api_print = new ApiPrint();
    $api_print->setIdUser($api_user->getId());
    if ($url !== null) {
      $api_print->setUrl($url);
    } elseif ($content !== null) {
      $api_print->setContent($content);
    } else {
      $response->sendDisastrousError('Logical error : no content, no url');
    }
    $api_print->setMd5($md5);
    $api_print->setNb(0);
  }
  
  // Print to PDF
  try {
    // Check the options
    if (isset($_POST['options']) === true && is_array($_POST['options']) === true) {
      $options = $_POST['options'];
    }
    
    $pdf = $api_print->printPage($options);
    $api_print->save();
  } catch (Exception $e) {
    $response->sendTransformError($e->getMessage());
  }
  
  // Send PDF
  $response->setContent($pdf, true);
  $response->setDownloadHeader('transform.pdf');
  $response->send();
  
} catch (Exception $e) {
  if (DEBUG) {
    $response->sendDisastrousError($e->getMessage());
  } else {
    $response->sendDisastrousError();
  }
}