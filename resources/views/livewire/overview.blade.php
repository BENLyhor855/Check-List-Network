<div>
    <div class="page-intro">
        <div><div class="eyebrow">OPERATIONS / OVERVIEW</div><h1>Network overview</h1><p class="muted">Your inventory and the latest recorded information, in one place.</p></div>
        <span class="badge-soft"><span class="status-dot"></span> Inventory workspace</span>
    </div>
    <div class="notice" role="status"><span class="notice-icon">i</span><div><strong>Live status is not collected</strong><p>Online and offline figures below are last recorded values, not a live health check. Client and traffic data are not available yet.</p></div></div>
    <div class="kpi-grid" aria-label="Inventory summary">
        <div class="kpi"><div class="kpi-label">ACCESS POINTS</div><strong>{{ $accessPoints }}</strong><small>In inventory</small></div>
        <div class="kpi"><div class="kpi-label">LAST RECORDED ONLINE</div><strong>{{ ($online + $offline) ? $online : '—' }}</strong><small>Not live status</small></div>
        <div class="kpi"><div class="kpi-label">LAST RECORDED OFFLINE</div><strong>{{ ($online + $offline) ? $offline : '—' }}</strong><small>Not live status</small></div>
        <div class="kpi"><div class="kpi-label">STATUS UNAVAILABLE</div><strong>{{ $unknown }}</strong><small>Devices without a usable status</small></div>
    </div>
    <div class="overview-grid">
        <section aria-labelledby="locations-title"><div class="section-title"><div><div class="eyebrow">INVENTORY</div><h2 id="locations-title">Buildings <span class="heading-count">{{ $buildings }} active</span></h2></div>@can('administer')<a class="link-action" href="{{ route('admin.buildings') }}">Manage buildings <span aria-hidden="true">→</span></a>@endcan</div>
            <div class="panel">@forelse($sites as $site)<div class="building-row"><div><strong>{{ $site->name }}</strong><small>{{ $site->location ?: 'Location not specified' }} · {{ $site->code }} @unless($site->active) · Disabled @endunless</small></div><div class="text-end"><strong>{{ $site->access_points_count }}</strong><small>access points</small></div></div>@empty<div class="empty-state"><strong>No buildings yet</strong><p>Add a building to organize your access points.</p>@can('administer')<a class="btn btn-primary" href="{{ route('admin.buildings') }}">Add building</a>@endcan</div>@endforelse</div>
        </section>
        <section aria-labelledby="collection-title"><div class="section-title"><div><div class="eyebrow">DATA COLLECTION</div><h2 id="collection-title">Inventory import</h2></div></div><div class="panel collection-panel"><div class="collection-line"><span>Last Meraki inventory import</span><strong>{{ $lastImport ? $lastImport->format('d M Y, H:i') . ' UTC' : 'Not yet imported' }}</strong></div><p class="muted">Imports add access point inventory. They do not collect live health, clients, or traffic.</p>@can('manage-integrations')<a class="link-action" href="{{ route('admin.integrations') }}">Open integrations <span aria-hidden="true">→</span></a>@endcan</div></section>
    </div>
    <div class="section-title"><div><div class="eyebrow">REPORTING</div><h2>Inventory report</h2></div><a class="link-action" href="{{ route('reports.inventory') }}">Open report <span aria-hidden="true">→</span></a></div>
    <div class="panel unavailable"><div><strong>Export your current inventory</strong><p>Filter by building or provider, then download Excel, PDF, or CSV.</p></div></div>
</div>
