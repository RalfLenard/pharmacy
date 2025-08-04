<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Distribution Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5px;
        }
        h1, h3 {
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
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9rem;
        }
        .filters {
            margin-top: 20px;
            text-align: left;
        }
        .filters .filter {
            margin-bottom: 8px;
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

    <!-- Table of recipient distribution records -->
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
                <th>Date Given</th> <!-- New column for Date Given -->
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
                    <td>
                        @if ($inventory)
                            {{ $inventory->lot_number }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $record->quantity ?? 'N/A' }}</td>
                    <td>{{ $record->date_given ? \Carbon\Carbon::parse($record->date_given)->format('F j, Y') : 'N/A' }}</td> <!-- Displaying the date_given -->
                </tr>
            @endforeach
        </tbody>
    </table>

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

    <!-- Footer -->
    <div class="footer">
        <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

</body>
</html>
