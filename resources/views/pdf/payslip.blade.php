<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $month }}/{{ $year }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
        }

        .header h3 {
            font-size: 16px;
            margin: 0;
            color: gray;
        }

        .section {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f4f4f4;
        }

        .totals {
            text-align: right;
            margin-top: 20px;
        }

        .totals p {
            font-size: 16px;
            margin: 5px 0;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 12px;
            color: gray;
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Payslip Header -->
        <div class="header">
            <h1>Payslip for {{ $month }}/{{ $year }}</h1>
            <h3>{{ ucfirst($company_data['company_name'] ?? '') }}</h3>
        </div>

        @if ($employee_data != null)
            <div class="section">
                <h4>Employee Information</h4>
                <p><strong>Name:</strong>
                    {{ ucfirst($employee_data['first_name']) . ' ' . ucfirst($employee_data['last_name']) }}</p>
                <p><strong>Employee ID:</strong> {{ $employee_data['employee_id'] }}</p>
            </div>
        @endif

        <!-- Bank and Payment Info -->
        <div class="section">
            <h4>Bank Information</h4>
            <p><strong>Bank Name:</strong> {{ $bank_name }}</p>
            <p><strong>Account Number:</strong> {{ $bank_account }}</p>
        </div>

        <!-- Earnings Table -->
        <div class="section">
            <h4>Earnings</h4>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($earnings as $earning)
                        <tr>
                            <td>{{ $earning['name'] }}</td>
                            <td>{{ number_format($earning['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No earnings recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Deductions Table -->
        <div class="section">
            <h4>Deductions</h4>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deductions as $deduction)
                        <tr>
                            <td>{{ $deduction['name'] }}</td>
                            <td>{{ number_format($deduction['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No deductions recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="totals">
            <p><strong>Gross Pay:</strong> {{ number_format($gross_pay, 2) }}</p>
            <p><strong>Net Pay:</strong> {{ number_format($net_pay, 2) }}</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a system-generated payslip. If you have any questions, please contact HR.</p>
        </div>
    </div>

</body>

</html>
