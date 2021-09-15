<?php

namespace ccxt\async;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use \ccxt\ExchangeError;
use \ccxt\Precise;

class btcturk extends Exchange {

    public function describe() {
        return $this->deep_extend(parent::describe (), array(
            'id' => 'btcturk',
            'name' => 'BTCTurk',
            'countries' => array( 'TR' ), // Turkey
            'rateLimit' => 10,
            'has' => array(
                'cancelOrder' => true,
                'CORS' => true,
                'createOrder' => true,
                'fetchBalance' => true,
                'fetchMarkets' => true,
                'fetchOHLCV' => true,
                'fetchOrderBook' => true,
                'fetchOpenOrders' => true,
                'fetchOrders' => true,
                'fetchTicker' => true,
                'fetchTickers' => true,
                'fetchTrades' => true,
                'fetchMyTrades' => true,
            ),
            'timeframes' => array(
                '1d' => '1d',
            ),
            'urls' => array(
                'logo' => 'https://user-images.githubusercontent.com/51840849/87153926-efbef500-c2c0-11ea-9842-05b63612c4b9.jpg',
                'api' => array(
                    'public' => 'https://api.btcturk.com/api/v2',
                    'private' => 'https://api.btcturk.com/api/v1',
                    'graph' => 'https://graph-api.btcturk.com/v1',
                ),
                'www' => 'https://www.btcturk.com',
                'doc' => 'https://github.com/BTCTrader/broker-api-docs',
            ),
            'api' => array(
                'public' => array(
                    'get' => array(
                        'orderbook' => 10,
                        'ticker' => 1,
                        'trades' => 10,   // ?last=COUNT (max 50)
                        'server/exchangeinfo' => 10,
                    ),
                ),
                'private' => array(
                    'get' => array(
                        'users/balances' => 10,
                        'openOrders' => 10,
                        'allOrders' => 10,
                        'users/transactions/trade' => 10,
                    ),
                    'post' => array(
                        'order' => 10,
                        'cancelOrder' => 10,
                    ),
                    'delete' => array(
                        'order' => 10,
                    ),
                ),
                'graph' => array(
                    'get' => array(
                        'ohlcs' => 10,
                    ),
                ),
            ),
            'fees' => array(
                'trading' => array(
                    'maker' => $this->parse_number('0.0005'),
                    'taker' => $this->parse_number('0.0009'),
                ),
            ),
            'exceptions' => array(
                'exact' => array(
                    'FAILED_ORDER_WITH_OPEN_ORDERS' => '\\ccxt\\InsufficientFunds',
                    'FAILED_LIMIT_ORDER' => '\\ccxt\\InvalidOrder',
                    'FAILED_MARKET_ORDER' => '\\ccxt\\InvalidOrder',
                ),
            ),
        ));
    }

