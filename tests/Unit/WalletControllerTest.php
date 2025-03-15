<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

# php artisan test --filter=WalletControllerTest
class WalletControllerTest extends TestCase
{
    use RefreshDatabase;
    # php artisan test --filter=walletControllerTest::test_statement_success
    public function test_statement_success()
    {
        $user = User::factory()->create([
            'cpf' => '33347854080'
        ]);
        $token = JWTAuth::fromUser($user);

        $transaction1 = Transaction::create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'type' => 'deposit',
            'status' => 'completed',
        ]);
        $transaction2 = Transaction::create([
            'user_id' => $user->id,
            'amount' => 50.00,
            'type' => 'transfer',
            'status' => 'completed',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/wallet/statement');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Extrato obtido com sucesso.',
                'saldo' => $user->balance,
            ])
            ->assertJsonCount(2, 'transaction');
    }

    # php artisan test --filter=walletControllerTest::test_statement_no_transactions
    public function test_statement_no_transactions()
    {
        $user = User::factory()->create([
            'cpf' => '12979698040'
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/wallet/statement');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Você não tem transações registradas.',
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_deposit_success
    public function test_deposit_success()
    {
        $user = User::factory()->create([
            'cpf' => '99939005083',
            'balance' => 0
        ]);
        $token = JWTAuth::fromUser($user);

        $data = ['amount' => 100.00];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/wallet/deposit', $data);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Deposito realizado com sucesso para ' . $user->name,
                'saldo' => $user->balance + $data['amount'],
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_deposit_negative_balance
    public function test_deposit_negative_balance()
    {
        $user = User::factory()->create([
            'cpf' => '99939005083',
            'balance' => -10
        ]);
        $token = JWTAuth::fromUser($user);

        $data = ['amount' => 100.00];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/wallet/deposit', $data);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Transação não autorizada, seu saldo atual está negativo.',
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_deposit_exception
    public function test_deposit_exception()
    {
        $user = User::factory()->create([
            'cpf' => '94459532077',
            'balance' => 0
        ]);
        $token = JWTAuth::fromUser($user);

        \DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database error'));

        $data = ['amount' => 100.00];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/wallet/deposit', $data);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Database error',
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_transfer_success
    public function test_transfer_success()
    {
        $user = User::factory()->create([
            'cpf' => '99939005083',
            'balance' => 200
        ]);
        $targetUser = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $data = ['target_user_id' => $targetUser->id, 'amount' => 100];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/wallet/transfer', $data);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transferência realizada com sucesso',
                'saldo' => $user->balance - $data['amount'],
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_transfer_insufficient_balance
    public function test_transfer_insufficient_balance()
    {
        $user = User::factory()->create([
            'balance' => 50,
            'cpf' => '04451230012'
        ]);
        $targetUser = User::factory()->create([
            'cpf' => '41049342089'
        ]);
        $token = JWTAuth::fromUser($user);

        $data = ['target_user_id' => $targetUser->id, 'amount' => 100];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/wallet/transfer', $data);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Transação não autorizada, seu saldo é insuficiente para esta transferência.',
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_transfer_user_not_found
    public function test_transfer_user_not_found()
    {
        $user = User::factory()->create([
            'cpf' => '17537648077'
        ]);
        $token = JWTAuth::fromUser($user);

        $data = [
            'target_user_id' => 9999,
            'amount' => 50.00
        ];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/wallet/transfer', $data);

        $response->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'target_user_id' => [
                        'O campo target_user_id deve ser um usuário existente'
                    ]
                ]
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_transfer_exception
    public function test_transfer_exception()
    {
        $user = User::factory()->create(
            ['cpf' => '27489708028']
        );
        $token = JWTAuth::fromUser($user);

        \DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database error'));

        $data = [
            'target_user_id' => 9999,
            'amount' => 50.00
        ];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/wallet/transfer', $data);

        $response->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'target_user_id' => [
                        'O campo target_user_id deve ser um usuário existente'
                    ]
                ]
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_reverse_transaction_success
    public function test_reverse_transaction_success()
    {
        $user = User::factory()->create([
            'cpf' => '46389643039'
        ]);
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'type' => 'deposit',
            'status' => 'completed',
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/wallet/reverse/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transação revertida com sucesso!',
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_reverse_transaction_permission_error
    public function test_reverse_transaction_permission_error()
    {
        $user = User::factory()->create([
            'cpf' => '62179501030'
        ]);
        $otherUser = User::factory()->create([
            'cpf' => '79542554095'
        ]);
        $transaction = Transaction::create([
            'user_id' => $otherUser->id,
            'amount' => 100.00,
            'type' => 'deposit',
            'status' => 'completed',
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/wallet/reverse/{$transaction->id}");

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Você não tem permissão para reverter esta transação!',
            ]);
    }

    # php artisan test --filter=walletControllerTest::test_reverse_transaction_invalid
    public function test_reverse_transaction_invalid()
    {
        $user = User::factory()->create([
            'cpf' => '83651164055'
        ]);
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'type' => 'deposit',
            'status' => 'reversed',
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/wallet/reverse/{$transaction->id}");

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Transação já revertida ou identificador inválido!',
            ]);
    }
}
