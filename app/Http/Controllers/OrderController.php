<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Order;
use App\Order_detail;
use App\Customer;
use Cookie;
use DB;
use Carbon\Carbon;
use App\User;
use PDF;

class OrderController extends Controller
{
    public function addOrder(){
    	$products = Product::orderBy('created_at', 'DESC')->get();
    	return view('orders.add', compact('products'));
    }

    public function getProduct($id){
    	$products = Product::findOrFail($id);
    	return response()->json($products, 200);
    }

    public function addToCart(Request $request){
    	//validasi data yang diterima dari ajax request addToCart
    	//mengirimkan product_id dan qty
    	$this->validate($request, [
    		'product_id' => 'required|exists:products,id',
    		'qty' => 'required|integer'
    	]);

    	//mengambil data product berdasarkan id
    	$product = Product::findOrFail($request->product_id);
    	//mengambil cookie cart dengan $request->cookie('cart')
    	$getCart = json_decode($request->cookie('cart'), true);

    	//jika datanya ada
    	if($getCart){
    		//jika keynya exists berdasarkan product_id
    		if(array_key_exists($request->product_id, $getCart)){
    			//jumlahkan qty barangnya
    			$getCart[$request->product_id]['qty'] += $request->qty;
    			//dikirim kembali untuk disimpan ke cookie
    			return response()->json($getCart, 200)
    				->cookie('cart', json_encode($getCart), 120);
    		}
    	}

    	//jika cart kosong maka tambahkan cart baru
    	$getCart[$request->product_id] = [
    		'code' => $product->code,
    		'name' => $product->name,
    		'price' => $product->price,
    		'qty' => $request->qty
    	];
    	//kirim responsenya kemudian simpan ke cookie
    	return response()->json($getCart, 200)
    		->cookie('cart', json_encode($getCart), 120);
    }

    public function getCart(){
    	//mengambil cart dari cookie
    	$cart = json_decode(request()->cookie('cart'), true);
    	//mengirimkan kembali dalam bentuk json untuk ditampilkan dengan vuejs
    	return response()->json($cart, 200);
    }

    public function removeCart($id){
    	//mengambil cart dari cookie
    	$cart = json_decode(request()->cookie('cart'), true);
    	//menghapus cart berdasarkan product_id
    	unset($cart[$id]);
    	//cart diperbarui
    	return response()->json($cart, 200)->cookie('cart', json_encode($cart), 120);
    }

    public function checkout(){
    	return view('orders.checkout');
    }

    public function storeOrder(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'name' => 'required|string|max:100',
            'address' => 'required',
            'phone' => 'required|numeric'
        ]);
        $cart = json_decode($request->cookie('cart'), true);
        $result = collect($cart)->map(function($value) {
            return [
                'code' => $value['code'],
                'name' => $value['name'],
                'qty' => $value['qty'],
                'price' => $value['price'],
                'result' => $value['price'] * $value['qty']
            ];
        })->all();
        DB::beginTransaction();
        try {
            $customer = Customer::firstOrCreate([
                'email' => $request->email
            ], [
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone
            ]);
            $order = Order::create([
                'invoice' => $this->generateInvoice(),
                'customer_id' => $customer->id,
                'user_id' => auth()->user()->id,
                'total' => array_sum(array_column($result, 'result'))
            ]);
            foreach ($result as $key => $row) {
                $order->order_detail()->create([
                    'product_id' => $key,
                    'qty' => $row['qty'],
                    'price' => $row['price']
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => $order->invoice,
            ], 200)->cookie(Cookie::forget('cart'));
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function generateInvoice()
    {
        $order = Order::orderBy('created_at', 'DESC');
        if ($order->count() > 0) {
            $order = $order->first();
            $explode = explode('-', $order->invoice);
            $count = $explode[1] + 1;
            return 'INV-' . $count;
        }
        return 'INV-1';
    }

    public function index(Request $request){
    	//mengambil data dari customer
    	$customers = Customer::orderBy('name', 'ASC')->get();
    	//mengambil data user yang memiliki role kasir
    	$users = User::role('kasir')->orderBy('name', 'ASC')->get();
    	//mengambil data transaksi
    	$orders = Order::orderBy('created_at', 'DESC')->with('order_detail', 'customer');

    	//jika pelanggan dipilih pada combobox
    	if(!empty($request->customer_id)){
    		//maka ditambahkan where condition
    		$orders = $orders->where('customer_id', $request->customer_id);
    	}

    	//jika user / kasir dipilih pada combobox
    	if(!empty($request->user_id)){
    		//maka ditambahkan where condition
    		$orders = $orders->where('user_id', $request->user_id);
    	}

    	//jika start date & end date terisi
    	if(!empty($request->start_date) && !empty($request->end_date)){
    		//maka di-validasi dimana formatnya harus date
    		$this->validate($request, [
    			'start_date' => 'nullable|date',
    			'end_date' => 'nullable|date'
    		]);

    		//start_date dan end_date di re-format menjadi Y-m-d H:i:s
    		$start_date = Carbon::parse($request->start_date)->format('Y-m-d') . ' 00:00:01';
    		$end_date = Carbon::parse($request->end_date)->format('Y-m-d') . ' 23:59:59';

    		//ditambahkan whereBetween untuk mengambil data dengan range
    		$orders = $orders->whereBetween('created_at', [$start_date, $end_date])->get();
    	}else{
    		//jika start_date dan end_date kosong maka di-load 10 data terbaru
    		$orders = $orders->take(10)->skip(0)->get();
    	}

    	//menampilkan ke view
    	return view('orders.index', [
    		'orders' => $orders,
    		'sold' => $this->countItem($orders),
    		'total' => $this->countTotal($orders),
    		'total_customer' => $this->countCustomer($orders),
    		'customers' => $customers,
    		'users' => $users
    	]);
    }

    private function countCustomer($orders){
    	//array kosong didefinisikan
    	$customer = [];
    	//jika terdapat data yang akan ditampilkan
    	if($orders->count() > 0){
    		//di-looping untuk menyimpan email ke dalam array
    		foreach($orders as $row){
    			$customer[] = $row->customer->email;
    		}
    	}
    	//menghitung total data yang ada di dalam array
    	//dimana data yang duplicat akan dihapus menggunakan array_unique
    	return count(array_unique($customer));
    }

    private function countTotal($orders){
    	//default total bernilai 0
    	$total = 0;
    	//jika data ada
    	if($orders->count() > 0){
    		//mengambil nilai value dari total -> pluck() akan mengubahnya menjadi array
    		$sub_total = $orders->pluck('total')->all();
    		//kemudian data yang ada di dalam array dijumlahkan
    		$total = array_sum($sub_total);
    	}
    	return $total;
    }

    private function countItem($order){
    	//default data 0
    	$data = 0;
    	//jika data tersedia
    	if($order->count() > 0){
    		//di-looping
    		foreach($order as $row){
    			//untuk mengambil qty
    			$qty = $row->order_detail->pluck('qty')->all();
    			//kemudian qty dijumlahkan
    			$val = array_sum($qty);
    			$data += $val; 
    		}
    	}
    	return $data;
    }

    public function invoicePdf($invoice){
    	//mengambil data transaksi berdasarkan invoice
    	$order = Order::where('invoice', $invoice)
    		->with('customer', 'order_detail', 'order_detail.product')->first();

    	//set config PDF menggunakan Font sans-serif
    	//dengan me-load view invoice.blade.php
    	$pdf = PDF::setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif'])
    		->loadView('orders.report.invoice', compact('order'));
    	return $pdf->stream(); 
    }

    public function invoiceExcel($invoice){

    }

}
