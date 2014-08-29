<?php

namespace OpenTok\Util;

use OpenTok\Util\Client as DefaultClient;

use OpenTok\Exception\Exception;
use OpenTok\Exception\DomainException;
use OpenTok\Exception\UnexpectedValueException;
use OpenTok\Exception\AuthenticationException;

use OpenTok\Exception\ArchiveException;
use OpenTok\Exception\ArchiveDomainException;
use OpenTok\Exception\ArchiveUnexpectedValueException;
use OpenTok\Exception\ArchiveAuthenticationException;

// TODO: build this dynamically
/** @internal */
define('OPENTOK_SDK_VERSION', '2.2.3-alpha.1');
/** @internal */
define('OPENTOK_SDK_USER_AGENT', 'OpenTok-PHP-SDK/' . OPENTOK_SDK_VERSION);

/**
* @internal
*/
class OpenTokClient implements OpenTokClientInterface
{
    protected $apiKey;
    protected $apiSecret;
    protected $configured = false;
    
    /**
     * 
     * @var \OpenTok\Util\ClientInterface
     */
    protected $http_client;

    public function __construct($http_client = null)
    {
    	if(null !== $http_client) {
    		$this->http_client = $http_client;
    	}
    }

    public function configure($apiKey, $apiSecret, $apiUrl)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->getHttpClient()->setBaseUrl($apiUrl);
        $this->http_client->setUserAgent(OPENTOK_SDK_USER_AGENT, true);
        $this->http_client->addAuth($this->apiKey, $this->apiSecret);
        
        $this->configured = true;
    }

    public function isConfigured() {
        return $this->configured;
    }

    // General API Requests

    public function createSession($options)
    {
        $this->getHttpClient()->post('/session/create');
        $this->http_client->setParameterPost($this->postFieldsForOptions($options));
        
        return $this->http_client->send()->xml();
    }

    // Archiving API Requests

    public function startArchive($params)
    {
        $this->getHttpClient()->post('/v2/partner/'.$this->apiKey.'/archive');
        $this->http_client->setBody(json_encode($params));
        $this->http_client->setHeader('Content-Type', 'application/json');

        try {
        	$archiveJson = $this->http_client->send()->json();
        } catch (\Exception $e) {
            $this->handleArchiveException($e);
        }
        
        return $archiveJson;
    }

    public function stopArchive($archiveId)
    {
        // set up the request
        $this->getHttpClient()->post('/v2/partner/'.$this->apiKey.'/archive/'.$archiveId.'/stop');
        $this->http_client->setHeader('Content-Type', 'application/json');

        try {
            $archiveJson = $this->http_client->send()->json();
        } catch (\Exception $e) {
            // TODO: what happens with JSON parse errors?
            $this->handleArchiveException($e);
        }
        return $archiveJson;
    }

    public function getArchive($archiveId)
    {
        $this->getHttpClient()->get('/v2/partner/'.$this->apiKey.'/archive/'.$archiveId);
        return $this->http_client->send()->json();
    }

    public function deleteArchive($archiveId)
    {
        $this->getHttpClient()->delete('/v2/partner/'.$this->apiKey.'/archive/'.$archiveId);
        $this->http_client->setHeader('Content-Type', 'application/json');
        $this->http_client->send()->json();

        return true;
    }

    public function listArchives($offset, $count)
    {
        $this->getHttpClient()->get('/v2/partner/'.$this->apiKey.'/archive');
        if ($offset != 0) {
        	$this->http_client->setParameterGet(array('offset', $offset));
        }
        if (!empty($count)) {
        	$this->http_client->setParameterGet(array('count', $count));
        }
        
        return $this->http_client->send()->json();
    }

    // Helpers

    private function postFieldsForOptions($options)
    {
        $options['p2p.preference'] = empty($options['mediaMode']) ? MediaMode::ROUTED : $options['mediaMode'];
        unset($options['mediaMode']);
        if (empty($options['location'])) {
            unset($options['location']);
        }
        $options['api_key'] = $this->apiKey;
        
        return $options;
    }

    /**
     * Get HTTP Client
     * 
     * @return \OpenTok\Util\ClientInterface
     */
    public function getHttpClient()
    {
    	if(null === $this->http_client) {
    		$this->http_client = new DefaultClient();
    	}
    	
    	return $this->http_client;
    }
    
    private function handleArchiveException($e)
    {
    	try {
    		throw $e;
    	} catch (AuthenticationException $ae) {
    		throw new ArchiveAuthenticationException($this->apiKey, $this->apiSecret, null, $ae->getPrevious());
    	} catch (DomainException $de) {
    		throw new ArchiveDomainException($e->getMessage(), null, $de->getPrevious());
    	} catch (UnexpectedValueException $uve) {
    		throw new ArchiveUnexpectedValueException($e->getMessage(), null, $uve->getPrevious());
    	} catch (Exception $oe) {
    		// TODO: check if this works because ArchiveException is an interface not a class
    		throw new ArchiveException($e->getMessage(), null, $oe->getPrevious());
    	}
    }
}