    public function fetch_markets($params = array ()) {
        $response = yield $this->publicGetServerExchangeinfo ($params);
        //
        //     {
        //       "$data" => {
        //         "timeZone" => "UTC",
        //         "serverTime" => "1618826678404",
        //         "symbols" => array(
        //           array(
        //             "$id" => "1",
        //             "name" => "BTCTRY",
        //             "nameNormalized" => "BTC_TRY",
        //             "$status" => "TRADING",
        //             "numerator" => "BTC",
        //             "denominator" => "TRY",
        //             "numeratorScale" => "8",
        //             "denominatorScale" => "2",
        //             "hasFraction" => false,
        //             "$filters" => array(
        //               array(
        //                 "$filterType" => "PRICE_FILTER",
        //                 "$minPrice" => "0.0000000000001",
        //                 "$maxPrice" => "10000000",
        //                 "tickSize" => "10",
        //                 "minExchangeValue" => "99.91",
        //                 "$minAmount" => null,
        //                 "$maxAmount" => null
        //               }
        //             ),
        //             "orderMethods" => array(
        //               "MARKET",
        //               "LIMIT",
        //               "STOP_MARKET",
        //               "STOP_LIMIT"
        //             ),
        //             "displayFormat" => "#,###",
        //             "commissionFromNumerator" => false,
        //             "order" => "1000",
        //             "priceRounding" => false
        //           ),
        //         ),
        //       ),
        //     }
        //
        $data = $this->safe_value($response, 'data');
        $markets = $this->safe_value($data, 'symbols', array());
        $result = array();
        for ($i = 0; $i < count($markets); $i++) {
            $entry = $markets[$i];
            $id = $this->safe_string($entry, 'name');
            $baseId = $this->safe_string($entry, 'numerator');
            $quoteId = $this->safe_string($entry, 'denominator');
            $base = $this->safe_currency_code($baseId);
            $quote = $this->safe_currency_code($quoteId);
            $symbol = $base . '/' . $quote;
            $filters = $this->safe_value($entry, 'filters');
            $minPrice = null;
            $maxPrice = null;
            $minAmount = null;
            $maxAmount = null;
            $minCost = null;
            for ($j = 0; $j < count($filters); $j++) {
                $filter = $filters[$j];
                $filterType = $this->safe_string($filter, 'filterType');
                if ($filterType === 'PRICE_FILTER') {
                    $minPrice = $this->safe_number($filter, 'minPrice');
                    $maxPrice = $this->safe_number($filter, 'maxPrice');
                    $minAmount = $this->safe_number($filter, 'minAmount');
                    $maxAmount = $this->safe_number($filter, 'maxAmount');
                    $minCost = $this->safe_number($filter, 'minExchangeValue');
                }
            }
            $status = $this->safe_string($entry, 'status');
            $active = $status === 'TRADING';
            $limits = array(
                'price' => array(
                    'min' => $minPrice,
                    'max' => $maxPrice,
                ),
                'amount' => array(
                    'min' => $minAmount,
                    'max' => $maxAmount,
                ),
                'cost' => array(
                    'min' => $minCost,
                    'max' => null,
                ),
            );
            $precision = array(
                'price' => $this->safe_integer($entry, 'denominatorScale'),
                'amount' => $this->safe_integer($entry, 'numeratorScale'),
            );
            $result[] = array(
                'info' => $entry,
                'symbol' => $symbol,
                'id' => $id,
                'base' => $base,
                'quote' => $quote,
                'baseId' => $baseId,
                'quoteId' => $quoteId,
                'limits' => $limits,
                'precision' => $precision,
                'active' => $active,
            );
        }
        return $result;
    }

    public function fetch_balance($params = array ()) {
        yield $this->load_markets();
        $response = yield $this->privateGetUsersBalances ($params);
        //
        //     {
        //       "$data" => array(
        //         {
        //           "asset" => "TRY",
        //           "assetname" => "Türk Lirası",
        //           "balance" => "0",
        //           "locked" => "0",
        //           "free" => "0",
        //           "orderFund" => "0",
        //           "requestFund" => "0",
        //           "precision" => 2
        //         }
        //       )
        //     }
        //
        $data = $this->safe_value($response, 'data', array());
        $result = array(
            'info' => $response,
            'timestamp' => null,
            'datetime' => null,
        );
        for ($i = 0; $i < count($data); $i++) {
            $entry = $data[$i];
            $currencyId = $this->safe_string($entry, 'asset');
            $code = $this->safe_currency_code($currencyId);
            $account = $this->account();
            $account['total'] = $this->safe_string($entry, 'balance');
            $account['free'] = $this->safe_string($entry, 'free');
            $account['used'] = $this->safe_string($entry, 'locked');
            $result[$code] = $account;
        }
        return $this->parse_balance($result);
    }

    public function fetch_order_book($symbol, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $request = array(
            'pairSymbol' => $market['id'],
        );
        $response = yield $this->publicGetOrderbook (array_merge($request, $params));
        //     {
        //       "$data" => {
        //         "$timestamp" => 1618827901241,
        //         "bids" => array(
        //           array(
        //             "460263.00",
        //             "0.04244000"
        //           )
        //         )
        //       }
        //     }
        $data = $this->safe_value($response, 'data');
        $timestamp = $this->safe_integer($data, 'timestamp');
        return $this->parse_order_book($data, $symbol, $timestamp, 'bids', 'asks', 0, 1);
    }

    public function parse_ticker($ticker, $market = null) {
        //
        //   {
        //     "pair" => "BTCTRY",
        //     "pairNormalized" => "BTC_TRY",
        //     "$timestamp" => 1618826361234,
        //     "$last" => 462485,
        //     "high" => 473976,
        //     "low" => 444201,
        //     "bid" => 461928,
        //     "ask" => 462485,
        //     "open" => 456915,
        //     "volume" => 917.41368645,
        //     "average" => 462868.29574589,
        //     "daily" => 5570,
        //     "dailyPercent" => 1.22,
        //     "denominatorSymbol" => "TRY",
        //     "numeratorSymbol" => "BTC",
        //     "order" => 1000
        //   }
        //
        $marketId = $this->safe_string($ticker, 'pair');
        $symbol = $this->safe_symbol($marketId, $market);
        $timestamp = $this->safe_integer($ticker, 'timestamp');
        $last = $this->safe_number($ticker, 'last');
        return array(
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'high' => $this->safe_number($ticker, 'high'),
            'low' => $this->safe_number($ticker, 'low'),
            'bid' => $this->safe_number($ticker, 'bid'),
            'bidVolume' => null,
            'ask' => $this->safe_number($ticker, 'ask'),
            'askVolume' => null,
            'vwap' => null,
            'open' => $this->safe_number($ticker, 'open'),
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => $this->safe_number($ticker, 'daily'),
            'percentage' => $this->safe_number($ticker, 'dailyPercent'),
            'average' => $this->safe_number($ticker, 'average'),
            'baseVolume' => $this->safe_number($ticker, 'volume'),
            'quoteVolume' => null,
            'info' => $ticker,
        );
    }

