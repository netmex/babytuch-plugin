<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Base;

use \Inc\Base\BaseController;

/**
* 
*/
class Enqueue extends BaseController
{
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));
	}
	
	function enqueue() {
		// enqueue all our scripts
		wp_enqueue_style( 'babytuch-style', $this->plugin_url . 'assets/style.css' );
        wp_enqueue_style( 'tachyons', $this->plugin_url . 'assets/tachyons.min.css' ,null,'4.12.0.1');
		//wp_enqueue_script( 'babytuch-script', $this->plugin_url . 'assets/myscript.js' );

	}

}