@if (count($newest_products) > 0)

    @if(request()->page == null || request()->page == 1)
        <div class="tz-grid" id="newest-products-list">
    @endif

        @foreach ($newest_products as $index => $new_product)
            @include('frontend.nexa.partials.modern_product_card', ['product' => $new_product])
        @endforeach

    @if(request()->page == null || request()->page == 1)
        </div>
    @endif

@endif
