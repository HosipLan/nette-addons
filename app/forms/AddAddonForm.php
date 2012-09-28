<?php

namespace NetteAddons;

use NetteAddons\Model\Addon;
use NetteAddons\Model\Tags;
use NetteAddons\Model\Utils\FormValidators;
use Nette;
use Nette\Utils\Html;



/**
 * Form for new addon registration. When importing from GitHub, most of the field should be prefilled.
 * The license input won't be visible when composer.json is available.
 */
class AddAddonForm extends BaseForm
{
	/** @var FormValidators */
	private $validators;

	/** @var Tags */
	private $tags;



	public function __construct(FormValidators $validators, Tags $tags)
	{
		$this->validators = $validators;
		$this->tags = $tags;
		parent::__construct();
	}



	protected function buildForm()
	{
		$this->addText('name', 'Name', 40, 100)
			->setRequired();
		$this->addText('composerName', 'Composer name')
			->setRequired()
			->addRule(self::PATTERN, 'Invalid composer name', FormValidators::COMPOSER_NAME_RE)
			->addRule($this->validators->isComposerNameUnique, 'This composer name has been already taken.')
			->setOption('description', '<vendor>/<project-name>, only lowercase letters and dash separation is allowed');
		$this->addText('shortDescription', 'Short description', NULL, 250)
			->setAttribute('class', 'span4')
			->setRequired();
		$this->addTextArea('description', 'Description', 80, 20)
			->setAttribute('class', 'span6')
			->setRequired();
		$this->addText('defaultLicense', 'Default license')
			->setRequired()
			->addRule($this->validators->isLicenseValid, 'Invalid license identifier.')
			->setOption(
				'description',
				Html::el()->setHtml(
					'See <a href="http://www.spdx.org/licenses/">SPDX Open Source License Registry</a>' .
					' for list of possible identifiers. Multiple licenses can be separated by comma.'
				)
			);
		$this->addText('repository', 'Repository URL', 60, 500)
			->setAttribute('class', 'span6')
			->addCondition(self::FILLED)
				->addRule(self::URL);
		$this->addText('demo', 'Demo URL', 60, 500)
			->setAttribute('class', 'span6')
			->addCondition(self::FILLED)
				->addRule(self::URL);

		foreach ($this->tags->findMainTags() as $tag) {
			$categories[$tag->id] = $tag->name;
		}

		$this->addMultiSelect('tags', 'Categories', $categories)
			->setAttribute('class', 'chzn-select');


		$this->addSubmit('create', 'Next');
	}



	/**
	 * Sets default values. Used when importing from GitHub.
	 *
	 * @param Addon
	 */
	public function setAddonDefaults(Addon $addon)
	{
		$this->setDefaults(array(
			'name' => $addon->name,
			'shortDescription' => $addon->shortDescription,
			'description' => $addon->description,
			'defaultLicense' => $addon->defaultLicense,
			'repository' => $addon->repository,
			'demo' => $addon->demo
		));
	}
}
