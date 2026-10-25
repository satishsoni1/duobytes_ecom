@php
    $cpStatusClass = [
        'pending' => 'warning', 'confirmed' => 'info', 'picked_up' => 'info', 'on_the_way' => 'info',
        'delivered' => 'success', 'cancelled' => 'danger',
    ][$status] ?? 'muted';
@endphp
<span class="cp-pill cp-pill-{{ $cpStatusClass }}">{{ translate(ucfirst(str_replace('_', ' ', $status))) }}</span>
