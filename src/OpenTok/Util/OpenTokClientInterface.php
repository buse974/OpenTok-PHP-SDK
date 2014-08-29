<?php

namespace OpenTok\Util;

interface OpenTokClientInterface
{
	public function configure($apiKey, $apiSecret, $apiUrl);
	public function isConfigured();
	public function createSession($options);
	public function startArchive($params);
	public function stopArchive($archiveId);
	public function getArchive($archiveId);
	public function deleteArchive($archiveId);
	public function listArchives($offset, $count);
	public function getHttpClient();
}