<?php

test('the application returns server available', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertJson([
            'status' => 'available',
            'message' => 'Server Available',
        ]);
});
