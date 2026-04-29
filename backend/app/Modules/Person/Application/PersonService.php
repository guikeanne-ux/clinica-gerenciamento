<?php

declare(strict_types=1);

namespace App\Modules\Person\Application;

use App\Core\Exceptions\HttpException;
use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Person\Infrastructure\Models\Patient;
use App\Modules\Person\Infrastructure\Models\PatientResponsible;
use App\Modules\Person\Infrastructure\Models\Professional;
use App\Modules\Person\Infrastructure\Models\Supplier;
use Illuminate\Database\Capsule\Manager as DB;

final class PersonService
{
    public function __construct(private readonly AuditService $audit = new AuditService())
    {
    }

    public function listPatients(array $query): array
    {
        $qb = Patient::query()->whereNull('deleted_at');
        return $this->applyListFilters($qb, $query, ['full_name', 'cpf', 'email', 'phone_primary']);
    }

    public function createPatient(array $data, string $actor): Patient
    {
        $this->validatePatient($data);
        $this->assertUnique('patients', 'cpf', $data['cpf'] ?? null);

        $patient = Patient::query()->create(array_merge($data, [
            'uuid' => Uuid::v4(),
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));

        $this->audit->log('patients.created', $actor, ['patient_uuid' => $patient->uuid]);
        return $patient;
    }

    public function getPatient(string $uuid): Patient
    {
        /** @var Patient|null $patient */
        $patient = Patient::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($patient === null) {
            throw new HttpException('Paciente não encontrado.', 404);
        }

        return $patient;
    }

    public function updatePatient(string $uuid, array $data, string $actor): Patient
    {
        $patient = $this->getPatient($uuid);
        $this->validatePatient(array_merge($patient->toArray(), $data));

        if (array_key_exists('cpf', $data)) {
            $this->assertUnique('patients', 'cpf', $data['cpf'], $uuid);
            $this->audit->log('patients.cpf_changed', $actor, ['patient_uuid' => $uuid]);
        }

        $patient->fill($data);
        $patient->updated_at = date('Y-m-d H:i:s');
        $patient->save();

        $this->audit->log('patients.updated', $actor, ['patient_uuid' => $uuid]);
        return $patient;
    }

    public function deletePatient(string $uuid, string $actor): void
    {
        $patient = $this->getPatient($uuid);
        $patient->status = 'inactive';
        $patient->deleted_at = date('Y-m-d H:i:s');
        $patient->save();
        $this->audit->log('patients.deleted', $actor, ['patient_uuid' => $uuid]);
    }

    public function listResponsibles(string $patientUuid): array
    {
        $this->getPatient($patientUuid);
        return PatientResponsible::query()
            ->where('patient_uuid', $patientUuid)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function createResponsible(string $patientUuid, array $data, string $actor): PatientResponsible
    {
        $this->getPatient($patientUuid);
        if (($data['name'] ?? '') === '') {
            throw new HttpException('Nome do responsável é obrigatório.', 422);
        }
        $this->assertValidCpf($data['cpf'] ?? null);
        $this->assertValidEmail($data['email'] ?? null);
        $this->assertValidPhone($data['phone'] ?? null);

        $resp = PatientResponsible::query()->create(array_merge($data, [
            'uuid' => Uuid::v4(),
            'patient_uuid' => $patientUuid,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));

        $this->audit->log(
            'patient_responsibles.created',
            $actor,
            ['responsible_uuid' => $resp->uuid, 'patient_uuid' => $patientUuid]
        );

        return $resp;
    }

    public function updateResponsible(string $uuid, array $data, string $actor): PatientResponsible
    {
        /** @var PatientResponsible|null $resp */
        $resp = PatientResponsible::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($resp === null) {
            throw new HttpException('Responsável não encontrado.', 404);
        }

        $this->assertValidCpf($data['cpf'] ?? $resp->cpf ?? null);
        $this->assertValidEmail($data['email'] ?? $resp->email ?? null);
        $this->assertValidPhone($data['phone'] ?? $resp->phone ?? null);

        $resp->fill($data);
        $resp->updated_at = date('Y-m-d H:i:s');
        $resp->save();
        $this->audit->log('patient_responsibles.updated', $actor, ['responsible_uuid' => $uuid]);
        return $resp;
    }

    public function deleteResponsible(string $uuid, string $actor): void
    {
        /** @var PatientResponsible|null $resp */
        $resp = PatientResponsible::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($resp === null) {
            throw new HttpException('Responsável não encontrado.', 404);
        }

        $resp->deleted_at = date('Y-m-d H:i:s');
        $resp->save();
        $this->audit->log('patient_responsibles.deleted', $actor, ['responsible_uuid' => $uuid]);
    }

    public function listProfessionals(array $query): array
    {
        $qb = Professional::query()->whereNull('deleted_at');
        return $this->applyListFilters($qb, $query, ['full_name', 'cpf', 'email', 'phone']);
    }

    public function createProfessional(array $data, string $actor): Professional
    {
        if (($data['full_name'] ?? '') === '') {
            throw new HttpException('Nome do profissional é obrigatório.', 422);
        }
        $this->assertUnique('professionals', 'cpf', $data['cpf'] ?? null);
        $this->assertUnique('professionals', 'email', $data['email'] ?? null);
        $this->assertValidCpf($data['cpf'] ?? null);
        $this->assertValidEmail($data['email'] ?? null);
        $this->assertValidPhone($data['phone'] ?? null);

        $scheduleColor = $data['schedule_color'] ?? sprintf('#%06X', random_int(0, 0xFFFFFF));
        $alsoUser = (bool) ($data['also_user'] ?? false);
        unset($data['also_user']);

        $professional = Professional::query()->create(array_merge($data, [
            'uuid' => Uuid::v4(),
            'schedule_color' => $scheduleColor,
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));

        if ($alsoUser) {
            $this->createUserForProfessional($professional->uuid, $actor);
            $professional->refresh();
        }

        $this->audit->log('professionals.created', $actor, ['professional_uuid' => $professional->uuid]);
        return $professional;
    }

    public function getProfessional(string $uuid): Professional
    {
        /** @var Professional|null $professional */
        $professional = Professional::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($professional === null) {
            throw new HttpException('Profissional não encontrado.', 404);
        }

        return $professional;
    }

    public function updateProfessional(string $uuid, array $data, string $actor): Professional
    {
        $professional = $this->getProfessional($uuid);

        if (array_key_exists('cpf', $data)) {
            $this->assertUnique('professionals', 'cpf', $data['cpf'], $uuid);
            $this->audit->log('professionals.cpf_changed', $actor, ['professional_uuid' => $uuid]);
        }

        if (array_key_exists('email', $data)) {
            $this->assertUnique('professionals', 'email', $data['email'], $uuid);
        }

        $this->assertValidCpf($data['cpf'] ?? $professional->cpf ?? null);
        $this->assertValidEmail($data['email'] ?? $professional->email ?? null);
        $this->assertValidPhone($data['phone'] ?? $professional->phone ?? null);

        $professional->fill($data);
        $professional->updated_at = date('Y-m-d H:i:s');
        $professional->save();
        $this->audit->log('professionals.updated', $actor, ['professional_uuid' => $uuid]);
        return $professional;
    }

    public function deleteProfessional(string $uuid, string $actor): void
    {
        $professional = $this->getProfessional($uuid);
        $professional->status = 'inactive';
        $professional->deleted_at = date('Y-m-d H:i:s');
        $professional->save();
        $this->audit->log('professionals.deleted', $actor, ['professional_uuid' => $uuid]);
    }

    public function createUserForProfessional(string $uuid, string $actor): Professional
    {
        $professional = $this->getProfessional($uuid);
        if (($professional->email ?? '') === '') {
            throw new HttpException('Profissional precisa ter e-mail para criar usuário.', 422);
        }

        if ($professional->user_uuid !== null) {
            throw new HttpException('Profissional já vinculado a usuário.', 422);
        }

        $existing = DB::table('users')
            ->where('login', $professional->email)
            ->orWhere('email', $professional->email)
            ->first();
        if ($existing !== null) {
            throw new HttpException('Já existe usuário com este e-mail/login.', 422);
        }

        $userUuid = Uuid::v4();
        DB::table('users')->insert([
            'uuid' => $userUuid,
            'name' => $professional->full_name,
            'login' => $professional->email,
            'email' => $professional->email,
            'password_hash' => password_hash('alterar123', PASSWORD_ARGON2ID),
            'status' => 'active',
            'professional_uuid' => $professional->uuid,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $roleUuid = DB::table('roles')->where('name', 'Profissional clínico')->value('uuid');
        if ($roleUuid !== null) {
            DB::table('user_roles')->insert([
                'uuid' => Uuid::v4(),
                'user_uuid' => $userUuid,
                'role_uuid' => $roleUuid,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $professional->user_uuid = $userUuid;
        $professional->updated_at = date('Y-m-d H:i:s');
        $professional->save();

        $this->audit->log(
            'professionals.user_linked',
            $actor,
            ['professional_uuid' => $uuid, 'user_uuid' => $userUuid]
        );

        return $professional;
    }

    public function listSuppliers(array $query): array
    {
        $qb = Supplier::query()->whereNull('deleted_at');
        return $this->applyListFilters($qb, $query, ['name_or_legal_name', 'document', 'email', 'phone']);
    }

    public function createSupplier(array $data, string $actor): Supplier
    {
        if (($data['name_or_legal_name'] ?? '') === '') {
            throw new HttpException('Nome/razão social é obrigatório.', 422);
        }

        $this->assertUnique('suppliers', 'document', $data['document'] ?? null);
        $this->assertValidCpfCnpj($data['document'] ?? null);
        $this->assertValidEmail($data['email'] ?? null);
        $this->assertValidPhone($data['phone'] ?? null);

        $supplier = Supplier::query()->create(array_merge($data, [
            'uuid' => Uuid::v4(),
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));

        $this->audit->log('suppliers.created', $actor, ['supplier_uuid' => $supplier->uuid]);
        return $supplier;
    }

    public function getSupplier(string $uuid): Supplier
    {
        /** @var Supplier|null $supplier */
        $supplier = Supplier::query()->where('uuid', $uuid)->whereNull('deleted_at')->first();
        if ($supplier === null) {
            throw new HttpException('Fornecedor não encontrado.', 404);
        }

        return $supplier;
    }

    public function updateSupplier(string $uuid, array $data, string $actor): Supplier
    {
        $supplier = $this->getSupplier($uuid);

        if (array_key_exists('document', $data)) {
            $this->assertUnique('suppliers', 'document', $data['document'], $uuid);
            $this->assertValidCpfCnpj($data['document']);
            $this->audit->log('suppliers.document_changed', $actor, ['supplier_uuid' => $uuid]);
        }

        $this->assertValidEmail($data['email'] ?? $supplier->email ?? null);
        $this->assertValidPhone($data['phone'] ?? $supplier->phone ?? null);

        $supplier->fill($data);
        $supplier->updated_at = date('Y-m-d H:i:s');
        $supplier->save();
        $this->audit->log('suppliers.updated', $actor, ['supplier_uuid' => $uuid]);
        return $supplier;
    }

    public function deleteSupplier(string $uuid, string $actor): void
    {
        $supplier = $this->getSupplier($uuid);
        $supplier->status = 'inactive';
        $supplier->deleted_at = date('Y-m-d H:i:s');
        $supplier->save();
        $this->audit->log('suppliers.deleted', $actor, ['supplier_uuid' => $uuid]);
    }

    private function applyListFilters($qb, array $query, array $fields): array
    {
        $search = trim((string) ($query['search'] ?? ''));
        if ($search !== '') {
            $qb->where(function ($q) use ($search, $fields): void {
                foreach ($fields as $i => $field) {
                    if ($i === 0) {
                        $q->where($field, 'like', '%' . $search . '%');
                    } else {
                        $q->orWhere($field, 'like', '%' . $search . '%');
                    }
                }
            });
        }

        if (($query['status'] ?? '') !== '') {
            $qb->where('status', $query['status']);
        }

        $sort = (string) ($query['sort'] ?? 'created_at');
        $direction = strtolower((string) ($query['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 15)));

        $total = (clone $qb)->count();
        $items = $qb->orderBy($sort, $direction)->forPage($page, $perPage)->get()->toArray();

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    private function validatePatient(array $data): void
    {
        if (($data['full_name'] ?? '') === '') {
            throw new HttpException('Nome completo é obrigatório.', 422);
        }
        if (($data['birth_date'] ?? '') === '') {
            throw new HttpException('Data de nascimento é obrigatória.', 422);
        }
        $this->assertValidCpf($data['cpf'] ?? null);
        $this->assertValidEmail($data['email'] ?? null);
        $this->assertValidPhone($data['phone_primary'] ?? null);
        $this->assertValidPhone($data['phone_secondary'] ?? null);
        $this->assertValidCep($data['address_zipcode'] ?? null);

        if (isset($data['status']) && ! in_array($data['status'], ['active', 'inactive'], true)) {
            throw new HttpException('Status inválido.', 422);
        }
    }

    private function assertUnique(string $table, string $field, ?string $value, ?string $exceptUuid = null): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $qb = DB::table($table)->where($field, $value);
        if ($exceptUuid !== null) {
            $qb->where('uuid', '!=', $exceptUuid);
        }

        if ($qb->first() !== null) {
            throw new HttpException('Valor duplicado para ' . $field . '.', 422);
        }
    }

    private function assertValidEmail(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException('E-mail inválido.', 422);
        }
    }

    private function assertValidPhone(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (! in_array(strlen($digits), [10, 11], true)) {
            throw new HttpException('Telefone inválido.', 422);
        }
    }

    private function assertValidCep(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) !== 8) {
            throw new HttpException('CEP inválido.', 422);
        }
    }

    private function assertValidCpf(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) !== 11) {
            throw new HttpException('CPF inválido.', 422);
        }
    }

    private function assertValidCpfCnpj(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (! in_array(strlen($digits), [11, 14], true)) {
            throw new HttpException('CPF/CNPJ inválido.', 422);
        }
    }
}
