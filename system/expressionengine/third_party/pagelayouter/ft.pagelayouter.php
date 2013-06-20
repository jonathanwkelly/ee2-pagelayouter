<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once(PATH_THIRD.'pagelayouter/config.php');

/**
 * Pagelayouter Fieldtype
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author 		Jonathan W. Kelly
 * @link 		http://github.com/jonathanwkelly/ee2-pagelayouter
 * @copyright 	Copyright (c) 2013 Paramore - the digital agency
 */
class Pagelayouter_ft extends EE_Fieldtype {

	var $info = array(
		'name' 		=> 'Pagelayouter',
		'version' 	=> '1.0'
	);

	/**
	 * The EE instance reference
	 * @var $EE {object}
	 */
	var $EE;

	/**
	 * Will hold our template results so that we don't have to keep running
	 * queries every time we need a template.
	 * @var $cache {array}
	 */
	var $templates_cache;

	/**
	 * These are standard cp publish tabs, and will be ignored in our config
	 * @var $standard_tabs {array}
	 */
	protected $standard_tabs = array(
		'publish',
		'options',
		'categories',
		'date'
	);

	// --------------------------------------------------------------------
	// PUBLIC METHODS
	// --------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		$this->EE =& get_instance();

		$this->cache =& $this->EE->session->cache['pagelayouter'];

