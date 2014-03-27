<?php

namespace NetteAddons;


final class HomepagePresenter extends BaseListPresenter
{
	const ADDONS_LIMIT = 3;

	/**
	 * @inject
	 * @var \NetteAddons\Model\DevelopmentUtils
	 */
	public $developmentUtils;


	public function renderDefault()
	{
		$ignoreDeleted = $this->auth->isAllowed('addon', 'delete');

		$this->template->updatedAddons = $this->addons->findLastUpdated(self::ADDONS_LIMIT, $ignoreDeleted);
		$this->template->favoritedAddons = $this->addons->findMostFavorited(self::ADDONS_LIMIT, $ignoreDeleted);

		$this->template->categories = $categories = $this->tags->findMainTagsWithAddons();
		$this->template->addons = $this->addons->findGroupedByCategories($categories, $ignoreDeleted);
	}
}
