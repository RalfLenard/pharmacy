<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distribution Report - Remarks: {{ $remarks }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            color: #1f2937;
            margin: 20px;
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background-color: #f3f4f6;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .footer {
            margin-top: 40px;
            font-size: 12px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
        <h3>Lot Number / Batch: {{ $distributions->first()->inventory->lot_number ?? 'N/A' }}</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Brand Name</th>
                <th>Generic Name</th>
                <th>Lot Number</th>
                <th>Quantity</th>
                <th>Date Distributed</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($distributions as $index => $distribution)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $distribution->inventory->brand_name ?? 'N/A' }}</td>
                    <td>{{ $distribution->inventory->generic_name ?? 'N/A' }}</td>
                    <td>{{ $distribution->inventory->lot_number ?? 'N/A' }}</td>
                    <td>{{ $distribution->quantity }}</td>
                    <td>{{ \Carbon\Carbon::parse($distribution->created_at)->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Report generated on {{ now()->format('F j, Y g:i A') }}
    </div>
</body>
</html>
