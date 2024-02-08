import cexRest from '../cex.js';
import type { Int, OrderSide, OrderType, Strings, Str, OrderBook, Trade, Ticker, Tickers, OHLCV, Order, Balances } from '../base/types.js';
import Client from '../base/ws/Client.js';
export default class cex extends cexRest {
    describe(): any;
    requestId(): any;
    watchBalance(params?: {}): Promise<Balances>;
    handleBalance(client: Client, message: any): void;
    watchTrades(symbol: string, since?: Int, limit?: Int, params?: {}): Promise<Trade[]>;
    handleTradesSnapshot(client: Client, message: any): void;
    parseWsOldTrade(trade: any, market?: any): Trade;
    handleTrade(client: Client, message: any): void;
    watchTicker(symbol: string, params?: {}): Promise<Ticker>;
    watchTickers(symbols?: Strings, params?: {}): Promise<Tickers>;
    fetchTickerWs(symbol: string, params?: {}): Promise<any>;
    handleTicker(client: Client, message: any): void;
    parseWsTicker(ticker: any, market?: any): Ticker;
    fetchBalanceWs(params?: {}): Promise<Balances>;
    watchOrders(symbol?: string, since?: Int, limit?: Int, params?: {}): Promise<Order[]>;
    watchMyTrades(symbol?: Str, since?: Int, limit?: Int, params?: {}): Promise<Trade[]>;
    handleTransaction(client: Client, message: any): void;
    handleMyTrades(client: Client, message: any): void;
    parseWsTrade(trade: any, market?: any): Trade;
    handleOrderUpdate(client: Client, message: any): void;
    parseWsOrderUpdate(order: any, market?: any): Order;
    fromPrecision(amount: any, scale: any): string;
    currencyFromPrecision(currency: any, amount: any): string;
    handleOrdersSnapshot(client: Client, message: any): void;
    watchOrderBook(symbol: string, limit?: Int, params?: {}): Promise<OrderBook>;
    handleOrderBookSnapshot(client: Client, message: any): void;
    pairToSymbol(pair: any): string;
    handleOrderBookUpdate(client: Client, message: any): void;
    handleDelta(bookside: any, delta: any): void;
    handleDeltas(bookside: any, deltas: any): void;
    watchOHLCV(symbol: string, timeframe?: string, since?: Int, limit?: Int, params?: {}): Promise<OHLCV[]>;
    handleInitOHLCV(client: Client, message: any): void;
    handleOHLCV24(client: Client, message: any): any;
    handleOHLCV1m(client: Client, message: any): void;
    handleOHLCV(client: Client, message: any): void;
    fetchOrderWs(id: string, symbol?: string, params?: {}): Promise<Order>;
    fetchOpenOrdersWs(symbol?: string, since?: Int, limit?: Int, params?: {}): Promise<Order[]>;
    createOrderWs(symbol: string, type: OrderType, side: OrderSide, amount: number, price?: number, params?: {}): Promise<Order>;
    editOrderWs(id: string, symbol: string, type: OrderType, side: OrderSide, amount?: number, price?: number, params?: {}): Promise<Order>;
    cancelOrderWs(id: string, symbol?: string, params?: {}): Promise<Order>;
    cancelOrdersWs(ids: string[], symbol?: string, params?: {}): Promise<Order[]>;
    resolveData(client: Client, message: any): void;
    handleConnected(client: Client, message: any): any;
    handleErrorMessage(client: Client, message: any): void;
    handleMessage(client: Client, message: any): void;
    handleAuthenticationMessage(client: Client, message: any): void;
    authenticate(params?: {}): Promise<any>;
}
