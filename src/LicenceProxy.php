<?php


namespace JFBCore;


use JFBCore\JetFormBuilder\WithInit;

class LicenceProxy {

	use WithInit;

	public function plugin_version_compare(): string {
		return '1.4.0';
	}

	public function on_plugin_init() {
		if ( ! is_admin() ) {
			return;
		}
		require_once __DIR__ . DIRECTORY_SEPARATOR . 'JFB_License_Manager.php';

		\JFB_License_Manager::instance();
	}
}