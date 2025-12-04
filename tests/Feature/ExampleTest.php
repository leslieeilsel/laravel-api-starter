<?php

test('the application redirects to status page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/status');
});

test('the status page returns server available', function () {
    $response = $this->get('/status');

    $response->assertOk()
        ->assertJson([
            'status' => 'available',
            'message' => 'Server Available',
        ]);
});
