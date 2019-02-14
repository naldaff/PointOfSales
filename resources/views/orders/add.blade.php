@extends('layouts.master')

@section('title')
	<title>Transaksi</title>
@endsection

@push('css')
	<link rel="stylesheet" href="{{ asset('plugins/select2/select2.min.css') }}" />
@endpush

@section('content')
	<div class="content-wrapper">
		<div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">Transaksi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Transaksi</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content" id="ris">
        	<div class="container-fluid">
        		<div class="row">
        			<div class="col-md-8">
        				@component('components.card')
        					@slot('title')

        					@endslot

        					<div class="row">
        						<div class="col-md-4">
                                    <!-- SUBMIT DIJALANKAN KETIKA TOMBOL DITEKAN -->
                                    <form action="#" @submit.prevent="addToCart" method="post">                                
            							<div class="form-group">
            								<label>Produk</label>
            								<select name="product_id" id="product_id"
                                                v-model="cart.product_id"
                                                class="form-control" width="100%" required>
                                                <option value="">Pilih</option>
                                                @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->code }} - {{ $product->name }}</option>
                                                @endforeach
                                            </select>
            							</div>
            							<div class="form-group">
            								<label for="">Qty</label>
            								<input type="number" name="qty" id="qty" 
                                                v-model="cart.qty"
                                                value="1" min="1" class="form-control" required>
            							</div>
            							<div class="form-group">
            								<button class="btn btn-primary btn-sm" :disabled="submitCart">
            									<i class="fa fa-shopping-cart"></i>@{{ submitCart ? ' Loading...': ' Ke Keranjang' }}
            								</button>
            							</div>
                                    </form>
        						</div>

        						<div class="col-sm-5">
        							<h4>Detail Produk</h4>
        							<div v-if="product.name">
        								<table class="table table-stripped">
        									<tr>
        										<td>Kode</td>
        										<td>:</td>
        										<td>@{{ product.code }}</td>
        									</tr>
        									<tr>
        										<td width="3%">Produk</td>
        										<td width="2%">:</td>
        										<td>@{{ product.name }}</td>
        									</tr>
        									<tr>
        										<td>Harga</td>
        										<td>:</td>
        										<td>@{{ product.price | currency }}</td>
        									</tr>
        								</table>
        							</div>	
        						</div>

        						<div class="col-md-3" v-if="product.photo">
        							<img :src="'/uploads/product/' + product.photo"
        								height="160px"
        								width="150px"
        								:alt="product.name">
        						</div>

        					</div>
        					@slot('footer')

        					@endslot
        				@endcomponent
        			</div>

                    @include('orders.cart')
        		
                </div>
        	</div>
        </section>
	</div>
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/select2.full.min.js') }}"></script>
    <script src="{{ asset('plugins/accounting/accounting.min.js') }}"></script>
    <script src="{{ asset('js/transaksi.js') }}"></script>
@endsection