@extends('backend.layouts.app')

@section('content')

    @php($result = session('price_stock_result'))

    @if ($result && $result['errors'])
        <div class="card border-danger">
            <div class="card-header">
                <h5 class="mb-0 h6 text-danger">{{ translate('Nothing was updated. Fix these rows and upload again') }}</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0 pl-3">
                    @foreach ($result['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @elseif ($result && !$result['changes'])
        <div class="alert alert-info">{{ translate('No changes found. Every price and stock in the file already matches the store.') }}</div>
    @elseif ($result)
        <div class="card border-success">
            <div class="card-header">
                <h5 class="mb-0 h6 text-success">{{ count($result['changes']) }} {{ translate('variants updated') }}</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Product') }}</th>
                            <th>{{ translate('Variant') }}</th>
                            <th>{{ translate('SKU') }}</th>
                            <th>{{ translate('Price') }}</th>
                            <th>{{ translate('Stock') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($result['changes'] as $change)
                            <tr>
                                <td>{{ $change['product'] }}</td>
                                <td>{{ $change['variant'] ?: translate('Default') }}</td>
                                <td>{{ $change['sku'] }}</td>
                                <td>
                                    @if ($change['old_price'] != $change['new_price'])
                                        {{ single_price($change['old_price']) }} &rarr; <strong>{{ single_price($change['new_price']) }}</strong>
                                    @else
                                        {{ single_price($change['new_price']) }}
                                    @endif
                                </td>
                                <td>
                                    @if ($change['old_stock'] != $change['new_stock'])
                                        {{ $change['old_stock'] }} &rarr; <strong>{{ $change['new_stock'] }}</strong>
                                    @else
                                        {{ $change['new_stock'] }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Bulk Price & Stock Update') }}</h5>
        </div>
        <div class="card-body">
            <div class="alert mb-3" style="color: #004085;background-color: #cce5ff;border-color: #b8daff;">
                <p>1. {{ translate('Download the sheet. It has one row for each product variant') }}.</p>
                <p>2. {{ translate('Change only the yellow Price and Stock columns. Do not change the Stock ID') }}.</p>
                <p>3. {{ translate('Upload the file below. Leave a cell blank to keep its current value') }}.</p>
                <p class="mb-0">4. {{ translate('If any row has a mistake, nothing is updated and the rows to fix are listed') }}.</p>
            </div>
            @can('product_bulk_export')
                <a href="{{ route('product_price_stock.download') }}" class="btn btn-info">
                    {{ translate('Download Price & Stock Sheet') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6"><strong>{{ translate('Upload Updated Sheet') }}</strong></h5>
        </div>
        <div class="card-body">
            <form action="{{ route('product_price_stock.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group row">
                    <div class="col-sm-9">
                        <div class="custom-file">
                            <label class="custom-file-label">
                                <input type="file" name="price_stock_file" class="custom-file-input" accept=".xlsx,.xls,.csv" required>
                                <span class="custom-file-name">{{ translate('Choose File') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-info">{{ translate('Upload and Update') }}</button>
                </div>
            </form>
        </div>
    </div>

@endsection
