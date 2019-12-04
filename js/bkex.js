'use strict';

//  ---------------------------------------------------------------------------

const Exchange = require ('./base/Exchange');
const { ExchangeError } = require ('./base/errors');

//  ---------------------------------------------------------------------------

module.exports = class bkex extends Exchange {
    describe () {
        return this.deepExtend (super.describe (), {
            'id': 'bkex',
            'name': 'BKEX',
            'countries': ['BVI'],
            'version': 'v1',
            'rateLimit': 1000,
            'has': {
                'createMarketOrder': false,
                'fetchOrder': false,
                'fetchOrders': false,
                'fetchOpenOrders': false,
                'fetchCurrencies': false,
                'fetchTicker': true,
                'fetchTickers': false,
                'fetchOHLCV': false,
                'fetchOrderBook': true,
                'fetchTrades': false,
            },
            'urls': {
                'api': {
                    'public': 'https://api.bkex.com/v1',
                    'private': 'https://api.bkex.com/v1/u',
                },
                'www': 'https://www.bkex.com',
                'doc': [
                    'https://github.com/bkexexchange/bkex-official-api-docs/blob/master/api_EN.md',
                ],
                'fees': 'https://www.bkex.com/help/instruction/33',
            },
            'api': {
                'public': {
                    'get': [
                        'exchangeInfo',
                        'q/depth',
                        'q/deals',
                        'q/ticker',
                        'q/ticker/price',
                        'q/kline',
                    ],
                },
                'private': {
                    'get': [
                        'trade/order/listUnfinished',
                        'trade/order/history',
                        'trade/order/unfinished/detail',
                        'trade/order/finished/detail',
                        'wallet/balance',
                        'wallet/address',
                        'wallet/withdraw',
                        'wallet/depositRecord',
                        'wallet/withdrawRecord',
                    ],
                    'post': [
                        'trade/order/create',
                        'trade/order/cancel',
                        'trade/order/batchCreate',
                    ],
                },
            },
            'fees': {
                'trading': {
                    'maker': 0.09,
                    'taker': 0.12,
                },
            },
        });
    }

    async fetchMarkets (params = {}) {
        const response = await this.publicGetExchangeInfo (params);
        const data = this.safeValue (response, 'data');
        const markets = this.safeValue (data, 'pairs');
        const numMarkets = markets.length;
        if (numMarkets < 1) {
            throw new ExchangeError (this.id + ' publicGetExchangeInfo returned empty response: ' + this.json (markets));
        }
        const result = [];
        for (let i = 0; i < markets.length; i++) {
            const market = markets[i];
            const id = this.safeString (market, 'pair');
            const baseId = id.split ('_')[0];
            const quoteId = id.split ('_')[1];
            const base = this.safeCurrencyCode (baseId);
            const quote = this.safeCurrencyCode (quoteId);
            const symbol = base + '/' + quote;
            const precision = {
                'amount': this.safeInteger (market, 'amountPrecision'),
                'price': this.safeInteger (market, 'pricePrecision') || this.safeInteger (market, 'defaultPrecision'),
            };
            const minAmount = this.safeFloat (market, 'minimumTradeAmount');
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
            'pair': market['id'],
        }, params);
        const response = await this.publicGetQTicker (request);
        const ticker = this.safeValue (response, 'data');
        return {
            'symbol': symbol,
            'timestamp': timestamp,
            'datetime': this.iso8601 (timestamp),
            'high': this.safeFloat (ticker, 'h'),
            'low': this.safeFloat (ticker, 'l'),
            'bid': undefined,
            'bidVolume': undefined,
            'ask': undefined,
            'askVolume': undefined,
            'vwap': undefined,
            'previousClose': undefined,
            'open': this.safeFloat (ticker, 'o'),
            'close': this.safeFloat (ticker, 'c'),
            'last': this.safeFloat (ticker, 'c'),
            'percentage': undefined,
            'change': this.safeFloat (ticker, 'r'),
            'average': undefined,
            'baseVolume': this.safeFloat (ticker, 'a'),
            'quoteVolume': undefined,
            'info': ticker,
        };
    }

    async fetchOrderBook (symbol, limit = undefined, params = {}) {
        await this.loadMarkets ();
        const request = {
            'pair': this.marketId (symbol),
        };
        if (limit !== undefined) {
            request['size'] = limit;
        }
        const response = await this.publicGetQDepth (this.extend (request, params));
        const data = this.safeValue (response, 'data');
        return this.parseOrderBook (data, undefined, 'bids', 'asks', 'price', 'amt');
    }

    async fetchBalance (params = {}) {
        await this.loadMarkets ();
        const query = this.omit (params, 'type');
        const response = await this.privateGetWalletBalance (query);
        const balances = this.safeValue (response, 'data');
        const wallets = this.safeValue (balances, 'WALLET');
        const result = { 'info': wallets };
        for (let i = 0; i < wallets.length; i++) {
            const wallet = wallets[i];
            const currencyId = wallet['coinType'];
            const code = this.safeCurrencyCode (currencyId);
            const account = this.account ();
            account['free'] = this.safeFloat (wallet, 'available');
            account['total'] = this.safeFloat (wallet, 'total');
            result[code] = account;
        }
        return this.parseBalance (result);
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
            if (method === 'POST') {
                body = this.json (query);
                query = this.encode (body);
            } else {
                query = this.encode (query);
            }
            const payload = this.stringToBase64 (query);
            const secret = this.encode (this.secret);
            const signature = this.hmac (payload, secret, 'sha256');
            headers = {
                'Content-type': 'application/json',
                'X_ACCESS_KEY': this.apiKey,
                'X_SIGNATURE': signature,
            };
        }
        return { 'url': url, 'method': method, 'body': body, 'headers': headers };
    }

    handleErrors (code, reason, url, method, headers, body, response, requestHeaders, requestBody) {
        const httpCode = this.safeInteger (response, 'code', 200);
        if (response === undefined) {
            return;
        }
        if (code >= 400) {
            throw new ExchangeError (this.id + ' HTTP Error ' + code + ' reason: ' + reason);
        }
        if (httpCode >= 400) {
            const message = this.safeValue (response, 'msg', '');
            throw new ExchangeError (this.id + ' HTTP Error ' + httpCode + ' message: ' + message);
        }
    }
};
