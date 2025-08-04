<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Inventory Report' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
            font-size: 12px;
            line-height: 1.4;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background-color: #f2f2f2;
        }

        .summary {
            margin-top: 25px;
            font-weight: bold;
        }

        /* Container for signatures side by side */
        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-size: 12px;
        }

        .signature-block {
            width: 45%;
            text-align: center; /* Center text inside each block */
        }

        .prepared-name, .noted-name {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 0;
        }

        .prepared-position, .noted-position {
            font-style: italic;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Inventory Report' }}</h1>
    <h5>As of {{ now()->format('F j, Y') }}</h5>

    @if ($inventories->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date In</th>
                    <th>Qty Delivered</th>
                    <th>Generic Name</th>
                    <th>Brand Name</th>
                    <th>Units</th>
                    <th>Lot Number</th>
                    <th>Expiration Date</th>
                    <th>SOH</th>
                    <th>Source</th>
                   
                </tr>
            </thead>
            <tbody>
                @php $i = 1; @endphp
                @foreach ($inventories->sortBy(fn($item) => strtolower($item->generic_name ?? '')) as $item)
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->date_in)->format('M d, Y') }}</td>
                        <td>{{ $item->quantity }}</td>
                      
                        <td>{{ $item->generic_name }}</td>
                        <td>{{ $item->brand_name }}</td>
                        <td>{{ $item->utils }}</td>
                        <td>{{ $item->lot_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->expiration_date)->format('M d, Y') }}</td>
                       
                        <td>{{ $item->stocks }}</td>
                        <td>{{ $item->stock_type ?? 'N/A' }}</td>
                       
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="summary">Total Records: {{ $inventories->count() }}</p>
    @else
        <p>No inventory records found for the selected filters.</p>
    @endif

    <div class="signatures">
        <!-- Prepared By -->
        <div class="signature-block">
            <p>Prepared by:</p>
            <p class="prepared-name">
                {{ request('prepared_by') ?: '________________________' }}
            </p>
            <p class="prepared-position">PA/Encoder</p>
        </div>

        <!-- Noted By -->
        <div class="signature-block">
            <p>Noted by:</p>
            <p class="noted-name">Diana Cunanan</p>
            <p class="noted-position">Pharmacist</p>
        </div>
    </div>
</body>
</html>
