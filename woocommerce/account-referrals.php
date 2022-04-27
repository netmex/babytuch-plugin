<?php 


function get_referral_id($user_id) {
	if ( !$user_id ) {
		return false;
	}
	$referralID = get_user_meta($user_id, "gens_referral_id", true);
	if($referralID && $referralID != "") {
		return $referralID;
	}
}


/**
 * Endpoint HTML content.
 */
function my_custom_referrals_content() {
	$referral_id = get_referral_id( get_current_user_id() );
	$refLink = esc_url(add_query_arg( 'raf', $referral_id, get_home_url() )); 
	?>
		<div id="raf-message" class="woocommerce-message"><?php _e( 'Vermittlungsprogramm URL:','gens-raf'); ?> <a href="<?php echo $refLink; ?>" ><?php echo $refLink; ?></a></div>
	<?php
		$user_info = get_userdata(get_current_user_id());
		$user_email = $user_info->user_email;
		$date_format = get_option( 'date_format' );
		$args = array(
			'posts_per_page'   => -1,
			'post_type'        => 'shop_coupon',
			'post_status'      => 'publish',
			'meta_query' => array (
				array (
				  'key' => 'customer_email',
				  'value' => $user_email,
				  'compare' => 'LIKE'
				)
			),
		);
			
		$coupons = get_posts( $args );

		if($coupons) { ?>

			<h2><?php echo apply_filters( 'wpgens_raf_title', __( 'Erfolgreiche Vermittlungen', 'gens-raf' ) ); ?></h2>
			<table class="shop_table shop_table_responsive">
				<tr>
					<th><?php _e('','gens-raf'); ?></th>
					<th><?php _e('Vermittelte Person','gens-raf'); ?></th>
					<th><?php _e('Erfolgreiche Vermittlungen','gens-raf'); ?></th>
					<th><?php _e('Anzahl vermittelte Tücher','gens-raf'); ?></th>
					<th><?php _e('Belohnung','gens-raf'); ?></th>
				</tr>
		<?php
			$count=0;
			$total_amount = 0;
			$total_amount_open = 0;
			$count_open = 0;
			$total_refund=0;
			$total_refund_open=0;
			foreach ( $coupons as $coupon ) {
				if(substr( $coupon->post_title, 0, 3 ) != "RAF") {
					continue;
				}
				$discount = get_post_meta($coupon->ID, "coupon_amount" ,true);
				$discount_type = get_post_meta($coupon->ID, "discount_type" ,true);
				$usage_count = get_post_meta($coupon->ID, "usage_count" ,true);
				$order_amount = get_post_meta($coupon->ID, "order_amount" ,true);
				$reffered_who = get_post_meta($coupon->ID, "reffered_who" ,true);
				$reffered_who_fname = get_post_meta($coupon->ID, "reffered_who_fname" ,true);
				$reffered_who_lname = get_post_meta($coupon->ID, "reffered_who_lname" ,true);
				$transaction_complete = get_post_meta($coupon->ID, "transaction_complete" ,true);
				//$expiry_date = get_post_meta($coupon->ID,"expiry_date",true);

				if($transaction_complete){
					$total_amount = $total_amount + (int)$order_amount;
					$total_refund = $total_refund + ($discount*(int)$order_amount);
					$count++;
				}else{
					$total_amount_open = $total_amount_open + (int)$order_amount;
					$total_refund_open = $total_refund_open + ($discount*(int)$order_amount);
					$count_open++;
				}
				echo '<tr>';
				echo '<td></td>';
				echo '<td>'.$reffered_who_fname.' '.$reffered_who_lname.'</td>';
				echo '<td>1</td>';
				echo '<td>'.$order_amount.'</td>';
				echo '<td>'.$discount*(int)$order_amount.' Fr.</td>';
				echo '</tr>';
			}
			$total = $total_amount*$discount;
			$total_open = $total_amount_open*$discount;
			if($count_open != 0) { // If coupon isnt used yet.

				echo '<tr>';
				echo '<td><b>Offene Belohnungen Total</b></td>';
				echo '<td></td>';
				echo '<td>'.$count_open.'</td>';
				echo '<td>'.$total_amount_open.'</td>';
				echo '<td>'.$total_refund_open.' Fr.</td>';
				echo '</tr>';
			} 
			if($count != 0) { // If coupon isnt used yet.

				echo '<tr>';
				echo '<td><b>Abgeschlossene Belohnungen Total</b></td>';
				echo '<td></td>';
				echo '<td>'.$count.'</td>';
				echo '<td>'.$total_amount.'</td>';
				echo '<td>'.$total_refund.' Fr.</td>';
				echo '</tr>';
			} 
			echo '</table>';
		}
		
		
		$client = wp_get_current_user();
		if ( $client->exists() ) {
			$client_id = $client->ID;
		 }    
		$current_iban = get_user_meta($client_id, 'iban_num');
		
		echo ''?>

		<h4>Ihre IBAN-Nr</h4>
		<p>Wird nur für Rückerstattung und Vermittlungsprogramm verwendet</p>
		<form method="post" action="">
		<label>IBAN-Nr: </label>
		<input style="width:300px;" type="text" placeholder="CH1908518016000520000" name="iban" value=<?php if(!empty($current_iban)){echo $current_iban[0];}?>>
		<br><br><input type="submit" value="Speichern" name="save">
		</form> 

		<?php
		if(isset($_POST['save'])){
			echo'IBAN-Nr. erfolgreich gespeichert.';
			$post = str_replace(' ','',$_POST['iban']);
			update_user_meta($client_id, 'iban_num', $post);
		}
		
		
}

$endpoint = 'referrals';
add_action( 'woocommerce_account_' . $endpoint .  '_endpoint', 'my_custom_referrals_content' );


