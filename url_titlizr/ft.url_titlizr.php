<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @package		URL Titlizr
 * @author		Gauthier De Paoli
 * @copyright	Copyright (c) 2013, Gauthier De Paoli.
 * @link		https://github.com/entipe/
 * @since		Version 1.0
 */

class Url_titlizr_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'URL Titlizr',
		'version'	=> '1.0'
	);

	// --------------------------------------------------------------------
	
	function validate($data)
	{
		if ($data == '')
		{
			return TRUE;
		}
		
		if ( ! isset($this->field_content_types))
		{
			ee()->load->model('field_model');
			$this->field_content_types = ee()->field_model->get_field_content_types();
		}

		if ( ! isset($this->settings['field_content_type']))
		{
			return TRUE;
		}

		$content_type = $this->settings['field_content_type'];
		
		if (in_array($content_type, $this->field_content_types['text']) && $content_type != 'any')
		{
			
			if ($content_type == 'decimal')
			{
				if ( ! ee()->form_validation->numeric($data))
				{
					return ee()->lang->line($content_type);
				}
				
				// Check if number exceeds mysql limits
				if ($data >= 999999.9999)
				{
					return ee()->lang->line('number_exceeds_limit');
				}
				
				return TRUE;
			}

			if ( ! ee()->form_validation->$content_type($data))
			{
				return ee()->lang->line($content_type);
			}
			
			// Check if number exceeds mysql limits			
			if ($content_type == 'integer')
			{
				if (($data < -2147483648) OR ($data > 2147483647))
				{
					return ee()->lang->line('number_exceeds_limit');
				}
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	function display_field($data)
	{
		$field = array(
			'name'		=> $this->field_name,
			'id'		=> $this->field_name,
			'value'		=> $data
		);
		$parent_id = $this->settings["url_titlizr_parent_field_id"];
	
		ee()->javascript->output("
			$('#field_id_".$parent_id."').bind('propertychange keyup input paste',function() {
				$('#field_id_".$parent_id."').ee_url_title($('#".$this->field_name."'));
			});
			");
		return form_input($field);
	}
	
	// --------------------------------------------------------------------
	
	function replace_tag($data, $params = '', $tagdata = '')
	{
		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return ee()->functions->encode_ee_tags($data);
		}

		$type		= isset($this->settings['field_content_type']) ? $this->settings['field_content_type'] : 'all';
		$decimals	= isset($params['decimal_place']) ? (int) $params['decimal_place'] : FALSE;
		
		$data = $this->_format_number($data, $type, $decimals);

		return ee()->typography->parse_type(
			ee()->functions->encode_ee_tags($data),
			array(
				'text_format'	=> false,
				'html_format'	=> false,
				'auto_links'	=> $this->row['channel_auto_link_urls'],
				'allow_img_url' => $this->row['channel_allow_img_urls']
			)
		);
	}
	
	// --------------------------------------------------------------------

	function display_settings($data)
	{
		$prefix = 'text';
		$extra = '';
		ee()->table->add_row("Parent field",$this->get_text_fields($data));
		
	}

	private function get_text_fields($data) 
	{
		$query = ee()->db->query("SELECT field_id,field_label FROM ".ee()->db->dbprefix."channel_fields WHERE group_id = '".intval(ee()->input->get("group_id"))."' AND field_type = 'text'");

		$selected_id =  isset($data["url_titlizr_parent_field_id"]) ? intval($data["url_titlizr_parent_field_id"]) : 0;

		if($query->num_rows() > 0)
		{
			$options = array();
			foreach ($query->result_array() as $row) {
				$options[$row["field_id"]] = $row["field_label"];
			}
			return form_dropdown('url_titlizr_parent', $options,$selected_id);
		}
		else
			return "No textfields found. Create textfield before URL Titlizr";
	}
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		return array(
			'url_titlizr_parent_field_id'			=> ee()->input->post('url_titlizr_parent')
		);
	}
	

	// --------------------------------------------------------------------
	
	// --------------------------------------------------------------------
	
	function _format_number($data, $type = 'all', $decimals = FALSE)
	{
		switch($type)
		{
			case 'numeric':	$data = rtrim(rtrim(sprintf('%F', $data), '0'), '.'); // remove trailing zeros up to decimal point and kill decimal point if no trailing zeros
				break;
			case 'integer': $data = sprintf('%d', $data);
				break;
			case 'decimal':
				$parts = explode('.', sprintf('%F', $data));
				$parts[1] = isset($parts[1]) ? rtrim($parts[1], '0') : '';
				
				$decimals = ($decimals === FALSE) ? 2 : $decimals;
				$data = $parts[0].'.'.str_pad($parts[1], $decimals, '0');
				break;
			default:
				if ($decimals && ctype_digit(str_replace('.', '', $data))) {
					$data = number_format($data, $decimals);
				}
		}
		
		return $data;
	}
}