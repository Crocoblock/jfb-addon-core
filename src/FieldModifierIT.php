<?php


namespace JFBCore;


interface FieldModifierIT {

	public function type(): string;

	public function getFormId(): int;

	public function onRender(): array;

	public function getArgs(): array;

	public function getClass();

	public function renderHandler( $args, $instance ): array;

	public function editorAssets(): void;

	public static function register();

}