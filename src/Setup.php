<?php
namespace WillV\Project;
use Composer\Script\Event;

/**
 * This class should be hooked into composer to set-up the
 * appropriate directories etc on update and install
 *
 */
abstract class Setup {

	private static function getProjectRoot($event) {
		$projectRoot = dirname($event->getComposer()->getConfig()->get('vendor-dir'));
	}

	private static function ensureProjectDirectoryPresent($event, $relativeDirectoryPath) {
		$event->getIO()->write("Making sure the '".basename($relativeDirectoryPath)."' directory is present");

		$projectRoot = dirname($event->getComposer()->getConfig()->get('vendor-dir'));
		$fullDirectoryPath = $projectRoot."/".$relativeDirectoryPath;
		
		if (file_exists($fullDirectoryPath)) {
			return true;
		}

		if (is_file($fullDirectoryPath)) {
			throw new Exception("Could not create directory '".$fullDirectoryPath."' - there is already a file at that location");
		}

		mkdir($fullDirectoryPath);
	}

	public static function ensureControllersDirectoryPresent(Event $event) {
		self::ensureProjectDirectoryPresent($event, "controllers");
	}

	public static function ensureTemplatesDirectoryPresent(Event $event) {
		self::ensureProjectDirectoryPresent($event, "templates");
	}

}