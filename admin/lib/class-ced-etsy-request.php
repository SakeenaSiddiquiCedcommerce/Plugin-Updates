<?php
namespace Cedcommerce\EtsyManager;

class Ced_Etsy_Request {
	/**
	 * Etsy Cleint ID variable
	 *
	 * @var int
	 */
	public $client_id = 'ghvcvauxf2taqidkdx2sw4g4';
	/**
	 * Base URL for Etsy API.
	 *
	 * @var string
	 */
	public $base_url = 'https://api.etsy.com/v3/';
	/**
	 * Etsy API Key.
	 *
	 * @var string
	 */
	public $client_secret = 'hznh7z8xkb';
	/**
	 * Delete method Etsy API.
	 *
	 * @since    1.0.0
	 */
	public function delete( $action = '', $shop_name = '', $query_args = array(), $method = 'DELETE' ) {
		$api_url = $this->base_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		$header = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'x-api-key: ' . $this->client_id,
		);

		$access_token = $this->get_access_token( $shop_name );
		if ( ! empty( $access_token ) ) {
			$header[] = 'Authorization: Bearer ' . $access_token;
		}
		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'DELETE',
				CURLOPT_HTTPHEADER     => $header,
			)
		);

		$response = curl_exec( $curl );
		$response = $this->parse_reponse( $response );

		if ( isset( $response['error'] ) && 'invalid_token' == $response['error'] ) {
			update_option( 'ced_etsy_reauthorize_account', 'yes' );
		}

		curl_close( $curl );
		return $response;

	}
	/**
	 * *************************
	 *  PUT METHOD ETSY API
	 * *************************
	 *
	 * @param string $action
	 * @param array  $parameters
	 * @param string $shop_name
	 * @param array  $query_args
	 * @param string $request_type
	 * @return array
	 */
	public function put( $action = '', $parameters = array(), $shop_name = '', $query_args = array(), $request_type = 'PUT' ) {
		$api_url = $this->base_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		$header = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'x-api-key: ' . $this->client_id,
		);

		$access_token = $this->get_access_token( $shop_name );
		if ( ! empty( $access_token ) && 'public/oauth/token' != $action ) {
			$header[] = 'Authorization: Bearer ' . $access_token;
		}
		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'PUT',
				CURLOPT_POSTFIELDS     => json_encode( $parameters ),
				CURLOPT_HTTPHEADER     => $header,
			)
		);
		$response = curl_exec( $curl );
		$response = $this->parse_reponse( $response );

		if ( isset( $response['error'] ) && 'invalid_token' == $response['error'] ) {
			update_option( 'ced_etsy_reauthorize_account', 'yes' );
		}

		curl_close( $curl );
		return $response;
	}

	/**
	 * *************************
	 *  POST METHOD ETSY API
	 * *************************
	 *
	 * @param string $action
	 * @param array  $parameters
	 * @param string $shop_name
	 * @param array  $query_args
	 * @param string $request_type
	 * @param string $content_type
	 * @return array
	 */
	public function post( $action = '', $parameters = array(), $shop_name = '', $query_args = array(), $request_type = 'POST', $content_type = '' ) {

		$api_url = $this->base_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}
		if ( empty( $content_type ) ) {
			$content_type = 'application/json';
		}
		$header = array(
			'Content-Type:' . $content_type,
			'Accept: application/json',
			'x-api-key: ' . $this->client_id,
		);

		$access_token = $this->get_access_token( $shop_name );
		if ( ! empty( $access_token ) && 'public/oauth/token' !== $action ) {
			$header[] = 'Authorization: Bearer ' . $access_token;
		}

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => $request_type,
				CURLOPT_POSTFIELDS     => json_encode( $parameters ),
				CURLOPT_HTTPHEADER     => $header,
			)
		);

		$response = curl_exec( $curl );
		$response = $this->parse_reponse( $response );

		if ( isset( $response['error'] ) && 'invalid_token' == $response['error'] ) {
			update_option( 'ced_etsy_reauthorize_account', 'yes' );
		}

		curl_close( $curl );
		return $response;

	}

	/**
	 * *********************************
	 *  UPDATE FILE AND IMAGED TO ETSY
	 * *********************************
	 *
	 * @param string $types
	 * @param string $action
	 * @param string $source_file
	 * @param string $file_name
	 * @param string $shop_name
	 * @return object
	 */
	public function ced_etsy_upload_image_and_file( $types, $action, $source_file, $file_name, $shop_name ) {
		$access_token = $this->get_access_token( $shop_name );

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => 'https://openapi.etsy.com/v3/' . $action,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => array(
					$types => new \CURLFile( $source_file ),
					'name' => $file_name,
				),
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: multipart/form-data',
					'x-api-key: ' . $this->client_id,
					'Authorization: Bearer ' . $access_token,
				),
			)
		);
		$response = curl_exec( $curl );

		if ( isset( $response['error'] ) && 'invalid_token' == $response['error'] ) {
			update_option( 'ced_etsy_reauthorize_account', 'yes' );
		}

		curl_close( $curl );
		return $response;
	}

	/**
	 * *************************
	 *  GET METHOD ETSY API
	 * *************************
	 *
	 * @param string $action
	 * @param string $shop_name
	 * @param array  $query_args
	 * @return array
	 */
	public function get( $action = '', $shop_name = '', $query_args = array() ) {

		$api_url = $this->base_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		$header = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'x-api-key: ' . $this->client_id,
		);

		$access_token = $this->get_access_token( $shop_name );
		if ( ! empty( $access_token ) ) {
			$header[] = 'Authorization: Bearer ' . $access_token;
		}

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $api_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'GET',
				CURLOPT_HTTPHEADER     => $header,
			)
		);

		$response = curl_exec( $curl );
		$response = $this->parse_reponse( $response );

		if ( isset( $response['error'] ) && 'invalid_token' == $response['error'] ) {
			update_option( 'ced_etsy_reauthorize_account', 'yes' );
		}

		curl_close( $curl );
		return $response;

	}

	/**
	 * Parse Etsy reponse.
	 *
	 * @param object $response
	 * @return array
	 */
	public function parse_reponse( $response ) {
		return json_decode( $response, true );
	}

	/**
	 * Get access token.
	 *
	 * @param string $shop_name
	 * @return string
	 */
	public function get_access_token( $shop_name = '' ) {
		$user_details     = get_option( 'ced_etsy_details', array() );
			$access_token = isset( $user_details[ $shop_name ]['details']['token']['access_token'] ) ? $user_details[ $shop_name ]['details']['token']['access_token'] : '';
		return ! empty( $access_token ) ? $access_token : '';
	}
}
