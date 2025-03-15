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
            return response()->json(
                [
                    'message' => 'Depósito não realizado. Seu saldo atual está negativo. Por favor, entre em contato com o suporte para regularizar sua situação antes de realizar novos depósitos'
                ],
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

        if (!$targetUser) {
            return response()->json(['error' => 'Usuário alvo não encontrado!'], 404);
        }
        if ($user->balance < 0) {
            return response()->json(
                [
                    'message' => "Transferência não realizada. Seu saldo atual está negativo. Por favor, regularize sua situação antes de realizar novas transferências. Entre em contato com o suporte para mais informações."
                ],
                403
            );
        }

        DB::beginTransaction();

        try {
            if ($user->balance < $transferRequest->amount) {
                return response()->json(['error' => 'Transferência não realizada, seu saldo é insuficiente!'], 400);
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

            return response()->json(
                [
                    'error' => 'Falha na transferência'
                ],
                500
            );
        }
    }

    public function reverseTransaction($transactionId)
    {
        $transaction = Transaction::find($transactionId);
        if (!$transaction || $transaction->status !== 'completed') {
            return response()->json(['error' => 'Transação já revertida ou inválida!'], 400);
        }

        if (Auth::id() !== $transaction->user_id) {
            return response()->json(['error' => 'Você não tem permissão para reverter esta transação!'], 403);
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

            return response()->json(
                [
                    'message' => 'Transação revertida com sucesso!',
                    'saldo' => $user->balance,
                ],
                200
            );
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json(
                [
                    'error' => 'Falha na reversão!'
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
}
