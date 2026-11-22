<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kontrak GeoAnchor
    |--------------------------------------------------------------------------
    | Alamat kontrak + endpoint JSON-RPC node Hardhat. Lihat chain/README.md.
    */
    'rpc_url' => env('CHAIN_RPC_URL', 'http://127.0.0.1:8545'),

    'contract' => env('CHAIN_CONTRACT_ADDRESS'),

    'node_bin' => env('CHAIN_NODE_BIN', 'node'),

    'write_script' => base_path('chain/scripts/anchor.js'),

];
