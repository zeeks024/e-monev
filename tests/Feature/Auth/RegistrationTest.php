<?php

test('registration screen is not publicly accessible', function () {
    $this->get('/register')->assertNotFound();
});

test('guest cannot access registration page', function () {
    $this->get('/register')->assertNotFound();
});
