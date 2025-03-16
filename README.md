# API Carteira Financeira - WalletApi

#### Projeto desafio para a vaga de desenvolvedor Backend PHP para o Grupo Adriano Cobuccio

## Visão Geral

Este projeto é uma aplicação de carteira digital (wallet) desenvolvida com Laravel, utilizando Laravel Sail para ambiente de desenvolvimento com Docker e Laravel Telescope para observabilidade e depuração.

### Tecnologias Necessárias para instalação da API

-   Docker
-   Docker Compose
-   Git

## Configuração do Ambiente de Desenvolvimento

### Instalação

#### Clone o repositório:

```
git clone https://github.com/charlescleyton/wallet_app.git

```

#### Navegue até o diretório do projeto:

```
cd wallet_app
```

#### Copie o arquivo de ambiente:

```
cp .env.example .env
```

#### Edite o arquivo .env para configurar as variáveis do banco de dados:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=wallet_db
DB_USERNAME=sail
DB_PASSWORD=password
```

### Instale as dependencias:

```
composer update
```

### Inicie o ambiente Docker com Laravel Sail:

```
./vendor/bin/sail up -d
```

### Gere a chave da aplicação:

```
./vendor/bin/sail artisan key:generate
```

### Execute as migrações do banco de dados:

```
./vendor/bin/sail artisan migrate
```

# Cadastro e autenticação de usuários

O cadastro e autenticação de usuário atendem às boas práticas de validação e segurança. A validação de dados é realizada para assegurar a integridade das informações e prever erros de input, como campos obrigatórios ou formatos inválidos.

## Endpoints e Payloads para a API

### Registro e autenticação de Usuário

#### POST /api/auth/register

Payload:

```
{
  "name": "John Doe",
  "email": "john@example.com",
  "cpf": "99999999999",
  "password": "Password#123",
}
```

Resposta esperada:

```
{
  "id": 1,
  "name": "John Doe",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

### Login

#### POST /api/auth/login

Payload:

```
{
  "email": "john@example.com",
  "password": "Password#123"
}
```

Resposta esperada:

```
{
  "id": 1,
  "name": "John Doe",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

## Para todas as ações a partir desta será necessário o uso da Autorization Bearer Token

### Logout

#### POST /api/auth/logout

Headers:

```
Authorization: Bearer {token}
```

Resposta esperada:

```
{
  "message": "Logout realizado com sucesso, obrigado por usar nossa API."
}
```

## Gerenciamento de carteiras

-   Transações:
    -   Realizar Depósito;
    -   Realizar transferência;
    -   Reversão de transação.
-   Extrato das Transações do usuário.

## Transações

### Realizar Depósito

#### POST /api/wallet/deposit

Payload:

```
{
  "amount": 500.00
}
```

Resposta esperada:

```
{
    "message": "Deposito realizado com sucesso para Silvanio Saldanha",
    "transaction": {
        "user_id": 1,
        "target_user_id": null,
        "amount": 500,
        "type": "deposit",
        "status": "completed",
        "updated_at": "2025-03-16T14:54:29.000000Z",
        "created_at": "2025-03-16T14:54:29.000000Z",
        "id": 2
    },
    "saldo": 500
}
```

### Realizar Transferência

#### POST /api/wallet/transfer

Payload:

```
{
  "amount": 100,
  "target_user_id": 2
}
```

Resposta esperada:

```
{
    "message": "Transferência realizada com sucesso",
    "transaction": {
        "user_id": 1,
        "target_user_id": 2,
        "amount": 100,
        "type": "transfer",
        "status": "completed",
        "updated_at": "2025-03-16T15:01:40.000000Z",
        "created_at": "2025-03-16T15:01:40.000000Z",
        "id": 3
    },
    "saldo": 400
}
```

### Reversão de transação.

#### POST /api/wallet/reverse/idTransaction

Resposta esperada:

```
{
    "message": "Transação revertida com sucesso!",
    "transaction": "transfer",
    "saldo": 500
}
```

### Extrato das Transações do usuário.

#### GET /api/wallet/statement

Resposta esperada:

```
{
    "message": "Extrato obtido com sucesso.",
    "transaction": [
        {
            "id": 2,
            "user_id": 1,
            "target_user_id": 2,
            "amount": "100.00",
            "type": "transfer",
            "status": "reversed",
            "created_at": "2025-03-16T15:01:40.000000Z",
            "updated_at": "2025-03-16T15:06:53.000000Z"
        },
        {
            "id": 1,
            "user_id": 1,
            "target_user_id": null,
            "amount": "500.00",
            "type": "deposit",
            "status": "completed",
            "created_at": "2025-03-16T14:57:18.000000Z",
            "updated_at": "2025-03-16T14:57:18.000000Z"
        },
    ]
}
```

# Utilizando o Laravel Sail

O Laravel Sail é uma interface de linha de comando leve para interagir com o ambiente Docker do Laravel. Abaixo estão alguns comandos úteis:

### Iniciar os contêineres Docker:

```
./vendor/bin/sail up -d
```

### Parar os contêineres Docker:

```
./vendor/bin/sail down
```

### Executar comandos Artisan:

```
./vendor/bin/sail artisan [comando]
```

### Executar comandos Composer:

```
./vendor/bin/sail composer [comando]
```

# Laravel Telescope

O Laravel Telescope está configurado neste projeto para fornecer observabilidade e facilitar a depuração.

### Acessando o Telescope

Após iniciar o ambiente com Sail, você pode acessar o Telescope através da URL:

http://localhost/telescope

Recursos do Telescope
O Telescope oferece monitoramento para:

-   Requisições HTTP
-   Comandos Artisan
-   Consultas SQL
-   Eventos
-   Logs
-   Notificações
-   E-mails
-   Cache
-   Tarefas agendadas
-   Dumps de variáveis

# Comandos Úteis para Desenvolvimento

## Limpar cache:

```
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
```

# Executar testes:

```
./vendor/bin/sail artisan test
```

# Solução de Problemas

## Erro de permissão:

Se encontrar erros de permissão, execute:

```
chmod -R 777 storage bootstrap/cache
```

## Problemas com o Docker:

Se os contêineres não iniciarem corretamente, tente:

```
./vendor/bin/sail down --rmi all -v
./vendor/bin/sail up -d
```

# Problemas com o Telescope:

Se o Telescope não estiver funcionando corretamente, verifique se está habilitado no arquivo .env:

```
TELESCOPE_ENABLED=true
```

E execute:

```
./vendor/bin/sail artisan telescope:install
./vendor/bin/sail artisan migrate
```

Contato e Suporte
Para suporte ou dúvidas sobre o projeto, entre em contato com [charles.pereira.ti@gmail.com].

Esta documentação foi gerada para o projeto Wallet App e pode ser atualizada conforme o desenvolvimento do projeto avança.
