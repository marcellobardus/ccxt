<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import

class coinsbit extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'coinsbit',
            'name' => 'Coinsbit',
            'countries' => ['EE'],
            'version' => 'v1',
            'rateLimit' => 1000,
            'has' => array (
                'createMarketOrder' => false,
                'fetchOrder' => true,
                'fetchOrders' => false,
                'fetchOpenOrders' => true,
                'fetchCurrencies' => false,
                'fetchL2OrderBook' => false,
                'fetchTicker' => true,
                'fetchTickers' => false,
                'fetchOHLCV' => false,
                'fetchTrades' => false,
            ),
            'urls' => array (
                'api' => array (
                    'public' => 'https://coinsbit.io/api/v1/public',
                    'private' => 'https://coinsbit.io/api/v1',
                ),
                'www' => 'https://coinsbit.io/',
                'doc' => array (
                    'https://www.notion.so/API-COINSBIT-WS-API-COINSBIT-cf1044cff30646d49a0bab0e28f27a87',
                ),
                'fees' => 'https://coinsbit.io/fee-schedule',
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'markets',
                        'tickers',
                        'ticker',
                        'book',
                        'history',
                        'symbols',
                        'depth/result',
                    ),
                ),
                'private' => array (
                    'post' => array (
                        'order/new',
                        'order/cancel',
                        'orders',
                        'account/balances',
                        'account/balance',
                        'account/order',
                        'account/order_history',
                    ),
                ),
            ),
            'fees' => array (
                'trading' => array (
                    'maker' => 0.002,
                    'taker' => 0.002,
                ),
            ),
            'exceptions' => array (
                'balance not enough' => '\\ccxt\\InsufficientFunds',
                'amount is less than' => '\\ccxt\\InvalidOrder',
                'Total is less than' => '\\ccxt\\InvalidOrder',
                'validation.total' => '\\ccxt\\InvalidOrder',
                'Too many requests' => '\\ccxt\\DDoSProtection',
                'This action is unauthorized.' => '\\ccxt\\AuthenticationError',
            ),
        ));
    }

    public function fetch_markets ($params = array ()) {
        $response = $this->publicGetMarkets ($params);
        $markets = $this->safe_value($response, 'result');
        $numMarkets = is_array ($markets) ? count ($markets) : 0;
        if ($numMarkets < 1) {
            throw new ExchangeError($this->id . ' publicGetMarkets returned empty $response => ' . $this->json ($markets));
        }
        $result = array();
        for ($i = 0; $i < count ($markets); $i++) {
            $market = $markets[$i];
            $baseId = $this->safe_string($market, 'stock');
            $quoteId = $this->safe_string($market, 'money');
            $id = $baseId . '_' . $quoteId;
            $base = $this->safe_currency_code($baseId);
            $quote = $this->safe_currency_code($quoteId);
            $symbol = $base . '/' . $quote;
            $precision = array (
                'amount' => $this->safe_integer($market, 'stockPrec'),
                'price' => $this->safe_integer($market, 'moneyPrec'),
            );
            $minAmount = $this->safe_float($market, 'minAmount', 0);
            $result[] = array (
                'id' => $id,
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'baseId' => $baseId,
                'quoteId' => $quoteId,
                'active' => true,
                'precision' => $precision,
                'limits' => array (
                    'amount' => array (
                        'min' => $minAmount,
                        'max' => null,
                    ),
                    'price' => array (
                        'min' => pow(10, -$precision['price']),
                        'max' => null,
                    ),
                    'cost' => array (
                        'min' => null,
                        'max' => null,
                    ),
                ),
                'info' => $market,
            );
        }
        return $result;
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $timestamp = $this->milliseconds ();
        $market = $this->market ($symbol);
        $request = array_merge (array (
            'market' => $market['id'],
        ), $params);
        $response = $this->publicGetTicker ($request);
        $ticker = $this->safe_value($response, 'result');
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => $this->safe_float($ticker, 'high'),
            'low' => $this->safe_float($ticker, 'low'),
            'bid' => $this->safe_float($ticker, 'bid'),
            'bidVolume' => null,
            'ask' => $this->safe_float($ticker, 'ask'),
            'askVolume' => null,
            'vwap' => null,
            'previousClose' => null,
            'open' => $this->safe_float($ticker, 'open'),
            'close' => $this->safe_float($ticker, 'last'),
            'last' => $this->safe_float($ticker, 'last'),
            'percentage' => null,
            'change' => $this->safe_float($ticker, 'change'),
            'average' => null,
            'baseVolume' => $this->safe_float($ticker, 'volume'),
            'quoteVolume' => $this->safe_float($ticker, 'deal'),
            'info' => $ticker,
        );
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $method = 'privatePostOrderNew';
        $request = array (
            'side' => $side,
            'market' => $market['id'],
            'amount' => $this->amount_to_precision($symbol, $amount),
            'price' => $this->price_to_precision($symbol, $price),
        );
        $response = $this->$method (array_merge ($request, $params));
        $order = $this->parse_new_order ($this->safe_value($response, 'result'), $market);
        return $order;
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            'market' => $this->market_id($symbol),
            'orderId' => intval ($id),
        );
        return $this->privatePostOrderCancel (array_merge ($request, $params));
    }

    public function fetch_open_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        if ($symbol === null) {
            throw new ArgumentsRequired($this->id . ' fetchOrders requires a $symbol argument');
        }
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'market' => $market['id'],
        );
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $response = $this->privatePostOrders (array_merge ($request, $params));
        $result = $this->safe_value($response, 'result');
        return $this->parse_orders($this->safe_value($result, 'result'), $market, $since, $limit);
    }

    public function fetch_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $orderIdField = $this->get_order_id_field ();
        $request = array();
        $request[$orderIdField] = $id;
        $response = $this->privatePostAccountOrder (array_merge ($request, $params));
        if (strlen ($response['result']) === 0) {
            throw new OrderNotFound($this->id . ' order ' . $id . ' not found');
        }
        return $this->parse_order($response['result']['records']);
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            'market' => $this->market_id($symbol),
        );
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $response = $this->publicGetDepthResult (array_merge ($request, $params));
        return $this->parse_order_book($response, null, 'bids', 'asks');
    }

    public function fetch_l2_order_book ($symbol, $limit = null, $params = array ()) {
        if ($params['side'] === null) {
            throw new ArgumentsRequired($this->id . ' fetchL2OrderBook requires a side argument');
        }
        $this->load_markets();
        $request = array (
            'market' => $this->market_id($symbol),
            'side' => $params['side'],
        );
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $response = $this->publicGetBook (array_merge ($request, $params));
        $timestamp = $this->safe_value($response, 'cache_time');
        $result = $this->safe_value($response, 'result');
        $orderBook = $this->safe_value($result, 'result', $result);
        return $this->parse_l2_order_book ($orderBook, $timestamp);
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $query = $this->omit ($params, 'type');
        $response = $this->privatePostAccountBalances ($query);
        $balances = $this->safe_value($response, 'result');
        $symbols = is_array($balances) ? array_keys($balances) : array();
        $result = array( 'info' => $balances );
        for ($i = 0; $i < count ($symbols); $i++) {
            $currencyId = $symbols[$i];
            $code = $this->safe_currency_code($currencyId);
            $balance = $this->safe_value($balances, $code);
            if ($balance) {
                $account = $this->account ();
                $account['free'] = $this->safe_float($balance, 'available');
                $account['total'] = $this->safe_float($balance, 'available') . $this->safe_float($balance, 'freeze');
                $result[$code] = $account;
            }
        }
        return $this->parse_balance($result);
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $url = $this->urls['api'][$api] . '/' . $this->implode_params($path, $params);
        $query = $this->omit ($params, $this->extract_params($path));
        if ($api === 'public') {
            if ($query) {
                $url .= '?' . $this->urlencode ($query);
            }
        } else {
            $this->check_required_credentials();
            $request = '/api/' . $this->version . '/' . $this->implode_params($path, $params);
            $nonce = (string) $this->nonce ();
            $query = array_merge (array (
                'nonce' => (string) $nonce,
                'request' => $request,
            ), $query);
            $body = $this->json ($query);
            $query = $this->encode ($body);
            $payload = base64_encode ($query);
            $secret = $this->encode ($this->secret);
            $signature = $this->hmac ($payload, $secret, 'sha512');
            $headers = array (
                'Content-type' => 'application/json',
                'X-TXC-APIKEY' => $this->apiKey,
                'X-TXC-PAYLOAD' => $payload,
                'X-TXC-SIGNATURE' => $signature,
            );
        }
        return array( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function get_order_id_field () {
        return 'orderId';
    }

    public function parse_l2_order_book ($orderbook, $timestamp = null, $bidsKey = 'bids', $asksKey = 'asks', $priceKey = 0) {
        $orders = $this->safe_value($orderbook, 'orders');
        $asks = array();
        $bids = array();
        if (strlen ($orders) > 0) {
            $side = $this->safe_value($orders[0], 'side');
            $bookMap = array();
            $book = array();
            for ($i = 0; $i < count ($orders); $i++) {
                $price = $this->safe_float($orders[$i], 'price');
                $amount = $this->safe_float($orders[$i], 'amount');
                $existingOrderAmount = $this->safe_value($bookMap, $price, 0.0);
                $bookMap[$price] = $amount . $existingOrderAmount;
            }
            $bookPrices = is_array($bookMap) ? array_keys($bookMap) : array();
            for ($i = 0; $i < count ($bookPrices); $i++) {
                $key = $bookPrices[$i];
                $book[] = [floatval ($key), $bookMap[$key]];
            }
            if ($side === 'buy') {
                $bids = $this->sort_by($book, $priceKey, true);
            } else {
                $asks = $this->sort_by($book, $priceKey);
            }
        }
        return array (
            'bids' => $bids,
            'asks' => $asks,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'nonce' => null,
        );
    }

    public function parse_new_order ($order, $market = null) {
        $marketName = $this->safe_string($order, 'market');
        $market = $market || $this->find_market($marketName);
        $symbol = $this->safe_string($market, 'symbol');
        $timestamp = $this->safe_string($order, 'timestamp');
        if ($timestamp !== null) {
            $timestamp = (int) round(floatval ($timestamp) * 1000);
        }
        $amount = $this->safe_float($order, 'amount');
        $remaining = $this->safe_float($order, 'left');
        $fillAmount = $amount - $remaining;
        return array (
            'id' => $this->safe_string($order, 'orderId'),
            'datetime' => $this->iso8601 ($timestamp),
            'timestamp' => $timestamp,
            'lastTradeTimestamp' => null,
            'status' => null,
            'symbol' => $symbol,
            'type' => $this->safe_string($order, 'type'),
            'side' => $this->safe_string($order, 'side'),
            'price' => $this->safe_float($order, 'price'),
            'cost' => $this->safe_float($order, 'dealFee', 0.0),
            'amount' => $amount,
            'filled' => $fillAmount,
            'remaining' => $remaining,
            'fee' => $this->safe_float($order, 'dealFee'),
            'info' => $order,
        );
    }

    public function parse_order ($order, $market = null) {
        $marketName = $this->safe_string($order, 'market');
        $market = $market || $this->find_market($marketName);
        $symbol = $this->safe_string($market, 'symbol');
        $timestamp = $this->safe_string($order, 'time') || $this->safe_string($order, 'timestamp');
        if ($timestamp !== null) {
            $timestamp = (int) round(floatval ($timestamp) * 1000);
        }
        $amount = $this->safe_float($order, 'amount');
        $fillAmount = $this->safe_float($order, 'dealStock', $amount);
        $remaining = $amount - $fillAmount;
        return array (
            'id' => $this->safe_string($order, 'id'),
            'datetime' => $this->iso8601 ($timestamp),
            'timestamp' => $timestamp,
            'lastTradeTimestamp' => null,
            'status' => null,
            'symbol' => $symbol,
            'type' => $this->safe_string($order, 'type'),
            'side' => $this->safe_string($order, 'side'),
            'price' => $this->safe_float($order, 'price'),
            'cost' => $this->safe_float($order, 'dealFee', 0.0),
            'amount' => $amount,
            'filled' => $fillAmount,
            'remaining' => $remaining,
            'fee' => $this->safe_float($order, 'dealFee'),
            'info' => $order,
        );
    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body, $response, $requestHeaders, $requestBody) {
        if ($response === null) {
            return;
        }
        if ($code >= 400 || $this->safe_value($response, 'status', 200) >= 400) {
            if (mb_strpos($body, 'Server Error') !== false) {
                throw new ExchangeError($this->id . ' Server Error');
            }
        }
        if (strlen ($body) > 0) {
            if ($body[0] === '{') {
                $success = $this->safe_value($response, 'success', true);
                $errorMessage = $this->safe_value($response, 'message', [array()]);
                if (!$success && $errorMessage) {
                    $message = '';
                    if (gettype ($errorMessage) === 'array' && count (array_filter (array_keys ($errorMessage), 'is_string')) == 0) {
                        $message = (string) $errorMessage;
                    } else {
                        $messageKey = is_array($errorMessage) ? array_keys($errorMessage) : array()[0];
                        $message = $errorMessage[$messageKey][0];
                    }
                    $exceptionMessages = is_array($this->exceptions) ? array_keys($this->exceptions) : array();
                    for ($i = 0; $i < count ($exceptionMessages); $i++) {
                        $exceptionMessage = $exceptionMessages[$i];
                        if (mb_strpos($message, $exceptionMessage) !== false) {
                            $ExceptionClass = $this->exceptions[$exceptionMessage];
                            throw new $ExceptionClass($this->id . ' ' . $message);
                        }
                    }
                    throw new ExchangeError($this->id . ' Error ' . $message);
                }
            }
        }
    }
}
