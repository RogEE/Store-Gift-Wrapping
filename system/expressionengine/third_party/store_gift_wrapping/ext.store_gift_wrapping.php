<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Store: Gift Wrapping Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		Michael Rog
 * @link		http://rog.ee
 */

class Store_gift_wrapping_ext {
	
	public $settings 		= array();
	public $description		= 'Adds/removes a Gift Wrapping item when the cart is updated.';
	public $docs_url		= '';
	public $name			= 'Store: Gift Wrapping';
	public $settings_exist	= 'y';
	public $version			= '0.0.1';
	
	private $EE;
	private $cart_contents = array();
	
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
		

				
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Settings Form
	 *
	 * If you wish for ExpressionEngine to automatically create your settings
	 * page, work in this method.  If you wish to have fine-grained control
	 * over your form, use the settings_form() and save_settings() methods 
	 * instead, and delete this one.
	 *
	 * @see http://expressionengine.com/user_guide/development/extensions.html#settings
	 */
	public function settings()
	{
		return array(
			
		);
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://codeigniter.com/user_guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		// Setup custom settings in this array.
		$this->settings = array();
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'on_store_cart_update_start',
			'hook'		=> 'store_cart_update_start',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		$this->EE->db->insert('extensions', $data);
		
	}	

	// ----------------------------------------------------------------------
	
	/**
	 * store_cart_update_end
	 *
	 * @param 
	 * @return 
	 */
	public function on_store_cart_update_start($cart_contents)
	{

		$this->cart_contents = $cart_contents;
		
		$this->cart_contents['gw_cart_updated'] = date(DATE_RFC1036);
		
		/*
		echo "<pre>";
		if ( ! isset($this->EE->store_cart)) { debug_print_backtrace(); exit; }
		echo "</pre>";
		*/
		
		$this->insert(3,1,array(),array('Message'=>'this is the message!'),TRUE);
		
		mail("michael@michaelrog.com", "on_store_cart_update_end: ".$this->EE->uri->uri_string(), print_r($this->cart_contents, TRUE));
		
		return $this->cart_contents;
		
	}

	// ----------------------------------------------------------------------

	/**
	 * store_order_submit_start
	 *
	 * @param 
	 * @return 
	 */
	public function on_store_order_submit_start($order_data)
	{

		// $order_data['gw_order_submitted'] = date(DATE_RFC1036);
		
		mail("michael@michaelrog.com", "on_store_order_submit_start: ".$this->EE->uri->uri_string(), print_r($order_data, TRUE));

		return $order_data;
		
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Adds item to the current cart
	 */
	protected function insert($entry_id, $item_qty, $mod_values, $input_values, $update_qty = TRUE)
	{
	
		if (empty($this->cart_contents['items'])) $this->cart_contents['items'] = array();

		// check item doesn't already exist in cart
		if (empty($mod_values) OR ! is_array($mod_values)) $mod_values = array();
		if (empty($input_values) OR ! is_array($input_values)) $input_values = array();

		$existing_key = $this->find($entry_id, $mod_values, $input_values);

		if ($existing_key === FALSE)
		{
			// add to cart
			$item = array(
				'key' => $this->_next_key(),
				'entry_id' => $entry_id,
				'item_qty' => $item_qty,
				'mod_values' => $mod_values,
				'input_values' => $input_values
			);

			$this->cart_contents['items'][$item['key']] = $item;
		}
		else
		{
			// update item
			$this->cart_contents['items'][$existing_key] = array(
				'key' => $existing_key,
				'entry_id' => $entry_id,
				'item_qty' => ($update_qty ? $item_qty : $this->cart_contents['items'][$existing_key]['item_qty'] + $item_qty),
				'mod_values' => $mod_values,
				'input_values' => $input_values
			);
		}
		
	}

	/**
	 * Find the key of a specified product in the array, if it exists
	 */
	protected function find($entry_id, $mod_values, $input_values)
	{
		foreach ($this->cart_contents['items'] as $item_key => $item)
		{
			if
			(
				$item['entry_id'] == $entry_id AND
				$item['mod_values'] == $mod_values // AND
				// $item['input_values'] == $input_values
			)
			{
				return $item_key;
			}
		}

		return FALSE;
	}
	
	/**
	 * Find the next available item key for the current cart
	 */
	protected function _next_key()
	{
		return empty($this->cart_contents) ? 0 : max(array_keys($this->cart_contents['items'])) + 1;
	}

	// ----------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	// ----------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	}	
	
	// ----------------------------------------------------------------------
}

/* End of file ext.store_gift_wrapping.php */
/* Location: /system/expressionengine/third_party/store_gift_wrapping/ext.store_gift_wrapping.php */