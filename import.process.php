<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Los_Angeles');

define('IMPORT_LOCK', __DIR__.'/import.lock');
define('PRODUCTS_DIR', __DIR__.'/products/');
define('PRODUCTS_DIR_HISTORY', __DIR__.'/products_history/');
define('EMAIL_TO', 'info@website.com');
define('EMAIL_SUBJECT', '1C PRODUCT IMPORT '. date("Y-m-d H:i:s", time()));
define('EMAIL_HEADERS', "From: noreply@website.com \r\n" .
//    "Bcc: volgodark@gmail.com\r\n".
    "Reply-To: noreply@website.com \r\n" .
    "Content-type: text/html; charset=UTF-8 \r\n");


// Check, is import busy
if (file_exists(IMPORT_LOCK)) {

    // Delete file if time more than 2200 seconds
    $fileTime = filemtime(IMPORT_LOCK);
    if ((time() - $fileTime) > 2200 ) {
        unlink(IMPORT_LOCK);
    } else {
        echo 'import in process';
        exit();
    }

}

function timer()
{
    $time = explode(' ', microtime());
    return $time[0]+$time[1];
}    
$beginning = timer();  


include_once 'scan.class.php';
include_once 'import.class.php';
include_once 'updateAttributes.php';
$Scan = new Scan();
$Import = new Import();


    
$xml_files = $Scan->ScanDirByTime(PRODUCTS_DIR);
if (!$xml_files) exit('no files');
foreach ($xml_files as $_file) {
    break;
}

define('IMPORT_FILE', $_file);
    

    /*
     * Mage class
     * 
     */
    set_time_limit(0);
    ini_set('memory_limit', '1024M');
    include_once "../app/Mage.php";

    $app = Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
    $products = $Scan->BuildArrayFromXML(PRODUCTS_DIR.'/'.IMPORT_FILE);
    $xmlProducts = count($products);
    $total = 0;
    $productIds = array();
    $log = 'IMPORT FILE: '.IMPORT_FILE.'<br/>';



if ($xmlProducts) {

    touch(IMPORT_LOCK);

    foreach ($products as $_product) {

        $total ++;
        $res = $Import->ImportProduct($_product);
        $productIds[] = $res['id'];
        $log.= 'SKU: '.$res['code'].'<br/>';

//        updateProductStores($productIds);
//        if ($total > 3) {
//            echo $log;
//            echo 'Total products: '. $total.'<br/>';
//            echo 'Spent time: '. round(timer()-$beginning,6);
//            exit();
//        }

    }

    updateProductStores($productIds);


    // var_dump($productIds);
    // Remove store specific vars


    unlink(IMPORT_LOCK);


    rename(PRODUCTS_DIR.'/'.IMPORT_FILE, PRODUCTS_DIR_HISTORY.'/'.IMPORT_FILE);
    $log.='File: '. IMPORT_FILE.'<br/>';
    $log.='Total products: '. $total.'<br/>';
    $log.='Spent time: '. round(timer()-$beginning,6);
    echo $log;

    mail(EMAIL_TO, EMAIL_SUBJECT, $log, EMAIL_HEADERS);

}




?>