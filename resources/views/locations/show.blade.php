
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lokasi - GeoIntegrity</title>

    <style>
        * {
            box-sizing: border-box;
        }

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
            position: sticky;
            top: 0;
            z-index: 9999;
        }

        header h1 {
            margin: 0;
            font-size: 23px;
        }

        header p {
            margin: 7px 0 0;
            color: #cbd5e1;
            font-size: 14px;
        }

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

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-item {
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
        }

        .detail-item.full {
            grid-column: 1 / -1;
        }

        .detail-label {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .detail-value {
            font-size: 15px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .action-btn {
            display: inline-block;
            padding: 7px 13px;
            margin: 2px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s ease;
            font-family: inherit;
        }

        .detail-btn {
            background: #2563eb;
            color: white;
        }

        .detail-btn:hover {
            background: #1d4ed8;
        }

        .edit-btn {
            background: #f59e0b;
            color: white;
        }

        .edit-btn:hover {
            background: #d97706;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
        }

        .delete-btn:hover {
            background: #b91c1c;
        }

        .back-btn {
            background: #e5e7eb;
            color: #1f2937;
        }

        .back-btn:hover {
            background: #d1d5db;
        }

        .action-group {
            margin-top: 20px;
        }

        .badge {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .ok { background: #dcfce7; color: #166534; }
        .bad { background: #fee2e2; color: #991b1b; }

        .mono {
            font-family: Consolas, monospace;
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .success {
            background: #dcfce7;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            color: #166534;
        }




        @media (max-width: 650px) {
            header {
                padding: 16px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-item.full {
                grid-column: auto;
            }

            .panel {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>GeoIntegrity</h1>
    <p>Geospatial Data Integrity Verification System</p>
    @include('partials.actor-switcher')
</header>

<div class="container">

    <div class="panel">
        <h2>Detail Data Lokasi</h2>
        <p>Informasi lengkap data geospasial yang terdaftar.</p>

        <div class="detail-grid">

            <div class="detail-item full">
                <div class="detail-label">Nama Lokasi</div>
                <div class="detail-value">
                    {{ $geoLocation->name }}
                </div>
            </div>

            <div class="detail-item full">
                <div class="detail-label">Deskripsi</div>
                <div class="detail-value">
                    {{ $geoLocation->description ?? '-' }}
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Latitude</div>
                <div class="detail-value">
                    {{ $geoLocation->latitude }}
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Longitude</div>
                <div class="detail-value">
                    {{ $geoLocation->longitude }}
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Tanggal Dibuat</div>
                <div class="detail-value">
                    {{ $geoLocation->created_at }}
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Terakhir Diperbarui</div>
                <div class="detail-value">
                    {{ $geoLocation->updated_at }}
                </div>
            </div>

        </div>

        <div class="action-group">

            <a href="{{ route('locations.index') }}"
               class="action-btn back-btn">
                Kembali
            </a>

            <a href="{{ route('locations.edit', $geoLocation->id) }}"
               class="action-btn edit-btn">
                Edit Lokasi
            </a>

            <form action="{{ route('locations.destroy', $geoLocation->id) }}"
                  method="POST"
                  style="display: inline;"
                  onsubmit="return confirm('Yakin ingin menghapus lokasi ini?')">

                @csrf
                @method('DELETE')

                <button type="submit" class="action-btn delete-btn">
                    Hapus Lokasi
                </button>
            </form>

        </div>
    </div>

    <div class="panel">
        <h2>Credential Blockchain</h2>
        <p>Bukti kriptografis data lokasi ini di rantai blok.</p>

        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <div class="detail-grid">

            <div class="detail-item full">
                <div class="detail-label">Status Data</div>
                <div class="detail-value">
                    @if ($hashMatches === null)
                        <span class="badge bad">Belum terdaftar di blockchain</span>
                    @elseif ($hashMatches)
                        <span class="badge ok">Hash cocok — data tidak diubah</span>
                    @else
                        <span class="badge bad">Hash tidak valid — data diubah setelah anchor</span>
                    @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Dibuat oleh</div>
                <div class="detail-value">{{ $geoLocation->created_by ?? '-' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Terakhir diubah oleh</div>
                <div class="detail-value">{{ $geoLocation->updated_by ?? '-' }}</div>
            </div>

            @if ($geoLocation->created_by && $geoLocation->updated_by && $geoLocation->updated_by !== $geoLocation->created_by)
                <div class="detail-item full">
                    <div class="detail-label">Kepemilikan</div>
                    <div class="detail-value">
                        <span class="badge bad">
                            Diubah oleh pihak lain — pembuat {{ $geoLocation->created_by }},
                            pengubah terakhir {{ $geoLocation->updated_by }}
                        </span>
                    </div>
                </div>
            @endif

            <div class="detail-item full">
                <div class="detail-label">Data Hash (SHA-256)</div>
                <div class="detail-value mono">{{ $geoLocation->data_hash ?? '-' }}</div>
            </div>

            @if ($anchor)
                <div class="detail-item">
                    <div class="detail-label">Anchor Index</div>
                    <div class="detail-value">{{ $anchor['index'] }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Di-anchor oleh</div>
                    <div class="detail-value">{{ $anchor['actor'] ?? '-' }}</div>
                </div>

                <div class="detail-item full">
                    <div class="detail-label">Data Hash di Blockchain (anchor)</div>
                    <div class="detail-value mono">{{ $anchor['data_hash'] }}</div>
                </div>

                @if ($anchor['tx'])
                    <div class="detail-item full">
                        <div class="detail-label">Tx / Block Hash</div>
                        <div class="detail-value mono">{{ $anchor['tx'] }}</div>
                    </div>
                @endif

                <div class="detail-item full">
                    <div class="detail-label">Waktu Anchor</div>
                    <div class="detail-value">
                        {{ date('Y-m-d H:i:s', $anchor['timestamp']) }}
                    </div>
                </div>
            @else
                <div class="detail-item full">
                    <div class="detail-label">Anchor</div>
                    <div class="detail-value">Belum ada credential di blockchain.</div>
                </div>
            @endif

        </div>

        @if ($hashMatches === false)
            <p style="margin-top: 20px; color: #991b1b; font-weight: bold;">
                Credential tidak dapat di-anchor ulang. Data sudah menyimpang dari anchor #{{ $anchor['index'] }}.
            </p>
        @endif
    </div>

</div>

</body>
</html>
