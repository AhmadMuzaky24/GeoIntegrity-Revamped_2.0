<?php

namespace App\Http\Controllers;

use App\Models\GeoLocation;
use App\Models\User;
use App\Services\EvmAnchorStore;
use App\Services\GeoIntegrity;
use App\Services\GeoLocationHasher;
use App\Support\Actor;
use Illuminate\Http\Request;

class GeoLocationController extends Controller
{
    public function index(GeoIntegrity $integrity)
    {
        $locations = GeoLocation::latest()->get();

        $status = $locations->mapWithKeys(fn (GeoLocation $location) => [
            $location->id => match (true) {
                $integrity->latest($location) === null => 'unregistered',
                $integrity->intact($location) => 'valid',
                default => 'tampered',
            },
        ]);

        $actors = User::orderBy('id')->get();

        return view('locations.index', compact('locations', 'status', 'actors'));
    }

    public function store(
        Request $request,
        GeoLocationHasher $hasher,
        EvmAnchorStore $store
    ) {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $location = GeoLocation::create($validated + [
            'created_by' => Actor::name(),
            'updated_by' => Actor::name(),
        ]);

        $location->update([
            'data_hash' => $hasher->hash($location),
        ]);

        $store->anchor($location->id, $location->data_hash, Actor::name());

        return redirect()
            ->route('locations.index')
            ->with('success', 'Lokasi ditambahkan dan credential di-anchor ke '.$store->driver().'.');
    }

    public function show(GeoLocation $geoLocation, GeoIntegrity $integrity)
    {
        $anchor = $integrity->latest($geoLocation);

        // Compare against the hash the chain anchored, never the mutable column alone.
        $hashMatches = $anchor === null ? null : $integrity->intact($geoLocation, $anchor);

        $actors = User::orderBy('id')->get();

        return view('locations.show', compact('geoLocation', 'anchor', 'hashMatches', 'actors'));
    }

    public function edit(GeoLocation $geoLocation)
    {
        return view('locations.edit', compact('geoLocation'));
    }

    public function update(
        Request $request,
        GeoLocation $geoLocation,
        GeoIntegrity $integrity
    ) {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // No re-anchoring anywhere: once the anchored data is edited, the
        // credential stays invalid for good. Editing is still allowed so the
        // change is visible and attributable, not hidden behind a new block.
        $geoLocation->update($validated + ['updated_by' => Actor::name()]);

        $intact = $integrity->intact($geoLocation->fresh());

        return redirect()
            ->route('locations.show', $geoLocation->id)
            ->with('success', $intact
                ? 'Lokasi diperbarui tanpa perubahan data terkait hash.'
                : 'Lokasi diperbarui. Credential sekarang TIDAK VALID PERMANEN — data berubah setelah anchor dan tidak bisa di-anchor ulang.');
    }

    public function destroy(GeoLocation $geoLocation)
    {
        $geoLocation->delete();

        return redirect()
            ->route('locations.index')
            ->with('success', 'Lokasi berhasil dihapus.');
    }
}
