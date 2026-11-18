<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Enums\Locale;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates a Super Admin account without embedding passwords in database seeders (Module-11 §17.11-A).
 */
class CreateAdminUserCommand extends Command
{
    protected $signature = 'rafiq:admin:create
        {--name= : Admin display name}
        {--email= : Admin email address}
        {--password= : Password (at least 12 characters)}
        {--locale=ar : Preferred interface language (ar or en)}';

    protected $description = 'Create an initial Super Admin account for Rafiq Admin';

    public function handle(AuditLogger $audit): int
    {
        $data = [
            'name' => (string) ($this->option('name') ?? ''),
            'email' => strtolower(trim((string) ($this->option('email') ?? ''))),
            'password' => (string) ($this->option('password') ?? ''),
            'locale' => (string) ($this->option('locale') ?? 'ar'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'locale' => ['required', 'in:'.implode(',', Locale::values())],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($data, $audit): void {
            Role::ensureAllExist();

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'preferred_locale' => $data['locale'],
                'is_active' => true,
            ]);

            $user->grantRole(AdminRole::SuperAdmin);

            $audit->record('user.created', $user, [
                'email' => $user->email,
                'source' => 'cli',
            ]);

            $audit->record('role.granted', $user, [
                'role' => AdminRole::SuperAdmin->value,
                'source' => 'cli',
            ]);
        });

        $this->components->info("Super Admin [{$data['email']}] created. 2FA setup will be required on first login.");

        return self::SUCCESS;
    }
}
