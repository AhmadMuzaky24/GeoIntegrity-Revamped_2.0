<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain Explorer - GeoIntegrity</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }

        header {
            background: #172554;
            color: white;
            padding: 20px 30px;
        }

        header h1 { margin: 0; font-size: 23px; }
        header p { margin: 7px 0 0; color: #cbd5e1; font-size: 14px; }

        .container {
            width: 100%;
            max-width: 1600px;
            margin: 25px auto;
            padding: 0 clamp(12px, 2vw, 24px);
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
        }

        .ok { background: #dcfce7; color: #166534; }
        .bad { background: #fee2e2; color: #991b1b; }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 13px;
        }

        th { background: #f9fafb; }

        .mono {
            font-family: Consolas, monospace;
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            background: #e5e7eb;
            color: #1f2937;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>

<body>

<header>
    <h1>GeoIntegrity</h1>
    <p>Blockchain Explorer — credential point map</p>
</header>

<div class="container">

    <div class="panel">
        <h2>Status Rantai</h2>

        @if ($status['valid'])
            <span class="badge ok">Chain valid — {{ $status['blocks'] }} anchor</span>
        @else
            <span class="badge bad">
                {{ $status['error'] ?? 'Chain rusak di ketinggian '.($status['broken_at'] ?? '?') }}
            </span>
        @endif

        <p>Driver: <code>{{ $status['driver'] }}</code></p>

        <p>
            Verifikasi mesin: <code>/chain/verify</code>
        </p>

        <a href="{{ route('locations.index') }}" class="back-btn">Kembali ke Peta</a>
    </div>

    <div class="panel">
        <h2>Anchor Terbaru</h2>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Lokasi</th>
                    <th>Oleh</th>
                    <th>Data Hash</th>
                    <th>Waktu</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($anchors as $anchor)
                    <tr>
                        <td>{{ $anchor['index'] }}</td>
                        <td>
                            @if ($anchor['location_id'] && isset($locations[$anchor['location_id']]))
                                <a href="{{ route('locations.show', $anchor['location_id']) }}">
                                    {{ $locations[$anchor['location_id']]->name }}
                                </a>
                            @else
                                <em>dihapus</em>
                            @endif
                        </td>
                        <td>{{ $anchor['actor'] ?? '-' }}</td>
                        <td class="mono">{{ $anchor['data_hash'] }}</td>
                        <td>{{ date('Y-m-d H:i:s', $anchor['timestamp']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada anchor. Tambahkan lokasi untuk membuat credential.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
