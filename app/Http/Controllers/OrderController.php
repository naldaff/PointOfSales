<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Order;
use App\Order_detail;
use App\Customer;
use Cookie;
use DB;

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

}
