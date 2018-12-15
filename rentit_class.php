<?php
/**
 * @package RentIt_Class
 * @version 1.0
 */
/*
Plugin Name: RentIt Class
Plugin URI: https://wordpress.org/plugins/hello-dolly/
Description: this can register new method to Rent_It_Class
Version: 1.0
Author URI: https://ma.tt/
Text Domain: RentIt_Class
*/


//this function only format price
function format_price( $price ) {
	//get formatted price
	extract( apply_filters( 'wc_price_args', wp_parse_args( array(), array(
		'ex_tax_label' => false,
		'currency' => '',
		'decimal_separator' => wc_get_price_decimal_separator(),
		'thousand_separator' => wc_get_price_thousand_separator(),
		'decimals' => wc_get_price_decimals(),
		'price_format' => get_woocommerce_price_format()
	) ) ) );
	$negative = $price < 0;
	$price = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * -1 : $price ) );
	$price = apply_filters( 'formatted_woocommerce_price', number_format( $price, $decimals, $decimal_separator, $thousand_separator ), $price, $decimals, $decimal_separator, $thousand_separator );

	if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $decimals > 0 ) {
		$price = wc_trim_zeros( $price );
	}


	$formatted_price = ( $negative ? '-' : '' ) . sprintf( $price_format, get_woocommerce_currency_symbol( $currency ), $price );


	return wp_kses_post( $formatted_price );
}




//rentit_get_formatted_price
/*
this function calculate time remaining less than one day
*/
//my function

function get_time_delay( $start_date, $end_date ) {

	//fmod renturn the remaining of data
	return fmod( rentit_DateDiff( 'h', strtotime( $start_date ), strtotime( $end_date ) ), 24 );
}


/*
this function get start and end date and return the total calculated by time percentage/days/delayed time
*/
//my function
function get_all_date_or_time( $start_date, $end_date, $dayortime = 'day' ) { //dayortime=day\hour|total
	//check is rtl?
	$dropin_date = $start_date;
	$dropoff_date = $end_date;

	$days = rentit_DateDiff( 'd', strtotime( $dropin_date ), strtotime( $dropoff_date ) );
	$hour = get_time_delay( $dropin_date, $dropoff_date );
	//var_dump($dropin_date);
	//var_dump($dropoff_date);
	//var_dump($days);
	//var_dump($hour);
	if ( $dayortime == 'day' ) {
		return $days;
	} elseif ( $dayortime == 'hour' ) {
		return $hour;
	} elseif ( $dayortime == 'total' ) {
		$hour_percentage = get_hour_percentage( $hour );
		//var_dump($days);
		//var_dump($hour_percentage);
		$total = $days + $hour_percentage;
		return $total;
	}

}

/*
	calculate the percentage of delayed time
	*/
//my function

function get_hour_percentage( $days_hour ) {
	if ( $days_hour >= 12 ) {
		$percent_hour = 1;
	} elseif ( $days_hour >= 9 ) {
		$percent_hour = 0.75;
	} elseif ( $days_hour >= 6 ) {
		$percent_hour = 0.5;
	} elseif ( $days_hour >= 3 ) {
			$percent_hour = 0.25;
		}
		//var_dump($percent_hour);
		//die;
	return $percent_hour;
}


/*
		return date ranges for using as text
		*/
//my function

function get_hour_percentage_hours( $days_hour ) {
	if ( $days_hour >= 12 ) {
		$hours = '12' . esc_html__( ' - ', 'rentit' ) . '24';
	} elseif ( $days_hour >= 9 ) {
		$hours = '9' . esc_html__( ' - ', 'rentit' ) . '12';
	} elseif ( $days_hour >= 6 ) {
		$hours = '6' . esc_html__( ' - ', 'rentit' ) . '9';
	} elseif ( $days_hour >= 3 ) {
		$hours = '3' . esc_html__( ' - ', 'rentit' ) . '6';
	}
	return $hours;
}


/*
this function get formatted price to show it can return delayed hour/days/total
*/
//my function

