<?php

namespace LanSuite\Module\Stats;

use Symfony\Component\HttpFoundation\Request;

class Stats
{

    public function __construct(Request $request)
    {
        global $db, $cfg;

        $httpReferer = $request->server->get('HTTP_REFERER');
        $httpUserAgent = $request->server->get('HTTP_USER_AGENT');
        $httpAcceptLanguage = $request->server->get('HTTP_ACCEPT_LANGUAGE');

        // Try not to count search engine bots
        // Bad Examples:
        //   Baiduspider+(+http://www.baidu.jp/spider/)
        //   msnbot/2.0b (+http://search.msn.com/msnbot.htm)
        //   Mozilla/5.0 (compatible; Exabot/3.0; +http://www.e...
        //   Mozilla/5.0 (compatible; Googlebot/2.1; +http://ww...
        // see also http://www.user-agents.org/

        if (!str_contains(strtolower($httpUserAgent), 'bot')
            && !str_contains(strtolower($httpUserAgent), 'spider')
            && !str_contains(strtolower($httpUserAgent), 'crawl')
            && !str_contains(strtolower($httpUserAgent), 'search')
            && !str_contains(strtolower($httpUserAgent), 'google')
            && !str_contains(strtolower($httpUserAgent), 'find')) {

            if (array_key_exists('log_browser_stats', $cfg) && $cfg['log_browser_stats']) {
                $db->qry(
                    '
                  INSERT INTO %prefix%stats_browser
                  SET
                    useragent = %string%,
                    referrer = %string%,
                    accept_language = %string%',
                    $httpUserAgent,
                    $httpReferer,
                    $httpAcceptLanguage,
                );
            }

            // Update usage stats
            // Is the user known, or is it a new visit? - After 30min idle this counts as a new visit
            // Existing session -> Only hit
            if (array_key_exists('last_hit', $_SESSION) && $_SESSION['last_hit'] > (time() - 60 * 30)) {
                $db->qry("
                  INSERT INTO %prefix%stats_usage
                  SET
                    visits = 0,
                    hits = 1,
                    time = DATE_FORMAT(NOW(), '%Y-%m-%d %H:00:00')
                  ON DUPLICATE KEY UPDATE hits = hits + 1;");

            // New session -> Hit and visit
            } else {
                $db->qry("
                  INSERT INTO %prefix%stats_usage
                  SET
                    visits = 1,
                    hits = 1,
                    time = DATE_FORMAT(NOW(), '%Y-%m-%d %H:00:00')
                  ON DUPLICATE KEY UPDATE visits = visits + 1, hits = hits + 1;");
            }
            $_SESSION['last_hit'] = time();

            // Update search engine data
            $search_engine = '';
            if (strpos($httpReferer, 'ttps://www.google.') > 0) {
                $search_engine = 'google';
            } elseif (strpos($httpReferer, '.yahoo.com/search') > 0) {
                $search_engine = 'yahoo';
            } elseif (strpos($httpReferer, '.altavista.com') > 0) {
                $search_engine = 'altavista';
            } elseif (strpos($httpReferer, 'ttp://search.msn.') > 0) {
                $search_engine = 'msn';
            } elseif (strpos($httpReferer, '.aol.de/suche') > 0) {
                $search_engine = 'aol_de';
            } elseif (strpos($httpReferer, 'search.aol.com/') > 0) {
                $search_engine = 'aol_com';
            } elseif (strpos($httpReferer, '.web.de/') > 0) {
                $search_engine = 'web_de';
            }

            if ($search_engine != '') {
                $query_var = array(
                    "google" => 'q',
                    "yahoo" => "p",
                    "altavista" => "q",
                    "msn" => "q",
                    "aol_de" => "q",
                    "aol_com" => "query",
                    "web_de" => "su"
                );

                // Read URL parameters into an array
                $url_paras = explode("?", $httpReferer); // URL part behind ? -> $url_paras[1]
                $url_paras = explode("&", $url_paras[1]);

                foreach ($url_paras as $akt_para) {
                    [$para_var, $para_val] = explode("=", $akt_para);

                    // Search for parameter containing the search term
                    if ($para_var == $query_var[$search_engine]) {
                        $row = $db->qry_first_rows("SELECT term FROM %prefix%stats_se WHERE term = %string% AND se = %string%", $para_val, $search_engine);
                        if ($row["number"] > 0) {
                            $db->qry("UPDATE %prefix%stats_se SET hits = hits + 1 WHERE term = %string% AND se = %string%", $para_val, $search_engine);
                        } else {
                            $db->qry("INSERT INTO %prefix%stats_se SET hits = 1, term = %string%, se = %string%, first = NOW()", $para_val, $search_engine);
                        }
                    }
                }
            }
        }
    }
}
