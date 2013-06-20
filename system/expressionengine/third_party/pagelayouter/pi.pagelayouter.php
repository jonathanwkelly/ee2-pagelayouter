<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Pagelayouter Class
 *
 * @package		ExpressionEngine
 * @category	Plugin
 * @author 		Jonathan W. Kelly
 * @copyright 	Copyright (c) 2013, Paramore - the digital agency
 * @link 		http://github.com/jonathanwkelly/ee2-pagelayouter
 */

$plugin_info = array(
	'pi_name'         => 'Pagelayouter Plugin',
	'pi_version'      => '1.0',
	'pi_author'       => 'Jonathan W. Kelly',
	'pi_author_url'   => 'http://github.com/jonathanwkelly/ee2-pagelayouter',
    'pi_description'  => '',
    'pi_usage'        => ''
);

class Pagelayouter
{
    public $return_data = '';
    private $EE;

    // --------------------------------------------------------------------

    /**
     * @return string
     */
    public function __construct()
    {
		$this->EE = ee();
    }

    // --------------------------------------------------------------------

    /**
     * Will attempt to gather a list of entry IDs that are appropriate related 
     * entries for a particular entry. 
     * @return {string}
     */
    public function related_entry_ids()
    {
        $this->return_data = "3|4";

        return $this->return_data;
    }

}
/* End of file pi.pagelayouter.php */
/* Location: ./system/expressionengine/third_party/pagelayouter/pi.pagelayouter.php */
