//////////////////////////////////////
//          TESTE P2PB2B
//////////////////////////////////////

// // npm run build
// // node run-tests --js --python coinsbit
// // python3 python/test/test.py p2pb2b BTC/USD

// const P2pb2b = require('./js/p2pb2b.js');
// // const p2pb2b = new P2pb2b({
// //     'apiKey': '47057813d00723654f33fb4c8c238f8c',
// //     'secret': '35feaf5226eb54f7c38d62b1acd2143f'
// // });
// const p2pb2b = new P2pb2b({
//     'apiKey': 'bdc6d1fb3b6eead57fe66baccbe92b90',
//     'secret': '303e321d5e2ca236b749aef036851de0'
// });

// // const promise = p2pb2b.fetchMarkets();
// // const promise = p2pb2b.fetchTicker('BTC/USD');
// // const promise = p2pb2b.fetchOpenOrders('KSH/BTC');
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'sell', 2, 0.0000513);
// var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'sell', 10, 0.0000435);
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // var promise = p2pb2b.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // const promise = p2pb2b.createOrder('ETH/BTC', 'limit', 'buy', 2, 0.00006000);
// // const promise = p2pb2b.createOrder('ETH/BTC', 'limit', 'sell', 2, 0.00016000);
// // const promise = p2pb2b.cancelOrder(123213, 'KSH/BTC');
// // const promise = p2pb2b.cancelOrder("1378511545", 'KSH/BTC');
// // const promise = p2pb2b.fetchOrder(123213, 'KSH/BTC');
// // const promise = p2pb2b.fetchOrderBook('KSH/BTC', limit=90);
// // const promise = p2pb2b.fetchL2OrderBook('KSH/BTC', undefined, {'side': 'sell'});
// // const promise = p2pb2b.fetchL2OrderBook('KSH/BTC', undefined, {'side': 'buy'});
// // const promise = p2pb2b.fetchBalance();


// promise.then(function(response) {
//     console.log(response);
//     p2pb2b.cancelOrder(response.id, 'KSH/BTC');
// });



//////////////////////////////////////
//          TESTE BKEX
//////////////////////////////////////


// // npm run build
// // node run-tests --js --python coinsbit
// // python3 python/test/test.py bkex BTC/USD

// const Bkex = require('./js/bkex.js');
// const bkex = new Bkex({
//     'apiKey': 'b1c4b596b9a7fa9edbe74c68b3fdc78cd421583eede8326cca7adfbe3a3737c4',
//     'secret': 'c0f513b1aee84d1e85dce35b0937c302fe72ec4ef25ea41fe20c892bc4d510ac'
// });

// // const promise = bkex.fetchMarkets();
// // const promise = bkex.fetchTicker('BTC/USDT');
// const promise = bkex.fetchOpenOrders('BTC/USDT');
// // var promise = bkex.createOrder('KSH/BTC', 'limit', 'sell', 2, 0.0000513);
// // var promise = bkex.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // var promise = bkex.createOrder('KSH/BTC', 'limit', 'buy', 2, 0.000001);
// // const promise = bkex.createOrder('ETH/BTC', 'limit', 'buy', 2, 0.00006000);
// // const promise = bkex.createOrder('ETH/BTC', 'limit', 'sell', 2, 0.00016000);
// // const promise = bkex.cancelOrder(123213, 'KSH/BTC');
// // const promise = bkex.cancelOrder("1378511545", 'KSH/BTC');
// // const promise = bkex.fetchOrder(123213, 'KSH/BTC');
// // const promise = bkex.fetchOrderBook('BTC/USDT');
// // const promise = bkex.fetchBalance();


// promise.then(function(response) {
//     console.log(response);
// });



//////////////////////////////////////
//          TESTE COINSBIT
//////////////////////////////////////

// // npm run build
// // node run-tests --js --python coinsbit
// // python3 python/test/test.py coinsbit BTC/USD

// const Coinsbit = require('./js/coinsbit.js');
// const coinsbit = new Coinsbit({
//     'apiKey': '510838c1177423883020301917d0477a',
//     'secret': 'c90d95a7529fd47745df3db5deb1e37b'
// });
// // const coinsbit = new Coinsbit({
// //     'apiKey': 'da523cfeff5c78561f9f5613a6fb8996',
// //     'secret': 'fd6a6c953a4ff572086669c4c2d0d3ec'
// // });

// // const promise = coinsbit.fetchMarkets();
// // const promise = coinsbit.fetchTicker('BTC/USD');
// // const promise = coinsbit.fetchOpenOrders('KSH/BTC');
// // const promise = coinsbit.createOrder('ETH/BTC', 'limit', 'buy', 2, 0.00006000);
// // const promise = coinsbit.createOrder('ETH/BTC', 'limit', 'buy', 2, 0.00006000);
// // const promise = coinsbit.createOrder('ETH/BTC', 'limit', 'sell', 2, 0.00016000);
// // const promise = coinsbit.cancelOrder(123213, 'ETH/BTC');
// // const promise = coinsbit.fetchOrder(123213, 'ETH/BTC');
// // const promise = coinsbit.fetchOrderBook('KSH/BTC');
// // const promise = coinsbit.fetchL2OrderBook('KSH/BTC', undefined, {'side': 'sell'});
// // const promise = coinsbit.fetchBalance();


// promise.then(function(response) {
//     console.log(response);
// });
