<section class="container" aria-labelledby="factory-careers-title" style="padding-top:24px;padding-bottom:24px">
    <h2 id="factory-careers-title">Follow the work through a factory</h2>
    <p>A typical manufacturing route. The actual sequence and qualifications depend on the plant and product.</p>
    <ol style="display:flex;flex-wrap:wrap;gap:12px;padding-left:24px">
        @foreach(['Material inward','Stores and planning','Casting / forging / fabrication','Machining and assembly','Inspection and testing','Packaging and dispatch'] as $step)
        <li style="flex:1 1 150px;padding:12px;background:#eef4fb;color:#143452;border-radius:8px">{{ $step }}</li>
        @endforeach
    </ol>
    <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse">
        <caption style="text-align:left;font-weight:700;padding:12px 0">Find roles by department</caption>
        <thead><tr><th scope="col">Department</th><th scope="col">Work and example roles</th></tr></thead>
        <tbody>
        @foreach(['Material Inward / Stores'=>'Receiving, material checks, inventory and store keeping','PPC / Purchase'=>'Production planning, material scheduling and procurement','Foundry / Forging'=>'Melting, moulding, core making and die setting','Machine Shop'=>'VMC operation, CNC machining, heavy machining and fixtures','Maintenance'=>'Maintenance fitter, conveyor technician, diesel and tractor mechanic','Tool Room'=>'Die maintenance fitter, tool / die fixture technician','Quality / Testing'=>'Inspection, metrology and product testing','Research and Development'=>'Product design, engineering trials and process improvement','Accounts'=>'Costing, supplier invoices and financial records','Packaging / Dispatch'=>'Packing, dispatch documentation and loading'] as $department=>$work)
        <tr><th scope="row" style="text-align:left;padding:10px;border-bottom:1px solid #ccd8e6">{{ $department }}</th><td style="padding:10px;border-bottom:1px solid #ccd8e6">{{ $work }}</td></tr>
        @endforeach
        </tbody>
    </table></div>
    <ul><li>Example roles explain careers; they are not advertised vacancies.</li><li>Opening counts come from active job records. Use the employer link to check requirements and apply.</li><li>A job is assigned to a plant only when the company and location match.</li></ul>
    <details><summary>Official recruitment sources and coverage</summary>
        <p>Coverage is growing. This directory does not yet cover every company or every industrial estate.</p>
        <ul>@foreach(config('industrial_sources.feeds',[]) as $source)<li><a href="{{ $source['careers_url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['name'] }} ? official job board</a></li>@endforeach</ul>
        <div style="overflow-x:auto"><table style="width:100%"><thead><tr><th scope="col">Research source</th><th scope="col">Current coverage</th></tr></thead><tbody>
        @foreach(config('industrial_sources.research',[]) as $source)<tr><th scope="row" style="text-align:left;padding:8px"><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['name'] }}</a></th><td style="padding:8px">{{ $source['status'] }}</td></tr>@endforeach
        </tbody></table></div>
    </details>
</section>
