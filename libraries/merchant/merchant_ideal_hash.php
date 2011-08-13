<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * CI-Merchant Library
 *
 * Copyright (c) 2011 Crescendo Multimedia Ltd
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Merchant iDEAL Hash Class
 *
 * Payment processing using iDEAL Basic Hash
 */

class Merchant_ideal_hash extends CI_Driver {

	public $name = 'iDEAL Hash';

	public $required_fields = array('amount', 'transaction_id', 'reference', 'currency_code', 'return_url', 'cancel_url');

	public $settings = array(
		'merchant_id' => '',
		'merchant_key' => '',
		'test_mode' => FALSE
	);

	const PROCESS_URL = 'https://ideal.secure-ing.com/ideal/mpiPayInitIng.do';
	const PROCESS_URL_TEST = 'https://idealtest.secure-ing.com/ideal/mpiPayInitIng.do';

	public $CI;

	public function __construct($settings = array())
	{
		foreach ($settings as $key => $value)
		{
			if(array_key_exists($key, $this->settings))	$this->settings[$key] = $value;
		}
		$this->CI =& get_instance();
	}

	public function _process($params)
	{
		//check that currency is EUR
		if ($params['currency_code'] !== 'EUR')
		{
			return new Merchant_response('failed', 'Invalid Store currency, '.$name.' gateway only accepts the EUR currency.');
		}

		// ask paypal to generate request url
		$request = array(
			'merchant_id' => $this->settings['merchant_id'],
			'merchant_key' => $this->settings['merchant_key'],
			'sub_id' => '0',
			'payment_type' => 'ideal',
			'purchase_id' => $params['transaction_id'],
			'valid_until' => date('Y-m-d\TH:i:s.000\Z', time()+3600),
			'currency_code' => $params['currency_code'],
			'description' => 'Store Order',
			'item_number' => 'Store Order',
			'item_description' => 'Store Order',
			'item_qty' => 1,
			'item_price' => $params['amount'] * 100,
			'amount' => $params['amount'] * 100,
			'language' => 'nl',
		);

		$shastring = $request['merchant_key'].$request['merchant_id'].$request['sub_id'].$request['amount'].$request['purchase_id'].
					$request['payment_type'].$request['valid_until'].$request['item_number'].$request['item_description'].
					$request['item_qty'].$request['item_price'];

		$shastring = preg_replace(array("/[ \t\n]/", '/&amp;/i', '/&lt;/i', '/&gt;/i', '/&quot/i'), array( '', '&', '<', '>', '"'), $shastring);
		$shasign = sha1($shastring);

		$url = $this->settings['test_mode'] ? self::PROCESS_URL_TEST : self::PROCESS_URL;
		?>
<html>
<head><meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'></head>
<body onLoad="document.forms['form1'].submit();">
<form method='post' name='form1' action="<?php echo $url; ?>">
<input type='hidden' name='merchantID' value="<?php echo $request['merchant_id']; ?>">
<input type='hidden' name='subID' value="<?php echo $request['sub_id']; ?>">
<input type='hidden' name='amount' value="<?php echo $request['amount']; ?>">
<input type='hidden' name='purchaseID' value="<?php echo $request['purchase_id']; ?>">
<input type='hidden' name='language' value="<?php echo $request['language']; ?>">
<input type='hidden' name='currency' value="<?php echo $request['currency_code']; ?>">
<input type='hidden' name='description' value="<?php echo $request['description']; ?>">
<input type='hidden' name='hash' value="<?php echo $shasign; ?>">
<input type='hidden' name='paymentType' value="<?php echo $request['payment_type']; ?>">
<input type='hidden' name='validUntil' value="<?php echo $request['valid_until']; ?>">
<input type='hidden' name='itemNumber1' value="<?php echo $request['item_number']; ?>">
<input type='hidden' name='itemDescription1' value="<?php echo $request['item_description']; ?>">
<input type='hidden' name='itemQuantity1' value="<?php echo $request['item_qty']; ?>">
<input type='hidden' name='itemPrice1' value="<?php echo $request['item_price']; ?>">
<input type='hidden' name='urlSuccess' value="<?php echo $params['return_url']; ?>">
<input type='hidden' name='urlCancel' value="<?php echo $request['cancel_url']; ?>">
<input type='hidden' name='urlError' value="<?php echo $request['error_url']; ?>">
<input type='submit' name='submit2' value='Verstuur'>
</form>
</body>
</html>
	<?php
		exit();
	}

	public function _process_return()
	{
		$action = $this->CI->input->get('action', TRUE);

		if ($action == 'success')
		{
			return new Merchant_response('authorized', 'payment_authorized', (string)$this->CI->input->get('id', TRUE), (string)$this->CI->input->get('amt', TRUE));
		}
		elseif ($action == 'cancel')
		{
			return new Merchant_response('failed', 'An error occurred while processing your iDEAL transaction. Please contact the online shop or try again later. Please check your account if the payment has been processed before you pay again with iDEAL.');
		}
		else
		{
			return new Merchant_response('failed', 'An error occurred while processing your iDEAL transaction. Please contact the online shop or try again later. Please check your account if the payment has been processed before you pay again with iDEAL.');
		}
	}
}
/* End of file ./libraries/merchant/drivers/merchant_ideal_hash.php */