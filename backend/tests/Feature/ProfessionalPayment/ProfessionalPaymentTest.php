<?php

declare(strict_types=1);

use App\Core\Database\DatabaseManager;
use Illuminate\Database\Capsule\Manager as DB;
use Tests\Support\ApiRequester;
use Tests\Support\PersonApi;
use Tests\Support\ProfessionalPaymentApi;

beforeEach(function (): void {
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = ':memory:';
    $_ENV['JWT_SECRET'] = 'test-secret';
    $_ENV['JWT_TTL'] = '3600';

    DatabaseManager::reset();
    require dirname(__DIR__, 3) . '/bootstrap.php';
    require dirname(__DIR__, 3) . '/database/migrations/run.php';
    require dirname(__DIR__, 3) . '/database/seeders/run.php';
});

it('criar tabela válida', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela A',
        'calculation_type' => 'fixed_per_attendance',
        'default_fixed_amount' => 150.25,
        'default_percentage' => 35.5,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(201);
    $uuid = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    expect((int) DB::table('payment_tables')->where('uuid', $uuid)->value('default_fixed_amount'))->toBe(15025);
});

it('bloquear tabela sem nome', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'calculation_type' => 'fixed_per_attendance',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('bloquear calculation_type inválido', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela A',
        'calculation_type' => 'invalid',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('editar tabela', function (): void {
    $token = PersonApi::tokenFor();
    $create = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela A',
        'calculation_type' => 'fixed_monthly',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('PUT', '/api/v1/payment-tables/' . $uuid, [
        'name' => 'Tabela A2',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(200);
});

it('soft delete de tabela', function (): void {
    $token = PersonApi::tokenFor();
    $create = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela A',
        'calculation_type' => 'fixed_monthly',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('DELETE', '/api/v1/payment-tables/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
    expect(DB::table('payment_tables')->where('uuid', $uuid)->value('deleted_at'))->not->toBeNull();
});

it('bloquear acesso sem permissão', function (): void {
    $token = PersonApi::createLimitedUser('no-pay', []);

    $res = ApiRequester::call('GET', '/api/v1/payment-tables', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(403);
});

it('auditoria registra criação edição exclusão de tabela', function (): void {
    $token = PersonApi::tokenFor();

    $create = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela Audit',
        'calculation_type' => 'fixed_monthly',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $uuid = json_decode($create['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('PUT', '/api/v1/payment-tables/' . $uuid, ['description' => 'Atualizou'], [
        'Authorization' => 'Bearer ' . $token,
    ]);
    ApiRequester::call('DELETE', '/api/v1/payment-tables/' . $uuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $events = DB::table('audit_logs')->pluck('event')->all();
    expect($events)->toContain('payment_tables.created');
    expect($events)->toContain('payment_tables.updated');
    expect($events)->toContain('payment_tables.deleted');
});

it('criar item válido', function (): void {
    $token = PersonApi::tokenFor();
    $table = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela Itens',
        'calculation_type' => 'fixed_per_attendance',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);
    $tableUuid = json_decode($table['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/payment-tables/' . $tableUuid . '/items', [
        'fixed_value' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(201);
    $itemUuid = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    expect((int) DB::table('payment_table_items')->where('uuid', $itemUuid)->value('fixed_value'))->toBe(5000);
});

it('bloquear item com valor negativo', function (): void {
    $token = PersonApi::tokenFor();
    $table = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela Itens',
        'calculation_type' => 'fixed_per_attendance',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);
    $tableUuid = json_decode($table['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/payment-tables/' . $tableUuid . '/items', [
        'fixed_value' => -1,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('bloquear percentage fora de 0 a 100', function (): void {
    $token = PersonApi::tokenFor();
    $table = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela Itens',
        'calculation_type' => 'fixed_per_attendance',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);
    $tableUuid = json_decode($table['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('POST', '/api/v1/payment-tables/' . $tableUuid . '/items', [
        'percentage' => 120,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('bloquear item para tabela inexistente', function (): void {
    $res = ApiRequester::call('POST', '/api/v1/payment-tables/' . \App\Core\Support\Uuid::v4() . '/items', [
        'fixed_value' => 10,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(404);
});

it('editar item', function (): void {
    $token = PersonApi::tokenFor();
    $table = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela Itens',
        'calculation_type' => 'fixed_per_attendance',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);
    $tableUuid = json_decode($table['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $item = ApiRequester::call('POST', '/api/v1/payment-tables/' . $tableUuid . '/items', [
        'fixed_value' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);
    $itemUuid = json_decode($item['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('PUT', '/api/v1/payment-table-items/' . $itemUuid, [
        'fixed_value' => 55,
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(200);
});

it('soft delete de item', function (): void {
    $token = PersonApi::tokenFor();
    $table = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela Itens',
        'calculation_type' => 'fixed_per_attendance',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);
    $tableUuid = json_decode($table['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $item = ApiRequester::call('POST', '/api/v1/payment-tables/' . $tableUuid . '/items', [
        'fixed_value' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);
    $itemUuid = json_decode($item['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    $res = ApiRequester::call('DELETE', '/api/v1/payment-table-items/' . $itemUuid, [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(200);
    expect(DB::table('payment_table_items')->where('uuid', $itemUuid)->value('deleted_at'))->not->toBeNull();
});

it('atribuir configuração fixa por atendimento', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678911', 'profpay11@clinica.local');

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_per_attendance',
        'fixed_per_attendance_amount' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(201);
});

it('atribuir configuração fixa mensal', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678912', 'profpay12@clinica.local');

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_monthly',
        'fixed_monthly_amount' => 6500,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(201);
    $configUuid = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];
    expect((int) DB::table('professional_payment_configs')->where('uuid', $configUuid)->value('fixed_monthly_amount'))
        ->toBe(650000);
});

it('atribuir configuração híbrida', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678913', 'profpay13@clinica.local');

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'hybrid',
        'hybrid_base_amount' => 3000,
        'hybrid_threshold_quantity' => 100,
        'hybrid_extra_amount_per_attendance' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(201);
});

it('bloquear profissional inexistente', function (): void {
    $res = ApiRequester::call(
        'POST',
        '/api/v1/professionals/' . \App\Core\Support\Uuid::v4() . '/payment-configs',
        [
            'payment_mode' => 'fixed_monthly',
            'fixed_monthly_amount' => 6500,
            'effective_start_date' => '2026-01-01',
        ],
        ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]
    );

    expect($res['status'])->toBe(404);
});

it('bloquear valores negativos', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678914', 'profpay14@clinica.local');

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_monthly',
        'fixed_monthly_amount' => -1,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . PersonApi::tokenFor()]);

    expect($res['status'])->toBe(422);
});

it('bloquear duas configurações ativas vigentes para o mesmo profissional na mesma data', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678915', 'profpay15@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_monthly',
        'fixed_monthly_amount' => 6500,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_monthly',
        'fixed_monthly_amount' => 7000,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(409);
});

it('resolver regra vigente por data', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678916', 'profpay16@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_monthly',
        'fixed_monthly_amount' => 6500,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call(
        'GET',
        '/api/v1/professionals/' . $professionalUuid . '/payment-rule?date=2026-02-01',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );

    expect($res['status'])->toBe(200);
});

it('gerar snapshot da regra vigente', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678917', 'profpay17@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_monthly',
        'fixed_monthly_amount' => 6500,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call(
        'GET',
        '/api/v1/professionals/' . $professionalUuid . '/payment-rule?date=2026-02-01',
        [],
        ['Authorization' => 'Bearer ' . $token]
    );

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($json['data']['snapshot']['payment_config_uuid'])->not->toBeEmpty();
    expect($json['data']['snapshot']['generated_at'])->not->toBeEmpty();
});

it('simular fixo por atendimento', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678918', 'profpay18@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_per_attendance',
        'fixed_per_attendance_amount' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'reference_month' => '2026-02',
        'attendances_count' => 10,
    ], ['Authorization' => 'Bearer ' . $token]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect((float) $json['data']['total_amount'])->toBe(500.0);
    expect($json['data']['financial_generated'])->toBeFalse();
    expect($json['data']['calculation_memory'])->not->toBeEmpty();
});

it('simular fixo por atendimento usando valor resolvido da tabela sem exigir bruto', function (): void {
    $token = PersonApi::tokenFor();
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678924', 'profpay24@clinica.local');

    $table = ApiRequester::call('POST', '/api/v1/payment-tables', [
        'name' => 'Tabela Simulação',
        'calculation_type' => 'fixed_per_attendance',
        'default_fixed_amount' => 80,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);
    $tableUuid = json_decode($table['body'], true, flags: JSON_THROW_ON_ERROR)['data']['uuid'];

    ApiRequester::call('POST', '/api/v1/payment-tables/' . $tableUuid . '/items', [
        'fixed_value' => 120,
        'appointment_type' => 'terapia_individual',
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_per_attendance',
        'payment_table_uuid' => $tableUuid,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'simulation_type' => 'single_attendance',
        'reference_month' => '2026-02',
        'appointment_type' => 'terapia_individual',
    ], ['Authorization' => 'Bearer ' . $token]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect((float) $payload['data']['total_amount'])->toBe(120.0);
    expect($payload['data']['rule_resolution']['payment_table_uuid'])->toBe($tableUuid);
    expect($payload['data']['rule_resolution']['payment_table_item'])->not->toBeNull();
});

it('simular fixo mensal', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678919', 'profpay19@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_monthly',
        'fixed_monthly_amount' => 6500,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'reference_month' => '2026-02',
        'attendances_count' => 10,
    ], ['Authorization' => 'Bearer ' . $token]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect((float) $json['data']['total_amount'])->toBe(6500.0);
});

it('simular híbrido com quantidade abaixo do limite', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678920', 'profpay20@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'hybrid',
        'hybrid_base_amount' => 3000,
        'hybrid_threshold_quantity' => 100,
        'hybrid_extra_amount_per_attendance' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'reference_month' => '2026-02',
        'attendances_count' => 80,
    ], ['Authorization' => 'Bearer ' . $token]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect((float) $json['data']['total_amount'])->toBe(3000.0);
});

it('simular híbrido com quantidade acima do limite', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678921', 'profpay21@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'hybrid',
        'hybrid_base_amount' => 3000,
        'hybrid_threshold_quantity' => 100,
        'hybrid_extra_amount_per_attendance' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'reference_month' => '2026-02',
        'attendances_count' => 120,
    ], ['Authorization' => 'Bearer ' . $token]);

    $json = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect((float) $json['data']['total_amount'])->toBe(4000.0);
});

it('permite valor manual apenas como override opcional com justificativa', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678925', 'profpay25@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_per_attendance',
        'fixed_per_attendance_amount' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'simulation_type' => 'period_attendances',
        'reference_month' => '2026-02',
        'attendances_count' => 2,
        'manual_base_amount' => 100,
        'manual_override_reason' => 'Cenário excepcional de teste',
    ], ['Authorization' => 'Bearer ' . $token]);

    $payload = json_decode($res['body'], true, flags: JSON_THROW_ON_ERROR);
    expect($res['status'])->toBe(200);
    expect((float) $payload['data']['total_amount'])->toBe(200.0);
    expect($payload['data']['manual_override']['enabled'])->toBeTrue();
});

it('bloqueia valor manual sem justificativa', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678926', 'profpay26@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_per_attendance',
        'fixed_per_attendance_amount' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'simulation_type' => 'single_attendance',
        'reference_month' => '2026-02',
        'manual_base_amount' => 100,
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(422);
});

