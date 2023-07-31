<?php

namespace App\Service;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    private $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function verifyPassword($user, $oldPassword)
    {
        return $this->hasher->isPasswordValid($user, $oldPassword);
    }
}
