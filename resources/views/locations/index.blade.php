
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GeoIntegrity</title>

    <!-- Leaflet CSS -->
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

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

        header a {
            color: #93c5fd;
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

        #map {
            height: 600px;
            width: 100%;
            border-radius: 8px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        input, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-top: 6px;
        }

        textarea {
            resize: vertical;
        }

        label {
            font-size: 14px;
            font-weight: bold;
        }

        .full {
            grid-column: 1 / -1;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .success {
            background: #dcfce7;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            color: #166534;
        }

        .error {
            color: #dc2626;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f9fafb;
        }

        @media (max-width: 650px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }

            #map {
                height: 320px;
            }
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

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-bad { background: #fee2e2; color: #991b1b; }

    </style>
</head>

<body>

<header>
    <h1>GeoIntegrity</h1>
    <p>Geospatial Data Integrity Verification System</p>
    <a href="{{ route('chain.index') }}">Blockchain Explorer</a>
    @include('partials.actor-switcher')
</header>

<div class="container">

    <!-- PETA -->
    <div class="panel">
        <h2>Peta Geospasial</h2>
        <p>Klik peta untuk menentukan koordinat lokasi.</p>

        <div id="map"></div>
    </div>

    <!-- FORM -->
    <div class="panel">
        <h2>Tambah Data Lokasi</h2>

        @if (session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('locations.store') }}" method="POST">

            @csrf

            <div class="form-grid">

                <div class="full">
                    <label>Nama Lokasi</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="Contoh: Puskesmas A"
                    >
                </div>

                <div class="full">
                    <label>Deskripsi</label>
                    <textarea
                        name="description"
                        rows="3"
                        placeholder="Deskripsi lokasi"
                    >{{ old('description') }}</textarea>
                </div>

                <div>
                    <label>Latitude</label>
                    <input
                        type="number"
                        id="latitude"
                        name="latitude"
                        step="any"
                        value="{{ old('latitude') }}"
                        required
                        readonly
                    >
                </div>

                <div>
                    <label>Longitude</label>
                    <input
                        type="number"
                        id="longitude"
                        name="longitude"
                        step="any"
                        value="{{ old('longitude') }}"
                        required
                        readonly
                    >
                </div>

                <div class="full">
                    <button type="submit">
                        Simpan Lokasi
                    </button>
                </div>

            </div>

        </form>
    </div>

    <!-- DAFTAR LOKASI -->
    <div class="panel">
        <h2>Data Lokasi Terdaftar</h2>

        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Pembuat</th>
                    <th>Blockchain</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($locations as $location)
                    <tr>
                        <td>{{ $location->name }}</td>
                        <td>{{ $location->latitude }}</td>
                        <td>{{ $location->longitude }}</td>
                        <td>
                            {{ $location->created_by ?? '-' }}

                            @if ($location->created_by && $location->updated_by && $location->updated_by !== $location->created_by)
                                <span class="badge badge-bad">diubah {{ $location->updated_by }}</span>
                            @endif
                        </td>
                        <td>
                            @switch($status[$location->id])
                                @case('valid')
                                    <span class="badge badge-ok">Valid</span>
                                    @break
                                @case('tampered')
                                    <span class="badge badge-bad">Hash tidak valid</span>
                                    @break
                                @default
                                    <span class="badge badge-bad">Belum terdaftar</span>
                            @endswitch
                        </td>
                        <td>
                            <a href="{{ route('locations.show', $location->id) }}"
                            class="action-btn detail-btn">
                                Detail
                            </a>

                            <a href="{{ route('locations.edit', $location->id) }}"
                            class="action-btn edit-btn">
                                Edit
                            </a>

                            <form action="{{ route('locations.destroy', $location->id) }}"
                                method="POST"
                                style="display: inline;"
                                onsubmit="return confirm('Yakin ingin menghapus lokasi ini?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="action-btn delete-btn">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            Belum ada data lokasi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

    // Inisialisasi peta
    const map = L.map('map').setView([-2.5, 118], 5);

    // Menggunakan OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let selectedMarker = null;

    // Memilih koordinat melalui klik peta
    map.on('click', function(e) {

        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        document.getElementById('latitude').value = lat.toFixed(7);
        document.getElementById('longitude').value = lng.toFixed(7);

        if (selectedMarker) {
            map.removeLayer(selectedMarker);
        }

        selectedMarker = L.marker([lat, lng]).addTo(map);

        selectedMarker.bindPopup(
            'Koordinat terpilih:<br>' +
            lat.toFixed(7) + ', ' + lng.toFixed(7)
        ).openPopup();

    });

    // Menampilkan lokasi yang sudah tersimpan
    @foreach ($locations as $location)

        L.marker([
            {{ $location->latitude }},
            {{ $location->longitude }}
        ])
        .addTo(map)
        .bindPopup(@js($location->name));

    @endforeach

</script>

</body>
</html>
