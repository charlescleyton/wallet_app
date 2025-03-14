<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function deposit(DepositRequest $depositRequest)
    {
        $user = Auth::user();
        DB::beginTransaction();

        try {
            $user = $this->updateBalance($user, amount: $depositRequest->amount);
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $depositRequest->amount,
                'type' => 'deposit',
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Deposito realizado com sucesso para ' . $user->name,
                    'transação' => $transaction,
                    'saldo' => $user->balance,
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

    public function transfer(TransferRequest $transferRequest)
    {
        $user = Auth::user();
        $targetUser = User::find($transferRequest->target_user_id);

        if ($user->balance < $transferRequest->amount) {
            return response()->json(['error' => 'Saldo insuficiente!'], 400);
        }

        DB::beginTransaction();

        try {
            $user = $this->deductBalance($user, amount: $transferRequest->amount);
            $targetUser = $this->updateBalance($targetUser, amount: $transferRequest->amount);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'target_user_id' => $targetUser->id,
                'amount' => $transferRequest->amount,
                'type' => 'transfer',
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Transferência realizada com sucesso',
                    'transaction' => $transaction,
                    'saldo' => $user->balance,
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Falha na transferência'], 500);
        }
    }

    private function updateBalance(User $user, float $amount)
    {
        $user->balance += $amount;
        $user->save();
        return $user;
    }

    public function deductBalance(User $user, float $amount)
    {
        $user->balance -= $amount;
        $user->save();
        return $user;
    }
}
