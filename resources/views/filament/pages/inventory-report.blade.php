<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #f3f4f6;
            color: #333;
            font-weight: bold;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        td {
            padding: 8px 10px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f3f4f6;
            border-radius: 5px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 30px;
        }
        .summary-label {
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inventory Report</h1>
        <p>Generated on: {{ $generatedAt }}</p>
        <p>Total Items: {{ $totalItems }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Shelf</th>
                <th>Product Name</th>
                <th>Variant</th>
                <th>Ethos ID</th>
                <th class="text-right">Quantity</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQuantity = 0;
            @endphp
            @foreach($items as $item)
                @php
                    $totalQuantity += (int)($item['quantity'] ?? 0);
                @endphp
                <tr>
                    <td>{{ $item['shelf_name'] ?? '—' }}</td>
                    <td>{{ $item['product_name'] ?? '—' }}</td>
                    <td>{{ $item['variant_name'] ?? '—' }}</td>
                    <td>{{ $item['ethos_id'] ?? '—' }}</td>
                    <td class="text-right">{{ number_format($item['quantity'] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #e5e7eb; font-weight: bold;">
                <td colspan="4" class="text-right">Total Quantity:</td>
                <td class="text-right">{{ number_format($totalQuantity) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>This report was generated automatically by the Inventory Management System</p>
    </div>
</body>
</html>

