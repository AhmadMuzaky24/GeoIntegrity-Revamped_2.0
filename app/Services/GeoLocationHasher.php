<?php

namespace App\Services;

use App\Models\GeoLocation;

class GeoLocationHasher
{
    public function hash(GeoLocation $location): string
    {
        $data = $location->getAttributes();

        unset($data['data_hash'], $data['updated_at']);

        // ponytail: only the decimal columns need formatting; add here if more appear.
        $data['latitude'] = number_format((float) $data['latitude'], 7, '.', '');
        $data['longitude'] = number_format((float) $data['longitude'], 7, '.', '');

        foreach ($data as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        ksort($data);

        return hash(
            'sha256',
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }
}
