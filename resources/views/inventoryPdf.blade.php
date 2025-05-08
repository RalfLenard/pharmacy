<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Report - Lot {{ $lot_number }}</title>
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

        .meta {
            margin-bottom: 20px;
        }

        .meta p {
            margin: 0;
            font-size: 13px;
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
    <h3>Lot Number / Batch: {{ $inventories->first()->lot_number }}</h3>

    <table>
        <thead>
            <tr>
                <th>#</th>
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
</body>
</html>
