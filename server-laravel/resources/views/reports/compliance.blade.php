<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
  .header { background: #065f46; color: #fff; padding: 14px 18px; margin-bottom: 16px; }
  .header h1 { font-size: 15px; font-weight: bold; }
  .header p  { font-size: 9px; margin-top: 3px; opacity: .8; }
  .standard-block { margin: 12px 18px; }
  .standard-title { font-size: 12px; font-weight: bold; color: #065f46; padding: 6px 10px; background: #d1fae5; border-left: 4px solid #059669; margin-bottom: 8px; }
  .clause { margin-bottom: 12px; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; }
  .clause-header { background: #f0fdf4; padding: 6px 10px; border-bottom: 1px solid #e5e7eb; }
  .clause-no { font-weight: bold; color: #065f46; font-size: 10px; }
  .clause-label { font-size: 9px; color: #6b7280; }
  .clause-desc { font-size: 8px; color: #9ca3af; margin-top: 1px; }
  .metrics { padding: 6px 10px; }
  .metric-row { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #f3f4f6; }
  .metric-row:last-child { border-bottom: none; }
  .metric-key { color: #374151; }
  .metric-val { font-weight: bold; color: #065f46; }
  .footer { margin-top: 20px; padding: 10px 18px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
  <h1>ISO Compliance Report</h1>
  <p>ISO 9001:2015 &amp; ISO 15489-1:2016 &mdash; Generated {{ $generatedAt }}</p>
</div>

@php
  $standards = [
    'iso_9001'  => ['label' => 'ISO 9001:2015 — Quality Management', 'key' => 'iso_9001'],
    'iso_15489' => ['label' => 'ISO 15489-1:2016 — Records Management', 'key' => 'iso_15489'],
  ];
@endphp

@foreach($standards as $std)
<div class="standard-block">
  <div class="standard-title">{{ $std['label'] }}</div>
  @foreach($data[$std['key']] as $clauseNo => $section)
  <div class="clause">
    <div class="clause-header">
      <span class="clause-no">Clause {{ $clauseNo }}</span>
      &nbsp;&mdash;&nbsp;
      <span class="clause-label">{{ $section['label'] }}</span>
      <div class="clause-desc">{{ $section['description'] }}</div>
    </div>
    <div class="metrics">
      @foreach($section['metrics'] as $key => $value)
        @if(!is_array($value))
        <div class="metric-row">
          <span class="metric-key">{{ str_replace('_', ' ', ucwords($key, '_')) }}</span>
          <span class="metric-val">{{ $value }}</span>
        </div>
        @endif
      @endforeach
    </div>
  </div>
  @endforeach
</div>
@endforeach

<div class="footer">
  DTS ISO Compliance Report &bull; {{ $generatedAt }} &bull; Confidential
</div>
</body>
</html>
