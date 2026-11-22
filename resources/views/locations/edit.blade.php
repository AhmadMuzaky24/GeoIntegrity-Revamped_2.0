
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lokasi - GeoIntegrity</title>

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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-top: 6px;
            font-family: inherit;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #2563eb;
        }

        .action-btn {
            display: inline-block;
            padding: 12px 18px;
            margin: 2px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s ease;
            font-family: inherit;
        }

        .save-btn {
            background: #2563eb;
            color: white;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        .back-btn {
            background: #e5e7eb;
            color: #1f2937;
        }

        .back-btn:hover {
            background: #d1d5db;
        }

        .error {
            color: #dc2626;
            font-size: 13px;
            margin-bottom: 15px;
        }

        @media (max-width: 650px) {
            header {
                padding: 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
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
</header>

<div class="container">

    <div class="panel">

        <h2>Edit Data Lokasi</h2>
        <p>Perbarui informasi dan koordinat lokasi geospasial.</p>

        @if ($errors->any())
            <div class="error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('locations.update', $geoLocation->id) }}"
              method="POST">

            @csrf
            @method('PUT')

            <div class="form-grid">

                <div class="full">
                    <label>Nama Lokasi</label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $geoLocation->name) }}"
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
                    >{{ old('description', $geoLocation->description) }}</textarea>
                </div>

                <div>
                    <label>Latitude</label>

                    <input
                        type="number"
                        name="latitude"
                        step="any"
                        min="-90"
                        max="90"
                        value="{{ old('latitude', $geoLocation->latitude) }}"
                        required
                    >
                </div>

                <div>
                    <label>Longitude</label>

                    <input
                        type="number"
                        name="longitude"
                        step="any"
                        min="-180"
                        max="180"
                        value="{{ old('longitude', $geoLocation->longitude) }}"
                        required
                    >
                </div>

                <div class="full">
                    <button type="submit" class="action-btn save-btn">
                        Simpan Perubahan
                    </button>

                    <a href="{{ route('locations.show', $geoLocation->id) }}"
                       class="action-btn back-btn">
                        Batal
                    </a>
                </div>

            </div>

        </form>

    </div>

</div>

</body>
</html>