    public function fetch_tickers($symbols = null, $params = array ()) {
        yield $this->load_markets();
        $response = yield $this->publicGetTicker ($params);
        $tickers = $this->safe_value($response, 'data');
        return $this->parse_tickers($tickers, $symbols);
    }

    public function fetch_ticker($symbol, $params = array ()) {
        yield $this->load_markets();
        $tickers = yield $this->fetch_tickers(array( $symbol ), $params);
        return $this->safe_value($tickers, $symbol);
    }

    public function parse_trade($trade, $market = null) {
        //
        // fetchTrades
        //     {
        //       "pair" => "BTCUSDT",
        //       "pairNormalized" => "BTC_USDT",
        //       "numerator" => "BTC",
        //       "denominator" => "USDT",
        //       "date" => "1618916879083",
        //       "tid" => "637545136790672520",
        //       "$price" => "55774",
        //       "$amount" => "0.27917100",
        //       "$side" => "buy"
        //     }
        //
        // fetchMyTrades
        //     {
        //       "$price" => "56000",
        //       "numeratorSymbol" => "BTC",
        //       "denominatorSymbol" => "USDT",
        //       "orderType" => "buy",
        //       "orderId" => "2606935102",
        //       "$id" => "320874372",
        //       "$timestamp" => "1618916479593",
        //       "$amount" => "0.00020000",
        //       "$fee" => "0",
        //       "tax" => "0"
        //     }
        //
        $timestamp = $this->safe_integer_2($trade, 'date', 'timestamp');
        $id = $this->safe_string_2($trade, 'tid', 'id');
        $order = $this->safe_string($trade, 'orderId');
        $priceString = $this->safe_string($trade, 'price');
        $amountString = Precise::string_abs($this->safe_string($trade, 'amount'));
        $price = $this->parse_number($priceString);
        $amount = $this->parse_number($amountString);
        $cost = $this->parse_number(Precise::string_mul($priceString, $amountString));
        $marketId = $this->safe_string($trade, 'pair');
        $symbol = $this->safe_symbol($marketId, $market);
        $side = $this->safe_string_2($trade, 'side', 'orderType');
        $fee = null;
        $feeAmountString = $this->safe_string($trade, 'fee');
        if ($feeAmountString !== null) {
            $feeCurrency = $this->safe_string($trade, 'denominatorSymbol');
            $fee = array(
                'cost' => $this->parse_number(Precise::string_abs($feeAmountString)),
                'currency' => $this->safe_currency_code($feeCurrency),
            );
        }
        return array(
            'info' => $trade,
            'id' => $id,
            'order' => $order,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'symbol' => $symbol,
            'type' => null,
            'side' => $side,
            'takerOrMaker' => null,
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'fee' => $fee,
        );
    }

    public function fetch_trades($symbol, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        // $maxCount = 50;
        $request = array(
            'pairSymbol' => $market['id'],
        );
        if ($limit !== null) {
            $request['last'] = $limit;
        }
        $response = yield $this->publicGetTrades (array_merge($request, $params));
        //
        //     {
        //       "$data" => array(
        //         {
        //           "pair" => "BTCTRY",
        //           "pairNormalized" => "BTC_TRY",
        //           "numerator" => "BTC",
        //           "denominator" => "TRY",
        //           "date" => 1618828421497,
        //           "tid" => "637544252214980918",
        //           "price" => "462585.00",
        //           "amount" => "0.01618411",
        //           "side" => "sell"
        //         }
        //       )
        //     }
        //
        $data = $this->safe_value($response, 'data');
        return $this->parse_trades($data, $market, $since, $limit);
    }

