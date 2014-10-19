<?php

$all = @$_REQUEST['all'];


if ($all) {
    $file = __DIR__.'/orders/orders_all.xml';
} else {
    $file = __DIR__.'/orders/orders.xml';
    if (file_exists($file)) {
        exit();
    }
}



set_time_limit(0);
ini_set('memory_limit', '1024M');
include_once "../app/Mage.php";
umask (0);
//Mage::app('default');
$app = Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$orderids = array();

$orders = Mage::getModel('sales/order')->getCollection();

$poArray = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Orders></Orders>');

$total = 0;
foreach ($orders as $order) {
    
//    var_dump($order->getData());
//    exit();
    
    if (($order->getData('ext_order_id') =='1C') && ($all == false )) continue;
    
    $total++;
    // order id
    $orderid = $order->getIncrementId();

    // add child element to the SimpleXML object
    $pOrder = $poArray->addChild('Order');

    // addresses
    $shippingAddress = $order->getShippingAddress();
    $billingAddress = $order->getBillingAddress();
    
    // Add attributes to the SimpleXML element
    $pOrder->addChild('Number', $orderid);
    $pOrder->addChild('status', $order->getStatus());
    $pOrder->addChild('payment', $order->getPayment()->getData('method'));
    $pOrder->addChild('createdAt', $order->getCreatedAt());
    $pOrder->addChild('quoteId', $order->getQuoteId());
    $pOrder->addChild('taxAmount', $order->getTaxAmount());
    $pOrder->addChild('discountAmount', $order->getDiscountAmount());
    $pOrder->addChild('shipping_description', $order->getShippingDescription());
    $pOrder->addChild('shippingInclTax', $order->getShippingInclTax());
    $pOrder->addChild('grandTotal', $order->getGrandTotal());
    
    $customer = $pOrder->addChild('Customer');     
    $customer->addChild('customerId', $order->getCustomerId());
    $customer->addChild('fistName', $order->getCustomerFirstname());
    $customer->addChild('secondName', $order->getCustomerLastname());
    $customer->addChild('company', $order->getCompany());
    $customer->addChild('email', $order->getCustomerEmail());
    $customer->addChild('phone', $order->getPhone());
    $customer->addChild('currency', $order->getOrderCurrencyCode());
    $customer->addChild('shippingAddressId', $order->getShippingAddressId());    
    
    $shipping = $pOrder->addChild('Shipping');    
    $shipping->addChild('fistName', $shippingAddress->getFirstname());
    $shipping->addChild('secondName', $shippingAddress->getLastname());
    $shipping->addChild('company', $shippingAddress->getCompany());
    $shipping->addChild('email', $shippingAddress->getEmail());
    $shipping->addChild('phone', $shippingAddress->getTelephone());
    $shipping->addChild('address1', $shippingAddress->getStreet(1));
    $shipping->addChild('address2', $shippingAddress->getStreet(2));
    $shipping->addChild('address3', $shippingAddress->getStreet(3));
    $shipping->addChild('city',  $shippingAddress->getCity());
    $shipping->addChild('region', $shippingAddress->getRegion());
    $shipping->addChild('zip', $shippingAddress->getPostcode());
    $shipping->addChild('country', $shippingAddress->getCountry_id());
    
    $billing = $pOrder->addChild('Billing');    
    $billing->addChild('address1', $billingAddress->getStreet(1));
    $billing->addChild('address2', $billingAddress->getStreet(2));
    $billing->addChild('address3', $billingAddress->getStreet(3));
    $billing->addChild('city', $billingAddress->getCity());
    $billing->addChild('region', $billingAddress->getRegion());
    $billing->addChild('zip', $billingAddress->getPostcode());
    $billing->addChild('country', $billingAddress->getCountry_id());

    $pItems = $pOrder->addChild('Rows');
    $items = $order->getItemsCollection();

    // loop through the order items
    foreach ($items AS $itemid => $item) {
        $pItem = $pItems->addChild('Row');
        $pItem->addChild('createdAt', $item->getCreatedAt());
        $pItem->addChild('productId', $item->getProductId());
        $pItem->addChild('orderId', $orderid);
        $pItem->addChild('sku', $item->getSku());
        $pItem->addChild('name', $item->getName());
        $pItem->addChild('price', $item->getPrice());
        $pItem->addChild('tax', $item->getTaxAmount());
        $pItem->addChild('discount', $item->getDiscount());
        $pItem->addChild('qty', $item->getQtyOrdered());
    }
    // add the id to the order ids array
    $orderids[] = $orderid;
    $order->setData('ext_order_id','1C');
    $order->save();
}


file_put_contents($file, $poArray->asXML());
echo 'Exported orders: '.$total;
?>

<!--

[0]=>  "canceled" => "Canceled"
[1]=>  "closed" => "Closed"
[2]=>  "complete" => "Complete"
[3]=>  "fraud" => "Suspected Fraud"
[4]=>  "holded" => "On Hold"
[5]=>  "payment_review" =>  "Payment Review"
[6]=>  "pending" => "Pending"
[7]=>  "pending_payment" => "Pending Payment"
[8]=>  "pending_paypal" => "Pending PayPal"
[9]=>  "processing" =>  "Processing"

-->
