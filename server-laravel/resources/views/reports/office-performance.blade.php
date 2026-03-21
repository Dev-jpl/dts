<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
  .header { background: #1e40af; color: #fff; padding: 14px 18px; margin-bottom: 16px; }
  .header h1 { font-size: 15px; font-weight: bold; }
  .header p  { font-size: 9px; margin-top: 3px; opacity: .8; }
  .meta { display: flex; gap: 20px; margin: 0 18px 12px; font-size: 9px; color: #6b7280; }
  table { width: calc(100% - 36px); margin: 0 18px; border-collapse: collapse; font-size: 9px; }
  thead th { background: #dbeafe; color: #1e40af; padding: 6px 8px; text-align: left; border-bottom: 2px solid #93c5fd; }
  tbody td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  tbody tr:nth-child(even) td { background: #f9fafb; }
  .section-title { margin: 14px 18px 6px; font-size: 11px; font-weight: bold; color: #1e40af; }
  .reasons { font-size: 8px; color: #6b7280; }
  .footer { margin-top: 20px; padding: 10px 18px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
  <h1>Office Performance Report</h1>
  <p>Document Tracking System &mdash; Generated {{ $generatedAt }}</p>
</div>

<div class="meta">
  <span><strong>Period:</strong> {{ $periodStart }} &ndash; {{ $periodEnd }}</span>
  <span><strong>Offices:</strong> {{ count($data) }}</span>
</div>

<p class="section-title">Performance Metrics</p>
<table>
  <thead>
    <tr>
      <th>Office</th>
      <th style="text-align:center">Received</th>
      <th style="text-align:center">On-Time %</th>
      <th style="text-align:center">Avg Receive (hrs)</th>
      <th style="text-align:center">Avg Complete (hrs)</th>
      <th style="text-align:center">Return Rate %</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data as $row)
    <tr>
      <td>{{ $row['office_name'] }}</td>
      <td style="text-align:center">{{ $row['received_count'] }}</td>
      <td style="text-align:center">{{ $row['on_time_rate'] !== null ? $row['on_time_rate'].'%' : '—' }}</td>
      <td style="text-align:center">{{ $row['avg_time_to_receive'] !== null ? $row['avg_time_to_receive'] : '—' }}</td>
      <td style="text-align:center">{{ $row['avg_time_to_complete'] !== null ? $row['avg_time_to_complete'] : '—' }}</td>
      <td style="text-align:center">{{ $row['return_rate'] }}%</td>
    </tr>
    @if(!empty($row['return_reasons']))
    <tr>
      <td colspan="6" style="padding: 2px 8px 6px;">
        <span class="reasons">
          Return reasons:
          @foreach($row['return_reasons'] as $rr)
            {{ $rr['reason'] }} ({{ $rr['count'] }}){{ !$loop->last ? ' · ' : '' }}
          @endforeach
        </span>
      </td>
    </tr>
    @endif
    @endforeach
  </tbody>
</table>

<div class="footer">
  DTS Office Performance Report &bull; {{ $generatedAt }} &bull; Confidential
</div>
</body>
</html>
