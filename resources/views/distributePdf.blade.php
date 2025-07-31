<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distribution Report - Remarks: {{ $remarks ?? 'All' }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            color: #1f2937;
            margin: 20px;
        }

        h1, h3 {
            margin-bottom: 10px;
        }

        .summary {
            margin-bottom: 20px;
            border: 1px solid #d1d5db;
            padding: 10px;
            background-color: #f3f4f6;
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
            background-color: #e5e7eb;
            font-weight: bold;
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
    <h1>Distribution Report</h1>

    <div class="summary">
        <p><strong>Remarks:</strong> {{ $remarks ?? 'All' }}</p>
        <p><strong>Stock Type:</strong> {{ $stock_type ?? 'All' }}</p>
        <p><strong>Month:</strong> {{ $month ? \Carbon\Carbon::create()->month($month)->format('F') : 'All' }}</p>
        <p><strong>Year:</strong> {{ $year ?? 'All' }}</p>
        <p><strong>As of:</strong> {{ now()->format('F j, Y') }}</p>
        Report ID: {{ $report_id }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Generic Name</th>
                <th>Brand Name</th>
                <th>Stock Type</th>
                <th>Units</th>
                <th>Lot Number</th>
                <th>Expiration Date</th>
                <th>Quantity</th>
                <th>Date Distributed</th>
                <th>Reasons</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @foreach ($distributions->sortBy(fn($d) => strtolower($d->inventory->generic_name ?? '')) as $distribution)
                <tr>
                    <td>{{ $i++ }}</td>
                    <td>{{ $distribution->inventory->generic_name ?? 'N/A' }}</td>
                    <td>{{ $distribution->inventory->brand_name ?? 'N/A' }}</td>
                    <td>{{ $distribution->inventory->stock_type ?? 'N/A' }}</td>
                    <td>{{ $distribution->inventory->utils ?? 'N/A' }}</td>
                    <td>{{ $distribution->inventory->lot_number ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($distribution->inventory->expiration_date)->format('M d, Y') }}</td>
                    <td>{{ $distribution->quantity }}</td>
                    <td>{{ \Carbon\Carbon::parse($distribution->date_distribute)->format('Y-m-d') }}</td>
                    <td>{{ $distribution->reason ?? 'N/A' }}</td>
                   
                </tr>
            @endforeach
        </tbody>
    </table>
    
</body>
</html>
