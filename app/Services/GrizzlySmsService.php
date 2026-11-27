<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrizzlySmsService
{
    protected string $baseUrl = 'https://api.grizzlysms.com/stubs/handler_api.php';
    protected ?string $apiKey;

    /**
     * Comprehensive Map of service codes to readable names for Grizzly SMS / SMS-Activate protocol.
     */
    protected array $serviceDictionary = [
        // Top Global & Messaging
        'tg' => 'Telegram',
        'tgm' => 'Telegram',
        'te' => 'Telegram',
        'wa' => 'WhatsApp',
        'wha' => 'WhatsApp',
        'go' => 'Google / Gmail / YouTube',
        'ig' => 'Instagram',
        'fb' => 'Facebook',
        'tw' => 'Twitter / X',
        'tv' => 'Twitter / X',
        'twi' => 'Twitter / X',
        'vi' => 'Viber',
        'vbr' => 'Viber',
        'vb' => 'Viber',
        'oi' => 'Tinder',
        'tin' => 'Tinder',
        'ds' => 'Discord',
        'nf' => 'Netflix',
        'tk' => 'TikTok',
        'tik' => 'TikTok',
        'lf' => 'TikTok / Douyin',
        'mb' => 'Yahoo',
        'yah' => 'Yahoo',
        'yh' => 'Yahoo',
        'ub' => 'Uber',
        'ua' => 'Uber',
        'ue' => 'Uber Eats',
        'wb' => 'WeChat',
        'wc' => 'WeChat',
        'wx' => 'Apple / iCloud',
        'sn' => 'Snapchat',
        'am' => 'Amazon',
        'li' => 'LinkedIn',
        'lin' => 'LinkedIn',
        'me' => 'Line',
        'ln' => 'Line',
        'pm' => 'AOL',
        'hw' => 'AliPay / Alibaba',
        'kt' => 'KakaoTalk',
        'dr' => 'OpenTable',
        'ya' => 'Yandex',
        'yd' => 'Yandex',
        'yx' => 'Yandex',
        'ym' => 'Yandex Money / YooMoney',
        'vk' => 'VKontakte',
        'ok' => 'Odnoklassniki',
        'od' => 'Odnoklassniki',
        'ma' => 'Mail.ru',
        'bl' => 'Bigo Live',
        'qq' => 'QQ',
        
        // Financial & Crypto
        'ts' => 'PayPal',
        'pp' => 'PayPal',
        're' => 'Coinbase',
        'bd' => 'Binance',
        'bin' => 'Binance',
        'bo' => 'Wise (TransferWise)',
        'ii' => 'Cash App',
        'csh' => 'Cash App',
        'rj' => 'Revolut',
        'rev' => 'Revolut',
        've' => 'Venmo',
        'pa' => 'Payoneer',
        'str' => 'Stripe',
        'sq' => 'Square',
        'kw' => 'KuCoin',
        'ku' => 'KuCoin',
        'byb' => 'Bybit',
        'okx' => 'OKX',
        'kra' => 'Kraken',
        'gem' => 'Gemini',
        'bex' => 'Bitfinex',
        'btx' => 'Bittrex',
        'mxc' => 'MEXC',
        'gat' => 'Gate.io',
        'huo' => 'HTX / Huobi',
        'ld' => 'Ledger',
        'lt' => 'BitForex',
        'ky' => 'Kriptomat',
        'jy' => 'BitClout',
        'jc' => 'Bitso',
        'pc' => 'CoinField',
        'we' => 'WebMoney',
        'wm' => 'WebMoney',
        'ge' => 'PaySend',
        'pe' => 'Paysera',
        'jq' => 'Paysafecard',
        'gr' => 'AstroPay',
        'gm' => 'Monese',
        'py' => 'Monese',
        'mu' => 'Monese',
        'mn' => 'Monobank',
        'mon' => 'Monzo',
        'nu' => 'Nubank',
        'iu' => 'Itau Bank',
        'pj' => 'PrivatBank',
        'kn' => 'KoronaPay',
        'wu' => 'Western Union',
        'jo' => 'SoFi',
        'chk' => 'Chime',
        'jz' => 'Tazapay',
        'lx' => 'Luxon Pay',
        'kg' => 'Kredivo',
        'qg' => 'Qiwi',
        'qh' => 'Qiwi',
        'qi' => 'Qiwi',
        'hc' => 'Paycell',
        'gg' => 'PagSmile',
        'ix' => 'Celcoin',
        'ja' => 'WiZink',
        'jb' => 'Wing Money',
        'jp' => 'JKOPAY',
        'mp' => 'MirPay',
        'mk' => 'Mir',
        'mr' => 'Mir',
        'js' => 'Freecharge',
        'mj' => 'Mercado Pago',
        'cq' => 'Mercado',
        'ov' => 'OVO',
        'dp' => 'ProtonMail',

        // Tech, Cloud & Productivity
        'ms' => 'Microsoft / Outlook / Hotmail',
        'mm' => 'Microsoft / Outlook / Hotmail',
        'off' => 'Office 365',
        'clo' => 'Cloudflare',
        'ns' => 'Notion',
        'wp' => 'WordPress',
        'ra' => 'Keybase',
        'up' => 'Upwork',
        'iy' => 'Fiverr',
        'to' => 'Toptal',
        'kd' => 'Kwork',
        'ji' => 'Medium',
        'zoo' => 'Zoom',
        'id' => 'Intercom',
        'jh' => 'PingCode',
        'si' => 'Signal',
        'gf' => 'Google Voice',
        'tc' => 'Truecaller',
        'im' => 'Imo',
        'io' => 'Imo',
        'tu' => 'Tuya / Smart Life',

        // Entertainment, Gaming & Streaming
        'mt' => 'Steam',
        'ste' => 'Steam',
        'rf' => 'Roblox',
        'rob' => 'Roblox',
        'ps' => 'PlayStation / Sony',
        'xb' => 'Xbox',
        'tt' => 'Twitch',
        'hb' => 'Twitch',
        'spo' => 'Spotify',
        'yt' => 'YouTube',
        'bla' => 'Blizzard / Battle.net',
        'epi' => 'Epic Games',
        'eas' => 'EA Sports',
        'lp' => 'League of Legends',
        'mq' => 'MOBA',
        'rr' => 'Razer',
        'rk' => 'Roku',
        'ze' => 'Zee5',
        'iq' => 'iQIYI',
        'iw' => 'iQIYI',
        'lc' => 'Likee',
        'jw' => 'Kwai',
        'ni' => 'Nimo TV',
        'hg' => 'Huya',
        'kj' => 'Kugou',
        'ru' => 'Rutube',
        'vd' => 'Video',
        'vr' => 'VR Games',
        'll' => '888casino',
        'ie' => 'Betfair',
        'nb' => 'NetBet',
        'pz' => 'Loto',
        'gc' => 'Gamekit',
        'pk' => 'PlayerAuctions',
        'pl' => 'Playtech',
        'pq' => 'CDKeys',
        'ng' => 'Discord Nitro',

        // Dating & Social Apps
        'mo' => 'Bumble',
        'df' => 'Happn',
        'se' => 'Feeld',
        'sb' => 'Skout',
        'ta' => 'Tantan',
        'pi' => 'Pure Dating',
        'pr' => 'Pure Dating',
        'pu' => 'JustDating',
        'lw' => 'Lovoo',
        'mh' => 'Match.com',
        'oc' => 'OkCupid',
        'fd' => 'Mamba',
        'of' => 'OnlyFans',
        'rd' => 'Reddit',
        'red' => 'Reddit',
        'za' => 'Zalo',
        'co' => 'RedBook (XiaoHongShu)',
        'rb' => 'RedBook (XiaoHongShu)',
        'mc' => 'MiChat',
        'tm' => 'TamTam',
        'cla' => 'Clubhouse',
        'kf' => 'Weverse',
        'om' => 'Omegle',
        'hl' => 'Band',
        'hn' => 'Handshake',
        'if' => 'Yalla',
        'gb' => 'YouStar',
        'nk' => 'Nekto.me',
        'sm' => 'YoWin',

        // E-Commerce & Shopping
        'ew' => 'Nike',
        'nq' => 'Nike',
        'nh' => 'Nike',
        'ks' => 'SNKRS (Nike)',
        'eb' => 'eBay',
        'ka' => 'Shopee',
        'sh' => 'Shopee',
        'jl' => 'Lazada',
        'lz' => 'Lazada',
        'tb' => 'Taobao',
        'pt' => 'Pinduoduo',
        'wh' => 'Wish',
        'wd' => 'Wildberries',
        'oz' => 'Ozon',
        'vz' => 'Vinted',
        'jv' => 'Vinted',
        'dep' => 'Depop',
        'pos' => 'Poshmark',
        'ets' => 'Etsy',
        'dhg' => 'DHgate',
        'ly' => 'OLX',
        'jm' => 'Jiji',
        'kh' => 'Bukalapak',
        'gx' => 'Hepsiburada',
        'hv' => 'HepsiGlobal',
        'sk' => 'Skroutz',
        'my' => 'Myntra',
        'lk' => 'Lamoda',
        'lm' => 'Leroy Merlin',
        'ir' => 'Redbubble',
        'pw' => 'SellMonitor',
        'le' => 'Leboncoin',
        'kb' => 'Kufar',
        'kz' => 'Kufar',
        'la' => 'Lalafo',
        'it' => 'Rozetka',
        'ro' => 'Rozetka',
        'km' => 'Rozetka',
        'yb' => 'Youla',
        'yo' => 'YouDo',
        'nam' => 'Namshi',
        'iz' => 'Big C',

        // Delivery, Food & Travel
        'wr' => 'Wolt',
        'wl' => 'Wolt',
        'nz' => 'Foodpanda',
        'dop' => 'DoorDash',
        'dh' => 'DoorDash',
        'grb' => 'Grubhub',
        'je' => 'Just Eat',
        'pd' => 'iFood',
        'ph' => 'SnappFood',
        'jk' => 'Swiggy',
        'jf' => 'JioMart',
        'md' => 'McDonalds',
        'ip' => 'Burger King',
        'lq' => 'Luckin Coffee',
        'pn' => 'Coffee Like',
        'hf' => 'HungryPanda',
        'jg' => 'Grab',
        'jn' => 'Ola Cabs',
        'ol' => 'Ola Cabs',
        'gt' => 'Gett',
        'hj' => 'DiDi',
        'hy' => 'InDrive',
        'iv' => 'InDrive',
        'ic' => 'Citymobil',
        'sd' => 'Citymobil',
        'ju' => 'Uklon',
        'rp' => 'Rapido',
        'ht' => 'Bykea',
        'lyf' => 'Lyft',
        'bz' => 'BlaBlaCar',
        'air' => 'Airbnb',
        'ab' => 'Airbnb',
        'sa' => 'Agoda',
        'ho' => 'Cathay',
        'ev' => 'Hermes',
        'jr' => 'CDEK',
        'np' => 'Nova Poshta',

        // Telecom, Portals & Others
        'nv' => 'Naver',
        'mi' => 'Xiaomi',
        'hi' => 'Jio',
        'hh' => 'Optus',
        'tl' => 'Tele2',
        'lg' => 'Lycamobile',
        'lv' => 'Megafon',
        'vf' => 'Vodafone',
        'vg' => 'Virgin Mobile',
        'ke' => 'KKTCell',
        'hr' => 'Rostelecom',
        'rg' => 'Rostelecom',
        'hs' => 'Alfa Bank',
        'hu' => 'Ukrnet',
        'sw' => 'Swagbucks',
        'is' => 'Seosprint',
        'na' => 'Namars',
        'kp' => 'HQ Trivia',
        'kc' => 'Vkusvill',
        'mf' => 'Miratorg',
        'mg' => 'Magnit',
        'mz' => 'Magnit',
        'lj' => 'Lenta',
        'lr' => 'Lenta',
        'zz' => 'Dent',
        'eve' => 'Evernote',
        'gh' => 'GyFTR',
        'gj' => 'Carousell',
        'fx' => 'PGbonus',
        'pg' => 'PGbonus',
        'fz' => 'Facily',
        'hd' => 'Handypark',
        'il' => 'IQOS',
        'ib' => 'Ibotta',
        'ij' => 'Rebtel',
        'jx' => 'Paxful',
        'kq' => 'Koo',
        'ku' => 'Kueski',
        'kx' => 'Kumu',
        'lh' => 'Luno',
        'nc' => 'Nic.ru',
        'op' => 'OpenSea',
        'px' => 'Nifty',
        'ot' => 'Any Other Service',
    ];

    /**
     * Get human-readable service name for a given service code.
     */
    public function getServiceName(string $code): string
    {
        $codeClean = strtolower(trim($code));

        // 1. Check live/cached dynamic dictionary from Grizzly API
        $dynamic = $this->getDynamicServices();
        if (isset($dynamic[$codeClean]) && !empty($dynamic[$codeClean])) {
            return $dynamic[$codeClean];
        }

        // 2. Check comprehensive static dictionary
        if (isset($this->serviceDictionary[$codeClean])) {
            return $this->serviceDictionary[$codeClean];
        }

        // 3. Fallback: if it's already a descriptive word, format cleanly
        if (strlen($codeClean) > 3) {
            return ucwords(str_replace(['_', '-'], ' ', $codeClean));
        }

        return 'Other (' . strtoupper($codeClean) . ')';
    }

    /**
     * Retrieve and cache dynamic service list from Grizzly SMS API if available.
     */
    public function getDynamicServices(): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        return \Illuminate\Support\Facades\Cache::remember('grizzly_services_dictionary', 86400, function () {
            try {
                $response = Http::timeout(15)->get($this->baseUrl, [
                    'api_key' => $this->apiKey,
                    'action' => 'getServicesList',
                    'format' => 'json',
                    'lang' => 'en',
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $dict = [];
                    if (is_array($json)) {
                        if (isset($json['services']) && is_array($json['services'])) {
                            foreach ($json['services'] as $svc) {
                                if (isset($svc['code']) && isset($svc['name'])) {
                                    $dict[strtolower(trim($svc['code']))] = trim($svc['name']);
                                }
                            }
                        } else {
                            foreach ($json as $k => $v) {
                                if (is_array($v) && isset($v['name'])) {
                                    $dict[strtolower(trim($k))] = trim($v['name']);
                                } elseif (is_string($v)) {
                                    $dict[strtolower(trim($k))] = trim($v);
                                }
                            }
                        }
                    }
                    if (!empty($dict)) {
                        return $dict;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Grizzly getServicesList API call skipped: ' . $e->getMessage());
            }

            return [];
        });
    }

    public function __construct()
    {
        $this->apiKey = \App\Models\Setting::get('grizzly_api_key', env('GRIZZLY_API_KEY'));
    }

    /**
     * Get account balance from Grizzly SMS.
     */
    public function getBalance(): array
    {
        if (empty($this->apiKey)) {
            return ['status' => false, 'balance' => 0.00, 'message' => 'Grizzly API key is not configured.'];
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'getBalance',
            ]);

            $body = trim($response->body());
            if (str_starts_with($body, 'ACCESS_BALANCE:')) {
                $balance = (float) substr($body, strlen('ACCESS_BALANCE:'));
                return ['status' => true, 'balance' => $balance];
            }

            return ['status' => false, 'balance' => 0.00, 'error' => $body];
        } catch (\Throwable $e) {
            Log::error('Grizzly SMS getBalance failed: ' . $e->getMessage());
            return ['status' => false, 'balance' => 0.00, 'error' => $e->getMessage()];
        }
    }

    /**
     * Retrieve supported countries list from Grizzly SMS.
     */
    public function getCountries(): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $response = Http::timeout(20)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'getCountries',
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Grizzly country catalogue returned HTTP ' . $response->status());
            }

            $json = $response->json();
            if (!is_array($json) || empty($json)) {
                throw new \RuntimeException('Grizzly returned an empty or invalid country catalogue.');
            }

            $list = [];
            foreach ($json as $id => $countryData) {
                $cId = is_array($countryData) && isset($countryData['id']) ? $countryData['id'] : $id;
                $name = is_array($countryData)
                    ? ($countryData['eng'] ?? $countryData['name'] ?? "Country #{$cId}")
                    : (string) $countryData;

                $list[] = [
                    'ID' => (string) $cId,
                    'name' => (string) $name,
                    'cc' => (string) (is_array($countryData) ? ($countryData['prefix'] ?? $countryData['code'] ?? '') : ''),
                ];
            }

            return $list;
        } catch (\Throwable $e) {
            Log::warning('Grizzly country catalogue refresh failed.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get pricing and services for a given country code on Grizzly SMS.
     */
    public function getPricesForCountry(string|int $country): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $response = Http::timeout(25)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'getPrices',
                'country' => $country,
            ]);

            if ($response->failed()) {
                Log::error('Grizzly getPrices failed: ' . $response->body());
                return [];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [];
            }

            $countryData = $data[(string) $country] ?? $data[(int) $country] ?? $data;
            if (!is_array($countryData)) {
                return [];
            }

            $items = [];
            foreach ($countryData as $code => $val) {
                if (!is_array($val)) {
                    continue;
                }

                $cost = isset($val['cost']) ? (float) $val['cost'] : (isset($val['price']) ? (float) $val['price'] : null);
                $count = isset($val['count']) ? (int) $val['count'] : 1;
                if ($cost === null || $cost <= 0 || $count <= 0) {
                    continue;
                }

                $items[] = [
                    'service' => (string) $code,
                    'service_name' => $this->getServiceName((string) $code),
                    'price' => $cost,
                    'count' => $count,
                ];
            }

            return $items;
        } catch (\Throwable $e) {
            Log::error('Grizzly getPricesForCountry exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Purchase a virtual number from Grizzly SMS.
     */
    public function buyNumber(string|int $country, string $service, ?float $maxPrice = null): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Grizzly SMS API key is not configured. Please contact support.');
        }

        $params = [
            'api_key' => $this->apiKey,
            'action' => 'getNumberV2',
            'country' => $country,
            'service' => $service,
        ];

        if ($maxPrice !== null) {
            $params['maxPrice'] = number_format($maxPrice, 2, '.', '');
        }

        try {
            $response = Http::timeout(25)->get($this->baseUrl, $params);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \App\Exceptions\ProviderTimeoutException(
                'Grizzly purchase outcome could not be confirmed after a connection interruption.',
                0,
                $e
            );
        }

        $body = trim($response->body());

        // These responses explicitly confirm that no activation was created.
        if (in_array($body, ['NO_NUMBERS', 'NO_BALANCE', 'BAD_KEY'], true)
            || str_contains($body, 'SERVICE_UNAVAILABLE')
            || str_contains(strtolower($body), 'prohibited')
            || str_contains($body, 'MAX_PRICE_EXCEEDED')
            || str_contains($body, 'PRICE_LIMIT')) {
            throw new \RuntimeException(match (true) {
                $body === 'NO_NUMBERS' => 'No numbers currently available for this service and country on Server 2. Please try again or select another service.',
                $body === 'NO_BALANCE' => 'Server 2 provider balance is temporarily depleted. Please use another server or contact support.',
                $body === 'BAD_KEY' => 'Invalid Server 2 API configuration.',
                str_contains($body, 'MAX_PRICE_EXCEEDED') || str_contains($body, 'PRICE_LIMIT') => 'The current Grizzly price exceeds the confirmed safe spending limit. Please refresh the quote.',
                default => 'This service is currently restricted or unavailable on Server 2.',
            });
        }

        // A valid activation payload is authoritative even if the upstream HTTP
        // status is unusual; persist it rather than risking a duplicate purchase.
        $data = $response->json();
        if (is_array($data) && isset($data['activationId'], $data['phoneNumber'])) {
            $cancelAvailableAt = null;
            if (!empty($data['activationCancel'])) {
                try {
                    // Grizzly documents activationCancel as a UTC timestamp.
                    $cancelAvailableAt = \Illuminate\Support\Carbon::parse($data['activationCancel'], 'UTC')
                        ->utc()
                        ->toDateTimeString();
                } catch (\Throwable $dateError) {
                    Log::warning('Grizzly returned an invalid activationCancel timestamp.', [
                        'activation_id' => $data['activationId'],
                        'value' => $data['activationCancel'],
                    ]);
                }
            }

            return [
                'success' => 1,
                'order_id' => (string) $data['activationId'],
                'phonenumber' => (string) $data['phoneNumber'],
                'cost' => (isset($data['activationCost']) && (float) $data['activationCost'] > 0)
                    ? (float) $data['activationCost']
                    : null,
                'country' => (string) ($data['countryCode'] ?? $country),
                'service' => $this->getServiceName($service),
                'time_left' => 1200,
                'cancel_available_at' => $cancelAvailableAt,
            ];
        }

        if (str_starts_with($body, 'ACCESS_NUMBER:')) {
            $parts = explode(':', $body);
            if (count($parts) >= 3 && $parts[1] !== '' && $parts[2] !== '') {
                return [
                    'success' => 1,
                    'order_id' => (string) $parts[1],
                    'phonenumber' => (string) $parts[2],
                    'cost' => null,
                    'country' => (string) $country,
                    'service' => $this->getServiceName($service),
                    'time_left' => 1200,
                ];
            }
        }

        // Any unrecognized response (including upstream HTTP errors) is ambiguous:
        // the provider may have created an activation before the response failed.
        Log::error('Grizzly buyNumber returned an unrecognized response.', [
            'http_status' => $response->status(),
            'response' => substr($body, 0, 500),
        ]);

        throw new \App\Exceptions\ProviderTimeoutException(
            'Grizzly returned an unrecognized purchase response. The activation outcome is unknown and requires reconciliation.'
        );
    }

    /**
     * Retrieve all active activations for safe recovery after a lost purchase response.
     * An empty list is valid only when the provider explicitly reports NO_ACTIVATIONS.
     */
    public function getActiveActivations(): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Grizzly SMS API key is not configured.');
        }

        $response = Http::timeout(20)->get($this->baseUrl, [
            'api_key' => $this->apiKey,
            'action' => 'getActiveActivations',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Grizzly active activations request failed with HTTP ' . $response->status());
        }

        $body = trim($response->body());
        if ($body === 'NO_ACTIVATIONS' || $body === 'NO_ACTIVE_ACTIVATIONS') {
            return [];
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new \RuntimeException('Grizzly returned an invalid active activations response.');
        }

        $activations = $data['activeActivations'] ?? $data['activations'] ?? $data;
        if (!is_array($activations)) {
            throw new \RuntimeException('Grizzly active activations payload is malformed.');
        }

        return array_values(array_filter($activations, 'is_array'));
    }

    /**
     * Check status of an active number order / poll for SMS.
     * Statuses matching SMSPool format:
     * 1 = pending
     * 3 = completed / received
     * 5/6 = canceled / refunded
     */
    public function checkOrder(string|int $orderId): array
    {
        if (empty($this->apiKey)) {
            return ['status' => -1, 'error' => 'API key is not configured'];
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'getStatus',
                'id' => $orderId,
            ]);

            $body = trim($response->body());

            // STATUS_WAIT_CODE or STATUS_WAIT_RESEND
            if ($body === 'STATUS_WAIT_CODE' || $body === 'STATUS_WAIT_RESEND' || $body === 'STATUS_WAIT_RETRY') {
                return ['status' => 1];
            }

            // STATUS_OK:<code>
            if (str_starts_with($body, 'STATUS_OK:')) {
                $code = trim(substr($body, strlen('STATUS_OK:')));
                return [
                    'status' => 3,
                    'sms' => $code,
                    'full_sms' => "Your verification code is: {$code}"
                ];
            }

            // STATUS_CANCEL or NO_ACTIVATION (purged/expired by provider)
            if ($body === 'STATUS_CANCEL' || $body === 'NO_ACTIVATION') {
                return ['status' => 5];
            }

            return ['status' => -1, 'error' => 'Unrecognized provider status'];
        } catch (\Throwable $e) {
            Log::error("Grizzly checkOrder error: " . $e->getMessage());
            return ['status' => -1, 'error' => 'Provider status check failed'];
        }
    }

    /**
     * Cancel an active number on Grizzly SMS (releases number and refunds).
     * Action: setStatus, status=8
     */
    public function cancelOrder(string|int $orderId): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Grizzly SMS API key is not configured. Cancellation cannot be confirmed.');
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'setStatus',
                'id' => $orderId,
                'status' => 8, // 8 = cancel
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Grizzly cancellation HTTP failure: {$response->status()}");
            }

            $body = trim($response->body());
            Log::info("Grizzly cancelOrder result: {$body}");

            if (str_starts_with($body, 'EARLY_CANCEL_DENIED')) {
                // Use an exact delay only when the provider includes one. The
                // documented bare response does not specify a universal wait.
                if (preg_match('/(\\d+)\\s*seconds?/i', $body, $waitMatch)) {
                    throw new \RuntimeException('Please wait ' . $waitMatch[1] . ' seconds before cancelling this number.');
                }

                throw new \RuntimeException('Grizzly has not yet enabled cancellation for this number. Please wait a little longer and try again.');
            }

            // Only the explicit cancellation acknowledgement is proof of cancellation.
            if ($body === 'ACCESS_CANCEL') {
                return ['success' => true, 'response' => $body];
            }

            throw new \Exception("Server 2 cancellation failed: {$body}");
        } catch (\Throwable $e) {
            Log::error("Grizzly cancelOrder error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark an order as finished on Grizzly SMS.
     * Action: setStatus, status=6
     */
    public function finishOrder(string|int $orderId): array
    {
        if (empty($this->apiKey)) {
            return ['success' => true];
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'setStatus',
                'id' => $orderId,
                'status' => 6, // 6 = finished
            ]);

            return ['success' => true, 'response' => trim($response->body())];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Request another SMS code on the same number.
     * Action: setStatus, status=3
     */
    public function resendOrder(string|int $orderId): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Grizzly SMS API key is not configured. Resend cannot be confirmed.');
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'setStatus',
                'id' => $orderId,
                'status' => 3, // 3 = request another SMS
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Grizzly resend HTTP failure: {$response->status()}");
            }

            $body = trim($response->body());
            if ($body === 'ACCESS_RETRY_GET') {
                return ['success' => 1, 'message' => 'Waiting for next SMS code.'];
            }

            throw new \Exception("Server 2 resend request rejected: {$body}");
        } catch (\Throwable $e) {
            Log::error("Grizzly resendOrder error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fallback standard country dictionary for Grizzly SMS.
     */
    protected function getDefaultCountryList(): array
    {
        return [
            ['ID' => '0', 'name' => 'Russia', 'cc' => '7'],
            ['ID' => '1', 'name' => 'Ukraine', 'cc' => '380'],
            ['ID' => '2', 'name' => 'Kazakhstan', 'cc' => '7'],
            ['ID' => '3', 'name' => 'China', 'cc' => '86'],
            ['ID' => '4', 'name' => 'Philippines', 'cc' => '63'],
            ['ID' => '5', 'name' => 'Myanmar', 'cc' => '95'],
            ['ID' => '6', 'name' => 'Indonesia', 'cc' => '62'],
            ['ID' => '7', 'name' => 'Malaysia', 'cc' => '60'],
            ['ID' => '8', 'name' => 'Kenya', 'cc' => '254'],
            ['ID' => '10', 'name' => 'Vietnam', 'cc' => '84'],
            ['ID' => '11', 'name' => 'Kyrgyzstan', 'cc' => '996'],
            ['ID' => '12', 'name' => 'USA', 'cc' => '1'],
            ['ID' => '13', 'name' => 'Israel', 'cc' => '972'],
            ['ID' => '14', 'name' => 'Hong Kong', 'cc' => '852'],
            ['ID' => '15', 'name' => 'Poland', 'cc' => '48'],
            ['ID' => '16', 'name' => 'United Kingdom', 'cc' => '44'],
            ['ID' => '18', 'name' => 'Democratic Republic of Congo', 'cc' => '243'],
            ['ID' => '22', 'name' => 'India', 'cc' => '91'],
            ['ID' => '23', 'name' => 'Cambodia', 'cc' => '855'],
            ['ID' => '24', 'name' => 'Laos', 'cc' => '856'],
            ['ID' => '29', 'name' => 'Romania', 'cc' => '40'],
            ['ID' => '31', 'name' => 'South Africa', 'cc' => '27'],
            ['ID' => '32', 'name' => 'Netherlands', 'cc' => '31'],
            ['ID' => '36', 'name' => 'Canada', 'cc' => '1'],
            ['ID' => '37', 'name' => 'Morocco', 'cc' => '212'],
            ['ID' => '38', 'name' => 'Ghana', 'cc' => '233'],
            ['ID' => '39', 'name' => 'Argentina', 'cc' => '54'],
            ['ID' => '43', 'name' => 'Germany', 'cc' => '49'],
            ['ID' => '44', 'name' => 'Lithuania', 'cc' => '370'],
            ['ID' => '47', 'name' => 'Sweden', 'cc' => '46'],
            ['ID' => '48', 'name' => 'Iraq', 'cc' => '964'],
            ['ID' => '52', 'name' => 'Thailand', 'cc' => '66'],
            ['ID' => '53', 'name' => 'Saudi Arabia', 'cc' => '966'],
            ['ID' => '56', 'name' => 'Spain', 'cc' => '34'],
            ['ID' => '62', 'name' => 'Turkey', 'cc' => '90'],
            ['ID' => '68', 'name' => 'Egypt', 'cc' => '20'],
            ['ID' => '73', 'name' => 'Nigeria', 'cc' => '234'],
            ['ID' => '78', 'name' => 'France', 'cc' => '33'],
            ['ID' => '86', 'name' => 'Italy', 'cc' => '39'],
            ['ID' => '117', 'name' => 'Portugal', 'cc' => '351'],
            ['ID' => '175', 'name' => 'Mexico', 'cc' => '52'],
            ['ID' => '187', 'name' => 'United States (Virtual)', 'cc' => '1'],
        ];
    }
}
