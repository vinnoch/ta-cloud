<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ImportUserController extends Controller
{
    use BuildsKaprodiPage;
    public function showDosen(): View
    {
        return view('kaprodi.import.dosen', $this->page('Import Dosen', 'KAPRODI • IMPORT DOSEN', [
            'templateName' => 'template_dosen.csv',
            'requiredColumns' => ['name', 'email'],
            'optionalColumns' => ['nidn_nip', 'password'],
            'sampleRows' => [
                ['name' => 'Dr. Sarah Wijaya', 'nidn_nip' => '0412345678', 'email' => 'sarah.wijaya@kampus.ac.id', 'password' => 'password123'],
            ],
        ]));
    }

    public function importDosen(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handleImport($request, 'dosen');
    }

    public function showMahasiswa(): View
    {
        return view('kaprodi.import.mahasiswa', $this->page('Import Mahasiswa', 'KAPRODI • IMPORT MAHASISWA', [
            'templateName' => 'template_mahasiswa.csv',
            'requiredColumns' => ['name', 'email', 'nim'],
            'optionalColumns' => ['password'],
            'sampleRows' => [
                ['name' => 'Adrian Sterling', 'email' => 'adrian@kampus.ac.id', 'nim' => '2021004592', 'password' => 'password123'],
            ],
        ]));
    }

    public function importMahasiswa(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handleImport($request, 'mahasiswa');
    }

    private function handleImport(Request $request, string $role): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'catatan' => ['nullable', 'string'],
        ], [
            'file.required' => 'Silakan pilih file CSV terlebih dahulu.',
            'file.file' => 'File import tidak valid.',
            'file.mimes' => 'Format file harus CSV.',
            'file.max' => 'Ukuran file melebihi 2 MB.',
        ]);

        $rows = $this->readCsvRows($request->file('file')->getRealPath());

        if ($rows === []) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'File CSV kosong atau tidak memiliki data.'], 422);
            }
            return back()->withErrors(['file' => 'File CSV kosong atau tidak memiliki data.'])->withInput();
        }

        $headers = array_map([$this, 'normalizeHeader'], array_shift($rows));
        $required = $role === 'mahasiswa' ? ['name', 'email', 'nim'] : ['name', 'email'];
        $missingHeaders = array_values(array_diff($required, $headers));

        if ($missingHeaders !== []) {
            $message = 'Header wajib tidak lengkap: ' . implode(', ', $missingHeaders);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->withErrors([
                'file' => $message,
            ])->withInput();
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rowNumber = $index + 2;
            $payload = $this->mapRowToPayload($headers, $row);

            $validator = Validator::make($payload, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'nim' => $role === 'mahasiswa' ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
                'nidn_nip' => $role === 'dosen' ? ['nullable', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = 'Baris ' . $rowNumber . ': ' . $validator->errors()->first();
                continue;
            }

            $data = $validator->validated();
            $emailOwner = User::query()->where('email', $data['email'])->first();
            $nimOwner = $role === 'mahasiswa' && !empty($data['nim'])
                ? User::query()->where('nim', $data['nim'])->when($emailOwner, fn ($q) => $q->whereKeyNot($emailOwner->id))->first()
                : null;
            $nidnOwner = $role === 'dosen' && !empty($data['nidn_nip'])
                ? User::query()->where('nidn_nip', $data['nidn_nip'])->when($emailOwner, fn ($q) => $q->whereKeyNot($emailOwner->id))->first()
                : null;

            if ($nimOwner) {
                $skipped++;
                $errors[] = 'Baris ' . $rowNumber . ': NIM sudah dipakai user lain (' . $data['nim'] . ').';
                continue;
            }

            if ($nidnOwner) {
                $skipped++;
                $errors[] = 'Baris ' . $rowNumber . ': NIDN/NIP sudah dipakai user lain (' . $data['nidn_nip'] . ').';
                continue;
            }

            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $role,
            ];

            if ($role === 'mahasiswa') {
                $attributes['nim'] = $data['nim'];
                $attributes['nidn_nip'] = null;
            } else {
                $attributes['nim'] = null;
                $attributes['nidn_nip'] = $data['nidn_nip'] ?? null;
            }

            if (!empty($data['password'])) {
                $attributes['password'] = Hash::make($data['password']);
            } elseif (!$emailOwner) {
                $attributes['password'] = Hash::make('password123');
            }

            if ($emailOwner) {
                $emailOwner->fill($attributes);
                $emailOwner->save();
                $updated++;
            } else {
                User::query()->create($attributes);
                $created++;
            }
        }

        $summary = [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => ucfirst($role) . ' import selesai.',
                'summary' => $summary,
            ]);
        }

        return back()->with('success', ucfirst($role) . ' import selesai.')
            ->with('importSummary', $summary);
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            if ($rows === [] && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeHeader(?string $header): string
    {
        return strtolower(trim((string) $header));
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     * @return array<string, string|null>
     */
    private function mapRowToPayload(array $headers, array $row): array
    {
        $payload = [];

        foreach ($headers as $index => $header) {
            $payload[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $payload;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        return $this->kaprodiPage($heading, $crumbs, $extra);
    }
}
