<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $receipt['receipt_number'] ?? 'GSU Receipt' }}</title>
    <style>
        @page {
            margin: 18mm 14mm 16mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.4;
        }

        .receipt-page {
            position: relative;
            border: 1.5px solid #0f172a;
            padding: 18px 18px 20px;
            overflow: hidden;
        }

        .watermark {
            position: absolute;
            top: 180px;
            left: 50%;
            width: 360px;
            margin-left: -180px;
            opacity: 0.06;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .header-table,
        .meta-table,
        .details-table,
        .amount-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-wrap {
            width: 84px;
        }

        .logo {
            width: 74px;
            height: 74px;
            object-fit: contain;
        }

        .title-block {
            text-align: center;
            padding: 0 8px;
        }

        .title-block h1 {
            margin: 0;
            font-size: 25px;
            font-weight: 700;
        }

        .title-block p {
            margin: 6px 0 0;
            font-size: 13px;
            font-weight: 700;
        }

        .title-block .receipt-title {
            margin-top: 10px;
            font-size: 18px;
            text-decoration: underline;
        }

        .verify-box {
            width: 175px;
            border: 1px solid #94a3b8;
            padding: 10px 12px;
            background: #f8fafc;
        }

        .verify-box .label {
            margin: 0 0 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
        }

        .verify-box .value {
            margin: 0 0 10px;
            font-size: 13px;
            font-weight: 700;
            word-break: break-word;
        }

        .section-line {
            margin: 16px 0 12px;
            border-top: 2px solid #14532d;
        }

        .reference-line {
            margin: 6px 0 14px;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
        }

        .reference-line span {
            color: #991b1b;
        }

        .details-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px 0 0;
        }

        .detail-row {
            margin-bottom: 7px;
        }

        .detail-label {
            display: inline-block;
            width: 128px;
            font-weight: 700;
        }

        .amount-table {
            margin-top: 16px;
            border: 1.5px solid #111827;
        }

        .amount-table th,
        .amount-table td {
            border: 1px solid #111827;
            padding: 8px 10px;
        }

        .amount-table th {
            background: #f1f5f9;
            text-align: left;
            font-size: 12px;
        }

        .amount-table td:last-child,
        .amount-table th:last-child {
            text-align: right;
            width: 32%;
        }

        .amount-table .total-row td {
            background: #111827;
            color: #ffffff;
            font-weight: 700;
        }

        .note-box {
            margin-top: 16px;
            border: 1px solid #86efac;
            background: #f0fdf4;
            padding: 12px 14px;
        }

        .note-box p {
            margin: 0 0 6px;
        }

        .note-title {
            font-weight: 700;
            color: #14532d;
        }

        .footer-table {
            margin-top: 22px;
            font-size: 10px;
            color: #475569;
        }

        .footer-table td:last-child {
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $currency = static fn ($amount) => 'NGN '.number_format((float) $amount, 2);
@endphp

<div class="receipt-page">
    @if (! empty($logoDataUri))
        <img src="{{ $logoDataUri }}" alt="GSU watermark" class="watermark">
    @endif

    <div class="content">
        <table class="header-table">
            <tr>
                <td class="logo-wrap">
                    @if (! empty($logoDataUri))
                        <img src="{{ $logoDataUri }}" alt="GSU logo" class="logo">
                    @endif
                </td>
                <td class="title-block">
                    <h1>GOMBE STATE UNIVERSITY ALUMNI ASSOCIATION</h1>
                    <p>TUDUN WADA GOMBE, GOMBE STATE</p>
                    <p>{{ $receipt['graduation_session'] ?? 'Academic Session' }} Academic Session</p>
                    <p class="receipt-title">Member's Receipt</p>
                </td>
                <td>
                    <div class="verify-box">
                        <p class="label">Receipt Number</p>
                        <p class="value">{{ $receipt['receipt_number'] ?? 'N/A' }}</p>

                        <p class="label">Status</p>
                        <p class="value">{{ $receipt['payment_status_label'] ?? 'Verified' }}</p>

                        <p class="label">Issued At</p>
                        <p class="value">{{ $receipt['issued_at'] ? \Carbon\Carbon::parse($receipt['issued_at'])->format('d/m/Y h:i A') : 'Not recorded' }}</p>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-line"></div>

        <div class="reference-line">
            Payment Reference:
            <span>{{ $receipt['payment_reference'] ?? $receipt['payment_request_public_reference'] ?? 'N/A' }}</span>
        </div>

        <table class="details-table">
            <tr>
                <td>
                    <div class="detail-row"><span class="detail-label">Name:</span> {{ $receipt['full_name'] ?? 'N/A' }}</div>
                    <div class="detail-row"><span class="detail-label">Matric Number:</span> {{ $receipt['matric_number'] ?? 'N/A' }}</div>
                    <div class="detail-row"><span class="detail-label">Faculty:</span> {{ $receipt['faculty'] ?? 'N/A' }}</div>
                    <div class="detail-row"><span class="detail-label">Department:</span> {{ $receipt['department'] ?? 'N/A' }}</div>
                    <div class="detail-row"><span class="detail-label">Programme:</span> {{ $receipt['program_type_name'] ?? 'Not recorded' }}</div>
                    <div class="detail-row"><span class="detail-label">Phone No.:</span> {{ $receipt['phone_number'] ?? 'N/A' }}</div>
                </td>
                <td>
                    <div class="detail-row"><span class="detail-label">Email:</span> {{ $receipt['email'] ?? 'N/A' }}</div>
                    <div class="detail-row"><span class="detail-label">Session:</span> {{ $receipt['graduation_session'] ?? 'N/A' }}</div>
                    <div class="detail-row"><span class="detail-label">Payment Type:</span> {{ $receipt['payment_type_name'] ?? 'N/A' }}</div>
                    <div class="detail-row"><span class="detail-label">Payment Date:</span> {{ ! empty($receipt['payment_date']) ? \Carbon\Carbon::parse($receipt['payment_date'])->format('d/m/Y h:i A') : 'Not recorded' }}</div>
                    <div class="detail-row"><span class="detail-label">Gateway Ref.:</span> {{ $receipt['paystack_reference'] ?? 'Not available' }}</div>
                    <div class="detail-row"><span class="detail-label">Channel:</span> {{ $receipt['payment_channel'] ?? 'Not available' }}</div>
                </td>
            </tr>
        </table>

        <table class="amount-table">
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>Payment Amount</td>
                <td>{{ $currency($receipt['base_amount'] ?? 0) }}</td>
            </tr>
        </table>

        <div class="note-box">
            <p class="note-title">Official Note</p>
            <p>{{ $receipt['official_note'] ?? 'This is evidence of payment.' }}</p>
            <p>This document was generated from a verified payment record and is valid without signature or stamp.</p>
        </div>

        <table class="footer-table">
            <tr>
                <td>Receipt No: {{ $receipt['receipt_number'] ?? 'N/A' }}</td>
                <td>Generated: {{ $generatedAt->format('d/m/Y h:i A') }}</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
