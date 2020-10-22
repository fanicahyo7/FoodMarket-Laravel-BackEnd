<?php

namespace App\Http\Controllers\API;

use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request){
        // variabel
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        if($id)
        {
            $transaction = Transactions::with(['food','user'])->find($id);

            if($transaction){
                return ResponseFormatter::success($transaction,'Data transaksi Berhasil terambil');
            }
            else{
                return ResponseFormatter::error(null,'data transaksi tidak ada',404);
            }
        }

        $transaction = Transactions::with(['food','user'])->where('user_id',Auth::user()->id);

            if($food_id){
                $transaction->where('food_id',$food_id);
            }

            if($status){
                $transaction->where('status',$status);
            }

            return ResponseFormatter::success($food->paginate($limit),'Data list transaksi berhasil diambil');
    }

    public function update(Request $request, $id){
        $transaction = Transactions::fingOrFail($id);

        $transaction->update($request->all());

        return ResponseFormatter::success($transaction,'Data Transaksi berhasil diubah');
    }

    public function checkout(Request $request){
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required'
        ]);

        $transaction = Transactions::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => ''
        ]);

        //konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanizited = config('services.midtrans.isSanizited');
        Config::$is3ds = config('services.midtrans.is3ds');

        //panggil transaksi yg sudah dibuat
        $transaction = Transactions::with(['food','user'])->find($transaction->id);

        //membuat transaksi midtrans
        $midtrans = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email
            ],
            'enabled_payments' => ['gopay', 'bank_transfer'],
            'vtweb' => []
        ];

        // memanggil midtrans
        try{
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;

            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            return ResponseFormatter::success($transaction,'Transaksi Berhasil');
        }
        catch(Exception $e){
            return ResponseFormatter::error($e->getMessage(),'Transaksi gagal');
        }
    }
}
