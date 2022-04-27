<?php


namespace Inc\Base;


class Mailer extends BaseController  {

	public function register() {
		add_action( 'woocommerce_email_footer', [$this, 'customize_email_footer'] );
	}

	public function customize_email_footer() {
		$html = '
		<p>
			Liebe Grüsse <br />
			Neva von babytuch.ch
		</p>
		';
		echo $html;
	}

}