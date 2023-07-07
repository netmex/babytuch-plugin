<?php
/**
 * @package  BabytuchPlugin
 */
namespace Inc;

final class Init
{
	/**
	 * Store all the classes inside an array
	 * @return array Full list of classes
	 */
	public static function get_services() 
	{
		return [
			Pages\Admin::class,
			Pages\Returns::class,
			Base\Enqueue::class,
			Base\SettingsLinks::class,
			Base\Functions::class,
			Base\Payments::class,
			Base\Shipping::class,
			Base\Returns::class,
            Base\Referrals::class,
			Base\Mailer::class,
			Pages\OrderProcessing::class,
			Pages\OrderSending::class,
			Pages\ReturnReceiving::class,
			Pages\ReturnReceivingAdmin::class,
			Pages\Replacements::class,
			Pages\StockGraphs::class,
			Pages\ReturnsAndReplacements::class,
			Pages\ReorderReceiving::class
		];
	}

	/**
	 * Loop through the classes, initialize them, 
	 * and call the register() method if it exists
	 * @return
	 */
	public static function register_services() 
	{
		foreach ( self::get_services() as $class ) {
			$service = self::instantiate( $class );
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	/**
	 * Initialize the class
	 * @param  class $class    class from the services array
	 * @return class instance  new instance of the class
	 */
	private static function instantiate( $class )
	{
		$service = new $class();

		return $service;
	}
}