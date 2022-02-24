<?php
/*
Description: PHP class file for WordPress+WooCommerce based webshop to generate XML output from orders for Naturasoft software
Version: 2021.07.12.
Usage:
// generate XML if URL contains naturasoft.xml
if(strpos($_SERVER['REQUEST_URI'], 'naturasoft.xml') !== false)
{
	add_action('template_redirect', function()
	{
		include_once('classes/WPRenterWooCommerceNaturasoft.class.php');
		new WPRenterWooCommerceNaturasoft('echoXML');
	});
}
*/
if(!class_exists('WPRenterWooCommerceNaturasoft'))
{
	class WPRenterWooCommerceNaturasoft
	{
		public function __construct()
		{
			echo 'alma';
		}

	}
	/*
	require_once('Polygons_Web_Naturasoft.class.php');
	include_once(WP_PLUGIN_DIR.'/woocommerce/woocommerce.php');
	class WPRenterWooCommerceNaturasoft extends Polygons_Web_Naturasoft
	{
		public $Polygons_Web_WordPress_WooCommerce;
		public function __construct($action)
		{
			require_once('Polygons_Web_WordPress_WooCommerce.class.php');
			$this->Polygons_Web_WordPress_WooCommerce = new Polygons_Web_WordPress_WooCommerce();
			parent::__construct();
			$this->fromID = get_option('naturasoftlastorderid', $this->fromID);
			switch($action)
			{
				case 'getXML': $this->getXML(); break;
				default: $this->echoXML(); break;
			}
		}

		function getOrderCustomerDetailsForNaturasoft()
		{
			$CustomerDetails = $this->Polygons_Web_WordPress_WooCommerce->getCustomerDetails($this->fromID);
			$HeaderVevoData = array();
			$HeaderVevoData['Customer_User'] = $CustomerDetails->get_id();
			$HeaderVevoData['Billing_Full_Name'] = $CustomerDetails->get_billing_last_name().' '.$CustomerDetails->get_billing_first_name();
			$HeaderVevoData['Billing_State'] = $CustomerDetails->get_billing_state();
			$HeaderVevoData['Billing_Postcode'] = $CustomerDetails->get_billing_postcode();
			$HeaderVevoData['Billing_City'] = $CustomerDetails->get_billing_city();
			$HeaderVevoData['Billing_Address_1'] = $CustomerDetails->get_billing_address_1().' '.$CustomerDetails->get_billing_address_2();
			$HeaderVevoData['Billing_Address_2'] = $CustomerDetails->get_billing_address_2();
			$HeaderVevoData['Shipping_Full_Name'] = $CustomerDetails->get_shipping_last_name().' '.$CustomerDetails->get_shipping_first_name();
			$HeaderVevoData['Shipping_State'] = $CustomerDetails->get_shipping_state();
			$HeaderVevoData['Shipping_Postcode'] = $CustomerDetails->get_shipping_postcode();
			$HeaderVevoData['Shipping_Address_1'] = $CustomerDetails->get_shipping_address_1();
			$HeaderVevoData['Shipping_Address_2'] = $CustomerDetails->get_shipping_address_2();
			$HeaderVevoData['Shipping_City'] = $CustomerDetails->get_shipping_city();
			$HeaderVevoData['Billing_Phone'] = $CustomerDetails->get_billing_phone();
			$HeaderVevoData['Billing_Email'] = $CustomerDetails->get_billing_email();
			$HeaderVevoData['Adoszam'] = '';
			return $HeaderVevoData;
		}
		protected function getFirstOrderId()
		{
			return $this->Polygons_Web_WordPress_WooCommerce->getFirstOrderId();
		}
		function getOrderInfosForNaturaSoft($orderID)
		{
			$order = new WC_Order($this->fromID);
			$OrderInfos = array();
			$OrderInfos['Order_Number'] = $orderID;
			$OrderInfos['Order_Date'] = date('Y.m.d. H:i:s', strtotime($order->get_date_created()));
			$OrderInfos['Order_Status'] = $order->get_status();
			$OrderInfos['Payment_Method_Title'] = $order->get_payment_method_title();
			$OrderInfos['Shipping_Method_Title'] =  $order->get_shipping_method();
			$OrderInfos['Order_Notes'] =  $order->get_customer_note();
			$OrderInfos['Customer_Ip_Address'] = $order->get_customer_ip_address();
			return $OrderInfos;
		}
		protected function getStartID()
		{
			return $this->Polygons_Web_WordPress_WooCommerce->getFirstOrderId();
		}
		protected function getStopId()
		{
			return $this->Polygons_Web_WordPress_WooCommerce->getLastOrderId();
		}

		function getItemsForNaturasoft($orderID)
		{
			$order = new WC_Order($orderID);

			$ItemsArray = array();
			// add order items to return
			if(sizeof($order->get_items()) > 0)
			{
				$i = 0;
				foreach($order->get_items() as $itemID=>$item)
				{
					// create product object to get some fix data, e.g. SKU
					$product = $order->get_product_from_item( $item );
					$sku = $product->get_sku();

					$ItemsArray[$i]['Name'] = $item['name'];
					$ItemsArray[$i]['Termekkod'] = $item['product_id'];
					$ItemsArray[$i]['Sku'] = $sku;
					$ItemsArray[$i]['Qty'] = $item['qty'];
					$ItemsArray[$i]['Mee'] = 'db';
					$ItemsArray[$i]['Item_Price'] = $item['subtotal'];

					$itemsubtotal = $item['subtotal']==0 ? 1 : $item['subtotal'];
					$ItemsArray[$i]['AFA'] = $item['subtotal_tax']*100/$itemsubtotal.'%';
					$ItemsArray[$i]['Kedvezmeny_szazaleka'] = '0';

					$i++;
				}
				// shipping cost to order
				$ItemsArray[$i]['Name'] = 'Szállítási költség';
				$ItemsArray[$i]['Termekkod'] = 0;
				$ItemsArray[$i]['Sku'] = 0;
				$ItemsArray[$i]['Qty'] = 1;
				$ItemsArray[$i]['Mee'] = 'db';
				$ItemsArray[$i]['Item_Price'] = $order->get_shipping_total();
				$ItemsArray[$i]['AFA'] = $order->get_shipping_tax().'%';
				$ItemsArray[$i]['Kedvezmeny_szazaleka'] = '0';
			}
			return $ItemsArray;
		}
	}
	*/
}
