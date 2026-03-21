<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
  .header { background: #4c1d95; color: #fff; padding: 14px 18px; margin-bottom: 16px; }
  .header h1 { font-size: 15px; font-weight: bold; }
  .header p  { font-size: 9px; margin-top: 3px; opacity: .8; }
  .integrity-badge { display: inline-block; background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; border-radius: 3px; padding: 2px 6px; font-size: 8px; font-weight: bold; margin: 0 18px 10px; }
  .doc-info { margin: 0 18px 14px; background: #f5f3ff; border: 1px solid #c4b5fd; border-radius: 4px; padding: 10px; }
  .doc-info table { width: 100%; }
  .doc-info td { padding: 3px 0; }
  .doc-info .label { color: #7c3aed; font-weight: bold; width: 140px; }
  .section-title { margin: 14px 18px 6px; font-size: 11px; font-weight: bold; color: #4c1d95; border-bottom: 1px solid #ddd8fe; padding-bottom: 3px; }
  table { width: calc(100% - 36px); margin: 0 18px; border-collapse: collapse; font-size: 8.5px; }
  thead th { background: #ede9fe; color: #4c1d95; padding: 5px 7px; text-align: left; border-bottom: 2px solid #c4b5fd; }
  tbody td { padding: 4px 7px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  tbody tr:nth-child(even) td { background: #faf5ff; }
  .status-pill { display: inline-block; background: #ede9fe; color: #5b21b6; border-radius: 3px; padding: 1px 5px; font-size: 7.5px; }
  .footer { margin-top: 20px; padding: 10px 18px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
  .page-break { page-break-after: always; }
</style>
</head>
<body>
<div class="header">
  <h1>Transaction Audit Report</h1>
  <p>Document Tracking System &mdash; Full Integrity Log &mdash; Generated {{ $generatedAt }}</p>
</div>

<span class="integrity-badge">⚑ PDF-Only Export — Integrity Protected</span>

@php $doc = $data['document']; @endphp
<div class="doc-info">
  <table>
    <tr>
      <td class="label">Document No</td>
      <td>{{ $doc['document_no'] }}</td>
      <td class="label">Status</td>
      <td>{{ $doc['status'] }}</td>
    </tr>
    <tr>
      <td class="label">Document Type</td>
      <td>{{ $doc['document_type'] ?? '—' }}</td>
      <td class="label">Origin Office</td>
      <td>{{ $doc['office_name'] ?? '—' }}</td>
    </tr>
    <tr>
      <td class="label">Transactions</td>
      <td>{{ count($data['transactions']) }}</td>
      <td class="label">Created At</td>
      <td>{{ $doc['created_at'] }}</td>
    </tr>
  </table>
</div>

{{-- Transaction Logs --}}
<p class="section-title">Full Transaction Log ({{ count($data['logs']) }} entries)</p>
<table>
  <thead>
    <tr>
      <th style="width:100px">Timestamp</th>
      <th>Status</th>
      <th>Office</th>
      <th>Transaction No</th>
      <th>Reason</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['logs'] as $log)
    <tr>
      <td>{{ \Carbon\Carbon::parse($log['created_at'])->format('Y-m-d H:i') }}</td>
      <td><span class="status-pill">{{ $log['status'] }}</span></td>
      <td>{{ $log['office_name'] ?? '—' }}</td>
      <td style="font-size:8px">{{ $log['transaction_no'] }}</td>
      <td>{{ $log['reason'] ?? '—' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

{{-- Recipients --}}
@if(count($data['recipients']) > 0)
<p class="section-title">All Recipients ({{ count($data['recipients']) }})</p>
<table>
  <thead>
    <tr>
      <th>Transaction No</th>
      <th>Office</th>
      <th>Type</th>
      <th>Sequence</th>
      <th>Active</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['recipients'] as $r)
    <tr>
      <td style="font-size:8px">{{ $r['transaction_no'] }}</td>
      <td>{{ $r['office_name'] }}</td>
      <td>{{ $r['recipient_type'] }}</td>
      <td style="text-align:center">{{ $r['sequence'] }}</td>
      <td style="text-align:center">{{ $r['isActive'] ? 'Yes' : 'No' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- Versions --}}
@if(count($data['versions']) > 0)
<p class="section-title">Document Versions ({{ count($data['versions']) }})</p>
<table>
  <thead>
    <tr>
      <th>Version</th>
      <th>Action Type</th>
      <th>Changed By</th>
      <th>Changed At</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['versions'] as $v)
    <tr>
      <td>v{{ $v['version_number'] }}</td>
      <td>{{ $v['action_type'] }}</td>
      <td>{{ $v['changed_by_name'] }}</td>
      <td>{{ \Carbon\Carbon::parse($v['changed_at'])->format('Y-m-d H:i') }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- Official Notes --}}
@if(count($data['notes']) > 0)
<p class="section-title">Official Notes ({{ count($data['notes']) }})</p>
<table>
  <thead>
    <tr>
      <th style="width:100px">Date</th>
      <th>Office</th>
      <th>Note</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['notes'] as $note)
    <tr>
      <td>{{ \Carbon\Carbon::parse($note['created_at'])->format('Y-m-d H:i') }}</td>
      <td>{{ $note['office_name'] }}</td>
      <td>{{ $note['note'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- Attachments --}}
@if(count($data['attachments']) > 0)
<p class="section-title">Attachments ({{ count($data['attachments']) }})</p>
<table>
  <thead>
    <tr>
      <th>Filename</th>
      <th>Transaction No</th>
      <th>Uploaded At</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['attachments'] as $att)
    <tr>
      <td>{{ $att['original_name'] ?? $att['file_name'] ?? '—' }}</td>
      <td style="font-size:8px">{{ $att['transaction_no'] }}</td>
      <td>{{ \Carbon\Carbon::parse($att['created_at'])->format('Y-m-d H:i') }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@endif

<div class="footer">
  DTS Transaction Audit Report &bull; Document: {{ $data['document']['document_no'] }} &bull; {{ $generatedAt }} &bull; Confidential — Do not distribute
</div>
</body>
</html>
