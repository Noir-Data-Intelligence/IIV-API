<?php

namespace Database\Factories;

use App\Models\Documento;
use App\Models\DocVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocVersion>
 */
class DocVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => Documento::factory(),
            'version_number' => 1,
            'file_path' => 'documents/'.fake()->uuid().'/v1/ficheiro.pdf',
            'file_name' => 'ficheiro.pdf',
            'file_size' => fake()->numberBetween(1000, 500000),
            'mime_type' => 'application/pdf',
            'change_notes' => 'Versão 1',
            'uploaded_by' => User::factory(),
            'created_at' => now(),
        ];
    }
}