function get_price_of_all_days( $dayortime = 'day' ) {

	$days_hour = get_all_date_or_time( $_GET[ 'start_date' ], $_GET[ 'end_date' ], $dayortime );
	//var_dump($_GET[ 'start_date' ]);
	//var_dump($_GET[ 'end_date' ]);
	//get formatted price
	//the ceil is to round number to get more beautiful number and more than ده هزار

	$price = rentit_get_current_price_product( get_the_ID() );
	//var_dump($price);
	if ( $dayortime == 'day' ) {
		return format_price( ceil( $price * $days_hour / 1000 ) * 1000 );
	} elseif ( $dayortime == 'hour' ) {
		$percent_hour = get_hour_percentage( $days_hour );
		//var_dump($price );
		//var_dump($percent_hour );
		//var_dump(ceil($price * $percent_hour / 1000 ) * 1000 );
		//var_dump(format_price( ceil($price * $percent_hour / 1000 ) * 1000  ));
		//die;					
		//return format_price( ceil($price * $percent_hour / 1000 ) * 1000  );

		return format_price( ceil( $price * $percent_hour / 1000 ) * 1000 );

	} elseif ( $dayortime == 'total' ) {
		//get all day cost
		$allday = get_all_date_or_time( $_GET[ 'start_date' ], $_GET[ 'end_date' ], 'day' );
		//$allday_price = ceil($price * $allday / 1000 ) * 1000 ;
		//var_dump($allday_price);
		//get all hour cost by percentage
		$allhour = get_all_date_or_time( $_GET[ 'start_date' ], $_GET[ 'end_date' ], 'hour' );
		$percent_hour = get_hour_percentage( $allhour );

		$total_day_hour = $allday + $percent_hour;
		$total_day_hour_price = ceil( $price * $total_day_hour / 1000 ) * 1000;
		//var_dump($total_day_hour);
		//return format_price( $allhour_price+$allday_price );
		return format_price( $total_day_hour_price );
	}
}





/*
     return user friendly and ready to output text for days/delayed hour/total price // i dont know (forgotten) but i think its for billing
     */
//my function

function get_price_of_all_days_with_text( $dayortime = 'day' ) {
	$dayortimevalue = get_all_date_or_time( $_GET[ 'start_date' ], $_GET[ 'end_date' ], $dayortime );
	$price_of_all_days = get_price_of_all_days( $dayortime );
	//var_dump($price_of_all_days );
	//var_dump($_GET[ 'start_date' ]);
	//var_dump($_GET[ 'end_date' ]);
	//var_dump($price_of_all_days);
	//die;
	if ( $dayortime == 'day' ) { //total day(s) : 8,700,000 / 8 day(s)
		$text .= esc_html__( 'total day(s) : ', 'rentit' );
		$text .= $price_of_all_days;
		$text .= esc_html__( ' / ', 'rentit' );
		$text .= $dayortimevalue;
		$text .= esc_html__( ' day(s) ', 'rentit' );
	} elseif ( $dayortime == 'hour' ) { //total extra hour(s) : 700,000 / 6 - 9 hour(s)
		//var_dump($price_of_all_days);
		//die;
		$text .= esc_html__( 'total extra hour(s) : ', 'rentit' );
		$text .= $price_of_all_days;
		$text .= esc_html__( ' / ', 'rentit' );
		$text .= get_hour_percentage_hours( $dayortimevalue );
		$text .= esc_html__( ' hour(s) ', 'rentit' );
	} elseif ( $dayortime == 'total' ) { //total : 9,400,000 /  8 day(s) and 6 - 9 hour(s)
		$text .= esc_html__( 'total : ', 'rentit' );
		$text .= $price_of_all_days;
		$text .= esc_html__( ' / ', 'rentit' );
		//$text .= $dayortimevalue;
		$dayortimevalue = get_all_date_or_time( $_GET[ 'start_date' ], $_GET[ 'end_date' ], 'day' );
		$text .= $dayortimevalue . esc_html__( ' day(s) ', 'rentit' );
		if ( get_price_of_all_days( 'hour' ) > 0 ) {
			$text .= esc_html__( ' and ', 'rentit' );
			$dayortimevalue = get_all_date_or_time( $_GET[ 'start_date' ], $_GET[ 'end_date' ], 'hour' );
			$text .= $dayortimevalue . esc_html__( ' hour(s) ', 'rentit' );
		}
		//$text .= esc_html__( ' total time ', 'rentit' );
	}
	return $text;

}