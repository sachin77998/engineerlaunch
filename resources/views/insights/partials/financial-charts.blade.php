@php
    $chartRows = $ratios->values();
@endphp
<div class="ci-two" style="margin-top:20px">
@foreach([
    ['title'=>'Revenue vs Net Profit','keys'=>['revenue'=>'Revenue','pat'=>'Profit after tax'],'scale'=>10000000,'unit'=>'Cr'],
    ['title'=>'Margin Trends','keys'=>['ebitda_margin'=>'EBITDA margin','net_margin'=>'PAT margin'],'scale'=>1,'unit'=>'%']
] as $chart)
    @php
        $numbers = $chartRows->flatMap(fn($r)=>collect(array_keys($chart['keys']))->map(fn($key)=>$r[$key] === null ? null : $r[$key]/$chart['scale']))->filter(fn($v)=>$v!==null);
        $low=min(0,$numbers->min()??0); $high=max(0,$numbers->max()??0); $range=max(1,$high-$low); $low-=$range*0.08; $high+=$range*0.08;
        $cx=fn($i)=>75+($i+0.5)*430/max(1,$chartRows->count());
        $cy=fn($v)=>225-($v-$low)/($high-$low)*195;
        $colors=['#527fad','#43936c'];
    @endphp
    <section class="ci-card" style="min-width:0"><h3 class="ci-h3">{{ $chart['title'] }}</h3>
    @if($numbers->isNotEmpty())
    <svg viewBox="0 0 540 280" style="width:100%;height:auto" role="img" aria-label="{{ $chart['title'] }} by financial year; missing values are not plotted">
        @for($tick=0;$tick<=4;$tick++)
            @php($value=$low+($high-$low)*$tick/4)
            <line x1="75" x2="510" y1="{{ $cy($value) }}" y2="{{ $cy($value) }}" stroke="#e4eaf0"/>
            <text x="68" y="{{ $cy($value)+4 }}" text-anchor="end" font-size="11" fill="#526175">{{ number_format($value,1) }}{{ $chart['unit'] }}</text>
        @endfor
        <line x1="75" x2="510" y1="{{ $cy(0) }}" y2="{{ $cy(0) }}" stroke="#93a3b6"/>
        @foreach($chart['keys'] as $key=>$label)
            @php($seriesIndex=$loop->index)
            @foreach($chartRows as $i=>$row)
                @if($row[$key]!==null)
                    @php($point=$row[$key]/$chart['scale'])
                    @if($key==='revenue')
                        <rect x="{{ $cx($i)-min(24,150/max(1,$chartRows->count())) }}" y="{{ min($cy(0),$cy($point)) }}" width="{{ min(48,300/max(1,$chartRows->count())) }}" height="{{ abs($cy($point)-$cy(0)) }}" fill="{{ $colors[$seriesIndex] }}"><title>{{ $label }} FY {{ $row['year'] }}: {{ number_format($point,2) }} {{ $chart['unit'] }}</title></rect>
                    @else
                        @if($i>0 && $chartRows[$i-1][$key]!==null && (int)$row['year']===(int)$chartRows[$i-1]['year']+1)
                            <line x1="{{ $cx($i-1) }}" y1="{{ $cy($chartRows[$i-1][$key]/$chart['scale']) }}" x2="{{ $cx($i) }}" y2="{{ $cy($point) }}" stroke="{{ $colors[$seriesIndex] }}" stroke-width="2"/>
                        @endif
                        <circle cx="{{ $cx($i) }}" cy="{{ $cy($point) }}" r="4" fill="white" stroke="{{ $colors[$seriesIndex] }}" stroke-width="2"><title>{{ $label }} FY {{ $row['year'] }}: {{ number_format($point,2) }} {{ $chart['unit'] }}</title></circle>
                    @endif
                @endif
            @endforeach
        @endforeach
        @foreach($chartRows as $i=>$row)<text x="{{ $cx($i) }}" y="247" text-anchor="middle" font-size="10" fill="#526175">{{ $row['year'] }}</text>@endforeach
        @foreach($chart['keys'] as $key=>$label)<text x="{{ 80+$loop->index*210 }}" y="272" font-size="12" fill="{{ $colors[$loop->index] }}">{{ $label }}</text>@endforeach
    </svg>
    @else<p class="ci-muted">No filed figures available for this chart.</p>@endif
    </section>
@endforeach
</div>
<details class="ci-more"><summary>Financial highlights: chart data</summary><div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>FY</th><th>Revenue</th><th>EBITDA</th><th>Profit after tax</th><th>Net worth</th><th>Assets</th><th>Debt</th><th>EBITDA margin</th><th>PAT margin</th></tr></thead><tbody>
@foreach($ratios as $row)<tr><td>{{ $row['year'] }}</td>@foreach(['revenue','ebitda','pat','net_worth','total_assets','total_debt'] as $key)<td>{{ $money($row[$key]) }}</td>@endforeach @foreach(['ebitda_margin','net_margin'] as $key)<td>{{ $row[$key]===null?'Unknown':$row[$key].'%' }}</td>@endforeach</tr>@endforeach
</tbody></table></div></details>
