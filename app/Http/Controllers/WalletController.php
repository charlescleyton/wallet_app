<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletDepositRequest;
use App\Http\Requests\WalletTransferRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{


    public function statement(Request $request)
    {
        $user = Auth::user();

        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($transactions->isEmpty()) {
            return $this->errorResponse(
                'Você não tem transações registradas.',
                404
            );
        }

        return $this->successResponse(
            'Extrato obtido com sucesso.',
            $transactions,
            $user->balance
        );
    }


    public function deposit(WalletDepositRequest $walletDepositRequest)
    {
        $user = Auth::user();
        DB::beginTransaction();
        if ($user->balance < 0) {
            return $this->errorResponse(
                'Transação não autorizada, seu saldo atual está negativo.',
                403
            );
        }
        try {
            $user = $this->updateBalance($user, amount: $walletDepositRequest->amount);
            $transaction = $this->createTransaction(
                $user->id,
                $walletDepositRequest->amount,
                'deposit'
            );

            DB::commit();

            return $this->successResponse(
                'Deposito realizado com sucesso para ' . $user->name,
                $transaction,
                $user->balance,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Falha no depósito!',
                401
            );
        }
    }

    public function transfer(WalletTransferRequest $walletDepositRequest)
    {
        $user = Auth::user();
        $targetUser = User::find($walletDepositRequest->target_user_id);

        if (!$targetUser) {
            return $this->errorResponse(
                'Usuário alvo não encontrado!',
                404
            );
        }
        if ($user->balance < 0) {
            return $this->errorResponse(
                'Transação não autorizada. Seu saldo atual está negativo.',
                403
            );
        }

        DB::beginTransaction();

        try {
            if ($user->balance < $walletDepositRequest->amount) {

                return $this->errorResponse(
                    'Transação não autorizada, seu saldo é insuficiente para esta transferência.',
                    403
                );
            }
            $user = $this->deductBalance($user, amount: $walletDepositRequest->amount);
            $targetUser = $this->updateBalance($targetUser, amount: $walletDepositRequest->amount);

            $transaction = $this->createTransaction(
                $user->id,
                $walletDepositRequest->amount,
                'transfer',
                $targetUser->id
            );

            DB::commit();

            return $this->successResponse(
                'Transferência realizada com sucesso',
                $transaction,
                $user->balance,
            );
        } catch (\Exception $e) {

            DB::rollBack();

            return $this->errorResponse(
                'Falha na transferência',
                500
            );
        }
    }

    public function reverseTransaction($transactionId)
    {
        $transaction = Transaction::find($transactionId);
        if (!$transaction || $transaction->status !== 'completed') {
            return $this->errorResponse(
                'Transação já revertida ou identificador inválido!',
                400
            );
        }

        if (Auth::id() !== $transaction->user_id) {
            return $this->errorResponse(
                'Você não tem permissão para reverter esta transação!',
                403
            );
        }

        DB::beginTransaction();

        try {
            $user = User::find($transaction->user_id);

            if ($transaction->type == 'deposit') {
                $this->deductBalance($user, amount: $transaction->amount);
            } elseif ($transaction->type == 'transfer') {
                $targetUser = User::find($transaction->target_user_id);
                $this->deductBalance(user: $targetUser, amount: $transaction->amount);
                $this->updateBalance(user: $user, amount: $transaction->amount);
                $targetUser->save();
            }

            $user->save();

            $transaction->status = 'reversed';
            $transaction->save();

            DB::commit();

            return $this->successResponse(
                'Transação revertida com sucesso!',
                $transaction->type,
                $user->balance,
            );
        } catch (\Exception $e) {

            DB::rollBack();

            return $this->errorResponse(
                'Falha na reversão!',
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

    public function deductBalance(User $user, float $amount)
    {
        $user->balance -= $amount;
        $user->save();
        return $user;
    }

    private function createTransaction(
        int $userId,
        float $amount,
        string $type,
        ?int $targetUserId = null
    ): Transaction {
        return Transaction::create([
            'user_id' => $userId,
            'target_user_id' => $targetUserId,
            'amount' => $amount,
            'type' => $type,
            'status' => 'completed',
        ]);
    }

    private function successResponse(string $message, $transaction = null, float $balance)
    {
        return response()->json([
            'message' => $message,
            'transaction' => $transaction,
            'saldo' => $balance,
        ], 200);
    }

    private function errorResponse(string $message, int $statusCode)
    {
        return response()->json(
            [
                'error' => $message
            ],
            $statusCode
        );
    }
}
