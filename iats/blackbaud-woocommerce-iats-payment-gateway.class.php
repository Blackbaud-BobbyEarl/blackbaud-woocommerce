<?php

/**
* Blackbaud WooCommerce iATS Payment Gateway
*
* Adds iATS as a payment gateway for WooCommerce.
*
* @class Blackbaud_WooCommerce_iATS_Gateway
* @extends WC_Payment_Gateway
* @author Blackbaud - Bobby Earl
* @version 1.0.0
*/
class Blackbaud_WooCommerce_iATS_Gateway extends WC_Payment_Gateway {

	/**
	* Constructor
	*
	* @constructor
	* @return null
	*/
	public function __construct() {
		global $woocommerce;

		// Required members
		$this->id = 'iATS';
		$this->icon = '';
		$this->has_fields = true;
		$this->method_title = __('iATS', 'woocommerce');
		$this->method_description = __('iATS Payment Gateway', 'woocommerce');

		// WooCommerce Settings API won't return values from multiselect, only keys.
		$this->cardtypeValues = array(
			'AMX' => 'American Express',
			'DSC' => 'Discover',
			'MC' => 'MasterCard',
			'VISA' => 'Visa'
		);

		// Required methods
		$this->init_form_fields();
		$this->init_settings();

		// User set variables
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->agent_code = $this->get_option('agent_code');
		$this->password = $this->get_option('password');
		$this->cardtypes = $this->get_option('cardtypes');
		$this->logging = $this->get_option('logging') == 'yes';
		$this->sandbox = $this->get_option('sandbox');

		// Create log
		if ($this->logging) {
			$this->logger = new WC_Logger();
		}

		// Save options
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
	* Initialize the fields presented on the admin/settings screen.
	*
	* @method init_form_fields
	* @return null
	*/
	function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'woocommerce'),
				'type' => 'checkbox',
				'label' => __('Enable iATS Payment Gateway', 'woocommerce'),
				'default' => 'yes'
			),
			'agent_code' => array(
				'title' => __('iATS Agent Code', 'woocommerce'),
				'type' => 'text',
				'description' => __('Required agent code provided by iATS.', 'woocommerce'),
				'desc_tip' => true
			),
			'password' => array(
				'title' => __('iATS Password', 'woocommerce'),
				'type' => 'password',
				'description' => __('Required password provided by iATS.', 'woocommerce'),
				'desc_tip' => true
			),
			'title' => array(
				'title' => __('Title', 'woocommerce'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
				'default' => __('iATS', 'woocommerce'),
				'desc_tip' => true
			),
			'description' => array(
				'title' => __('Description', 'woocommerce'),
				'type' => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
				'default' => __('Pay via iATS', 'woocommerce'),
				'desc_tip' => true
			),
			'cardtypes'   => array(
				'title' => __( 'Accepted Cards', 'woocommerce' ),
				'type' => 'multiselect',
				'description' => __( 'Select which card types to accept.', 'woocommerce' ),
				'options'     => $this->cardtypeValues
			),
			'logging' => array(
				'title' => __('Logging', 'woocommerce'),
				'type' => 'checkbox',
				'label' => __('Enable Logging', 'woocommerce'),
				'default' => 'no',
				'description' => sprintf(
					__('Log all events in <code>%s</code><br /><strong>NOTE:</strong>  This will record ALL transaction information.', 'woocommerce'),
          wc_get_log_file_path('blackbaud')
				)
			),
			'sandbox' => array(
				'title' => __('Sandbox Transaction', 'woocommerce'),
				'type' => 'select',
				'description' => __('Selecting one of these overrides the credentials, card number and total in order to generate a sandbox transaction.', 'woocommerce'),
				'options' => array(
					'' => 'DISABLED',
					'1.00' => '1.00 - OK: 678594',
					'2.00' => '2.00 - REJ: 15',
					'3.00' => '3.00 - OK: 678594',
					'4.00' => '4.00 - REJ: 15',
					'5.00' => '5.00 - REJ: 15',
					'6.00' => '6.00 - OK: 678594:X',
					'7.00' => '7.00 - OK: 678594:Y',
					'8.00' => '8.00 - OK: 678594:A',
					'9.00' => '9.00 - OK: 678594:Z',
					'10.00' => '10.00 - OK: 678594:N',
					'15.00' => '15.00 - OK: 678594:Y (CVV2=1234) or REJ: 19',
					'16.00' => '16.00 - REJ: 2',
					'17.00' => '17.00 - REJ: 22'
				)
			)
		);
	}

	/**
	* Creates the payment fields form.
	* Currently this uses default woocommerce styles.
	*
	* @method payment_fields
	* @return null
	*/
	function payment_fields() {	
	?>

		<div class="col2-set">
			<div class="col-1">
				
				<p class="form-row form-row-wide validate-required" id="mop-field">
					<label for="mop">
						<?php echo __('Card Type', 'woocommerce') ?> 
						<abbr class="required" title="required">*</abbr>
					</label>
    				<select name="mop" id="mop" class="woocommerce-select">
    					<option value=""></option>
  						<?php foreach( $this->cardtypes as $type ): ?>
            				<option value="<?php echo $type ?>"><?php _e($this->cardtypeValues[$type], 'woocommerce'); ?></option>
  						<?php endforeach ?>
       				</select>
				</p>

				<p class="form-row form-row-wide validate-required" id="creditCardNum-field">
					<label for="creditCardNum">
						<?php echo __('Card Number', 'woocommerce') ?>
						<abbr class="required" title="required">*</abbr>
					</label>
					<input type="text" class="input-text" name="creditCardNum" id="creditCardNum" autocomplete="no" maxlength="16" />
				</p>

				<p class="form-row form-row-first validate-required" id="creditCardExpiry-Month-field">
					<label for="creditCardExpiry-Month">
						<?php echo __('Expiration', 'woocommerce') ?>
						<abbr class="required" title="required">*</abbr>
					</label>
					<input type="text" class="input-text" name="creditCardExpiry-Month" id="creditCardExpiry-Month" autocomplete="no" maxlength="2" placeholder="MM" />
					/
					<input type="text" class="input-text" name="creditCardExpiry-Year" id="creditCardExpiry-Year" autocomplete="no" maxlength="2" placeholder="YY" />
				</p>

				<p class="form-row form-row-last validate-required" id="cvv2-field">
					<label for="cvv2">
						<?php echo __('Security Code', 'woocommerce') ?>
						<abbr class="required" title="required">*</abbr>
					</label>
					<input type="text" class="input-text" name="cvv2" id="cvv2" autocomplete="no" maxlength="4" />
				</p>

			</div>
			<div class="col-2">



			</div>
		</div>

	<?php
	}

	/**
	* Validate the compulsary (required by law) fields for iATS.
	*
	* @method validate_fields
	* @return null
	*/
	function validate_fields() {				
		global $woocommerce;	
				
		$validated = true;
		$fields = array(
			'creditCardNum' => 'Credit card number is required.',
			'mop' => 'Credit card type is required.',
			'creditCardExpiry-Month' => 'Credit card expiration month is required.',
			'creditCardExpiry-Year' => 'Credit card expiration year is required.'
		);

		$this->log('Validating Fields:');

		foreach ($fields as $key => $error) {
			
			$v = (!isset($_POST[$key]) || empty($_POST[$key]));
			$this->log($key . ': ' . ($v ? 'invalid' : 'valid'));

			if ($v) {
				$woocommerce->add_error(__('Payment Error: ', 'woocommerce') . $error);
				$validated = false;
			}
		}
		
		return $validated;
	}

	/**
	* Process the payment and return the result.
	*
	* @method process_payment
	* @param {Number} $order_id ID of the WooCommerce order
	* @return {Array} [$return_array] Only returned if payment was successful.  Includes options below.
	*	{String} [$return_array.result] "success" or "failure".
	*	{String} [$return_array.redirect] The return url, as presented by the WC_Payment_Gateway class.
	*/
	function process_payment($order_id) {
		global $woocommerce;

		// Create order
        $order = new WC_Order($order_id);

        // Submit payment
        $response = $this->submit_payment($order);

        // Parse response
        switch ($response->STATUS) {
        	case 'Success':

        		switch (strrpos($response->PROCESSRESULT->AUTHORIZATIONRESULT,'OK')) {
        			case false:
        			case -1:
        				$woocommerce->add_error(__('Payment Error: Your payment was not approved.  Please verify your information below.', 'woocommerce'));
        			break;
        			default:
	            		$order->payment_complete();
	            		$r = array(
	            			'result' => 'success',
	            			'redirect' => $this->get_return_url($order)
	            		);
        			break;
        		}

        	break;

        	case 'Failure':
        	default:
	            $woocommerce->add_error(__('Payment Error: Unexpected error.  Unable to process your payment at this time.', 'woocommerce'));
        	break;
        }

        return $r;
	}

	/**
	* Submits the payment to iATS.
	* This method currently builds the request manually.  SoapClient is a possible alternative,
	* but was not available on the client server.
	*
	* @method submit_payment
	* @param {WC_Order} $order
	* @return {SimpleXMLElement Object} [$xml=null] Only returned if parsing was successful.
	*/
	function submit_payment($order) {

		$request[] = '<?xml version="1.0" encoding="utf-8"?>';
		$request[] = '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">';
		$request[] = '	<soap12:Body>';
		$request[] = '		<ProcessCreditCardV1 xmlns="https://www.iatspayments.com/NetGate/">';

		switch ($this->sandbox) {
			case '':
				$request[] = '			<agentCode>' . $this->agent_code . '</agentCode>';
				$request[] = '			<password>' . $this->password . '</password>';
				$request[] = '			<total>' . $order->order_total . '</total>';
			break;
			default:
				$request[] = '			<agentCode>TEST88</agentCode>';
				$request[] = '			<password>TEST88</password>';
				$request[] = '			<total>' . $this->sandbox . '</total>';
			break;
		}

		$request[] = '			<customerIPAddress>' . $_SERVER['REMOTE_ADDR'] . '</customerIPAddress>';
		$request[] = '			<invoiceNum>' . $order->order_key .'</invoiceNum>';
		$request[] = '			<creditCardNum>'. $_POST['creditCardNum'] . '</creditCardNum>';
		$request[] = '			<creditCardExpiry>' . $_POST['creditCardExpiry-Month'] . '/' . $_POST['creditCardExpiry-Year'] . '</creditCardExpiry>';
		$request[] = '			<cvv2>' . $_POST['cvv2'] . '</cvv2>';
		$request[] = '			<mop>' . $_POST['mop'] . '</mop>';
		$request[] = '			<firstName>'. $order->billing_first_name . '</firstName>';
		$request[] = '			<lastName>' . $order->billing_last_name . '</lastName>';
		$request[] = '			<address>' . $order->billing_address_1 . $order->billing_address_2 . '</address>';
		$request[] = '			<city>' . $order->billing_city . '</city>';
		$request[] = '			<state>' . $order->billing_state . '</state>';
		$request[] = '			<zipCode>' . $order->billing_postcode . '</zipCode>';
		$request[] = '			<comment>' . $order->customer_note . '</comment>';
		$request[] = '		</ProcessCreditCardV1>';
		$request[] = '	</soap12:Body>';
		$request[] = '</soap12:Envelope>';

		// Log request
		$this->log('iATS Request: ' . print_r($request, true));

		// Generate SOAP request
		$ch = curl_init('https://www.iatspayments.com/NetGate/ProcessLink.asmx?op=ProcessCreditCardV1');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, implode($request));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response_raw = curl_exec($ch);
		curl_close($ch);

		// Parse response
		$response_xml = new SimpleXMLElement($response_raw);
		$response_xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
		$response_parsed = $response_xml->xpath('//IATSRESPONSE');

		// Verify response
		if ($response_parsed !== false && count($response_parsed) == 1) {
			$response_parsed = $response_parsed[0];
		}

		// Log response				
		$this->log('iATS Response (string): ' . print_r($response_raw, true));
		$this->log('iATS Response (xml): ' . print_r($response_parsed, true));

		return $response_parsed;
	}

	/**
	* Logs message IF logging is enabled in the settings.
	*
	* @private
	* @method log
	* @return null
	*/
	private function log($message) {
		if ($this->logging) {
			$this->logger->add('blackbaud', $message);
		}
	}
}
?>
