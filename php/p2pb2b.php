<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception as Exception; // a common import

class p2pb2b extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'p2pb2b',
            'name' => 'p2pb2b',
            'countries' => ['EE'],
            'version' => 'v1',
            'rateLimit' => 1000,
            'has' => array (
                'createMarketOrder' => false,
                'fetchOrder' => true,
                'fetchOrders' => false,
                'fetchOpenOrders' => true,
                'fetchCurrencies' => false,
                'fetchTicker' => true,
                'fetchTickers' => false,
                'fetchOHLCV' => false,
                'fetchTrades' => false,
            ),
            'urls' => array (
                'api' => array (
                    'public' => 'https://api.p2pb2b.io/api/v1/public',
                    'private' => 'https://api.p2pb2b.io/api/v1',
                    'wapi' => 'wss://apiws.p2pb2b.io/',
                ),
                'www' => 'https://p2pb2b.io/',
                'doc' => array (
                    'https://documenter.getpostman.com/view/6288660/SVYxnEmD?version=latest',
                    'https://p2pb2bwsspublic.docs.apiary.io/',
                ),
                'fees' => 'https://p2pb2b.io/fee-schedule',
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'markets',
                        'tickers',
                        'ticker',
                        'book',
                        'history',
                        'history/result',
                        'products',
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
                'wapi' => array (
                    'server' => array (
                        'ping',
                        'time',
                    ),
                    'kline' => array (
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ),
                    'price' => array (
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ),
                    'state' => array (
                        'query',
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ),
                    'deals' => array (
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ),
                    'depth' => array (
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ),
                ),
            ),
            'fees' => array (
                'trading' => array (
                    'tierBased' => false,
                    'maker' => 0.002,
                    'taker' => 0.002,
                ),
            ),
            'exceptions' => array (
                'Balance not enough' => '\\ccxt\\InsufficientFunds',
                'Total is less than' => '\\ccxt\\InvalidOrder',
                'Order not found' => '\\ccxt\\OrderNotFound',
                'Unauthorized request.' => '\\ccxt\\AuthenticationError',
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
                        'min' => pow(10, -$precision['amount']),
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
        $result = $response->result;
        return $this->parse_orders($result, $market, $since, $limit);
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
        $orderBook = $this->safe_value($response, 'result');
        return $this->parse_order_book($orderBook, null, 'bids', 'asks');
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
            $balance = $balances[$code];
            $account = $this->account ();
            $account['free'] = $this->safe_float($balance, 'available');
            $account['total'] = $this->safe_float($balance, 'available') . $this->safe_float($balance, 'freeze');
            $result[$code] = $account;
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
        $timestamp = $this->safe_string($order, 'time');
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

    public function get_order_id_field () {
        return 'orderId';
    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body, $response, $requestHeaders, $requestBody) {
        if ($response === null) {
            return;
        }
        if (strlen ($body) > 0) {
            if ($body[0] === '{') {
                $success = $this->safe_value($response, 'success', true);
                $errorMessage = $this->safe_value($response, 'message', [array()]);
                if (!$success && $errorMessage) {
                    $messageKey = is_array($errorMessage) ? array_keys($errorMessage) : array()[0];
                    $message = $errorMessage[$messageKey][0];
                    $exceptionMessages = is_array($this->exceptions) ? array_keys($this->exceptions) : array();
                    for ($i = 0; $i < count ($exceptionMessages); $i++) {
                        $exceptionMessage = $exceptionMessages[$i];
                        if (mb_strpos($message, $exceptionMessage) !== false) {
                            $ExceptionClass = $this->exceptions[$exceptionMessage];
                            throw new $ExceptionClass($this->id . ' ' . $message);
                        }
                    }
                }
            }
        }
        if ($code >= 400 || $this->safe_value($response, 'status', 200) >= 400) {
            if (mb_strpos($body, 'Server Error') !== false) {
                throw new ExchangeError($this->id . ' Server Error');
            }
        }
    }
}
