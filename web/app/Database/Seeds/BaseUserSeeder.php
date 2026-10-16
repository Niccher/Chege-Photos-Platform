<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Models\UserIdentityModel;

/**
 * Shared logic for seeding Shield users with a given group.
 *
 * Override the protected properties in a concrete seeder to define the
 * group, environment variable names, and fallback credentials.
 */
abstract class BaseUserSeeder extends Seeder
{
    protected string $group           = 'user';
    protected string $emailEnv        = 'ADMIN_EMAIL';
    protected string $passwordEnv     = 'ADMIN_PASSWORD';
    protected string $usernameEnv     = 'ADMIN_USERNAME';
    protected string $emailDefault    = 'admin@example.com';
    protected string $passwordDefault = 'Admin@2024!';
    protected string $usernameDefault = 'admin';

    public function run(): void
    {
        $username = env($this->usernameEnv, $this->usernameDefault);
        $email    = env($this->emailEnv, $this->emailDefault);
        $password = env($this->passwordEnv, $this->passwordDefault);

        if ($username === '' || $email === '' || $password === '') {
            echo "  Skipping group '{$this->group}': a credential env var is empty.\n";

            return;
        }

        /** @var UserModel $users */
        $users = model(UserModel::class);

        // If the username already exists, synchronize credentials and ensure group.
        $existing = $users->where('username', $username)->first();
        if ($existing !== null) {
            $existing->addGroup($this->group);
            $existing->activate();

            // Synchronize email and password from env / defaults
            $existing->email    = $email;
            $existing->password = $password;
            $users->skipValidation(true)->save($existing);

            // Also guarantee auth_identities record is synchronized
            $identityModel = model(UserIdentityModel::class);
            $identity      = $identityModel
                ->where('user_id', $existing->id)
                ->where('type', 'email_password')
                ->first();

            if ($identity !== null) {
                $identity->secret  = $email;
                $identity->secret2 = service('passwords')->hash($password);
                $identityModel->save($identity);
            } else {
                $identityModel->insert([
                    'user_id' => $existing->id,
                    'type'    => 'email_password',
                    'secret'  => $email,
                    'secret2' => service('passwords')->hash($password),
                ]);
            }

            echo "  User '{$username}' already exists — credentials synchronized and group '{$this->group}' ensured.\n";

            return;
        }

        // If the email is already registered, synchronize password and ensure group.
        $identityModel = model(UserIdentityModel::class);
        $identity      = $identityModel
            ->where('type', 'email_password')
            ->where('secret', $email)
            ->first();
        if ($identity !== null && !empty($identity->user_id)) {
            $emailUser = $users->findById($identity->user_id);
            if ($emailUser !== null) {
                $emailUser->addGroup($this->group);
                $emailUser->activate();
                $emailUser->password = $password;
                $users->skipValidation(true)->save($emailUser);

                $identity->secret2 = service('passwords')->hash($password);
                $identityModel->save($identity);

                echo "  User with email '{$email}' already exists — credentials synchronized and group '{$this->group}' ensured.\n";

                return;
            }
        }

        $user = $users->createNewUser([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
            'active'   => true,
        ]);

        $users->save($user);

        // Re-fetch so the entity carries the generated ID.
        $user = $users->findById($users->getInsertID());

        $user->addGroup($this->group);
        $user->activate();

        echo "  User '{$username}' created with group '{$this->group}'.\n";
    }
}
