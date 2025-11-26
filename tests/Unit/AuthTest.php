<?php

namespace Tests\Unit;

use Tests\TestCase;
use AuthService;
use UserRepository;
use MailService;
use RememberTokenRepository;
use PHPUnit\Framework\MockObject\MockObject;

class AuthTest extends TestCase
{
    private AuthService $authService;
    private UserRepository $userRepository;
    private MockObject $mailService;
    private MockObject $rememberTokenRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository($this->db);
        $this->mailService = $this->createMock(MailService::class);
        $this->rememberTokenRepository = $this->createMock(RememberTokenRepository::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->mailService,
            $this->rememberTokenRepository
        );
    }

    public function test_register_creates_new_user(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $role = 'student';
        $name = 'Test User';

        $this->mailService->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($email),
                $this->stringContains('Verify'),
                $this->anything(),
                $this->anything()
            );

        $user = $this->authService->register($email, $password, $role, $name);

        $this->assertNotNull($user['id']);
        $this->assertEquals($email, $user['email']);
        $this->assertEquals($role, $user['role']);
        $this->assertEquals('unverified', $user['status']);
        $this->assertNotNull($user['verification_token']);
        
        // Verify password hash
        $dbUser = $this->userRepository->findById($user['id']);
        $this->assertTrue(password_verify($password, $dbUser['password_hash']));
    }

    public function test_register_fails_if_email_exists(): void
    {
        $email = 'existing@example.com';
        $this->userRepository->create([
            'name' => 'Existing User',
            'email' => $email,
            'password_hash' => 'hash',
            'role' => 'student'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email already registered');

        $this->authService->register($email, 'password', 'student', 'New User');
    }

    public function test_login_success(): void
    {
        $email = 'login@example.com';
        $password = 'password123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->userRepository->create([
            'name' => 'Login User',
            'email' => $email,
            'password_hash' => $hash,
            'role' => 'student',
            'status' => 'active'
        ]);

        $user = $this->authService->login($email, $password);

        $this->assertEquals($email, $user['email']);
    }

    public function test_login_fails_wrong_password(): void
    {
        $email = 'wrongpass@example.com';
        $password = 'password123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->userRepository->create([
            'name' => 'User',
            'email' => $email,
            'password_hash' => $hash,
            'role' => 'student',
            'status' => 'active'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login($email, 'wrongpassword');
    }

    public function test_login_fails_unverified(): void
    {
        $email = 'unverified@example.com';
        $password = 'password123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->userRepository->create([
            'name' => 'Unverified User',
            'email' => $email,
            'password_hash' => $hash,
            'role' => 'student',
            'status' => 'unverified'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please verify your email address');

        $this->authService->login($email, $password);
    }
}