    public function parse_ohlcv($ohlcv, $market = null) {
        //     array(
        //        "pair" => "BTCTRY",
        //        "time" => 1508284800,
        //        "open" => 20873.689453125,
        //        "high" => 20925.0,
        //        "low" => 19310.0,
        //        "close" => 20679.55078125,
        //        "volume" => 402.216101626982,
        //        "total" => 8103096.44443274,
        //        "average" => 20146.13,
        //        "dailyChangeAmount" => -194.14,
        //        "dailyChangePercentage" => -0.93
        //      ),
        return array(
            $this->safe_timestamp($ohlcv, 'time'),
            $this->safe_number($ohlcv, 'open'),
            $this->safe_number($ohlcv, 'high'),
            $this->safe_number($ohlcv, 'low'),
            $this->safe_number($ohlcv, 'close'),
            $this->safe_number($ohlcv, 'volume'),
        );
    }

    public function fetch_ohlcv($symbol, $timeframe = '1d', $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $request = array(
            'pair' => $market['id'],
        );
        if ($limit !== null) {
            $request['last'] = $limit;
        }
        $response = yield $this->graphGetOhlcs (array_merge($request, $params));
        return $this->parse_ohlcvs($response, $market, $timeframe, $since, $limit);
    }

    public function create_order($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $request = array(
            'orderType' => $side,
            'orderMethod' => $type,
            'pairSymbol' => $market['id'],
            'quantity' => $this->amount_to_precision($symbol, $amount),
        );
        if ($type !== 'market') {
            $request['price'] = $this->price_to_precision($symbol, $price);
        }
        if (is_array($params) && array_key_exists('clientOrderId', $params)) {
            $request['newClientOrderId'] = $params['clientOrderId'];
        } else if (!(is_array($params) && array_key_exists('newClientOrderId', $params))) {
            $request['newClientOrderId'] = $this->uuid();
        }
        $response = yield $this->privatePostOrder (array_merge($request, $params));
        $data = $this->safe_value($response, 'data');
        return $this->parse_order($data, $market);
    }

    public function cancel_order($id, $symbol = null, $params = array ()) {
        $request = array(
            'id' => $id,
        );
        return yield $this->privateDeleteOrder (array_merge($request, $params));
    }

    public function fetch_open_orders($symbol = null, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $request = array();
        $market = null;
        if ($symbol !== null) {
            $market = $this->market($symbol);
            $request['pairSymbol'] = $market['id'];
        }
        $response = yield $this->privateGetOpenOrders (array_merge($request, $params));
        $data = $this->safe_value($response, 'data');
        $bids = $this->safe_value($data, 'bids', array());
        $asks = $this->safe_value($data, 'asks', array());
        return $this->parse_orders($this->array_concat($bids, $asks), $market, $since, $limit);
    }

    public function fetch_orders($symbol = null, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = $this->market($symbol);
        $request = array(
            'pairSymbol' => $market['id'],
        );
        if ($limit !== null) {
            // default 100 max 1000
            $request['last'] = $limit;
        }
        if ($since !== null) {
            $request['startTime'] = (int) floor($since / 1000);
        }
        $response = yield $this->privateGetAllOrders (array_merge($request, $params));
        // {
        //   "$data" => array(
        //     {
        //       "id" => "2606012912",
        //       "price" => "55000",
        //       "amount" => "0.0003",
        //       "quantity" => "0.0003",
        //       "stopPrice" => "0",
        //       "pairSymbol" => "BTCUSDT",
        //       "pairSymbolNormalized" => "BTC_USDT",
        //       "type" => "buy",
        //       "method" => "$limit",
        //       "orderClientId" => "2ed187bd-59a8-4875-a212-1b793963b85c",
        //       "time" => "1618913189253",
        //       "updateTime" => "1618913189253",
        //       "status" => "Untouched",
        //       "leftAmount" => "0.0003000000000000"
        //     }
        //   )
        // }
        $data = $this->safe_value($response, 'data');
        return $this->parse_orders($data, $market, $since, $limit);
    }

    public function parse_order_status($status) {
        $statuses = array(
            'Untouched' => 'open',
            'Partial' => 'open',
            'Canceled' => 'canceled',
            'Closed' => 'closed',
        );
        return $this->safe_string($statuses, $status, $status);
    }

