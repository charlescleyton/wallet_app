<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function deposit(DepositRequest $depositrequest)
    {
        $user = Auth::user();
        DB::beginTransaction();

        try {
            $user = $this->updateBalance($user, amount: $depositrequest->amount);
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $depositrequest->amount,
                'type' => 'deposit',
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Deposito realizado com sucesso para ' . $user->name,
                    'transação' => $transaction,
                    'saldo Total' => $user->balance,
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'error' => 'Falha no depósito!'
                ],
                401
            );
        }
    }

    private function updateBalance(User $user, float $amount)
    {
        $user->balance += $amount;
        $user->save();
        return $user;
    }
}
