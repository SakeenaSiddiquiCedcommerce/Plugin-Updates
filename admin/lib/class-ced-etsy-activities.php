<?php

class Etsy_Activities {

	public $action;
	public $type;
	public $input_payload;
	public $reponse;
	public $post_id;
	public $post_title;
	public $shop_name;

	public function __construct() {
		$this->action        = '';
		$this->type          = '';
		$this->input_payload = array();
		$this->response      = array();
		$this->post_id       = '';
		$this->post_title    = '';
		$this->is_auto       = false;
		$this->shop_name     = '';
	}

	public function execute() {
		$activity_log = get_option( 'ced_etsy_' . $this->type . '_logs_' . $this->shop_name, '' );
		if ( empty( $activity_log ) ) {
			$activity_log = array();
		} else {
			$activity_log = array_reverse( json_decode( $activity_log, true ) );
		}

		$activity_log[] = array(
			'time'          => date_i18n( 'F j, Y g:i a' ),
			'action'        => $this->action,
			'input_payload' => $this->input_payload,
			'response'      => $this->response,
			'post_id'       => $this->post_id,
			'post_title'    => $this->post_title,
			'is_auto'       => $this->is_auto,
		);

		$activity_log = array_slice( array_reverse( $activity_log ), 0, 1000 );

		update_option( 'ced_etsy_' . $this->type . '_logs_' . $this->shop_name, json_encode( $activity_log ) );
	}

}