    public function parse_order($order, $market) {
        //
        // fetchOrders / fetchOpenOrders
        //     {
        //       "$id" => 2605984008,
        //       "$price" => "55000",
        //       "$amount" => "0.00050000",
        //       "quantity" => "0.00050000",
        //       "stopPrice" => "0",
        //       "pairSymbol" => "BTCUSDT",
        //       "pairSymbolNormalized" => "BTC_USDT",
        //       "$type" => "buy",
        //       "method" => "limit",
        //       "orderClientId" => "f479bdb6-0965-4f03-95b5-daeb7aa5a3a5",
        //       "time" => 0,
        //       "updateTime" => 1618913083543,
        //       "$status" => "Untouched",
        //       "leftAmount" => "0.00050000"
        //     }
        //
        // createOrder
        //     {
        //       "$id" => "2606935102",
        //       "quantity" => "0.0002",
        //       "$price" => "56000",
        //       "stopPrice" => null,
        //       "newOrderClientId" => "98e5c491-7ed9-462b-9666-93553180fb28",
        //       "$type" => "buy",
        //       "method" => "limit",
        //       "pairSymbol" => "BTCUSDT",
        //       "pairSymbolNormalized" => "BTC_USDT",
        //       "datetime" => "1618916479523"
        //     }
        //
        $id = $this->safe_string($order, 'id');
        $priceString = $this->safe_string($order, 'price');
        $precisePrice = new Precise ($priceString);
        $price = null;
        $isZero = (string) $precisePrice === '0';
        if (!$isZero) {
            $price = $this->parse_number($precisePrice);
        }
        $amountString = $this->safe_string($order, 'quantity');
        $amount = $this->parse_number(Precise::string_abs($amountString));
        $remaining = $this->safe_number($order, 'leftAmount');
        $marketId = $this->safe_number($order, 'pairSymbol');
        $symbol = $this->safe_symbol($marketId, $market);
        $side = $this->safe_string($order, 'type');
        $type = $this->safe_string($order, 'method');
        $clientOrderId = $this->safe_string($order, 'orderClientId');
        $timestamp = $this->safe_integer_2($order, 'updateTime', 'datetime');
        $rawStatus = $this->safe_string($order, 'status');
        $status = $this->parse_order_status($rawStatus);
        return $this->safe_order(array(
            'info' => $order,
            'id' => $id,
            'price' => $price,
            'amount' => $amount,
            'remaining' => $remaining,
            'filled' => null,
            'cost' => null,
            'average' => null,
            'status' => $status,
            'side' => $side,
            'type' => $type,
            'clientOrderId' => $clientOrderId,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
            'symbol' => $symbol,
            'fee' => null,
        ));
    }

    public function fetch_my_trades($symbol = null, $since = null, $limit = null, $params = array ()) {
        yield $this->load_markets();
        $market = null;
        if ($symbol !== null) {
            $market = $this->market($symbol);
        }
        $response = yield $this->privateGetUsersTransactionsTrade ();
        //
        //     {
        //       "$data" => array(
        //         {
        //           "price" => "56000",
        //           "numeratorSymbol" => "BTC",
        //           "denominatorSymbol" => "USDT",
        //           "orderType" => "buy",
        //           "orderId" => "2606935102",
        //           "id" => "320874372",
        //           "timestamp" => "1618916479593",
        //           "amount" => "0.00020000",
        //           "fee" => "0",
        //           "tax" => "0"
        //         }
        //       ),
        //       "success" => true,
        //       "message" => "SUCCESS",
        //       "code" => "0"
        //     }
        //
        $data = $this->safe_value($response, 'data');
        return $this->parse_trades($data, $market, $since, $limit);
    }

    public function nonce() {
        return $this->milliseconds();
    }

    public function sign($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        if ($this->id === 'btctrader') {
            throw new ExchangeError($this->id . ' is an abstract base API for BTCExchange, BTCTurk');
        }
        $url = $this->urls['api'][$api] . '/' . $path;
        if (($method === 'GET') || ($method === 'DELETE')) {
            if ($params) {
                $url .= '?' . $this->urlencode($params);
            }
        } else {
            $body = $this->json($params);
        }
        if ($api === 'private') {
            $this->check_required_credentials();
            $nonce = (string) $this->nonce();
            $secret = base64_decode($this->secret);
            $auth = $this->apiKey . $nonce;
            $headers = array(
                'X-PCK' => $this->apiKey,
                'X-Stamp' => $nonce,
                'X-Signature' => $this->hmac($this->encode($auth), $secret, 'sha256', 'base64'),
                'Content-Type' => 'application/json',
            );
        }
        return array( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function handle_errors($code, $reason, $url, $method, $headers, $body, $response, $requestHeaders, $requestBody) {
        $errorCode = $this->safe_string($response, 'code', '0');
        $message = $this->safe_string($response, 'message');
        $output = ($message === null) ? $body : $message;
        $this->throw_exactly_matched_exception($this->exceptions['exact'], $message, $this->id . ' ' . $output);
        if ($errorCode !== '0') {
            throw new ExchangeError($this->id . ' ' . $output);
        }
    }
}
