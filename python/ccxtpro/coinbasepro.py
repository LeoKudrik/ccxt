# -*- coding: utf-8 -*-

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

from ccxtpro.base.exchange import Exchange
import ccxt.async_support as ccxt
from ccxtpro.base.cache import ArrayCache, ArrayCacheBySymbolById
import hashlib
from ccxt.base.errors import BadSymbol


class coinbasepro(Exchange, ccxt.coinbasepro):

    def describe(self):
        return self.deep_extend(super(coinbasepro, self).describe(), {
            'has': {
                'ws': True,
                'watchOHLCV': False,  # missing on the exchange side
                'watchOrderBook': True,
                'watchTicker': True,
                'watchTickers': False,  # for now
                'watchTrades': True,
                'watchBalance': False,
                'watchStatus': False,  # for now
                'watchOrders': True,
                'watchMyTrades': True,
            },
            'urls': {
                'api': {
                    'ws': 'wss://ws-feed.pro.coinbase.com',
                },
            },
            'options': {
                'tradesLimit': 1000,
                'ordersLimit': 1000,
                'myTradesLimit': 1000,
            },
        })

    def authenticate(self):
        self.check_required_credentials()
        path = '/users/self/verify'
        nonce = self.nonce()
        payload = str(nonce) + 'GET' + path
        signature = self.hmac(self.encode(payload), self.base64_to_binary(self.secret), hashlib.sha256, 'base64')
        return {
            'timestamp': nonce,
            'key': self.apiKey,
            'signature': signature,
            'passphrase': self.password,
        }

    async def subscribe(self, name, symbol, messageHashStart, params={}):
        await self.load_markets()
        market = self.market(symbol)
        messageHash = messageHashStart + ':' + market['id']
        url = self.urls['api']['ws']
        if 'signature' in params:
            # need to distinguish between public trades and user trades
            url = url + '?'
        subscribe = {
            'type': 'subscribe',
            'product_ids': [
                market['id'],
            ],
            'channels': [
                name,
            ],
        }
        request = self.extend(subscribe, params)
        return await self.watch(url, messageHash, request, messageHash)

    async def watch_ticker(self, symbol, params={}):
        name = 'ticker'
        return await self.subscribe(name, symbol, name, params)

    async def watch_trades(self, symbol, since=None, limit=None, params={}):
        name = 'matches'
        trades = await self.subscribe(name, symbol, name, params)
        if self.newUpdates:
            limit = trades.getLimit(symbol, limit)
        return self.filter_by_since_limit(trades, since, limit, 'timestamp', True)

    async def watch_my_trades(self, symbol=None, since=None, limit=None, params={}):
        if symbol is None:
            raise BadSymbol(self.id + ' watchMyTrades requires a symbol')
        name = 'user'
        messageHash = 'myTrades'
        authentication = self.authenticate()
        trades = await self.subscribe(name, symbol, messageHash, self.extend(params, authentication))
        if self.newUpdates:
            limit = trades.getLimit(symbol, limit)
        return self.filter_by_since_limit(trades, since, limit, 'timestamp', True)

    async def watch_orders(self, symbol=None, since=None, limit=None, params={}):
        if symbol is None:
            raise BadSymbol(self.id + ' watchMyTrades requires a symbol')
        name = 'user'
        messageHash = 'orders'
        authentication = self.authenticate()
        orders = await self.subscribe(name, symbol, messageHash, self.extend(params, authentication))
        if self.newUpdates:
            limit = orders.getLimit(symbol, limit)
        return self.filter_by_since_limit(orders, since, limit, 'timestamp', True)

    async def watch_order_book(self, symbol, limit=None, params={}):
        name = 'level2'
        await self.load_markets()
        market = self.market(symbol)
        messageHash = name + ':' + market['id']
        url = self.urls['api']['ws']
        subscribe = {
            'type': 'subscribe',
            'product_ids': [
                market['id'],
            ],
            'channels': [
                name,
            ],
        }
        request = self.extend(subscribe, params)
        subscription = {
            'messageHash': messageHash,
            'symbol': symbol,
            'marketId': market['id'],
            'limit': limit,
        }
        orderbook = await self.watch(url, messageHash, request, messageHash, subscription)
        return orderbook.limit(limit)

    def handle_trade(self, client, message):
        #
        #     {
        #         type: 'match',
        #         trade_id: 82047307,
        #         maker_order_id: '0f358725-2134-435e-be11-753912a326e0',
        #         taker_order_id: '252b7002-87a3-425c-ac73-f5b9e23f3caf',
        #         side: 'sell',
        #         size: '0.00513192',
        #         price: '9314.78',
        #         product_id: 'BTC-USD',
        #         sequence: 12038915443,
        #         time: '2020-01-31T20:03:41.158814Z'
        #     }
        #
        marketId = self.safe_string(message, 'product_id')
        if marketId is not None:
            trade = self.parse_ws_trade(message)
            symbol = trade['symbol']
            # the exchange sends type = 'match'
            # but requires 'matches' upon subscribing
            # therefore we resolve 'matches' here instead of 'match'
            type = 'matches'
            messageHash = type + ':' + marketId
            array = self.safe_value(self.trades, symbol)
            if array is None:
                tradesLimit = self.safe_integer(self.options, 'tradesLimit', 1000)
                array = ArrayCache(tradesLimit)
                self.trades[symbol] = array
            array.append(trade)
            client.resolve(array, messageHash)
        return message

    def handle_my_trade(self, client, message):
        marketId = self.safe_string(message, 'product_id')
        if marketId is not None:
            trade = self.parse_ws_trade(message)
            type = 'myTrades'
            messageHash = type + ':' + marketId
            array = self.myTrades
            if array is None:
                limit = self.safe_integer(self.options, 'myTradesLimit', 1000)
                array = ArrayCacheBySymbolById(limit)
                self.myTrades = array
            array.append(trade)
            client.resolve(array, messageHash)
        return message

    def parse_ws_trade(self, trade):
        #
        # private trades
        # {
        #     "type": "match",
        #     "trade_id": 10,
        #     "sequence": 50,
        #     "maker_order_id": "ac928c66-ca53-498f-9c13-a110027a60e8",
        #     "taker_order_id": "132fb6ae-456b-4654-b4e0-d681ac05cea1",
        #     "time": "2014-11-07T08:19:27.028459Z",
        #     "product_id": "BTC-USD",
        #     "size": "5.23512",
        #     "price": "400.23",
        #     "side": "sell",
        #     "taker_user_id: "5844eceecf7e803e259d0365",
        #     "user_id": "5844eceecf7e803e259d0365",
        #     "taker_profile_id": "765d1549-9660-4be2-97d4-fa2d65fa3352",
        #     "profile_id": "765d1549-9660-4be2-97d4-fa2d65fa3352",
        #     "taker_fee_rate": "0.005"
        # }
        #
        # {
        #     "type": "match",
        #     "trade_id": 10,
        #     "sequence": 50,
        #     "maker_order_id": "ac928c66-ca53-498f-9c13-a110027a60e8",
        #     "taker_order_id": "132fb6ae-456b-4654-b4e0-d681ac05cea1",
        #     "time": "2014-11-07T08:19:27.028459Z",
        #     "product_id": "BTC-USD",
        #     "size": "5.23512",
        #     "price": "400.23",
        #     "side": "sell",
        #     "maker_user_id: "5844eceecf7e803e259d0365",
        #     "maker_id": "5844eceecf7e803e259d0365",
        #     "maker_profile_id": "765d1549-9660-4be2-97d4-fa2d65fa3352",
        #     "profile_id": "765d1549-9660-4be2-97d4-fa2d65fa3352",
        #     "maker_fee_rate": "0.001"
        # }
        #
        # public trades
        # {
        #     "type": "received",
        #     "time": "2014-11-07T08:19:27.028459Z",
        #     "product_id": "BTC-USD",
        #     "sequence": 10,
        #     "order_id": "d50ec984-77a8-460a-b958-66f114b0de9b",
        #     "size": "1.34",
        #     "price": "502.1",
        #     "side": "buy",
        #     "order_type": "limit"
        # }
        parsed = super(coinbasepro, self).parse_trade(trade)
        feeRate = None
        if 'maker_fee_rate' in trade:
            parsed['takerOrMaker'] = 'maker'
            feeRate = self.safe_float(trade, 'maker_fee_rate')
        else:
            parsed['takerOrMaker'] = 'taker'
            feeRate = self.safe_float(trade, 'taker_fee_rate')
        market = self.market(parsed['symbol'])
        feeCurrency = market['quote']
        feeCost = None
        if (parsed['cost'] is not None) and (feeRate is not None):
            feeCost = parsed['cost'] * feeRate
        parsed['fee'] = {
            'rate': feeRate,
            'cost': feeCost,
            'currency': feeCurrency,
        }
        return parsed

    def parse_ws_order_status(self, status):
        statuses = {
            'filled': 'closed',
            'canceled': 'canceled',
        }
        return self.safe_string(statuses, status, 'open')

    def handle_order(self, client, message):
        #
        # Order is created
        # {
        #   type: 'received',
        #   side: 'sell',
        #   product_id: 'BTC-USDC',
        #   time: '2021-03-05T16:42:21.878177Z',
        #   sequence: 5641953814,
        #   profile_id: '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        #   user_id: '54fc141576dcf32596000133',
        #   order_id: '11838707-bf9c-4d65-8cec-b57c9a7cab42',
        #   order_type: 'limit',
        #   size: '0.0001',
        #   price: '50000',
        #   client_oid: 'a317abb9-2b30-4370-ebfe-0deecb300180'
        # }
        #
        # {
        #     "type": "received",
        #     "time": "2014-11-09T08:19:27.028459Z",
        #     "product_id": "BTC-USD",
        #     "sequence": 12,
        #     "order_id": "dddec984-77a8-460a-b958-66f114b0de9b",
        #     "funds": "3000.234",
        #     "side": "buy",
        #     "order_type": "market"
        # }
        #
        # Order is on the order book
        # {
        #   type: 'open',
        #   side: 'sell',
        #   product_id: 'BTC-USDC',
        #   time: '2021-03-05T16:42:21.878177Z',
        #   sequence: 5641953815,
        #   profile_id: '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        #   user_id: '54fc141576dcf32596000133',
        #   price: '50000',
        #   order_id: '11838707-bf9c-4d65-8cec-b57c9a7cab42',
        #   remaining_size: '0.0001'
        # }
        #
        # Order is partially or completely filled
        # {
        #   type: 'match',
        #   side: 'sell',
        #   product_id: 'BTC-USDC',
        #   time: '2021-03-05T16:37:13.396107Z',
        #   sequence: 5641897876,
        #   profile_id: '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        #   user_id: '54fc141576dcf32596000133',
        #   trade_id: 5455505,
        #   maker_order_id: 'e5f5754d-70a3-4346-95a6-209bcb503629',
        #   taker_order_id: '88bf7086-7b15-40ff-8b19-ab4e08516d69',
        #   size: '0.00021019',
        #   price: '47338.46',
        #   taker_profile_id: '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        #   taker_user_id: '54fc141576dcf32596000133',
        #   taker_fee_rate: '0.005'
        # }
        #
        #  Order is canceled / closed
        # {
        #   type: 'done',
        #   side: 'buy',
        #   product_id: 'BTC-USDC',
        #   time: '2021-03-05T16:37:13.396107Z',
        #   sequence: 5641897877,
        #   profile_id: '774ee0ce-fdda-405f-aa8d-47189a14ba0a',
        #   user_id: '54fc141576dcf32596000133',
        #   order_id: '88bf7086-7b15-40ff-8b19-ab4e08516d69',
        #   reason: 'filled'
        # }
        #
        orders = self.orders
        if orders is None:
            limit = self.safe_integer(self.options, 'ordersLimit', 1000)
            orders = ArrayCacheBySymbolById(limit)
            self.orders = orders
        type = self.safe_string(message, 'type')
        marketId = self.safe_string(message, 'product_id')
        if marketId is not None:
            messageHash = 'orders:' + marketId
            symbol = self.safe_symbol(marketId)
            orderId = self.safe_string(message, 'order_id')
            makerOrderId = self.safe_string(message, 'maker_order_id')
            takerOrderId = self.safe_string(message, 'taker_order_id')
            orders = self.orders
            previousOrders = self.safe_value(orders.hashmap, symbol, {})
            previousOrder = self.safe_value(previousOrders, orderId)
            if previousOrder is None:
                previousOrder = self.safe_value_2(previousOrders, makerOrderId, takerOrderId)
            if previousOrder is None:
                parsed = self.parse_ws_order(message)
                orders.append(parsed)
            else:
                if type == 'match':
                    trade = self.parse_ws_trade(message)
                    if previousOrder['trades'] is None:
                        previousOrder['trades'] = []
                    previousOrder['trades'].append(trade)
                    previousOrder['lastTradeTimestamp'] = trade['timestamp']
                    totalCost = 0
                    totalAmount = 0
                    trades = previousOrder['trades']
                    for i in range(0, len(trades)):
                        trade = trades[i]
                        totalCost = self.sum(totalCost, trade['cost'])
                        totalAmount = self.sum(totalAmount, trade['amount'])
                    if totalAmount > 0:
                        previousOrder['average'] = totalCost / totalAmount
                    previousOrder['cost'] = totalCost
                    if previousOrder['filled'] is not None:
                        previousOrder['filled'] += trade['amount']
                        if previousOrder['amount'] is not None:
                            previousOrder['remaining'] = previousOrder['amount'] - previousOrder['filled']
                    if previousOrder['fee'] is None:
                        previousOrder['fee'] = {
                            'cost': 0,
                            'currency': trade['fee']['currency'],
                        }
                    if (previousOrder['fee']['cost'] is not None) and (trade['fee']['cost'] is not None):
                        previousOrder['fee']['cost'] = self.sum(previousOrder['fee']['cost'], trade['fee']['cost'])
                else:
                    info = self.extend(previousOrder['info'], message)
                    order = self.parse_ws_order(info)
                    keys = list(order.keys())
                    # update the reference
                    for i in range(0, len(keys)):
                        key = keys[i]
                        if order[key] is not None:
                            previousOrder[key] = order[key]
                # update the newUpdates count
                orders.append(previousOrder)
            client.resolve(orders, messageHash)

    def parse_ws_order(self, order):
        id = self.safe_string(order, 'order_id')
        marketId = self.safe_string(order, 'product_id')
        symbol = self.safe_symbol(marketId)
        side = self.safe_string(order, 'side')
        price = self.safe_float(order, 'price')
        amount = self.safe_float_2(order, 'size', 'funds')
        time = self.safe_string(order, 'time')
        timestamp = self.parse8601(time)
        reason = self.safe_string(order, 'reason')
        status = self.parse_ws_order_status(reason)
        orderType = self.safe_string(order, 'order_type')
        remaining = self.safe_float(order, 'remaining_size')
        type = self.safe_string(order, 'type')
        filled = None
        if (amount is not None) and (remaining is not None):
            filled = amount - remaining
        elif type == 'received':
            filled = 0
        cost = None
        if (price is not None) and (amount is not None):
            cost = price * amount
        return {
            'info': order,
            'symbol': symbol,
            'id': id,
            'clientOrderId': None,
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'lastTradeTimestamp': None,
            'type': orderType,
            'timeInForce': None,
            'postOnly': None,
            'side': side,
            'price': price,
            'stopPrice': None,
            'amount': amount,
            'cost': cost,
            'average': None,
            'filled': filled,
            'remaining': remaining,
            'status': status,
            'fee': None,
            'trades': None,
        }

    def handle_ticker(self, client, message):
        #
        #     {
        #         type: 'ticker',
        #         sequence: 12042642428,
        #         product_id: 'BTC-USD',
        #         price: '9380.55',
        #         open_24h: '9450.81000000',
        #         volume_24h: '9611.79166047',
        #         low_24h: '9195.49000000',
        #         high_24h: '9475.19000000',
        #         volume_30d: '327812.00311873',
        #         best_bid: '9380.54',
        #         best_ask: '9380.55',
        #         side: 'buy',
        #         time: '2020-02-01T01:40:16.253563Z',
        #         trade_id: 82062566,
        #         last_size: '0.41969131'
        #     }
        #
        marketId = self.safe_string(message, 'product_id')
        if marketId is not None:
            ticker = self.parse_ticker(message)
            symbol = ticker['symbol']
            self.tickers[symbol] = ticker
            type = self.safe_string(message, 'type')
            messageHash = type + ':' + marketId
            client.resolve(ticker, messageHash)
        return message

    def parse_ticker(self, ticker, market=None):
        #
        #     {
        #         type: 'ticker',
        #         sequence: 12042642428,
        #         product_id: 'BTC-USD',
        #         price: '9380.55',
        #         open_24h: '9450.81000000',
        #         volume_24h: '9611.79166047',
        #         low_24h: '9195.49000000',
        #         high_24h: '9475.19000000',
        #         volume_30d: '327812.00311873',
        #         best_bid: '9380.54',
        #         best_ask: '9380.55',
        #         side: 'buy',
        #         time: '2020-02-01T01:40:16.253563Z',
        #         trade_id: 82062566,
        #         last_size: '0.41969131'
        #     }
        #
        type = self.safe_string(ticker, 'type')
        if type is None:
            return super(coinbasepro, self).parse_ticker(ticker, market)
        marketId = self.safe_string(ticker, 'product_id')
        symbol = self.safe_symbol(marketId, market, '-')
        timestamp = self.parse8601(self.safe_string(ticker, 'time'))
        last = self.safe_float(ticker, 'price')
        return {
            'symbol': symbol,
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'high': self.safe_float(ticker, 'high_24h'),
            'low': self.safe_float(ticker, 'low_24h'),
            'bid': self.safe_float(ticker, 'best_bid'),
            'bidVolume': None,
            'ask': self.safe_float(ticker, 'best_ask'),
            'askVolume': None,
            'vwap': None,
            'open': self.safe_float(ticker, 'open_24h'),
            'close': last,
            'last': last,
            'previousClose': None,
            'change': None,
            'percentage': None,
            'average': None,
            'baseVolume': self.safe_float(ticker, 'volume_24h'),
            'quoteVolume': None,
            'info': ticker,
        }

    def handle_delta(self, bookside, delta):
        price = self.safe_float(delta, 0)
        amount = self.safe_float(delta, 1)
        bookside.store(price, amount)

    def handle_deltas(self, bookside, deltas):
        for i in range(0, len(deltas)):
            self.handle_delta(bookside, deltas[i])

    def handle_order_book(self, client, message):
        #
        # first message(snapshot)
        #
        #     {
        #         "type": "snapshot",
        #         "product_id": "BTC-USD",
        #         "bids": [
        #             ["10101.10", "0.45054140"]
        #         ],
        #         "asks": [
        #             ["10102.55", "0.57753524"]
        #         ]
        #     }
        #
        # subsequent updates
        #
        #     {
        #         "type": "l2update",
        #         "product_id": "BTC-USD",
        #         "time": "2019-08-14T20:42:27.265Z",
        #         "changes": [
        #             ["buy", "10101.80000000", "0.162567"]
        #         ]
        #     }
        #
        type = self.safe_string(message, 'type')
        marketId = self.safe_string(message, 'product_id')
        market = self.safe_market(marketId, None, '-')
        symbol = market['symbol']
        name = 'level2'
        messageHash = name + ':' + marketId
        subscription = self.safe_value(client.subscriptions, messageHash, {})
        limit = self.safe_integer(subscription, 'limit')
        if type == 'snapshot':
            self.orderbooks[symbol] = self.order_book({}, limit)
            orderbook = self.orderbooks[symbol]
            self.handle_deltas(orderbook['asks'], self.safe_value(message, 'asks', []))
            self.handle_deltas(orderbook['bids'], self.safe_value(message, 'bids', []))
            orderbook['timestamp'] = None
            orderbook['datetime'] = None
            client.resolve(orderbook, messageHash)
        elif type == 'l2update':
            orderbook = self.orderbooks[symbol]
            timestamp = self.parse8601(self.safe_string(message, 'time'))
            changes = self.safe_value(message, 'changes', [])
            sides = {
                'sell': 'asks',
                'buy': 'bids',
            }
            for i in range(0, len(changes)):
                change = changes[i]
                key = self.safe_string(change, 0)
                side = self.safe_string(sides, key)
                price = self.safe_float(change, 1)
                amount = self.safe_float(change, 2)
                bookside = orderbook[side]
                bookside.store(price, amount)
            orderbook['timestamp'] = timestamp
            orderbook['datetime'] = self.iso8601(timestamp)
            client.resolve(orderbook, messageHash)

    def handle_subscription_status(self, client, message):
        #
        #     {
        #         type: 'subscriptions',
        #         channels: [
        #             {
        #                 name: 'level2',
        #                 product_ids: ['ETH-BTC']
        #             }
        #         ]
        #     }
        #
        return message

    def handle_message(self, client, message):
        type = self.safe_string(message, 'type')
        methods = {
            'snapshot': self.handle_order_book,
            'l2update': self.handle_order_book,
            'subscribe': self.handle_subscription_status,
            'ticker': self.handle_ticker,
            'received': self.handle_order,
            'open': self.handle_order,
            'change': self.handle_order,
            'done': self.handle_order,
        }
        length = len(client.url)
        authenticated = client.url[length - 1] == '?'
        method = self.safe_value(methods, type)
        if method is None:
            if type == 'match':
                if authenticated:
                    self.handle_my_trade(client, message)
                    self.handle_order(client, message)
                else:
                    self.handle_trade(client, message)
        else:
            return method(client, message)
