<?php

namespace NetteAddons;

use Nette\Http,
	NetteAddons\Model\Addon,
	NetteAddons\Model\Addons,
	NetteAddons\Model\AddonDownloads,
	NetteAddons\Model\AddonVersions,
	NetteAddons\Model\AddonVotes;



/**
 * @author Jan Marek
 * @author Jan Tvrdík
 */
class DetailPresenter extends BasePresenter
{
	/**
	 * @var int addon ID
	 * @persistent
	 */
	public $id;

	/** @var Addon */
	private $addon;

	/** @var Addons */
	private $addons;

	/** @var AddonDownloads */
	private $addonDownloads;

	/** @var AddonVersions */
	private $addonVersions;

	/** @var AddonVotes */
	private $addonVotes;

	/**
	 * @param Addons
	 * @param AddonDownloads
	 * @param AddonVersions
	 * @param AddonVotes
	 */
	public function injectAddons(Addons $addons, AddonDownloads $downloads, AddonVersions $versions, AddonVotes $votes)
	{
		$this->addons = $addons;
		$this->addonDownloads = $downloads;
		$this->addonVersions = $versions;
		$this->addonVotes = $votes;
	}



	protected function startup()
	{
		parent::startup();

		if (!$row = $this->addons->find($this->id)) {
			$this->error('Addon not found!');
		}

		$this->addon = Addon::fromActiveRow($row);
		$this->addonVersions->rsort($this->addon->versions);
	}



	/**
	 * @param int addon ID
	 */
	public function renderDefault($id)
	{
		$description = $this->textPreprocessor->processDescription($this->addon);

		$this->template->content = $description['content'];
		$this->template->toc = $description['toc'];
		$this->template->netteRepositoryUrl = $this->getHttpRequest()->getUrl()->getBaseUrl();
	}



	/**
	 * @param int addon ID
	 */
	public function renderVersions($id)
	{
	}



	/**
	 * @secured
	 * @param string version identifier
	 */
	public function handleDownload($version = NULL)
	{
		if ($version === NULL) { // current
			$version = $this->addonVersions->getCurrent($this->addon->versions);
		} elseif (isset($this->addon->versions[$version])) { // archive
			$version = $this->addon->versions[$version];
		} else {
			$this->error('Unknown addon version.');
		}

		$this->addonDownloads->saveDownload(
			AddonDownloads::TYPE_DOWNLOAD,
			$version->id,
			$this->getHttpRequest()->getRemoteAddress(),
			$this->getHttpRequest()->getHeader('user-agent'),
			$this->getUser()->isLoggedIn() ? $this->getUser()->getId() : NULL
		);
		$this->addonVersions->incrementDownloadsCount($version);
		$this->addons->incrementDownloadsCount($this->addon);

		$this->redirectUrl($version->distUrl);
	}



	/**
	 * Handle voting for current addon.
	 *
	 * @author Jan Tvrdík
	 * @param  string 'up' or 'down'
	 * @return void
	 * @secured
	 */
	public function handleVote($vote)
	{
		$trans = array(
			'up' => 1,
			'cancel' => 0,
			'down' => -1,
		);

		if (!isset($trans[$vote])) {
			$this->error('invalid vote');
		} else {
			$vote = $trans[$vote];
		}

		if (!$this->user->loggedIn) {
			$this->error('not logged in', 403); // TODO: better
		}

		$this->addonVotes->vote($this->id, $this->user->id, $vote);
		if ($this->isAjax()) {
			$this->invalidateControl('rating');
		} else {
			$this->flashMessage('Voting was successfull!');
			$this->redirect('this');
		}
	}



	protected function beforeRender()
	{
		parent::beforeRender();

		$currentVersion = $this->addonVersions->getCurrent($this->addon->versions);
		$popularity = $this->addonVotes->calculatePopularity($this->addon->id);

		if ($this->getUser()->isLoggedIn()) {
			$row = $this->addonVotes->findOneBy(array(
				'userId' => $this->getUser()->getId(),
				'addonId' => $this->addon->id,
			));
			$myVote = $row ? $row->vote : NULL;
		} else {
			$myVote = NULL;
		}

		$this['subMenu']->setAddon($this->addon);

		$this->template->addon = $this->addon;
		$this->template->version = $currentVersion;
		$this->template->composer = $currentVersion->composerJson;

		$this->template->plus = $popularity->plus;
		$this->template->minus = $popularity->minus;
		$this->template->percents = $popularity->percent;
		$this->template->myVote = $myVote;
	}
}
