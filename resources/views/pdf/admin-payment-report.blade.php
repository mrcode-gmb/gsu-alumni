<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GSU Payment Report</title>
    <style>
        @page {
            margin: 14mm 12mm 14mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 10px;
            line-height: 1.45;
        }

        .page {
            border: 1.5px solid #0f172a;
            position: relative;
            overflow: hidden;
            padding: 16px 16px 18px;
        }

        .watermark {
            position: absolute;
            top: 130px;
            left: 50%;
            width: 320px;
            margin-left: -160px;
            opacity: 0.05;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .header,
        .summary-table,
        .records-table,
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: top;
        }

        .logo-wrap {
            width: 82px;
        }

        .logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .title {
            text-align: center;
            padding: 0 10px;
        }

        .title h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .title p {
            margin: 5px 0 0;
            font-size: 11px;
            font-weight: 700;
        }

        .title .subtitle {
            margin-top: 9px;
            font-size: 16px;
            text-decoration: underline;
        }

        .meta-box {
            width: 210px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
        }

        .meta-label {
            margin: 0 0 3px;
            font-size: 9px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .meta-value {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        .section-line {
            margin: 14px 0 12px;
            border-top: 2px solid #166534;
        }

        .filter-tags {
            margin-bottom: 12px;
        }

        .filter-tag {
            display: inline-block;
            margin: 0 6px 6px 0;
            padding: 4px 8px;
            font-size: 9px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 999px;
        }

        .summary-grid {
            width: 100%;
            margin-bottom: 14px;
        }

        .summary-grid td {
            width: 20%;
            padding-right: 8px;
        }

        .summary-card {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 10px 12px;
            min-height: 68px;
        }

        .summary-card .label {
            margin: 0;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .summary-card .value {
            margin: 7px 0 0;
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .records-table {
            border: 1.2px solid #0f172a;
        }

        .records-table th,
        .records-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: top;
        }

        .records-table th {
            background: #e2e8f0;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .muted {
            color: #64748b;
            font-size: 9px;
        }

        .mono {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9px;
        }

        .status {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            background: #e2e8f0;
        }

        .status-successful {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-failed,
        .status-abandoned {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer {
            margin-top: 14px;
            font-size: 9px;
            color: #475569;
        }

        .footer td:last-child {
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $currency = static fn ($amount) => 'NGN '.number_format((float) $amount, 2);
    $formatDateTime = static function (?string $value): string {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y h:i A') : 'Not recorded';
    };
@endphp

<div class="page">
    @if (! empty($logoDataUri))
        <img src="{{ $logoDataUri }}" alt="GSU watermark" class="watermark">
    @endif

    <div class="content">
        <table class="header">
            <tr>
                <td class="logo-wrap">
                    @if (! empty($logoDataUri))
                        <img src="{{ $logoDataUri }}" alt="GSU logo" class="logo">
                    @endif
                </td>
                <td class="title">
                    <h1>GOMBE STATE UNIVERSITY</h1>
                    <p>TUDUN WADA GOMBE, GOMBE STATE</p>
                    <p class="subtitle">Admin Payment Report</p>
                </td>
                <td>
                    <div class="meta-box">
                        <p class="meta-label">Generated at</p>
                        <p class="meta-value">{{ $formatDateTime($reportMeta['generated_at'] ?? null) }}</p>

                        <p class="meta-label">Matching records</p>
                        <p class="meta-value">{{ $reportMeta['total'] ?? 0 }}</p>

                        <p class="meta-label">Export note</p>
                        <p class="meta-value" style="font-size: 10px;">
                            {{ ! empty($reportMeta['truncated']) ? 'This PDF includes the first '.$reportMeta['limit'].' records only.' : 'All matching records are included.' }}
                        </p>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-line"></div>

        @if (! empty($activeFilters))
            <div class="filter-tags">
                @foreach ($activeFilters as $filter)
                    <span class="filter-tag">{{ $filter['label'] }}: {{ $filter['value'] }}</span>
                @endforeach
            </div>
        @endif

        <table class="summary-grid">
            <tr>
                <td>
                    <div class="summary-card">
                        <p class="label">Total Requests</p>
                        <p class="value">{{ $summary['total_payment_requests'] ?? 0 }}</p>
                    </div>
                </td>
                <td>
                    <div class="summary-card">
                        <p class="label">Successful</p>
                        <p class="value">{{ $summary['total_successful_payments'] ?? 0 }}</p>
                    </div>
                </td>
                <td>
                    <div class="summary-card">
                        <p class="label">Pending</p>
                        <p class="value">{{ $summary['total_pending_payments'] ?? 0 }}</p>
                    </div>
                </td>
                <td>
                    <div class="summary-card">
                        <p class="label">Failed</p>
                        <p class="value">{{ $summary['total_failed_payments'] ?? 0 }}</p>
                    </div>
                </td>
                <td>
                    <div class="summary-card">
                        <p class="label">Collected</p>
                        <p class="value">{{ $currency($summary['total_amount_collected'] ?? 0) }}</p>
                    </div>
                </td>
            </tr>
        </table>

        <table class="records-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 19%;">Student</th>
                    <th style="width: 11%;">Department</th>
                    <th style="width: 11%;">Faculty</th>
                    <th style="width: 12%;">Payment Type</th>
                    <th style="width: 8%;">Amount</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 10%;">Payment Date</th>
                    <th style="width: 10%;">Reference</th>
                    <th style="width: 7%;">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paymentRecords as $index => $record)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div>{{ $record['full_name'] }}</div>
                            <div class="muted">{{ $record['matric_number'] }}</div>
                            <div class="muted">{{ $record['email'] }}</div>
                        </td>
                        <td>{{ $record['department'] }}</td>
                        <td>{{ $record['faculty'] }}</td>
                        <td>{{ $record['payment_type_name'] }}</td>
                        <td>{{ $currency($record['amount']) }}</td>
                        <td>
                            <span class="status status-{{ $record['payment_status'] }}">
                                {{ $record['payment_status_label'] }}
                            </span>
                        </td>
                        <td>{{ $formatDateTime($record['recorded_at'] ?? null) }}</td>
                        <td class="mono">{{ $record['payment_reference'] ?? 'Not generated' }}</td>
                        <td class="mono">{{ $record['receipt_number'] ?? 'No receipt' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 20px 12px;">
                            No payment records matched the current report filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="meta-table footer">
            <tr>
                <td>GSU Alumni Payment Portal report for internal payment monitoring.</td>
                <td>Generated on {{ now()->format('d/m/Y h:i A') }}</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
