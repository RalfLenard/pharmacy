<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distribution Report - Remarks: {{ $remarks ?? 'All' }}</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #1f2937;
            margin: 20px 30px;
            line-height: 1.5;
        }

        h1 {
            text-align: center;
            margin-bottom: 15px;
            font-weight: 700;
            font-size: 22px;
            color: #2d3748;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 13px;
            color: #4a5568;
        }

        .summary > div p {
            margin: 4px 0;
        }

        .summary strong {
            color: #2d3748;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 45px;
            font-size: 11px;
            color: #2d3748;
        }

        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #edf2f7;
            font-weight: 600;
            color: #4a5568;
            user-select: none;
        }

        tr:nth-child(even) {
            background-color: #f7fafc;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            font-size: 13px;
            color: #2d3748;
        }

        .signature-block {
            width: 48%;
            border-top: 1.5px solid #cbd5e0;
            padding-top: 20px;
            text-align: center;
        }

        .signature-block p {
            margin: 0;
        }

        .prepared-name, .noted-name {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 8px;
            margin-bottom: 2px;
            font-size: 14px;
            user-select: text;
        }

        .prepared-position, .noted-position {
            font-style: italic;
            color: #718096;
            font-size: 12px;
            user-select: text;
        }
    </style>
</head>
<body>
    <h1>Distribution Report</h1>

    <div class="summary">
        <div>
            <p><strong>As of:</strong> {{ now()->format('F j, Y') }}</p>
            <p><strong>Remarks:</strong> {{ $remarks ?? 'All' }}</p>
            <p><strong>Stock Type:</strong> {{ $stock_type ?? 'All' }}</p>
        </div>

        <div>
            <p><strong>Reference No.:</strong> RHUI-{{ $report_id }}</p>
        </div>
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

    <div class="signatures">
        <!-- Prepared By -->
        <div class="signature-block" style="text-align: left;">
            <p>Prepared by:</p>
            <p class="prepared-name">
                {{ request('prepared_by') ?: '________________________' }}
            </p>
            <p class="prepared-position">PA/Encoder</p>
        </div>

        <!-- Noted By -->
        <div class="signature-block" style="text-align: right;">
            <p>Noted by:</p>
            <p class="noted-name">Diana Cunanan</p>
            <p class="noted-position">Pharmacist</p>
        </div>
    </div>
    
</body>
</html>
