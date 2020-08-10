'use strict';

//  ---------------------------------------------------------------------------

const Exchange = require ('./base/Exchange');
const { ArgumentsRequired, AuthenticationError, DDoSProtection, ExchangeError, OrderNotFound, InsufficientFunds, InvalidOrder } = require ('./base/errors');

//  ---------------------------------------------------------------------------

module.exports = class p2pb2b extends Exchange {
    describe () {
        return this.deepExtend (super.describe (), {
            'id': 'p2pb2b',
            'name': 'p2pb2b',
            'countries': ['EE'],
            'version': 'v1',
            'rateLimit': 1000,
            'has': {
                'createMarketOrder': false,
                'fetchOrder': true,
                'fetchOrders': false,
                'fetchOpenOrders': true,
                'fetchCurrencies': false,
                'fetchL2OrderBook': false,
                'fetchTicker': true,
                'fetchTickers': false,
                'fetchOHLCV': false,
                'fetchTrades': false,
            },
            'urls': {
                'api': {
                    'public': 'https://api.p2pb2b.io/api/v1/public',
                    'private': 'https://api.p2pb2b.io/api/v1',
                    'wapi': 'wss://apiws.p2pb2b.io/',
                },
                'www': 'https://p2pb2b.io/',
                'doc': [
                    'https://documenter.getpostman.com/view/6288660/SVYxnEmD?version=latest',
                    'https://p2pb2bwsspublic.docs.apiary.io/',
                ],
                'fees': 'https://p2pb2b.io/fee-schedule',
            },
            'api': {
                'public': {
                    'get': [
                        'markets',
                        'tickers',
                        'ticker',
                        'book',
                        'history',
                        'history/result',
                        'products',
                        'symbols',
                        'depth/result',
                    ],
                },
                'private': {
                    'post': [
                        'order/new',
                        'order/cancel',
                        'orders',
                        'account/balances',
                        'account/balance',
                        'account/order',
                        'account/order_history',
                    ],
                },
                'wapi': {
                    'server': [
                        'ping',
                        'time',
                    ],
                    'kline': [
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ],
                    'price': [
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ],
                    'state': [
                        'query',
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ],
                    'deals': [
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ],
                    'depth': [
                        'subscribe',
                        'unsubscribe',
                        'update',
                    ],
                },
            },
            'fees': {
                'trading': {
                    'tierBased': false,
                    'maker': 0.002,
                    'taker': 0.002,
                },
            },
            'exceptions': {
                'Balance not enough': InsufficientFunds,
                'amount is less than': InvalidOrder,
                'Total is less than': InvalidOrder,
                'validation.total': InvalidOrder,
                'Order not found': OrderNotFound,
                'Unauthorized request.': AuthenticationError,
                'Too many requests': DDoSProtection,
            },
        });
    }

    async fetchMarkets (params = {}) {
        const response = await this.publicGetMarkets (params);
        const markets = this.safeValue (response, 'result');
        const numMarkets = markets.length;
        if (numMarkets < 1) {
            throw new ExchangeError (this.id + ' publicGetMarkets returned empty response: ' + this.json (markets));
        }
        const result = [];
        for (let i = 0; i < markets.length; i++) {
            const market = markets[i];
            const baseId = this.safeString (market, 'stock');
            const quoteId = this.safeString (market, 'money');
            const id = baseId + '_' + quoteId;
            const base = this.safeCurrencyCode (baseId);
            const quote = this.safeCurrencyCode (quoteId);
            const symbol = base + '/' + quote;
            const precision = {
                'amount': this.safeInteger (market, 'stockPrec'),
                'price': this.safeInteger (market, 'moneyPrec'),
            };
            const minAmount = this.safeFloat (market, 'minAmount', 0);
            result.push ({
                'id': id,
                'symbol': symbol,
                'base': base,
                'quote': quote,
                'baseId': baseId,
                'quoteId': quoteId,
                'active': true,
                'precision': precision,
                'limits': {
                    'amount': {
                        'min': minAmount,
                        'max': undefined,
                    },
                    'price': {
                        'min': Math.pow (10, -precision['price']),
                        'max': undefined,
                    },
                    'cost': {
                        'min': undefined,
                        'max': undefined,
                    },
                },
                'info': market,
            });
        }
        return result;
    }

    async fetchTicker (symbol, params = {}) {
        await this.loadMarkets ();
        const timestamp = this.milliseconds ();
        const market = this.market (symbol);
        const request = this.extend ({
            'market': market['id'],
        }, params);
        const response = await this.publicGetTicker (request);
        const ticker = this.safeValue (response, 'result');
        return {
            'symbol': symbol,
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'high': this.safeFloat (ticker, 'high'),
            'low': this.safeFloat (ticker, 'low'),
            'bid': this.safeFloat (ticker, 'bid'),
            'bidVolume': undefined,
            'ask': this.safeFloat (ticker, 'ask'),
            'askVolume': undefined,
            'vwap': undefined,
            'previousClose': undefined,
            'open': this.safeFloat (ticker, 'open'),
            'close': this.safeFloat (ticker, 'last'),
            'last': this.safeFloat (ticker, 'last'),
            'percentage': undefined,
            'change': this.safeFloat (ticker, 'change'),
            'average': undefined,
            'baseVolume': this.safeFloat (ticker, 'volume'),
            'quoteVolume': this.safeFloat (ticker, 'deal'),
            'info': ticker,
        };
    }

    async createOrder (symbol, _type, side, amount, price = undefined, params = {}) {
        await this.loadMarkets ();
        const market = this.market (symbol);
        const method = 'privatePostOrderNew';
        const request = {
            'side': side,
            'market': market['id'],
            'amount': this.amountToPrecision (symbol, amount),
            'price': this.priceToPrecision (symbol, price),
        };
        const response = await this[method] (this.extend (request, params));
        const order = this.parseNewOrder (this.safeValue (response, 'result'), market);
        return order;
    }

    async cancelOrder (id, symbol = undefined, params = {}) {
        await this.loadMarkets ();
        const request = {
            'market': this.marketId (symbol),
            'orderId': parseInt (id),
        };
        return await this.privatePostOrderCancel (this.extend (request, params));
    }

    async fetchOpenOrders (symbol = undefined, since = undefined, limit = undefined, params = {}) {
        if (symbol === undefined) {
            throw new ArgumentsRequired (this.id + ' fetchOrders requires a symbol argument');
        }
        await this.loadMarkets ();
        const market = this.market (symbol);
        const request = {
            'market': market['id'],
        };
        if (limit !== undefined) {
            request['limit'] = limit;
        }
        const response = await this.privatePostOrders (this.extend (request, params));
        const result = this.safeValue (response, 'result');
        return this.parseOrders (result, market, since, limit);
    }

    async fetchOrder (id, symbol = undefined, params = {}) {
        await this.loadMarkets ();
        const orderIdField = this.getOrderIdField ();
        const request = {};
        request[orderIdField] = id;
        const response = await this.privatePostAccountOrder (this.extend (request, params));
        if (response['result'].length === 0) {
            throw new OrderNotFound (this.id + ' order ' + id + ' not found');
        }
        return this.parseOrder (response['result']['records']);
    }

    async fetchL2OrderBook (symbol, limit = undefined, params = {}) {
        if (params['side'] === undefined) {
            throw new ArgumentsRequired (this.id + ' fetchL2OrderBook requires a side argument');
        }
        await this.loadMarkets ();
        const request = {
            'market': this.marketId (symbol),
            'side': params['side'],
        };
        if (limit !== undefined) {
            request['limit'] = limit;
        }
        const response = await this.publicGetBook (this.extend (request, params));
        const timestamp = this.safeValue (response, 'cache_time');
        const orderBook = this.safeValue (response, 'result');
        return this.parseL2OrderBook (orderBook, timestamp);
    }

    async fetchOrderBook (symbol, limit = undefined, params = {}) {
        await this.loadMarkets ();
        const request = {
            'market': this.marketId (symbol),
        };
        if (limit !== undefined) {
            request['limit'] = limit;
        }
        const response = await this.publicGetDepthResult (this.extend (request, params));
        const orderBook = this.safeValue (response, 'result');
        return this.parseOrderBook (orderBook, undefined, 'bids', 'asks');
    }

    async fetchBalance (params = {}) {
        await this.loadMarkets ();
        const query = this.omit (params, 'type');
        const response = await this.privatePostAccountBalances (query);
        const balances = this.safeValue (response, 'result');
        const symbols = Object.keys (balances);
        const result = { 'info': balances };
        for (let i = 0; i < symbols.length; i++) {
            const currencyId = symbols[i];
            const balance = balances[currencyId];
            const account = this.account ();
            account['free'] = this.safeFloat (balance, 'available');
            account['total'] = this.safeFloat (balance, 'available') + this.safeFloat (balance, 'freeze');
            result[currencyId] = account;
        }
        return this.parseBalance (result);
    }

    nonce () {
        return 1000 * this.microseconds ();
    }

    sign (path, api = 'public', method = 'GET', params = {}, headers = undefined, body = undefined) {
        let url = this.urls['api'][api] + '/' + this.implodeParams (path, params);
        let query = this.omit (params, this.extractParams (path));
        if (api === 'public') {
            if (Object.keys (query).length) {
                url += '?' + this.urlencode (query);
            }
        } else {
            this.checkRequiredCredentials ();
            const request = '/api/' + this.version + '/' + this.implodeParams (path, params);
            const nonce = this.nonce ().toString ();
            query = this.extend ({
                'nonce': nonce.toString (),
                'request': request,
            }, query);
            body = this.json (query);
            query = this.encode (body);
            const payload = this.stringToBase64 (query);
            const secret = this.encode (this.secret);
            const signature = this.hmac (payload, secret, 'sha512');
            headers = {
                'Content-type': 'application/json',
                'X-TXC-APIKEY': this.apiKey,
                'X-TXC-PAYLOAD': payload,
                'X-TXC-SIGNATURE': signature,
            };
        }
        return { 'url': url, 'method': method, 'body': body, 'headers': headers };
    }

    parseL2OrderBook (orderbook, timestamp = undefined, bidsKey = 'bids', asksKey = 'asks', priceKey = 0) {
        const orders = this.safeValue (orderbook, 'orders');
        let asks = [];
        let bids = [];
        if (orders.length > 0) {
            const side = this.safeValue (orders[0], 'side');
            const bookMap = {};
            const book = [];
            for (let i = 0; i < orders.length; i++) {
                const price = this.safeFloat (orders[i], 'price');
                const amount = this.safeFloat (orders[i], 'amount');
                const existingOrderAmount = this.safeValue (bookMap, price, 0.0);
                bookMap[price] = amount + existingOrderAmount;
            }
            const bookPrices = Object.keys (bookMap);
            for (let i = 0; i < bookPrices.length; i++) {
                const key = bookPrices[i];
                book.push ([parseFloat (key), bookMap[key]]);
            }
            if (side === 'buy') {
                bids = this.sortBy (book, priceKey, true);
            } else {
                asks = this.sortBy (book, priceKey);
            }
        }
        return {
            'bids': bids,
            'asks': asks,
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'nonce': undefined,
        };
    }

    parseNewOrder (order, market = undefined) {
        const marketName = this.safeString (order, 'market');
        market = market || this.findMarket (marketName);
        const symbol = this.safeString (market, 'symbol');
        let timestamp = this.safeString (order, 'timestamp');
        if (timestamp !== undefined) {
            timestamp = Math.round (parseFloat (timestamp) * 1000);
        }
        const amount = this.safeFloat (order, 'amount');
        const remaining = this.safeFloat (order, 'left');
        const fillAmount = amount - remaining;
        return {
            'id': this.safeString (order, 'orderId'),
            'datetime': this.iso8601 (timestamp),
            'timestamp': timestamp,
            'lastTradeTimestamp': undefined,
            'status': undefined,
            'symbol': symbol,
            'type': this.safeString (order, 'type'),
            'side': this.safeString (order, 'side'),
            'price': this.safeFloat (order, 'price'),
            'cost': this.safeFloat (order, 'dealFee', 0.0),
            'amount': amount,
            'filled': fillAmount,
            'remaining': remaining,
            'fee': this.safeFloat (order, 'dealFee'),
            'info': order,
        };
    }

    parseOrder (order, market = undefined) {
        const marketName = this.safeString (order, 'market');
        market = market || this.findMarket (marketName);
        const symbol = this.safeString (market, 'symbol');
        let timestamp = this.safeString (order, 'time');
        if (timestamp !== undefined) {
            timestamp = Math.round (parseFloat (timestamp) * 1000);
        }
        const amount = this.safeFloat (order, 'amount');
        const fillAmount = this.safeFloat (order, 'dealStock', amount);
        const remaining = amount - fillAmount;
        return {
            'id': this.safeString (order, 'id'),
            'datetime': this.iso8601 (timestamp),
            'timestamp': timestamp,
            'lastTradeTimestamp': undefined,
            'status': undefined,
            'symbol': symbol,
            'type': this.safeString (order, 'type'),
            'side': this.safeString (order, 'side'),
            'price': this.safeFloat (order, 'price'),
            'cost': this.safeFloat (order, 'dealFee', 0.0),
            'amount': amount,
            'filled': fillAmount,
            'remaining': remaining,
            'fee': this.safeFloat (order, 'dealFee'),
            'info': order,
        };
    }

    getOrderIdField () {
        return 'orderId';
    }

    handleErrors (code, _reason, _url, _method, _headers, body, response, requestHeaders, requestBody) {
        if (response === undefined) {
            return;
        }
        if (body.length > 0) {
            if (body[0] === '{') {
                const success = this.safeValue (response, 'success', true);
                const errorMessage = this.safeValue (response, 'message', [[]]);
                if (!success && errorMessage) {
                    let message = '';
                    if (Array.isArray (errorMessage)) {
                        message = errorMessage.toString ();
                    } else if (typeof errorMessage === 'string') {
                        message = errorMessage;
                    } else {
                        const messageKey = Object.keys (errorMessage)[0];
                        message = errorMessage[messageKey][0];
                    }
                    const exceptionMessages = Object.keys (this.exceptions);
                    for (let i = 0; i < exceptionMessages.length; i++) {
                        const exceptionMessage = exceptionMessages[i];
                        if (message.indexOf (exceptionMessage) >= 0) {
                            const ExceptionClass = this.exceptions[exceptionMessage];
                            throw new ExceptionClass (this.id + ' ' + message);
                        }
                    }
                    throw new ExchangeError (this.id + ' Error ' + message);
                }
            }
        }
        if (code >= 400 || this.safeValue (response, 'status', 200) >= 400) {
            if (body.indexOf ('Server Error') >= 0) {
                throw new ExchangeError (this.id + ' Server Error');
            }
        }
    }
};
