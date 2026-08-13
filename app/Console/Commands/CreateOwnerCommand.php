<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateOwnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:owner {--name= : Name of the Owner} {--email= : Email address} {--phone= : Phone number} {--password= : Secure password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an Owner account safely without hardcoded default passwords';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('--- SELON BEAUTY Attendance: Owner Account Creator ---');

        $name = $this->option('name') ?: $this->ask('Masukkan Nama Lengkap Owner');
        $email = $this->option('email');
        $phone = $this->option('phone');

        if (! $email && ! $phone) {
            $email = $this->ask('Masukkan Email Owner (opsional)');
            $phone = $this->ask('Masukkan Nomor HP Owner (opsional)');
        }

        $password = $this->option('password') ?: $this->secret('Masukkan Password Baru (min 8 karakter)');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email tersebut sudah digunakan oleh akun lain.',
            'phone.unique' => 'Nomor HP sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return Command::FAILURE;
        }

        if (empty($email) && empty($phone)) {
            $this->error('Gagal: Wajib memberikan Email atau Nomor HP untuk login Owner.');

            return Command::FAILURE;
        }

        $owner = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->info("Berhasil! Akun Owner ID #{$owner->id} [{$owner->name}] telah dibuat.");

        return Command::SUCCESS;
    }
}
