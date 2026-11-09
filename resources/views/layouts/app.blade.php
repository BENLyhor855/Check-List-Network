<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NOC Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('app.css') }}">
    @livewireStyles
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('overview') }}"><span class="mark" aria-hidden="true">N</span><span>NOC Platform<small>NETWORK OPERATIONS</small></span></a>
        <nav aria-label="Main navigation">
            <div class="nav-caption">WORKSPACE</div>
            <a class="nav-item {{ request()->routeIs('overview') ? 'selected' : '' }}" href="{{ route('overview') }}" @if(request()->routeIs('overview')) aria-current="page" @endif><span class="nav-indicator" aria-hidden="true"></span>Overview</a>
            <a class="nav-item {{ request()->routeIs('reports.*') ? 'selected' : '' }}" href="{{ route('reports.inventory') }}" @if(request()->routeIs('reports.*')) aria-current="page" @endif><span class="nav-indicator" aria-hidden="true"></span>Reports</a>
            @can('administer')
                <div class="nav-caption">MANAGE</div>
                <a class="nav-item {{ request()->routeIs('admin.devices') ? 'selected' : '' }}" href="{{ route('admin.devices') }}" @if(request()->routeIs('admin.devices')) aria-current="page" @endif><span class="nav-indicator" aria-hidden="true"></span>Access points</a>
                <a class="nav-item {{ request()->routeIs('admin.buildings') ? 'selected' : '' }}" href="{{ route('admin.buildings') }}" @if(request()->routeIs('admin.buildings')) aria-current="page" @endif><span class="nav-indicator" aria-hidden="true"></span>Buildings</a>
                <a class="nav-item {{ request()->routeIs('admin.audit') ? 'selected' : '' }}" href="{{ route('admin.audit') }}" @if(request()->routeIs('admin.audit')) aria-current="page" @endif><span class="nav-indicator" aria-hidden="true"></span>Audit log</a>
            @endcan
            @can('manage-integrations')
                <a class="nav-item {{ request()->routeIs('admin.integrations') ? 'selected' : '' }}" href="{{ route('admin.integrations') }}" @if(request()->routeIs('admin.integrations')) aria-current="page" @endif><span class="nav-indicator" aria-hidden="true"></span>Integrations</a>
                <a class="nav-item {{ request()->routeIs('admin.users') ? 'selected' : '' }}" href="{{ route('admin.users') }}" @if(request()->routeIs('admin.users')) aria-current="page" @endif><span class="nav-indicator" aria-hidden="true"></span>Users</a>
            @endcan
        </nav>
        <div class="side-note"><span class="side-note-label">DATA MODE</span><strong>Inventory only</strong><span>Live monitoring not connected</span></div>
    </aside>
    <main id="main-content" class="main" tabindex="-1">
        <header class="topbar"><span>Network operations <span class="top-separator">/</span> <strong>{{ request()->routeIs('admin.*') ? 'Management' : 'Workspace' }}</strong></span><div class="user-area"><span>{{ auth()->user()->name }}</span><form action="{{ route('logout') }}" method="POST">@csrf<button class="logout" type="submit">Sign out</button></form></div></header>
        <div class="content">{{ $slot }}</div>
    </main>
</div>
@livewireScripts
</body>
</html>
