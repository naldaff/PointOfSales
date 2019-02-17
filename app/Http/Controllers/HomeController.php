<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Product;
use App\Customer;
use App\User;
use App\Order;
use DB;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $product = Product::count();
        $order = Order::count();
        $customer = Customer::count();
        $user = User::count();
        return view('home', compact('product', 'order', 'customer', 'user'));
    }

    //Method untuk meng-generate data order 7 hari terakhir
    public function getChart(){
        //mengambil tanggal 7 hari yang lalu dari tanggal hari ini
        $start = Carbon::now()->subWeek()->addDay()->format('Y-m-d') . ' 00:00:01';
        //mengambil tanggal hari ini
        $end = Carbon::now()->format('Y-m-d') . ' 23:59:00';

        //select data kapan records dibuat dan juga total pesanan
        $order = Order::select(DB::raw('date(created_at) as order_date'), DB::raw('count(*) as total_order'))
            //dengan kondisi antara tanggal yang ada di variabel $start dan $end
            ->whereBetween('created_at', [$start, $end])
            //kemudian di kelompokkan berdasarkan tanggal
            ->groupBy('created_at')
            ->get()->pluck('total_order', 'order_date')->all();

        //looping tanggal dengan interval seminggu terakhir
        for($i = Carbon::now()->subWeek()->addDay(); $i <= Carbon::now(); $i->addDay()){
            //jika data nya ada
            if(array_key_exists($i->format('Y-m-d'), $order)){
                //maka total pesanannya di-push dengan key tanggal
                $data[$i->format('Y-m-d')] = $order[$i->format('Y-m-d')];
            }else{
                //jika tidak, masukan niilai 0 
                $data[$i->format('Y-m-d')] = 0;
            }
        }

        return response()->json($data);
    }

}
