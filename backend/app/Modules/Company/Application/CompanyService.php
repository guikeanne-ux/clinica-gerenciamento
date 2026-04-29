<?php

declare(strict_types=1);

namespace App\Modules\Company\Application;

use App\Core\Exceptions\HttpException;
use App\Core\Support\Uuid;
use App\Modules\Audit\Infrastructure\Services\AuditService;
use App\Modules\Company\Infrastructure\Models\Company;

final class CompanyService
{
    public function __construct(private readonly AuditService $auditService = new AuditService())
    {
    }

    public function getOrCreate(): Company
    {
        $company = Company::query()->first();
        if ($company instanceof Company) {
            return $company;
        }

        return Company::query()->create([
            'uuid' => Uuid::v4(),
            'timezone' => 'America/Sao_Paulo',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function update(array $data, string $actorUserUuid): Company
    {
        $document = (string) ($data['document'] ?? '');
        $email = (string) ($data['email'] ?? '');
        $phone = (string) ($data['phone'] ?? '');

        if ($document !== '' && ! $this->isValidCpfCnpj($document)) {
            throw new HttpException(
                'Documento inválido.',
                422,
                [['field' => 'document', 'message' => 'CPF/CNPJ inválido.']]
            );
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException('E-mail inválido.', 422, [['field' => 'email', 'message' => 'E-mail inválido.']]);
        }

        if (
            $phone !== '' &&
            ! preg_match('/^\(?\d{2}\)?\s?\d{4,5}\-?\d{4}$/', preg_replace('/\s+/', '', $phone))
        ) {
            throw new HttpException(
                'Telefone inválido.',
                422,
                [['field' => 'phone', 'message' => 'Telefone inválido.']]
            );
        }

        $company = $this->getOrCreate();
        $payload = $data;
        $payload['timezone'] = $data['timezone'] ?? 'America/Sao_Paulo';
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $company->fill($payload);
        $company->save();

        $this->auditService->log(
            'company.updated',
            $actorUserUuid,
            ['company_uuid' => $company->uuid]
        );

        return $company;
    }

    private function isValidCpfCnpj(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        return in_array(strlen($digits), [11, 14], true);
    }
}
