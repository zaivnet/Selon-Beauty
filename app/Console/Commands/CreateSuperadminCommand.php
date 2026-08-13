<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperadminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:superadmin {--name= : Name of the Superadmin} {--email= : Email address} {--phone= : Phone number} {--password= : Secure password}';

    /**
     * Aliases for the command.
     *
     * @var array<int, string>
     */
    protected $aliases = ['selon:create-superadmin'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Superadmin account safely without hardcoded default passwords';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('--- SELON BEAUTY Attendance: Superadmin Account Creator ---');

        $name = $this->option('name') ?: $this->ask('Masukkan Nama Lengkap Superadmin');
        $email = $this->option('email') ?: $this->ask('Masukkan Email Superadmin');
        $phone = $this->option('phone') ?: $this->ask('Masukkan Nomor HP Superadmin (opsional)');
        $password = $this->option('password') ?: $this->secret('Masukkan Password Baru (min 8 karakter)');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
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

        $superadmin = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->info("Berhasil! Akun Superadmin ID #{$superadmin->id} [{$superadmin->name}] ({$superadmin->email}) telah dibuat.");

        return Command::SUCCESS;
    }
}
