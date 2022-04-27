<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WC_Email' ) ) :

class WCReturnMail extends WC_Email {
	
	/**
	 * Set email defaults
	 */
	public function __construct() {

		// Unique ID for custom email
		$this->id = 'wc_return_orders';

		// Is a customer email
		$this->customer_email = true;
		
		// Title field in WooCommerce Email settings
		$this->title = __( 'Rücksendung', 'woocommerce' );

		// Description field in WooCommerce email settings
		$this->description = __( 'Bestellungen welche zurückgesandt werden.', 'woocommerce' );

		// these define the locations of the templates that this email should use, we'll just use the new order template since this email is similar
		$upload_dir = wp_upload_dir();
		
		//$this->template_base  = WPD_BASE_DIR . '/wc-emails/';	// Fix the template base lookup for use on admin screen template path display
	
		$this->template_html  = 'emails/customer_return.php';
		$this->template_plain = 'emails/plain/customer_return.php';

		$this->placeholders   = array(
			'{site_title}'   => $this->get_blogname(),
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// Trigger email when payment is complete
		//add_action( 'woocommerce_payment_complete', array( $this, 'trigger' ) );
		//add_action( 'woocommerce_order_status_returning', array( $this, 'trigger' ) );
		
	

		
		// Call parent constructor to load any other defaults not explicity defined here
		parent::__construct();

	}

	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Rücksendung', 'woocommerce' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Sie haben Ihre Bestellung erfolgreich zur Rücksendung aktiviert.', 'woocommerce' );
	}


	/**
	 * Prepares email content and triggers the email
	 *
	 * @param int $order_id
	 */
	public function trigger( $order_id, $order = false ) {

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}
			
		if ( is_a( $order, 'WC_Order' ) ) {
			
			$this->object = $order;
			
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
		
			//* Maybe include an additional check to make sure that the online training program account was created
			/* Uncomment and add your own conditional check
			$online_training_account_created = get_post_meta( $order_id, '_crwc_user_account_created', 1 );
			
			if ( ! empty( $online_training_account_created ) && false === $online_training_account_created ) {
				return;
			}
			*/

			/* Proceed with sending email */
			
			$this->recipient = $this->object->get_billing_email();

		}

		// Send welcome email only once and not on every order status change		
		/*if ( get_post_meta( $order_id, '_crwc_welcome_email_sent', true ) ) {

			return;
		}*/

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			
			return;
		}

		// All well, send the email
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		
		// add order note about the same
		//$this->object->add_order_note( sprintf( __( '%s email sent to the customer.', 'woocommerce' ), $this->get_title() ) );

		// Set order meta to indicate that the welcome email was sent
		//update_post_meta( $order_id, '_crwc_welcome_email_sent', 1 );
		
	}
	
	/**
	 * get_content_html function.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'			=> $this->object,
			'email_heading'		=> $this->get_heading(),
			'sent_to_admin'		=> false,
			'plain_text'		=> false,
			'email'			=> $this
		) );
	}


	/**
	 * get_content_plain function.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'			=> $this->object,
			'email_heading'		=> $this->get_heading(),
			'sent_to_admin'		=> false,
			'plain_text'		=> true,
			'email'			=> $this
		) );
	}


	/**
	 * Initialize settings form fields
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => sprintf( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->get_subject() ),
				'placeholder' => $this->get_default_subject(),
				'default'     => ''
			),
			'heading'    => array(
				'title'       => __( 'Email Heading', 'woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' ), $this->get_heading() ),
				'placeholder' => $this->get_default_heading(),
				'default'     => ''
			),
			'email_type' => array(
				'title'			=> __( 'Email type', 'woocommerce' ),
				'type'			=> 'select',
				'description'	=> __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'		=> 'html',
				'class'			=> 'email_type wc-enhanced-select',
				'options'		=> $this->get_email_type_options(),
				'desc_tip'		=> true,
			)
		);
	}
		
}

endif;