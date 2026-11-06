<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/** Admins can never self-register; they are created here. */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email?} {--name=}';

    protected $description = 'Create (or promote) a platform administrator account';

    public function handle(): int
    {
        $email = $this->argument('email') ?? text('Admin email', required: true);
        $name = $this->option('name') ?? text('Full name', required: true);
        $pass = password('Password (min 12 characters)', required: true);

        $validator = Validator::make(compact('email', 'name', 'pass'), [
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:120'],
            'pass' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => mb_strtolower($email)]);
        $user->fill(['name' => $name, 'password' => $pass]);
        $user->forceFill(['role' => Role::Admin, 'status' => UserStatus::Active, 'email_verified_at' => now()])->save();

        $this->info("Admin ready: {$user->email}. They'll be asked to enable two-factor authentication on first login in production.");

        return self::SUCCESS;
    }
}
