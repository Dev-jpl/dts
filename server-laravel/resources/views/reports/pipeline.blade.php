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
  .section-title { margin: 14px 18px 6px; font-size: 11px; font-weight: bold; color: #1e40af; }
  .summary-grid { display: flex; gap: 10px; margin: 0 18px 12px; flex-wrap: wrap; }
  .stat-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 8px 14px; min-width: 90px; }
  .stat-box .label { font-size: 8px; color: #6b7280; text-transform: uppercase; }
  .stat-box .value { font-size: 18px; font-weight: bold; color: #1e40af; }
  table { width: calc(100% - 36px); margin: 0 18px; border-collapse: collapse; font-size: 9px; }
  thead th { background: #dbeafe; color: #1e40af; padding: 6px 8px; text-align: left; border-bottom: 2px solid #93c5fd; }
  tbody td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
  tbody tr:nth-child(even) td { background: #f9fafb; }
  .overdue-badge { background: #fee2e2; color: #b91c1c; border-radius: 3px; padding: 1px 4px; font-size: 8px; }
  .age-table { width: calc(100% - 36px); margin: 0 18px; border-collapse: collapse; font-size: 9px; }
  .age-table td { padding: 5px 10px; border: 1px solid #e5e7eb; }
  .age-table thead th { background: #fef3c7; color: #92400e; padding: 5px 10px; border: 1px solid #e5e7eb; }
  .footer { margin-top: 20px; padding: 10px 18px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
  <h1>Document Pipeline Report</h1>
  <p>Document Tracking System &mdash; Generated {{ $generatedAt }}</p>
</div>

<p class="section-title">Status Summary</p>
<div class="summary-grid">
  @foreach($data['summary'] as $status => $count)
  <div class="stat-box">
    <div class="label">{{ $status }}</div>
    <div class="value">{{ $count }}</div>
  </div>
  @endforeach
</div>

<p class="section-title">Age Distribution (Active Documents)</p>
<table class="age-table">
  <thead>
    <tr>
      <th>&lt; 7 days</th>
      <th>7 – 30 days</th>
      <th>30 – 90 days</th>
      <th>&gt; 90 days</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>{{ $data['aged']['under_7'] }}</td>
      <td>{{ $data['aged']['7_to_30'] }}</td>
      <td>{{ $data['aged']['30_to_90'] }}</td>
      <td>{{ $data['aged']['over_90'] }}</td>
    </tr>
  </tbody>
</table>

@if(count($data['overdue']) > 0)
<p class="section-title">Overdue Documents ({{ count($data['overdue']) }})</p>
<table>
  <thead>
    <tr>
      <th>Document No</th>
      <th>Subject</th>
      <th>Type</th>
      <th>Urgency</th>
      <th>Office</th>
      <th>Due Date</th>
      <th>Days Overdue</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['overdue'] as $item)
    <tr>
      <td>{{ $item['document_no'] }}</td>
      <td>{{ \Illuminate\Support\Str::limit($item['subject'], 40) }}</td>
      <td>{{ $item['document_type'] }}</td>
      <td>{{ $item['urgency_level'] }}</td>
      <td>{{ $item['office_name'] }}</td>
      <td>{{ $item['due_date'] }}</td>
      <td><span class="overdue-badge">{{ $item['days_overdue'] }}d</span></td>
    </tr>
    @endforeach
  </tbody>
</table>
@else
<p style="margin: 10px 18px; color: #6b7280; font-size: 9px;">No overdue documents found.</p>
@endif

<div class="footer">
  DTS Document Pipeline Report &bull; {{ $generatedAt }} &bull; Confidential
</div>
</body>
</html>