it('bloquear simulação sem permissão', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678922', 'profpay22@clinica.local');
    $token = PersonApi::createLimitedUser('nosim', []);

    $res = ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'reference_month' => '2026-02',
        'attendances_count' => 10,
    ], ['Authorization' => 'Bearer ' . $token]);

    expect($res['status'])->toBe(403);
});

it('auditoria registra simulação', function (): void {
    $professionalUuid = ProfessionalPaymentApi::createProfessional('12345678923', 'profpay23@clinica.local');
    $token = PersonApi::tokenFor();

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/payment-configs', [
        'payment_mode' => 'fixed_per_attendance',
        'fixed_per_attendance_amount' => 50,
        'effective_start_date' => '2026-01-01',
    ], ['Authorization' => 'Bearer ' . $token]);

    ApiRequester::call('POST', '/api/v1/professionals/' . $professionalUuid . '/simulate-payout', [
        'reference_month' => '2026-02',
        'attendances_count' => 10,
    ], ['Authorization' => 'Bearer ' . $token]);

    expect(DB::table('audit_logs')->pluck('event')->all())->toContain('professional_payment.simulated');
});

it('perfil profissional clínico não consegue acessar endpoints de configuração de pagamento', function (): void {
    $token = ProfessionalPaymentApi::createProfessionalClinicoUser();

    $res = ApiRequester::call('GET', '/api/v1/payment-tables', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    expect($res['status'])->toBe(403);
});
