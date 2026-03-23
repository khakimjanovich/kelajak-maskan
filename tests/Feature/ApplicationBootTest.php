<?php

it('boots the application', function (): void {
    $this->get('/')->assertOk();
});
