<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
  .header { background: #92400e; color: #fff; padding: 14px 18px; margin-bottom: 16px; }
  .header h1 { font-size: 15px; font-weight: bold; }
  .header p  { font-size: 9px; margin-top: 3px; opacity: .8; }
  .overall { margin: 0 18px 14px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 4px; padding: 10px 14px; display: flex; gap: 30px; }
  .overall .stat { text-align: center; }
  .overall .stat .label { font-size: 8px; color: #92400e; text-transform: uppercase; }
  .overall .stat .value { font-size: 22px; font-weight: bold; color: #78350f; }
  .section-title { margin: 14px 18px 6px; font-size: 11px; font-weight: bold; color: #92400e; }
  table { width: calc(100% - 36px); margin: 0 18px; border-collapse: collapse; font-size: 9px; margin-bottom: 12px; }
  thead th { background: #fef3c7; color: #92400e; padding: 5px 8px; text-align: left; border-bottom: 2px solid #fcd34d; }
  tbody td { padding: 4px 8px; border-bottom: 1px solid #e5e7eb; }
  tbody tr:nth-child(even) td { background: #fafafa; }
  .footer { margin-top: 20px; padding: 10px 18px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
  <h1>Individual Turnaround Report</h1>
  <p>Document Tracking System &mdash; Generated {{ $generatedAt }}</p>
</div>

@php $overall = $data['overall']; @endphp
<div class="overall">
  <div class="stat">
    <div class="label">Avg Turnaround</div>
    <div class="value">{{ $overall['avg_hours'] ?? '—' }} <small style="font-size:10px">hrs</small></div>
  </div>
  <div class="stat">
    <div class="label">Completed Actions</div>
    <div class="value">{{ $overall['completed_count'] }}</div>
  </div>
  <div class="stat">
    <div class="label">Period</div>
    <div class="value" style="font-size:12px">{{ $periodStart }}<br>to {{ $periodEnd }}</div>
  </div>
</div>

{{-- By Action Type --}}
@if(count($data['by_action_type']) > 0)
<p class="section-title">By Action Type</p>
<table>
  <thead>
    <tr>
      <th>Action Type</th>
      <th style="text-align:center">Avg Turnaround (hrs)</th>
      <th style="text-align:center">Completed</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['by_action_type'] as $row)
    <tr>
      <td>{{ $row['action_type'] }}</td>
      <td style="text-align:center">{{ $row['avg_hours'] ?? '—' }}</td>
      <td style="text-align:center">{{ $row['completed_count'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- By Document Type --}}
@if(count($data['by_document_type']) > 0)
<p class="section-title">By Document Type</p>
<table>
  <thead>
    <tr>
      <th>Document Type</th>
      <th style="text-align:center">Avg Turnaround (hrs)</th>
      <th style="text-align:center">Completed</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['by_document_type'] as $row)
    <tr>
      <td>{{ $row['document_type'] }}</td>
      <td style="text-align:center">{{ $row['avg_hours'] ?? '—' }}</td>
      <td style="text-align:center">{{ $row['completed_count'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- By Urgency --}}
@if(count($data['by_urgency']) > 0)
<p class="section-title">By Urgency Level</p>
<table>
  <thead>
    <tr>
      <th>Urgency</th>
      <th style="text-align:center">Avg Turnaround (hrs)</th>
      <th style="text-align:center">Completed</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['by_urgency'] as $row)
    <tr>
      <td>{{ $row['urgency_level'] }}</td>
      <td style="text-align:center">{{ $row['avg_hours'] ?? '—' }}</td>
      <td style="text-align:center">{{ $row['completed_count'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- Monthly Trend --}}
@if(count($data['trend']) > 0)
<p class="section-title">Monthly Trend</p>
<table>
  <thead>
    <tr>
      <th>Period</th>
      <th style="text-align:center">Avg Turnaround (hrs)</th>
      <th style="text-align:center">Completed</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['trend'] as $row)
    <tr>
      <td>{{ $row['period'] }}</td>
      <td style="text-align:center">{{ $row['avg_hours'] ?? '—' }}</td>
      <td style="text-align:center">{{ $row['completed_count'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

<div class="footer">
  DTS Individual Turnaround Report &bull; {{ $generatedAt }} &bull; Confidential
</div>
</body>
</html>
