<?php

namespace NetteAddons\Model\Importers;

use Nette\Utils\Strings;

/**
 * @author	Patrik Votoček
 */
class GitHubImporter extends \Nette\Object implements \NetteAddons\Model\IAddonImporter
{
	/** @var GitHub\Repository */
	private $repository;

	/**
	 * @param callable
	 * @param string
	 */
	public function __construct($repositoryFactory, $url)
	{
		$this->repository = callback($repositoryFactory)->invoke($url);
	}

	/**
	 * @return Addon
	 */
	public function import()
	{
		try {
			$repo = $this->repository->getMetadata();
			if (!$repo) {
				return NULL;
			}
		} catch(\NetteAddons\InvalidStateException $e) {
			if ($e->getCode() == 404) {
				return NULL;
			}
			throw $e;
		}

		$addon = new \NetteAddons\Model\Addon;
		$addon->name = $this->repository->getVendor()." ".$this->repository->getName();
		$addon->description = $this->repository->getReadme();
		if (isset($repo->description)) {
			$addon->shortDescription = Strings::truncate($repo->description, 250);
		}

		$composer = GitHub\Helpers::decodeJSON($this->repository->getComposerJson());
		if ($composer) {
			$addon->repository = "http://github.com/{$this->repository->getVendor()}/{$this->repository->getName()}";
			if (isset($composer->name)) {
				$addon->composerName = $composer->name;
				$addon->name = str_replace('/', ' ', $composer->name);
			}
			if (isset($composer->description)) {
				$addon->shortDescription = Strings::truncate($composer->description, 250);
			}
			if (isset($composer->keywords)) {
				$addon->tags = $composer->keywords;
			}
		}

		return $addon;
	}

	/**
	 * @return AddonVersion[]
	 */
	public function importVersions()
	{
		$versions = array();
		foreach($this->repository->getVersionsComposersJson() as $v => $data) {
			$composer = GitHub\Helpers::decodeJSON($data);
			$version = new \NetteAddons\Model\AddonVersion;
			$version->version = Strings::startsWith($v, 'v') ? Strings::substring($v, 1) : $v;
			$version->composerJson = GitHub\Helpers::decodeJSON($data, TRUE);

			if (isset($composer->license)) {
				$version->license = is_array($composer->license)
					? implode(',', $composer->license) : $composer->license;
			} else {
				//@todo throw exception!
			}
			if (isset($composer->require)) {
				$version->require = $version->composerJson['require'];
			}
			if (isset($composer->recommend)) {
				$version->recommend = $version->composerJson['recommend'];
			}
			if (isset($composer->suggest)) {
				$version->suggest = $version->composerJson['suggest'];
			}
			if (isset($composer->conflict)) {
				$version->conflict = $version->composerJson['conflict'];
			}
			if (isset($composer->replace)) {
				$version->replace = $version->composerJson['replace'];
			}
			if (isset($composer->provide)) {
				$version->provide = $version->composerJson['provide'];
			}

			$versions[$v] = $version;
		}

		return $versions;
	}
}