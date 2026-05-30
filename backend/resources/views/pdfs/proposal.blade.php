<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proposal - {{ $proposal->title }}</title>
    @php($company = $proposal->company ?? $proposal->user->company)
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .logo {
            max-width: 96px;
            max-height: 96px;
        }
        .details {
            margin-bottom: 30px;
        }
        .details table {
            width: 100%;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
        }
        .items th, .items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items th {
            background-color: #f4f4f4;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    @if($company?->logo_url)
                        <img src="{{ $company->logo_url }}" alt="Company logo" class="logo">
                    @endif
                </td>
                <td style="text-align: right;">
                    <h1 style="margin: 0;">PROPOSAL</h1>
                    <h2 style="margin: 6px 0 0;">{{ $proposal->title }}</h2>
                    <p style="margin: 8px 0 0;">
                        <strong>{{ $company?->name ?? config('app.name') }}</strong>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="details">
        <table>
            <tr>
                <td>
                    <strong>Prepared By:</strong><br>
                    {{ $company?->name ?? config('app.name') }}<br>
                    @if($company?->email)
                        {{ $company->email }}<br>
                    @endif
                    @if($company?->phone)
                        {{ $company->phone }}<br>
                    @endif
                    @if($company?->website)
                        {{ $company->website }}<br>
                    @endif
                    @if($company?->address)
                        {{ $company->address }}<br>
                    @endif
                    @if($company?->tax_id)
                        Tax ID: {{ $company->tax_id }}<br>
                    @endif
                </td>
                <td style="text-align: right;">
                    <strong>Prepared For:</strong><br>
                    {{ $proposal->client->name }}<br>
                    {{ $proposal->client->email }}
                </td>
            </tr>
        </table>
        <br>
        <p><strong>Issue Date:</strong> {{ $proposal->issue_date }}</p>
        @if($proposal->expiry_date)
            <p><strong>Expiry Date:</strong> {{ $proposal->expiry_date }}</p>
        @endif
    </div>

    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proposal->items as $item)
                <tr>
                    <td>{{ $item->name }}<br><small>{{ $item->description }}</small></td>
                    <td>{{ $item->quantity }}</td>
                    <td>₦{{ number_format($item->unit_price, 2) }}</td>
                    <td>₦{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <p><strong>Subtotal:</strong> ₦{{ number_format($proposal->subtotal, 2) }}</p>
        <p><strong>Tax:</strong> ₦{{ number_format($proposal->tax, 2) }}</p>
        <h3><strong>Total:</strong> ₦{{ number_format($proposal->total, 2) }}</h3>
    </div>

    @if($proposal->notes)
    <div style="margin-top: 40px;">
        <h4>Notes:</h4>
        <p>{{ $proposal->notes }}</p>
    </div>
    @endif
</body>
</html>
