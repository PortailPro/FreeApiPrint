Installation
============

* install the SQL base to execute install.sql file
* download the [wkhtmltopdf binary](http://code.google.com/p/wkhtmltopdf/) in the exec folder
* copy inc/config.inc.php-dist file to inc/config.inc.php
* edit the inc/config.inc.php to set your parameters (PDO database, Print binary path, ...)
  
Using server side
=================

* each user must be defined in the api_print_user table :
  - email et api_key must be use in the api call to convert the HTML to PDF
  - passwd will be use when this application will have a registration system

Using client side
=================

* use the class inside the "public" folder to connect in the API
* add a media print stylesheet in your HTML file to print
* tips : in the media print stylesheet, import the media screen stylesheet if you want only override the default stylesheet (with @import in the media screen stylesheet)

Example to call the PDF print :

``` php
// Setup the API call
$print = new ApiPrintPdf();
$print->setService('[URL of the API]');
$print->setEmail('[email of the API account]');
$print->setApiKey('[API key of the API account]');
$print->setUrl('[URL to print]'); // or $print->setContent('[HTML content to print]');

// You can define some options (see inc/ApiPrintOption.class.php to known availables options)
$print->setOptions(array('grayscale' => true, 'margin-top' => '5mm'));

// call the API
$res = $print->callApi();
if ($res === true) {
  // for save the file
  $print->save('/path/my-awesome-pdf.pdf');
  // for the download by client
  $print->download('my awesome PDF.pdf');
}
``` 

Credit
======
The project can't exists without [wkhtmltopdf](http://code.google.com/p/wkhtmltopdf/). Thanks to them

License
=======

FreeApiPrint is release under [BSD license](http://www.opensource.org/licenses/bsd-license.php "A fucking awesome license")

Authors
=======
Simon Leblanc <contact@leblanc-simon.eu> with the agreement of my fabulous company [Portail Pro](http://www.portailpro.net)