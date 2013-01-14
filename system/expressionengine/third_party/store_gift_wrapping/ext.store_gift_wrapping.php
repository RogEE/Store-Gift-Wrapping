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
	public $docs_url		= 'http://rog.ee';
	public $name			= 'Store: Gift Wrapping';
	public $settings_exist	= 'y';
	public $version			= '0.0.5';
	
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

		$settings = array();
		
		$settings['store_gw_quantity_field'] = array('i', '', "store_gw_qty");
		$settings['store_gw_quantity_default'] = array('i', '', "0");
		
		$settings['store_gw_action_field'] = array('i', '', "store_gw_act");
		$settings['store_gw_action_default'] = array('r', array('a'=>'store_gw_add','u'=>'store_gw_update','n'=>'store_gw_nothing'), "n");
		
		$settings['store_gw_message_field'] = array('i', '', "store_gw_message");

		$settings['store_gw_product_id'] = array('i', '', '');
		$settings['store_gw_allow_multiple'] = array('r', array('y'=>'store_gw_yes','n'=>'store_gw_no'), "n");
				
		return $settings;

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

		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'on_store_cart_update_end',
			'hook'		=> 'store_cart_update_end',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		$this->EE->db->insert('extensions', $data);
		
	}	

	// ----------------------------------------------------------------------


	/**
	 * on_store_cart_update_start
	 *
	 * @param 
	 * @return 
	 */
	public function on_store_cart_update_start($cart_contents)
	{

		// If there's another extension in the pipe before us, play nice
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$cart_contents = $this->EE->extensions->last_call;
		}
		$this->cart_contents = $cart_contents;
		
		// unique timestamp, for debugging purposes		
		$this->cart_contents['gw_submitted'] = date(DATE_RFC1036);

		// get Product ID of giftwrapping item
		$gw_id = intval($this->settings['store_gw_product_id']);
		// debug
		$this->cart_contents['gw_product_id'] = $gw_id;
		
		// get Action input, if there is one
		$gw_action_input = $this->EE->input->post($this->settings['store_gw_action_field'], TRUE);
		// debug
		$this->cart_contents['gw_submitted_action'] = $gw_action_input;
		// validate and apply default if needed
		$gw_action = (in_array($gw_action_input,array('a','u','n')) ? $gw_action_input : $this->settings['store_gw_action_default']);
		// debug
		$this->cart_contents['gw_action'] = $gw_action;
		
		// only continue if there is a giftwrapping item specified
		// only continue if the action is not "Do Nothing"
		if ($gw_id AND $gw_action != 'n')
		{
		
			// get Quantity input, if there is one
			$gw_qty_input = $this->EE->input->post($this->settings['store_gw_quantity_field'], TRUE);
			// debug
			$this->cart_contents['gw_submitted_qty'] = $gw_qty_input;
			// sanitize and apply default if needed
			$gw_qty = ($gw_qty_input === FALSE ? intval($this->settings['store_gw_quantity_default']) : intval($gw_qty_input));
			// debug
			$this->cart_contents['gw_qty'] = $gw_qty;

			// get Message input, if there is one
			$gw_message_input = $this->EE->input->post($this->settings['store_gw_message_field'], TRUE);
			// debug
			$this->cart_contents['gw_submitted_message'] = $gw_message_input;
			// If there's a message, place it in the input values array. Else, input values is empty.
			$input_values_array = ($gw_message_input === FALSE ? array() : array('Message' => $gw_message_input));
			
			// Mod values array is empty no matter what, for standardization
			$mod_values_array = array();

			// Are we updating quantity, or adding?
			$gw_update = $gw_action == 'u' ? TRUE : FALSE;
			// debug
			$this->cart_contents['gw_update'] = $gw_update;
		
			$this->insert($gw_id,$gw_qty,$mod_values_array,$input_values_array,$gw_update);
		
		}
		
		return $this->cart_contents;
		
	} // on_store_cart_update_start

	// ----------------------------------------------------------------------

	
	/**
	 * on_store_cart_update_end
	 *
	 * @param 
	 * @return 
	 */
	public function on_store_cart_update_end($cart_contents)
	{

		// If there's another extension in the pipe before us, play nice
		
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$cart_contents = $this->EE->extensions->last_call;
		}

		// Add a few more gw_ helper variables
		
		$qty_in_cart = 0;
		$subtotal = 0;
		$total = 0;
		$order_subtotal_sans_giftwrapping = 0;
		$order_total_sans_giftwrapping = 0;
		
		$gw_id = intval($this->settings['store_gw_product_id']);
		
		foreach (($cart_contents['items']) as $item)
		{
			if ($item['entry_id'] == $gw_id)
			{
				$qty_in_cart += $item['item_qty'];
				$subtotal += $item['item_subtotal_val'];
				$total += $item['item_total_val'];
			}
		}
		
		$cart_contents['gw_qty_in_cart'] = $qty_in_cart;
		$cart_contents['gw_order_qty_sans_giftwrapping'] = intval($cart_contents['order_qty']) - intval($qty_in_cart);
		
		$this->EE->load->add_package_path(PATH_THIRD.'store/', TRUE);
		$this->EE->load->helper('store_helper');
		
		$cart_contents['gw_subtotal_val'] = $subtotal;
		$cart_contents['gw_subtotal'] = store_format_currency($cart_contents['gw_subtotal_val']);
		
		$cart_contents['gw_total_val'] = $total;
		$cart_contents['gw_total'] = store_format_currency($cart_contents['gw_total_val']);
		
		$cart_contents['gw_order_subtotal_sans_giftwrapping_val'] = $cart_contents['order_subtotal_val'] - $subtotal;
		$cart_contents['gw_order_subtotal_sans_giftwrapping'] = store_format_currency($cart_contents['gw_order_subtotal_sans_giftwrapping_val']);
		
		$cart_contents['gw_order_total_sans_giftwrapping_val'] = $cart_contents['order_total_val'] - $total;
		$cart_contents['gw_order_total_sans_giftwrapping'] = store_format_currency($cart_contents['gw_order_total_sans_giftwrapping_val']);

		// Add a cart_contents printout for front-end debugging
		
		unset($cart_contents['gw_cart_contents_printr']);
		$this->cart_contents = $cart_contents;
		$this->cart_contents['gw_cart_contents_printr'] = print_r($cart_contents,TRUE);
		
		// And send it all up the pipe
		
		return $this->cart_contents;
		
	} // on_store_cart_update_end

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
		
		$this->cart_contents['gw_allow_multiple'] = $this->settings['store_gw_allow_multiple'];
		
		foreach ($this->cart_contents['items'] as $item_key => $item)
		{
			if
			(
				$item['entry_id'] == $entry_id
				AND $item['mod_values'] == $mod_values
				AND ($this->settings['store_gw_allow_multiple'] == 'n' OR $item['input_values'] == $input_values)
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
		return (empty($this->cart_contents) OR empty($this->cart_contents['items'])) ? 0 : max(array_keys($this->cart_contents['items'])) + 1;
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