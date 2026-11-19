<?php


namespace JFBCore;


use JFBCore\Exceptions\ApiHandlerException;

abstract class BaseHandler {

	protected $api_base_url = '';
	protected $api_key      = '';
	protected $request_args = array();

	public function __construct() {
		if ( wp_doing_ajax() && ! empty( $this->ajax_action() ) ) {
			add_action( 'wp_ajax_' . $this->ajax_action(), array( $this, 'get_api_data' ) );
		}
	}

	/**
	 * @return array
	 *
	 * Example:
	 * array(
	 *      'arg_name' => array(
	 *          'sanitize_func' => 'sanitize_text_field'
	 *          'save_func'     => 'some_save_callback',
	 *          'required'      => false
	 *      )
	 * )
	 */
	public function required_ajax_args() {
		return array(
			'api_key' => array(
				'sanitize_func' => 'sanitize_text_field',
			)
		);
	}

	public function get_arg( $arg_name = false, $if_not_exist = false ) {
		if ( ! $arg_name ) {
			return $this->request_args;
		}

		return isset( $this->request_args[ $arg_name ] ) ? $this->request_args[ $arg_name ] : $if_not_exist;
	}

	public function set_arg( $arg_name, $value ) {
		$this->request_args[ $arg_name ] = $value;

		return $this;
	}

	public function set_args_from( $source = array() ) {
		$args_keys = array_keys( $this->required_ajax_args() );

		foreach ( $args_keys as $args_key ) {
			if ( isset( $source[ $args_key ] ) ) {
				$this->set_arg( $args_key, $source[ $args_key ] );
			}
		}
	}


	abstract public function ajax_action(): string;

	abstract public function get_all_data();

	public function get_api_request_args() {
		return array();
	}

	/**
	 * Who may call the editor AJAX of this handler. It works with the site-wide
	 * API key, so it is limited to form editors (JetFormBuilder) and admins (JetEngine).
	 *
	 * Filter `jfb-addon-core/handler/can-request` lets a site allow its own role.
	 *
	 * @return bool
	 */
	public function current_user_can_request(): bool {
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$form_capability = apply_filters( 'jet-form-builder/capability/form', 'manage_options' );

		$can = current_user_can( 'edit_jet_fb_forms' )
			|| current_user_can( $form_capability )
			|| current_user_can( 'manage_options' );

		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		return (bool) apply_filters( 'jfb-addon-core/handler/can-request', $can, $this );
	}

	/**
	 * Return true once the addon's editor JS sends `_nonce` (see create_nonce()).
	 * Kept opt-in so addons rebuilt with this library but not yet with updated JS
	 * keep working; the capability check above applies to all of them.
	 *
	 * @return bool
	 */
	public function requires_nonce(): bool {
		return false;
	}

	public function nonce_action(): string {
		return 'jfb-addon-handler-' . $this->ajax_action();
	}

	/**
	 * Nonce for the editor JS: send it as `_nonce` with the AJAX request.
	 *
	 * @return string
	 */
	public function create_nonce(): string {
		return wp_create_nonce( $this->nonce_action() );
	}

	protected function verify_request() {
		if ( ! $this->current_user_can_request() ) {
			wp_send_json_error(
				array( 'message' => __( 'You are not allowed to perform this request.', 'jet-form-builder' ) )
			);
		}

		if ( ! $this->requires_nonce() ) {
			return;
		}

		$nonce = sanitize_key( wp_unslash( $_REQUEST['_nonce'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, $this->nonce_action() ) ) {
			wp_send_json_error(
				array( 'message' => __( 'The page is outdated. Please, reload it and try again.', 'jet-form-builder' ) )
			);
		}
	}

	public function get_api_data() {
		$this->verify_request();

		try {
			foreach ( $this->required_ajax_args() as $ajax_arg => $options ) {
				if ( empty( $_REQUEST[ $ajax_arg ] ) && ! empty( $options['required'] ) ) {
					wp_send_json_error( array( "Empty {$ajax_arg}", $options ) );
				}
				$value = isset( $_REQUEST[ $ajax_arg ] ) ? $_REQUEST[ $ajax_arg ] : '';

				if ( isset( $options['sanitize_func'] ) && is_callable( $options['sanitize_func'] ) ) {
					$value = call_user_func( $options['sanitize_func'], $value );
				}

				if ( isset( $options['save_func'] ) && is_callable( $options['save_func'] ) ) {
					call_user_func( $options['save_func'], $value );
				} else {
					$this->request_args[ $ajax_arg ] = $value;
				}
			}
			$this->filter_result();
		} catch ( ApiHandlerException $exception ) {
			wp_send_json_error( $this->parse_exception( $exception ) );
		}
	}

	protected function parse_exception( ApiHandlerException $exception ) {
		return array_merge( array(
			'message' => $exception->getMessage()
		), $exception->getAdditional() );
	}

	public function filter_result() {
		$data = $this->get_all_data();

		if ( empty( $data ) ) {
			wp_send_json_error();
		} else {
			wp_send_json_success( $data );
		}
	}

	public function request( $end_point, $request_args = array() ) {
		$args = $this->get_api_request_args();

		$args     = array_merge_recursive( $args, $request_args );
		$response = wp_remote_request( $this->api_base_url . $end_point, $args );

		if ( ! $response || is_wp_error( $response ) ) {
			return false;
		}

		$data = wp_remote_retrieve_body( $response );

		if ( ! $data ) {
			return array();
		}

		$data = json_decode( $data, true );

		return $data;
	}

}