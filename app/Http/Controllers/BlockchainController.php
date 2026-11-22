<?php

namespace App\Http\Controllers;

use App\Models\GeoLocation;
use App\Services\EvmAnchorStore;
use App\Services\GeoIntegrity;

class BlockchainController extends Controller
{
    public function index(EvmAnchorStore $store)
    {
        $anchors = $store->all();
        $status = $store->status();

        $locations = GeoLocation::whereIn('id', array_filter(array_column($anchors, 'location_id')))
            ->get()
            ->keyBy('id');

        return view('blockchain.index', compact('anchors', 'status', 'locations'));
    }

    public function verify(EvmAnchorStore $store, GeoIntegrity $integrity)
    {
        return response()->json($store->status() + ['data' => $integrity->audit()]);
    }

    /** Membership proof: is this map point anchored in the chain, and is its data untouched? */
    public function proof(GeoLocation $geoLocation, EvmAnchorStore $store, GeoIntegrity $integrity)
    {
        $anchor = $store->latest($geoLocation->id);

        if ($anchor === null) {
            return response()->json([
                'registered' => false,
                'location_id' => $geoLocation->id,
                'driver' => $store->driver(),
            ], 404);
        }

        return response()->json([
            'registered' => true,
            'location_id' => $geoLocation->id,
            'driver' => $store->driver(),
            'index' => $anchor['index'],
            'tx' => $anchor['tx'],
            'data_hash' => $anchor['data_hash'],
            'anchored_at' => date('c', $anchor['timestamp']),
            'anchored_by' => $anchor['actor'],
            'created_by' => $geoLocation->created_by,
            'updated_by' => $geoLocation->updated_by,
            'changed_by_other' => $geoLocation->created_by !== null
                && $geoLocation->updated_by !== $geoLocation->created_by,
            'data_intact' => $integrity->intact($geoLocation, $anchor),
            'chain_valid' => $store->status()['valid'],
        ]);
    }
}
