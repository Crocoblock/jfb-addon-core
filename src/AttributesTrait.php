<?php


namespace JFBCore;


trait AttributesTrait {

	public $attrs = array();

	/**
	 * Add attribute
	 *
	 * @param $attr
	 * @param null $value
	 */
	public function add_attribute( $attr, $value = null ) {

		if ( '' === $value ) {
			return;
		}
		if ( ! isset( $this->attrs[ $attr ] ) ) {
			$this->attrs[ $attr ] = $value;
		} else {
			$this->attrs[ $attr ] .= ' ' . $value;
		}

	}

	/**
	 * Reset attributes array
	 */
	public function reset_attributes() {
		$this->attrs = array();
	}

	/**
	 * Render current attributes string
	 *
	 * @return void
	 */
	public function render_attributes_string() {
		$this->render_attributes_string_save();
		$this->attrs = array();
	}

	public function render_attributes_string_save() {
		echo $this->get_attributes_string_save();
	}

	public function get_attributes_string_save() {
		$response = '';
		foreach ( $this->attrs as $attr => $value ) {
			// Skip names that could break out of the tag (Alpine/Vue names like @change, x-on:input stay valid).
			if ( ! is_string( $attr ) || ! preg_match( '/^[A-Za-z_:@][A-Za-z0-9_:@.\-]*$/', $attr ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = implode( ' ', $value );
			}
			$response .= sprintf( ' %1$s="%2$s"', $attr, esc_attr( (string) $value ) );
		}

		return $response;
	}

	public function get_attributes_string() {
		$response = $this->get_attributes_string_save();
		$this->attrs = array();

		return $response;
	}

}