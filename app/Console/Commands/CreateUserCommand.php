<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('user:create {name} {email} {password}')]
#[Description('Create a user account, e.g. to provision the first account when registration is closed')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        $validator = Validator::make(
            [
                'name' => $this->argument('name'),
                'email' => $this->argument('email'),
                'password' => $this->argument('password'),
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', Password::defaults()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->info("Created user #{$user->id} ({$user->email}).");

        return self::SUCCESS;
    }
}
