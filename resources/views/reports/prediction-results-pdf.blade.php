<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { 
            margin: 10mm;
            size: A4 landscape;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .confidence-high { color: #28a745; font-weight: bold; }
        .confidence-medium { color: #ffc107; font-weight: bold; }
        .confidence-low { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generated: {{ $date }} | Total Records: {{ $totalRecords }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Crop</th>
                <th>Market</th>
                <th>Region</th>
                <th>District</th>
                <th>Price</th>
                <th>Confidence</th>
                <th>Model</th>
                <th>Prediction Date</th>
                <th>Target Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($predictionResults as $result)
                <tr>
                    <td>{{ $result->id }}</td>
                    <td>{{ $result->crop_name }}</td>
                    <td>{{ $result->market }}</td>
                    <td>{{ $result->admin1 }}</td>
                    <td>{{ $result->admin2 }}</td>
                    <td>${{ number_format($result->predicted_price, 2) }}</td>
                    <td class="
                        @if($result->confidence_score >= 80) confidence-high 
                        @elseif($result->confidence_score >= 60) confidence-medium 
                        @else confidence-low 
                        @endif
                    ">
                        {{ $result->confidence_score }}%
                    </td>
                    <td>{{ $result->model_used }}</td>
                    <td>{{ \Carbon\Carbon::parse($result->prediction_date)->format('Y-m-d H:i') }}</td>

                    <td>{{ \Carbon\Carbon::parse($result->target_date)->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>