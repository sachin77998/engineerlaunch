@php
    $alertMessages = collect($errors->any() ? $errors->all() : []);
    foreach (['error', 'danger', 'warning'] as $flashKey) {
        if (session()->has($flashKey)) $alertMessages->push(session($flashKey));
    }
    $alertMessages = $alertMessages->filter()->unique()->values();
@endphp
@if ($alertMessages->isNotEmpty())
<div class="container py-3" aria-live="assertive">
@foreach ($alertMessages as $message)
<div class="alert alert-danger d-flex align-items-start mb-2" role="alert">
<svg class="bi flex-shrink-0 me-2 mt-1" width="20" height="20" role="img" aria-label="Danger"><use href="#exclamation-triangle-fill" xlink:href="#exclamation-triangle-fill"></use></svg>
<div>{{ $message }}</div>
</div>
@endforeach
</div>
@endif
<svg xmlns="http://www.w3.org/2000/svg" class="d-none" aria-hidden="true"><symbol id="exclamation-triangle-fill" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"></path></symbol></svg>
