# -*- coding: utf-8 -*-

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

from ccxt.base.exchange import Exchange
import base64
import hashlib
import math
from ccxt.base.errors import ExchangeError
from ccxt.base.errors import AuthenticationError
from ccxt.base.errors import ArgumentsRequired
from ccxt.base.errors import InsufficientFunds
from ccxt.base.errors import InvalidOrder
from ccxt.base.errors import OrderNotFound
from ccxt.base.errors import DDoSProtection


class p2pb2b(Exchange):

    def describe(self):
        return self.deep_extend(super(p2pb2b, self).describe(), {
            'id': 'p2pb2b',
            'name': 'p2pb2b',
            'countries': ['EE'],
            'version': 'v1',
            'rateLimit': 1000,
            'has': {
                'createMarketOrder': False,
                'fetchOrder': True,
                'fetchOrders': False,
                'fetchOpenOrders': True,
                'fetchCurrencies': False,
                'fetchL2OrderBook': False,
                'fetchTicker': True,
                'fetchTickers': False,
                'fetchOHLCV': False,
                'fetchTrades': False,
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
                    'tierBased': False,
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
        })

    def fetch_markets(self, params={}):
        response = self.publicGetMarkets(params)
        markets = self.safe_value(response, 'result')
        numMarkets = len(markets)
        if numMarkets < 1:
            raise ExchangeError(self.id + ' publicGetMarkets returned empty response: ' + self.json(markets))
        result = []
        for i in range(0, len(markets)):
            market = markets[i]
            baseId = self.safe_string(market, 'stock')
            quoteId = self.safe_string(market, 'money')
            id = baseId + '_' + quoteId
            base = self.safe_currency_code(baseId)
            quote = self.safe_currency_code(quoteId)
            symbol = base + '/' + quote
            precision = {
                'amount': self.safe_integer(market, 'stockPrec'),
                'price': self.safe_integer(market, 'moneyPrec'),
            }
            minAmount = self.safe_float(market, 'minAmount', 0)
            result.append({
                'id': id,
                'symbol': symbol,
                'base': base,
                'quote': quote,
                'baseId': baseId,
                'quoteId': quoteId,
                'active': True,
                'precision': precision,
                'limits': {
                    'amount': {
                        'min': minAmount,
                        'max': None,
                    },
                    'price': {
                        'min': math.pow(10, -precision['price']),
                        'max': None,
                    },
                    'cost': {
                        'min': None,
                        'max': None,
                    },
                },
                'info': market,
            })
        return result

    def fetch_ticker(self, symbol, params={}):
        self.load_markets()
        timestamp = self.milliseconds()
        market = self.market(symbol)
        request = self.extend({
            'market': market['id'],
        }, params)
        response = self.publicGetTicker(request)
        ticker = self.safe_value(response, 'result')
        return {
            'symbol': symbol,
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'high': self.safe_float(ticker, 'high'),
            'low': self.safe_float(ticker, 'low'),
            'bid': self.safe_float(ticker, 'bid'),
            'bidVolume': None,
            'ask': self.safe_float(ticker, 'ask'),
            'askVolume': None,
            'vwap': None,
            'previousClose': None,
            'open': self.safe_float(ticker, 'open'),
            'close': self.safe_float(ticker, 'last'),
            'last': self.safe_float(ticker, 'last'),
            'percentage': None,
            'change': self.safe_float(ticker, 'change'),
            'average': None,
            'baseVolume': self.safe_float(ticker, 'volume'),
            'quoteVolume': self.safe_float(ticker, 'deal'),
            'info': ticker,
        }

    def create_order(self, symbol, _type, side, amount, price=None, params={}):
        self.load_markets()
        market = self.market(symbol)
        method = 'privatePostOrderNew'
        request = {
            'side': side,
            'market': market['id'],
            'amount': self.amount_to_precision(symbol, amount),
            'price': self.price_to_precision(symbol, price),
        }
        response = getattr(self, method)(self.extend(request, params))
        order = self.parse_new_order(self.safe_value(response, 'result'), market)
        return order

    def cancel_order(self, id, symbol=None, params={}):
        self.load_markets()
        request = {
            'market': self.market_id(symbol),
            'orderId': int(id),
        }
        return self.privatePostOrderCancel(self.extend(request, params))

    def fetch_open_orders(self, symbol=None, since=None, limit=None, params={}):
        if symbol is None:
            raise ArgumentsRequired(self.id + ' fetchOrders requires a symbol argument')
        self.load_markets()
        market = self.market(symbol)
        request = {
            'market': market['id'],
        }
        if limit is not None:
            request['limit'] = limit
        response = self.privatePostOrders(self.extend(request, params))
        result = self.safe_value(response, 'result')
        return self.parse_orders(result, market, since, limit)

    def fetch_order(self, id, symbol=None, params={}):
        self.load_markets()
        orderIdField = self.get_order_id_field()
        request = {}
        request[orderIdField] = id
        response = self.privatePostAccountOrder(self.extend(request, params))
        if len(response['result']) == 0:
            raise OrderNotFound(self.id + ' order ' + id + ' not found')
        return self.parse_order(response['result']['records'])

    def fetch_l2_order_book(self, symbol, limit=None, params={}):
        if params['side'] is None:
            raise ArgumentsRequired(self.id + ' fetchL2OrderBook requires a side argument')
        self.load_markets()
        request = {
            'market': self.market_id(symbol),
            'side': params['side'],
        }
        if limit is not None:
            request['limit'] = limit
        response = self.publicGetBook(self.extend(request, params))
        timestamp = self.safe_value(response, 'cache_time')
        orderBook = self.safe_value(response, 'result')
        return self.parse_l2_order_book(orderBook, timestamp)

    def fetch_order_book(self, symbol, limit=None, params={}):
        self.load_markets()
        request = {
            'market': self.market_id(symbol),
        }
        if limit is not None:
            request['limit'] = limit
        response = self.publicGetDepthResult(self.extend(request, params))
        orderBook = self.safe_value(response, 'result')
        return self.parse_order_book(orderBook, None, 'bids', 'asks')

    def fetch_balance(self, params={}):
        self.load_markets()
        query = self.omit(params, 'type')
        response = self.privatePostAccountBalances(query)
        balances = self.safe_value(response, 'result')
        symbols = list(balances.keys())
        result = {'info': balances}
        for i in range(0, len(symbols)):
            currencyId = symbols[i]
            balance = balances[currencyId]
            account = self.account()
            account['free'] = self.safe_float(balance, 'available')
            account['total'] = self.safe_float(balance, 'available') + self.safe_float(balance, 'freeze')
            result[currencyId] = account
        return self.parse_balance(result)

    def sign(self, path, api='public', method='GET', params={}, headers=None, body=None):
        url = self.urls['api'][api] + '/' + self.implode_params(path, params)
        query = self.omit(params, self.extract_params(path))
        if api == 'public':
            if query:
                url += '?' + self.urlencode(query)
        else:
            self.check_required_credentials()
            request = '/api/' + self.version + '/' + self.implode_params(path, params)
            nonce = str(self.nonce())
            query = self.extend({
                'nonce': str(nonce),
                'request': request,
            }, query)
            body = self.json(query)
            query = self.encode(body)
            payload = base64.b64encode(query)
            secret = self.encode(self.secret)
            signature = self.hmac(payload, secret, hashlib.sha512)
            headers = {
                'Content-type': 'application/json',
                'X-TXC-APIKEY': self.apiKey,
                'X-TXC-PAYLOAD': payload,
                'X-TXC-SIGNATURE': signature,
            }
        return {'url': url, 'method': method, 'body': body, 'headers': headers}

    def parse_l2_order_book(self, orderbook, timestamp=None, bidsKey='bids', asksKey='asks', priceKey=0):
        orders = self.safe_value(orderbook, 'orders')
        asks = []
        bids = []
        if len(orders) > 0:
            side = self.safe_value(orders[0], 'side')
            bookMap = {}
            book = []
            for i in range(0, len(orders)):
                price = self.safe_float(orders[i], 'price')
                amount = self.safe_float(orders[i], 'amount')
                existingOrderAmount = bookMap[price] or 0.0
                bookMap[price] = amount + existingOrderAmount
            for i in range(0, bookMap):
                key = list(bookMap.keys())[i]
                book.append([float(key), bookMap[key]])
            if side == 'buy':
                bids = self.sort_by(book, priceKey, True)
            else:
                asks = self.sort_by(book, priceKey)
        return {
            'bids': bids,
            'asks': asks,
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'nonce': None,
        }

    def parse_new_order(self, order, market=None):
        marketName = self.safe_string(order, 'market')
        market = market or self.find_market(marketName)
        symbol = self.safe_string(market, 'symbol')
        timestamp = self.safe_string(order, 'timestamp')
        if timestamp is not None:
            timestamp = int(round(float(timestamp)) * 1000)
        amount = self.safe_float(order, 'amount')
        remaining = self.safe_float(order, 'left')
        fillAmount = amount - remaining
        return {
            'id': self.safe_string(order, 'orderId'),
            'datetime': self.iso8601(timestamp),
            'timestamp': timestamp,
            'lastTradeTimestamp': None,
            'status': None,
            'symbol': symbol,
            'type': self.safe_string(order, 'type'),
            'side': self.safe_string(order, 'side'),
            'price': self.safe_float(order, 'price'),
            'cost': self.safe_float(order, 'dealFee', 0.0),
            'amount': amount,
            'filled': fillAmount,
            'remaining': remaining,
            'fee': self.safe_float(order, 'dealFee'),
            'info': order,
        }

    def parse_order(self, order, market=None):
        marketName = self.safe_string(order, 'market')
        market = market or self.find_market(marketName)
        symbol = self.safe_string(market, 'symbol')
        timestamp = self.safe_string(order, 'time')
        if timestamp is not None:
            timestamp = int(round(float(timestamp)) * 1000)
        amount = self.safe_float(order, 'amount')
        fillAmount = self.safe_float(order, 'dealStock', amount)
        remaining = amount - fillAmount
        return {
            'id': self.safe_string(order, 'id'),
            'datetime': self.iso8601(timestamp),
            'timestamp': timestamp,
            'lastTradeTimestamp': None,
            'status': None,
            'symbol': symbol,
            'type': self.safe_string(order, 'type'),
            'side': self.safe_string(order, 'side'),
            'price': self.safe_float(order, 'price'),
            'cost': self.safe_float(order, 'dealFee', 0.0),
            'amount': amount,
            'filled': fillAmount,
            'remaining': remaining,
            'fee': self.safe_float(order, 'dealFee'),
            'info': order,
        }

    def get_order_id_field(self):
        return 'orderId'

    def handle_errors(self, code, _reason, _url, _method, _headers, body, response, requestHeaders, requestBody):
        if response is None:
            return
        if len(body) > 0:
            if body[0] == '{':
                success = self.safe_value(response, 'success', True)
                errorMessage = self.safe_value(response, 'message', [[]])
                if not success and errorMessage:
                    message = ''
                    if isinstance(errorMessage, list):
                        message = str(errorMessage)
                    else:
                        messageKey = list(errorMessage.keys())[0]
                        message = errorMessage[messageKey][0]
                    exceptionMessages = list(self.exceptions.keys())
                    for i in range(0, len(exceptionMessages)):
                        exceptionMessage = exceptionMessages[i]
                        if message.find(exceptionMessage) >= 0:
                            ExceptionClass = self.exceptions[exceptionMessage]
                            raise ExceptionClass(self.id + ' ' + message)
                    raise ExchangeError(self.id + ' Error ' + message)
        if code >= 400 or self.safe_value(response, 'status', 200) >= 400:
            if body.find('Server Error') >= 0:
                raise ExchangeError(self.id + ' Server Error')
