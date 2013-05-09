<?php
App::uses('DateUtil', 'Lib');
/**
 * Class GoogleApi
 */
class GoogleApi {
	/**
	 * @var Google_Client
	 */
	private $__client;

	/**
	 * @var Google_AnalyticsService
	 */
	private $__analyticsService;

	/**
	 * @var string session token key
	 */
	private $__tokenKey = 'access_token';

	private $__refreshTokenKey = 'refresh_token';

	/**
	 * @var string last error message
	 */
	private $__error = '';

	/**
	 * @var Google_Profiles GA profiles
	 */
	private $__profiles = array();

	/**
	 * Client for communicate with Google API
	 * @throws Google_AuthException
	 */
	public function __construct() {
		$this->__initClient();
		$this->__setGoogleAnalyticsServiceLib();
	}

	/**
	 * Set instance of Google_Client to $this->__client
	 */
	private function __setGoogleClientLib() {
		$key = 'Google_Client';
		if (ClassRegistry::isKeySet($key)) {
			$this->__client = ClassRegistry::getObject($key);
		} else {
			App::uses($key, 'Vendor/google-api-php-client/src');
			$this->__client = new Google_Client();
			ClassRegistry::addObject($key, $this->__client);
		}
	}

	/**
	 * Set instance of Google_AnalyticsService to $this->__analyticsService
	 */
	private function __setGoogleAnalyticsServiceLib() {
		$key = 'Google_AnalyticsService';
		if (ClassRegistry::isKeySet($key)) {
			$this->__analyticsService =  ClassRegistry::getObject($key);
		} else {
			App::uses($key, 'Vendor/google-api-php-client/src/contrib');
			$this->__analyticsService = new Google_AnalyticsService($this->__client);
			ClassRegistry::addObject($key, $this->__analyticsService);
		}
	}

	/**
	 * @return Google_AnalyticsService
	 */
	public function getAnalyticsService() {
		return $this->__analyticsService;
	}

	/**
	 * Initialization and configure Google_Client (api lib)
	 */
	private function __initClient() {
		$this->__setGoogleClientLib();
		$this->__client->setApplicationName(Configure::read('GoogleApi.app_name'));
		$this->__client->setClientId(Configure::read('GoogleApi.client_id'));
		$this->__client->setClientSecret(Configure::read('GoogleApi.client_secret'));
		$this->__client->setDeveloperKey(Configure::read('GoogleApi.api_key'));
		$this->__client->setRedirectUri(Configure::read('GoogleApi.redirect_uri'));
		$this->__client->setScopes(Configure::read('GoogleApi.scopes'));
		$this->__client->setAccessType(Configure::read('GoogleApi.access_type'));
		$this->__client->setApprovalPrompt(Configure::read('GoogleApi.approval_prompt'));
		$this->__client->setUseObjects(true);

		// check, if token in session, set it to client
		if (CakeSession::check($this->__tokenKey)) {
			$this->__client->setAccessToken(CakeSession::read($this->__tokenKey));
		}
	}

	/**
	 * Get url for auth in google
	 * @return string
	 */
	public function getAuthUrl() {
		return $this->__client->createAuthUrl();
	}

	/**
	 * Check, is user authorized (access token in session)
	 * @return bool
	 */
	public function isAuthorized() {
		return $this->__client->getAccessToken() ? true : false;
	}

	/**
	 * Authorize user and save token to session
	 * @return bool
	 */
	public function authorize() {
		try {
			$this->__client->authenticate();
		} catch (Google_AuthException $e) {
			$this->__error = $e->getMessage();
			return false;
		}

		// refresh token
		if ($this->__client->isAccessTokenExpired()) {
			if (!$this->__refreshToken()) {
				return false;
			}
		}

		$token = $this->__client->getAccessToken();
		CakeSession::write($this->__tokenKey, $token);
		$this->__saveRefreshToken($token);
		return true;
	}

	/**
	 * Save refresh token is isset
	 * @param string $token json response from google
	 */
	private function __saveRefreshToken($token) {
		$token = json_decode($token, true);
		if (isset($token[$this->__refreshTokenKey])) {
			/** @var $Config Config */
			$Config = ClassRegistry::init('Config');
			$id = $Config->field('id', array(
				'key' => $this->__refreshTokenKey,
			));

			$Config->save(array(
				'id' => $id,
				'site_id' => null,
				'key' => $this->__refreshTokenKey,
				'value' => $token[$this->__refreshTokenKey],
			));
		}
	}

