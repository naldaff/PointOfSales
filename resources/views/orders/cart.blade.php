<div class="col-md-4">
	@component('components.card')
		@slot('title')
		<span class="fa fa-shopping-cart"></span> Keranjang
		@endslot

		<div class="table-responsive">
			<table class="table table-hover">
				<thead>
					<tr>
						<th>Produk</th>
						<th>Harga</th>
						<th>Qty</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(row, index) in shoppingCart">
						<td>@{{ row.name }} <label for="" class="badge badge-info">@{{ row.code }}</label></td>
                        <td>@{{ row.price | currency }}</td>
                        <td>@{{ row.qty }}</td>
                        <td>
                            <!-- Event Unclick untuk menghapus cart -->
                            <button
                                @click.prevent="removeCart(index)"
                                class="btn btn-danger btn-sm">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
					</tr>
				</tbody>
			</table>
		</div>
		@slot('footer')
		<div class="card-footer text-muted">
			@if (url()->current() == route('order.transaksi'))
			<a href="{{ route('order.checkout') }}"
				class="btn btn-info btn-sm float-right">
				Checkout
			</a>
			@else
			<a href="{{ route('order.transaksi') }}"
				class="btn btn-secondary btn-sm float-right">
				Kembali		
			</a>
			@endif
		</div>
		@endslot
	@endcomponent
</div>

@section('js')
    <script src="{{ asset('plugins/select2/selects2.min.js') }}"></script>
    <script src="{{ asset('plugins/accounting/accounting.min.js') }}"></script>
    <script src="{{ asset('js/transaksi.js') }}"></script>
@endsection
