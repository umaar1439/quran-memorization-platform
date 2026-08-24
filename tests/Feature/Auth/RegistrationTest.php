<?php

test('public self-registration is disabled — the register route does not exist', function () {
    $this->get('/register')->assertNotFound();
});

test('public self-registration submission is rejected', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