		$this->EE->lang->loadfile(PAGELAYOUTER_LANG_FILE);
	}

	// --------------------------------------------------------------------

	public function install()
	{
		return array(
			PAGELAYOUTER_LANG_PREFIX.'rules' => '',
			PAGELAYOUTER_LANG_PREFIX.'publish_page_templates' => '',
		);
	}

	// --------------------------------------------------------------------

	/**
	 * CP Settings Page
	 * @return {void}
	 */
	public function display_global_settings()
	{
		$this->_cp_css();

		$this->EE->load->library('table');

		/* get our custom tabs, and pagelayouter custom fields */
		$custom_fields = $this->_get_pagelayouter_fields(TRUE);
		$custom_tabs = $this->_get_templates(TRUE);

		if((count($custom_fields) > 1) && (count($custom_tabs) > 1))
		{
			$output = '';

			/* build table to select template options */
			$this->EE->table->set_template(array(
				'table_open' 	=> '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
				'row_start' 	=> '<tr class="even">',
				'row_alt_start' => '<tr class="odd">'
			));

			/* "thead" row */
			$this->EE->table->set_heading(
				array(
					'data' 	=> '',
					'style' => 'width: 25%'
				),
				array(
					'data' 	=> lang(PAGELAYOUTER_LANG_PREFIX.'thead_templates'),
					'style' => 'width: 75%'
				)
			);

			$this->EE->table->add_row(

				lang(PAGELAYOUTER_LANG_PREFIX.'select_templates'),

				form_multiselect(
					PAGELAYOUTER_LANG_PREFIX.'publish_page_templates[]',
					$this->_get_templates(),
					explode('|', $this->settings[PAGELAYOUTER_LANG_PREFIX.'publish_page_templates']),
					' style="width: 90%; height: 200px;" '
				)
			);

			$output .= $this->EE->table->generate();

			// --------------

			/* table to configure templates to map to tabs */
			$this->EE->table->set_template(array(
				'table_open' 	=> '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
				'row_start' 	=> '<tr class="even">',
				'row_alt_start' => '<tr class="odd">'
			));

			/* "thead" row */
			$this->EE->table->set_heading(
				array(
					'data' 	=> lang(PAGELAYOUTER_LANG_PREFIX.'thead_field'),
					'style' => 'width: 25%'
				),
				array(
					'data' 	=> lang(PAGELAYOUTER_LANG_PREFIX.'thead_value'),
					'style' => 'width: 25%'
				),
				array(
					'data' 	=> lang(PAGELAYOUTER_LANG_PREFIX.'thead_tabs_show'),
					'style' => 'width: 25%'
				),
				array(
					'data' 	=> lang(PAGELAYOUTER_LANG_PREFIX.'thead_tabs_hide'),
					'style' => 'width: 25%'
				)
			);

			/* build template-tab-show-hide rows */
			$count = count(@$this->settings[PAGELAYOUTER_LANG_PREFIX.'field_id']);

			for($i = 0; $i <= $count; $i++)
			{
				$this->EE->table->add_row(

					/* channel field dd */
					form_dropdown(
						sprintf('%s[%s][%d]', PAGELAYOUTER_LANG_PREFIX.'rules', PAGELAYOUTER_LANG_PREFIX.'field_id', $i),
						$custom_fields,
						@$this->settings[PAGELAYOUTER_LANG_PREFIX.'field_id'][$i]
					),

					/* template selection dd */
					form_dropdown(
						sprintf('%s[%s][%d]', PAGELAYOUTER_LANG_PREFIX.'rules', PAGELAYOUTER_LANG_PREFIX.'template_id', $i),
						$custom_tabs,
						@$this->settings[PAGELAYOUTER_LANG_PREFIX.'template_id'][$i]
					),

					/* tabs to show dd */
					form_multiselect(
						sprintf('%s[%s][%d][]', PAGELAYOUTER_LANG_PREFIX.'rules', PAGELAYOUTER_LANG_PREFIX.'show_tabs', $i),
						$this->_get_publish_page_custom_tabs(),
						@$this->settings[PAGELAYOUTER_LANG_PREFIX.'show_tabs'][$i],
						' style="width: 90%; height: 100px;" '
					),

					/* tabs to hide dd */
					form_multiselect(
						sprintf('%s[%s][%d][]', PAGELAYOUTER_LANG_PREFIX.'rules', PAGELAYOUTER_LANG_PREFIX.'hide_tabs', $i),
						$this->_get_publish_page_custom_tabs(),
						@$this->settings[PAGELAYOUTER_LANG_PREFIX.'hide_tabs'][$i],
						' style="width: 90%; height: 100px;" '
					)
				);
			}

			$output .= $this->EE->table->generate();

			return $output;
		}

		return lang(PAGELAYOUTER_LANG_PREFIX.'no_data_to_config');
	}

	// --------------------------------------------------------------------

	public function save_global_settings()
	{
		$P =& $_POST[PAGELAYOUTER_LANG_PREFIX.'rules'];

		for($i = 0; $i <= count($P[PAGELAYOUTER_LANG_PREFIX.'field_id']); $i++)
		{
			if(!@$P[PAGELAYOUTER_LANG_PREFIX.'field_id'][$i] || !@$P[PAGELAYOUTER_LANG_PREFIX.'template_id'][$i])
			{
				unset($P[PAGELAYOUTER_LANG_PREFIX.'field_id'][$i]);
				unset($P[PAGELAYOUTER_LANG_PREFIX.'template_id'][$i]);
				unset($P[PAGELAYOUTER_LANG_PREFIX.'show_tabs'][$i]);
				unset($P[PAGELAYOUTER_LANG_PREFIX.'hide_tabs'][$i]);
			}
		}

		$P[PAGELAYOUTER_LANG_PREFIX.'publish_page_templates'] = implode('|', $_POST[PAGELAYOUTER_LANG_PREFIX.'publish_page_templates']);

		return $P;
	}

	// --------------------------------------------------------------------

	/**
	 * Build CP field
	 * @return {string}
	 */
	public function display_field($current_value)
	{
		return
			$this->_cp_js().
			form_dropdown($this->field_name, $this->_get_templates(TRUE, TRUE), $current_value, 'class="pagelayouter-dd" id="pagelayouter-field-id-'.$this->settings['field_id'].'"');
	}

	// --------------------------------------------------------------------

	/**
	 * Do the front-end thang
	 * @param {string}
	 */
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		if(empty($this->cache))
			$this->cache = $this->_get_templates(FALSE, TRUE, TRUE);

		return @$this->cache[$data];
	}

	// --------------------------------------------------------------------
	// PRIVATE METHODS
	// --------------------------------------------------------------------

	/**
	 * Get the publish page tabs
	 * @return {array}
	 */
	private function _get_publish_page_custom_tabs()
	{
		$this->EE->db->select('channels.channel_title, field_layout');
		$this->EE->db->join('channels', 'channels.channel_id = layout_publish.channel_id');
		$query = $this->EE->db->get('layout_publish');

		$custom_tabs = array();

		foreach($query->result() as $row)
		{
			$field_layout = unserialize($row->field_layout);

			foreach($field_layout as $layout_key => $layout)
			{
				if(!in_array($layout_key, $this->standard_tabs) && !array_key_exists($layout_key, $custom_tabs))
				{
					$custom_tabs[$layout_key] = $layout['_tab_label'];
				}
			}
		}

		return $custom_tabs;
	}

	// --------------------------------------------------------------------

	/**
	 * Get the templates from the DB
	 * @param $first_one_empty {boolean} TRUE to include an empty first array item
	 * @param $selected_only {boolean} TRUE to only return those configured on the fieldtype settings page
	 * @param $key_by_id {boolean} TRUE to return a flat array of template_id => template_path (this option
	 * also disregards the templates that have been selected in the admin -- it returns 'em all)
	 * @return {array}
	 */
	private function _get_templates($first_one_empty=FALSE, $selected_only=FALSE, $key_by_id=FALSE)
	{
		$ppage_sel_templates = explode('|', $this->settings[PAGELAYOUTER_LANG_PREFIX.'publish_page_templates']);

		$templates = array();

		if($first_one_empty)
			$templates[''] = '--';

		$this->EE->db->select('template_id, template_name, group_name');
		$this->EE->db->where('template_groups.site_id', $this->EE->config->item('site_id'));
		$this->EE->db->join('template_groups', 'templates.group_id = template_groups.group_id');
		$this->EE->db->order_by('group_name, template_name');

		$query = $this->EE->db->get('templates');

		if($query->num_rows())
		{
			foreach($query->result() as $row)
			{
				if($key_by_id === TRUE)
				{
					$templates[$row->template_id] = $row->group_name.'/'.$row->template_name;
				}

				else
				{
					/* template group name label */
					$template_group_name = $this->_pretty_name($row->group_name);

					/* should we even return this template? */
					if(!$selected_only || in_array($row->template_id, $ppage_sel_templates))
					{

						/* ensure a group array element exists; we'll add the actual templates under that */
						if(
							!isset($templates[$template_group_name]) ||
							!is_array($templates[$template_group_name])
						)
							$templates[$template_group_name] = array();

						/* template name label */
						$template_name = $this->_pretty_name($row->template_name);

						$templates[$template_group_name][$row->template_id] = $template_group_name.' -> '.$template_name;
					}
				}
			}
		}

		return $templates;
	}

	// --------------------------------------------------------------------

	/**
	 * Will get all the fields for each channel, that will be used as options
	 * for the hide/show configuration.
	 * @param $first_one_empty {boolean} TRUE to include an empty first array item
	 * @return {array}
	 */
	private function _get_pagelayouter_fields($first_one_empty=FALSE)
	{
		$fields = array();

		if($first_one_empty)
			$fields[''] = '--';

		$this->EE->db->select('channel_fields.group_id, group_name, field_id, field_label');
		$this->EE->db->where('field_type', 'pagelayouter');
		$this->EE->db->where('channel_fields.site_id', $this->EE->config->item('site_id'));
		$this->EE->db->join('field_groups', 'field_groups.group_id = channel_fields.group_id');

		$query = $this->EE->db->get('channel_fields');

		if($query->num_rows())
		{
			foreach($query->result() as $row)
			{
				$field_group_id = $row->group_id;

				if(!array_key_exists($row->field_id, $fields))
					$fields[$row->field_id] = $row->group_name.' -> '.$row->field_label;
			}
		}

		return $fields;
	}

	// --------------------------------------------------------------------

	/**
	 * Add custom CSS to the CP settings page
	 * @return {void}
	 */
	private function _cp_css()
	{
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.PAGELAYOUTER_CSS_PATH.'" />');
	}

	// --------------------------------------------------------------------

	/**
	 * Add custom JS to the CP publish page
	 * @return {string}
	 */
	private function _cp_js()
	{
		$config = array();

		for($i = 0; $i < count(@$this->settings[PAGELAYOUTER_LANG_PREFIX.'field_id']); $i++)
		{
			$field_id = @$this->settings[PAGELAYOUTER_LANG_PREFIX.'field_id'][$i];
			$template_id = @$this->settings[PAGELAYOUTER_LANG_PREFIX.'template_id'][$i];
			$show_tabs = @$this->settings[PAGELAYOUTER_LANG_PREFIX.'show_tabs'][$i];
			$hide_tabs = @$this->settings[PAGELAYOUTER_LANG_PREFIX.'hide_tabs'][$i];

			if(!isset($config[$field_id]))
				$config[$field_id] = array();

			$config[$field_id][$template_id] = array(
				'show_tabs' => $show_tabs,
				'hide_tabs' => $hide_tabs,
				'all_tabs' => @array_merge($show_tabs, $hide_tabs)
			);
		}

		$js_tmpl =
		'<script>
			var pagelayouter_rules = %s;

			function pagelayouter_show_tabs(tabs)
			{
				if(tabs){
					console.log("showing");
					$.each(tabs, function(index, name)
					{
						console.log(name);
						if($("#menu_"+name).size())
							$("#menu_"+name).show();
					});
				}
			}

			function pagelayouter_hide_tabs(tabs)
			{
				if(tabs){
					console.log("hiding");
					$.each(tabs, function(index, name)
					{
						console.log(name);
						if($("#menu_"+name).size())
							$("#menu_"+name).hide();
					});
				}
			}

			function pagelayouter_toggle_tabs(field_id, template_id)
			{
				if(typeof pagelayouter_rules[field_id][template_id] == "undefined")
					return;

				pagelayouter_hide_tabs(pagelayouter_rules[field_id][template_id].all_tabs);
				pagelayouter_show_tabs(pagelayouter_rules[field_id][template_id].show_tabs);
			}

			function pagelayouter_parse_id(raw_id)
			{
				return parseInt(raw_id.replace("pagelayouter-field-id-", ""));
			}

			$(document).ready(function()
			{
				$("select.pagelayouter-dd").on("change", function()
				{
					pagelayouter_toggle_tabs(pagelayouter_parse_id($(this).attr("id")), $(this).val());
				});

				$.each($("select.pagelayouter-dd"), function(index, dd)
				{
					pagelayouter_toggle_tabs(pagelayouter_parse_id($(dd).attr("id")), $(dd).val());
				});
			});
		</script>';

		return sprintf(
			$js_tmpl,
			json_encode($config)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * For displaying a template group as "Template Group" instead of like "template_group"
	 * @return {string}
	 */
	private function _pretty_name($uglyname='')
	{
		return ucwords(str_replace(array('_', '-'), ' ', $uglyname));
	}

}

// END Pagelayouter_ft class

/* End of file ft.pagelayouter.php */
/* Location: ./system/expressionengine/third_party/pagelayouter/ft.pagelayouter.php */