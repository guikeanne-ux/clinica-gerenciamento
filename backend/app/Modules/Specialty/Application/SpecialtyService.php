<?php

declare(strict_types=1);

namespace App\Modules\Specialty\Application;

use App\Core\Exceptions\ConflictException;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Support\Uuid;
use App\Modules\Specialty\Infrastructure\Models\Specialty;

final class SpecialtyService
{
    public function list(array $query): array
    {
        $qb = Specialty::query();

        if (($query['status'] ?? '') !== '') {
            $qb->where('status', $query['status']);
        }

        $search = trim((string) ($query['search'] ?? ''));
        if ($search !== '') {
            $qb->where('name', 'like', '%' . $search . '%');
        }

        return $qb->orderBy('name')->get()->toArray();
    }

    public function create(array $data): Specialty
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new ValidationException(
                'Não foi possível salvar a especialidade.',
                [['field' => 'name', 'message' => 'Nome da especialidade é obrigatório.']]
            );
        }

        $exists = Specialty::query()->where('name', $name)->first();
        if ($exists !== null) {
            throw new ConflictException('Especialidade já cadastrada.');
        }

        return Specialty::query()->create([
            'uuid' => Uuid::v4(),
            'name' => $name,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function update(string $uuid, array $data): Specialty
    {
        /** @var Specialty|null $specialty */
        $specialty = Specialty::query()->where('uuid', $uuid)->first();
        if ($specialty === null) {
            throw new NotFoundException('Especialidade não encontrada.');
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new ValidationException(
                'Não foi possível atualizar a especialidade.',
                [['field' => 'name', 'message' => 'Nome da especialidade é obrigatório.']]
            );
        }

        $conflict = Specialty::query()->where('name', $name)->where('uuid', '!=', $uuid)->first();
        if ($conflict !== null) {
            throw new ConflictException('Já existe outra especialidade com este nome.');
        }

        $specialty->name = $name;
        $specialty->updated_at = date('Y-m-d H:i:s');
        $specialty->save();

        return $specialty;
    }

    public function delete(string $uuid): void
    {
        /** @var Specialty|null $specialty */
        $specialty = Specialty::query()->where('uuid', $uuid)->first();
        if ($specialty === null) {
            throw new NotFoundException('Especialidade não encontrada.');
        }

        $specialty->delete();
    }
}
