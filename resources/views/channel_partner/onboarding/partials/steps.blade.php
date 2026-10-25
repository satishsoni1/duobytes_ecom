@php
    $cpSteps = [1 => translate('Account'), 2 => translate('Business'), 3 => translate('Documents'), 4 => translate('Review')];
@endphp
<div class="cp-steps" aria-label="{{ translate('Step') }} {{ $current }} / 4">
    @foreach ($cpSteps as $n => $label)
        <div class="cp-step {{ $n < $current ? 'done' : ($n === $current ? 'active' : '') }}" @if ($n === $current) aria-current="step" @endif>
            <div class="cp-step-bar"></div>
            {{ $n }}<span>. {{ $label }}</span>
        </div>
    @endforeach
</div>
