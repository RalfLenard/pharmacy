<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recipient Distribution Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5px;
        }
        h1 {
            text-align: center;
            margin-bottom: 0;
        }
        .top-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .filters {
            font-size: 12px;
            margin-bottom: 10px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th, td {
            padding: 3px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
        }
        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-size: 12px;
        }
        .signature-block {
            width: 45%;
            text-align: center;
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
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="top-info">
        <div></div> <!-- Empty left for spacing -->
        <div>{{ now()->format('F j, Y ') }}</div>
    </div>

    <h1>Distribution Report</h1>

    <div class="filters">
        @php
            $start = $request->input('start_date');
            $end = $request->input('end_date');
            $exact = $request->input('date');
            $month = $request->input('month');
            $year = $request->input('year');
        @endphp

        @if ($start && $end)
            <p><strong>{{ \Carbon\Carbon::parse($start)->format('F j, Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($end)->format('F j, Y') }}</strong></p>
        @elseif ($exact)
            <p>Date: <strong>{{ \Carbon\Carbon::parse($exact)->format('F j, Y') }}</strong></p>
        @elseif ($month && $year)
            <p>Month: <strong>{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</strong></p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Recipient Name</th>
                <th>Age</th>
                <th>Barangay</th>
                <th>Gender</th>
                <th>Medicine</th>
                <th>Batch/Lot Number</th>
                <th>Qty</th>
                <th>Date Given</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                @php
                    $recipient = $record->recipient;
                    $distribution = $record->distribution;
                    $inventory = $distribution?->inventory;
                @endphp
                <tr>
                    <td>{{ $recipient->full_name ?? 'N/A' }}</td>
                    <td>{{ $recipient->birthdate ? \Carbon\Carbon::parse($recipient->birthdate)->age : 'N/A' }}</td>
                    <td>{{ $recipient->barangay ?? 'N/A' }}</td>
                    <td>{{ $recipient->gender ?? 'N/A' }}</td>
                    <td>
                        @if ($inventory)
                            {{ $inventory->brand_name }} / {{ $inventory->generic_name }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $inventory?->lot_number ?? 'N/A' }}</td>
                    <td>{{ $record->quantity ?? 'N/A' }}</td>
                    <td>{{ $record->date_given ? \Carbon\Carbon::parse($record->date_given)->format('F j, Y') : 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <!-- Prepared By -->
        <div class="signature-block">
            <p>Prepared by:</p>
            <p class="prepared-name">
                {{ $preparedBy ?: '________________________' }}
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
