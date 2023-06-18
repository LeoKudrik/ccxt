<?php
namespace ccxt;
include_once (__DIR__.'/../../ccxt.php');
// ----------------------------------------------------------------------------

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

// -----------------------------------------------------------------------------

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('UTC');

use ccxt\Precise;
use React\Async;
use React\Promise;


// import { kucoin } from '../../js/ccxt.js';
// AUTO-TRANSPILE //
function example_1() {
    return Async\async(function () {
        $my_ex = new \ccxt\async\kucoin();
        $my_ex->proxy_url = 'https://cors-anywhere.herokuapp.com/'; // It prepends redirect url to requests, so requests leads to call url i.e.: https://cors-anywhere.herokuapp.com/?https://target_url.com . It might be useful for simple redirection or CORS bypassing purposes (Note, this will not work for websocket connections, but only for REST calls).
        // you can also set ".proxyUrlCallback" to callback function with with signature `(url, method, headers, body)` and from there return the proxy url string.
        var_dump(Async\await($my_ex->fetch('https://api.ipify.org/')));
    }) ();
}


function example_2() {
    return Async\async(function () {
        $my_ex = new \ccxt\async\kucoin();
        // same as httpProxy
        $my_ex->https_proxy = 'http://51.83.140.52:11230'; // It sets a real proxy for communication, so calls are made directly to url https://target_url.com , but tunneled through a proxy server (Note, this might work for websocket connections too).
        var_dump(Async\await($my_ex->fetch('https://api.ipify.org/')));
    }) ();
}


function example_3() {
    return Async\async(function () {
        $my_ex = new \ccxt\async\kucoin();
        $my_ex->socks_proxy = 'socks5://127.0.0.1:1080'; // It is for socks5 or socks5h proxy (Note, this might work for websocket connections too).
        var_dump(Async\await($my_ex->fetch('https://api.ipify.org/')));
    }) ();
}


function example_4() {
    return Async\async(function () {
        $my_ex = new \ccxt\async\kucoin();
        // for advanced use, set  `meEx.userAgentCallback` callback
        $my_ex->user_agent_callback = $my_callback;
    }) ();
}


function my_callback($url, $method, $headers, $body) {
    // in JS it sets .agent property for fetch, in PHP it sets .userAgent, in Python (sync) it returns dict of proxies for `Requests` module, in Python (async) it returns signle proxy entry
    return array();  //
}


Async\await(example_1());
