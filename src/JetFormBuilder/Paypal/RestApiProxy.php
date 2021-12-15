<?php


namespace JFBCore\JetFormBuilder\Paypal;


abstract class RestApiProxy {

	abstract public function routes(): array;

	public static function register() {
		$self = new static();

		add_filter(
			'jet-form-builder/gateways/paypal/rest-api-endpoints',
			array( $self, 'add_routes' )
		);
	}

	public function add_routes( $endpoints ) {
		return array_merge( $endpoints, $this->routes() );
	}

}