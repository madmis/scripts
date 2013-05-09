<?php
/**
 * Class UrlFormat
 */
class UrlFormat {
	/**
	 * Clean url as site URL
	 * Remove schema part and "www" part from the url
	 * Remove all path information. Keep only domain information.
	 * Example:
	 * input url: http://www.example.com/path?param#hash
	 * result: example.com
	 *
	 * @param string $url
	 * @return string|null Cleaned URL or null if not domain in url
	 */
	public static function extractDomain($url) {
		#$host = parse_url($url, PHP_URL_HOST);// <- not work for an url without a schema part
		$trimedUrl = trim($url);
		$pattern = '#^(?:\w+?://)?(?:www\.)?([^/]+).*$#i';
		preg_match($pattern, $trimedUrl, $matches);
		if (isset($matches[1])) {
			return $matches[1];
		}

		return null;
		// this variant broken if link without domain
		// example: /search?q=the+hunger+games&safe=off&hl=en&as_qdr=all&tbm=isch&tbo=u&source=univ&sa=X&ei=ool-UYXfGanE4APy74GgCA&ved=0CC8QsAQ4Cg
		// come from Seotools (DevShell updateSerp)
//		$cleaned = preg_replace('#^(?:\w+?://)?(?:www\.)?([^/]+).*$#i', '$1', $trimedUrl);
//		return $cleaned;
	}

	public static function extractPage($url) {
		$pageUrl = preg_replace('#^[^:/]+://[^/]+#', '', $url);
		if ($pageUrl === '') {
			$pageUrl = '/';
		}

		$pageUrl = '/' . ltrim($pageUrl, '/');

		return $pageUrl;
	}

	/**
	 * Parse the url and return important segments:
	 * Returns segments:
	 *    schema,
	 *    domain,
	 *    path (path+query options)
	 * Only the "domain" segment is required, all other segments are optional and can be omitted.
	 * If a segment is omitted in result array this part will be equals to an empty string
	 *
	 * @param string $url The url for parsing
	 * @return array|boolean Results of parsing. Keys: schema, domain, path. False on failure.
	 */
	public static function parse($url) {
		if (!preg_match('#(\w+://|)([^/]+)(/.*|)#', $url, $segments)) {
			return false;
		}
		$result = array(
			'schema' => $segments[1],
			'domain' => $segments[2],
			'path' => $segments[3],
		);
		return $result;
	}

	/**
	 * Convert domain name to IDNA ASCII (punycode) form.
	 * @param string $url The url for encoding.
	 * @return mixed Domain name encoded in ASCII-compatible form. or false on failure
	 */
	public static function idnToAscii($url) {
		$segments = self::parse($url);
		if (!$segments) {
			return false;
		}
		$encodedDomain = idn_to_ascii($segments['domain']);
		if (!$encodedDomain) {
			return false;
		}
		$segments['domain'] = $encodedDomain;
		$result = implode('', $segments);
		return $result;
	}

	/**
	 * Check if domain is valid
	 *
	 * @param $domain
	 * @return int
	 */
	public static function isValidDomain($domain) {
		//TODO: check with RFC what should be correct regex
		return preg_match('#^[a-zA-Z0-9][a-zA-Z0-9\-]*\.[a-zA-Z0-9\-\.]*[a-zA-Z]$#', $domain);
	}
}