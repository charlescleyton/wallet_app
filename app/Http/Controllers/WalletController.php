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
        if ($user->balance < 0) {
            return $this->errorResponse(
                'Transação não autorizada, seu saldo atual está negativo.',
                403
            );
        }
        try {
            $user = $this->updateBalance($user, amount: $depositRequest->amount);
            $transaction = $this->createTransaction(
                $user->id,
                $depositRequest->amount,
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

    public function transfer(TransferRequest $transferRequest)
    {
        $user = Auth::user();
        $targetUser = User::find($transferRequest->target_user_id);

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
            if ($user->balance < $transferRequest->amount) {

                return $this->errorResponse(
                    'Transação não autorizada, seu saldo é insuficiente para esta transferência.',
                    403
                );
            }
            $user = $this->deductBalance($user, amount: $transferRequest->amount);
            $targetUser = $this->updateBalance($targetUser, amount: $transferRequest->amount);

            $transaction = $this->createTransaction(
                $user->id,
                $transferRequest->amount,
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
