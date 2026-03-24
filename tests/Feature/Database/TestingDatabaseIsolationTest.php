<?php

it('uses in-memory sqlite for feature tests', function (): void {
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
});
