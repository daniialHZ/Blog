<?php

test('register with valid auth code', function () {
    $email = 'test@example.com';

    // Step 1: Request the signup auth code (this will create the PasswordResetToken)
    $this->postJson('/api/signup-auth-code', [
        'email' => $email,
    ])->assertStatus(200);

    // Step 2: Retrieve the generated auth code from the database
    $authCode = \App\Models\PasswordResetToken::where('email', $email)->value('auth_code');

    // Ensure auth code exists
    expect($authCode)->not->toBeNull();

    // Step 3: Register using the auth code
    $response = $this->postJson('/api/register', [
        'email' => $email,
        'auth_code' => $authCode,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'password' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'user', 'token']);
});


test('login with correct credentials', function () {
    // Ensure the user exists before attempting to log in
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'test@example.com'],
        [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => bcrypt('password123'), // Store a hashed password
        ]
    );

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['message', 'user', 'token']);
});

test('reset password with valid auth code', function () {
    $email = 'test@example.com';

    // Step 1: Request the reset password auth code (this will create the PasswordResetToken)
    $this->postJson('/api/reset-password', [
        'email' => $email,
    ])->assertStatus(200);

    // Step 2: Retrieve the generated auth code from the database
    $authCode = \App\Models\PasswordResetToken::where('email', $email)->value('auth_code');

    // Ensure auth code exists
    expect($authCode)->not->toBeNull();

    // Step 3: Validate the auth code and reset password
    $response = $this->postJson('/api/validate-auth-code', [
        'email' => $email,
        'auth_code' => $authCode,
        'new_password' => 'newpassword123',
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Password reset successfully']);
});
