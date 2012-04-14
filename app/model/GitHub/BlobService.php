<?php

namespace NetteAddons\Model\GitHub;

/**
 * @author	Patrik Votoček
 */
class BlobService extends \Nette\Object
{
	const METHOD_GET = 'GET';

	/** @var string */
	private $baseUrl;
	/** @var \NetteAddons\Curl */
	private $curl;

	/**
	 * @param \NetteAddons\Curl
	 * @param string
	 */
	public function __construct(\NetteAddons\Curl $curl, $baseUrl = 'https://raw.github.com')
	{
		$this->baseUrl = $baseUrl;
		$this->curl = $curl;
	}

	/**
	 * @param string
	 * @param string
	 * @return stdClass|NULL
	 * @throws \NetteAddons\NotImplementedException
	 */
	public function exec($path, $method = self::METHOD_GET)
	{
		$url = new \Nette\Http\Url($this->baseUrl);
		$url->setPath($path);

		if ($method != self::METHOD_GET) {
			throw new \NetteAddons\NotImplementedException;
		}

		return $this->curl->get($url);
	}
}