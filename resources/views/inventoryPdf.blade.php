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
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Inventory Report' }}</h1>

    @if ($inventories->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Lot Number</th>
                    <th>Brand Name</th>
                    <th>Generic Name</th>
                    <th>Quantity</th>
                    <th>Stocks</th>
                    <th>Units</th>
                    <th>Date In</th>
                    <th>Expiration Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($inventories as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->lot_number }}</td>
                        <td>{{ $item->brand_name }}</td>
                        <td>{{ $item->generic_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->stocks }}</td>
                        <td>{{ $item->utils }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->date_in)->format('M d, Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->expiration_date)->format('M d, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="summary">Total Records: {{ $inventories->count() }}</p>
    @else
        <p>No inventory records found for the selected filters.</p>
    @endif
</body>
</html>
