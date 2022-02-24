window.onload = function()
{
	// if WooCommerce is activated
	if( typeof document.getElementsByClassName("toplevel_page_woocommerce")["0"]!=="undefined" )
	{
		// change WordPress Dashboard WooCommerce menu name to Webshop
		document.getElementsByClassName("toplevel_page_woocommerce")["0"].getElementsByClassName("wp-menu-name")["0"].innerHTML = "Webshop";
		// hide "Connect Jetpack to activate WooCommerce Services" block
		if( typeof document.getElementsByClassName("wcs-nux__notice")["0"] !== "undefined" )
		{
			document.getElementsByClassName("wcs-nux__notice")["0"].style.display = "none";
		}
	}
}