	/**
	 * Refresh expired token
	 * @return bool
	 */
	private function __refreshToken() {
		/** @var $Config Config */
		$Config = ClassRegistry::init('Config');
		$refreshToken = $Config->field('value', array(
			'key' => $this->__refreshTokenKey,
		));

		if ($refreshToken) {
			try {
				$this->__client->refreshToken($refreshToken);
			} catch (Google_AuthException $e) {
				$this->__error = $e->getMessage();
				return false;
			}
		} else {
			$this->__error = __('Error to refresh token. You must allow access to your data.');
			return false;
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function getLastError() {
		return $this->__error;
	}

/////////////////////// Analytic methods ///////////////////////
	/**
	 * Get profile id for site
	 * @param string $siteUrl
	 * @return int|null
	 */
	public function getProfileId($siteUrl) {
		static $cache = array();
		if (isset($cache[$siteUrl])) {
			return $cache[$siteUrl];
		}

		if (!$this->__profiles) {
			$this->__profiles = $this->__analyticsService->management_profiles->listManagementProfiles("~all", "~all");
		}

		$result = null;

		/** @var $item Google_Profile */
		foreach ($this->__profiles->items as $item) {
			if ($item->websiteUrl == $siteUrl) {
				$result = $item->getId();
				break;
			}
		}

		if (empty($result)) {
			App::uses('UrlFormat', 'Lib');
			$siteDomain = UrlFormat::extractDomain($siteUrl);
			foreach ($this->__profiles->items as $item) {
				if (UrlFormat::extractDomain($item->websiteUrl) == $siteDomain) {
					$result = $item->getId();
					break;
				}
			}
		}

		$cache[$siteUrl] = $result;

		return $result;
	}

	/**
	 * Get visitors.
	 * Result fields are different for different queries.
	 * @param int $profileId
	 * @param string $beginDate mysql format date
	 * @param string $endDate mysql format date
	 * @param array $options <pre>Available keys:
	 * 	dimensions - example: 'ga:medium'
	 *	max-results - example: 50,
	 * 	sort - example: '-ga:visitors',
	 * 	filters - example: 'ga:medium==referral'
	 * </pre>
	 * @param bool $prev get results for prev period or not
	 * @return array
	 * <pre>
	 * 	array(current => array(...), prev => array(...))
	 * </pre>
	 */
	public function getVisitors($profileId, $beginDate, $endDate, $options = array(), $prev = true) {
		$options = array_merge(array(
			'dimensions' => 'ga:medium',
			'max-results' => 50,
			'sort' => '-ga:visitors',
		), 	$options);
		$result = array();

		$result['current'] = $this->__analyticsService->data_ga
			->get('ga:' . $profileId, $beginDate, $endDate, 'ga:visitors', $options)
			->getRows();

		// calculate prev date
		if ($prev) {
			$days = DateUtil::diff($endDate, $beginDate) * 2;
			$prevBeginDate = DateUtil::getDateDbFormat($days . ' day');
			$result['prev'] = $this->__analyticsService->data_ga
				->get('ga:' . $profileId, $prevBeginDate, $beginDate, 'ga:visitors', $options)
				->getRows();
		}

		return $result;
	}

	/**
	 * Combine arrays.
	 * Type '(none)' replaced by 'direct'
	 * @param $current
	 * @param $prev
	 * @return array
	 * <pre>
	 * array(
	 * 	array(
	 * 		type => ,
	 * 		visitors => ,
	 * 		visitors_prev =>
	 * 	),
	 * 	array(...),
	 * );
	 * </pre>
	 */
	private function __trafficByTypes($current, $prev) {
		$result = array();

		foreach ($current as $item) {
			$type = $item[0] == '(none)' ? 'direct' : $item[0];
			$result[$type] = array(
				'type' => $type,
				'visitors' => (int)$item[1],
				'visitors_prev' => 0,
			);
		}

		foreach ($prev as $item) {
			$type = $item[0] == '(none)' ? 'direct' : $item[0];
			$result[$type]['visitors_prev'] = (int)$item[1];
		}

		return array_values($result);
	}

	/**
	 * Prepare data to save. Combine arrays.
	 * @param array $current data for current period
	 * @param array $prev data for previous period
	 * @param string $type name for first result key (source | keyword | )
	 * @return array
	 * <pre>
	 * array(
	 * 	array(
	 * 		$type => ,
	 * 		visitors => ,
	 * 		visitors_prev =>
	 * 	),
	 * 	array(...),
	 * );
	 * </pre>
	 */
	private function __prepareData($current, $prev, $type) {
		$result = array();

		foreach ($current as $item) {
			$result[$item[0]] = array(
				$type => $item[0],
				'visitors' => (int)$item[1],
				'visitors_prev' => 0,
			);
		}

		foreach ($prev as $item) {
			if (isset($result[$item[0]])) {
				$result[$item[0]]['visitors_prev'] = (int)$item[1];
			}
		}

		return array_values($result);
	}

	/**
	 * Combine arrays.
	 * @param $current
	 * @param $prev
	 * @return array
	 * <pre>
	 * array(
	 * 	array(source: ..., referralPath: ..., visitors: ..., visitors_prev: ...),
	 * 	...,
	 * );
	 * </pre>
	 */
	private function __referringPages($current, $prev) {
		$result = array();

		foreach ($current as $item) {
			$key = $item[0] . $item[1];
			$result[$key] = array(
				'source' => $item[0],
				'referralPath' => $item[1],
				'visitors' => (int)$item[2],
				'visitors_prev' => 0,
			);
		}

		foreach ($prev as $item) {
			$key = $item[0] . $item[1];
			if (isset($result[$key])) {
				$result[$key]['visitors_prev'] = (int)$item[2];
			}
		}

		return array_values($result);
	}

	/**
	 * @param $current
	 * @return array
	 * <pre>
	 * array(
	 * 	direct => array(date1=> visitors, ...),
	 * 	organic => array(date1=> visitors, ...),
	 * 	referral => array(date1=> visitors, ... ),
	 * );
	 * </pre>
	 */
	private function __monthSourcesChart($current) {
		$result = array();

		foreach ($current as $item) {
			$type = $item[1] == '(none)' ? 'direct' : $item[1];
			if (!isset($result[$type])) {
				$result[$type] = array();
			}
			$date = DateUtil::getDateDbFormat($item[0]);

			$result[$type][$date] = (int)$item[2];
		}

		return $result;
	}

	/**
	 * @param $current
	 * @return array
	 * <pre>
	 * array(
	 * 	direct => array(0001=> visitors, ...),
	 * 	organic => array(0001=> visitors, ...),
	 * 	referral => array(0001=> visitors, ... ),
	 * );
	 * </pre>
	 */
	private function __yearSourcesChart($current) {
		$result = array();

		foreach ($current as $item) {
			$type = $item[0] == '(none)' ? 'direct' : $item[0];
			if (!isset($result[$type])) {
				$result[$type] = array();
			}

			$result[$type][$item[1]] = (int)$item[2];
		}

		return $result;
	}

	/**
	 * @param $profileId
	 * @param $beginMonthDate
	 * @param $beginYearDate
	 * @param $endDate
	 * @return array
	 */
	public function getSiteDataByProfile($profileId, $beginMonthDate, $beginYearDate, $endDate) {
		$data = array(
			'year' => array(),
			'month' => array(),
		);

		// traffic by types
		$items = $this->getVisitors($profileId, $beginMonthDate, $endDate);
		$data['month']['type'] = $this->__trafficByTypes($items['current'], $items['prev']);
		$items = $this->getVisitors($profileId, $beginYearDate, $endDate);
		$data['year']['type'] = $this->__trafficByTypes($items['current'], $items['prev']);

		// referring sites
		$options = array('dimensions' => 'ga:source', 'max-results' => 20, 'filters' => 'ga:medium==referral');
		$items = $this->getVisitors($profileId, $beginMonthDate, $endDate, $options);
		$data['month']['referring_sites'] = $this->__prepareData($items['current'], $items['prev'], 'source');
		$items = $this->getVisitors($profileId, $beginYearDate, $endDate, $options);
		$data['year']['referring_sites'] = $this->__prepareData($items['current'], $items['prev'], 'source');

		// referring pages
		$options = array('dimensions' => 'ga:source,ga:referralPath', 'max-results' => 20, 'filters' => 'ga:medium==referral');
		$items = $this->getVisitors($profileId, $beginMonthDate, $endDate, $options);
		$data['month']['referring_pages'] = $this->__referringPages($items['current'], $items['prev']);
		$items = $this->getVisitors($profileId, $beginYearDate, $endDate, $options);
		$data['year']['referring_pages'] = $this->__referringPages($items['current'], $items['prev']);

		// keywords
		$options = array('dimensions' => 'ga:keyword', 'max-results' => 20, 'filters' => 'ga:medium==organic');
		$items = $this->getVisitors($profileId, $beginMonthDate, $endDate, $options);
		$data['month']['keywords'] = $this->__prepareData($items['current'], $items['prev'], 'keyword');
		$items = $this->getVisitors($profileId, $beginYearDate, $endDate, $options);
		$data['year']['keywords'] = $this->__prepareData($items['current'], $items['prev'], 'keyword');

		// referring sites - search engines
		$options = array('dimensions' => 'ga:source', 'max-results' => 20, 'filters' => 'ga:medium==organic');
		$items = $this->getVisitors($profileId, $beginMonthDate, $endDate, $options);
		$data['month']['referring_sites_se'] = $this->__prepareData($items['current'], $items['prev'], 'source');
		$items = $this->getVisitors($profileId, $beginYearDate, $endDate, $options);
		$data['year']['referring_sites_se'] = $this->__prepareData($items['current'], $items['prev'], 'source');

		// referring sites - search engines
		$options = array('dimensions' => 'ga:source', 'max-results' => 20, 'filters' => 'ga:medium==organic');
		$items = $this->getVisitors($profileId, $beginMonthDate, $endDate, $options);
		$data['month']['referring_sites_se'] = $this->__prepareData($items['current'], $items['prev'], 'source');
		$items = $this->getVisitors($profileId, $beginYearDate, $endDate, $options);
		$data['year']['referring_sites_se'] = $this->__prepareData($items['current'], $items['prev'], 'source');

		// sources_chart
		$options = array('dimensions' => 'ga:date,ga:medium', 'max-results' => 10000, 'sort' => 'ga:date,ga:medium');
		$items = $this->getVisitors($profileId, $beginMonthDate, $endDate, $options);
		$data['month']['sources_chart'] = $this->__monthSourcesChart($items['current']);
		$options = array('dimensions' => 'ga:medium,ga:nthMonth', 'max-results' => 10000, 'sort' => 'ga:nthMonth,ga:medium');
		// get data for year, begin from previous month
		$beginYearDate = $this->__chartDate($beginYearDate);
		$endDate = $this->__chartDate($endDate, false);
		$items = $this->getVisitors($profileId, $beginYearDate, $endDate, $options);
		$data['year']['sources_chart'] = $this->__yearSourcesChart($items['current']);
		return $data;
	}

	/**
	 * Calculate dates for chart data
	 * @param $date
	 * @param bool $isBeginDate
	 * @return string
	 */
	private function __chartDate($date, $isBeginDate = true) {
		$date = DateUtil::sub($date, '-1 month');
		if ($isBeginDate) {
			$date = DateUtil::firstDayMonth($date);
		} else {
			$date = DateUtil::lastDayMonth($date);
		}

		return $date->format(DateUtil::DB_DATE_FORMAT);
	}

	public function authorizeIfNeeded() {
		if (!$this->isAuthorized() || $this->__client->isAccessTokenExpired()) {
			$this->authorize();
		}
	}

	/**
	 * @param $profileId
	 * @param $date
	 * @return null
	 */
	public function getVisitorsByProfile($profileId, $date) {
// traffic by types
		$options = array('dimensions' => null, 'sort' => null);
		$visitors = $this->getVisitors($profileId, $date, $date, $options, false);

		if (!empty($visitors['current'][0][0])) {
			$visitors = $visitors['current'][0][0];
			return $visitors;
		} else {
			$visitors = null;
			return $visitors;
		}
	}
}