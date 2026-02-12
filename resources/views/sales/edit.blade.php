@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Sale</h1>
    <form action="{{ route('sales.update', $sale->id ?? '') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="product" class="form-label">Product</label>
            <input type="text" class="form-control" id="product" name="product" value="{{ old('product', $sale->product ?? '') }}">
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" class="form-control" id="quantity" name="quantity" value="{{ old('quantity', $sale->quantity ?? '') }}">
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" value="{{ old('price', $sale->price ?? '') }}">
        </div>
        <button type="submit" class="btn btn-primary">Update Sale</button>
    </form>
</div>
@endsection
